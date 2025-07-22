<?php
if(isset($_GET['id']) && !empty($_GET['id'])){
    $qry = $conn->query("SELECT * FROM leads where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k = $v;
        }
    }
}
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><?php echo isset($id) ? "Update Lead - ".$company_name : "New Lead" ?></h3>
        <div class="card-tools">
            <a href="./?page=leads" class="btn btn-flat btn-danger"><span class="fas fa-arrow-left"></span> Back</a>
        </div>
    </div>
    <div class="card-body">
        <form id="lead-form">
            <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
            <div class="row">
                <div class="form-group col-md-4">
                    <label>Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" required value="<?php echo isset($company_name) ? $company_name : '' ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Contact Person <span class="text-danger">*</span></label>
                    <input type="text" name="contact_person" class="form-control" required value="<?php echo isset($contact_person) ? $contact_person : '' ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>City <span class="text-danger">*</span></label>
                    <input type="text" name="city" class="form-control" required value="<?php echo isset($city) ? $city : '' ?>">
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo isset($email) ? $email : '' ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo isset($phone) ? $phone : '' ?>">
                </div>
            </div>
            <div class="row">
                <div class="form-group col-md-6">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="new" <?php echo isset($status) && $status == 'new' ? 'selected' : '' ?>>New</option>
                        <option value="contacted" <?php echo isset($status) && $status == 'contacted' ? 'selected' : '' ?>>Contacted</option>
                        <option value="negotiation" <?php echo isset($status) && $status == 'negotiation' ? 'selected' : '' ?>>Negotiation</option>
                        <option value="converted" <?php echo isset($status) && $status == 'converted' ? 'selected' : '' ?>>Converted</option>
                        <option value="closed" <?php echo isset($status) && $status == 'closed' ? 'selected' : '' ?>>Closed</option>
                        <option value="lost" <?php echo isset($status) && $status == 'lost' ? 'selected' : '' ?>>Lost</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Source</label>
                    <input type="text" name="source" class="form-control" value="<?php echo isset($source) ? $source : '' ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3" class="form-control"><?php echo isset($address) ? $address : '' ?></textarea>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="3" class="form-control"><?php echo isset($notes) ? $notes : '' ?></textarea>
            </div>
        </form>
    </div>
    <div class="card-footer text-center">
        <button class="btn btn-primary mr-2" form="lead-form">Save</button>
        <a class="btn btn-default" href="./?page=leads">Cancel</a>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#lead-form').submit(function(e){
        e.preventDefault();
        start_loader();
        $.ajax({
            url: _base_url_+"classes/Master.php?f=save_lead",
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            dataType: 'json',
            error:err=>{
                console.log(err);
                alert_toast("An error occurred",'error');
                end_loader();
            },
            success:function(resp){
                if(resp.status == 'success'){
                    location.href = "./?page=leads/view_lead&id=" + resp.id;
                }else{
                    alert_toast(resp.msg,'error');
                }
                end_loader();
            }
        });
    });
});
</script>