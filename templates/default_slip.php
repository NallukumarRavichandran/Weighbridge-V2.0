<!-- ============================================================
     DEFAULT PRINT SLIP (STANDALONE OFFLINE STATIC TEMPLATE)
     File: templates/default_slip.php
     Location: Local Project Directory
     Reliability: 100% Offline Compatible (Zero Cloud Dependency)
     ============================================================ -->
<style>
@page {
    size: A4 portrait;
    margin: 0;
}
* {
    box-sizing: border-box;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
body {
    font-family: "Courier New", Courier, monospace, Arial;
    margin: 0;
    padding: 8mm 12mm;
    background: #fff;
    color: #000;
}
.slip-container {
    width: 100%;
    max-width: 720px;
    margin: 0 auto;
    background: #fff;
}
.header {
    text-align: center;
    margin-bottom: 6px;
    position: relative;
}
.company-logo {
    position: absolute;
    left: 0;
    top: 0;
    width: 55px;
    height: 55px;
    object-fit: contain;
}
.company-name {
    font-size: 20px;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
}
.company-addr {
    font-size: 13px;
    margin-top: 3px;
    line-height: 1.35;
}
.cert-title {
    font-size: 15px;
    font-weight: bold;
    margin-top: 6px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}
.divider-dashed {
    border-top: 1.5px dashed #000;
    margin: 8px 0;
    width: 100%;
}
.info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
    font-weight: bold;
}
.info-table td {
    padding: 3.5px 0;
    vertical-align: top;
}
.info-table .lbl {
    width: 140px;
    font-weight: bold;
}
.info-table .sep {
    width: 15px;
    text-align: center;
}
.info-table .val {
    font-weight: normal;
    word-break: break-word;
}

/* Weights Table */
.weights-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
    margin: 4px 0;
}
.weights-table td {
    padding: 4px 0;
    vertical-align: middle;
}
.weights-table .w-lbl {
    font-weight: bold;
    width: 95px;
}
.weights-table .w-sep {
    width: 15px;
    text-align: center;
    font-weight: bold;
}
.weights-table .w-val {
    width: 150px;
    font-weight: bold;
}
.weights-table .w-dt-lbl, .weights-table .w-tm-lbl {
    font-weight: bold;
    width: 55px;
    text-align: right;
    padding-right: 5px;
}
.weights-table .w-dt-val, .weights-table .w-tm-val {
    width: 95px;
    font-weight: normal;
}

/* CCTV Photos Section */
.slip-photos-section {
    margin: 8px 0;
    width: 100%;
}
.photos-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
.photos-grid.single-photo {
    grid-template-columns: 1fr;
    max-width: 360px;
    margin: 0 auto;
}
.photo-card {
    border: 1.5px solid #000;
    padding: 4px;
    text-align: center;
    background: #fff;
    box-sizing: border-box;
}
.photo-card .photo-title {
    font-size: 10.5px;
    font-weight: bold;
    margin-bottom: 3px;
    text-transform: uppercase;
}
.photo-card img {
    width: 100%;
    height: 130px;
    object-fit: cover;
    display: block;
    border: 1px solid #333;
}
.photo-placeholder {
    width: 100%;
    height: 130px;
    background: #fafafa;
    border: 1px dashed #000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
    color: #000;
    text-transform: uppercase;
}

/* Footer & Signatures */
.footer-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 25px;
    padding: 0 10px;
    font-size: 13px;
    font-weight: bold;
}
.signature-box {
    text-align: center;
    min-width: 180px;
}
.signature-line {
    border-top: 1px solid #000;
    margin-bottom: 5px;
}
</style>

