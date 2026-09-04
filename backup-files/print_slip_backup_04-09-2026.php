<?php
date_default_timezone_set('Asia/Kolkata');

// 1. Error Reporting & Sessions
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";
require_once "functions.php";

if (!isset($_SESSION['user_id'])) die("Unauthorized Access");

$slip_no = $_GET['slip'] ?? '';
if ($slip_no == '') die("Slip Number Missing");

$company = getCompany($conn);

/* ============================================================
   1. FETCH WEIGHMENT DATA (FINAL OR FIRST)
   ============================================================ */
$row = null;
$isFinal = false;

// Check Finalized 2nd Weighment
$stmt2 = $conn->prepare("SELECT * FROM sweighment WHERE slip_no = ? LIMIT 1");
if ($stmt2) {
    $stmt2->bind_param("s", $slip_no);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($res2 && $res2->num_rows > 0) {
        $row = $res2->fetch_assoc();
        $isFinal = true;
    }
    $stmt2->close();
}

// Check First Weighment if not finalized
if (!$isFinal) {
    $stmt1 = $conn->prepare("SELECT * FROM weighments WHERE slip_no = ? LIMIT 1");
    if ($stmt1) {
        $stmt1->bind_param("s", $slip_no);
        $stmt1->execute();
        $res1 = $stmt1->get_result();
        if ($res1 && $res1->num_rows > 0) {
            $row = $res1->fetch_assoc();
        }
        $stmt1->close();
    }
}

if (!$row) {
    echo "<script>alert('Slip #{$slip_no} not found');window.location='mainform.php';</script>";
    exit;
}

/* ============================================================
   2. DYNAMIC FIELDS EXTRACTION & MAPPING
   ============================================================ */
