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
    <div class="card-header">
        <h3 class="card-title">List of Purchase Orders</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/?page=purchase_order/manage_po" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Create New</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-striped table-bordered" id="purchase_order_table">
                <colgroup>
                    <col width="5%">                        
                    <col width="16%">
                    <col width="14%">
                    <col width="28%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="7%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr.</th>                            
                        <th>PO Code</th>
                        <th>Supplier</th>
                        <th>Items</th>
                        <th>Internal Ref</th>
                        <th>Total Amt.</th>
                        <th>Date Created</th>                        
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $qry = $conn->query("SELECT p.*, email, s.name as supplier FROM `purchase_order_list` p inner join supplier_list s on p.supplier_id = s.id ORDER BY p.created_at DESC");
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
                            <td class="text-center"><?php echo $i++; ?>.</td>
                            <td><?php echo $row['po_code'] ?></td>
                            <td><?php echo $row['supplier'] ?></td>
                            <td><?php echo implode(', ', $item_names) ?></td>
                            <td><?php echo isset($row['internal_ref_no']) ? $row['internal_ref_no'] : ''; ?></td>
                            <td><?php echo number_format($row['grand_total'],2) ?></td>
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
                                    <a class="dropdown-item" href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo $row['email']; ?>" target="_blank">
                                        <span class="fa fa-envelope text-primary"></span> Send Email</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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
</style>
<script>
    $(document).ready(function(){
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