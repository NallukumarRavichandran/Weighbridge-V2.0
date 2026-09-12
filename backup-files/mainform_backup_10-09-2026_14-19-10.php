<?php
/* ============================================================
   LIVE CAMERA STREAMING & SNAPSHOT PROXY (NON-BLOCKING)
   ============================================================ */
if (isset($_GET['camera'])) 
{
    $camNum   = (int)$_GET['camera'];
    $camUrl   = $_GET['url'] ?? '';
    $camUser  = trim($_GET['user'] ?? 'admin');
    $camPass  = trim($_GET['pass'] ?? '');
    $isStream = isset($_GET['stream']) && $_GET['stream'] == '1';

    // Normalize 'admin' case-insensitively so cameras running Linux firmware are never rejected with 401
    if (strcasecmp($camUser, 'admin') === 0) {
        $camUser = 'admin';
    }

    // Fallback to local config.php if present
    if (empty($camUrl) && file_exists(__DIR__ . '/config.php')) {
        $wbConfig = include __DIR__ . '/config.php';
        if (isset($wbConfig['cameras'][$camNum])) {
            $cam     = $wbConfig['cameras'][$camNum];
            $camUrl  = $cam['url'] ?? '';
            $camUser = $cam['username'] ?? 'admin';
            $camPass = $cam['password'] ?? '';
        }
    }

    if (!empty($camUrl)) {
        $camUrl = trim($camUrl);
        if (!preg_match('#^https?://#i', $camUrl)) {
            $camUrl = 'http://' . $camUrl;
        }
        $parsed = parse_url($camUrl);
        if (!empty($parsed['user']) && empty($_GET['user'])) {
            $camUser = $parsed['user'];
        }
        if (!empty($parsed['pass']) && empty($_GET['pass'])) {
            $camPass = $parsed['pass'];
        }

        // Ensure session is written and closed so Apache threads remain 100% non-blocking
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // MODE 1: Continuous Native Live MJPEG Video Stream (for Browser Live Video Display)
        if ($isStream) {
            if (ob_get_level()) ob_end_clean();
            set_time_limit(0);
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);
            ob_implicit_flush(true);

            header("Cache-Control: no-cache, no-store, must-revalidate");
            header("Pragma: no-cache");
            header("Expires: 0");
            header("Content-Type: multipart/x-mixed-replace; boundary=myboundary");

            $ch = curl_init($camUrl);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "$camUser:$camPass");
            curl_setopt($ch, CURLOPT_TIMEOUT, 0); // Infinite continuous live stream
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($c, $chunk) {
                if (connection_aborted()) {
                    return 0; // Terminate stream cleanly when browser closes image
                }
                echo $chunk;
                @ob_flush();
                flush();
                return strlen($chunk);
            });
            curl_exec($ch);
            curl_close($ch);
            exit;
        }

        // MODE 2: Single-Frame Snapshot Capture (for Weighment Slip Printing or Thumbnail)
        $isMjpeg = (stripos($camUrl, 'mjpg') !== false || stripos($camUrl, 'video.cgi') !== false);
        if ($isMjpeg) {
            $buffer = '';
            $ch = curl_init($camUrl);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST | CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "$camUser:$camPass");
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($c, $chunk) use (&$buffer) {
                $buffer .= $chunk;
                $start = strpos($buffer, "\xFF\xD8");
                if ($start !== false) {
                    $end = strpos($buffer, "\xFF\xD9", $start + 2);
                    if ($end !== false) {
                        return 0; // Complete JPEG frame captured
                    }
                }
                return strlen($chunk);
            });
            curl_exec($ch);
            curl_close($ch);

            $start = strpos($buffer, "\xFF\xD8");
            $end   = ($start !== false) ? strpos($buffer, "\xFF\xD9", $start + 2) : false;
            if ($start !== false && $end !== false) {
                $jpeg = substr($buffer, $start, $end - $start + 2);
                if (ob_get_length()) ob_clean();
                header("Content-Type: image/jpeg");
                header("Content-Length: " . strlen($jpeg));
                echo $jpeg;
                exit;
            }
        } else {
            // Standard static snapshot URL fallback
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
                if (ob_get_length()) ob_clean();
                header("Content-Type: image/jpeg");
                header("Content-Length: " . strlen($img));
                echo $img;
                exit;
            }
        }
    }

    http_response_code(404);
    exit;
}

require_once "header.php";

