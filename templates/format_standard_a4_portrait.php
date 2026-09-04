<!-- ============================================================
     LAYOUT 1: STANDARD FULL-PAGE A4 PORTRAIT CERTIFICATE
     ============================================================ -->
<style>
@page { size: A4 portrait; margin: 0; }
body { font-family: "Courier New", Courier, monospace, Arial; margin: 0; padding: 8mm 12mm; background: #fff; color: #000; }
.slip-container { width: 100%; max-width: 720px; margin: 0 auto; }
.header { text-align: center; margin-bottom: 6px; position: relative; }
.company-logo { position: absolute; left: 0; top: 0; width: 52px; height: 52px; object-fit: contain; }
.company-name { font-size: 19px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
.company-addr { font-size: 12.5px; margin-top: 3px; line-height: 1.3; }
.cert-title { font-size: 15px; font-weight: bold; margin-top: 6px; letter-spacing: 1.5px; }
.divider-dashed { border-top: 1.5px dashed #000; margin: 8px 0; width: 100%; }
.info-table { width: 100%; border-collapse: collapse; font-size: 13.5px; font-weight: bold; }
.info-table td { padding: 4px 0; vertical-align: top; }
.info-table .lbl { min-width: 120px; }
.info-table .sep { width: 15px; text-align: center; }
.info-table .val { font-weight: normal; }
.weights-table { width: 100%; border-collapse: collapse; font-size: 13.5px; margin: 6px 0; }
.weights-table th, .weights-table td { border: 2px solid #000; padding: 6px 4px; text-align: center; }
.weights-table th { background: #f8fafc; font-size: 11px; font-weight: bold; }
.weights-table td { font-size: 13.5px; font-weight: bold; }
.signature-section { text-align: right; margin-top: 30px; padding-right: 25px; font-size: 13.5px; font-weight: bold; }
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

    <?php if ($isFinal): ?>
    <table class="weights-table">
        <thead><tr><th>Gross Wt</th><th>Tare Wt</th><th>Net Wt</th><th>Gross Date & Time</th><th>Tare Date & Time</th></tr></thead>
        <tbody><tr>
            <td><?= number_format($disp_gross, 2) ?> <?= $display_unit ?></td><td><?= number_format($disp_tare, 2) ?> <?= $display_unit ?></td><td><?= number_format($disp_net, 2) ?> <?= $display_unit ?></td>
            <td><?= formatDateTime($row['gross_date']??'', $row['gross_time']??'') ?></td><td><?= formatDateTime($row['tare_date']??'', $row['tare_time']??'') ?></td>
        </tr></tbody>
    </table>
    <?php else: ?>
    <table class="weights-table">
        <thead><tr><th><?= ($row['gt_type']=='G')?'Gross Wt':'Tare Wt' ?></th><th>Date & Time</th></tr></thead>
        <tbody><tr><td><?= number_format($disp_first, 2) ?> <?= $display_unit ?></td><td><?= formatDateTime($row['first_date']??'', $row['first_time']??'') ?></td></tr></tbody>
    </table>
    <?php endif; ?>
    <div class="divider-dashed"></div>

    <?php if (showPrintField('signature_block')): ?>
    <div class="signature-section"><?= getPrintFieldLabel('signature_block', "Operator's Signature") ?></div>
    <?php endif; ?>
</div>