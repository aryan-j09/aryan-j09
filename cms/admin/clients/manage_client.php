<?php 
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM clients where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_array() as $k => $v){
            $$k = $v;
        }
    }
}
if(isset($_GET['convert_from_lead'])) {
    $lead_id = $_GET['convert_from_lead'];
    $lead = $conn->query("SELECT * FROM leads WHERE id = '{$lead_id}'");
    if($lead->num_rows > 0) {
        $lead_data = $lead->fetch_assoc();
        // Pre-fill form with lead data
        $company_name = $lead_data['company_name'];
        $email = $lead_data['email'];
        $contact_person = $lead_data['contact_person'];
        $contact_no = $lead_data['phone'];
        $shipping_address = $lead_data['address'];
        $billing_address = $lead_data['address'];
        
        // Store lead_id for later use
        echo "<input type='hidden' name='converted_from_lead' value='{$lead_id}'>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Client</title>
    <script>
        var base_url = "<?php echo base_url; ?>";
    </script>
</head>
<body>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><?php echo isset($id) ? "Update" : "Create New" ?> Client</h3>
    </div>
    <div class="card-body">
        <form action="" id="client-form">
            <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
            <div class="row">
            <div class="form-group col-4">
                <label for="company_name" class="control-label">Company Name</label>
                <input type="text" name="company_name" id="company_name" class="form-control form-control-sm" value="<?php echo isset($company_name) ? $company_name : '' ?>" required>
            </div>
            <div class="form-group col-4">
                <label for="email" class="control-label">Email</label>
                <input type="email" name="email" id="email" class="form-control form-control-sm" value="<?php echo isset($email) ? $email : '' ?>" >
            </div>
            <div class="form-group col-4">
                <label for="gst_number" class="control-label">GST Number</label>
                <input type="text" name="gst_number" id="gst_number" class="form-control form-control-sm" value="<?php echo isset($gst_number) ? $gst_number : '' ?>">
            </div>
            <div class="form-group col-6">
                <label for="billing_address" class="control-label">Billing Address</label>
                <textarea name="billing_address" id="billing_address" class="form-control form-control-sm mt-2" required><?php echo isset($billing_address) ? $billing_address : '' ?></textarea>
            </div>         
            <div class="form-group col-6">
                <label for="shipping_address" class="control-label">Shipping Address 
                    <input type="checkbox" id="same_as_billing" onclick="copyBillingAddress()">
                    <label for="same_as_billing">Same as Billing address</label></label>
                <textarea name="shipping_address" id="shipping_address" class="form-control form-control-sm" ><?php echo isset($shipping_address) ? $shipping_address : '' ?></textarea>
            </div>         
            <div class="form-group col-2">
                <label for="contact_person" class="control-label">Contact Person End User</label>
                <input type="text" name="contact_person" id="contact_person" class="form-control form-control-sm" value="<?php echo isset($contact_person) ? $contact_person : '' ?>" >
            </div>
            <div class="form-group col-2 border-right">
                <label for="contact_no" class="control-label">Contact No</label>
                <input type="text" name="contact_no" id="contact_no" class="form-control form-control-sm" value="<?php echo isset($contact_no) ? $contact_no : '' ?>">
            </div>
            <div class="form-group col-2">
                <label for="cperson_acc" class="control-label">Contact Person Accounts</label>
                <input type="text" name="cperson_acc" id="cperson_acc" class="form-control form-control-sm" value="<?php echo isset($cperson_acc) ? $cperson_acc : '' ?>" >
            </div>
            <div class="form-group col-2 border-right">
                <label for="cperson_no_acc" class="control-label">Contact No</label>
                <input type="text" name="cperson_no_acc" id="cperson_no_acc" class="form-control form-control-sm" value="<?php echo isset($cperson_no_acc) ? $cperson_no_acc : '' ?>">
            </div>
            <div class="form-group col-2">
                <label for="cperson_pur" class="control-label">Contact Person Purchase</label>
                <input type="text" name="cperson_pur" id="cperson_pur" class="form-control form-control-sm" value="<?php echo isset($cperson_pur) ? $cperson_pur : '' ?>" >
            </div>
            <div class="form-group col-2">
                <label for="cperson_no_pur" class="control-label">Contact No</label>
                <input type="text" name="cperson_no_pur" id="cperson_no_pur" class="form-control form-control-sm" value="<?php echo isset($cperson_no_pur) ? $cperson_no_pur : '' ?>">
            </div>            
</div>
        </form>
    </div>
    <div class="card-footer">
        <button class="btn btn-flat btn-primary" form="client-form">Save</button>
        <a class="btn btn-flat btn-default" href="<?php echo base_url.'/admin?page=clients' ?>">Cancel</a>
    </div>
</div>




<script>
    

    function copyBillingAddress() {
        if (document.getElementById('same_as_billing').checked) {
            document.getElementById('shipping_address').value = document.getElementById('billing_address').value;
        } else {
            document.getElementById('shipping_address').value = '';
        }
    }

    $(document).ready(function(){
        $('#client-form').submit(function(e){
            e.preventDefault();
            
            // Basic validation
            var phoneRegex = /^\d{10}$/;
            var phones = {
                'contact_no': $('#contact_no').val(),
                'cperson_no_acc': $('#cperson_no_acc').val(),
                'cperson_no_pur': $('#cperson_no_pur').val()
            };

            // Check phone numbers only if they are not empty
            for(var key in phones) {
                if(phones[key] !== '' && !phoneRegex.test(phones[key])) {
                    alert_toast("Please enter valid 10-digit phone numbers", 'error');
                    end_loader();
                    return false;
                }
            }

            start_loader();
            $.ajax({
                url: base_url + "classes/Master.php?f=save_client",
                method: "POST",
                data: $(this).serialize(),
                dataType: "json",
                error: err => {
                    console.log(err);
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                },
                success: function(resp){
                    console.log(resp); // Add this line to debug the response
                    if(typeof resp == 'object' && resp.status == 'success'){
                        location.href = base_url + "admin?page=clients";
                    } else if (resp.status == 'failed' && resp.msg) {
                        alert_toast(resp.msg, 'error');
                        end_loader();
                    } else {
                        alert_toast("An error occurred.", 'error');
                        end_loader();
                    }
                }
            });
        });
    });
</script>
</body>
</html>