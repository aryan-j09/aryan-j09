<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $delete = $conn->query("DELETE FROM `purchase_order_list` WHERE id = '{$id}'");
    if ($delete) {
        $resp['status'] = 'success';
        $_SESSION['flashdata']['type'] = 'success';
        $_SESSION['flashdata']['message'] = 'PO successfully deleted.';
    } else {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred. Error: ' . $conn->error;
    }
    echo json_encode($resp);
    exit;
}
?>

<div class="card card-outline card-primary">
    <div class="card-header" data-toggle="collapse" data-target="#summaryBody" aria-expanded="false" aria-controls="summaryBody" style="cursor: pointer;">
        <h3 class="card-title">Purchase Orders List <small class="text-muted">(Click to toggle summary)</small></h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool mr-2">
                <i class="fas fa-plus"></i>
            </button>
            <a href="<?php echo base_url ?>admin/?page=purchase_order/manage_po" class="btn btn-flat btn-primary" onclick="event.stopPropagation();"><span class="fas fa-plus"></span>  Create New</a>
        </div>
    </div>
    <div id="summaryBody" class="collapse">
        <div class="card-body border-bottom">
            <div class="form-group mb-3">
                <label for="po_daterange" class="mr-2">Select Period:</label>
                <div class="input-group" style="width: 350px;">
                    <input type="text" class="form-control" id="po_daterange" placeholder="Select Date Range">
                    <div class="input-group-append">
                        <span class="input-group-text" id="po_calendar_icon" style="cursor: pointer;"><i class="far fa-calendar-alt"></i></span>
                    </div>
                </div>
            </div>
            <div class="row" id="summary_container">
                <div class="col-md-4">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Amount</span>
                            <span class="info-box-number" id="total_amount">₹0</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" id="company_summary_container">
                    <!-- Company summaries will be inserted here -->
                </div>
                
                <div class="col-md-4" id="supplier_summary_container">
                    <!-- Supplier summaries will be inserted here -->
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-striped table-bordered" id="purchase_order_table">
                <colgroup>
                    <col width="5%">                        
                    <col width="13%">
                    <col width="11%">
                    <col width="20%">
                    <col width="9%">
                    <col width="8%">
                    <!-- <col width="8%">
                    <col width="8%">
                    <col width="8%"> -->
                    <col width="9%">
                    <col width="6%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr.</th>                            
                        <th>PO Code</th>
                        <th>Supplier</th>
                        <th>Items</th>
                        <th>Internal Ref</th>
                        <th>Total Amt.</th>
                        <!-- <th>Paid Amt.</th>
                        <th>Payment Status</th>
                        <th>Close Status</th> -->
                        <th>Date Created</th>                        
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $has_paid_amount = false;
                    $has_close_status = false;
                    $paid_amount_check = $conn->query("SHOW COLUMNS FROM `purchase_order_list` LIKE 'paid_amount'");
                    if($paid_amount_check && $paid_amount_check->num_rows > 0){
                        $has_paid_amount = true;
                    }
                    $close_status_check = $conn->query("SHOW COLUMNS FROM `purchase_order_list` LIKE 'close_status'");
                    if($close_status_check && $close_status_check->num_rows > 0){
                        $has_close_status = true;
                    }

                    if($has_paid_amount && $has_close_status){
                        // Reconcile closure status on load so old/missed records self-correct.
                        $conn->query("UPDATE purchase_order_list p
                            LEFT JOIN (
                                SELECT po_id, COALESCE(SUM(quantity),0) AS total_ordered
                                FROM po_items
                                GROUP BY po_id
                            ) poq ON poq.po_id = p.id
                            LEFT JOIN (
                                SELECT reference_id AS po_id, COALESCE(SUM(quantity),0) AS total_received
                                FROM stock_movement
                                WHERE UPPER(reference_type) = 'PO' AND UPPER(movement_type) = 'IN'
                                GROUP BY reference_id
                            ) smr ON smr.po_id = p.id
                            SET p.close_status = CASE
                                WHEN COALESCE(p.paid_amount,0) >= COALESCE(p.grand_total,0)
                                 AND COALESCE(poq.total_ordered,0) > 0
                                 AND COALESCE(smr.total_received,0) >= COALESCE(poq.total_ordered,0)
                                THEN 'closed'
                                ELSE 'open'
                            END,
                            p.closed_at = CASE
                                WHEN COALESCE(p.paid_amount,0) >= COALESCE(p.grand_total,0)
                                 AND COALESCE(poq.total_ordered,0) > 0
                                 AND COALESCE(smr.total_received,0) >= COALESCE(poq.total_ordered,0)
                                THEN IFNULL(p.closed_at, NOW())
                                ELSE NULL
                            END");

                        $qry = $conn->query("SELECT p.*, email, s.name as supplier FROM `purchase_order_list` p inner join supplier_list s on p.supplier_id = s.id ORDER BY p.created_at DESC");
                    } elseif($has_paid_amount && !$has_close_status) {
                        $qry = $conn->query("SELECT p.*, 'open' as close_status, NULL as closed_at, email, s.name as supplier FROM `purchase_order_list` p inner join supplier_list s on p.supplier_id = s.id ORDER BY p.created_at DESC");
                    } elseif(!$has_paid_amount && $has_close_status) {
                        $qry = $conn->query("SELECT p.*, 0 as paid_amount, email, s.name as supplier FROM `purchase_order_list` p inner join supplier_list s on p.supplier_id = s.id ORDER BY p.created_at DESC");
                    } else {
                        $qry = $conn->query("SELECT p.*, 0 as paid_amount, 'open' as close_status, NULL as closed_at, email, s.name as supplier FROM `purchase_order_list` p inner join supplier_list s on p.supplier_id = s.id ORDER BY p.created_at DESC");
                    }

                    while($row = $qry->fetch_assoc()):
                        // Get item names instead of count
                        $items_qry = $conn->query("SELECT i.name FROM `po_items` pi 
                                                  INNER JOIN item_list i ON pi.item_id = i.id 
                                                  WHERE pi.po_id = '{$row['id']}'");
                        $item_names = array();
                        while($item = $items_qry->fetch_assoc()) {
                            $item_names[] = $item['name'];
                        }
                    ?>
                        <tr class="data-row" data-company="<?php echo $row['company']; ?>">
                            <?php
                                $grand_total = (float)$row['grand_total'];
                                $paid_amount = (float)$row['paid_amount'];
                                if($paid_amount <= 0){
                                    $payment_status = 'Unpaid';
                                    $payment_badge = 'badge-secondary';
                                } elseif($paid_amount < $grand_total){
                                    $payment_status = 'Partially Paid';
                                    $payment_badge = 'badge-warning';
                                } elseif($paid_amount == $grand_total){
                                    $payment_status = 'Fully Paid';
                                    $payment_badge = 'badge-success';
                                } else {
                                    $payment_status = 'Overpaid';
                                    $payment_badge = 'badge-danger';
                                }

                                $is_closed = isset($row['close_status']) && strtolower((string)$row['close_status']) === 'closed';
                                $close_status = $is_closed ? 'Closed' : 'Open';
                                $close_badge = $is_closed ? 'badge-success' : 'badge-warning';
                            ?>
                            <td class="text-center"><?php echo $i++; ?>.</td>
                            <td><?php echo $row['po_code'] ?></td>
                            <td><?php echo $row['supplier'] ?></td>
                            <td><?php echo implode(', ', $item_names) ?></td>
                            <td><?php echo isset($row['internal_ref_no']) ? $row['internal_ref_no'] : ''; ?></td>
                            <td><?php echo number_format($row['grand_total'],2) ?></td>
                            <!-- <td><?php echo number_format($paid_amount,2) ?></td>
                            <td><span class="badge <?php echo $payment_badge; ?>"><?php echo $payment_status; ?></span></td>
                            <td><span class="badge <?php echo $close_badge; ?>"><?php echo $close_status; ?></span></td> -->
                            <td><?php echo date("d-M-Y",strtotime($row['created_at'])) ?></td>                            
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item" href="<?php echo base_url.'admin?page=purchase_order/view_po&id='.$row['id'].'&company='.urlencode($row['company']) ?>" data-id="<?php echo $row['id'] ?>" target="_blank" rel="noopener noreferrer">
                                        <span class="fa fa-eye text-dark"></span> View
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo base_url.'admin?page=purchase_order/manage_po&id='.$row['id'] ?>"><span class="fa fa-edit text-primary"></span>Edit</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item repeat_order" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                        <span class="fa fa-copy text-info"></span> Repeat Order
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item update_paid_amount" href="javascript:void(0)"
                                       data-id="<?php echo $row['id'] ?>"
                                       data-po-code="<?php echo htmlspecialchars($row['po_code'], ENT_QUOTES) ?>"
                                       data-grand-total="<?php echo (float)$row['grand_total'] ?>"
                                       data-paid-amount="<?php echo (float)$row['paid_amount'] ?>">
                                        <span class="fa fa-rupee-sign text-success"></span> Update Paid Amount
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo $row['email']; ?>" target="_blank">
                                        <span class="fa fa-envelope text-primary"></span> Send Email</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php if(!$has_paid_amount || !$has_close_status): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    Add the <strong>paid_amount</strong> and <strong>close_status</strong> columns in <strong>purchase_order_list</strong> first for full payment/closure tracking.
                </div>
            <?php endif; ?>
        </div>    
    </div>
</div>

<div class="modal fade" id="paidAmountModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Paid Amount</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="paid-amount-form">
                <div class="modal-body">
                    <input type="hidden" name="id" id="paid_amount_po_id">
                    <div class="form-group">
                        <label for="paid_amount_po_code">PO Code</label>
                        <input type="text" class="form-control" id="paid_amount_po_code" readonly>
                    </div>
                    <div class="form-group">
                        <label for="paid_amount_total">Total Amount</label>
                        <input type="text" class="form-control" id="paid_amount_total" readonly>
                    </div>
                    <div class="form-group">
                        <label for="paid_amount_balance">Balance Amount</label>
                        <input type="text" class="form-control" id="paid_amount_balance" readonly>
                    </div>
                    <div class="form-group mb-0">
                        <label for="paid_amount_input">Paid Amount</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="paid_amount" id="paid_amount_input" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    .hugopharm-row {
        background-color: rgba(0, 123, 255, 0.1) !important;
        /* Light blue */
    }

    .sbpanchal-row {
        background-color: rgba(255, 153, 0, 0.1) !important;
        /* Light orange */
    }

    .supplier-item {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.5rem 0;
    }

    .supplier-item:last-child {
        border-bottom: none;
    }

    .info-box-number {
        font-size: 1.5rem;
        font-weight: bold;
    }

    #po_daterange {
        cursor: pointer;
        background-color: #fff;
    }

    #po_daterange:focus {
        background-color: #fff;
        border-color: #80bdff;
    }

    /* Hide scrollbar but keep scrolling functionality */
    .supplier-list {
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE and Edge */
    }

    .supplier-list::-webkit-scrollbar {
        display: none; /* Chrome, Safari and Opera */
    }

    #po_calendar_icon {
        cursor: pointer;
    }

    #po_calendar_icon:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }
