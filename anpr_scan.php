<?php
/* ============================================================
   AUTOMATIC NUMBER PLATE RECOGNITION (ANPR) OCR SCANNER SERVICE
   ============================================================ */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Ensure session is closed so requests never block Apache workers
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$tempDir = __DIR__ . '/captures';
if (!is_dir($tempDir)) {
    @mkdir($tempDir, 0777, true);
}

$tempFile = $tempDir . '/anpr_temp_' . time() . '_' . rand(1000, 9999) . '.jpg';

try {
    $hasImage = false;

    // METHOD 1: Base64 image payload from browser canvas (Instant, 0 network fetch overhead)
    $inputData = file_get_contents('php://input');
    $postJson = json_decode($inputData, true);
    $base64Image = $_POST['image'] ?? ($postJson['image'] ?? '');

    if (!empty($base64Image)) {
        if (strpos($base64Image, 'base64,') !== false) {
            $base64Image = explode('base64,', $base64Image)[1];
        }
        $decoded = base64_decode($base64Image);
        if ($decoded !== false && strlen($decoded) > 100) {
            file_put_contents($tempFile, $decoded);
            $hasImage = true;
        }
    }

    // METHOD 2: Fetch snapshot frame directly from camera if URL is provided or camera index given
    if (!$hasImage && (!empty($_GET['url']) || !empty($_POST['url']))) {
        $camUrl  = trim($_GET['url'] ?? $_POST['url']);
        $camUser = trim($_GET['user'] ?? $_POST['user'] ?? 'admin');
        $camPass = trim($_GET['pass'] ?? $_POST['pass'] ?? '');

        if (strcasecmp($camUser, 'admin') === 0) {
            $camUser = 'admin';
        }

        // Direct Fast Frame Capture:
        // If camera URL is an explicit rtsp:// stream, use Python grabber
        if (strpos($camUrl, 'rtsp://') === 0) {
            $pythonExe = 'C:\\Python314\\python.exe';
            if (!file_exists($pythonExe)) {
                $pythonExe = 'python';
            }
            $grabberPy = __DIR__ . '/rtsp_grabber.py';
            if (file_exists($grabberPy)) {
                $grabCmd = escapeshellarg($pythonExe) . ' ' . escapeshellarg($grabberPy) . ' ' . escapeshellarg($camUrl) . ' ' . escapeshellarg($tempFile);
                $res = @shell_exec($grabCmd);
                if (trim($res) === 'SUCCESS' && file_exists($tempFile) && filesize($tempFile) > 1000) {
                    $hasImage = true;
                }
            }
        }

        // Direct Native HTTP/MJPEG Capture (< 1 second, pure uncorrupted JPEG frame)
        if (!$hasImage) {
            if (!preg_match('#^https?://#i', $camUrl)) {
                $camUrl = 'http://' . $camUrl;
            }

            $isMjpeg = (stripos($camUrl, 'mjpg') !== false || stripos($camUrl, 'video.cgi') !== false);
            if ($isMjpeg) {
                $buffer = '';
                $ch = curl_init($camUrl);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, "$camUser:$camPass");
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($c, $chunk) use (&$buffer) {
                    $buffer .= $chunk;
                    $start = strpos($buffer, "\xFF\xD8");
                    if ($start !== false) {
                        $end = strpos($buffer, "\xFF\xD9", $start + 2);
                        if ($end !== false) return 0;
                    }
                    return strlen($chunk);
                });
                curl_exec($ch);
                curl_close($ch);

                $start = strpos($buffer, "\xFF\xD8");
                $end   = ($start !== false) ? strpos($buffer, "\xFF\xD9", $start + 2) : false;
                if ($start !== false && $end !== false) {
                    $jpeg = substr($buffer, $start, $end - $start + 2);
                    file_put_contents($tempFile, $jpeg);
                    $hasImage = true;
                }
            } else {
                $ch = curl_init($camUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, "$camUser:$camPass");
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                $img = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($img && $httpCode >= 200 && $httpCode < 300) {
                    file_put_contents($tempFile, $img);
                    $hasImage = true;
                }
            }
        }
    }

    if (!$hasImage || !file_exists($tempFile) || filesize($tempFile) < 200) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'No valid camera frame captured'
        ]);
        exit;
    }

    // Execute Windows Native Hardware-Accelerated OCR
    $ocrScript = __DIR__ . '/ocr_engine.ps1';
    $cmd = "powershell -ExecutionPolicy Bypass -NoProfile -File " . escapeshellarg($ocrScript) . " -ImagePath " . escapeshellarg($tempFile);
    $output = shell_exec($cmd);

    // Clean up temporary snapshot file
    @unlink($tempFile);

    $ocrJson = null;
    if ($output) {
        // Strip non-printable ASCII control characters (U+0000..U+001F) that break json_decode
        $cleanOutput = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $output);
        if (preg_match('/\{[\s\S]*\}/', $cleanOutput, $jm)) {
            $ocrJson = json_decode($jm[0], true);
        }
        if (!$ocrJson) {
            $ocrJson = json_decode(trim($cleanOutput), true);
        }
    }

    if (!$ocrJson || $ocrJson['status'] !== 'success') {
        echo json_encode([
            'status'   => 'no_plate',
            'raw_text' => $ocrJson['message'] ?? (trim($output) ?: 'OCR engine error')
        ], JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    $rawText = $ocrJson['text'] ?? '';
    $lines   = $ocrJson['lines'] ?? [];

    // Analyze extracted text and search for Indian / standard vehicle registration number patterns
    $detectedPlate = extractVehicleNumber($rawText, $lines);

    if (!empty($detectedPlate)) {
        echo json_encode([
            'status'   => 'success',
            'plate'    => $detectedPlate,
            'raw_text' => $rawText
        ], JSON_INVALID_UTF8_SUBSTITUTE);
    } else {
        echo json_encode([
            'status'   => 'no_plate',
            'raw_text' => $rawText
        ], JSON_INVALID_UTF8_SUBSTITUTE);
    }
} catch (Exception $e) {
    if (file_exists($tempFile)) @unlink($tempFile);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_INVALID_UTF8_SUBSTITUTE);
}

