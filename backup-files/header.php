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
    // 🌐 Record logout event in cloud database before clearing local session
    if (function_exists('logCompanyCloudActivity')) {
        logCompanyCloudActivity($conn, $_SESSION['username'] ?? 'admin', 'Logged Out', 'logout');
    }
    session_unset();
    session_destroy();
    header("Location: login_php.php");
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
    font-family: "Segoe UI", Arial, sans-serif;
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
    min-width:220px;
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
   LIGHT-THEME GLASSMORPHISM CAMERA SETTINGS MODAL (WIDE)
   ============================================================ */
#cameraSettingsModal {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 99999;
    overflow-y: auto;
    padding: 30px 10px;
}

.glass-modal-card {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.6) inset;
    color: #1e293b;
    width: 95%;
    max-width: 820px;
    margin: 25px auto;
    padding: 26px 32px;
    border-radius: 16px;
    position: relative;
}

.glass-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 14px;
    margin-bottom: 18px;
}

.glass-modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    letter-spacing: 0.5px;
    color: #0f172a;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 8px;
}

.glass-close-btn {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #475569;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}
.glass-close-btn:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #fca5a5;
}

/* Master Toggle Card (Light) */
.master-toggle-card {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
}

.master-toggle-label {
    font-weight: 700;
    font-size: 15px;
    color: #0f172a;
}

/* Modern iOS-Style Toggle Switch */
.switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
    margin: 0;
}
.switch input { 
    opacity: 0;
    width: 0;
    height: 0;
}
.slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #cbd5e1;
    transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 34px;
    border: 1px solid #94a3b8;
}
.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.25);
}
input:checked + .slider {
    background-color: #10b981;
    border-color: #059669;
}
input:checked + .slider:before {
    transform: translateX(24px);
}

/* Individual Camera Card (Light) */
.glass-cam-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 14px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.glass-cam-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
}

.glass-cam-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.glass-cam-title {
    font-size: 13px;
    font-weight: 700;
    color: #2563eb;
    letter-spacing: 0.5px;
}

.glass-cam-field {
    margin-bottom: 8px;
}
.glass-cam-field label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.glass-cam-field input {
    width: 100%;
    padding: 8px 12px;
    box-sizing: border-box;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    color: #0f172a;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}
.glass-cam-field input:focus {
    outline: none;
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.glass-save-btn {
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #ffffff;
    border: none;
    padding: 12px 32px;
    font-size: 14px;
    font-weight: 700;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);
    transition: all 0.2s;
}
.glass-save-btn:hover {
    background: linear-gradient(135deg, #15803d, #166534);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(22, 163, 74, 0.45);
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
     LIGHT-THEME GLASSMORPHISM CAMERA SETTINGS MODAL (WIDE: 820PX)
     ============================================================ -->
<div id="cameraSettingsModal">
    <div class="glass-modal-card">
        <div class="glass-modal-header">
            <h2>📷 Camera Settings</h2>
            <button class="glass-close-btn" onclick="closeCameraSettings()">✕</button>
        </div>

        <!-- Master Toggle -->
        <div class="master-toggle-card">
            <span class="master-toggle-label">Enable Live Cameras Grid</span>
            <label class="switch">
                <input type="checkbox" id="modal_global_cam_toggle" onchange="updateMasterToggleState(this.checked)">
                <span class="slider"></span>
            </label>
        </div>

        <!-- 4 Channels Configuration (Live Camera URL) -->
        <?php for($i=1; $i<=4; $i++): ?>
        <div class="glass-cam-card">
            <div class="glass-cam-header">
                <span class="glass-cam-title">CAMERA <?= $i ?> (CHANNEL <?= $i ?>)</span>
                <label style="font-size:12px;font-weight:600;color:#475569;cursor:pointer;display:flex;align-items:center;gap:6px;">
                    <input type="checkbox" id="cam_enabled_<?= $i ?>" style="width:16px;height:16px;accent-color:#10b981;" checked> Enabled
                </label>
            </div>
            <div class="glass-cam-field">
                <label>Live Camera URL</label>
                <input type="text" id="cam_url_<?= $i ?>" placeholder="http://192.168.1.1<?= $i ?>/cgi-bin/mjpg/video.cgi?channel=1&subtype=1">
            </div>
            <div style="display:flex;gap:12px;">
                <div class="glass-cam-field" style="flex:1;">
                    <label>Username</label>
                    <input type="text" id="cam_user_<?= $i ?>" value="admin">
                </div>
                <div class="glass-cam-field" style="flex:1;">
                    <label>Password</label>
                    <input type="password" id="cam_pass_<?= $i ?>" value="">
                </div>
            </div>
        </div>
        <?php endfor; ?>

        <div style="text-align:right;margin-top:20px;">
            <button onclick="saveCameraSettings()" class="glass-save-btn">
                💾 SAVE SETTINGS
            </button>
        </div>
    </div>
</div>

<script>
/* ============================================================
   LIGHT GLASSMORPHISM CAMERA CONTROLLER
   ============================================================ */
function isCameraActive() {
    return localStorage.getItem("weighbridge_cam_active") === "true";
}

function updateCameraMenuUI() {
    const el = document.getElementById("camGlobalStatusText");
    const active = isCameraActive();
    if (el) {
        el.innerText = active ? "ON" : "OFF";
        el.style.color = active ? "#10b981" : "#dc2626";
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
   Guarantees last_logout_time is sent when window is closed
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