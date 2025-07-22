<?php 
require_once '/home/vol1_8/infinityfree.com/if0_37987606/htdocs/config.php';

if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `machine_list` where id = ".$_GET['id']);
    foreach($qry->fetch_array() as $k => $v){
        $$k = $v;
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
                        <dt class="text-info">Item Name:</dt>
                        <dd class="pl-3"><?php echo $name ?></dd>
                        <dt class="text-info">Item Type:</dt>
                        <dd class="pl-3"><?php echo $item_type ?></dd>
                        <dt class="text-info">Description:</dt>
                        <dd class="pl-3"><?php echo isset($description) ? $description : '' ?></dd>
                        <dt class="text-info">Cost:</dt>
                        <dd class="pl-3"><?php echo isset($cost) ? number_format($cost,2) : '' ?></dd>
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
            <button class="btn btn-secondary" type="button" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>

<script>
    $(function(){
        $('.table td,.table th').addClass('py-1 px-2 align-middle')
    })
</script>
