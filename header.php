<?php
ob_start();  
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";
require_once "functions.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login_php.php");
    exit;
}

/* ---------------- LOGOUT TRIGGER WITH CLOUD DISPATCH ---------------- */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (function_exists('logCompanyCloudActivity')) {
        logCompanyCloudActivity($conn, $_SESSION['username'] ?? 'admin', 'Logged Out', 'logout');
    }
    session_unset();
    session_destroy();
    header("Location: login_php.php");
    exit;
}

/* ---------------- 1. LOCAL NATIVE PREVIEW ENGINE (WITH CUSTOMER'S REAL LOGO & COMPANY DATA) ---------------- */
if (isset($_GET['action']) && $_GET['action'] === 'local_preview' && isset($_GET['format_key'])) {
    if (ob_get_length()) ob_clean();
    session_write_close();
    header('Content-Type: text/html; charset=UTF-8');
    
    $targetKey = trim($_GET['format_key']);
    $company = getCompany($conn);

    // Resolve Local Customer Logo
    $logoPath = file_exists(__DIR__ . '/LogoPrint.png') ? 'LogoPrint.png' : (file_exists(__DIR__ . '/gblogo.jpeg') ? 'gblogo.jpeg' : '');

    // Resolve Field Visibility & Custom Labels
    $fieldConfigMap = [];
    $resFld = $conn->query("SELECT * FROM `print_field_config`");
    if ($resFld) {
        while ($rf = $resFld->fetch_assoc()) {
            $fieldConfigMap[$rf['field_key']] = $rf;
        }
    }

    if (!function_exists('showPrintField')) {
        function showPrintField($key, $default = true) {
            global $fieldConfigMap;
            if (isset($fieldConfigMap[$key])) {
                return ((int)$fieldConfigMap[$key]['is_visible'] === 1);
            }
            return $default;
        }
    }

    if (!function_exists('getPrintFieldLabel')) {
        function getPrintFieldLabel($key, $default = '') {
            global $fieldConfigMap;
            if (!empty($fieldConfigMap[$key]['custom_label'])) {
                return htmlspecialchars($fieldConfigMap[$key]['custom_label']);
            }
            return htmlspecialchars($default);
        }
    }

    if (!function_exists('formatDateTime')) {
        function formatDateTime($date, $time) {
            return $date . ' ' . $time;
        }
    }

    // Realistic Sample Weighment Data
    $row = [
        'slip_no'            => '1',
        'vehicle_no'         => 'TN01TT0101',
        'gross_weight'       => '25480',
        'tare_weight'        => '10240',
        'net_weight'         => '15240',
        'first_weight'       => '25480',
        'gross_date'         => date('d/m/Y'),
        'gross_time'         => '11:45:00',
        'tare_date'          => date('d/m/Y'),
        'tare_time'          => '12:15:00',
        'first_date'         => date('d/m/Y'),
        'first_time'         => '11:45:00',
        'first_image_path'   => '',
        'second_image_path'  => '',
        'gt_type'            => 'G'
    ];

    $isFinal       = true;
    $material_name = 'APPLE 7777';
    $party_name    = 'FANTA JUICE';
    $vessel_name   = 'MV OCEAN VOYAGER';
    $vt_no         = 'VT-9942';
    $movement_type = 'EXPORT';
    $driver_name   = 'RAMESH KUMAR';
    $driver_no     = '9876543210';
    $sap_trans     = 'SAP-44021';
    $disp_gross    = 25480;
    $disp_tare     = 10240;
    $disp_net      = 15240;
    $disp_first    = 25480;
    $display_unit  = 'Kg';
    $tareImgs      = [];
    $grossImgs     = [];

    // Check if downloaded file exists on disk
    $tplFile = __DIR__ . '/templates/' . $targetKey . '.php';

    // Check if database has template_code
    $dbTemplateCode = '';
    $qCode = $conn->query("SELECT `template_code` FROM `print_format_settings` WHERE `format_key` = '{$targetKey}' AND `template_code` IS NOT NULL AND `template_code` != '' LIMIT 1");
    if ($qCode && $qCode->num_rows > 0) {
        $dbTemplateCode = $qCode->fetch_assoc()['template_code'];
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Print Preview - <?= htmlspecialchars($targetKey) ?></title>
    </head>
    <body onload="setTimeout(() => window.print(), 200);">
    <?php
    if (file_exists($tplFile)) {
        include $tplFile;
    } elseif (!empty($dbTemplateCode)) {
        eval('?>' . $dbTemplateCode);
    } elseif (file_exists(__DIR__ . '/templates/default_slip.php')) {
        include __DIR__ . '/templates/default_slip.php';
    } else {
        ?>
        <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: "Courier New", Courier, monospace, Arial; margin: 0; padding: 8mm 12mm; background: #fff; color: #000; }
        .slip-container { width: 100%; max-width: 700px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 6px; position: relative; }
        .company-logo { position: absolute; left: 0; top: 0; width: 50px; height: 50px; object-fit: contain; }
        .company-name { font-size: 19px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .company-addr { font-size: 13px; margin-top: 3px; line-height: 1.3; }
        .cert-title { font-size: 15px; font-weight: bold; margin-top: 6px; letter-spacing: 1.5px; }
        .divider-dashed { border-top: 1.5px dashed #000; margin: 8px 0; width: 100%; }
        .info-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .info-table td { padding: 3px 0; vertical-align: top; }
        .info-table .lbl { font-weight: bold; width: 130px; }
        .info-table .sep { width: 15px; text-align: center; }
        .info-table .val { width: 200px; }
        .weights-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .weights-table td { padding: 4px 0; vertical-align: middle; }
        .weights-table .w-lbl { font-weight: bold; width: 90px; }
        .weights-table .w-sep { width: 15px; text-align: center; }
        .weights-table .w-val { width: 145px; font-weight: bold; }
        .weights-table .w-dt-lbl, .weights-table .w-tm-lbl { font-weight: bold; width: 55px; text-align: right; padding-right: 5px; }
        .weights-table .w-dt-val, .weights-table .w-tm-val { width: 95px; }
        .signature-section { text-align: right; margin-top: 20px; padding-right: 20px; font-size: 13.5px; font-weight: bold; }
        </style>

        <div class="slip-container">
            <div class="header">
                <?php if (!empty($logoPath)): ?><img src="<?= $logoPath ?>?v=<?= time() ?>" class="company-logo" alt="Logo"><?php endif; ?>
                <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? '') ?></div>
                <div class="company-addr"><?= nl2br(htmlspecialchars($company['company_address'] ?? '')) ?></div>
                <div class="cert-title"><?= getPrintFieldLabel('cert_title', 'WEIGHMENT CERTIFICATE') ?></div>
            </div>
            <div class="divider-dashed"></div>

            <table class="info-table">
                <tr>
                    <?php if (showPrintField('slip_no')): ?><td class="lbl"><?= getPrintFieldLabel('slip_no', 'Slip No') ?></td><td class="sep">:</td><td class="val"><?= htmlspecialchars($row['slip_no']) ?></td><?php endif; ?>
                    <?php if (showPrintField('vehicle_no')): ?><td class="lbl"><?= getPrintFieldLabel('vehicle_no', 'Vehicle No') ?></td><td class="sep">:</td><td class="val"><?= htmlspecialchars($row['vehicle_no']) ?></td><?php endif; ?>
                </tr>
                <tr>
                    <?php if (showPrintField('material_name')): ?><td class="lbl"><?= getPrintFieldLabel('material_name', 'Material Name') ?></td><td class="sep">:</td><td class="val"><?= htmlspecialchars($material_name) ?></td><?php endif; ?>
                    <?php if (showPrintField('party_name')): ?><td class="lbl"><?= getPrintFieldLabel('party_name', 'Party Name') ?></td><td class="sep">:</td><td class="val"><?= htmlspecialchars($party_name) ?></td><?php endif; ?>
                </tr>
                <?php if (showPrintField('movement_type') && !empty($movement_type)): ?>
                <tr><td class="lbl"><?= getPrintFieldLabel('movement_type', 'Movement Type') ?></td><td class="sep">:</td><td class="val" colspan="4"><?= htmlspecialchars($movement_type) ?></td></tr>
                <?php endif; ?>
            </table>
            <div class="divider-dashed"></div>

            <table class="weights-table">
                <tr>
                    <td class="w-lbl">GROSS Wt</td><td class="w-sep">:</td><td class="w-val"><?= htmlspecialchars($disp_gross) ?> <?= htmlspecialchars($display_unit) ?></td>
                    <td class="w-dt-lbl">Date :</td><td class="w-dt-val"><?= htmlspecialchars($row['gross_date']??'') ?></td>
                    <td class="w-tm-lbl">Time :</td><td class="w-tm-val"><?= htmlspecialchars(substr($row['gross_time']??'', 0, 5)) ?></td>
                </tr>
                <tr>
                    <td class="w-lbl">Tare Wt</td><td class="w-sep">:</td><td class="w-val"><?= htmlspecialchars($disp_tare) ?> <?= htmlspecialchars($display_unit) ?></td>
                    <td class="w-dt-lbl">Date :</td><td class="w-dt-val"><?= htmlspecialchars($row['tare_date']??'') ?></td>
                    <td class="w-tm-lbl">Time :</td><td class="w-tm-val"><?= htmlspecialchars(substr($row['tare_time']??'', 0, 5)) ?></td>
                </tr>
                <tr>
                    <td class="w-lbl">Net Wt</td><td class="w-sep">:</td>
                    <td class="w-val" colspan="5"><?= htmlspecialchars($disp_net) ?> <?= htmlspecialchars($display_unit) ?></td>
                </tr>
            </table>

            <div class="divider-dashed"></div>
            <?php if (showPrintField('signature_block')): ?>
            <div class="signature-section"><?= getPrintFieldLabel('signature_block', "Operator's Signature") ?></div>
            <?php endif; ?>
        </div>
        <?php
    }
    ?>
    </body>
    </html>
    <?php
    exit;
}

/* ---------------- 2. SYNC: FETCH CLOUD TEMPLATES CATALOG & VERIFY LOCAL DISK CACHE ---------------- */
if (isset($_GET['action']) && $_GET['action'] === 'get_cloud_formats_catalog') {
    if (ob_get_length()) ob_clean();
    session_write_close();
    header('Content-Type: application/json; charset=UTF-8');
    
    $cloudApiUrl = "https://cloud.online-weighing.in/weighment_printslip.php?action=get_active_formats";

    $ch = curl_init($cloudApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $response = curl_exec($ch);
    curl_close($ch);

    $formats = [];
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['formats']) && is_array($data['formats'])) {
            $formats = $data['formats'];
        }
    }

    $tplDir = __DIR__ . '/templates';
    $resultFormats = [];

    // Permanent local static default layout (100% offline)
    $resultFormats[] = [
        'format_key' => 'default_slip',
        'name'       => 'Default Print Slip (Local)',
        'is_cached'  => true,
        'is_default' => true
    ];

    $standardFormatsCatalog = [
        'default_slip'                => 'Default Print Slip (Local)',
        'format_standard_a4_portrait' => 'Standard A4 Portrait Slip',
        'format_dual_landscape_cctv'  => 'Dual-Copy Top-Bottom A4 (Stacked 2-in-1 with CCTV)',
        'format_single_dotmatrix'     => 'Single Copy Continuous (TVS Dot-Matrix)',
        'format_triplicate_landscape' => 'Triplicate Copy Landscape A4 Slip'
    ];

    $catalogKeys = ['default_slip' => true];
    foreach ($formats as $fmt) {
        $fKey = $fmt['format_key'] ?? $fmt['id'] ?? '';
        if (empty($fKey) || $fKey === 'default_slip') continue;
        $isCachedLocally = file_exists($tplDir . '/' . $fKey . '.php');
        $fmt['is_cached'] = $isCachedLocally;
        $fmt['is_default'] = false;
        $resultFormats[] = $fmt;
        $catalogKeys[$fKey] = true;
    }

    // Append standard local templates if they exist on disk and weren't in cloud response
    foreach ($standardFormatsCatalog as $k => $name) {
        if (!isset($catalogKeys[$k]) && file_exists($tplDir . '/' . $k . '.php')) {
            $resultFormats[] = [
                'format_key' => $k,
                'name'       => $name,
                'is_cached'  => true,
                'is_default' => false
            ];
            $catalogKeys[$k] = true;
        }
    }

    echo json_encode([
        'status' => 'success',
        'formats' => $resultFormats
    ]);
    exit;
}

