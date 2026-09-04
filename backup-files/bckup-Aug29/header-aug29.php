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

/* ---------------- CLOUD TEMPLATE DOWNLOAD & LOCAL STORAGE ENGINE ---------------- */
if (isset($_GET['action']) && $_GET['action'] === 'store_cloud_templates') {
    header('Content-Type: application/json; charset=UTF-8');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!empty($data['formats']) && is_array($data['formats'])) {
        // Ensure local templates folder exists
        $tplDir = __DIR__ . '/templates';
        if (!is_dir($tplDir)) {
            @mkdir($tplDir, 0777, true);
        }

        // Auto-ensure template_code column exists locally
        $conn->query("CREATE TABLE IF NOT EXISTS `print_format_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `company_name` VARCHAR(255) NOT NULL DEFAULT 'ALL',
            `format_key` VARCHAR(100) NOT NULL UNIQUE,
            `name` VARCHAR(255) NOT NULL DEFAULT '',
            `template_code` LONGTEXT DEFAULT NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $chkCode = $conn->query("SHOW COLUMNS FROM `print_format_settings` LIKE 'template_code'");
        if ($chkCode && $chkCode->num_rows === 0) {
            $conn->query("ALTER TABLE `print_format_settings` ADD COLUMN `template_code` LONGTEXT DEFAULT NULL");
        }

        $stmtTpl = $conn->prepare("
            INSERT INTO `print_format_settings` (`company_name`, `format_key`, `name`, `template_code`) 
            VALUES ('ALL', ?, ?, ?)
            ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `template_code` = VALUES(`template_code`)
        ");

        foreach ($data['formats'] as $fmt) {
            $fKey = trim($fmt['format_key'] ?? $fmt['id'] ?? '');
            $fName = trim($fmt['name'] ?? '');
            $fCode = $fmt['template_code'] ?? '';

            if ($stmtTpl && !empty($fKey)) {
                $stmtTpl->bind_param("sss", $fKey, $fName, $fCode);
                $stmtTpl->execute();
            }

            // Write as standalone executable .php template file locally
            if (!empty($fKey) && !empty($fCode)) {
                @file_put_contents($tplDir . '/' . $fKey . '.php', $fCode);
            }
        }
        if ($stmtTpl) $stmtTpl->close();
    }

    echo json_encode(['status' => 'success', 'message' => 'Templates stored locally']);
    exit;
}

/* ---------------- PRINT LAYOUT, FIELDS & LOGO UPLOAD HANDLER ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_print_settings'])) {
    // 1. Handle Logo Upload
    if (!empty($_FILES['company_logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg'];
        if (in_array($ext, $allowed)) {
            $target = __DIR__ . "/LogoPrint.png";
            move_uploaded_file($_FILES['company_logo']['tmp_name'], $target);
        }
    }

    // 2. Handle Selected Print Format
    if (isset($_POST['selected_print_layout'])) {
        $chosenLayout = trim($_POST['selected_print_layout']);
        $stmtFmt = $conn->prepare("
            INSERT INTO `print_format_settings` (`company_name`, `format_key`) 
            VALUES ('ALL', ?) 
            ON DUPLICATE KEY UPDATE `format_key` = VALUES(`format_key`)
        ");
        if ($stmtFmt) {
            $stmtFmt->bind_param("s", $chosenLayout);
            $stmtFmt->execute();
            $stmtFmt->close();
        }
    }

    // 3. Handle Field Visibility & Custom Labels
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

/* ---------------- BACKUP TRIGGER ---------------- */
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    if ($_SESSION['role'] !== 'admin') {
        die("Unauthorized Access");
    }
    $backupFile = takeDatabaseBackup($conn);
	$_SESSION['msg'] = "Database Backup Created Successfully";
	header("Location: mainform.php");
	exit;
}

/* ---------------- RESET SYSTEM ---------------- */
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

