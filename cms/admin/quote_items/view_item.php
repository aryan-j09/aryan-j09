<?php
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Fetch main item data
    $item = $conn->query("SELECT * FROM quote_items WHERE id = $id")->fetch_assoc();
    if(!$item) {
        echo "Item not found.";
        exit;
    }
    
    // Fetch related data
    $attributes = $conn->query("SELECT * FROM quote_item_attributes WHERE quote_item_id = $id");
    $prices = $conn->query("SELECT * FROM quote_item_prices WHERE quote_item_id = $id");
    $accessories = $conn->query("SELECT * FROM quote_item_accessories WHERE quote_item_id = $id");
    // Fetch images and descriptions
    $images = $conn->query("SELECT * FROM quote_item_images WHERE quote_item_id = $id");
}

function indian_number_format($num) {
    $num = (string)$num;
    $after_decimal = '';
    if(strpos($num, '.') !== false) {
        list($num, $after_decimal) = explode('.', $num, 2);
        $after_decimal = '.' . $after_decimal;
    }
    $last3 = substr($num, -3);
    $rest = substr($num, 0, -3);
    if($rest != '') {
        $rest = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest);
        return $rest . ',' . $last3 . $after_decimal;
    } else {
        return $last3 . $after_decimal;
    }
}
?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Item Details</h3>
        <div class="card-tools">
            <a href="?page=quote_items/manage_item&id=<?php echo $id; ?>" class="btn btn-flat btn-primary">
                <span class="fas fa-edit"></span> Edit
            </a>
            <a href="<?php echo base_url ?>admin/?page=quote_items" class="btn btn-secondary no-print">Back to List</a>
            <a href="javascript:void(0)" onclick="delete_item(<?php echo $id; ?>)" class="btn btn-flat btn-danger">
                <span class="fas fa-trash"></span> Delete
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <dl>
                        <dt>Name</dt>
                        <dd><?php echo $item['name']; ?></dd>
                        
                        <dt>Description</dt>
                        <dd><?php echo nl2br($item['description']); ?></dd>
                    </dl>
                </div>
                
                <!-- Images Gallery -->
                <?php if(isset($images) && $images->num_rows > 0): ?>
                <div class="col-md-6 d-flex flex-wrap align-items-start">
                    <div class="w-100 d-flex flex-row flex-wrap justify-content-start">
                        <?php while($img = $images->fetch_assoc()): ?>
                        <div class="d-flex flex-column align-items-center m-2" style="width: 150px;">
                            <img src="<?php echo base_url . $img['image_path']; ?>"
                                 class="img-thumbnail mb-1"
                                 alt="Item Image"
                                 style="max-height: 120px; max-width: 100%; object-fit: contain;">
                            <?php if(!empty($img['description'])): ?>
                                <div class="text-center text-muted small mt-1"><?php echo htmlspecialchars($img['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Technical Specifications -->
            <?php if($attributes->num_rows > 0): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <h4>Technical Specifications</h4>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Attribute</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($attr = $attributes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $attr['attribute_name']; ?></td>
                                <td><?php echo nl2br($attr['attribute_value']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Prices -->
            <?php if($prices->num_rows > 0): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <h4>Pricing Options</h4>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>                                
                                <th>Price</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($price = $prices->fetch_assoc()): ?>
                            <tr>
                                <td>₹<?php echo indian_number_format($price['price'], 2); ?></td>
                                <td><?php echo nl2br($price['description']); ?></td>                                
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Accessories -->
            <?php if($accessories->num_rows > 0): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <h4>Available Accessories</h4>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Price</th>
                                <th>Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($acc = $accessories->fetch_assoc()): ?>
                            <tr>
                                <td>₹<?php echo indian_number_format($acc['price'], 2); ?></td>
                                <td><?php echo nl2br($acc['name']); ?></td>                                
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function delete_item(id) {
    if(confirm('Are you sure you want to delete this item?')) {
        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=delete_quote_item',
            method: 'POST',
            data: {id: id},
            dataType: 'json',
            success: function(resp) {
                if(resp.status == 'success') {
                    location.href = './?page=quote_items';
                } else {
                    alert_toast(resp.error, 'error');
                }
            }
        });
    }
}
</script>