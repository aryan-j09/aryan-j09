<?php
// Add indexes if they don't exist
$check_indexes = $conn->query("SHOW INDEX FROM proforma_invoice_list WHERE Key_name IN ('idx_company', 'idx_date', 'idx_client')");
if ($check_indexes->num_rows < 3) {
    $conn->query("ALTER TABLE `proforma_invoice_list` ADD INDEX `idx_company` (`company`)");
    $conn->query("ALTER TABLE `proforma_invoice_list` ADD INDEX `idx_date` (`po_date_created`)");
    $conn->query("ALTER TABLE `proforma_invoice_list` ADD INDEX `idx_client` (`client_id`)");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $delete = $conn->query("DELETE FROM `proforma_invoice_list` WHERE id = '{$id}'");
    if ($delete) {
        $resp['status'] = 'success';
        $_SESSION['flashdata']['type'] = 'success';
        $_SESSION['flashdata']['message'] = 'Proforma Invoice successfully deleted.';
    } else {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred. Error: ' . $conn->error;
    }
    echo json_encode($resp);
    exit;
}

// Get the selected date range
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Get the selected company
$selected_company = isset($_GET['company']) ? $_GET['company'] : '';

// Build the date range and company condition
$date_condition = "";
$conditions = array();

if (!empty($from_date) && !empty($to_date)) {
    $conditions[] = "pi.po_date_created BETWEEN '$from_date-01' AND LAST_DAY('$to_date-01')";
}
if (!empty($selected_company)) {
    $conditions[] = "pi.company = '$selected_company'";
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Use FORCE INDEX to ensure index usage
$query = "SELECT SQL_NO_CACHE pi.id, pi.po_code, pi.po_date_created, 
          pi.total_amount, pi.company, c.company_name as client,
          GROUP_CONCAT(pii.description ORDER BY pii.id SEPARATOR ', ') AS requirements
          FROM proforma_invoice_list pi FORCE INDEX (idx_date, idx_company)
          JOIN clients c ON c.id = pi.client_id
          LEFT JOIN proforma_invoice_items pii ON pii.proforma_invoice_id = pi.id";

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " GROUP BY pi.id, pi.po_code, pi.po_date_created, pi.total_amount, pi.company, c.company_name";
$query .= " ORDER BY pi.po_date_created DESC, pi.id DESC";

$result = $conn->query($query);
?>

<style>
    .hugopharm-row {
        background-color: rgba(0, 123, 255, 0.1) !important;
        /* Light blue */
    }

    .sbpanchal-row {
        background-color: rgba(255, 153, 0, 0.1) !important;
        /* Light orange */
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">List of Proforma Invoices</h3>
        <div class="card-tools">
            <div class="dropdown d-inline-block mr-2">
                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="filterDropdown" data-toggle="dropdown">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-right p-3" style="min-width: 300px;">
                    <form id="filterForm">
                        <div class="form-group row mb-2">
                            <label class="col-sm-4 col-form-label">Company:</label>
                            <div class="col-sm-8">
                                <select id="company" class="form-control">
                                    <option value="">All Companies</option>
                                    <option value="Hugopharm" <?php echo $selected_company == 'Hugopharm' ? 'selected' : ''; ?>>Hugopharm</option>
                                    <option value="S.B. Panchal" <?php echo $selected_company == 'S.B. Panchal' ? 'selected' : ''; ?>>S.B. Panchal</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row mb-2">
                            <label class="col-sm-4 col-form-label">From:</label>
                            <div class="col-sm-8">
                                <input type="month" id="from_date" class="form-control" value="<?php echo $from_date; ?>">
                            </div>
                        </div>
                        <div class="form-group row mb-2">
                            <label class="col-sm-4 col-form-label">To:</label>
                            <div class="col-sm-8">
                                <input type="month" id="to_date" class="form-control" value="<?php echo $to_date; ?>">
                            </div>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-sm btn-default" id="clearFilter">Clear</button>
                            <button type="button" class="btn btn-sm btn-primary" id="filter_date">Apply</button>
                        </div>
                    </form>
                </div>
            </div>
            <a href="<?php echo base_url ?>admin/?page=proforma_invoice/manage_pi" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span> Create New</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <?php if (isset($_SESSION['flashdata'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flashdata']['type']; ?>">
                    <?php echo $_SESSION['flashdata']['message']; ?>
                </div>
                <?php unset($_SESSION['flashdata']); ?>
            <?php endif; ?>
            <table class="table table-bordered table-striped" id="proforma_invoice_table">
                <colgroup>
                    <col style="width: 6%;">
                    <col style="width: 20%;">
                    <col style="width: 12%;">
                    <col style="width: 13%;">
                    <col style="width: 28%;">
                    <col style="width: 9%;">
                    <col style="width: 10%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>Client Name</th>
                        <th>PO Code</th>
                        <th>PO Date</th>
                        <th>Items</th>
                        <th>Total Amt.</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                        <tr class="<?php echo $row['company'] === 'Hugopharm' ? 'hugopharm-row' : 'sbpanchal-row'; ?>">
                            <td class="text-center"><?php echo $i++; ?>.</td>
                            <td><?php echo $row['client'] ?></td>
                            <td><?php echo $row['po_code'] ?></td>
                            <td><?php echo date("d-M-Y", strtotime($row['po_date_created'])) ?></td>
                            <td><?php echo !empty($row['requirements']) ? $row['requirements'] : '—'; ?></td>
                            <td><?php echo number_format($row['total_amount'], 2) ?></td>
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <?php if ($row['company'] == 'S.B. Panchal'): ?>
                                        <a class="dropdown-item" href="<?php echo base_url . 'admin?page=proforma_invoice/sbp_pi&id=' . $row['id'] ?>" data-id="<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</a>
                                    <?php else: ?>
                                        <a class="dropdown-item" href="<?php echo base_url . 'admin?page=proforma_invoice/view_pi&id=' . $row['id'] ?>" data-id="<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</a>
                                    <?php endif; ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo base_url . 'admin?page=proforma_invoice/manage_pi&id=' . $row['id'] ?>" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#filter_date').click(function(e) {
            e.preventDefault();
            var from_date = $('#from_date').val();
            var to_date = $('#to_date').val();
            var company = $('#company').val();
            var url = '<?php echo base_url ?>admin/?page=proforma_invoice';

            var params = [];
            if (company) params.push('company=' + encodeURIComponent(company));
            if (from_date) params.push('from_date=' + from_date);
            if (to_date) params.push('to_date=' + to_date);

            if (params.length > 0) {
                url += '&' + params.join('&');
            }

            window.location.href = url;
        });

        $('#clearFilter').click(function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event bubbling
            $('#from_date').val('');
            $('#to_date').val('');
            window.location.href = '<?php echo base_url ?>admin/?page=proforma_invoice';
        });

        // Prevent dropdown from closing when clicking inside
        $('.dropdown-menu').click(function(e) {
            e.stopPropagation();
        });

        // Existing code
        $('.delete_data').click(function() {
            _conf("Are you sure to delete this Proforma Invoice permanently?", "delete_pi", [$(this).attr('data-id')])
        })
        $('.table td,.table th').addClass('py-1 px-2 align-middle')
        $('#proforma_invoice_table').dataTable({
            "ordering": true,
            "pageLength": 10,
            "rowCallback": function(row, data, index) {
                if ($(row).find('td:eq(6)').find('.dropdown-menu a:first').attr('href').includes('sbp_pi')) {
                    $(row).addClass('sbpanchal-row');
                } else {
                    $(row).addClass('hugopharm-row');
                }
            }
        });
    })

    function delete_pi($id) {
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_pi",
            method: "POST",
            data: {
                id: $id
            },
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("An error occured.", 'error');
                end_loader();
            },
            success: function(resp) {
                if (typeof resp == 'object' && resp.status == 'success') {
                    location.reload();
                } else {
                    alert_toast("An error occured.", 'error');
                    end_loader();
                }
            }
        })
    }
</script>