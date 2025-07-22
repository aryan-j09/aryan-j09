<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `supplier_list` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>

<div class="container-fluid">
    <form action="" id="supplier-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : ''; ?>">
        <div class="form-group">
            <label for="name" class="control-label">Name</label>
            <input name="name" id="name" class="form-control rounded-0" value="<?php echo isset($name) ? $name : ''; ?>">
        </div>
        <div class="form-group">
            <label for="address" class="control-label">Address</label>
            <textarea name="address" id="address" cols="30" rows="2" class="form-control form no-resize"><?php echo isset($address) ? $address : ''; ?></textarea>
        </div>
        <div class="row">
        <div class="col-6 form-group">
            <label for="cperson" class="control-label">C. Person[Sales]</label>
            <input name="cperson" id="cperson" class="form-control rounded-0" value="<?php echo isset($cperson) ? $cperson : ''; ?>">
        </div>
        <div class="col-6 form-group">
            <label for="contact" class="control-label">Contact No.</label>
            <input name="contact" id="contact" class="form-control rounded-0" value="<?php echo isset($contact) ? $contact : ''; ?>">
        </div>
        <div class="col-6 form-group">
            <label for="cperson_acc" class="control-label">C. Person[Accounts]</label>
            <input name="cperson_acc" id="cperson_acc" class="form-control rounded-0" value="<?php echo isset($cperson_acc) ? $cperson_acc : ''; ?>">
        </div>
        <div class="col-6 form-group">
            <label for="contact_acc" class="control-label">Contact No.</label>
            <input name="contact_acc" id="contact_acc" class="form-control rounded-0" value="<?php echo isset($contact_acc) ? $contact_acc : ''; ?>">
        </div>
        </div>
        <div class="form-group">
            <label for="category" class="control-label">Category</label>
            <input name="category" id="category" class="form-control rounded-0" value="<?php echo isset($category) ? $category : ''; ?>">
        </div>
        <div class="form-group">
            <label for="subcategory" class="control-label">Sub-Category</label>
            <input name="subcategory" id="subcategory" class="form-control rounded-0" value="<?php echo isset($subcategory) ? $subcategory : ''; ?>">
        </div>
        <div class="form-group">
            <label for="gst_number" class="control-label">GST No.</label>
            <input name="gst_number" id="gst_number" class="form-control rounded-0" value="<?php echo isset($gst_number) ? $gst_number : ''; ?>">
        </div>
        <div class="form-group">
            <label for="email" class="control-label">Email ID</label>
            <input name="email" id="email" class="form-control rounded-0" value="<?php echo isset($email) ? $email : ''; ?>">
        </div>
        <div class="form-group">
            <label for="rating" class="control-label">Rating</label>
            <select name="rating" id="rating" class="form-control rounded-0">
                <option value="" disabled <?php echo !isset($rating) ? 'selected' : '' ?>>Please select a value</option>
                <option value="1" <?php echo isset($rating) && $rating == 1 ? 'selected' : '' ?>>1</option>
                <option value="2" <?php echo isset($rating) && $rating == 2 ? 'selected' : '' ?>>2</option>
                <option value="3" <?php echo isset($rating) && $rating == 3 ? 'selected' : '' ?>>3</option>
                <option value="4" <?php echo isset($rating) && $rating == 4 ? 'selected' : '' ?>>4</option>
                <option value="5" <?php echo isset($rating) && $rating == 5 ? 'selected' : '' ?>>5</option>
            </select>
        </div>
    </form>
</div>
<script>
    $(document).ready(function(){
        $('#supplier-form').submit(function(e){
            e.preventDefault();
            var _this = $(this);
            $('.err-msg').remove();

            // Validate email
            var email = $('#email').val();
            if (!validateEmail(email)) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Please enter a valid email address.',
                    showConfirmButton: false,
                    timer: 3000
                });
                return false;
            }

            // Validate contact numbers
            var contact = $('#contact').val();
            var contact_acc = $('#contact_acc').val();

            if (contact && !validateContact(contact)) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Please enter a valid sales contact number.',
                    showConfirmButton: false,
                    timer: 3000
                });
                return false;
            }

            if (contact_acc && !validateContact(contact_acc)) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Please enter a valid accounts contact number.',
                    showConfirmButton: false,
                    timer: 3000
                });
                return false;
            }

            // Validate GST number
            var gst_number = $('#gst_number').val();
            if (!validateGST(gst_number)) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Please enter a valid GST number.',
                    showConfirmButton: false,
                    timer: 3000
                });
                return false;
            }

            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_supplier",
                method: "POST",
                data: _this.serialize(),
                dataType: "json",
                error: function(err){
                    console.log(err);
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                },
                success: function(resp){
                    if(typeof resp == 'object' && resp.status == 'success'){
                        location.href = _base_url_ + 'admin/?page=maintenance/supplier';
                    }else if(resp.status == 'failed' && !!resp.msg){
                        alert_toast(resp.msg, 'error');
                    }else{
                        alert_toast("An error occurred.", 'error');
                        console.log(resp);
                    }
                    end_loader();
                }
            });
        });

        // Check for the success message in localStorage after the page reloads
        var successMessage = localStorage.getItem('successMessage');
        if (successMessage) {
            alert_toast(successMessage, 'success');
            localStorage.removeItem('successMessage');
        }
    });

    function validateEmail(email) {
        var re = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        return re.test(email);
    }

    function validateContact(contact) {
        var re = /^[0-9]{10}$/;
        return re.test(contact);
    }

    function validateGST(gst_number) {
        var re = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{3}$/;
        return re.test(gst_number);
    }
</script>