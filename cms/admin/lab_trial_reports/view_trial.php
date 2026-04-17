<?php
/**
 * Lab Trial Reports - View
 */

$report_id = intval($_GET['id'] ?? 0);
$report = null;

if ($report_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid report ID.</div>";
    return;
}

$qry = $conn->query("SELECT * FROM lab_trial_reports WHERE id = $report_id LIMIT 1");
if ($qry && $qry->num_rows > 0) {
    $report = $qry->fetch_assoc();
}

if (!$report) {
    echo "<div class='alert alert-danger'>Report not found.</div>";
    return;
}

$report_name = $report['name'] ?? ($report['product_name'] ?? '');
$batch_no_value = $report['batch_no'] ?? '';
$trial_no_value = $report['trial_no'] ?? '';
$batch_size_value = $report['batch_size'] ?? '';
$company_value = trim($report['company'] ?? 'Hugopharm');
if ($company_value !== 'S.B. Panchal') {
    $company_value = 'Hugopharm';
}
$client_value = '';
if (!empty($report['client_id'])) {
    $client_id = intval($report['client_id']);
    $cq = $conn->query("SELECT company_name FROM clients WHERE id = $client_id LIMIT 1");
    if ($cq && $cq->num_rows > 0) {
        $client_row = $cq->fetch_assoc();
        $client_value = $client_row['company_name'] ?? '';
    }
}
if ($client_value === '' && !empty($report['client'])) {
    $client_value = $report['client'];
}
$trial_date_range_value = $report['trial_date_range'] ?? '';
$client_representative_value = $report['client_representative'] ?? '';
$objective_value = $report['objective'] ?? '';
$equipment_value = $report['equipment'] ?? '';
$sections = [
    'purpose' => '',
    'input_characteristics' => '',
    'formula' => '',
    'observations' => '',
    'results_evaluation' => '',
    'future_action' => ''
];

foreach ($sections as $key => $value) {
    if (isset($report[$key]) && $report[$key] !== null && $report[$key] !== '') {
        $sections[$key] = $report[$key];
    }
}

