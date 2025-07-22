<?php require_once('./../../config.php') ?>
<?php 
 $qry = $conn->query("SELECT * FROM `supplier_list` where id = '{$_GET['id']}' ");
 if($qry->num_rows > 0){
     foreach($qry->fetch_assoc() as $k => $v){
         $$k=$v;
     }
 }
?>
   <style>
    #uni_modal .modal-footer{
        display:none;
    }
</style> 
<div class="container-fluid" id="print_out">
    <div id='transaction-printable-details' class='position-relative'>
        <div class="row">
            <fieldset class="w-100">
                <div class="col-12">
                    
                    <dl>
                        <dt class="text-info">Name:</dt>
                        <dd class="pl-3"><?php echo $name ?></dd>
                        <dt class="text-info">Address:</dt>
                        <dd class="pl-3"><?php echo isset($address) ? $address : '' ?></dd>
                        <?php if(!empty($cperson)): ?>
                        <dt class="text-info">Contact Person (Sales):</dt>
                        <dd class="pl-3"><?php echo $cperson ?></dd>
                        <?php endif; ?>
                        <?php if(!empty($contact)): ?>
                        <dt class="text-info">Contact # (Sales):</dt>
                        <dd class="pl-3"><?php echo $contact ?></dd>
                        <?php endif; ?>
                        <?php if(!empty($cperson_acc)): ?>
                        <dt class="text-info">Contact Person (Accounts):</dt>
                        <dd class="pl-3"><?php echo $cperson_acc ?></dd>
                        <?php endif; ?>
                        <?php if(!empty($contact_acc)): ?>
                        <dt class="text-info">Contact # (Accounts):</dt>
                        <dd class="pl-3"><?php echo $contact_acc ?></dd>
                        <?php endif; ?>
                        <dt class="text-info">Category:</dt>
                        <dd class="pl-3"><?php echo isset($category) ? $category : '' ?></dd>
                        <dt class="text-info">Sub-Category:</dt>
                        <dd class="pl-3"><?php echo isset($subcategory) ? $subcategory : '' ?></dd>
                        <dt class="text-info">GST No.:</dt>
                        <dd class="pl-3"><?php echo isset($gst_number) ? $gst_number : '' ?></dd>
                        <dt class="text-info">Email ID:</dt>
                        <dd class="pl-3"><?php echo isset($email) ? $email : '' ?></dd>
                        <dt class="text-info">Rating:</dt>
                        <dd class="pl-3"><?php echo isset($rating) ? $rating : '' ?></dd>
                        <dt class="text-info">Status:</dt>
                        <dd class="pl-3">
                            <?php if($status == 1): ?>
                                <span class="badge badge-success rounded-pill">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger rounded-pill">Inactive</span>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
            </fieldset>
        </div>
    </div>
</div>
<div class="form-group">
    <div class="col-12">
        <div class="d-flex justify-content-end align-items-center">
            <button class="btn btn-dark btn-flat" type="button" id="cancel" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>
    

<script>
    $(function(){
        $('.table td,.table th').addClass('py-1 px-2 align-middle')
    })
</script>
