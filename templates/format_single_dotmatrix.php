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