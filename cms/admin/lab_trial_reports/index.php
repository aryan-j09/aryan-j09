<?php
/**
 * Lab Trial Reports - List View
 */

if (!isset($conn)) {
    die('Database connection not available');
}

$table_exists = false;
$rows = [];

$chk = $conn->query("SHOW TABLES LIKE 'lab_trial_reports'");
if ($chk && $chk->num_rows > 0) {
    $table_exists = true;

    $qry = $conn->query("SELECT ltr.*, COALESCE(c.company_name, '-') AS client_name
        FROM lab_trial_reports ltr
        LEFT JOIN clients c ON c.id = ltr.client_id
        ORDER BY ltr.created_at DESC");
    if ($qry) {
        while ($row = $qry->fetch_assoc()) {
            $rows[] = $row;
        }
    }
}
?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">List of Lab Trial Reports</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/?page=lab_trial_reports/manage_trial" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span> Create New</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <?php if (!$table_exists): ?>
                <div class="alert alert-warning mb-3">
                    Missing table: <strong>lab_trial_reports</strong>. Please create this table first.
                </div>
            <?php endif; ?>

            <table class="table table-bordered table-striped" id="lab-trial-reports-table">
                <colgroup>
                    <col width="5%">
                    <col width="20%">
                    <col width="13%">
                    <col width="30%">
                    <col width="12%">
                    <col width="14%">
                    <col width="10%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>Report Name</th>
                        <th>Batch/Trial No</th>
                        <th>Objective</th>
                        <th>Client Name</th>
                        <th>Trial Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($table_exists && count($rows) > 0): ?>
                        <?php $i = 1; foreach ($rows as $row): ?>
                            <?php
                                $row_name = $row['name'] ?? ($row['product_name'] ?? '');
                                $row_batch_trial = 'Batch ' . trim($row['batch_no'] ?? '-') . '/Trial ' . trim($row['trial_no'] ?? '-');
                                $row_objective = trim(strip_tags($row['objective'] ?? ''));
                                if ($row_objective === '') {
                                    $row_objective = trim(strip_tags($row['description'] ?? ''));
                                    $legacy_objective_decoded = json_decode($row_objective, true);
                                    if (is_array($legacy_objective_decoded)) {
                                        $row_objective = trim(strip_tags($legacy_objective_decoded['objective'] ?? ''));
                                    }
                                }
                                if (strlen($row_objective) > 120) {
                                    $row_objective = substr($row_objective, 0, 120) . '...';
                                }
                                $row_trial_start_date = '-';
                                $row_trial_range = trim($row['trial_date_range'] ?? '');
                                if ($row_trial_range !== '') {
                                    $trial_range_parts = explode(' to ', $row_trial_range);
                                    $row_trial_start_date = trim($trial_range_parts[0] ?? $row_trial_range);
                                }
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?>.</td>
                                <td><?php echo htmlspecialchars($row_name); ?></td>
                                <td><?php echo htmlspecialchars($row_batch_trial); ?></td>
                                <td><?php echo htmlspecialchars($row_objective); ?></td>
                                <td><?php echo htmlspecialchars($row['client_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row_trial_start_date); ?></td>
                                <td align="center">
                                    <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                        Action
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <div class="dropdown-menu" role="menu">
                                        <a class="dropdown-item" href="<?php echo base_url . 'admin/?page=lab_trial_reports/view_trial&id=' . $row['id']; ?>">
                                            <span class="fa fa-eye text-dark"></span> View
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="<?php echo base_url . 'admin/?page=lab_trial_reports/manage_trial&id=' . $row['id']; ?>">
                                            <span class="fa fa-edit text-primary"></span> Edit
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="<?php echo base_url . 'admin/?page=lab_trial_reports/manage_trial&repeat_from=' . $row['id']; ?>">
                                            <span class="fa fa-copy text-success"></span> Repeat Trial
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>">
                                            <span class="fa fa-trash text-danger"></span> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function delete_report(id){
    start_loader();
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=delete_lab_trial_report',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        error: function(err){
            console.log(err);
            alert_toast('An error occurred.', 'error');
            end_loader();
        },
        success: function(resp){
            if (typeof resp === 'object' && resp.status === 'success') {
                location.reload();
            } else {
                alert_toast((resp && resp.msg) ? resp.msg : 'An error occurred.', 'error');
                end_loader();
            }
        }
    });
}

$(document).ready(function(){
    $('.delete_data').click(function(){
        _conf('Are you sure to delete this Lab Trial Report permanently?', 'delete_report', [$(this).attr('data-id')]);
    });

    $('.table td, .table th').addClass('py-1 px-2 align-middle');
    $('#lab-trial-reports-table').dataTable({
        order: [[5, 'desc']],
        drawCallback: function() {
            $('.dropdown-toggle').dropdown();
        }
    });
});
</script>