$dynamic_fields = [];
$stmt = $conn->prepare("
    SELECT f.id, f.field_label, f.field_name,
           COALESCE(v.field_value,'') AS field_value
    FROM weighment_fields f
    LEFT JOIN weighment_field_values v
      ON v.field_id = f.id
     AND v.weighment_id = ?
    WHERE f.company_id = ?
      AND f.is_active = 1
    ORDER BY f.field_order
");
if ($stmt) {
    $stmt->bind_param("si", $slip_no, $company['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $dynamic_fields[] = $r;
    }
    $stmt->close();
}

// Intelligent field mapper
$material_name = '';
$party_name    = '';
$vessel_name   = '';
$vt_no         = '';
$movement_type = '';
$driver_name   = '';
$driver_no     = '';
$sap_trans     = '';
$weight_unit   = 'Kg';

foreach ($dynamic_fields as $df) {
    $lbl  = strtolower(trim($df['field_label'] ?? ''));
    $name = strtolower(trim($df['field_name'] ?? ''));
    $val  = trim($df['field_value'] ?? '');

    if (empty($val)) continue;

    if (stripos($lbl, 'cargo') !== false || stripos($name, 'cargo') !== false || stripos($lbl, 'material') !== false || stripos($name, 'material') !== false) {
        $material_name = $val;
    } elseif (stripos($lbl, 'client') !== false || stripos($name, 'client') !== false || stripos($lbl, 'party') !== false || stripos($name, 'party') !== false) {
        $party_name = $val;
    } elseif (stripos($lbl, 'vessel') !== false || stripos($name, 'vessel') !== false) {
        $vessel_name = $val;
    } elseif (stripos($lbl, 'vt') !== false || stripos($name, 'vt') !== false) {
        $vt_no = $val;
    } elseif (stripos($lbl, 'movement') !== false || stripos($name, 'movement') !== false) {
        $movement_type = $val;
    } elseif (stripos($lbl, 'driver name') !== false || stripos($name, 'driver name') !== false) {
        $driver_name = $val;
    } elseif (stripos($lbl, 'driver no') !== false || stripos($lbl, 'driver number') !== false || stripos($name, 'driver no') !== false) {
        $driver_no = $val;
    } elseif (stripos($lbl, 'sap') !== false || stripos($lbl, 'transaction') !== false) {
        $sap_trans = $val;
    } elseif (stripos($lbl, 'unit') !== false || stripos($name, 'unit') !== false) {
        $weight_unit = $val;
    }
}

/* ============================================================
   3. FETCH FIELD VISIBILITY & CUSTOM LABELS CONFIGURATION
   ============================================================ */
$fieldConfigMap = [];
$resFld = $conn->query("SELECT * FROM `print_field_config`");
if ($resFld) {
    while ($rf = $resFld->fetch_assoc()) {
        $fieldConfigMap[$rf['field_key']] = $rf;
    }
}

function showPrintField($field_key, $default = true) {
    global $fieldConfigMap;
    if (isset($fieldConfigMap[$field_key])) {
        return ((int)$fieldConfigMap[$field_key]['is_visible'] === 1);
    }
    return $default;
}

function getPrintFieldLabel($field_key, $default_label = '') {
    global $fieldConfigMap;
    if (!empty($fieldConfigMap[$field_key]['custom_label'])) {
        return htmlspecialchars($fieldConfigMap[$field_key]['custom_label']);
    }
    return htmlspecialchars($default_label);
}

/* ============================================================
   4. WEIGHTS CONVERSION (TON TO KG)
   ============================================================ */
$isTon = (stripos($weight_unit, 'TON') !== false);
$display_unit = 'Kg';

if ($isFinal) {
    $gross_num = (float)$row['gross_weight'];
    $tare_num  = (float)$row['tare_weight'];
    $net_num   = (float)$row['net_weight'];

    if ($isTon) {
        $gross_num *= 1000;
        $tare_num  *= 1000;
        $net_num   *= 1000;
    }

    $disp_gross = $gross_num;
    $disp_tare  = $tare_num;
    $disp_net   = $net_num;
} else {
    $first_num = (float)$row['first_weight'];
    if ($isTon) $first_num *= 1000;
    $disp_first = $first_num;
}

function formatDateTime($date, $time) {
    if (empty($date) || empty($time)) return '';
    $datetime = trim($date) . ' ' . trim($time);
    $ts = strtotime($datetime);
    return $ts ? date('d-m-Y H:i:s', $ts) : ($date . ' ' . $time);
}

// Logo Path Resolver
$logoPath = file_exists(__DIR__ . '/LogoPrint.png') ? 'LogoPrint.png' : (file_exists(__DIR__ . '/gblogo.jpeg') ? 'gblogo.jpeg' : '');

// Image collections
$tareImgs = array_filter([$row['first_image_path'] ?? '', $row['first_image_path_2'] ?? '']);
$grossImgs = array_filter([$row['second_image_path'] ?? '', $row['second_image_path_2'] ?? '']);
if (!$isFinal) {
    $firstImgs = array_filter([$row['first_image_path'] ?? '', $row['first_image_path_2'] ?? '']);
    if (($row['gt_type'] ?? 'G') === 'T') $tareImgs = $firstImgs; else $grossImgs = $firstImgs;
}

/* ============================================================
   5. RESOLVE ACTIVE PRINT FORMAT & TEMPLATE
   ============================================================ */
$activeFormat = 'default_slip';
$qChoice = $conn->query("SELECT `format_key` FROM `print_format_settings` WHERE `company_name` = 'ACTIVE_CHOICE' LIMIT 1");
if ($qChoice && $qChoice->num_rows > 0) {
    $activeFormat = $qChoice->fetch_assoc()['format_key'];
} else {
    $qFmt = $conn->query("SELECT `format_key` FROM `print_format_settings` WHERE `company_name` = 'ALL' LIMIT 1");
    if ($qFmt && $qFmt->num_rows > 0) {
        $activeFormat = $qFmt->fetch_assoc()['format_key'];
    }
}

// Check if downloaded .php template exists in templates/ folder
$tplFile = __DIR__ . '/templates/' . $activeFormat . '.php';

// Check if local database has dynamic template_code
$dbTemplateCode = '';
$qCode = $conn->query("SELECT `template_code` FROM `print_format_settings` WHERE `format_key` = '{$activeFormat}' AND `template_code` IS NOT NULL AND `template_code` != '' LIMIT 1");
if ($qCode && $qCode->num_rows > 0) {
    $dbTemplateCode = $qCode->fetch_assoc()['template_code'];
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Weighment Certificate - Slip #<?= htmlspecialchars($row['slip_no']) ?></title>
</head>

<body onload="setTimeout(() => window.print(), 250); window.onafterprint = () => window.location = 'mainform.php';">

<?php
// 1. If downloaded file exists on disk, execute it
if (file_exists($tplFile)) {
    include $tplFile;
} 
// 2. If database has live template code, evaluate it
elseif (!empty($dbTemplateCode)) {
    eval('?>' . $dbTemplateCode);
} 
// 3. Fallback to dedicated local default template
elseif (file_exists(__DIR__ . '/templates/default_slip.php')) {
    include __DIR__ . '/templates/default_slip.php';
}
// 4. Fallback to built-in template engine
else {
    if ($activeFormat === 'format_dual_landscape_cctv') {
?>
<!-- ============================================================
     LAYOUT 2: DUAL-COPY A4 PORTRAIT (TOP & BOTTOM STACKED DUAL BILL)
     ============================================================ -->
<style>
@page { size: A4 portrait; margin: 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: "Courier New", Courier, monospace, Arial; background: #fff; color: #000; padding: 5mm 8mm; }
.dual-vertical-wrapper { display: flex; flex-direction: column; justify-content: space-between; width: 100%; max-width: 690px; margin: 0 auto; height: 284mm; }
.slip-copy { width: 100%; height: 137mm; display: flex; flex-direction: column; justify-content: space-between; padding: 2mm 0; }
.slip-header { text-align: center; position: relative; min-height: 44px; }
.company-logo { position: absolute; left: 0; top: 0; width: 44px; height: 44px; object-fit: contain; }
.company-name { font-size: 16px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
.company-addr { font-size: 11px; margin-top: 2px; }
.cert-title { font-size: 13px; font-weight: bold; margin-top: 3px; letter-spacing: 1.5px; }
.divider-dashed { border-top: 1.2px dashed #000; margin: 3px 0; width: 100%; }
.info-table { width: 100%; border-collapse: collapse; font-size: 12px; font-weight: bold; }
.info-table td { padding: 1.5px 0; vertical-align: top; }
.info-table .lbl { width: 130px; font-weight: bold; }
.info-table .sep { width: 15px; text-align: center; font-weight: bold; }
.info-table .val { width: 200px; font-weight: normal; }

.weights-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.weights-table td { padding: 2px 0; vertical-align: middle; }
.weights-table .w-lbl { font-weight: bold; width: 90px; }
.weights-table .w-sep { width: 15px; text-align: center; font-weight: bold; }
.weights-table .w-val { width: 145px; font-weight: bold; }
.weights-table .w-dt-lbl, .weights-table .w-tm-lbl { font-weight: bold; width: 55px; text-align: right; padding-right: 5px; }
.weights-table .w-dt-val, .weights-table .w-tm-val { width: 95px; }

.slip-photos-section { margin: 3px 0; width: 100%; }
.photos-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
.photos-grid.single-photo { grid-template-columns: 1fr; max-width: 320px; margin: 0 auto; }
.photo-card { border: 1.5px solid #000; padding: 3px; text-align: center; background: #fff; }
.photo-card .photo-title { font-size: 9.5px; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; }
.photo-card img { width: 100%; height: 75px; object-fit: cover; display: block; border: 1px solid #333; }
.photo-placeholder { width: 100%; height: 75px; background: #fff; border: 1px dashed #000; display: flex; align-items: center; justify-content: center; font-size: 10.5px; font-weight: bold; color: #000; text-transform: uppercase; }

.signature-section { text-align: right; padding-right: 15px; font-size: 11.5px; font-weight: bold; }
.copy-divider-line { border-top: 1.5px dashed #666; margin: 3mm 0; width: 100%; }
</style>

<div class="dual-vertical-wrapper">
    <?php for ($copy = 1; $copy <= 2; $copy++): ?>
    <div class="slip-copy">
        <div>
            <div class="slip-header">
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
                <?php if (showPrintField('vessel_name') || showPrintField('vt_no')): ?>
                <tr>
                    <?php if (showPrintField('vessel_name')): ?><td class="lbl"><?= getPrintFieldLabel('vessel_name', 'Vessel Name') ?></td><td class="sep">:</td><td class="val"><?= htmlspecialchars($vessel_name) ?></td><?php endif; ?>
                    <?php if (showPrintField('vt_no')): ?><td class="lbl"><?= getPrintFieldLabel('vt_no', 'Vt No') ?></td><td class="sep">:</td><td class="val"><?= htmlspecialchars($vt_no) ?></td><?php endif; ?>
                </tr>
                <?php endif; ?>
                <?php if (showPrintField('movement_type') && !empty($movement_type)): ?>
                <tr><td class="lbl"><?= getPrintFieldLabel('movement_type', 'Movement Type') ?></td><td class="sep">:</td><td class="val" colspan="4"><?= htmlspecialchars($movement_type) ?></td></tr>
                <?php endif; ?>
            </table>

            <div class="divider-dashed"></div>

            <table class="weights-table">
                <?php if ($isFinal): ?>
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
                <?php else: ?>
                <tr>
                    <td class="w-lbl"><?= ($row['gt_type'] == 'G') ? 'GROSS Wt' : 'Tare Wt' ?></td>
                    <td class="w-sep">:</td><td class="w-val"><?= htmlspecialchars($disp_first) ?> <?= htmlspecialchars($display_unit) ?></td>
                    <td class="w-dt-lbl">Date :</td><td class="w-dt-val"><?= htmlspecialchars($row['first_date']??'') ?></td>
                    <td class="w-tm-lbl">Time :</td><td class="w-tm-val"><?= htmlspecialchars(substr($row['first_time']??'', 0, 5)) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <?php if (showPrintField('cctv_images')): ?>
        <div class="divider-dashed"></div>
        <div class="slip-photos-section">
            <div class="photos-grid <?= (!$isFinal) ? 'single-photo' : '' ?>">
                <div class="photo-card">
                    <div class="photo-title"><?= getPrintFieldLabel('cctv_images', 'CCTV PHOTOS') ?> - 1ST WEIGHMENT</div>
                    <?php if (!empty($row['first_image_path']) && file_exists(__DIR__ . '/' . $row['first_image_path'])): ?>
                        <img src="<?= htmlspecialchars($row['first_image_path']) ?>" alt="1st Weighment Photo">
                    <?php else: ?>
                        <div class="photo-placeholder">CAMERA 1 [ OFFLINE ]</div>
                    <?php endif; ?>
                </div>

                <?php if ($isFinal): ?>
                <div class="photo-card">
                    <div class="photo-title"><?= getPrintFieldLabel('cctv_images', 'CCTV PHOTOS') ?> - 2ND WEIGHMENT</div>
                    <?php if (!empty($row['second_image_path']) && file_exists(__DIR__ . '/' . $row['second_image_path'])): ?>
                        <img src="<?= htmlspecialchars($row['second_image_path']) ?>" alt="2nd Weighment Photo">
                    <?php else: ?>
                        <div class="photo-placeholder">CAMERA 2 [ OFFLINE ]</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="divider-dashed"></div>

        <?php if (showPrintField('signature_block')): ?>
        <div class="signature-section">
            <?= getPrintFieldLabel('signature_block', "Operator's Signature") ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($copy === 1): ?>
        <div class="copy-divider-line"></div>
    <?php endif; ?>

    <?php endfor; ?>
</div>
<?php
    } elseif ($activeFormat === 'format_single_dotmatrix') {
?>
<!-- ============================================================
     LAYOUT 3: SINGLE CONTINUOUS HALF-PAGE (TVS DOT-MATRIX)
     ============================================================ -->
<style>
@page { size: 210mm 140mm; margin: 0; }
body { font-family: "Courier New", monospace; margin: 0; padding: 0; background: #fff; color: #000; }
.slip-container { width: 680px; margin: 0 auto; padding: 6mm 10mm 4mm 10mm; background: #fff; }
.header { text-align: center; margin-bottom: 4px; position: relative; }
.company-logo { position: absolute; left: 0; top: 0; width: 45px; height: 45px; object-fit: contain; }
.company-name { font-size: 18px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
.company-addr { font-size: 12.5px; margin-top: 2px; }
.cert-title { font-size: 14px; font-weight: bold; margin-top: 4px; letter-spacing: 1.5px; }
.divider-dashed { border-top: 1px dashed #000; margin: 6px 0; width: 100%; }
.info-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.info-table td { padding: 2.5px 0; }
.info-table .lbl { font-weight: bold; width: 120px; }
.weights-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.weights-table td { padding: 3px 0; }
.weights-table .w-lbl { font-weight: bold; width: 90px; }
.signature-space { height: 28px; }
.signature-section { text-align: right; padding-right: 20px; font-size: 13px; font-weight: bold; }
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
            <?php if (showPrintField('slip_no')): ?><td class="lbl"><?= getPrintFieldLabel('slip_no', 'Slip No') ?></td><td>: <?= $row['slip_no'] ?></td><?php endif; ?>
            <?php if (showPrintField('vehicle_no')): ?><td class="lbl"><?= getPrintFieldLabel('vehicle_no', 'Vehicle No') ?></td><td>: <?= htmlspecialchars($row['vehicle_no']) ?></td><?php endif; ?>
        </tr>
        <tr>
            <?php if (showPrintField('material_name')): ?><td class="lbl"><?= getPrintFieldLabel('material_name', 'Material Name') ?></td><td>: <?= htmlspecialchars($material_name) ?></td><?php endif; ?>
            <?php if (showPrintField('party_name')): ?><td class="lbl"><?= getPrintFieldLabel('party_name', 'Party Name') ?></td><td>: <?= htmlspecialchars($party_name) ?></td><?php endif; ?>
        </tr>
    </table>
    <div class="divider-dashed"></div>
    <table class="weights-table">
        <?php if ($isFinal): ?>
        <tr><td class="w-lbl">GROSS Wt</td><td>: <?= $disp_gross ?> <?= $display_unit ?></td><td>Date: <?= $row['gross_date'] ?></td><td>Time: <?= substr($row['gross_time'], 0, 5) ?></td></tr>
        <tr><td class="w-lbl">Tare Wt</td><td>: <?= $disp_tare ?> <?= $display_unit ?></td><td>Date: <?= $row['tare_date'] ?></td><td>Time: <?= substr($row['tare_time'], 0, 5) ?></td></tr>
        <tr><td class="w-lbl">Net Wt</td><td colspan="3">: <?= $disp_net ?> <?= $display_unit ?></td></tr>
        <?php else: ?>
        <tr><td class="w-lbl"><?= ($row['gt_type']=='G')?'GROSS Wt':'Tare Wt' ?></td><td>: <?= $disp_first ?> <?= $display_unit ?></td><td>Date: <?= $row['first_date'] ?></td><td>Time: <?= substr($row['first_time'], 0, 5) ?></td></tr>
        <?php endif; ?>
    </table>
    <div class="divider-dashed"></div>
    <div class="signature-space"></div>
    <?php if (showPrintField('signature_block')): ?>
    <div class="signature-section"><?= getPrintFieldLabel('signature_block', "Operator's Signature") ?></div>
    <?php endif; ?>
</div>
<?php
    } else {
?>
<!-- ============================================================
     LAYOUT 1: STANDARD A4 PORTRAIT SLIP (WITH CCTV PHOTOS)
     ============================================================ -->
<style>
@page { size: A4 portrait; margin: 0; }
body { font-family: "Courier New", Courier, monospace, Arial; margin: 0; padding: 8mm 10mm; background: #fff; color: #000; }
.slip-container { width: 100%; max-width: 700px; margin: 0 auto; padding: 5px; background: #fff; }
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

.slip-photos-section { margin: 10px 0; width: 100%; }
.photos-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.photos-grid.single-photo { grid-template-columns: 1fr; max-width: 380px; margin: 0 auto; }
.photo-card { border: 2px solid #000; padding: 5px; text-align: center; background: #fff; box-sizing: border-box; }
.photo-card .photo-title { font-size: 11px; font-weight: bold; margin-bottom: 4px; text-transform: uppercase; }
.photo-card img { width: 100%; height: 145px; object-fit: cover; display: block; border: 1px solid #333; }
.photo-placeholder { width: 100%; height: 145px; background: #fafafa; border: 1.5px dashed #000; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: #000; text-transform: uppercase; }

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
        <?php if (showPrintField('vessel_name') || showPrintField('vt_no')): ?>
        <tr>
            <?php if (showPrintField('vessel_name')): ?><td class="lbl"><?= getPrintFieldLabel('vessel_name', 'Vessel Name') ?></td><td class="sep">:</td><td class="val"><?= htmlspecialchars($vessel_name) ?></td><?php endif; ?>
            <?php if (showPrintField('vt_no')): ?><td class="lbl"><?= getPrintFieldLabel('vt_no', 'Vt No') ?></td><td class="sep">:</td><td class="val"><?= htmlspecialchars($vt_no) ?></td><?php endif; ?>
        </tr>
        <?php endif; ?>
        <?php if (showPrintField('movement_type') && !empty($movement_type)): ?>
        <tr><td class="lbl"><?= getPrintFieldLabel('movement_type', 'Movement Type') ?></td><td class="sep">:</td><td class="val" colspan="4"><?= htmlspecialchars($movement_type) ?></td></tr>
        <?php endif; ?>
    </table>

    <div class="divider-dashed"></div>

    <table class="weights-table">
        <?php if ($isFinal): ?>
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
        <?php else: ?>
        <tr>
            <td class="w-lbl"><?= ($row['gt_type'] == 'G') ? 'GROSS Wt' : 'Tare Wt' ?></td>
            <td class="w-sep">:</td><td class="w-val"><?= htmlspecialchars($disp_first) ?> <?= htmlspecialchars($display_unit) ?></td>
            <td class="w-dt-lbl">Date :</td><td class="w-dt-val"><?= htmlspecialchars($row['first_date']??'') ?></td>
            <td class="w-tm-lbl">Time :</td><td class="w-tm-val"><?= htmlspecialchars(substr($row['first_time']??'', 0, 5)) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- CCTV CAMERA PHOTOS SECTION -->
    <?php if (showPrintField('cctv_images')): ?>
    <div class="divider-dashed"></div>
    <div class="slip-photos-section">
        <div class="photos-grid <?= (!$isFinal) ? 'single-photo' : '' ?>">
            <div class="photo-card">
                <div class="photo-title"><?= getPrintFieldLabel('cctv_images', 'CCTV PHOTOS') ?> - 1ST WEIGHMENT</div>
                <?php if (!empty($row['first_image_path']) && file_exists(__DIR__ . '/' . $row['first_image_path'])): ?>
                    <img src="<?= htmlspecialchars($row['first_image_path']) ?>" alt="1st Weighment Photo">
                <?php else: ?>
                    <div class="photo-placeholder">CAMERA 1 [ OFFLINE ]</div>
                <?php endif; ?>
            </div>

            <?php if ($isFinal): ?>
            <div class="photo-card">
                <div class="photo-title"><?= getPrintFieldLabel('cctv_images', 'CCTV PHOTOS') ?> - 2ND WEIGHMENT</div>
                <?php if (!empty($row['second_image_path']) && file_exists(__DIR__ . '/' . $row['second_image_path'])): ?>
                    <img src="<?= htmlspecialchars($row['second_image_path']) ?>" alt="2nd Weighment Photo">
                <?php else: ?>
                    <div class="photo-placeholder">CAMERA 2 [ OFFLINE ]</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="divider-dashed"></div>

    <?php if (showPrintField('signature_block')): ?>
    <div class="signature-section">
        <?= getPrintFieldLabel('signature_block', "Operator's Signature") ?>
    </div>
    <?php endif; ?>
</div>
<?php
    }
}
?>

</body>
</html>