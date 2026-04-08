<?php
/**
 * Chemicals Master List (Display Only)
 */

if (!isset($conn)) {
    die('Database connection not available');
}

$open_create_modal = isset($_GET['open_modal']) && $_GET['open_modal'] === 'create';

$table_exists = false;
$chk = $conn->query("SHOW TABLES LIKE 'chemical_master_list'");
if ($chk && $chk->num_rows > 0) {
    $table_exists = true;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$rows = [];
if ($table_exists) {
    $where = '';
    if ($search !== '') {
        $search_esc = $conn->real_escape_string($search);
        $where = "WHERE name LIKE '%{$search_esc}%' OR brand LIKE '%{$search_esc}%' OR remarks LIKE '%{$search_esc}%'";
    }

    $qry = $conn->query("SELECT *
        FROM chemical_master_list
        {$where}
        ORDER BY name ASC, id DESC
        LIMIT 500");

    if ($qry) {
        while ($row = $qry->fetch_assoc()) {
            $rows[] = $row;
        }
    }
}
?>

<div class="container-fluid">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Chemical Master List</h3>
            <button type="button" id="open-create-modal" class="btn btn-sm btn-primary" style="margin-left:auto;">
                <i class="fas fa-plus"></i> Create New
            </button>
        </div>
        <div class="card-body">
            <?php if(!$table_exists): ?>
                <div class="alert alert-warning">
                    <strong>Missing table:</strong> <code>chemical_master_list</code>. Please run schema SQL first.
                </div>
            <?php endif; ?>

            <div class="form-section" style="background:#fff;padding:15px;border:1px solid #dee2e6;border-radius:6px;margin-bottom:15px;">
                <form method="GET" action="" class="row">
                    <input type="hidden" name="page" value="chemicals">
                    <div class="col-md-10">
                        <label>Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Search by name, brand, remarks">
                    </div>
                    <div class="col-md-2 d-flex align-items-end" style="gap:6px;">
                        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                        <a href="<?php echo base_url ?>admin/?page=chemicals" class="btn btn-secondary btn-sm">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead style="background-color: rgb(0, 31, 63); color: white;">
                        <tr>
                            <th width="40">#</th>
                            <th>Name</th>
                            <th width="180">Make/Brand</th>
                            <th>Remarks</th>
                            <th width="65" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($rows) === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No chemicals found</td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1; foreach($rows as $r): ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($r['name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['brand'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['remarks']); ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm delete-chemical" data-id="<?php echo $r['id']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="chemical-create-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Chemical</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" id="chem_name" class="form-control" placeholder="Chemical name">
                    </div>
                    <div class="col-md-6">
                        <label>Make/Brand</label>
                        <input type="text" id="chem_brand" class="form-control" placeholder="Brand">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label>Remarks</label>
                        <input type="text" id="chem_remarks" class="form-control" placeholder="Optional">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" id="save_chemical" class="btn btn-primary" <?php echo !$table_exists ? 'disabled' : ''; ?>><i class="fas fa-plus"></i> Save Chemical</button>
            </div>
        </div>
    </div>
</div>

<script>
function delete_chemical(id){
    $.ajax({
        url: '<?php echo base_url ?>classes/Master.php?f=delete_chemical_master',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(resp){
            if (resp.status === 'success') {
                sessionStorage.setItem('success_message', resp.msg || 'Chemical deleted');
                setTimeout(function(){ location.reload(); }, 350);
            } else {
                alert_toast(resp.msg || 'Delete failed', 'error');
            }
        },
        error: function(){
            alert_toast('Delete request failed', 'error');
        }
    });
}

$(function(){
    var successMsg = sessionStorage.getItem('success_message');
    if (successMsg) {
        alert_toast(successMsg, 'success');
        sessionStorage.removeItem('success_message');
    }

    $('#open-create-modal').on('click', function(){
        $('#chemical-create-modal').modal('show');
    });

    $('#save_chemical').on('click', function(){
        var payload = {
            name: $('#chem_name').val().trim(),
            brand: $('#chem_brand').val().trim(),
            remarks: $('#chem_remarks').val().trim()
        };

        if (!payload.name) {
            alert_toast('Name is required', 'warning');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '<?php echo base_url ?>classes/Master.php?f=save_chemical_master',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(resp){
                if (resp.status === 'success') {
                    sessionStorage.setItem('success_message', 'Chemical saved successfully.');
                    window.location.href = '<?php echo base_url ?>admin/?page=chemicals';
                } else {
                    alert_toast(resp.msg || 'Save failed', 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-plus"></i> Save Chemical');
                }
            },
            error: function(){
                alert_toast('Request failed', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-plus"></i> Save Chemical');
            }
        });
    });

    <?php if($open_create_modal): ?>
    $('#chemical-create-modal').modal('show');
    <?php endif; ?>

    $(document).on('click', '.delete-chemical', function(){
        var id = $(this).data('id');
        _conf('Are you sure to delete this Chemical permanently?', 'delete_chemical', [id]);
    });
});
</script>