/* ---------------- SHOW RESTORE SCREEN ---------------- */
if (isset($_GET['action']) && $_GET['action'] === 'restore_ui') {
    if ($_SESSION['role'] !== 'admin') {
        die("Unauthorized Access");
    }
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

/* ---------------- EXECUTE RESTORE ---------------- */
if (isset($_GET['action']) && $_GET['action'] === 'restore_exec') {
    if ($_SESSION['role'] !== 'admin') {
        die("Unauthorized Access");
    }
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
// AUTO-CREATE & FETCH LOCAL PRINT FORMAT SETTINGS SAFELY
// -------------------------------------------------------------
$conn->query("CREATE TABLE IF NOT EXISTS `print_format_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_name` VARCHAR(255) NOT NULL DEFAULT 'ALL',
    `format_key` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `template_code` LONGTEXT DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$activePrintFormat = 'format_standard_a4_portrait';
$qFmt = $conn->query("SELECT `format_key` FROM `print_format_settings` WHERE `company_name` = 'ALL' LIMIT 1");
if ($qFmt && $qFmt->num_rows > 0) {
    $activePrintFormat = $qFmt->fetch_assoc()['format_key'];
}

// -------------------------------------------------------------
// AUTO-CREATE & FETCH DYNAMIC PRINT FIELD CONFIGURATION
// -------------------------------------------------------------
$conn->query("CREATE TABLE IF NOT EXISTS `print_field_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `field_key` VARCHAR(100) NOT NULL UNIQUE,
    `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
    `custom_label` VARCHAR(255) NOT NULL DEFAULT '',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Standard Fields Matrix Definition
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

/* ---------------- FETCH REPORTABLE DYNAMIC FIELDS ---------------- */
$report_fields = [];
$rstmt = $conn->prepare("
    SELECT id, field_label
    FROM weighment_fields
    WHERE company_id = ?
      AND field_options LIKE '%report%'
	  AND is_active = 1	
    ORDER BY field_order
");
if ($rstmt) {
    $rstmt->bind_param("i", $company_id);
    $rstmt->execute();
    $rres = $rstmt->get_result();

    while ($r = $rres->fetch_assoc()) {
        $report_fields[] = $r;
    }
    $rstmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($company['company_name'] ?? 'Weighbridge'); ?> - Weighment System</title>

<style>
body {
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    margin:0;
    background:#f5f6fa;
}

/* ---------------- TOP MENU ---------------- */
.top-menu {
    background:#1f2937;
    color:#fff;
}
.top-menu ul {
    list-style:none;
    margin:0;
    padding:0 20px;
    display:flex;
}
.top-menu li {
    position:relative;
}
.top-menu a {
    display:block;
    padding:12px 18px;
    color:#fff;
    text-decoration:none;
    font-size:15px;
    font-weight:500;
}
.top-menu a:hover {
    background:#374151;
}
.top-menu .dropdown-menu {
    display:none;
    position:absolute;
    top:100%;
    left:0;
    background:#ffffff;
    min-width:230px;
    border-radius:4px;
    box-shadow:0 8px 16px rgba(0,0,0,0.15);
    z-index:1000;
}
.top-menu .dropdown-menu a {
    color:#111;
    padding:10px 14px;
    font-weight:400;
}
.top-menu .dropdown-menu a:hover {
    background:#f3f4f6;
}
.top-menu li:hover .dropdown-menu {
    display:block;
}
.disabled-link {
    color: #9ca3af !important;
    background: #f3f4f6 !important;
    cursor: not-allowed;
    pointer-events: none;
}

/* ---------------- COMPANY HEADER ---------------- */
.company-banner {
    background:#ffffff;
    border-bottom:1px solid #e5e7eb;
    text-align:center;
    padding:18px 10px;
}
.company-banner h1 {
    margin:0;
    font-size:26px;
    letter-spacing:1px;
    color:#16a34a;
}
.company-banner .info {
    margin-top:6px;
    font-size:14px;
    color:#16a34a;
    line-height:1.6;
    font-weight: 500;
}

.page-title {
    background:#2563eb;
    color:#ffffff;
    text-align:center;
    padding:10px;
    font-size:20px;
    font-weight:600;
    letter-spacing:1px;
}
.container {
    padding:20px;
}

/* ============================================================
   ENTERPRISE DIALOG STYLING (HIGH-VISIBILITY & EYE-FRIENDLY)
   ============================================================ */
#cameraSettingsModal,
#printSettingsModal {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.70);
    z-index: 99999;
    overflow-y: auto;
    padding: 20px 10px;
}

.enterprise-dialog-box {
    background: #ffffff;
    color: #1e293b;
    width: 95%;
    max-width: 980px;
    margin: 15px auto;
    border-radius: 8px;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.45);
    border: 1px solid #cbd5e1;
    overflow: hidden;
}

.dialog-header {
    background: #172d4e;
    color: #ffffff;
    padding: 14px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #0f1d33;
}

.dialog-header h2 {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
    letter-spacing: 0.5px;
    color: #ffffff;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dialog-close-btn {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #334155;
    width: 32px;
    height: 32px;
    border-radius: 4px;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s;
}
.dialog-close-btn:hover {
    background: #ef4444;
    color: #fff;
    border-color: #dc2626;
}

.dialog-body {
    padding: 22px 26px;
    background: #f8fafc;
}

/* High Visibility Groupbox Container */
.win-groupbox {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 6px;
    padding: 16px 20px;
    margin-bottom: 18px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
}

.win-group-title {
    font-size: 14.5px;
    font-weight: 800;
    color: #1e3a68;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1.5px solid #e2e8f0;
    padding-bottom: 6px;
}

/* 2-Column Fields Grid (Spacious, Large Text & Big Checkboxes) */
.win-fields-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 20px;
    width: 100%;
}

.win-field-row {
    display: flex;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 8px 12px;
    gap: 10px;
    min-width: 0;
    box-sizing: border-box;
    transition: border-color 0.2s, background-color 0.2s;
}
.win-field-row:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

/* Big High-Visibility Checkbox */
.win-field-row input[type="checkbox"] {
    width: 22px;
    height: 22px;
    accent-color: #10b981;
    cursor: pointer;
    flex-shrink: 0;
}

/* Clear, Large Field Label */
.win-field-row .field-lbl {
    font-size: 13.5px;
    font-weight: 700;
    color: #1e293b;
    width: 135px;
    flex-shrink: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Spacious, High-Contrast Text Input */
.win-field-row input[type="text"] {
    flex: 1;
    min-width: 0;
    padding: 7px 10px;
    border: 1.5px solid #cbd5e1;
    border-radius: 4px;
    font-size: 13.5px;
    font-weight: 600;
    background: #ffffff;
    color: #0f172a;
    box-sizing: border-box;
}

.win-field-row input[type="text"]:focus {
    outline: none;
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}

/* DOWNLOAD BUTTON */
.btn-win-download {
    background: #10b981;
    color: #ffffff;
    border: 1px solid #059669;
    padding: 6px 16px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.2s;
}
.btn-win-download:hover {
    background: #059669;
}

.dialog-footer {
    background: #f1f5f9;
    border-top: 1.5px solid #cbd5e1;
    padding: 14px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.btn-dialog-save {
    background: #16a34a;
    color: #ffffff;
    border: 1px solid #15803d;
    padding: 10px 30px;
    font-size: 14.5px;
    font-weight: 700;
    border-radius: 5px;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(22, 163, 74, 0.3);
}
.btn-dialog-save:hover {
    background: #15803d;
}

.btn-dialog-cancel {
    background: #e2e8f0;
    color: #334155;
    border: 1px solid #cbd5e1;
    padding: 10px 20px;
    font-size: 14.5px;
    font-weight: 600;
    border-radius: 5px;
    cursor: pointer;
}
.btn-dialog-cancel:hover {
    background: #cbd5e1;
}
</style>
</head>

<body>

<!-- TOP MENU -->
<div class="top-menu">
    <ul>
        <li>
            <a href="mainform.php">Home</a>
        </li>
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
                <a href="#" style="cursor:default;">
                    Status: <span id="connStatus">DISCONNECTED</span>
                </a>
            </div>
        </li>

        <!-- CAMERA MODULE MENU -->
        <li>
            <a href="#">Camera ▾</a>
            <div class="dropdown-menu">
                <a href="#" onclick="toggleGlobalCamera(); return false;">
                    🔘 Camera: <span id="camGlobalStatusText" style="font-weight:bold;color:#dc2626;">OFF</span>
                </a>
                <a href="#" onclick="openCameraSettings(); return false;">
                    ⚙ Camera Settings
                </a>
            </div>
        </li>

        <!-- PRINT LAYOUT & CUSTOM FIELD SETTINGS MENU -->
        <li>
            <a href="#">Print Layout ▾</a>
            <div class="dropdown-menu">
                <a href="#" onclick="openPrintSettings(); return false;">
                    📄 Select Print Layout
                </a>
                <a href="#" onclick="openPrintSettings(); return false;">
                    📋 Field Visibility & Labels
                </a>
                <a href="#" onclick="openPrintSettings(); return false;">
                    🖼️ Upload Company Logo
                </a>
            </div>
        </li>

        <?php if ($_SESSION['role'] === 'admin'): ?>
        <li>
            <a href="#">Settings ▾</a>
            <div class="dropdown-menu">
                <a href="?action=backup" onclick="return confirm('Do you want to take database backup now?');">
                    📦 Backup Database
                </a>
                <a href="#" class="disabled-link" onclick="return false;">
                    ♻ Restore Database (Disabled)
                </a>
                <a href="?action=reset" onclick="return confirm('Normal Reset? Pending slips must be completed.');">
                    🔄 Reset Slip Number
                </a>
                <a href="?action=reset&force=1" onclick="return confirm('FORCE RESET will delete even running weighments. Are you sure?');">
                    ⚠ Force Reset
                </a>
            </div>
        </li>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'admin'): ?>
        <li>
            <a href="admin_page.php">Dashboard</a>
        </li>
        <?php endif; ?>

        <li>
            <a href="?action=logout" onclick="return confirm('Are you sure you want to logout?');">
                Logout
            </a>
        </li>
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

        <?php if ($gst !== '' && $gst !== '-'): ?>
            GST: <?= htmlspecialchars($gst); ?>
        <?php endif; ?>

        <?php if ($phone !== '' && $phone !== '-'): ?>
            <?php if ($gst !== '' && $gst !== '-'): ?> | <?php endif; ?>
            Phone: <?= htmlspecialchars($phone); ?>
        <?php endif; ?>

        <?php if ($email !== '' && $email !== '-'): ?>
            <br>
            Email: <?= htmlspecialchars($email); ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($_SESSION['msg'])): ?>
<div style="background:#dcfce7;color:#166534;padding:10px;margin:10px;">
    <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
<div id="errorBox" style="background:#fee2e2;color:#991b1b;padding:10px;margin:10px;">
    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<script src="scale.js?v=<?= time(); ?>"></script>

<!-- SCALE CONNECT DIALOG -->
<div id="scaleDialog" style="
    display:none;
    position:fixed;
    top:0;left:0;right:0;bottom:0;
    background:rgba(0,0,0,0.4);
    z-index:9999;
">
    <div style="
        background:#fff;
        width:320px;
        padding:20px;
        margin:120px auto;
        text-align:center;
        border-radius:8px;
        box-shadow:0 10px 25px rgba(0,0,0,0.3);
    ">
        <h3>Connect Weighbridge</h3>
        <p>Click below to select COM Port</p>

        <button onclick="connectScaleDirect()" style="padding:10px 18px;font-size:16px;cursor:pointer;">
            CONNECT SCALE
        </button>

        <br><br>
        <button onclick="closeScaleDialog()">Cancel</button>
    </div>
</div>

<!-- ============================================================
     PRINT SETTINGS, FIELD VISIBILITY & LOGO DIALOG (DOWNLOAD BUTTON)
     ============================================================ -->
<div id="printSettingsModal">
    <div class="enterprise-dialog-box">
        <div class="dialog-header">
            <h2>📄 Print Slip Layout & Field Configuration</h2>
            <button class="dialog-close-btn" onclick="closePrintSettings()">✕</button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_print_settings" value="1">

            <div class="dialog-body">
                <!-- Section 1: Template Selection -->
                <div class="win-groupbox">
                    <div class="win-group-title">
                        <span>1. Active Print Slip Layout</span>
                        <button type="button" class="btn-win-download" onclick="downloadTemplatesFromCloud(this)">
                            Download
                        </button>
                    </div>
                    <div>
                        <select id="local_print_layout_select" name="selected_print_layout" style="width:100%;padding:9px 12px;border:1.5px solid #cbd5e1;border-radius:5px;font-size:14px;font-weight:700;color:#0f172a;">
                            <option value="format_standard_a4_portrait" <?= ($activePrintFormat==='format_standard_a4_portrait')?'selected':'' ?>>1. Standard A4 Portrait Slip (Clean Dashed Layout)</option>
                            <option value="format_dual_landscape_cctv" <?= ($activePrintFormat==='format_dual_landscape_cctv')?'selected':'' ?>>2. Dual-Copy Top-Bottom A4 (Stacked 2-in-1 with CCTV)</option>
                            <option value="format_single_dotmatrix" <?= ($activePrintFormat==='format_single_dotmatrix')?'selected':'' ?>>3. Single Copy Continuous (TVS MSP 240 Dot-Matrix)</option>
                            <option value="format_triplicate_landscape" <?= ($activePrintFormat==='format_triplicate_landscape')?'selected':'' ?>>4. Triplicate Copy Landscape A4 (3 Copies in 1 Sheet)</option>
                        </select>
                    </div>
                </div>

                <!-- Section 2: Field Visibility & Custom Labels (Large 2-Column Grid) -->
                <div class="win-groupbox">
                    <div class="win-group-title">2. Slip Fields Visibility & Custom Print Labels</div>
                    <div class="win-fields-grid">
                        <?php foreach ($standardFieldsDef as $fKey => $fMeta): 
                            $isVis = isset($savedFieldConfig[$fKey]) ? ((int)$savedFieldConfig[$fKey]['is_visible'] === 1) : ((int)$fMeta['default_vis'] === 1);
                            $curLbl = !empty($savedFieldConfig[$fKey]['custom_label']) ? $savedFieldConfig[$fKey]['custom_label'] : $fMeta['default_label'];
                        ?>
                        <div class="win-field-row">
                            <input type="checkbox" name="field_settings[<?= $fKey ?>][visible]" value="1" <?= $isVis ? 'checked' : '' ?> title="Check to show on slip">
                            <span class="field-lbl" title="<?= htmlspecialchars($fMeta['name']) ?>"><?= htmlspecialchars($fMeta['name']) ?></span>
                            <input type="text" name="field_settings[<?= $fKey ?>][label]" value="<?= htmlspecialchars($curLbl) ?>" placeholder="Print Label">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Section 3: Company Logo Upload -->
                <div class="win-groupbox" style="margin-bottom:0;">
                    <div class="win-group-title">3. Company Logo for Print Slip</div>
                    <div style="display:flex;align-items:center;gap:18px;">
                        <div style="width:80px;height:60px;border:1.5px solid #cbd5e1;border-radius:6px;display:flex;align-items:center;justify-content:center;background:#f8fafc;overflow:hidden;">
                            <img id="logo_preview" src="LogoPrint.png?v=<?= time(); ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;" onerror="this.src='gblogo.jpeg'">
                        </div>
                        <div style="flex:1;">
                            <input type="file" name="company_logo" accept="image/*" onchange="previewSelectedLogo(this)" style="font-size:13px;color:#334155;">
                            <div style="font-size:12px;color:#64748b;margin-top:3px;">Upload PNG, JPG, or JPEG logo for header integration</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dialog-footer">
                <button type="button" class="btn-dialog-cancel" onclick="closePrintSettings()">Cancel</button>
                <button type="submit" class="btn-dialog-save">💾 Save Print Settings</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     CAMERA SETTINGS MODAL
     ============================================================ -->
<div id="cameraSettingsModal">
    <div class="enterprise-dialog-box">
        <div class="dialog-header">
            <h2>📷 Camera Configuration</h2>
            <button class="dialog-close-btn" onclick="closeCameraSettings()">✕</button>
        </div>

        <div class="dialog-body">
            <div class="win-groupbox" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <span style="font-weight:800;font-size:14.5px;color:#1e3a68;">Enable Live Cameras Grid</span>
                <label style="display:inline-flex;align-items:center;gap:8px;font-size:13.5px;font-weight:bold;cursor:pointer;">
                    <input type="checkbox" id="modal_global_cam_toggle" onchange="updateMasterToggleState(this.checked)" style="width:22px;height:22px;accent-color:#16a34a;">
                    <span>Active</span>
                </label>
            </div>

            <?php for($i=1; $i<=4; $i++): ?>
            <div class="win-groupbox">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:13.5px;font-weight:700;color:#2563eb;">CAMERA <?= $i ?> (CHANNEL <?= $i ?>)</span>
                    <label style="font-size:12.5px;font-weight:600;color:#475569;cursor:pointer;display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" id="cam_enabled_<?= $i ?>" style="width:18px;height:18px;accent-color:#16a34a;" checked> Enabled
                    </label>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:3px;">Live Camera URL</label>
                    <input type="text" id="cam_url_<?= $i ?>" placeholder="http://192.168.1.1<?= $i ?>/cgi-bin/mjpg/video.cgi?channel=1&subtype=1" style="width:100%;padding:8px 10px;border:1.5px solid #cbd5e1;border-radius:4px;font-size:13px;">
                </div>
                <div style="display:flex;gap:14px;">
                    <div style="flex:1;">
                        <label style="display:block;font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:3px;">Username</label>
                        <input type="text" id="cam_user_<?= $i ?>" value="admin" style="width:100%;padding:8px 10px;border:1.5px solid #cbd5e1;border-radius:4px;font-size:13px;">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block;font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:3px;">Password</label>
                        <input type="password" id="cam_pass_<?= $i ?>" value="" style="width:100%;padding:8px 10px;border:1.5px solid #cbd5e1;border-radius:4px;font-size:13px;">
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <div class="dialog-footer">
            <button type="button" class="btn-dialog-cancel" onclick="closeCameraSettings()">Cancel</button>
            <button onclick="saveCameraSettings()" class="btn-dialog-save">💾 Save Settings</button>
        </div>
    </div>
</div>

<script>
/* ============================================================
   PRINT SETTINGS MODAL CONTROLS & LIVE CLOUD DOWNLOADER
   ============================================================ */
function openPrintSettings() {
    document.getElementById("printSettingsModal").style.display = "block";
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

function downloadTemplatesFromCloud(btn) {
    const selectEl = document.getElementById("local_print_layout_select");
    const originalText = btn ? btn.innerText : "Download";
    if (selectEl) selectEl.disabled = true;
    if (btn) btn.innerText = "⏳ Downloading...";

    fetch("https://cloud.online-weighing.in/weighment_printslip.php?action=get_active_formats")
        .then(res => res.json())
        .then(data => {
            if (data.status === "success" && data.formats && data.formats.length > 0) {
                // 1. Download & store template codes into local disk and database
                fetch("header.php?action=store_cloud_templates", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ formats: data.formats })
                })
                .then(() => {
                    // 2. Populate UI Dropdown
                    const currentVal = selectEl.value;
                    selectEl.innerHTML = "";
                    data.formats.forEach((fmt, idx) => {
                        const opt = document.createElement("option");
                        opt.value = fmt.format_key || fmt.id;
                        opt.text = (idx + 1) + ". " + fmt.name + " (" + (fmt.copies_layout || fmt.paper_size || "") + ")";
                        if ((fmt.format_key || fmt.id) === currentVal) opt.selected = true;
                        selectEl.appendChild(opt);
                    });

                    if (selectEl) selectEl.disabled = false;
                    if (btn) btn.innerText = originalText;
                    alert("✓ Downloaded and saved " + data.formats.length + " live template formats from Cloud into your local machine!");
                })
                .catch(() => {
                    if (selectEl) selectEl.disabled = false;
                    if (btn) btn.innerText = originalText;
                    alert("✓ Templates downloaded!");
                });
            } else {
                if (selectEl) selectEl.disabled = false;
                if (btn) btn.innerText = originalText;
                alert("Download notice: No new formats found on cloud.");
            }
        })
        .catch(err => {
            if (selectEl) selectEl.disabled = false;
            if (btn) btn.innerText = originalText;
            alert("Download notice: Using local cached formats.");
        });
}

/* ============================================================
   CAMERA CONTROLS
   ============================================================ */
function isCameraActive() {
    return localStorage.getItem("weighbridge_cam_active") === "true";
}

function updateCameraMenuUI() {
    const el = document.getElementById("camGlobalStatusText");
    const active = isCameraActive();
    if (el) {
        el.innerText = active ? "ON" : "OFF";
        el.style.color = active ? "#16a34a" : "#dc2626";
    }
    const modalCheck = document.getElementById("modal_global_cam_toggle");
    if (modalCheck) {
        modalCheck.checked = active;
    }
}

function updateMasterToggleState(isChecked) {
    localStorage.setItem("weighbridge_cam_active", isChecked ? "true" : "false");
    updateCameraMenuUI();
    if (typeof applyCameraGridVisibility === "function") {
        applyCameraGridVisibility();
    }
}

function toggleGlobalCamera() {
    const currentState = isCameraActive();
    const newState = !currentState;
    localStorage.setItem("weighbridge_cam_active", newState ? "true" : "false");
    updateCameraMenuUI();

    if (typeof applyCameraGridVisibility === "function") {
        applyCameraGridVisibility();
    } else {
        window.location.reload();
    }
}

function openCameraSettings() {
    loadCameraSettingsIntoModal();
    document.getElementById("cameraSettingsModal").style.display = "block";
}

function closeCameraSettings() {
    document.getElementById("cameraSettingsModal").style.display = "none";
}

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

    if (typeof applyCameraGridVisibility === "function") {
        applyCameraGridVisibility();
    }
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