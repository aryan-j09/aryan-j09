<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `utility_supplier_list` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<style>
    #uni_modal .modal-footer{
        display:none
    }
</style>
<div class="container-fluid">
    <dl>
        <dt class="text-muted">Name</dt>
        <dd class="pl-4"><?php echo isset($name) ? $name : "" ?></dd>
        <dt class="text-muted">Address</dt>
        <dd class="pl-4"><?php echo isset($address) ? $address : '' ?></dd>
        <dt class="text-muted">Contact Person</dt>
        <dd class="pl-4"><?php echo isset($contact_person) ? $contact_person : '' ?></dd>
        <dt class="text-muted">Contact Number</dt>
        <dd class="pl-4"><?php echo isset($contact_number) ? $contact_number : '' ?></dd>
        <dt class="text-muted">GST Number</dt>
        <dd class="pl-4"><?php echo isset($gst_number) ? $gst_number : '' ?></dd>
        <dt class="text-muted">Category</dt>
        <dd class="pl-4"><?php echo isset($category) ? $category : '' ?></dd>
        <dt class="text-muted">Email</dt>
        <dd class="pl-4"><?php echo isset($email) ? $email : '' ?></dd>
    </dl>
    <div class="clear-fix my-3"></div>
    <div class="text-right">
        <button class="btn btn-dark btn-sm btn-flat" type="button" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
    </div>
</div>