$company = getCompany($conn);
$company_id = $company['id'];
$pending_action = $_SESSION['pending_action'] ?? '';
?>
	
<?php if (!empty($_SESSION['weight_warning'])): ?>
<div style="
    color:#b00000;
    background:#ffe5e5;
    border:2px solid #b00000;
    padding:10px;
    margin:10px auto;
    width:80%;
    text-align:center;
    font-weight:bold;
">
    <?= htmlspecialchars($_SESSION['weight_warning']); ?>
</div>
<?php unset($_SESSION['weight_warning']); endif; ?>

<?php
/* VEHICLES FOR SECOND WEIGHMENT */
$vehicleData = [];

$q = $conn->query("
    SELECT id, slip_no, vehicle_no,
           first_weight, first_date, first_time, gt_type,
           company_id, user_id
    FROM weighments
    WHERE company_id = $company_id
    ORDER BY id DESC
");

while ($r = $q->fetch_assoc()) {
    $dyn = [];
    $dq = $conn->query("
        SELECT field_id, field_value
        FROM weighment_field_values
        WHERE weighment_id = {$r['slip_no']}
    ");
    while ($d = $dq->fetch_assoc()) {
        $dyn[$d['field_id']] = $d['field_value'];
    }
    $r['dynamic'] = $dyn;
    $vehicleData[] = $r;
}

/* DYNAMIC FIELDS */
$fields = [];
$res = $conn->query("
    SELECT *
    FROM weighment_fields
    WHERE company_id = $company_id
      AND is_active = 1
    ORDER BY field_order, id
");
while ($row = $res->fetch_assoc()) {
    $fields[] = $row;
}

/* SLIP NUMBER */
$slip_no = getNextSlipNumber($conn);
?>

<script>
const VEHICLE_DATA = <?php echo json_encode($vehicleData); ?>;
let secondMode = false;
let secondType = null;
</script>

<style>
body{font-family:Arial;background:#0033cc;color:white;margin:0}
.title{background:#0000aa;text-align:center;padding:6px;font-size:18px}
.container{display:flex;padding:15px}
.left{width:45%;padding:10px}
.right{width:55%;padding:10px}
label{display:inline-block;width:120px}
input,select{padding:6px;width:280px}
input[type=text]{text-transform:uppercase}
#cameraSettingsModal input,
.win95-dialog input,
.camera-grid-section input {
    text-transform: none !important;
}
.field-group{margin-bottom:18px}
.readonly{background:#ddd}
.weight-box{background:#111;height:160px;margin-bottom:15px;border:3px inset #aaa;
display:flex;align-items:center;justify-content:center;font-size:120px;font-weight:bold;color:#00ff00}
.weight-panel{border:2px solid #ccc;padding:10px}
.weight-panel input{width:110px}
button{padding:6px 15px;font-weight:bold}
#gross_date_ui,#gross_time_ui,#tare_date_ui,#tare_time_ui{
width:160px;padding:8px;font-weight:bold;text-align:center;background:#eee;color:#000;border:2px inset #666}
.row{
    display:flex;
    align-items:center;
    margin-bottom:12px;
}
.row label{
    width:80px;
    font-weight:bold;
}
.row input{
    margin-right:10px;
    padding:5px;
}
.row button{
    margin-right:10px;
}
.action-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}
.left-buttons,
.right-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
}
.right-buttons {
    margin-left: auto;
}
.save-btn {
    background-color: #28a745;
    color: white;
}
.cancel-btn {
    background-color: #dc3545;
    color: white;
}
.reprint-btn {
    background-color: #007bff;
    color: white;
}
.manual-btn {
    background-color: #f59e0b;
    color: white;
}
.save-btn,
.cancel-btn,
.reprint-btn,
.manual-btn {
    border: none;
    padding: 10px 16px;
    border-radius: 5px;
    font-weight: bold;
    cursor: pointer;
}
.save-btn:hover { background-color: #218838; }
.cancel-btn:hover { background-color: #c82333; }
.reprint-btn:hover { background-color: #0069d9; }
.manual-btn:hover { background-color: #d97706; }

/* ============================================================
   ENTERPRISE 2X2 CAMERA CCTV GRID
   ============================================================ */
.camera-grid-section {
    margin-top: 15px;
    width: 100%;
}
.camera-grid {
    display: grid;
    gap: 10px;
    width: 100%;
}
.camera-card {
    position: relative;
    background: #000000;
    border: 1px solid #ffffff;
    width: 100%;
    aspect-ratio: 16 / 9;
    border-radius: 4px;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.cam-top-overlay {
    position: absolute;
    top: 8px;
    left: 8px;
    right: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 10;
}
.cam-card-title {
    color: #ffffff;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 0.5px;
    text-shadow: 1px 1px 2px #000;
}
.cam-btn-crop {
    background: rgba(0, 0, 0, 0.7);
    color: #ffffff;
    border: 1px solid #ffffff;
    border-radius: 3px;
    font-size: 10px;
    font-weight: bold;
    padding: 3px 7px;
    cursor: pointer;
    text-transform: uppercase;
}
.cam-btn-crop:hover {
    background: #ffffff;
    color: #000000;
}
.cam_live_feed {
    width: 100%;
    height: 100%;
    object-fit: fill;
    display: block;
}
.cam_live_feed.fit-contain {
    object-fit: contain;
}
.cam-offline-text {
    color: #ff9800;
    font-size: 14px;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
}
</style>

<script>
// Validates that the vehicle number is entered (allows any alphanumeric format up to 25 chars)
function validateVehicleNumber() {
    const vInput = document.getElementById("vehicle_no");
    const vehicle = vInput ? vInput.value.trim().toUpperCase() : "";

    if (vehicle === "") {
        alert("Please enter Vehicle Number!");
        if (vInput) vInput.focus();
        return false;
    }

    const pattern = /^[A-Z0-9]{1,25}$/;
    if (!pattern.test(vehicle)) {
        alert("Vehicle Number must contain only letters and numbers!");
        if (vInput) vInput.focus();
        return false;
    }

    return true;
}

window.weightFrozen = false;
var frozenWeight = "";

/* LOAD SPECIFIC SLIP DATA INTO FORM */
function loadVehicleDataIntoForm(index) {
    let v = VEHICLE_DATA[index];
    if (!v) return;

    secondMode = true;
    window.weightFrozen = false; // Always keep live stream active

    const slipEl = document.querySelector("[name='slip_no']");
    const vehEl = document.querySelector("[name='vehicle_no']");
    if (slipEl) slipEl.value = v.slip_no;
    if (vehEl) vehEl.value = v.vehicle_no;

    const overwriteEl = document.getElementById("overwrite");
    if (overwriteEl) overwriteEl.value = "1";

    let gt = document.querySelector("[name='gt_type']");
    if (gt) {
        gt.value = v.gt_type;
        gt.disabled = true;
    }

    document.getElementById("gross_weight").value = "";
    document.getElementById("tare_weight").value = "";
    document.getElementById("net_weight").value = "";

    document.getElementById("gross_date_ui").value = "";
    document.getElementById("gross_time_ui").value = "";
    document.getElementById("tare_date_ui").value = "";
    document.getElementById("tare_time_ui").value = "";

    document.getElementById("gross_date").value = "";
    document.getElementById("gross_time").value = "";
    document.getElementById("tare_date").value = "";
    document.getElementById("tare_time").value = "";

    if (v.gt_type === "G") {
        document.getElementById("gross_weight").value = v.first_weight;
        document.getElementById("gross_date_ui").value = v.first_date;
        document.getElementById("gross_time_ui").value = v.first_time;
        document.getElementById("gross_date").value = v.first_date;
        document.getElementById("gross_time").value = v.first_time;
        secondType = "tare";
    } else {
        document.getElementById("tare_weight").value = v.first_weight;
        document.getElementById("tare_date_ui").value = v.first_date;
        document.getElementById("tare_time_ui").value = v.first_time;
        document.getElementById("tare_date").value = v.first_date;
        document.getElementById("tare_time").value = v.first_time;
        secondType = "gross";
    }

    if (v.dynamic) {
        Object.keys(v.dynamic).forEach(fid => {
            let el = document.querySelector(`[name="dyn_${fid}"]`);
            if (el) el.value = v.dynamic[fid];
        });
    }
    calculateNet();
}

document.addEventListener("DOMContentLoaded", function () {
    const vSelect = document.getElementById("vehicle_select");
    if (vSelect) {
        vSelect.addEventListener("change", function () {
            if (this.value !== "") {
                loadVehicleDataIntoForm(this.value);
            }
        });
    }
});

/* SECOND WEIGHMENT MODE */
function startSecondWeighment() {
    let err = document.getElementById("errorBox");
    if(err) err.style.display = "none";

    window.weightFrozen = false; // Keep scale stream continuous

    const vNo = document.getElementById("vehicle_no");
    const vSel = document.getElementById("vehicle_select");

    if (vNo) vNo.style.display = "none";
    if (vSel) {
        vSel.style.display = "inline-block";
        vSel.innerHTML = "<option value=''>SELECT VEHICLE</option>";

        VEHICLE_DATA.forEach((v, i) => {
            let o = document.createElement("option");
            o.value = i;
            let typeLabel = (v.gt_type === "G") ? "GROSS" : "TARE";
            o.text = `${v.vehicle_no} - Slip #${v.slip_no} (${typeLabel}: ${v.first_weight} Kg) [${v.first_time}]`;
            vSel.appendChild(o);
        });

        vSel.focus();
    }
}

/* SMART VEHICLE TYPING AUTO-LOADER */
function handleVehicleTyping(val) {
    if (secondMode) return;
    const cleanVal = val.trim().toUpperCase();
    if (cleanVal.length < 3) return;

    const matches = [];
    VEHICLE_DATA.forEach((v, i) => {
        if (v.vehicle_no.toUpperCase() === cleanVal) {
            matches.push({ index: i, data: v });
        }
    });

    if (matches.length === 1) {
        loadVehicleDataIntoForm(matches[0].index);
    } else if (matches.length > 1) {
        startSecondWeighment();
        const vSel = document.getElementById("vehicle_select");
        if (vSel) {
            vSel.innerHTML = "<option value=''>SELECT VEHICLE</option>";
            matches.forEach(m => {
                let o = document.createElement("option");
                o.value = m.index;
                let typeLabel = (m.data.gt_type === "G") ? "GROSS" : "TARE";
                o.text = `${m.data.vehicle_no} - Slip #${m.data.slip_no} (${typeLabel}: ${m.data.first_weight} Kg) [${m.data.first_time}]`;
                vSel.appendChild(o);
            });
        }
    }
}

/* DATE TIME */
function setDateTime(type) {
    let now = new Date();
    let d = String(now.getDate()).padStart(2,'0') + "/" +
            String(now.getMonth()+1).padStart(2,'0') + "/" +
            now.getFullYear();
    let t = String(now.getHours()).padStart(2,'0') + ":" +
            String(now.getMinutes()).padStart(2,'0') + ":00";

    const dUi = document.getElementById(type+"_date_ui");
    const tUi = document.getElementById(type+"_time_ui");
    const dVal = document.getElementById(type+"_date");
    const tVal = document.getElementById(type+"_time");

    if (dUi) dUi.value = d;
    if (tUi) tUi.value = t;
    if (dVal) dVal.value = d;
    if (tVal) tVal.value = t;
}

/* RECORD WEIGHT (SEAMLESS CAPTURE WITHOUT FREEZING THE LIVE SCALE DISPLAY) */
function recordSelectedWeight() {
    const liveEl = document.getElementById("live_weight");
    let live = liveEl ? liveEl.innerText.trim() : "";

    if (!live || parseFloat(live) === 0 || isNaN(parseFloat(live))) {
        alert("NO WEIGHT DETECTED FROM SCALE");
        return;
    }

    // Capture the value into the input box while keeping the live indicator streaming
    window.weightFrozen = false; 
	
    if (secondMode) {
        const target = document.getElementById(secondType+"_weight");
        if (target) target.value = live;
        setDateTime(secondType);
    } else {
        let gtSelect = document.querySelector("[name='gt_type']");
        let gt = gtSelect ? gtSelect.value : "G";
        let type = (gt === "G") ? "gross" : "tare";
        const target = document.getElementById(type+"_weight");
        if (target) target.value = live;
        setDateTime(type);
    }
    calculateNet();
}

/* NET CALCULATION */
function calculateNet() {
    const grossEl = document.getElementById("gross_weight");
    const tareEl = document.getElementById("tare_weight");
    const netEl = document.getElementById("net_weight");

    if (!grossEl || !tareEl || !netEl) return;

    let g = parseFloat(grossEl.value);
    let t = parseFloat(tareEl.value);

    if (!isNaN(g) && !isNaN(t) && grossEl.value.trim() !== "" && tareEl.value.trim() !== "") {
        let net = Math.abs(g - t);
        netEl.value = (net % 1 !== 0) ? net.toFixed(2) : net;
    }
}

document.addEventListener("keydown", e => {
    if(e.code === "Space" && e.target.tagName !== "INPUT" && e.target.tagName !== "SELECT" && e.target.tagName !== "TEXTAREA"){
        e.preventDefault();
        recordSelectedWeight();
    }
});

/* RESET WEIGHMENT */
function resetWeighment() {
    let err = document.getElementById("errorBox");
    if(err) err.style.display = "none";

    window.weightFrozen = false;
    frozenWeight = "";

    const liveEl = document.getElementById("live_weight");
    if (liveEl) liveEl.innerText = "";
	
    // Reset ONLY the specific weighment form
    const form = document.getElementById("weighmentForm");
    if (form) form.reset();

    const overwriteEl = document.getElementById("overwrite");
    if (overwriteEl) overwriteEl.value = "0";

    secondMode = false;
    secondType = null;

    const vNo = document.getElementById("vehicle_no");
    const vSel = document.getElementById("vehicle_select");

    if (vNo) {
        vNo.style.display = "inline-block";
        vNo.value = "";
    }

    if (vSel) {
        vSel.style.display = "none";
        vSel.innerHTML = "";
    }

    const gt = document.querySelector("[name='gt_type']");
    if (gt) gt.disabled = false;

    document.getElementById("gross_weight").value = "";
    document.getElementById("tare_weight").value = "";
    document.getElementById("net_weight").value = "";

    document.getElementById("gross_date_ui").value = "";
    document.getElementById("gross_time_ui").value = "";
    document.getElementById("tare_date_ui").value = "";
    document.getElementById("tare_time_ui").value = "";

    document.getElementById("gross_date").value = "";
    document.getElementById("gross_time").value = "";
    document.getElementById("tare_date").value = "";
    document.getElementById("tare_time").value = "";

    if (vNo) vNo.focus();
}

function showReprintBox() {
    let box = document.getElementById("reprintBox");
    if (box) {
        box.style.display = "inline-block";
        const rSlip = document.getElementById("reprint_slip");
        if (rSlip) rSlip.focus();
    }
}

function reprintSlip() {
    let slipBox = document.getElementById("reprintBox");
    let slipInput = document.getElementById("reprint_slip");
    let slip = slipInput ? slipInput.value.trim() : "";

    if (slip === "") {
        alert("Please enter Slip Number");
        return;
    }

    if (slipInput) slipInput.value = "";
    if (slipBox) slipBox.style.display = "none";

    window.location.href = "print_slip.php?slip=" + encodeURIComponent(slip);
}

/* ATTACH SUBMIT LISTENER STRICTLY TO WEIGHMENT FORM ONLY */
document.addEventListener("DOMContentLoaded", function () {
    const weighForm = document.getElementById("weighmentForm");
    if (weighForm) {
        weighForm.addEventListener("submit", function(e) {
            if (!secondMode && !validateVehicleNumber()) {
                e.preventDefault();
                return;
            }

            let btn = document.getElementById("saveBtn");
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = "SAVING...";
            }
        });
    }
});

/* ============================================================
   AUTO-RECONNECT ON RELOAD (250MS SNAPPY SETTLE DELAY)
   ============================================================ */
window.addEventListener("beforeunload", function () {
    if (typeof disconnectScale === "function") {
        disconnectScale();
    }
});

window.addEventListener("load", function () {
    setTimeout(function () {
        if (typeof autoReconnect === "function") {
            autoReconnect();
        }
    }, 250);
});

/* ============================================================
   DYNAMIC CCTV CAMERA GRID CONTROLLER
   ============================================================ */
let cameraPollingTimer = null;

function applyCameraGridVisibility() {
    const gridContainer = document.getElementById("liveCameraGridContainer");
    const gridEl = document.getElementById("cameraGridEl");
    if (!gridContainer || !gridEl) return;

    const isGlobalActive = localStorage.getItem("weighbridge_cam_active") === "true";
    if (!isGlobalActive) {
        gridContainer.style.display = "none";
        stopLiveCameraStreaming();
        return;
    }

    let savedConfig = {};
    try {
        savedConfig = JSON.parse(localStorage.getItem("weighbridge_cam_config") || "{}");
    } catch(e) {}

    let enabledCams = [];
    for (let c = 1; c <= 4; c++) {
        const camData = savedConfig["cam_" + c];
        const cardEl = document.getElementById("cam_card_" + c);
        
        if (camData && camData.enabled !== false) {
            enabledCams.push(c);
            if (cardEl) cardEl.style.display = "flex";
        } else {
            if (cardEl) cardEl.style.display = "none";
        }
    }

    const count = enabledCams.length;

    if (count === 0) {
        gridContainer.style.display = "none";
        stopLiveCameraStreaming();
        return;
    }

    gridContainer.style.display = "block";

    if (count === 1) {
        gridEl.style.gridTemplateColumns = "1fr";
    } else {
        gridEl.style.gridTemplateColumns = "repeat(2, 1fr)";
    }
    enabledCams.forEach(c => {
        const el = document.getElementById("cam_card_" + c);
        if (el) el.style.height = "auto";
    });

    startLiveCameraStreaming();
}

function startLiveCameraStreaming() {
    stopLiveCameraStreaming();

    const isGlobalActive = localStorage.getItem("weighbridge_cam_active") === "true";
    if (!isGlobalActive) return;

    let savedConfig = {};
    try {
        savedConfig = JSON.parse(localStorage.getItem("weighbridge_cam_config") || "{}");
    } catch(e) {}

    for (let c = 1; c <= 4; c++) {
        const camData  = savedConfig["cam_" + c];
        const cardEl   = document.getElementById("cam_card_" + c);
        const imgEl    = document.getElementById("cam_live_" + c);
        const statusEl = document.getElementById("cam_status_" + c);

        if (camData && camData.enabled !== false && camData.url) {
            const rawUrl = camData.url.trim();
            let camUserVal = (camData.user || "admin").trim();
            if (camUserVal.toLowerCase() === "admin") {
                camUserVal = "admin";
            }
            const streamUrl = "mainform.php?camera=" + c + 
                            "&stream=1" +
                            "&url=" + encodeURIComponent(rawUrl) + 
                            "&user=" + encodeURIComponent(camUserVal) + 
                            "&pass=" + encodeURIComponent(camData.pass || "");

            if (imgEl) {
                imgEl.onload = function() {
                    imgEl.style.display = "block";
                    if (statusEl) statusEl.style.display = "none";
                };
                imgEl.onerror = function() {
                    imgEl.style.display = "none";
                    if (statusEl) {
                        statusEl.style.display = "block";
                        statusEl.innerText = "OFFLINE";
                    }
                    setTimeout(() => {
                        if (localStorage.getItem("weighbridge_cam_active") === "true" && imgEl.style.display === "none") {
                            imgEl.src = streamUrl + "&t=" + new Date().getTime();
                        }
                    }, 4000);
                };
                imgEl.src = streamUrl;
                imgEl.style.display = "block";
                if (statusEl) statusEl.style.display = "none";
            }
        } else {
            if (imgEl) {
                imgEl.src = "";
                imgEl.style.display = "none";
            }
            if (statusEl) {
                statusEl.style.display = "block";
                statusEl.innerText = "OFFLINE";
            }
        }
    }
}

function stopLiveCameraStreaming() {
    if (cameraPollingTimer) {
        clearInterval(cameraPollingTimer);
        cameraPollingTimer = null;
    }
    for (let c = 1; c <= 4; c++) {
        const imgEl = document.getElementById("cam_live_" + c);
        if (imgEl) {
            imgEl.src = "";
            imgEl.style.display = "none";
        }
    }
}

function toggleCamFit(camNum) {
    const img = document.getElementById("cam_live_" + camNum);
    const btn = document.getElementById("cam_fit_btn_" + camNum);
    if (img) {
        const isContain = img.classList.toggle("fit-contain");
        if (btn) {
            btn.innerText = isContain ? "FIT" : "FULL (16:9)";
        }
    }
}

function updateLiveCameraFeeds() {
    startLiveCameraStreaming();
}

document.addEventListener("DOMContentLoaded", function () {
    applyCameraGridVisibility();
});
</script>
</head>

<div class="title">WEIGHMENT RECORDING SYSTEM</div>

<!-- MAIN WEIGHMENT FORM WITH DEDICATED ID -->
<form id="weighmentForm" method="post" action="saveform.php">
<input type="hidden" id="overwrite" name="overwrite" value="0">
<div class="container">

<div class="left">
<div class="field-group">
<label>SLIP NO</label>
<input type="text" name="slip_no" value="<?= $slip_no ?>" readonly>
</div>

<div class="field-group">
<label>GROSS / TARE</label>
<select name="gt_type">
    <option value="G">GROSS</option>
    <option value="T">TARE</option>
</select>
</div>

<div class="field-group">
<label>VEHICLE NO</label>
<input
    type="text"
    name="vehicle_no"
    id="vehicle_no"
    maxlength="25"
    style="text-transform:uppercase;"
    placeholder="ENTER VEHICLE NO"
    oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g, ''); handleVehicleTyping(this.value);">
<select id="vehicle_select" style="display:none;width:220px;"></select>
</div>

<?php foreach($fields as $f): 
    $displayLabel = !empty($f['field_name']) ? $f['field_name'] : $f['field_label'];
?>
<div class="field-group">
<label><?= htmlspecialchars($displayLabel) ?></label>

<?php if($f['field_type'] == 'dropdown'): ?>
    <select name="dyn_<?= $f['id'] ?>">
        <option value="">-- Select --</option>
<?php
$values = array_map('trim', explode(',', $f['field_values']));
foreach($values as $v):
    $v = strtoupper(trim($v));
    $isSelected = ((stripos($f['field_name'], 'WEIGHT UNIT') !== false || stripos($f['field_label'], 'WEIGHT UNIT') !== false) && $v === 'KG') ? 'selected' : '';
?>
    <option value="<?= htmlspecialchars($v) ?>" <?= $isSelected ?>>
        <?= htmlspecialchars($v) ?>
    </option>
<?php endforeach; ?>
    </select>
<?php else: ?>
    <input
        type="<?= htmlspecialchars($f['field_type']) ?>"
        name="dyn_<?= $f['id'] ?>">
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<div class="right">
<div class="weight-box"><span id="live_weight"></span></div>

<div class="weight-panel">
<div class="row">
    <label>GROSS</label>
    <input id="gross_weight" name="gross_weight" readonly>
    <input id="gross_date_ui" readonly>
    <input id="gross_time_ui" readonly>
    <input type="hidden" id="gross_date" name="gross_date">
    <input type="hidden" id="gross_time" name="gross_time">
</div>

<div class="row">
    <label>TARE</label>
    <input id="tare_weight" name="tare_weight" readonly>
    <input id="tare_date_ui" readonly>
    <input id="tare_time_ui" readonly>
    <input type="hidden" id="tare_date" name="tare_date">
    <input type="hidden" id="tare_time" name="tare_time">
</div>

<div class="row">
    <label>NET</label>
    <input name="net_weight" id="net_weight" readonly>
</div>

<div class="row action-row">
    <div class="left-buttons">
        <button type="button" onclick="recordSelectedWeight()">RECORD WEIGHT</button>
        <button type="button" onclick="startSecondWeighment()">SECOND WEIGHMENT</button>
    </div>
    <div class="right-buttons">
        <button type="submit" id="saveBtn" class="save-btn">SAVE</button>
        <button type="button" onclick="resetWeighment()" class="cancel-btn">CANCEL</button>
        <button type="button" onclick="showReprintBox()" class="reprint-btn">RE-PRINT</button>
    </div>
</div>
</div>

<!-- CCTV CAMERA GRID -->
<div id="liveCameraGridContainer" class="camera-grid-section" style="display:none;">
    <div class="camera-grid" id="cameraGridEl">
        <?php for ($c = 1; $c <= 4; $c++): ?>
        <div class="camera-card" id="cam_card_<?= $c ?>">
            <div class="cam-top-overlay">
                <span class="cam-card-title">CAMERA <?= $c ?></span>
                <button type="button" class="cam-btn-crop" id="cam_fit_btn_<?= $c ?>" onclick="toggleCamFit(<?= $c ?>)">FULL (16:9)</button>
            </div>
            <div id="cam_status_<?= $c ?>" class="cam-offline-text">OFFLINE</div>
            <img class="cam_live_feed" id="cam_live_<?= $c ?>" data-cam="<?= $c ?>" src="" style="display:none;">
        </div>
        <?php endfor; ?>
    </div>
</div>

<div style="text-align:center;margin-top:15px;">
    <span id="reprintBox" style="display:none;margin-left:10px;">
        <input type="text"
               id="reprint_slip"
               placeholder="Slip No"
               style="width:120px;padding:6px;font-weight:bold;text-align:center;">
        <button type="button" onclick="reprintSlip()">PRINT</button>
    </span>
</div>

</div>
</div>

</form>