<div class="slip-container">
    <!-- Header with Logo and Company Data -->
    <div class="header">
        <?php if (!empty($logoPath)): ?>
            <img src="<?= htmlspecialchars($logoPath) ?>?v=<?= time() ?>" class="company-logo" alt="Logo">
        <?php endif; ?>
        <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? '') ?></div>
        <div class="company-addr"><?= nl2br(htmlspecialchars($company['company_address'] ?? '')) ?></div>
        <div class="cert-title"><?= getPrintFieldLabel('cert_title', 'WEIGHMENT CERTIFICATE') ?></div>
    </div>

    <div class="divider-dashed"></div>

    <!-- Information Table -->
    <table class="info-table">
        <tr>
            <?php if (showPrintField('slip_no')): ?>
                <td class="lbl"><?= getPrintFieldLabel('slip_no', 'Slip No') ?></td>
                <td class="sep">:</td>
                <td class="val"><?= htmlspecialchars($row['slip_no'] ?? '') ?></td>
            <?php endif; ?>
            <?php if (showPrintField('vehicle_no')): ?>
                <td class="lbl"><?= getPrintFieldLabel('vehicle_no', 'Vehicle No') ?></td>
                <td class="sep">:</td>
                <td class="val"><?= htmlspecialchars($row['vehicle_no'] ?? '') ?></td>
            <?php endif; ?>
        </tr>

        <tr>
            <?php if (showPrintField('material_name')): ?>
                <td class="lbl"><?= getPrintFieldLabel('material_name', 'Material Name') ?></td>
                <td class="sep">:</td>
                <td class="val"><?= htmlspecialchars($material_name ?? '') ?></td>
            <?php endif; ?>
            <?php if (showPrintField('party_name')): ?>
                <td class="lbl"><?= getPrintFieldLabel('party_name', 'Party Name') ?></td>
                <td class="sep">:</td>
                <td class="val"><?= htmlspecialchars($party_name ?? '') ?></td>
            <?php endif; ?>
        </tr>

        <?php if (showPrintField('vessel_name') || showPrintField('vt_no')): ?>
        <tr>
            <?php if (showPrintField('vessel_name')): ?>
                <td class="lbl"><?= getPrintFieldLabel('vessel_name', 'Vessel Name') ?></td>
                <td class="sep">:</td>
                <td class="val"><?= htmlspecialchars($vessel_name ?? '') ?></td>
            <?php endif; ?>
            <?php if (showPrintField('vt_no')): ?>
                <td class="lbl"><?= getPrintFieldLabel('vt_no', 'VT No') ?></td>
                <td class="sep">:</td>
                <td class="val"><?= htmlspecialchars($vt_no ?? '') ?></td>
            <?php endif; ?>
        </tr>
        <?php endif; ?>

        <?php if (showPrintField('driver_name') || showPrintField('driver_no')): ?>
        <tr>
            <?php if (showPrintField('driver_name')): ?>
                <td class="lbl"><?= getPrintFieldLabel('driver_name', 'Driver Name') ?></td>
                <td class="sep">:</td>
                <td class="val"><?= htmlspecialchars($driver_name ?? '') ?></td>
            <?php endif; ?>
            <?php if (showPrintField('driver_no')): ?>
                <td class="lbl"><?= getPrintFieldLabel('driver_no', 'Driver Mobile No') ?></td>
                <td class="sep">:</td>
                <td class="val"><?= htmlspecialchars($driver_no ?? '') ?></td>
            <?php endif; ?>
        </tr>
        <?php endif; ?>

        <?php if (showPrintField('movement_type') && !empty($movement_type)): ?>
        <tr>
            <td class="lbl"><?= getPrintFieldLabel('movement_type', 'Movement Type') ?></td>
            <td class="sep">:</td>
            <td class="val" colspan="4"><?= htmlspecialchars($movement_type) ?></td>
        </tr>
        <?php endif; ?>

        <?php if (showPrintField('sap_trans') && !empty($sap_trans)): ?>
        <tr>
            <td class="lbl"><?= getPrintFieldLabel('sap_trans', 'SAP Transaction') ?></td>
            <td class="sep">:</td>
            <td class="val" colspan="4"><?= htmlspecialchars($sap_trans) ?></td>
        </tr>
        <?php endif; ?>

        <!-- Dynamic Additional Custom Fields -->
        <?php if (!empty($dynamic_fields)): ?>
            <?php foreach ($dynamic_fields as $df): 
                $dynKey = 'dyn_' . ($df['id'] ?? '');
                if (!showPrintField($dynKey, true)) continue;
                $val = trim($df['field_value'] ?? '');
                if (empty($val)) continue;
                $dfLbl = getPrintFieldLabel($dynKey, $df['field_label'] ?? $df['field_name'] ?? 'Field');
            ?>
            <tr>
                <td class="lbl"><?= htmlspecialchars($dfLbl) ?></td>
                <td class="sep">:</td>
                <td class="val" colspan="4"><?= htmlspecialchars($val) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <div class="divider-dashed"></div>

    <!-- Weights Section -->
    <table class="weights-table">
        <?php if (!empty($isFinal)): ?>
            <tr>
                <td class="w-lbl">GROSS Wt</td>
                <td class="w-sep">:</td>
                <td class="w-val"><?= number_format((float)($disp_gross ?? 0), 2) ?> <?= htmlspecialchars($display_unit ?? 'Kg') ?></td>
                <td class="w-dt-lbl">Date :</td>
                <td class="w-dt-val"><?= htmlspecialchars($row['gross_date'] ?? '') ?></td>
                <td class="w-tm-lbl">Time :</td>
                <td class="w-tm-val"><?= htmlspecialchars(substr($row['gross_time'] ?? '', 0, 5)) ?></td>
            </tr>
            <tr>
                <td class="w-lbl">Tare Wt</td>
                <td class="w-sep">:</td>
                <td class="w-val"><?= number_format((float)($disp_tare ?? 0), 2) ?> <?= htmlspecialchars($display_unit ?? 'Kg') ?></td>
                <td class="w-dt-lbl">Date :</td>
                <td class="w-dt-val"><?= htmlspecialchars($row['tare_date'] ?? '') ?></td>
                <td class="w-tm-lbl">Time :</td>
                <td class="w-tm-val"><?= htmlspecialchars(substr($row['tare_time'] ?? '', 0, 5)) ?></td>
            </tr>
            <tr>
                <td class="w-lbl" style="font-size:14.5px;">NET Wt</td>
                <td class="w-sep" style="font-size:14.5px;">:</td>
                <td class="w-val" colspan="5" style="font-size:15px;">
                    <b><?= number_format((float)($disp_net ?? 0), 2) ?> <?= htmlspecialchars($display_unit ?? 'Kg') ?></b>
                </td>
            </tr>
        <?php else: ?>
            <tr>
                <td class="w-lbl"><?= (($row['gt_type'] ?? 'G') === 'G') ? 'GROSS Wt' : 'Tare Wt' ?></td>
                <td class="w-sep">:</td>
                <td class="w-val"><?= number_format((float)($disp_first ?? 0), 2) ?> <?= htmlspecialchars($display_unit ?? 'Kg') ?></td>
                <td class="w-dt-lbl">Date :</td>
                <td class="w-dt-val"><?= htmlspecialchars($row['first_date'] ?? '') ?></td>
                <td class="w-tm-lbl">Time :</td>
                <td class="w-tm-val"><?= htmlspecialchars(substr($row['first_time'] ?? '', 0, 5)) ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <!-- Optional CCTV Photos Grid -->
    <?php if (showPrintField('cctv_images')): ?>
        <div class="divider-dashed"></div>
        <div class="slip-photos-section">
            <div class="photos-grid <?= empty($isFinal) ? 'single-photo' : '' ?>">
                <div class="photo-card">
                    <div class="photo-title"><?= getPrintFieldLabel('cctv_images', 'CCTV PHOTOS') ?> - 1ST WEIGHMENT</div>
                    <?php if (!empty($row['first_image_path']) && file_exists(__DIR__ . '/../' . $row['first_image_path'])): ?>
                        <img src="<?= htmlspecialchars($row['first_image_path']) ?>" alt="1st Weighment Photo">
                    <?php else: ?>
                        <div class="photo-placeholder">CAMERA 1 [ OFFLINE ]</div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($isFinal)): ?>
                <div class="photo-card">
                    <div class="photo-title"><?= getPrintFieldLabel('cctv_images', 'CCTV PHOTOS') ?> - 2ND WEIGHMENT</div>
                    <?php if (!empty($row['second_image_path']) && file_exists(__DIR__ . '/../' . $row['second_image_path'])): ?>
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

    <!-- Signatures Section -->
    <?php if (showPrintField('signature_block')): ?>
        <div class="footer-row">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div>Driver Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div><?= getPrintFieldLabel('signature_block', "Operator's Signature") ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>