if (!empty($report['description'])) {
    $decoded = json_decode($report['description'], true);
    if (is_array($decoded)) {
        foreach ($sections as $key => $value) {
            if (empty($sections[$key]) && isset($decoded[$key])) {
                $sections[$key] = $decoded[$key];
            }
        }
    } else if (empty($sections['purpose'])) {
        $sections['purpose'] = $report['description'];
    }
}
?>
<style>
    @media print {
        .no-print { display: none !important; }
        * { box-shadow: none !important; }
        .print-header { margin-bottom: 20px !important; }
    }
    .print-header {
        color: rgb(47, 84, 150);
        text-align: center;
        margin-bottom: 20px;
    }
    .print-header h1 {
        font: 70px 'Raleway SemiBold';
        margin: 0;
    }
    .print-header p {
        font: 27px 'Garamond';
        margin: 0;
        padding: 0;
    }
    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .header-left img {
        max-width: 130px;
        max-height: 130px;
    }
    .header-middle {
        text-align: center;
    }
    .header-right {
        text-align: left;
        position: relative;
        padding-left: 20px;
    }
    .header-right::before {
        content: '';
        position: absolute;
        left: 9px;
        top: 0;
        bottom: 0;
        width: 1px;
        border-left: 2px dotted rgb(47, 84, 150);
    }
    .trial-report-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        overflow: hidden;
    }
    .trial-report-header {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 14px 16px;
    }
    .trial-report-header h4 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }
    .trial-report-body {
        padding: 16px;
    }
    .report-top-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
        table-layout: fixed;
    }
    .report-top-table td {
        border: 1px solid #333;
        vertical-align: middle;
        padding: 3px 6px;
        line-height: 1.15;
    }
    .report-top-table .logo-cell {
        width: 22%;
        text-align: center;
        padding: 2px 4px;
    }
    .report-top-table .logo-cell img {
        display: block;
        width: 100%;
        max-width: 120px;
        max-height: 72px;
        margin: 0 auto;
        height: auto;
        object-fit: contain;
    }
    .report-top-table .company-cell {
        text-align: center;
        font-size: 9px;
        line-height: 1.15;
        font-weight: 600;
    }
    .report-top-table .record-title {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1.1;
    }
    .report-top-table .ref-cell {
        width: 22%;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.15;
        padding: 3px 6px;
    }
    .report-top-table .company-cell {
        width: 56%;
    }
    .report-top-table .ref-label {
        display: block;
        margin-bottom: 1px;
    }
    .report-top-table .top-product-row th,
    .report-top-table .top-product-row td {
        border: 1px solid #333;
        padding: 4px 8px;
        line-height: 1.15;
        font-weight: 600;
    }
    .report-top-table .top-product-row th {
        text-align: left;
        background: #f8f9fa;
    }
    .report-top-table .top-product-row td {
        font-weight: 400;
        background: #f8f9fa;
    }
    .report-sub-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin-top: -6px;
        margin-bottom: 8px;
    }
    .report-sub-table td {
        border: 1px solid #333;
        padding: 3px 8px;
        width: 50%;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.15;
    }
    .report-sub-table td + td {
        border-left: 1px solid #333;
    }
    .trial-meta-table {
        width: 100%;
        border-collapse: collapse;
    }
    .trial-meta-table th,
    .trial-meta-table td {
        border: 1px solid #dee2e6;
        padding: 3px 8px;
        vertical-align: middle;
        line-height: 1.15;
    }
    .trial-meta-table th {
        width: 180px;
        background: #f8f9fa;
        font-weight: 600;
    }
    .trial-legacy-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
        margin-bottom: 8px;
    }
    .trial-legacy-table th,
    .trial-legacy-table td {
        border: 1px solid #dee2e6;
        padding: 3px 8px;
        vertical-align: middle;
        line-height: 1.15;
    }
    .trial-legacy-table th {
        width: 180px;
        background: #f8f9fa;
        font-weight: 600;
        white-space: nowrap;
    }
    .trial-extra-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
    }
    .trial-extra-table th,
    .trial-extra-table td {
        border: 1px solid #bcbcbc;
        padding: 3px 8px;
        vertical-align: middle;
        line-height: 1.15;
    }
    .trial-extra-table th {
        width: 220px;
        background: #efefef;
        font-weight: 700;
    }
    .section-box {
        margin-top: 2px;
        line-height: 1.2;
        white-space: pre-wrap;
    }
    .section-box p {
        margin: 0;
    }
    .section-box table {
        width: 100%;
        border-collapse: collapse;
        margin: 2px 0;
    }
    .section-box th,
    .section-box td {
        border: 1px solid #bcbcbc;
        padding: 3px 8px;
        line-height: 1.15;
        vertical-align: middle;
    }
    .section-title {
        margin-top: 10px;
        margin-bottom: 4px;
        font-size: 16px;
        font-weight: 600;
    }
    .sbp-header {
        width: 100%;
        margin-bottom: 12px;
    }
    .sbp-header img {
        width: 100%;
        height: auto;
        object-fit: contain;
        display: block;
    }
</style>
<?php if ($company_value === 'S.B. Panchal'): ?>
<div class="sbp-header">
    <img src="<?php echo base_url; ?>uploads/SBLetter.png" alt="Company Letterhead">
</div>
<?php else: ?>
<div class="header-container print-header">
    <div class="header-left">
        <img src="<?php echo base_url; ?>uploads/HUGO.png" alt="Company Logo" class="company-logo">
    </div>
    <div class="header-middle text-center flex-grow-1">
        <h1 style="font-size:70px">HUGOPHARM</h1>
        <p style="font-size:30px; font-style: italic;">Systems Engineered with Mind and Spirit</p>
    </div>
    <div class="header-right text-left">
        <p style="font-size: 18px;"><strong>Regd Office:</strong> 8, Jogani Industrial Estate,<br>
        541 Senapati Bapat Marg, Dadar (W), Mumbai 400028<br>
        Mob: 9869415083 Email: sales@sbpanchal.com<br>
        <strong>Works:</strong> Plot No TS 20, MIDC Phase 2, Sagaon,<br>
        Manpada Road, Dombivli (E) 421203</p>
        GSTIN:27AACCH1711N1ZM
    </div>