/* ---------------- 3. DOWNLOAD: SAVE INDIVIDUAL SELECTED TEMPLATE TO DISK ---------------- */
if (isset($_GET['action']) && $_GET['action'] === 'download_single_template' && isset($_GET['format_key'])) {
    if (ob_get_length()) ob_clean();
    session_write_close();
    header('Content-Type: application/json; charset=UTF-8');
    
    $targetKey = trim($_GET['format_key']);
    if ($targetKey === 'default_slip') {
        echo json_encode([
            'status'     => 'success',
            'message'    => 'Default Print Slip is a built-in static local template.',
            'format_key' => 'default_slip'
        ]);
        exit;
    }
    $cloudSingleUrl = "https://cloud.online-weighing.in/weighment_printslip.php?action=get_template_code&format_key=" . urlencode($targetKey);

    $ch = curl_init($cloudSingleUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false || empty($response)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reach cloud server: ' . ($curlErr ?: 'Empty response')]);
        exit;
    }

    $data = json_decode($response, true);
    if (!$data || $data['status'] !== 'success' || empty($data['template_code'])) {
        echo json_encode(['status' => 'error', 'message' => 'Template code missing in cloud response']);
        exit;
    }

    $tplDir = __DIR__ . '/templates';
    if (!is_dir($tplDir)) {
        @mkdir($tplDir, 0777, true);
    }

    $localFilePath = $tplDir . '/' . $targetKey . '.php';
    @file_put_contents($localFilePath, $data['template_code']);

    $conn->query("CREATE TABLE IF NOT EXISTS `print_format_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `company_name` VARCHAR(255) NOT NULL DEFAULT 'ALL',
        `format_key` VARCHAR(100) NOT NULL UNIQUE,
        `name` VARCHAR(255) NOT NULL DEFAULT '',
        `template_code` LONGTEXT DEFAULT NULL,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $colNameCheck = $conn->query("SHOW COLUMNS FROM `print_format_settings` LIKE 'name'");
    if ($colNameCheck && $colNameCheck->num_rows === 0) {
        $conn->query("ALTER TABLE `print_format_settings` ADD COLUMN `name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `format_key`");
    }

    $colCodeCheck = $conn->query("SHOW COLUMNS FROM `print_format_settings` LIKE 'template_code'");
    if ($colCodeCheck && $colCodeCheck->num_rows === 0) {
        $conn->query("ALTER TABLE `print_format_settings` ADD COLUMN `template_code` LONGTEXT DEFAULT NULL AFTER `name`");
    }

    $tName = $data['name'] ?? $targetKey;
    $stmt = $conn->prepare("INSERT INTO `print_format_settings` (`company_name`, `format_key`, `name`, `template_code`) VALUES ('ALL', ?, ?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `template_code` = VALUES(`template_code`)");
    if ($stmt) {
        $stmt->bind_param("sss", $targetKey, $tName, $data['template_code']);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode([
        'status'     => 'success',
        'message'    => "Template [{$tName}] downloaded successfully!",
        'format_key' => $targetKey
    ]);
    exit;
}

/* ---------------- 4. SAVE PRINT SETTINGS (FORMAT, LOGO, FIELDS) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_print_settings'])) {
    if (!empty($_FILES['company_logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg'];
        if (in_array($ext, $allowed)) {
            $target = __DIR__ . "/LogoPrint.png";
            move_uploaded_file($_FILES['company_logo']['tmp_name'], $target);
        }
    }

    if (isset($_POST['selected_print_layout'])) {
        $chosenLayout = trim($_POST['selected_print_layout']);
        $stmtFmt = $conn->prepare("
            INSERT INTO `print_format_settings` (`company_name`, `format_key`) 
            VALUES ('ACTIVE_CHOICE', ?) 
            ON DUPLICATE KEY UPDATE `format_key` = VALUES(`format_key`)
        ");
        if ($stmtFmt) {
            $stmtFmt->bind_param("s", $chosenLayout);
            $stmtFmt->execute();
            $stmtFmt->close();
        }
    }

    if (isset($_POST['field_settings']) && is_array($_POST['field_settings'])) {
        $conn->query("CREATE TABLE IF NOT EXISTS `print_field_config` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `field_key` VARCHAR(100) NOT NULL UNIQUE,
            `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
            `custom_label` VARCHAR(255) NOT NULL DEFAULT '',
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $stmtFld = $conn->prepare("
            INSERT INTO `print_field_config` (`field_key`, `is_visible`, `custom_label`) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE `is_visible` = VALUES(`is_visible`), `custom_label` = VALUES(`custom_label`)
        ");
        
        if ($stmtFld) {
            foreach ($_POST['field_settings'] as $fKey => $fData) {
                $vis = isset($fData['visible']) ? 1 : 0;
                $lbl = trim($fData['label'] ?? '');
                $stmtFld->bind_param("sis", $fKey, $vis, $lbl);
                $stmtFld->execute();
            }
            $stmtFld->close();
        }
    }

    $_SESSION['msg'] = "Print Layout, Field Visibility, and Logo saved successfully!";
    header("Location: mainform.php");
    exit;
}

/* ---------------- BACKUP & SYSTEM ACTIONS ---------------- */
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    if ($_SESSION['role'] !== 'admin') die("Unauthorized Access");
    $backupFile = takeDatabaseBackup($conn);
	$_SESSION['msg'] = "Database Backup Created Successfully";
	header("Location: mainform.php");
	exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'reset') {
    $force = isset($_GET['force']) && $_GET['force'] == 1;
    $result = resetWeighmentTransactions($conn, $force);
    if ($result['status']) {
        $_SESSION['msg'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    header("Location: mainform.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'restore_ui') {
    if ($_SESSION['role'] !== 'admin') die("Unauthorized Access");
    $backupDir = __DIR__ . "/backups";
    $files = glob($backupDir . "/*.sql");
    rsort($files);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Restore Backup</title>
        <style>
            body { font-family:Segoe UI, Arial; background:#f3f4f6; padding:30px; }
            .box { background:#fff; padding:20px; max-width:600px; margin:auto; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
            a.restore { display:block; padding:10px; margin:6px 0; background:#2563eb; color:#fff; text-decoration:none; border-radius:4px; }
            a.restore:hover { background:#1d4ed8; }
        </style>
    </head>
    <body>
    <div class="box">
        <h2>Select Backup to Restore</h2>
        <?php if (empty($files)): ?>
            <p>No backups found.</p>
        <?php else: ?>
            <?php foreach ($files as $f): $name = basename($f); ?>
                <a class="restore" href="?action=restore_exec&file=<?= urlencode($name) ?>" onclick="return confirm('Restore <?= $name ?> ? This will overwrite current data!');">
                   <?= $name ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
        <br>
        <a href="mainform.php">⬅ Back</a>
    </div>
    </body>
    </html>
    <?php
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'restore_exec') {
    if ($_SESSION['role'] !== 'admin') die("Unauthorized Access");
    $file = $_GET['file'] ?? '';
    $result = restoreDatabaseBackup($conn, $file);
    if ($result === true) {
        echo "<script>alert('Database Restored Successfully');window.location='mainform.php';</script>";
    } else {
        echo "<script>alert('$result');window.history.back();</script>";
    }
    exit;
}

$company = getCompany($conn);
if (!$company) {
    header("Location: create_company.php");
    exit;
}
$company_id = (int)($company['id'] ?? 1);

// -------------------------------------------------------------
// READ ACTIVE USER CHOICE & LOCAL TEMPLATES
// -------------------------------------------------------------
$conn->query("CREATE TABLE IF NOT EXISTS `print_format_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_name` VARCHAR(255) NOT NULL DEFAULT 'ALL',
    `format_key` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `template_code` LONGTEXT DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$activePrintFormat = 'default_slip';
$qChoice = $conn->query("SELECT `format_key` FROM `print_format_settings` WHERE `company_name` = 'ACTIVE_CHOICE' LIMIT 1");
if ($qChoice && $qChoice->num_rows > 0) {
    $rowChoice = $qChoice->fetch_assoc();
    if (!empty($rowChoice['format_key'])) {
        $activePrintFormat = trim($rowChoice['format_key']);
    }
}

// -------------------------------------------------------------
// LOCAL TEMPLATES CATALOG (100% OFFLINE COMPATIBLE)
// -------------------------------------------------------------
$standardLocalFormats = [
    'default_slip'                => 'Default Print Slip (Local)',
    'format_standard_a4_portrait' => 'Standard A4 Portrait Slip',
    'format_dual_landscape_cctv'  => 'Dual-Copy Top-Bottom A4 (Stacked 2-in-1 with CCTV)',
    'format_single_dotmatrix'     => 'Single Copy Continuous (TVS Dot-Matrix)',
    'format_triplicate_landscape' => 'Triplicate Copy Landscape A4 Slip'
];

$localSavedFormats = [];
$addedKeys = [];

// 1. Guaranteed static local default slip as Option #1
$localSavedFormats[] = [
    'format_key' => 'default_slip',
    'name'       => $standardLocalFormats['default_slip']
];
$addedKeys['default_slip'] = true;

// 2. Add other standard templates if file exists on disk
foreach ($standardLocalFormats as $k => $name) {
    if ($k === 'default_slip') continue;
    if (file_exists(__DIR__ . '/templates/' . $k . '.php')) {
        $localSavedFormats[] = [
            'format_key' => $k,
            'name'       => $name
        ];
        $addedKeys[$k] = true;
    }
}

// 3. Scan templates/ folder for any additional custom or downloaded templates
$tplFiles = glob(__DIR__ . '/templates/*.php');
if ($tplFiles) {
    foreach ($tplFiles as $tf) {
        $fKey = basename($tf, '.php');
        if (!isset($addedKeys[$fKey])) {
            $fName = ucwords(str_replace(['format_', '_'], ['', ' '], $fKey));
            $qFmtName = $conn->query("SELECT `name` FROM `print_format_settings` WHERE `format_key` = '" . $conn->real_escape_string($fKey) . "' LIMIT 1");
            if ($qFmtName && $qFmtName->num_rows > 0) {
                $dbName = trim($qFmtName->fetch_assoc()['name'] ?? '');
                if (!empty($dbName)) $fName = $dbName;
            }
            $localSavedFormats[] = [
                'format_key' => $fKey,
                'name'       => $fName
            ];
            $addedKeys[$fKey] = true;
        }
    }
}

// -------------------------------------------------------------
// DYNAMIC FIELDS (FROM weighment_fields) & FIELD CONFIG
// -------------------------------------------------------------
$conn->query("CREATE TABLE IF NOT EXISTS `print_field_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `field_key` VARCHAR(100) NOT NULL UNIQUE,
    `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
    `custom_label` VARCHAR(255) NOT NULL DEFAULT '',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$standardFieldsDef = [
    'slip_no'         => ['name' => 'Slip Number',       'default_label' => 'Slip No',              'default_vis' => 1],
    'vehicle_no'      => ['name' => 'Vehicle Number',     'default_label' => 'Vehicle No',           'default_vis' => 1],
    'material_name'   => ['name' => 'Material / Cargo',   'default_label' => 'Material Name',        'default_vis' => 1],
    'party_name'      => ['name' => 'Party / Client',     'default_label' => 'Party Name',           'default_vis' => 1],
    'vessel_name'     => ['name' => 'Vessel Name',        'default_label' => 'Vessel Name',          'default_vis' => 0],
    'vt_no'           => ['name' => 'VT Number',          'default_label' => 'Vt No',                'default_vis' => 0],
    'movement_type'   => ['name' => 'Movement Type',      'default_label' => 'Movement Type',        'default_vis' => 1],
    'driver_name'     => ['name' => 'Driver Name',        'default_label' => 'Driver Name',          'default_vis' => 1],
    'driver_no'       => ['name' => 'Driver Mobile No',   'default_label' => 'DRIVER NO',            'default_vis' => 1],
    'sap_trans'       => ['name' => 'SAP Transaction',    'default_label' => 'Sap Trans',            'default_vis' => 0],
    'cctv_images'     => ['name' => 'CCTV Camera Photos', 'default_label' => 'CCTV Photos',          'default_vis' => 1],
    'signature_block' => ['name' => 'Operator Signature', 'default_label' => "Operator's Signature", 'default_vis' => 1]
];

$savedFieldConfig = [];
$resFld = $conn->query("SELECT * FROM `print_field_config`");
if ($resFld) {
    while ($rf = $resFld->fetch_assoc()) {
        $savedFieldConfig[$rf['field_key']] = $rf;
    }
}

// Fetch Dynamic Custom Fields from weighment_fields
$customDynamicFields = [];
$qDyn = $conn->query("SELECT id, field_name, field_label FROM weighment_fields WHERE company_id = $company_id AND is_active = 1 ORDER BY field_order, id");
if ($qDyn) {
    while ($df = $qDyn->fetch_assoc()) {
        $customDynamicFields[] = $df;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($company['company_name'] ?? 'Weighbridge'); ?> - Weighment System</title>

<style>
/* High-Legibility Modern Soft Typography */
body {
    font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, 'Inter', Roboto, sans-serif;
    margin: 0;
    background: #f5f6fa;
}

/* ---------------- TOP MENU ---------------- */
.top-menu {
    background: #1f2937;
    color: #fff;
}
.top-menu ul {
    list-style: none;
    margin: 0;
    padding: 0 20px;
    display: flex;
}
.top-menu li {
    position: relative;
}
.top-menu a {
    display: block;
    padding: 12px 18px;
    color: #fff;
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
}
.top-menu a:hover {
    background: #374151;
}
.top-menu .dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background: #ffffff;
    min-width: 230px;
    border-radius: 4px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    z-index: 1000;
}
.top-menu .dropdown-menu a {
    color: #111;
    padding: 10px 14px;
    font-weight: 400;
}
.top-menu .dropdown-menu a:hover {
    background: #f3f4f6;
}
.top-menu li:hover .dropdown-menu {
    display: block;
}
.disabled-link {
    color: #9ca3af !important;
    background: #f3f4f6 !important;
    cursor: not-allowed;
    pointer-events: none;
}

/* ---------------- COMPANY HEADER ---------------- */
.company-banner {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    text-align: center;
    padding: 18px 10px;
}
.company-banner h1 {
    margin: 0;
    font-size: 26px;
    letter-spacing: 1px;
    color: #16a34a;
}
.company-banner .info {
    margin-top: 6px;
    font-size: 14px;
    color: #16a34a;
    line-height: 1.6;
    font-weight: 500;
}

.page-title {
    background: #2563eb;
    color: #ffffff;
    text-align: center;
    padding: 10px;
    font-size: 20px;
    font-weight: 600;
    letter-spacing: 1px;
}
.container {
    padding: 20px;
}

/* ============================================================
   HIGH-LEGIBILITY LARGE PRINT SETTINGS & CAMERA DIALOG (WIN95/WIN32)
   ============================================================ */
#cameraSettingsModal, #printSettingsModal {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.65);
    z-index: 99999;
    overflow-y: auto;
    padding: 12px;
}

/* Large 1020px Resizable Window Frame */
.win95-dialog {
    background: #c0c0c0;
    border: 2px solid;
    border-color: #dfdfdf #000000 #000000 #dfdfdf;
    box-shadow: 4px 4px 18px rgba(0, 0, 0, 0.7);
    color: #000000;
    font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, 'Inter', Roboto, sans-serif;
    width: 1020px; /* Spacious width */
    min-width: 680px;
    min-height: 560px;
    max-width: 98vw;
    max-height: 96vh;
    margin: 10px auto;
    padding: 4px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    position: relative;
    resize: both; /* Full Click-and-Drag Edge & Corner Resize */
    overflow: hidden;
}

.win95-dialog form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    width: 100%;
}

/* High-Legibility Titlebar */
.win95-titlebar {
    background: linear-gradient(90deg, #000080, #1084d0);
    color: #ffffff;
    padding: 8px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 700;
    font-size: 16.5px; /* Large, clear title */
    letter-spacing: 0.4px;
    user-select: none;
    cursor: default;
    flex-shrink: 0;
}

.win95-titlebar-actions {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Large Square Beveled Titlebar Buttons */
.win95-titlebar-btn {
    background: #c0c0c0;
    border: 2px solid;
    border-color: #ffffff #808080 #808080 #ffffff;
    width: 26px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: bold;
    color: #000000;
    cursor: pointer;
    box-shadow: 1px 1px 0 #000;
    padding: 0;
    line-height: 1;
}
.win95-titlebar-btn:active {
    border-color: #808080 #ffffff #ffffff #808080;
    transform: translate(1px, 1px);
}

/* Spacious Dialog Body */
.win95-body {
    padding: 16px 20px;
    background: #c0c0c0;
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
    box-sizing: border-box;
}

/* 3D Groupbox / Fieldset (Bold Navy Section Titles) */
.win95-groupbox {
    border: 2px groove #ffffff;
    padding: 16px 20px;
    background: #c0c0c0;
    box-sizing: border-box;
    width: 100%;
}

.win95-group-title {
    font-size: 15.5px; /* High visibility section header */
    font-weight: 700;
    color: #000080;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

/* 2-Column Fields Grid (Spacious, Zero-Strain Layout) */
.win95-fields-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 22px;
    width: 100%;
    box-sizing: border-box;
}

.win95-field-row {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    box-sizing: border-box;
}

/* Large 24px High-Visibility Checkbox */
.win95-field-row input[type="checkbox"] {
    width: 24px;
    height: 24px;
    cursor: pointer;
    flex-shrink: 0;
    accent-color: #000080;
}

/* Large, Clear Field Name Label */
.win95-field-lbl {
    font-size: 15px; /* Large, easy-on-the-eyes text */
    font-weight: 700;
    color: #000000;
    width: 150px;
    flex-shrink: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Large 3D Inset Textbox */
.win95-input {
    flex: 1;
    min-width: 0;
    padding: 7px 12px; /* Comfortable padding */
    background: #ffffff;
    border: 2px solid;
    border-color: #808080 #ffffff #ffffff #808080;
    font-size: 15px; /* Large input text */
    font-weight: 600;
    color: #000000;
    outline: none;
    box-sizing: border-box;
    font-family: inherit;
}
.win95-input:focus {
    outline: 1px dotted #000;
    background: #fffffa;
}

/* Large Dropdown Select */
.win95-select {
    width: 100%;
    padding: 9px 14px;
    background: #ffffff;
    border: 2px solid;
    border-color: #808080 #ffffff #ffffff #808080;
    font-size: 15.5px;
    color: #000000;
    font-family: inherit;
    font-weight: 700;
    outline: none;
    box-sizing: border-box;
}

/* Large 3D Push Buttons */
.win95-btn {
    background: #c0c0c0;
    border: 2px solid;
    border-color: #ffffff #808080 #808080 #ffffff;
    box-shadow: 1px 1px 0px #000000;
    color: #000000;
    padding: 7px 20px;
    font-size: 14.5px; /* Large readable button font */
    font-weight: 700;
    cursor: pointer;
    text-align: center;
    font-family: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
}
.win95-btn:active {
    border-color: #808080 #ffffff #ffffff #808080;
    box-shadow: none;
    transform: translate(1px, 1px);
}

.win95-btn-primary {
    outline: 1px solid #000000;
}

.win95-logo-box {
    width: 90px;
    height: 65px;
    background: #ffffff;
    border: 2px solid;
    border-color: #808080 #ffffff #ffffff #808080;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

/* Dialog Footer with Large Buttons & Textured Corner Grip */
.win95-footer {
    padding: 14px 20px 8px 20px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 14px;
    border-top: 1px solid #dfdfdf;
    flex-shrink: 0;
    position: relative;
}

.win95-corner-grip {
    position: absolute;
    right: 2px;
    bottom: 2px;
    width: 16px;
    height: 16px;
    cursor: se-resize;
    font-size: 14px;
    color: #444444;
    user-select: none;
    pointer-events: none;
    line-height: 1;
    text-align: right;
}
</style>
</head>

<body>

<!-- TOP MENU -->
<div class="top-menu">
    <ul>
        <li><a href="mainform.php">Home</a></li>
        <li>
            <a href="#">Reports ▾</a>
            <div class="dropdown-menu">
                <a href="pending_report.php">Pending Report</a>
                <a href="vehiclewise_report.php">Vehicle-wise Report</a>
                <?php if (!empty($report_fields)): ?>
                    <div style="border-top:1px solid #e5e7eb;margin:6px 0;"></div>
                    <?php foreach ($report_fields as $rf): ?>
                        <a href="dynamic_report.php?field_id=<?= $rf['id'] ?>">
                            <?= htmlspecialchars(ucwords(strtolower($rf['field_label']))) ?> Report
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </li>

        <li>
            <a href="#">Scale ▾</a>
            <div class="dropdown-menu">
                <a href="#" onclick="openScaleDialog(); return false;">🔌 Connect Port</a>
                <a href="#" onclick="autoReconnect(); return false;">🔁 Reconnect</a>
                <a href="#" onclick="disconnectScale(); return false;">🔴 Disconnect</a>
                <div style="border-top:1px solid #e5e7eb;margin:6px 0;"></div>
                <a href="#" style="cursor:default;">Status: <span id="connStatus">DISCONNECTED</span></a>
            </div>
        </li>

        <li>
            <a href="#">Camera ▾</a>
            <div class="dropdown-menu">
                <a href="#" onclick="toggleGlobalCamera(); return false;">
                    🔘 Camera: <span id="camGlobalStatusText" style="font-weight:bold;color:#dc2626;">OFF</span>
                </a>
                <a href="#" onclick="openCameraSettings(); return false;">⚙ Camera Settings</a>
            </div>
        </li>

        <li>
            <a href="#">Print Layout ▾</a>
            <div class="dropdown-menu">
                <a href="#" onclick="openPrintSettings(); return false;">📄 Select Print Layout</a>
                <a href="#" onclick="openPrintSettings(); return false;">📋 Field Visibility & Labels</a>
                <a href="#" onclick="openPrintSettings(); return false;">🖼️ Upload Company Logo</a>
            </div>
        </li>

        <?php if ($_SESSION['role'] === 'admin'): ?>
        <li>
            <a href="#">Settings ▾</a>
            <div class="dropdown-menu">
                <a href="?action=backup" onclick="return confirm('Do you want to take database backup now?');">📦 Backup Database</a>
                <a href="#" class="disabled-link" onclick="return false;">♻ Restore Database (Disabled)</a>
                <a href="?action=reset" onclick="return confirm('Normal Reset? Pending slips must be completed.');">🔄 Reset Slip Number</a>
                <a href="?action=reset&force=1" onclick="return confirm('FORCE RESET will delete even running weighments. Are you sure?');">⚠ Force Reset</a>
            </div>
        </li>
        <li><a href="admin_page.php">Dashboard</a></li>
        <?php endif; ?>

        <li><a href="?action=logout" onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
    </ul>
</div>

<!-- COMPANY INFO BANNER -->
<div class="company-banner">
    <h1><?php echo strtoupper($company['company_name'] ?? ''); ?></h1>
    <div class="info">
        <?php echo nl2br(htmlspecialchars($company['company_address'] ?? '')); ?><br>
        <?php
        $gst   = trim($company['gst_number'] ?? '');
        $phone = trim($company['phone'] ?? '');
        $email = trim($company['email'] ?? '');
        ?>
        <?php if ($gst !== '' && $gst !== '-'): ?>GST: <?= htmlspecialchars($gst); ?><?php endif; ?>
        <?php if ($phone !== '' && $phone !== '-'): ?><?php if ($gst !== '' && $gst !== '-'): ?> | <?php endif; ?>Phone: <?= htmlspecialchars($phone); ?><?php endif; ?>
        <?php if ($email !== '' && $email !== '-'): ?><br>Email: <?= htmlspecialchars($email); ?><?php endif; ?>
    </div>
</div>

<?php if (!empty($_SESSION['msg'])): ?>
<div style="background:#dcfce7;color:#166534;padding:10px;margin:10px;"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
<div id="errorBox" style="background:#fee2e2;color:#991b1b;padding:10px;margin:10px;"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<script src="scale.js?v=<?= time(); ?>"></script>

<!-- SCALE CONNECT DIALOG -->
<div id="scaleDialog" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.4);z-index:9999;">
    <div style="background:#fff;width:320px;padding:20px;margin:120px auto;text-align:center;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,0.3);">
        <h3>Connect Weighbridge</h3>
        <p>Click below to select COM Port</p>
        <button onclick="connectScaleDirect()" style="padding:10px 18px;font-size:16px;cursor:pointer;">CONNECT SCALE</button>
        <br><br><button onclick="closeScaleDialog()">Cancel</button>
    </div>
</div>

<!-- ============================================================
     PRINT SETTINGS DIALOG (HIGH VISIBILITY & SPACIOUS)
     ============================================================ -->
<div id="printSettingsModal">
    <div class="win95-dialog" id="printSettingsDialog">
        <!-- Titlebar -->
        <div class="win95-titlebar">
            <span>Print Slip Layout & Field Configuration</span>
            <div class="win95-titlebar-actions">
                <button type="button" class="win95-titlebar-btn" onclick="closePrintSettings()" title="Close">✕</button>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_print_settings" value="1">

            <div class="win95-body">
                <!-- Section 1: Active Layout with Sync, Preview, and Download -->
                <div class="win95-groupbox">
                    <div class="win95-group-title">
                        <span>1. Active Print Slip Layout</span>
                        <button type="button" class="win95-btn" onclick="syncTemplatesCatalog(this)" title="Sync all templates from cloud">
                            🔄 Sync
                        </button>
                    </div>

                    <div style="display:flex;align-items:center;gap:12px;margin-top:8px;">
                        <select id="local_print_layout_select" name="selected_print_layout" onchange="updateSelectedTemplateStatus()" class="win95-select" style="flex:1;">
                            <?php foreach ($localSavedFormats as $idx => $lf): 
                                $isSelected = ($activePrintFormat === $lf['format_key']);
                            ?>
                            <option value="<?= htmlspecialchars($lf['format_key']) ?>" <?= $isSelected ? 'selected' : '' ?>>
                                <?= ($idx + 1) . '. ' . htmlspecialchars($lf['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- PREVIEW BUTTON -->
                        <button type="button" class="win95-btn" onclick="triggerLocalNativePrintPreview(this)" title="Preview exact browser print output with your logo">
                            👁️ Preview
                        </button>

                        <!-- DOWNLOAD / DOWNLOADED / DEFAULT PRINT BUTTON -->
                        <button type="button" id="btn_download_single" class="win95-btn" onclick="downloadSelectedTemplate(this)">
                            <?= ($activePrintFormat === 'default_slip') ? 'Default Print' : 'Downloaded' ?>
                        </button>
                    </div>
                </div>

                <!-- Section 2: Field Visibility & Custom Labels (Large High-Visibility Grid) -->
                <div class="win95-groupbox">
                    <div class="win95-group-title">
                        <span>2. Slip Fields Visibility & Custom Print Labels</span>
                    </div>
                    <div class="win95-fields-grid">
                        <?php foreach ($standardFieldsDef as $fKey => $fMeta): 
                            $isVis = isset($savedFieldConfig[$fKey]) ? ((int)$savedFieldConfig[$fKey]['is_visible'] === 1) : ((int)$fMeta['default_vis'] === 1);
                            $curLbl = !empty($savedFieldConfig[$fKey]['custom_label']) ? $savedFieldConfig[$fKey]['custom_label'] : $fMeta['default_label'];
                        ?>
                        <div class="win95-field-row">
                            <input type="checkbox" name="field_settings[<?= $fKey ?>][visible]" value="1" <?= $isVis ? 'checked' : '' ?>>
                            <span class="win95-field-lbl" title="<?= htmlspecialchars($fMeta['name']) ?>"><?= htmlspecialchars($fMeta['name']) ?></span>
                            <input type="text" name="field_settings[<?= $fKey ?>][label]" value="<?= htmlspecialchars($curLbl) ?>" class="win95-input" placeholder="Print Label">
                        </div>
                        <?php endforeach; ?>

                        <!-- Dynamic Custom Fields from weighment_fields -->
                        <?php foreach ($customDynamicFields as $cdf): 
                            $dynKey = 'dyn_' . $cdf['id'];
                            $dynName = !empty($cdf['field_name']) ? $cdf['field_name'] : $cdf['field_label'];
                            $isVis = isset($savedFieldConfig[$dynKey]) ? ((int)$savedFieldConfig[$dynKey]['is_visible'] === 1) : 1;
                            $curLbl = !empty($savedFieldConfig[$dynKey]['custom_label']) ? $savedFieldConfig[$dynKey]['custom_label'] : $cdf['field_label'];
                        ?>
                        <div class="win95-field-row">
                            <input type="checkbox" name="field_settings[<?= $dynKey ?>][visible]" value="1" <?= $isVis ? 'checked' : '' ?>>
                            <span class="win95-field-lbl" title="<?= htmlspecialchars($dynName) ?>"><?= htmlspecialchars($dynName) ?></span>
                            <input type="text" name="field_settings[<?= $dynKey ?>][label]" value="<?= htmlspecialchars($curLbl) ?>" class="win95-input" placeholder="Print Label">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Section 3: Company Logo Upload -->
                <div class="win95-groupbox" style="margin-bottom:0;">
                    <div class="win95-group-title">
                        <span>3. Company Logo for Print Slip</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:20px;">
                        <div class="win95-logo-box">
                            <img id="logo_preview" src="LogoPrint.png?v=<?= time(); ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;" onerror="this.src='gblogo.jpeg'">
                        </div>
                        <div style="flex:1;">
                            <input type="file" name="company_logo" accept="image/*" onchange="previewSelectedLogo(this)" style="font-size:14px;color:#000;font-weight:bold;">
                            <div style="font-size:13px;color:#333;margin-top:4px;">Upload PNG, JPG, or JPEG logo for header integration</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="win95-footer">
                <button type="submit" class="win95-btn win95-btn-primary" style="padding:10px 32px;font-size:15.5px;">💾 Save Print Settings</button>
                <button type="button" class="win95-btn" onclick="closePrintSettings()" style="padding:10px 24px;font-size:15.5px;">Cancel</button>
                <!-- Drag Grip Indicator -->
                <div class="win95-corner-grip">◢</div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     CAMERA SETTINGS MODAL (HIGH VISIBILITY & SPACIOUS)
     ============================================================ -->
<div id="cameraSettingsModal">
    <div class="win95-dialog" id="cameraSettingsDialog">
        <div class="win95-titlebar">
            <span>Camera Configuration</span>
            <div class="win95-titlebar-actions">
                <button type="button" class="win95-titlebar-btn" onclick="closeCameraSettings()" title="Close">✕</button>
            </div>
        </div>
        <div class="win95-body">
            <div class="win95-groupbox" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <span style="font-weight:bold;font-size:15.5px;color:#000;">Enable Live Cameras Grid</span>
                <label style="display:inline-flex;align-items:center;gap:8px;font-size:14.5px;font-weight:bold;cursor:pointer;">
                    <input type="checkbox" id="modal_global_cam_toggle" onchange="updateMasterToggleState(this.checked)" style="width:22px;height:22px;">
                    <span>Active</span>
                </label>
            </div>

            <?php for($i=1; $i<=4; $i++): ?>
            <div class="win95-groupbox">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <span style="font-size:14.5px;font-weight:bold;color:#000080;">CAMERA <?= $i ?> (CHANNEL <?= $i ?>)</span>
                    <label style="font-size:13.5px;font-weight:bold;color:#000;cursor:pointer;display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" id="cam_enabled_<?= $i ?>" style="width:18px;height:18px;" checked> Enabled
                    </label>
                </div>
                <div style="margin-bottom:10px;">
                    <label style="display:block;font-size:13px;font-weight:bold;color:#000;margin-bottom:4px;">Live Camera URL</label>
                    <input type="text" id="cam_url_<?= $i ?>" placeholder="http://192.168.1.1<?= $i ?>/cgi-bin/mjpg/video.cgi?channel=1&subtype=1" class="win95-input" style="width:100%;">
                </div>
                <div style="display:flex;gap:14px;">
                    <div style="flex:1;">
                        <label style="display:block;font-size:13px;font-weight:bold;color:#000;margin-bottom:4px;">Username</label>
                        <input type="text" id="cam_user_<?= $i ?>" value="admin" class="win95-input" style="width:100%;">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block;font-size:13px;font-weight:bold;color:#000;margin-bottom:4px;">Password</label>
                        <input type="password" id="cam_pass_<?= $i ?>" value="" class="win95-input" style="width:100%;">
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <div class="win95-footer">
            <button onclick="saveCameraSettings()" class="win95-btn win95-btn-primary" style="padding:10px 32px;font-size:15.5px;">💾 Save Settings</button>
            <button type="button" class="win95-btn" onclick="closeCameraSettings()" style="padding:10px 24px;font-size:15.5px;">Cancel</button>
            <div class="win95-corner-grip">◢</div>
        </div>
    </div>
</div>

<script>
/* ============================================================
   SEPARATED SYNC, LOCAL NATIVE PREVIEW & INDIVIDUAL DOWNLOAD
   ============================================================ */
let cloudTemplatesCatalog = [];

function openPrintSettings() {
    document.getElementById("printSettingsModal").style.display = "block";
    updateSelectedTemplateStatus();
}

function closePrintSettings() {
    document.getElementById("printSettingsModal").style.display = "none";
}

function previewSelectedLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById("logo_preview").src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// 🔄 1. SYNC: Loads and refreshes all templates list from cloud
function syncTemplatesCatalog(btn, isSilent = false) {
    const originalText = btn ? btn.innerText : "🔄 Sync";
    if (btn) btn.innerText = "⏳ Syncing...";

    fetch("header.php?action=get_cloud_formats_catalog")
        .then(res => res.json())
        .then(data => {
            if (btn) btn.innerText = originalText;
            if (data.status === "success" && data.formats && data.formats.length > 0) {
                let catalog = data.formats;
                if (!catalog.some(f => (f.format_key || f.id) === "default_slip")) {
                    catalog.unshift({
                        format_key: "default_slip",
                        name: "Default Print Slip (Local)",
                        is_cached: true,
                        is_default: true
                    });
                }
                cloudTemplatesCatalog = catalog;

                const selectEl = document.getElementById("local_print_layout_select");
                if (selectEl) {
                    const currentVal = selectEl.value;
                    selectEl.innerHTML = "";
                    catalog.forEach((fmt, idx) => {
                        const opt = document.createElement("option");
                        const optKey = fmt.format_key || fmt.id;
                        opt.value = optKey;
                        opt.text = (idx + 1) + ". " + fmt.name;
                        if (optKey === currentVal) opt.selected = true;
                        selectEl.appendChild(opt);
                    });
                    if (currentVal && catalog.some(f => (f.format_key || f.id) === currentVal)) {
                        selectEl.value = currentVal;
                    }
                    updateSelectedTemplateStatus();
                }
                if (!isSilent) {
                    alert("✓ Synced " + catalog.length + " layout templates from Cloud catalog!");
                }
            }
        })
        .catch(() => {
            if (btn) btn.innerText = originalText;
            updateSelectedTemplateStatus();
        });
}

// 🏷️ 2. Updates the button to "Default Print" or "Downloaded" or "Download"
function updateSelectedTemplateStatus() {
    const selectEl = document.getElementById("local_print_layout_select");
    const btn = document.getElementById("btn_download_single");
    if (!selectEl || !btn) return;

    const selectedKey = selectEl.value;

    if (selectedKey === "default_slip") {
        btn.innerText = "Default Print";
        btn.title = "Built-in local static default layout (100% Offline)";
        btn.disabled = true;
        btn.style.cursor = "default";
        btn.style.opacity = "0.9";
        return;
    }

    btn.disabled = false;
    btn.style.cursor = "pointer";
    btn.style.opacity = "1";

    const standardKeys = [
        "format_standard_a4_portrait",
        "format_dual_landscape_cctv",
        "format_single_dotmatrix",
        "format_triplicate_landscape"
    ];

    const found = cloudTemplatesCatalog.find(f => (f.format_key || f.id) === selectedKey);

    if (standardKeys.includes(selectedKey) || (found && found.is_cached)) {
        btn.innerText = "Downloaded";
        btn.title = "Already available on disk (Click to re-download)";
    } else {
        btn.innerText = "Download";
        btn.title = "Click to download this template from cloud";
    }
}

// 👁️ 3. LOCAL NATIVE BROWSER PRINT PREVIEW (RENDERS CUSTOMER'S ACTUAL LOGO & DATA)
function triggerLocalNativePrintPreview(btn) {
    const selectEl = document.getElementById("local_print_layout_select");
    if (!selectEl) return;
    const selectedKey = selectEl.value;

    const originalText = btn ? btn.innerText : "👁️ Preview";
    if (btn) btn.innerText = "⏳ Loading...";

    let oldFrame = document.getElementById("hiddenLocalPrintFrame");
    if (oldFrame) oldFrame.remove();

    const iframe = document.createElement("iframe");
    iframe.id = "hiddenLocalPrintFrame";
    iframe.style.position = "fixed";
    iframe.style.right = "0";
    iframe.style.bottom = "0";
    iframe.style.width = "0";
    iframe.style.height = "0";
    iframe.style.border = "none";
    iframe.style.visibility = "hidden";
    iframe.src = "header.php?action=local_preview&format_key=" + encodeURIComponent(selectedKey);

    iframe.onload = function () {
        if (btn) btn.innerText = originalText;
    };

    document.body.appendChild(iframe);
}

// ⬇ 4. DOWNLOAD BUTTON: Downloads ONLY the chosen template
function downloadSelectedTemplate(btn) {
    const selectEl = document.getElementById("local_print_layout_select");
    if (!selectEl) return;

    const selectedKey = selectEl.value;
    if (selectedKey === "default_slip") {
        alert("✓ This is the built-in local static default layout (templates/default_slip.php).");
        return;
    }

    const originalText = btn ? btn.innerText : "Download";
    if (btn) btn.innerText = "Downloading...";

    fetch("header.php?action=download_single_template&format_key=" + encodeURIComponent(selectedKey))
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                const found = cloudTemplatesCatalog.find(f => (f.format_key || f.id) === selectedKey);
                if (found) found.is_cached = true;
                updateSelectedTemplateStatus();
                alert("✓ " + data.message);
            } else {
                if (btn) btn.innerText = originalText;
                alert("Download Notice: " + (data.message || "Failed to download template."));
            }
        })
        .catch(err => {
            if (btn) btn.innerText = originalText;
            alert("Download Error: Could not connect to local server.");
        });
}

document.addEventListener("DOMContentLoaded", function () {
    updateSelectedTemplateStatus();
});

/* ============================================================
   CAMERA CONTROLS
   ============================================================ */
function isCameraActive() { return localStorage.getItem("weighbridge_cam_active") === "true"; }

function updateCameraMenuUI() {
    const el = document.getElementById("camGlobalStatusText");
    const active = isCameraActive();
    if (el) { el.innerText = active ? "ON" : "OFF"; el.style.color = active ? "#16a34a" : "#dc2626"; }
    const modalCheck = document.getElementById("modal_global_cam_toggle");
    if (modalCheck) {
        modalCheck.checked = active;
    }
}

function updateMasterToggleState(isChecked) {
    localStorage.setItem("weighbridge_cam_active", isChecked ? "true" : "false");
    updateCameraMenuUI();
    if (typeof applyCameraGridVisibility === "function") applyCameraGridVisibility();
}

function toggleGlobalCamera() {
    const currentState = isCameraActive();
    const newState = !currentState;
    localStorage.setItem("weighbridge_cam_active", newState ? "true" : "false");
    updateCameraMenuUI();
    if (typeof applyCameraGridVisibility === "function") applyCameraGridVisibility();
    else window.location.reload();
}

function openCameraSettings() { loadCameraSettingsIntoModal(); document.getElementById("cameraSettingsModal").style.display = "block"; }
function closeCameraSettings() { document.getElementById("cameraSettingsModal").style.display = "none"; }

function loadCameraSettingsIntoModal() {
    updateCameraMenuUI();
    const saved = localStorage.getItem("weighbridge_cam_config");
    if (saved) {
        try {
            const config = JSON.parse(saved);
            for (let i = 1; i <= 4; i++) {
                if (config["cam_" + i]) {
                    document.getElementById("cam_enabled_" + i).checked = config["cam_" + i].enabled !== false;
                    document.getElementById("cam_url_" + i).value = config["cam_" + i].url || "";
                    document.getElementById("cam_user_" + i).value = config["cam_" + i].user || "admin";
                    document.getElementById("cam_pass_" + i).value = config["cam_" + i].pass || "";
                }
            }
        } catch (e) {}
    }
}

function saveCameraSettings() {
    const isGlobalOn = document.getElementById("modal_global_cam_toggle").checked;
    localStorage.setItem("weighbridge_cam_active", isGlobalOn ? "true" : "false");
    const config = {};
    for (let i = 1; i <= 4; i++) {
        config["cam_" + i] = {
            enabled: document.getElementById("cam_enabled_" + i).checked,
            url: document.getElementById("cam_url_" + i).value.trim(),
            user: document.getElementById("cam_user_" + i).value.trim(),
            pass: document.getElementById("cam_pass_" + i).value.trim()
        };
    }
    localStorage.setItem("weighbridge_cam_config", JSON.stringify(config));
    alert("Camera settings saved successfully!");
    closeCameraSettings();
    updateCameraMenuUI();
    if (typeof applyCameraGridVisibility === "function") applyCameraGridVisibility();
}

document.addEventListener("DOMContentLoaded", function () {
    updateCameraMenuUI();
});

/* ============================================================
   WINDOW CLOSE (✖ BUTTON) BEACON DISPATCHER
   ============================================================ */
window.addEventListener("pagehide", function () {
    const cloudSyncUrl = "https://weighbridge.online-weighing.in/sync_company_log.php";
    const companyName  = "<?= htmlspecialchars($company['company_name'] ?? '') ?>";
    const deviceId     = "<?= htmlspecialchars($company['deviceid'] ?? 'WB1') ?>";
    const username     = "<?= htmlspecialchars($_SESSION['username'] ?? 'admin') ?>";

    if (companyName && navigator.sendBeacon) {
        const data = new FormData();
        data.append("company_name", companyName);
        data.append("deviceid", deviceId);
        data.append("username", username);
        data.append("action", "logout");
        navigator.sendBeacon(cloudSyncUrl, data);
    }
});
</script>

</body>
</html>