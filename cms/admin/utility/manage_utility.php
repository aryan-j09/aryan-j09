<?php
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `utility_supplier_list` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>

<div class="container-fluid">
    <form action="" id="utility-supplier-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : ''; ?>">
        <div class="form-group">
            <label for="name" class="control-label">Name</label>
            <input name="name" id="name" class="form-control rounded-0" value="<?php echo isset($name) ? $name : ''; ?>">
        </div>
        <div class="form-group">
            <label for="address" class="control-label">Address</label>
            <textarea name="address" id="address" cols="30" rows="2" class="form-control form no-resize"><?php echo isset($address) ? $address : ''; ?></textarea>
        </div>
        <div class="form-group">
            <label for="contact_number" class="control-label">Contact Number</label>
            <input name="contact_number" id="contact_number" class="form-control rounded-0" value="<?php echo isset($contact_number) ? $contact_number : ''; ?>">
        </div>
        <div class="form-group">
            <label for="contact_person" class="control-label">Contact Person</label>
            <input name="contact_person" id="contact_person" class="form-control rounded-0" value="<?php echo isset($contact_person) ? $contact_person : ''; ?>">
        </div>
        <div class="form-group">
            <label for="gst_number" class="control-label">GST Number</label>
            <input name="gst_number" id="gst_number" class="form-control rounded-0" value="<?php echo isset($gst_number) ? $gst_number : ''; ?>">
        </div>
        <div class="form-group">
            <label for="category" class="control-label">Category</label>
            <input name="category" id="category" class="form-control rounded-0" value="<?php echo isset($category) ? $category : ''; ?>">
        </div>
        <div class="form-group">
            <label for="email" class="control-label">Email</label>
            <input type="email" name="email" id="email" class="form-control rounded-0" value="<?php echo isset($email) ? $email : ''; ?>">
        </div>
    </form>
</div>

<script>
    $(document).ready(function(){
        $('#utility-supplier-form').submit(function(e){
            e.preventDefault();
            var _this = $(this);
            $('.err-msg').remove();
            
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_utility_supplier",
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                error: err=>{
                    console.log(err);
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                },
                success:function(resp){
                    if(typeof resp == 'object' && resp.status == 'success'){
                        location.href = _base_url_ + "admin/?page=utility";
                    }else if(resp.status == 'failed' && !!resp.msg){
                        var el = $('<div>')
                            el.addClass("alert alert-danger err-msg").text(resp.msg)
                            _this.prepend(el)
                            el.show('slow')
                    }else{
                        alert_toast("An error occurred.", 'error');
                        end_loader();
                        console.log(resp)
                    }
                }
            })
        })
    })
</script>
