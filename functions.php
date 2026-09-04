<?php
// 1. Force Indian Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');

/* ============================================================
   AUTOMATIC SCHEMA MIGRATION & REPAIR GUARD
   If company_id is missing in any table, it creates it automatically!
   ============================================================ */

function ensureSchemaIntegrity($conn) {
    static $executed = false;
    if ($executed) return;
    $executed = true;

    $tables = ['weighments', 'sweighment', 'weighment_fields'];

    foreach ($tables as $table) {
        // Check if table exists
        $tblCheck = $conn->query("SHOW TABLES LIKE '$table'");
        if ($tblCheck && $tblCheck->num_rows > 0) {
            // Check if company_id column exists
            $colCheck = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'company_id'");
            if ($colCheck && $colCheck->num_rows === 0) {
                // Automatically add the missing company_id column with default value 1
                $conn->query("ALTER TABLE `$table` ADD COLUMN `company_id` INT NOT NULL DEFAULT 1");
            }
        }
    }
}

/* ============================================================
   COMPANY FUNCTIONS (UNIVERSAL ENTITY RESOLVER)
   ============================================================ */

function getCompany($conn) {
    // Automatically repair schema if missing columns exist
    ensureSchemaIntegrity($conn);

    $q = $conn->query("SELECT * FROM company LIMIT 1");
    if (!$q || $q->num_rows === 0) {
        return null;
    }
    
    $row = $q->fetch_assoc();

    // Universal ID resolution
    if (!isset($row['id']) || empty($row['id'])) {
        if (!empty($row['company_id'])) {
            $row['id'] = (int)$row['company_id'];
        } elseif (!empty($row['ID'])) {
            $row['id'] = (int)$row['ID'];
        } else {
            $firstVal = reset($row);
            $row['id'] = (is_numeric($firstVal) && (int)$firstVal > 0) ? (int)$firstVal : 1;
        }
    } else {
        $row['id'] = (int)$row['id'];
    }

    return $row;
}

function saveCompany($conn, $data) {
    $company_name    = strtoupper(trim($data['company_name'] ?? ''));
    $company_address = strtoupper(trim($data['company_address'] ?? ''));
    $gst_number      = strtoupper(trim($data['gst_number'] ?? ''));
    $phone           = trim($data['phone'] ?? '');
    $email           = trim($data['email'] ?? '');

    $stmt = $conn->prepare("
        INSERT INTO company
        (company_name, company_address, gst_number, phone, email)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssss",
        $company_name,
        $company_address,
        $gst_number,
        $phone,
        $email
    );

    return $stmt->execute();
}

function updateCompany($conn, $data, $company_id) {
    $company_name    = strtoupper(trim($data['company_name'] ?? ''));
    $company_address = strtoupper(trim($data['company_address'] ?? ''));
    $gst_number      = strtoupper(trim($data['gst_number'] ?? ''));
    $phone           = trim($data['phone'] ?? '');
    $email           = trim($data['email'] ?? '');

    $stmt = $conn->prepare("
        UPDATE company
        SET company_name = ?,
            company_address = ?,
            gst_number = ?,
            phone = ?,
            email = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sssssi",
        $company_name,
        $company_address,
        $gst_number,
        $phone,
        $email,
        $company_id
    );

    return $stmt->execute();
}

// Generate next slip number
function getNextSlipNumber($conn) {
    $sql = "
        SELECT MAX(slip_no) AS max_slip FROM (
            SELECT slip_no FROM weighments
            UNION ALL
            SELECT slip_no FROM sweighment
        ) t
    ";

    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    return ($row['max_slip'] ?? 0) + 1;
}

// Always show all fields
function showField($field_name) {
    return true;
}

// Stub for indicator reading
function readWeight() {
    return 0;
}

function formatDate($db_date) {
    global $config;
    return date($config['date_format'], strtotime($db_date));
}

function formatTime($db_time) {
    global $config;
    return $db_time;
}

function takeDatabaseBackup($conn)
{
    $dbname = $conn->query("SELECT DATABASE()")->fetch_row()[0];

    $date = date("d-m-Y");
    $time = date("H-i-s");

    $filename = "weighbridge_backup_{$date}_{$time}.sql";

    $backupDir = __DIR__ . "/backups";
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }

    $filepath = $backupDir . "/" . $filename;
    $mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";

    $mysqlUser = "root";
    $mysqlPassword = "";

    $command = "\"$mysqldump\" --user=$mysqlUser ";
    if ($mysqlPassword !== "") {
        $command .= "--password=$mysqlPassword ";
    }
    $command .= "$dbname > \"$filepath\"";

    system($command);
    return $filepath;
}

function resetWeighmentTransactions(mysqli $conn, $force = false)
{
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn->set_charset("utf8");

        $backupFile = takeDatabaseBackup($conn);
        if ($backupFile === false) {
            throw new Exception("Backup failed. Reset cancelled.");
        }

        $conn->begin_transaction();

        if (!$force) {
            $check = $conn->query("
                SELECT COUNT(*) AS cnt
                FROM weighments w
                LEFT JOIN sweighment s ON w.slip_no = s.slip_no
                WHERE s.slip_no IS NULL
            ");

            $row = $check->fetch_assoc();
            if ($row['cnt'] > 0) {
                throw new Exception("Pending weighments exist. Use FORCE RESET if required.");
            }
        }

        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        $conn->query("DELETE FROM weighment_field_values");
        $conn->query("DELETE FROM sweighment");
        $conn->query("DELETE FROM weighments");
        $conn->query("SET FOREIGN_KEY_CHECKS=1");

        $conn->query("ALTER TABLE weighments AUTO_INCREMENT = 1");
        $conn->query("ALTER TABLE sweighment AUTO_INCREMENT = 1");
        $conn->query("ALTER TABLE weighment_field_values AUTO_INCREMENT = 1");

        $conn->commit();

        return [
            'status'  => true,
            'message' => "System reset successful. Backup created: " . basename($backupFile)
        ];

    } catch (Exception $e) {
        $conn->rollback();
        return [
            'status'  => false,
            'message' => $e->getMessage()
        ];
    }
}