</div>
<?php endif; ?>

<div class="no-print mb-3 d-flex justify-content-end">
    <a href="<?php echo base_url ?>admin/?page=lab_trial_reports/manage_trial&id=<?php echo $report_id; ?>" class="btn btn-sm btn-primary mr-2"><i class="fas fa-edit"></i> Edit</a>
    <a href="<?php echo base_url ?>admin/?page=lab_trial_reports" class="btn btn-sm btn-secondary mr-2">Back</a>
    <button type="button" onclick="window.print()" class="btn btn-sm btn-info"><i class="fas fa-print"></i> Print</button>
</div>
<div class="card card-outline card-primary trial-card">
    <div class="card-body">
<table class="report-top-table">
    <tr class="top-product-row">
        <th class="text-center" colspan="2">R&D BATCH MANUFACTURING RECORD</th>
    </tr>
    <tr>
        <td style="font-weight: 600;">Product name: <?php echo htmlspecialchars($report_name); ?></td>
        <td class="ref-cell" style="width: 35%;">
            <span class="ref-label">Date: <?php echo !empty($report['created_at']) ? date('d-M-Y', strtotime($report['created_at'])) : '-'; ?></span>
            <span class="ref-label">Batch No.: <?php echo htmlspecialchars($batch_no_value); ?></span>
        </td>
    </tr>
    <tr>
        <td>Trial No: <?php echo htmlspecialchars($trial_no_value); ?></td>
        <td>Batch Size: <?php echo htmlspecialchars($batch_size_value); ?></td>
    </tr>
</table>

        <div class="trial-section-block">
            <div class="section-title">Trial Details</div>
            <table class="trial-legacy-table mt-0">
                <tr>
                    <th>Client</th>
                    <td colspan="3"><?php echo htmlspecialchars($client_value); ?></td>
                </tr>
                <tr>
                    <th>Date of Trial</th>
                    <td colspan="3"><?php echo htmlspecialchars($trial_date_range_value); ?></td>
                </tr>
                <tr>
                    <th>Clients Representative</th>
                    <td colspan="3"><?php echo htmlspecialchars($client_representative_value); ?></td>
                </tr>
                <tr>
                    <th>Objective</th>
                    <td colspan="3"><?php echo nl2br(htmlspecialchars($objective_value)); ?></td>
                </tr>
                <tr>
                    <th>Equipment</th>
                    <td colspan="3"><?php echo nl2br(htmlspecialchars($equipment_value)); ?></td>
                </tr>                
            </table>
        </div>

        <div class="trial-section-block">
            <div class="section-title">Purpose</div>
            <div class="section-box mt-0"><?php echo !empty($sections['purpose']) ? $sections['purpose'] : '&nbsp;'; ?></div>
        </div>

        <div class="trial-section-block">
            <div class="section-title">Input Characteristics</div>
            <div class="section-box mt-0"><?php echo !empty($sections['input_characteristics']) ? $sections['input_characteristics'] : '&nbsp;'; ?></div>
        </div>

        <div class="trial-section-block">
            <div class="section-title">Formula</div>
            <div class="section-box mt-0"><?php echo !empty($sections['formula']) ? $sections['formula'] : '&nbsp;'; ?></div>
        </div>

        <div class="trial-section-block">
            <div class="section-title">Observations</div>
            <div class="section-box mt-0"><?php echo !empty($sections['observations']) ? $sections['observations'] : '&nbsp;'; ?></div>
        </div>

        <div class="trial-section-block">
            <div class="section-title">Results/Evaluation</div>
            <div class="section-box mt-0"><?php echo !empty($sections['results_evaluation']) ? $sections['results_evaluation'] : '&nbsp;'; ?></div>
        </div>

        <div class="trial-section-block">
            <div class="section-title">Future Action</div>
            <div class="section-box mt-0"><?php echo !empty($sections['future_action']) ? $sections['future_action'] : '&nbsp;'; ?></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'true' && typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Report saved successfully!',
            showConfirmButton: false,
            timer: 2000,
            didOpen: function(toast) {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
    }
});
</script>
