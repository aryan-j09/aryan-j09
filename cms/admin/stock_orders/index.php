<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $delete = $conn->query("DELETE FROM `stock_orders` WHERE id = '{$id}'");
    if ($delete) {
        $resp['status'] = 'success';
        $_SESSION['flashdata']['type'] = 'success';
        $_SESSION['flashdata']['message'] = 'Stock order successfully deleted.';
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
        <h3 class="card-title">List of Stock Orders</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/?page=stock_orders/manage_order" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span> Create New Order</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-striped table-bordered" id="stock_orders_table">
                <colgroup>
                    <col width="5%">                        
                    <col width="10%">
                    <col width="10%">
                    <col width="25%">
                    <col width="15%">
                    <col width="10%">
                    <col width="10%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr.</th>                            
                        <th>Order Code</th>
                        <th>Order Date</th>
                        <th>Supplier</th>
                        <th>Work Order</th>
                        <th>Total Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $qry = $conn->query("SELECT so.*, s.name as supplier_name, u.firstname, u.lastname 
                                        FROM `stock_orders` so 
                                        INNER JOIN supplier_list s ON so.supplier_id = s.id 
                                        INNER JOIN users u ON so.created_by = u.id 
                                        ORDER BY so.created_at DESC");
                    while($row = $qry->fetch_assoc()):
                        // Get item count
                        $items_qry = $conn->query("SELECT COUNT(*) as item_count FROM stock_order_items WHERE order_id = '{$row['id']}'");
                        $item_count = $items_qry->fetch_assoc()['item_count'];
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?>.</td>
                            <td><?php echo $row['order_code'] ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['order_date'])) ?></td>
                            <td><?php echo $row['supplier_name'] ?></td>
                            <td><?php echo $row['work_order_number'] ? $row['work_order_number'] : '<span class="text-muted">N/A</span>' ?></td>
                            <td><?php echo number_format($row['negotiated_amount'],2) ?></td>
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item" href="<?php echo base_url.'admin?page=stock_orders/view_order&id='.$row['id'] ?>">
                                        <span class="fa fa-eye text-dark"></span> View
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo base_url.'admin?page=stock_orders/manage_order&id='.$row['id'] ?>">
                                        <span class="fa fa-edit text-primary"></span> Edit
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                        <span class="fa fa-trash text-danger"></span> Delete
                                    </a>
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
    $(document).ready(function(){
        $('.delete_data').click(function(){
            _conf("Are you sure to delete this stock order permanently?","delete_stock_order",[$(this).attr('data-id')])
        })
        $('.table td,.table th').addClass('py-1 px-2 align-middle')
        $('#stock_orders_table').dataTable();
    })
    
    function delete_stock_order($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_stock_order",
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
</script> 