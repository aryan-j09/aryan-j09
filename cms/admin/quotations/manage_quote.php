<?php
if (isset($_GET['id'])) {
    $qry = $conn->query("SELECT * FROM quotations WHERE id = '{$_GET['id']}'");
    if ($qry->num_rows > 0) {
        foreach ($qry->fetch_assoc() as $k => $v) {
            $$k = $v;
        }
    }

    $item_ids = [];
    $price_ids = [];
    $accessory_ids = [];

    $items_qry = $conn->query("SELECT * FROM quotation_items WHERE quotation_id = '{$id}'");
    while($row = $items_qry->fetch_assoc()){
        $item_ids[] = $row['quote_item_id'];
        
        $prices_qry = $conn->query("SELECT * FROM quotation_item_prices WHERE quotation_item_id = '{$row['id']}'");
        while($p_row = $prices_qry->fetch_assoc()){
            $price_ids[] = $p_row['price_id'];
        }

        $acc_qry = $conn->query("SELECT * FROM quotation_item_accessories WHERE quotation_item_id = '{$row['id']}'");
        while($a_row = $acc_qry->fetch_assoc()){
            $accessory_ids[] = $a_row['accessory_id'];
        }
    }
}

$items = $conn->query("SELECT * FROM quote_items ORDER BY name ASC");
?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0"><?php echo isset($id) ? "Edit Quotation" : "Generate Quotation"; ?></h3>
    </div>
    <div class="card-body">
        <form id="generate-quote-form" method="post" action="">
            <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
            <input type="hidden" name="lead_id" value="<?php echo isset($lead_id) ? $lead_id : (isset($_GET['lead_id']) ? $_GET['lead_id'] : ''); ?>">
            <div class="form-group">
                <label for="quotation_no"><strong>Quotation Number</strong></label>
                <input type="text" class="form-control" name="quotation_no" id="quotation_no" value="<?php echo isset($quotation_code) ? $quotation_code : '' ?>" required>
            </div>
            <div class="form-group">
                <label><strong>Select Machines and Details to Include in Quotation</strong></label>
                <div class="accordion" id="machinesAccordion">
                    <?php $i = 0; while($item = $items->fetch_assoc()): $i++; ?>
                        <div class="card mb-2">
                            <div class="card-header p-2" id="heading_<?php echo $item['id']; ?>"
                                 data-toggle="collapse"
                                 data-target="#collapse_<?php echo $item['id']; ?>"
                                 aria-expanded="false"
                                 aria-controls="collapse_<?php echo $item['id']; ?>"
                                 style="cursor: pointer; user-select: none;">
                                <div class="d-flex align-items-center w-100">
                                    <div class="form-check mr-2">
                                        <input class="form-check-input item-master-checkbox" type="checkbox"
                                               name="selected_machines[]"
                                               value="<?php echo $item['id']; ?>"
                                               style="position: relative;" <?php echo isset($item_ids) && in_array($item['id'], $item_ids) ? "checked" : "" ?>>
                                    </div>
                                    <label class="mb-0 mr-2" for="machine_<?php echo $item['id']; ?>">
                                        <strong><?php echo htmlspecialchars($item['name'] ?? 'Machine #'.$item['id']); ?></strong>
                                    </label>
                                </div>
                                <div class="small text-muted ml-4">
                                    <?php echo htmlspecialchars(mb_strimwidth(strip_tags(html_entity_decode($item['description'] ?? '')), 0, 80, "...")); ?>
                                </div>
                            </div>
                            <div id="collapse_<?php echo $item['id']; ?>" class="collapse"
                                 aria-labelledby="heading_<?php echo $item['id']; ?>" data-parent="#machinesAccordion">
                                <div class="card-body py-2">
                                    <!-- Technical Specifications (NO checkboxes) -->
                                    <div class="mb-2">
                                        <strong>Technical Specifications:</strong>
                                        <?php
                                        $stmt_specs = $conn->prepare("SELECT * FROM quote_item_attributes WHERE quote_item_id = ?");
                                        $stmt_specs->bind_param("i", $item['id']);
                                        $stmt_specs->execute();
                                        $stmt_specs->store_result();
                                        $stmt_specs->bind_result($id, $quote_item_id, $attribute_name, $attribute_value);
                                        $specs = [];
                                        while($stmt_specs->fetch()) {
                                            $specs[] = [
                                                'id' => $id,
                                                'quote_item_id' => $quote_item_id,
                                                'attribute_name' => $attribute_name,
                                                'attribute_value' => $attribute_value
                                            ];
                                        }
                                        $stmt_specs->close();

                                        if($specs): ?>
                                            <table class="table table-sm table-bordered w-auto mb-2 ml-3">
                                                <thead>
                                                <tr>
                                                    <th>Attribute</th>
                                                    <th>Value</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach($specs as $spec): ?>
                                                    <tr>
                                                        <td>
                                                            <?php echo nl2br(htmlspecialchars(strip_tags(html_entity_decode($spec['attribute_name'] ?? '')))); ?>
                                                        </td>
                                                        <td>
                                                            <?php echo nl2br(htmlspecialchars(strip_tags(html_entity_decode($spec['attribute_value'] ?? '')))); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <div class="ml-3 text-muted">No specifications found.</div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Pricing Details -->
                                    <div class="mb-2">
                                        <strong>Pricing Details:</strong>
                                        <?php
                                        $stmt_prices = $conn->prepare("SELECT * FROM quote_item_prices WHERE quote_item_id = ?");
                                        $stmt_prices->bind_param("i", $item['id']);
                                        $stmt_prices->execute();
                                        $stmt_prices->store_result();
                                        $stmt_prices->bind_result($id, $quote_item_id, $price, $description);
                                        $prices = [];
                                        while($stmt_prices->fetch()) {
                                            $prices[] = [
                                                'id' => $id,
                                                'quote_item_id' => $quote_item_id,
                                                'price' => $price,
                                                'description' => $description
                                            ];
                                        }
                                        $stmt_prices->close();

                                        if($prices): ?>
                                            <table class="table table-sm table-bordered w-auto mb-2 ml-3">
                                                <thead>
                                                <tr>
                                                    <th>Select</th>
                                                    <th>Price</th>
                                                    <th>Description</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach($prices as $price): ?>
                                                    <tr>
                                                        <td class="text-center">
                                                            <div class="form-check d-flex justify-content-center">
                                                                <input class="form-check-input" type="checkbox"
                                                                       name="prices[<?php echo $item['id']; ?>][]"
                                                                       value="<?php echo $price['id']; ?>"
                                                                       id="price_<?php echo $item['id'].'_'.$price['id']; ?>"
                                                                       style="position: relative; margin: 0;" <?php echo isset($price_ids) && in_array($price['id'], $price_ids) ? "checked" : "" ?>>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            ₹<?php echo nl2br(htmlspecialchars(strip_tags(html_entity_decode($price['price'] ?? '0')))); ?>
                                                        </td>
                                                        <td>
                                                            <?php echo nl2br(htmlspecialchars(strip_tags(html_entity_decode($price['description'] ?? '')))); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Accessories -->
                                    <div class="mb-2">
                                        <strong>Accessories:</strong>
                                        <?php
                                        $stmt_accessories = $conn->prepare("SELECT * FROM quote_item_accessories WHERE quote_item_id = ?");
                                        $stmt_accessories->bind_param("i", $item['id']);
                                        $stmt_accessories->execute();
                                        $stmt_accessories->store_result();
                                        $stmt_accessories->bind_result($id, $quote_item_id, $accessory_name, $accessory_value);
                                        $accessories = [];
                                        while($stmt_accessories->fetch()) {
                                            $accessories[] = [
                                                'id' => $id,
                                                'quote_item_id' => $quote_item_id,
                                                'accessory_name' => $accessory_name,
                                                'accessory_value' => $accessory_value
                                            ];
                                        }
                                        $stmt_accessories->close();

                                        if($accessories): ?>
                                            <table class="table table-sm table-bordered w-auto mb-2 ml-3">
                                                <thead>
                                                <tr>
                                                    <th>Select</th>
                                                    <th>Name</th>
                                                    <th>Price</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach($accessories as $acc): ?>
                                                    <tr>
                                                        <td class="text-center">
                                                            <div class="form-check d-flex justify-content-center">
                                                                <input class="form-check-input" type="checkbox"
                                                                       name="accessories[<?php echo $item['id']; ?>][]"
                                                                       value="<?php echo $acc['id']; ?>"
                                                                       id="accessory_<?php echo $item['id'].'_'.$acc['id']; ?>"
                                                                       style="position: relative; margin: 0;" <?php echo isset($accessory_ids) && in_array($acc['id'], $accessory_ids) ? "checked" : "" ?>>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php echo nl2br(htmlspecialchars(strip_tags(html_entity_decode($acc['accessory_name'] ?? '')))); ?>
                                                        </td>
                                                        <td>
                                                            ₹<?php echo nl2br(htmlspecialchars(strip_tags(html_entity_decode($acc['accessory_value'] ?? '0')))); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary"><?php echo isset($id) ? "Update Quotation" : "Generate Quotation"; ?></button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Stop checkbox click from triggering collapse
    $('.item-master-checkbox').on('click', function(e) {
        e.stopPropagation();
    });

    // Handle master checkbox for each item
    $(document).on('change', '.item-master-checkbox', function() {
        var $card = $(this).closest('.card');
        var isChecked = $(this).prop('checked');

        // Check/uncheck all child checkboxes (prices and accessories)
        $card.find('.form-check-input').not('.item-master-checkbox').prop('checked', isChecked);
    });

    // Auto-check master checkbox if any child is checked
    $(document).on('change', '.form-check-input:not(.item-master-checkbox)', function() {
        var $card = $(this).closest('.card');
        var $masterCheckbox = $card.find('.item-master-checkbox');

        // Check if any child checkboxes are checked
        var anyChecked = $card.find('.form-check-input:not(.item-master-checkbox):checked').length > 0;

        // Update master checkbox
        $masterCheckbox.prop('checked', anyChecked);
    });

    $('#generate-quote-form').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '../classes/Master.php?f=save_quotation',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                if(resp.status == 'success') {
                    location.href = `./?page=quotations/view_quote&id=${resp.quotation_id}`;
                } else {
                    alert(resp.msg || 'An error occurred');
                }
            },
            error: function(xhr, status, error) {
                console.log("Error:", error);
            }
        });
    });
});
</script>