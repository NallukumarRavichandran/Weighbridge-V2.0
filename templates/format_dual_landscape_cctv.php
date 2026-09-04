<!-- ============================================================
     LAYOUT 2: DUAL-COPY LANDSCAPE A4 (4 CCTV PHOTOS)
     ============================================================ -->
<style>
@page { size: A4 landscape; margin: 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Courier New', monospace; background: #fff; color: #000; padding: 4mm 6mm; }
.dual-page-wrapper { display: flex; justify-content: space-between; width: 100%; height: 196mm; gap: 6mm; }
.slip-half { width: 49%; height: 196mm; border: 2px solid #000; padding: 8px 12px; background: #fff; display: flex; flex-direction: column; justify-content: space-between; }
.company-header { position: relative; text-align: center; min-height: 48px; }
.company-logo { position: absolute; left: 0; top: 0; width: 44px; height: 44px; object-fit: contain; }
.company-header h1 { font-size: 16px; font-weight: bold; letter-spacing: 1.5px; margin-left: 38px; }
.company-header h2 { font-size: 12px; font-weight: bold; letter-spacing: 2.5px; margin-top: 1px; margin-left: 38px; }
.company-header .sub { font-size: 8.5px; margin-top: 2px; line-height: 1.1; margin-left: 38px; }
.info-grid { display: grid; grid-template-columns: 1.1fr 1fr 1.1fr; gap: 2px 6px; padding: 4px 0; border-top: 2px solid #000; border-bottom: 2px solid #000; font-size: 10px; font-weight: bold; margin: 3px 0 5px 0; }
.info-grid .item { display: flex; white-space: nowrap; overflow: hidden; }
.info-grid .item .label { min-width: 78px; }
.weights-table { width: 100%; border-collapse: collapse; margin: 4px 0; }
.weights-table th, .weights-table td { border: 2px solid #000; padding: 4px 2px; text-align: center; }
.weights-table th { background: #f8fafc; font-size: 9.5px; font-weight: bold; }
.weights-table td { font-size: 11px; font-weight: bold; }
.certify-row { display: flex; justify-content: space-between; padding: 4px 0; border-top: 2px solid #000; font-size: 10px; font-weight: bold; margin-top: 3px; }
.weight-labels { display: flex; justify-content: space-between; padding: 3px 0; border-top: 2px solid #000; border-bottom: 2px solid #000; font-size: 10.5px; font-weight: bold; text-align: center; margin: 3px 0 5px 0; }
.weight-labels .weight-item { flex: 1; }
.weight-labels .weight-item .date-time { font-weight: normal; font-size: 9px; margin-top: 1px; }
.images-section { display: flex; gap: 6px; margin-top: 4px; }
.images-section .img-block { flex: 1; text-align: center; }
.images-section .img-block.left { border-right: 1.5px solid #000; padding-right: 3px; }
.images-section .img-block.right { padding-left: 3px; }
.images-section .img-block .img-title { font-weight: bold; margin-bottom: 3px; font-size: 9.5px; letter-spacing: 0.5px; }
.image-pair { display: flex; flex-direction: column; gap: 5px; }
.image-pair img { width: 100%; height: 118px; object-fit: cover; border: 1.5px solid #000; display: block; }
.image-pair .img-placeholder { width: 100%; height: 118px; background: #fff; border: 1.5px dashed #000; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; color: #000; }
</style>

<div class="dual-page-wrapper">
    <?php for ($copy = 1; $copy <= 2; $copy++): ?>
    <div class="slip-half">
        <div>
            <div class="company-header">
                <?php if (!empty($logoPath)): ?><img src="<?= $logoPath ?>?v=<?= time() ?>" class="company-logo" alt="Logo"><?php endif; ?>
                <h1><?= strtoupper(htmlspecialchars($company['company_name'] ?? '')) ?></h1>
                <h2>WEIGHMENT SLIP</h2>
                <div class="sub"><?= strtoupper(htmlspecialchars($company['company_address'] ?? '')) ?></div>
            </div>

            <div class="info-grid">
                <?php if (showPrintField('slip_no')): ?><div class="item"><span class="label">Slip No</span><span>: <?= $row['slip_no'] ?></span></div><?php endif; ?>
                <?php if (showPrintField('driver_name')): ?><div class="item"><span class="label">Driver Name</span><span>: <?= htmlspecialchars($driver_name) ?></span></div><?php endif; ?>
                <?php if (showPrintField('sap_trans')): ?><div class="item"><span class="label">Sap Trans</span><span>: <?= htmlspecialchars($sap_trans) ?></span></div><?php endif; ?>
                <?php if (showPrintField('vehicle_no')): ?><div class="item"><span class="label">Vehicle No</span><span>: <?= htmlspecialchars($row['vehicle_no']) ?></span></div><?php endif; ?>
                <?php if (showPrintField('driver_no')): ?><div class="item"><span class="label">DRIVER NO</span><span>: <?= htmlspecialchars($driver_no) ?></span></div><?php endif; ?>
                <?php if (showPrintField('party_name')): ?><div class="item"><span class="label">Party Name</span><span>: <?= htmlspecialchars($party_name) ?></span></div><?php endif; ?>
            </div>

            <?php if ($isFinal): ?>
            <table class="weights-table">
                <thead><tr><th>Gross Wt</th><th>Tare Wt</th><th>Net Wt</th><th>Gate In Date</th><th>Gate Out Date</th></tr></thead>
                <tbody><tr>
                    <td><?= number_format($disp_gross, 2) ?></td><td><?= number_format($disp_tare, 2) ?></td><td><?= number_format($disp_net, 2) ?></td>
                    <td><?= formatDateTime($row['gross_date']??'', $row['gross_time']??'') ?></td><td><?= formatDateTime($row['tare_date']??'', $row['tare_time']??'') ?></td>
                </tr></tbody>
            </table>
            <?php else: ?>
            <table class="weights-table">
                <thead><tr><th><?= ($row['gt_type']=='G')?'Gross Wt':'Tare Wt' ?></th><th>Date & Time</th></tr></thead>
                <tbody><tr><td><?= number_format($disp_first, 2) ?></td><td><?= formatDateTime($row['first_date']??'', $row['first_time']??'') ?></td></tr></tbody>
            </table>
            <?php endif; ?>

            <?php if (showPrintField('signature_block')): ?>
            <div class="certify-row"><div>Certified By: _____________</div><div style="text-align:right;">Billing By: _____________</div></div>
            <?php endif; ?>

            <div class="weight-labels">
                <div class="weight-item">TARE WEIGHT<div class="date-time"><?= formatDateTime($row['tare_date']??'', $row['tare_time']??'') ?></div></div>
                <div class="weight-item">GROSS WEIGHT<div class="date-time"><?= formatDateTime($row['gross_date']??'', $row['gross_time']??'') ?></div></div>
            </div>
        </div>

        <?php if (showPrintField('cctv_images')): ?>
        <div class="images-section">
            <div class="img-block left">
                <div class="img-title">TARE WEIGHT IMAGES</div>
                <div class="image-pair">
                    <?php if (!empty($tareImgs)): foreach ($tareImgs as $img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="Tare Image">
                    <?php endforeach; else: ?>
                        <div class="img-placeholder">CAMERA 1</div><div class="img-placeholder">CAMERA 2</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="img-block right">
                <div class="img-title">GROSS WEIGHT IMAGES</div>
                <div class="image-pair">
                    <?php if (!empty($grossImgs)): foreach ($grossImgs as $img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="Gross Image">
                    <?php endforeach; else: ?>
                        <div class="img-placeholder">CAMERA 3</div><div class="img-placeholder">CAMERA 4</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endfor; ?>
</div>