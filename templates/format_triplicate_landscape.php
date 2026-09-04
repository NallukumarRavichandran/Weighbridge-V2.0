<!-- ============================================================
     LAYOUT 4: TRIPLICATE LANDSCAPE 3-IN-1 VIEW
     ============================================================ -->
<style>
@page { size: A4 landscape; margin: 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Courier New', monospace; background: #fff; color: #000; padding: 4mm 5mm; }
.triplicate-wrapper { display: flex; justify-content: space-between; width: 100%; height: 196mm; gap: 4mm; }
.slip-third { width: 32.2%; height: 196mm; border: 1.5px solid #000; padding: 6px 8px; background: #fff; display: flex; flex-direction: column; justify-content: space-between; }
.copy-badge { text-align: center; font-size: 10px; font-weight: bold; background: #000; color: #fff; padding: 2px 0; margin-bottom: 4px; letter-spacing: 1px; }
.company-header { text-align: center; min-height: 45px; position: relative; }
.company-logo { position: absolute; left: 0; top: 0; width: 35px; height: 35px; object-fit: contain; }
.company-header h1 { font-size: 13.5px; font-weight: bold; letter-spacing: 1px; margin-left: 30px; }
.company-header h2 { font-size: 11px; font-weight: bold; letter-spacing: 2px; margin-left: 30px; }
.company-header .sub { font-size: 8px; margin-top: 1px; margin-left: 30px; }
.info-grid { font-size: 9.5px; font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 0; margin: 3px 0; }
.info-grid .item { display: flex; justify-content: space-between; padding: 1.5px 0; }
.weights-table { width: 100%; border-collapse: collapse; margin: 3px 0; font-size: 9px; }
.weights-table th, .weights-table td { border: 1px solid #000; padding: 3px 1px; text-align: center; }
.weights-table th { background: #f1f5f9; font-weight: bold; }
.certify-row { display: flex; justify-content: space-between; font-size: 9.5px; font-weight: bold; padding-top: 4px; border-top: 1px solid #000; }
.signature-section { text-align: right; font-size: 9.5px; font-weight: bold; margin-top: 8px; }
</style>

<?php $copyTitles = ['CUSTOMER COPY', 'TRANSPORTER COPY', 'GATE / SECURITY COPY']; ?>
<div class="triplicate-wrapper">
    <?php for ($c = 0; $c < 3; $c++): ?>
    <div class="slip-third">
        <div class="copy-badge"><?= $copyTitles[$c] ?></div>
        <div class="company-header">
            <?php if (!empty($logoPath)): ?><img src="<?= $logoPath ?>?v=<?= time() ?>" class="company-logo" alt="Logo"><?php endif; ?>
            <h1><?= strtoupper(htmlspecialchars($company['company_name'] ?? '')) ?></h1>
            <h2>WEIGHMENT SLIP</h2>
            <div class="sub"><?= strtoupper(htmlspecialchars($company['company_address'] ?? '')) ?></div>
        </div>

        <div class="info-grid">
            <?php if (showPrintField('slip_no')): ?><div class="item"><span>Slip No:</span><span><?= $row['slip_no'] ?></span></div><?php endif; ?>
            <?php if (showPrintField('vehicle_no')): ?><div class="item"><span>Vehicle:</span><span><?= htmlspecialchars($row['vehicle_no']) ?></span></div><?php endif; ?>
            <?php if (showPrintField('material_name')): ?><div class="item"><span>Material:</span><span><?= htmlspecialchars($material_name) ?></span></div><?php endif; ?>
            <?php if (showPrintField('party_name')): ?><div class="item"><span>Party:</span><span><?= htmlspecialchars($party_name) ?></span></div><?php endif; ?>
        </div>

        <?php if ($isFinal): ?>
        <table class="weights-table">
            <thead><tr><th>Gross</th><th>Tare</th><th>Net</th></tr></thead>
            <tbody><tr><td><?= number_format($disp_gross, 2) ?></td><td><?= number_format($disp_tare, 2) ?></td><td><b><?= number_format($disp_net, 2) ?></b></td></tr></tbody>
        </table>
        <div style="font-size:8.5px;margin:2px 0;">Date: <?= $row['gross_date'] ?? '' ?> <?= substr($row['gross_time'] ?? '', 0, 5) ?></div>
        <?php else: ?>
        <table class="weights-table">
            <thead><tr><th><?= ($row['gt_type']=='G')?'Gross':'Tare' ?> Wt</th><th>Date & Time</th></tr></thead>
            <tbody><tr><td><?= number_format($disp_first, 2) ?></td><td><?= formatDateTime($row['first_date']??'', $row['first_time']??'') ?></td></tr></tbody>
        </table>
        <?php endif; ?>

        <?php if (showPrintField('signature_block')): ?>
        <div class="certify-row"><div>Authorized Signatory</div><div>Driver Sign</div></div>
        <?php endif; ?>
    </div>
    <?php endfor; ?>
</div>