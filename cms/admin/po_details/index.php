<?php
function getCurrencySymbol($currency)
{
    switch (strtoupper($currency)) {
        case 'USD':
            return '$';
        case 'EUR':
            return '€';
        case 'INR':
        default:
            return '₹';
    }
}
function formatIndianMoney($num)
{
    $parts = explode('.', number_format($num, 2, '.', ''));
    $whole = $parts[0];
    $decimal = $parts[1];
    $formatted = preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $whole);
    return $formatted . "." . $decimal;
}
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$selected_company = isset($_GET['company']) ? $_GET['company'] : '';
$selected_fy = isset($_GET['fy']) ? $_GET['fy'] : '';

// Determine date range for summary based on financial year
$summary_from_date = '';
$summary_to_date = '';

if (!empty($selected_fy)) {
    // Financial year is in format YYYY-YYYY
    $years = explode('-', $selected_fy);
    $start_year = $years[0];
    $end_year = $years[1];

    $summary_from_date = $start_year . '-04-01';
    $summary_to_date = $end_year . '-03-31';
} elseif (!empty($from_date) && !empty($to_date)) {
    // Fallback to month filters if no FY is selected
    $summary_from_date = date('Y-m-d', strtotime($from_date . '-01'));
    $summary_to_date = date('Y-m-t', strtotime($to_date . '-01')); // 't' gives the last day of the month
}




// Initialize summary variables
$total_orders = 0;
$completed_orders = 0;
$pending_orders = 0;
$total_amount_all = 0;
$total_received_all = 0;
$total_pending_all = 0;

// Build conditions for summary query
$summary_conditions = array();
if (!empty($summary_from_date) && !empty($summary_to_date)) {
    $summary_conditions[] = "pil.po_date_created BETWEEN '{$summary_from_date}' AND '{$summary_to_date}'";
}
if (!empty($selected_company)) {
    $summary_conditions[] = "pil.company = '$selected_company'";
}
$summary_where_clause = !empty($summary_conditions) ? "WHERE " . implode(" AND ", $summary_conditions) : "";

