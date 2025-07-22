<?php
require_once '/home/vol1_8/infinityfree.com/if0_37987606/htdocs/config.php';

if(isset($_GET['id'])){
    $id = $_GET['id'];
    $qry = $conn->query("SELECT * FROM `machine_list` where id = $id ");
    if($qry->num_rows > 0){
        $row = $qry->fetch_assoc();
        foreach($row as $k => $v){
            $$k = $v;
        }
    }
}
?>
<div class="container-fluid">
    <form action="" id="manage-machine-item">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div class="form-group">
            <label for="name" class="control-label">Name</label>
            <input type="text" class="form-control" name="name" value="<?php echo isset($name) ? $name : '' ?>" required>
        </div>
        <div class="form-group">
            <label for="item_type" class="control-label">Item Type</label>
            <select name="item_type" id="item_type" class="custom-select" required>
                <option value="machine" <?php echo isset($item_type) && $item_type == 'machine' ? 'selected' : '' ?>>Machine</option>
                <option value="spare" <?php echo isset($item_type) && $item_type == 'spare' ? 'selected' : '' ?>>Spare</option>
            </select>
        </div>
        <div class="form-group">
            <label for="description" class="control-label">Description</label>
            <textarea name="description" id="description" cols="30" rows="4" class="form-control" required><?php echo isset($description) ? $description : '' ?></textarea>
        </div>
        <div class="form-group">
            <label for="cost" class="control-label">Cost</label>
            <input type="number" class="form-control" name="cost" value="<?php echo isset($cost) ? $cost : '' ?>">
        </div>
        <div class="form-group">
            <label for="status" class="control-label">Status</label>
            <select name="status" id="status" class="custom-select">
                <option value="1" <?php echo isset($status) && $status == 1 ? 'selected' : '' ?>>Active</option>
                <option value="0" <?php echo isset($status) && $status == 0 ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="modal-footer form-group text-right">            
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
    </form>
</div>
<script>
function start_load() {
    $('body').prepend('<div id="preloader2"></div>');
}

function end_load() {
    $('#preloader2').fadeOut('fast', function() {
        $(this).remove();
    });
}

$(document).ready(function(){
    $('#manage-machine-item').submit(function(e){
        e.preventDefault();
        start_load();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_machine_item",
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            success: function(resp){
                end_load();
                if(resp.status == 'success'){                    
                    setTimeout(function(){
                        location.reload();
                    }, );
                } else {
                    alert_toast("An error occurred", 'error');
                }
            },
            error: function(err){
                end_load();
                alert_toast("An error occurred", 'error');
            }
        });
    });
});
</script>