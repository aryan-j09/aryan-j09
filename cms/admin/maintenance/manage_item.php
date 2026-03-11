<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `item_list` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
    $attributes = $conn->query("SELECT * FROM `item_attributes` where `item_id` = '{$_GET['id']}'");
}
?>
<div class="container-fluid">
    <form action="" id="item-form">
        <input type="hidden" name ="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div class="form-group">
            <label for="name" class="control-label">Name</label>
            <input type="text" name="name" id="name" class="form-control rounded-0" value="<?php echo isset($name) ? $name : ''; ?>">
        </div>
        <div class="form-group">
            <label for="description" class="control-label">Description</label>
            <textarea name="description" id="description" cols="30" rows="2" class="form-control form no-resize"><?php echo isset($description) ? $description : ''; ?></textarea>
        </div>
        <div class="form-group">
            <label for="cost" class="control-label">Cost</label>
            <input type="number" name="cost" id="cost" step="any" class="form-control rounded-0 text-end" value="<?php echo isset($cost) ? $cost : ''; ?>">
        </div>
        <div class="form-group">
            <label for="supplier_id" class="control-label">Supplier</label>
            <select name="supplier_id" id="supplier_id" class="custom-select select2" required>
            <option value="" <?php echo !isset($supplier_id) ? 'selected' : '' ?> disabled>Select a supplier</option>
            <?php 
            $supplier = $conn->query("SELECT * FROM `supplier_list` where status = 1 order by `name` asc");
            while($row=$supplier->fetch_assoc()):
            ?>
            <option value="<?php echo $row['id'] ?>" <?php echo isset($supplier_id) && $supplier_id == $row['id'] ? "selected" : "" ?> ><?php echo $row['name'] ?></option>
            <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="status" class="control-label">Status</label>
            <select name="status" id="status" class="custom-select selevt">
            <option value="1" <?php echo isset($status) && $status == 1 ? 'selected' : '' ?>>Active</option>
            <option value="0" <?php echo isset($status) && $status == 0 ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="form-group">
            <label class="control-label">Attributes</label>
            <div id="attributes_container">
                <?php if(isset($attributes)): ?>
                    <?php while($attr = $attributes->fetch_assoc()): ?>
                        <div class="attribute_row mb-3">
                            <input type="text" name="attributes[]" placeholder="Attribute Name" class="form-control mb-2" value="<?php echo $attr['attribute'] ?>">
                            <input type="text" name="values[]" placeholder="Attribute Value" class="form-control mb-2" value="<?php echo $attr['value'] ?>">
                            <button type="button" class="btn btn-danger remove_attribute mb-2">Delete</button>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
            <button type="button" id="add_attribute" class="btn btn-secondary mt-3">Add Attribute</button>
        </div>
    </form>
</div>
<script>
    $(document).ready(function(){
        $('.select2').select2({placeholder:"Please Select here",width:"relative"});

        $('#add_attribute').click(function(){
            var newRow = `<div class="attribute_row mb-3">
                <input type="text" name="attributes[]" placeholder="Attribute Name" class="form-control mb-2">
                <input type="text" name="values[]" placeholder="Attribute Value" class="form-control mb-2">
                <button type="button" class="btn btn-danger remove_attribute mb-2">Delete</button>
            </div>`;
            $('#attributes_container').append(newRow);
        });

        // Use event delegation to handle the click event for dynamically added elements
        $(document).on('click', '.remove_attribute', function(){
            $(this).closest('.attribute_row').remove();
        });

        $('#item-form').submit(function(e){
            e.preventDefault();
            var _this = $(this);
            var supplierId = $('#supplier_id').val();
            $('.err-msg').remove();
            if(!supplierId){
                var el = $('<div>');
                el.addClass("alert alert-danger err-msg").text('Please select a supplier.');
                _this.prepend(el);
                el.show('slow');
                return;
            }
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_item",
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: err => {
                    console.log(err);
                    alert_toast("An error occurred", 'error');
                    end_loader();
                },
                success: function(resp){
                    end_loader(); // Always stop loader first
                    
                    if (typeof resp == 'object' && resp.status == 'success') {
                        location.reload();
                    } else if (resp.status == 'failed' && !!resp.msg) {
                        var el = $('<div>');
                        el.addClass("alert alert-danger err-msg").text(resp.msg);
                        _this.prepend(el);
                        el.show('slow');
                        $("html, body").animate({ scrollTop: _this.closest('.card').offset().top }, "fast");
                    } else {
                        alert_toast("An error occurred", 'error');
                        console.log(resp);
                    }
                }
            });
        });
    });
</script>