/**
 * Robust Vehicle License Plate Extraction Function
 * Standard Indian Formats:
 * - TN 01 AB 1234, TN01AB1234, MH 12 DE 1432, KA 04 MP 9999
 * - State codes: 2 letters, RTO code: 1-2 digits, Series: 1-2 letters (A-Z or AA-ZZ), Number: strictly 4 digits
 * - Bharat (BH) Series: 22 BH 1234 AA
 * - Eliminates stray hologram/emblem artifacts (e.g. Ashok Chakra/IND symbol read as 'S' or noise)
 */
function extractVehicleNumber($fullText, $lines = []) {
    $candidates = [];

    // 1. Single lines
    foreach ($lines as $l) {
        $lTrim = trim($l);
        if (!empty($lTrim)) $candidates[] = $lTrim;
    }

    // 2. Adjacent line pairs (handles plates split across two lines e.g. TN 82 on line 1, Y 8388 on line 2)
    $lineCount = count($lines);
    for ($i = 0; $i < $lineCount - 1; $i++) {
        $candidates[] = trim($lines[$i]) . ' ' . trim($lines[$i + 1]);
    }

    // 3. Full text
    if (!empty($fullText)) {
        $candidates[] = $fullText;
    }

    // Standard Indian Vehicle Registration Pattern:
    // 2 state letters + 1-2 RTO digits + 1-2 series letters + optional delimiter/stray noise/emblem + strictly 4 number digits
    // (In India, vehicle series are strictly 1-2 letters: A-Z or AA-ZZ. 3-letter series do not exist in state registrations)
    $pattern = '/\b([A-Z]{2})\s*[-.]?\s*([0-9]{1,2})\s*[-.]?\s*([A-Z]{1,2})\b(?:\s*[^0-9A-Z\s]?|\s+[A-Z]\s+|\s*[-.]?\s*)\s*([0-9]{4})\b/i';

    // Bharat (BH) Series Pattern: 2 digits + BH + 4 digits + 1-2 letters
    $patternBH = '/\b([0-9]{2})\s*[-.]?\s*(BH)\s*[-.]?\s*([0-9]{4})\s*[-.]?\s*([A-Z]{1,2})\b/i';

    foreach ($candidates as $c) {
        $cUpper = strtoupper(trim($c));
        // Normalize known OCR state character optical misread: TU -> TN (TU does not exist in India)
        $cUpper = preg_replace('/\bTU\s*(\d{1,2})/i', 'TN$1', $cUpper);

        if (preg_match($pattern, $cUpper, $m)) {
            $state  = $m[1];
            $rto    = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $series = $m[3];
            $num    = $m[4];
            return $state . $rto . $series . $num;
        }

        if (preg_match($patternBH, $cUpper, $m)) {
            return $m[1] . 'BH' . $m[3] . $m[4];
        }
    }

    // Secondary scan on stripped clean text:
    $sanitized = preg_replace('/[^A-Z0-9]/', '', strtoupper($fullText));
    // Normalize optical character misread of state code: TU -> TN
    $sanitized = preg_replace('/^TU(\d{2})/', 'TN$1', $sanitized);
    
    // Exact 1-2 letter series followed directly by 4 digits
    if (preg_match('/([A-Z]{2})([0-9]{1,2})([A-Z]{1,2})([0-9]{4})/', $sanitized, $m)) {
        $rto = str_pad($m[2], 2, '0', STR_PAD_LEFT);
        return $m[1] . $rto . $m[3] . $m[4];
    }

    // Extra stray single letter between a 2-letter series and 4 digits (e.g. TN 37 EY [S] 7399 -> TN37EYS7399 or EYL -> EY)
    if (preg_match('/([A-Z]{2})([0-9]{1,2})([A-Z]{2})[A-Z]([0-9]{4})/', $sanitized, $m)) {
        $rto = str_pad($m[2], 2, '0', STR_PAD_LEFT);
        return $m[1] . $rto . $m[3] . $m[4];
    }

    return null;
}

