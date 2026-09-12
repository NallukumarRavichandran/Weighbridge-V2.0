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

        // Sub-step A: Try Full HD 1080P RTSP capture via rtsp_grabber.py (Port 554)
        // Independent of browser HTTP socket limits, providing 1920x1080 resolution for long-range plates
        $pythonExe = 'C:\\Python314\\python.exe';
        if (!file_exists($pythonExe)) {
            $pythonExe = 'python';
        }
        $grabberPy = __DIR__ . '/rtsp_grabber.py';

        if (file_exists($grabberPy)) {
            $rtspUrl = '';
            if (strpos($camUrl, 'rtsp://') === 0) {
                $rtspUrl = $camUrl;
            } else {
                $host = parse_url($camUrl, PHP_URL_HOST);
                if (!$host && preg_match('#(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})#', $camUrl, $m)) {
                    $host = $m[1];
                }
                $channel = 1;
                if (preg_match('#channel=(\d+)#i', $camUrl, $cm)) {
                    $channel = $cm[1];
                }
                if ($host) {
                    $rtspUrl = "rtsp://{$camUser}:{$camPass}@{$host}:554/cam/realmonitor?channel={$channel}&subtype=0";
                }
            }

            if (!empty($rtspUrl)) {
                $grabCmd = escapeshellarg($pythonExe) . ' ' . escapeshellarg($grabberPy) . ' ' . escapeshellarg($rtspUrl) . ' ' . escapeshellarg($tempFile);
                $res = @shell_exec($grabCmd);
                if (trim($res) === 'SUCCESS' && file_exists($tempFile) && filesize($tempFile) > 1000) {
                    $hasImage = true;
                }
            }
        }

        // Sub-step B: Fallback to existing MJPEG / HTTP capture if RTSP not available or failed
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
 * - State codes: 2 letters, RTO code: 1-2 digits, Series: 1-3 letters, Number: 1-4 digits
 * - Bharat (BH) Series: 22 BH 1234 AA
 */
function extractVehicleNumber($fullText, $lines = []) {
    $candidates = array_merge($lines, [$fullText]);

    // Common OCR digit confusion map (e.g. B->8, J->8, O->0, I->1, S->5, Z->2)
    $digitMap = ['O' => '0', 'D' => '0', 'I' => '1', 'L' => '1', 'Z' => '2', 'S' => '5', 'B' => '8', 'J' => '8'];

    // Regex 1: Standard Indian Registration with OCR confusion allowance
    $patternStrict = '/\b([A-Z]{1,2})\s*[-.]?\s*([0-9OIZSBDJ]{1,2})\s*[-.]?\s*([A-Z]{1,3})\s*[-.]?\s*([0-9OIZSBDJ]{3,4})\b/i';

    // Regex 2: Indian BH Series (e.g. 22 BH 1234 AA)
    $patternBH = '/\b([0-9OIZSBDJ]{2})\s*[-.]?\s*(BH)\s*[-.]?\s*([0-9OIZSBDJ]{4})\s*[-.]?\s*([A-Z]{1,2})\b/i';

    // Regex 3: Compact format (e.g. TN22A1234 or KA041234 without alpha series)
    $patternCompact = '/\b([A-Z]{1,2})\s*[-.]?\s*([0-9OIZSBDJ]{1,2})\s*[-.]?\s*([0-9OIZSBDJ]{4})\b/i';

    foreach ($candidates as $c) {
        $cUpper = strtoupper(trim($c));

        // Test Regex 1 (Standard Indian Plate)
        if (preg_match($patternStrict, $cUpper, $matches)) {
            $state  = $matches[1];
            // Recover single-character clipped state codes from plate borders/brackets
            if ($state === 'N') $state = 'TN';
            if ($state === 'A') $state = 'KA';
            if ($state === 'L') $state = 'KL';
            if ($state === 'H') $state = 'MH';

            $rtoRaw = strtr($matches[2], $digitMap);
            $rto    = str_pad($rtoRaw, 2, '0', STR_PAD_LEFT);
            $series = $matches[3];
            $numRaw = strtr($matches[4], $digitMap);
            return $state . $rto . $series . $numRaw;
        }

        // Test Regex 2 (BH Series)
        if (preg_match($patternBH, $cUpper, $matches)) {
            $yr  = strtr($matches[1], $digitMap);
            $num = strtr($matches[3], $digitMap);
            return $yr . 'BH' . $num . $matches[4];
        }

        // Test Regex 3 (Compact without series)
        if (preg_match($patternCompact, $cUpper, $matches)) {
            $state  = $matches[1];
            if ($state === 'N') $state = 'TN';
            if ($state === 'A') $state = 'KA';
            if ($state === 'L') $state = 'KL';
            if ($state === 'H') $state = 'MH';

            $rtoRaw = strtr($matches[2], $digitMap);
            $rto    = str_pad($rtoRaw, 2, '0', STR_PAD_LEFT);
            $numRaw = strtr($matches[3], $digitMap);
            return $state . $rto . $numRaw;
        }
    }

    // Secondary scan: Remove common OCR misidentifications and search stripped text
    $sanitized = preg_replace('/[^A-Z0-9]/', '', strtoupper($fullText));
    if (preg_match('/([A-Z]{1,2})([0-9OIZSBDJ]{2})([A-Z]{1,3})([0-9OIZSBDJ]{4})/', $sanitized, $m)) {
        $state = $m[1];
        if ($state === 'N') $state = 'TN';
        if ($state === 'A') $state = 'KA';
        if ($state === 'L') $state = 'KL';
        if ($state === 'H') $state = 'MH';

        $rto = strtr($m[2], $digitMap);
        $num = strtr($m[4], $digitMap);
        return $state . $rto . $m[3] . $num;
    }

    return null;
}