$summary_qry = $conn->query("
    SELECT 
        SUM(CASE WHEN po.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN po.status != 'completed' THEN 1 ELSE 0 END) as pending_orders,
        SUM(pil.total_amount) as total_amount,
        SUM(po.advance_received + po.inspection_received + po.installation_received + po.credit_received) as total_received
    FROM purchase_orders po
    LEFT JOIN proforma_invoice_list pil ON pil.po_code = po.po_code
    {$summary_where_clause}
");
$summary_data = $summary_qry->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PO Records</title>
</head>

<body>
    <div class="card card-outline card-primary">
        <?php if (isset($_SESSION['userdata']) && $_SESSION['userdata']['type'] == '1') : ?>
            <?php
            // Get financial years for the dropdown
            $fy_qry = $conn->query("
                SELECT DISTINCT
                    CASE
                        WHEN MONTH(po_date_created) >= 4 THEN CONCAT(YEAR(po_date_created), '-', YEAR(po_date_created) + 1)
                        ELSE CONCAT(YEAR(po_date_created) - 1, '-', YEAR(po_date_created))
                    END as financial_year
                FROM proforma_invoice_list
                ORDER BY financial_year DESC
            ");
            ?>
            <div class="card-header" id="summaryHeader" data-toggle="collapse" data-target="#summaryBody" aria-expanded="<?= !empty($selected_fy) ? 'true' : 'false' ?>" aria-controls="summaryBody" style="cursor: pointer;">
                <h3 class="card-title">PO Summary</h3>
                <div class="card-tools" style="user-select: none;">
                    <i class="fas <?= !empty($selected_fy) ? 'fa-minus' : 'fa-plus' ?>"></i>
                </div>
            </div>
            <div id="summaryBody" class="collapse <?= !empty($selected_fy) ? 'show' : '' ?>" aria-labelledby="summaryHeader">
                <div class="card-body pb-0">
                    <div class="row">
                        <div class="col-12 d-flex justify-content-end">
                            <div class="form-inline">
                                <label for="fy_select" class="mr-2">Financial Year:</label>
                                <select id="fy_select" class="form-control form-control-sm">
                                    <option value="">All Time</option>
                                    <?php mysqli_data_seek($fy_qry, 0); // Reset pointer for reuse ?>
                                    <?php while ($fy_row = $fy_qry->fetch_assoc()) : ?>
                                        <option value="<?= $fy_row['financial_year'] ?>" <?= ($selected_fy == $fy_row['financial_year']) ? 'selected' : '' ?>><?= $fy_row['financial_year'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card-body">
                            <h5 class="text-center">Order Status</h5>
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <h5 class="text-center">Amount Summary</h5>
                            <div class="row mt-4">
                                <?php
                                $total_amount = $summary_data['total_amount'] ?? 0;
                                $total_received = $summary_data['total_received'] ?? 0;
                                $total_pending = $total_amount - $total_received;
                                ?>
                                <div class="col-md-4">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-center text-muted">Total Amount</span>
                                            <span class="info-box-number text-center text-bold"><?= getCurrencySymbol('INR') ?> <?= formatIndianMoney($total_amount) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box bg-success">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-center">Amount Received</span>
                                            <span class="info-box-number text-center text-bold"><?= getCurrencySymbol('INR') ?> <?= formatIndianMoney($total_received) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box bg-danger">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-center">Amount Pending</span>
                                            <span class="info-box-number text-center text-bold"><?= getCurrencySymbol('INR') ?> <?= formatIndianMoney($total_pending) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="card-header">
            <h3 class="card-title">PO Records</h3>
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
                <a href="<?php echo base_url ?>admin/?page=po_details/manage_po_details" class="btn btn-flat btn-primary">
                    <span class="fas fa-plus"></span> Add New PO
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-hover table-striped" id="po_details_table">
                <colgroup>
                    <col width="5%">
                    <col width="8%">
                    <col width="15%">
                    <col width="30%">
                    <col width="10%">
                    <col width="10%">
                    <col width="8%">
                    <?php if (isset($_SESSION['userdata']) && $_SESSION['userdata']['type'] == '1'): ?>
                        <col width="10%">
                    <?php endif; ?>
                    <col width="4%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>PO Number</th>
                        <th>Client Name</th>
                        <th>Requirement</th>
                        <th>PO Date</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <?php if (isset($_SESSION['userdata']) && $_SESSION['userdata']['type'] == '1'): ?>
                            <th>Payment Status</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Build conditions array
                    $conditions = array();
                    if (!empty($from_date) && !empty($to_date)) {
                        $conditions[] = "pil.po_date_created BETWEEN '$from_date-01' AND LAST_DAY('$to_date-01')";
                    }
                    if (!empty($selected_company)) {
                        $conditions[] = "pil.company = '$selected_company'";
                    }
                    $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

                    $result = $conn->query("
                        SELECT 
                            po.*,
                            c.company_name as client_name,
                            DATE_FORMAT(pil.po_date_created, '%d-%b-%Y') as po_date,
                            pil.po_date_created as po_date_raw,
                            pil.total_amount,
                            pil.currency,
                            pil.company,
                            (po.advance_received + po.inspection_received + po.installation_received + po.credit_received) as total_received
                        FROM purchase_orders po
                        LEFT JOIN clients c ON c.id = po.client_id 
                        LEFT JOIN proforma_invoice_list pil ON pil.po_code = po.po_code
                        {$where_clause}
                        ORDER BY po.id DESC
                    ");
                    $i = 1;

                    while ($row = $result->fetch_assoc()):
                        $delivery_date = strtotime($row['expected_delivery']);
                        $today = strtotime(date('Y-m-d'));
                        $days_left = ceil(($delivery_date - $today) / (60 * 60 * 24));
                        $start_date = strtotime($row['po_date_raw']);
                        $total_days = max(1, ceil(($delivery_date - $start_date) / (60 * 60 * 24)));
                        $days_passed = ceil(($today - $start_date) / (60 * 60 * 24));
                        if ($row['status'] == 'completed') {
                            $progress_class = 'bg-success';
                            $badge_class = 'badge-success';
                            $progress = 100;
                        } else {
                            if ($days_left < 0) {
                                $progress_class = 'bg-danger';
                                $badge_class = 'badge-danger';
                                $progress = 100;
                            } else {
                                $progress_class = 'bg-warning';
                                $badge_class = 'badge-warning';
                            }
                            $progress = min(100, max(0, ($days_passed / $total_days) * 100));
                        }
                    ?>
                        <tr class="data-row" data-company="<?php echo $row['company']; ?>">
                            <td class="align-middle"><?php echo $i++; ?>.</td>
                            <td class="align-middle"><?php echo $row['po_code']; ?></td>
                            <td class="align-middle"><?php echo $row['client_name']; ?></td>
                            <td class="align-middle"><?php echo nl2br($row['requirement']); ?></td>
                            <td class="align-middle"><?php echo $row['po_date']; ?></td>
                            <td class="align-middle"><?php echo date("d-M-Y", strtotime($row['expected_delivery'])); ?></td>
                            <td class="align-middle">
                                <?php
                                if ($row['status'] == 'completed') {
                                    echo '<span class="badge badge-success">Delivered: ' . date("d-M-Y", strtotime($row['actual_delivery_date'])) . '</span>';
                                } else {
                                    if ($days_left < 0) {
                                        echo '<span class="badge ' . $badge_class . '">Overdue by ' . abs($days_left) . ' days</span>';
                                    } elseif ($days_left == 0) {
                                        echo '<span class="badge ' . $badge_class . '">Due Today</span>';
                                    } else {
                                        echo '<span class="badge ' . $badge_class . '">' . $days_left . ' days left</span>';
                                    }
                                }
                                ?>
                            </td>
                            <?php if (isset($_SESSION['userdata']) && $_SESSION['userdata']['type'] == '1'): ?>
                                <td class="align-middle">
                                    <?php
                                    if ($row['total_amount'] > 0) {
                                        $balance = $row['total_amount'] - ($row['advance_received'] + $row['inspection_received'] + $row['installation_received'] + $row['credit_received']);
                                        if ($balance > 0) {
                                            echo '<span class="badge badge-danger">' . getCurrencySymbol($row['currency'] ?? 'INR') . ' ' . formatIndianMoney($balance) . '</span>';
                                        } else {
                                            echo '<span class="badge badge-success">Paid</span>';
                                        }
                                    } else {
                                        echo '<span class="badge badge-warning">' . getCurrencySymbol($row['currency'] ?? 'INR') . ' 0.00</span>';
                                    }
                                    ?>
                                </td>
                            <?php endif; ?>
                            <td class="align-middle text-center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item" href="?page=po_details/view_po_details&id=<?php echo $row['id']; ?>">
                                        <span class="fa fa-eye text-dark"></span> View
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="?page=po_details/manage_po_details&id=<?php echo $row['id'] ?>">
                                        <span class="fa fa-edit text-primary"></span> Edit
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <?php if ($row['status'] != 'completed'): ?>
                                        <a class="dropdown-item finish-project" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>" data-po="<?php echo $row['po_code']; ?>">
                                            <span class="fa fa-check text-success"></span> Finish Project
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    <?php endif; ?>
                                    <a class="dropdown-item delete-po" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>">
                                        <span class="fa fa-trash text-danger"></span> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <div id="progress_<?php echo $row['id']; ?>" class="progress-container d-none">
                            <div class="progress rounded-0" style="height: 4px;">
                                <div class="progress-bar <?php echo $progress_class ?>"
                                    role="progressbar"
                                    style="width: <?php echo $progress ?>%"
                                    title="<?php echo round($progress) ?>% time elapsed">
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <style>
        .progress {
            background-color: #f8f9fa;
        }

        .progress-wrapper {
            background: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border-top: 0 !important;
        }

        .hugopharm-row {
            background-color: rgba(0, 123, 255, 0.1) !important;
            /* Light blue */
        }

        .sbpanchal-row {
            background-color: rgba(255, 153, 0, 0.1) !important;
            /* Light orange */
        }

        .progress-wrapper {
            background: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border-top: 0 !important;
        }
    </style>
    <script>
        function formatIndianNumber(num) {
            const parts = num.toFixed(2).toString().split('.');
            const whole = parts[0];
            const decimal = parts[1];
            const formatted = whole.replace(/(\d)(?=(\d\d)+\d$)/g, "$1,");
            return formatted + "." + decimal;
        }

        function formatNumber(num) {
            return formatIndianNumber(parseFloat(num));
        }

        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#po_details_table').DataTable({
                "ordering": true,
                "pageLength": 10,
                "columnDefs": [{
                        "targets": [4, 5], // PO Date and Delivery Date columns
                        "type": "date",
                        "render": function(data, type, row) {
                            if (type !== 'sort') return data;
                            // Convert d-M-Y to YYYY-MM-DD for proper sorting
                            const parts = data.split('-');
                            const months = {
                                Jan: 1,
                                Feb: 2,
                                Mar: 3,
                                Apr: 4,
                                May: 5,
                                Jun: 6,
                                Jul: 7,
                                Aug: 8,
                                Sep: 9,
                                Oct: 10,
                                Nov: 11,
                                Dec: 12
                            };
                            const month = months[parts[1]] || 1;
                            return `${parts[2]}-${month.toString().padStart(2,'0')}-${parts[0]}`;
                        }
                    },
                    {
                        "targets": 6, // Status column index
                        "type": "num",
                        "orderable": true,
                        "render": function(data, type, row) {
                            if (type !== 'sort') return data;

                            // Extract days for sorting only when status column is clicked
                            if (data.includes('Delivered')) return 999999;
                            if (data.includes('Due Today')) return 0;

                            const match = data.match(/-?\d+/);
                            if (match) {
                                const days = parseInt(match[0]);
                                // For overdue, make it negative for proper sorting
                                return data.includes('Overdue') ? -days : days;
                            }
                            return 999999;
                        }
                    },
                    {
                        "targets": 7, // Payment Status column (only visible to admin)
                        "type": "num",
                        "render": function(data, type, row) {
                            if (type !== 'sort') return data;

                            if (data.includes('Fully Paid')) return 0;
                            if (data.includes('Amount Not Set')) return 999999;

                            // Extract amount for sorting
                            const match = data.match(/₹\s*([\d,]+\.?\d*)/);
                            if (match) {
                                // Remove commas and convert to number
                                return parseFloat(match[1].replace(/,/g, ''));
                            }
                            return 999999;
                        }
                    }
                ],
                "drawCallback": function(settings) {
                    // Remove previous progress bars to prevent duplicates
                    $('.progress-wrapper').remove();
                    
                    // Add company-specific row colors and handle progress bars
                    $('.data-row').each(function() {
                        // Add color coding based on company
                        const company = $(this).data('company');
                        if(company === 'Hugopharm') {
                            $(this).addClass('hugopharm-row');
                        } else if(company === 'S.B. Panchal') {
                            $(this).addClass('sbpanchal-row');
                        }
                        
                        // Handle progress bars (keeping existing functionality)
                        var id = $(this).find('.finish-project').data('id');
                        if(id) {
                            var progressBar = $('#progress_' + id).clone().removeClass('d-none');
                            var wrapper = $('<tr class="progress-wrapper">').append(
                                $('<td colspan="<?php echo (isset($_SESSION['userdata']) && $_SESSION['userdata']['type'] == '1') ? "9" : "8"; ?>" class="p-0">').append(progressBar)
                            );
                            wrapper.insertAfter($(this));
                        }
                    });

                    // Rebind finish project clicks after table redraws
                    $('.finish-project').off('click').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const id = $(this).data('id');
                        const po = $(this).data('po');
                        
                        $('#projectCompletionForm')[0].reset();
                        $('#po_id').val(id);
                        $('#po_code').val(po);
                        $('#projectCompletionModal').modal('show');
                    });
                }
            });

            // Remove the existing document-level handler
            $(document).off('click', '.finish-project');

            // Add a single delegated event handler
            $(document).on('click', '.finish-project', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const id = $(this).data('id');
                const po = $(this).data('po');

                // Reset form and set values
                $('#projectCompletionForm')[0].reset();
                $('#po_id').val(id);
                $('#po_code').val(po);

                // Show modal
                $('#projectCompletionModal').modal('show');
            });

            // Rest of your existing event handlers
            $('.delete-po').click(function() {
                const id = $(this).data('id');
                _conf("Are you sure to delete this Purchase Order permanently?", "delete_po_details", [id]);
            });

            $('.table td,.table th').addClass('py-1 px-2 align-middle');

            // Add filter handling code
            $('#filter_date').click(function(e) {
                e.preventDefault();
                var from_date = $('#from_date').val();
                var to_date = $('#to_date').val();
                var company = $('#company').val();
                var url = '<?php echo base_url ?>admin/?page=po_details';

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
                e.stopPropagation();
                $('#from_date').val('');
                $('#to_date').val('');
                $('#company').val('');
                window.location.href = '<?php echo base_url ?>admin/?page=po_details';
            });

            // Prevent dropdown from closing when clicking inside
            $('.dropdown-menu').click(function(e) {
                e.stopPropagation();
            });
        });

        function delete_po_details(id) {
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=delete_po_details",
                method: "POST",
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(resp) {
                    if (resp.status == 'success') {
                        location.reload();
                    } else {
                        alert_toast(resp.msg || "An error occurred", 'error');
                        end_loader();
                    }
                },
                error: function(xhr, status, error) {
                    alert_toast("An error occurred", 'error');
                    end_loader();
                }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Project Completion Modal -->
    <div class="modal fade" id="projectCompletionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Project</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="projectCompletionForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="po_id" id="po_id">
                        <input type="hidden" name="po_code" id="po_code">

                        <div class="form-group">
                            <label for="delivery_date">Actual Delivery Date</label>
                            <input type="date" class="form-control" name="delivery_date" id="delivery_date" required>
                        </div>

                        <div class="form-group">
                            <label for="bill_file">Bill Copy</label>
                            <input type="file" class="form-control-file" name="bill_file" id="bill_file" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>

                        <div class="form-group">
                            <label for="challan_file">Delivery Challan</label>
                            <input type="file" class="form-control-file" name="challan_file" id="challan_file" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Complete Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Financial Year filter change
            $('#fy_select').change(function() {
                var selected_fy = $(this).val();
                var current_url = new URL(window.location.href);

                if (selected_fy) {
                    current_url.searchParams.set('fy', selected_fy);
                } else {
                    current_url.searchParams.delete('fy');
                }
                window.location.href = current_url.toString();
            });

            // Summary section collapse toggle icon
            $('#summaryBody').on('show.bs.collapse', function() {
                $('#summaryHeader .card-tools i').removeClass('fa-plus').addClass('fa-minus');
            }).on('hide.bs.collapse', function() {
                $('#summaryHeader .card-tools i').removeClass('fa-minus').addClass('fa-plus');
            });

            // Order Status Pie Chart
            const ctx = document.getElementById('orderStatusChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['Pending', 'Delivered'],
                        datasets: [{
                            label: 'Order Status',
                            data: [
                                <?= $summary_data['pending_orders'] ?? 0 ?>,
                                <?= $summary_data['completed_orders'] ?? 0 ?>
                            ],
                            backgroundColor: [
                                'rgb(255, 99, 132)', // Red for Pending
                                'rgb(75, 192, 192)' // Green for Delivered
                            ],
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.label}: ${context.raw} Orders`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
        $(document).ready(function() {
            $('#projectCompletionForm').submit(function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                // Debug: Log form data
                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                $.ajax({
                    url: _base_url_ + "classes/Master.php?f=complete_project",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    beforeSend: () => {
                        start_loader();
                        console.log("Sending request...");
                    },
                    success: function(resp) {
                        console.log("Response:", resp);
                        end_loader();
                        if (resp.status == 'success') {
                            $('#projectCompletionModal').modal('hide');
                            location.reload();
                        } else {
                            alert_toast(resp.msg || "An error occurred", 'error');
                            console.error("Error:", resp.msg);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:");
                        console.error("Status:", status);
                        console.error("Error:", error);
                        console.error("Response:", xhr.responseText);
                        end_loader();
                        alert_toast("An error occurred", 'error');
                    }
                });
            });
        });
    </script>
    <?php
    if (isset($_SESSION['success_msg'])) {
        echo "<script>
            $(document).ready(function() {
                alert_toast('" . $_SESSION['success_msg'] . "', 'success');
            });
        </script>";
        unset($_SESSION['success_msg']);
    }

    if (isset($_SESSION['error_msg'])) {
        echo "<script>
            $(function() {
                alert_toast('{$_SESSION['error_msg']}', 'error');
            });
        </script>";
        unset($_SESSION['error_msg']);
    }
    ?>
</body>

</html>