function restoreDatabaseBackup(mysqli $conn, $filename)
{
    $backupDir = __DIR__ . "/backups";
    $filepath  = $backupDir . "/" . basename($filename);

    if (!file_exists($filepath)) {
        return "Backup file not found.";
    }

    $mysql = "C:\\xampp\\mysql\\bin\\mysql.exe";
    $dbname = $conn->query("SELECT DATABASE()")->fetch_row()[0];
    $mysqlUser = "root";
    $mysqlPassword = "";

    $command = "\"$mysql\" --user=$mysqlUser ";
    if ($mysqlPassword !== "") {
        $command .= "--password=$mysqlPassword ";
    }
    $command .= "$dbname < \"$filepath\"";

    exec($command . " 2>&1", $output, $result);
    if ($result !== 0) {
        return "Restore failed:\n" . implode("\n", $output);
    }

    return true;
}

/* ============================================================
   CLIENT BROWSER DETECTION (FULL VERSION, OS & EMAIL CAPTURE)
   ============================================================ */
function getClientBrowserDetails($email = '') {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser';
    $browser   = "Google Chrome";
    $os        = "Windows 10/11";
    $arch      = "32-bit";

    // Detect Architecture
    if (stripos($userAgent, 'x86_64') !== false || stripos($userAgent, 'x86-64') !== false || stripos($userAgent, 'Win64') !== false || stripos($userAgent, 'x64') !== false || stripos($userAgent, 'WOW64') !== false) {
        $arch = "64-bit";
    }

    // Detect Exact OS
    if (preg_match('/windows nt 10/i', $userAgent))          $os = "Windows 10/11 $arch";
    elseif (preg_match('/windows nt 6.3/i', $userAgent))     $os = "Windows 8.1 $arch";
    elseif (preg_match('/windows nt 6.2/i', $userAgent))     $os = "Windows 8 $arch";
    elseif (preg_match('/windows nt 6.1/i', $userAgent))     $os = "Windows 7 $arch";
    elseif (preg_match('/macintosh|mac os x/i', $userAgent)) $os = "Mac OS";
    elseif (preg_match('/linux/i', $userAgent))              $os = "Linux";

    // Detect Exact Browser & Full Version (Edge checked before Chrome)
    if (preg_match('/edg\/([0-9\.]+)/i', $userAgent, $m)) {
        $browser = "Microsoft Edge " . explode('.', $m[1])[0];
    } elseif (preg_match('/chrome\/([0-9\.]+)/i', $userAgent, $m)) {
        $browser = "Google Chrome " . explode('.', $m[1])[0];
    } elseif (preg_match('/firefox\/([0-9\.]+)/i', $userAgent, $m)) {
        $browser = "Mozilla Firefox " . explode('.', $m[1])[0];
    } elseif (preg_match('/safari\/([0-9\.]+)/i', $userAgent, $m) && stripos($userAgent, 'chrome') === false) {
        $browser = "Safari";
    }

    // Append Account / Company Email if valid
    $clientDetails = "$browser ($os)";
    if (!empty($email) && trim($email) !== '-' && trim($email) !== '') {
        $clientDetails .= " | Email: " . trim($email);
    }

    return $clientDetails;
}

/* ============================================================
   CLOUD COMPANY ACTIVITY LOGGER (LOGIN & LOGOUT DISPATCHER)
   ============================================================ */
function logCompanyCloudActivity($conn, $username = '', $token_status = 'Active', $action = 'login') {
    try {
        $company      = getCompany($conn);
        $company_name = !empty($company['company_name']) ? trim($company['company_name']) : 'BALAJEE STEELS';
        $deviceid     = !empty($company['deviceid']) ? trim($company['deviceid']) : 'WB1';
        $email        = !empty($company['email']) ? trim($company['email']) : ($_SESSION['email'] ?? '');
        $user         = !empty($username) ? trim($username) : ($_SESSION['username'] ?? 'admin');
        
        $browser      = getClientBrowserDetails($email);
        $ip           = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $log_time     = date('Y-m-d H:i:s');

        // Structured Cloud Log Payload with Action Flag
        $payload = [
            'deviceid'     => $deviceid,
            'company_name' => $company_name,
            'username'     => $user,
            'browser_name' => $browser,
            'token_status' => $token_status,
            'action'       => $action, // 'login' or 'logout'
            'ip_address'   => $ip,
            'log_time'     => $log_time
        ];

        $cloudSyncUrl = "https://weighbridge.online-weighing.in/sync_company_log.php";
        $jsonPayload  = json_encode($payload);

        // 1. Primary: cURL with IPv4 & SSL bypass
        if (function_exists('curl_init')) {
            $ch = curl_init($cloudSyncUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            $res = curl_exec($ch);
            curl_close($ch);
            if ($res !== false) return true;
        }

        // 2. Secondary Fallback: Native Stream Context
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => $jsonPayload,
                'timeout' => 3
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ]
        ];

        @file_get_contents($cloudSyncUrl, false, stream_context_create($options));
        return true;

    } catch (Throwable $e) {
        // Fails safely in background so weighbridge operations are NEVER blocked
        return false; 
    }
}
?>