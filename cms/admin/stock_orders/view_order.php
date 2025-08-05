<?php
if (!isset($_GET['id'])) {
    echo '<script>window.location.href = "' . base_url . 'admin/?page=stock_orders";</script>';
    exit;
}

$id = $_GET['id'];
$qry = $conn->query("SELECT so.*, s.name as supplier_name, s.address as supplier_address, s.contact as supplier_contact, 
                      u.firstname, u.lastname 
                      FROM stock_orders so 
                      INNER JOIN supplier_list s ON so.supplier_id = s.id 
                      INNER JOIN users u ON so.created_by = u.id 
                      WHERE so.id = $id");

if ($qry->num_rows == 0) {
    echo '<script>window.location.href = "' . base_url . 'admin/?page=stock_orders";</script>';
    exit;
}

$order = $qry->fetch_assoc();

// Get PO code if channel is purchase_order
$po_code = null;
if ($order['channel'] == 'purchase_order') {
    // Get PO code directly from the po_code column
    $po_code = $order['po_code'];
}

// Fetch order items
$items_qry = $conn->query("SELECT soi.*, i.name as item_name 
                           FROM stock_order_items soi 
                           INNER JOIN item_list i ON soi.item_id = i.id 
                           WHERE soi.order_id = $id");
?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Stock Order Details</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/?page=stock_orders/manage_order&id=<?php echo $id ?>" class="btn btn-flat btn-primary">
                <span class="fas fa-edit"></span> Edit
            </a>
            <a href="<?php echo base_url ?>admin/?page=stock_orders" class="btn btn-flat btn-secondary">
                <span class="fas fa-arrow-left"></span> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Order Code:</strong></td>
                        <td><?php echo $order['order_code']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Channel:</strong></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $order['channel'] == 'purchase_order' ? 'primary' : 
                                    ($order['channel'] == 'whatsapp' ? 'success' : 
                                    ($order['channel'] == 'phone_call' ? 'info' : 'secondary')); 
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order['channel'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Order Date:</strong></td>
                        <td><?php echo date('d-M-Y', strtotime($order['order_date'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Order Type:</strong></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></td>
                    </tr>
                    <?php if ($order['channel'] == 'purchase_order' && $po_code): ?>
                    <tr>
                        <td><strong>PO Code:</strong></td>
                        <td><?php echo $po_code; ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Supplier:</strong></td>
                        <td><?php echo $order['supplier_name']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Supplier Contact:</strong></td>
                        <td><?php echo $order['supplier_contact']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Work Order:</strong></td>
                        <td><?php echo $order['work_order_number'] ? $order['work_order_number'] : '<span class="text-muted">N/A</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Created By:</strong></td>
                        <td><?php echo $order['firstname'] . ' ' . $order['lastname']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Created On:</strong></td>
                        <td><?php echo date('d-M-Y H:i', strtotime($order['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if ($order['remarks']): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <strong>Remarks:</strong> <?php echo $order['remarks']; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-12">
                <h5>Order Items</h5>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Sr.</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th>Total Price</th>
                            <th>Negotiated Total</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $total_amount = 0;
                        $total_negotiated = 0;
                        while($item = $items_qry->fetch_assoc()): 
                            $total_amount += $item['total_price'];
                            $total_negotiated += $item['negotiated_total'];
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo $item['item_name']; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo $item['unit']; ?></td>
                                <td><?php echo number_format($item['unit_price'], 2); ?></td>
                                <td><?php echo number_format($item['total_price'], 2); ?></td>
                                <td><?php echo number_format($item['negotiated_total'], 2); ?></td>
                                <td><?php echo $item['remarks'] ? $item['remarks'] : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="5" class="text-right"><strong>Total:</strong></th>
                            <th><strong><?php echo number_format($total_amount, 2); ?></strong></th>
                            <th><strong><?php echo number_format($total_negotiated, 2); ?></strong></th>
                            <th></th>
                        </tr>
                        <tr class="table-success">
                            <th colspan="5" class="text-right"><strong>Savings:</strong></th>
                            <th colspan="3"><strong><?php echo number_format($total_amount - $total_negotiated, 2); ?></strong></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Print functionality
    $('.btn-print').click(function(){
        window.print();
    });
});
</script> 