</style>
<script>
    $(document).ready(function(){
        // Initialize date range picker
        const today = new Date();
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        
        // Only initialize date picker and load summary when summary section is expanded
        let datePickerInitialized = false;

        $('#summaryBody').on('show.bs.collapse', function() {
            // Initialize date picker when summary is expanded
            if (!datePickerInitialized) {
                $('#po_daterange').daterangepicker({
                    startDate: startOfMonth,
                    endDate: today,
                    alwaysShowCalendars: true,
                    opens: 'left',
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'day'), moment().subtract(1, 'day')],
                        'Last 7 Days': [moment().subtract(6, 'day'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'day'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                        'Last 90 Days': [moment().subtract(89, 'day'), moment()],
                        'This Year': [moment().startOf('year'), moment().endOf('year')],
                        'All Time': [moment('2020-01-01'), moment()]
                    },
                    locale: {
                        format: 'DD-MM-YYYY',
                        separator: ' to ',
                        applyLabel: 'Apply',
                        cancelLabel: 'Cancel',
                        fromLabel: 'From',
                        toLabel: 'To',
                        customRangeLabel: 'Custom',
                        daysOfWeek: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                        firstDay: 1
                    }
                });

                // Load summary on date range change
                $('#po_daterange').on('apply.daterangepicker', function(ev, picker) {
                    loadSummary(picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD'));
                });

                datePickerInitialized = true;
                
                // Load initial summary
                const initialDates = $('#po_daterange').data('daterangepicker');
                loadSummary(initialDates.startDate.format('YYYY-MM-DD'), initialDates.endDate.format('YYYY-MM-DD'));
            }
        });

        // Update collapse icon
        $('#summaryBody').on('show.bs.collapse', function() {
            $('.card-header .btn-tool .fa').removeClass('fa-plus').addClass('fa-minus');
            $('.card-header').attr('aria-expanded', 'true');
        }).on('hide.bs.collapse', function() {
            $('.card-header .btn-tool .fa').removeClass('fa-minus').addClass('fa-plus');
            $('.card-header').attr('aria-expanded', 'false');
        });

        // Make calendar icon clickable
        $(document).on('click', '#po_calendar_icon', function() {
            $('#po_daterange').focus();
        });

        // Use event delegation for dynamically rendered table rows
        $(document).on('click', '.delete_data', function(){
            _conf("Are you sure to delete this Purchase order permanently?","delete_po",[$(this).attr('data-id')])
        });
        
        $(document).on('click', '.receive_data', function(){
            uni_modal("<i class='fa fa-boxes'></i> Receive Items","receiving/manage_receiving.php?po_id="+$(this).attr('data-id'),"large")
        });
        
        $(document).on('click', '.repeat_order', function(){
            _conf("Are you sure to create a repeat order?", "repeat_po", [$(this).attr('data-id')]);
        });

        $(document).on('click', '.update_paid_amount', function(){
            const grandTotal = parseFloat($(this).attr('data-grand-total') || 0);
            const paidAmount = parseFloat($(this).attr('data-paid-amount') || 0);
            $('#paid_amount_po_id').val($(this).attr('data-id'));
            $('#paid_amount_po_code').val($(this).attr('data-po-code'));
            $('#paid_amount_total').val(grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#paid_amount_balance').val((grandTotal - paidAmount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#paid_amount_input').val($(this).attr('data-paid-amount'));
            $('#paidAmountModal').modal('show');
        });

        $('#paid-amount-form').on('submit', function(e){
            e.preventDefault();
            start_loader();
            $.ajax({
                url:_base_url_+"classes/Master.php?f=update_po_paid_amount",
                method:"POST",
                data: $(this).serialize(),
                dataType:"json",
                error:err=>{
                    console.log(err)
                    alert_toast("An error occured.",'error');
                    end_loader();
                },
                success:function(resp){
                    if(typeof resp == 'object' && resp.status == 'success'){
                        $('#paidAmountModal').modal('hide');
                        location.reload();
                    }else{
                        alert_toast(resp.msg || "An error occured.",'error');
                        end_loader();
                    }
                }
            })
        });
        
        $('.table td,.table th').addClass('py-1 px-2 align-middle')
        $('#purchase_order_table').dataTable({
            "drawCallback": function(settings) {
                // Add company-specific row colors
                $('.data-row').each(function() {
                    const company = $(this).data('company');
                    if(company === 'Hugopharm') {
                        $(this).addClass('hugopharm-row');
                    } else if(company === 'S.B. Panchal') {
                        $(this).addClass('sbpanchal-row');
                    }
                });
            }
        });
    })

    function loadSummary(startDate, endDate) {
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=get_po_summary",
            method: "POST",
            data: {
                start_date: startDate,
                end_date: endDate
            },
            dataType: "json",
            error: function(xhr, status, error) {
                console.log('AJAX Error:', status, error);
                console.log('Response Text:', xhr.responseText);
                alert_toast("Error loading summary: " + error, 'error');
            },
            success: function(resp) {
                console.log('Summary Response:', resp);
                
                if(resp && resp.overall_total !== undefined) {
                    // Display overall total
                    $('#total_amount').text('₹' + new Intl.NumberFormat('en-IN', { maximumFractionDigits: 2 }).format(parseFloat(resp.overall_total)));
                    
                    // Display company totals
                    let companyHtml = '';
                    if(resp.by_company && resp.by_company.length > 0) {
                        resp.by_company.forEach(function(company) {
                            companyHtml += `
                                <div class="info-box bg-warning">
                                    <span class="info-box-icon"><i class="fas fa-building"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">${company.company}</span>
                                        <span class="info-box-number">₹${new Intl.NumberFormat('en-IN', { maximumFractionDigits: 2 }).format(parseFloat(company.total))}</span>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    $('#company_summary_container').html(companyHtml || '<p class="text-muted">No data</p>');
                    
                    // Display supplier totals as a list
                    let supplierHtml = '<div class="info-box bg-success"><span class="info-box-icon"><i class="fas fa-truck"></i></span><div class="info-box-content"><span class="info-box-text">Top Suppliers</span><div class="supplier-list" style="max-height: 150px; overflow-y: auto;">';
                    
                    if(resp.by_supplier && resp.by_supplier.length > 0) {
                        resp.by_supplier.slice(0, 5).forEach(function(supplier) {
                            supplierHtml += `<div class="supplier-item d-flex justify-content-between py-1"><small>${supplier.supplier}</small><small class="font-weight-bold">₹${new Intl.NumberFormat('en-IN', { maximumFractionDigits: 2 }).format(parseFloat(supplier.total))}</small></div>`;
                        });
                    } else {
                        supplierHtml += '<p class="text-muted">No data</p>';
                    }
                    
                    supplierHtml += '</div></div></div>';
                    $('#supplier_summary_container').html(supplierHtml);
                } else {
                    console.error('Invalid response structure:', resp);
                    alert_toast("Error: Invalid response from server", 'error');
                }
            }
        });
    }

    function delete_po($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_po",
            method:"POST",
            data:{id: $id},
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("An error occured.",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    location.reload();
                }else{
                    alert_toast("An error occured.",'error');
                    end_loader();
                }
            }
        })
    }

    function repeat_po($id){
    start_loader();
        location.href = _base_url_ + "admin/?page=purchase_order/manage_po&repeat_id=" + $id;
    }
</script>