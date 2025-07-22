<?php 

$qry = $conn->query("SELECT p.*, s.name as supplier, s.address as supplier_address, s.contact as supplier_contact, s.email as supplier_email, s.cperson as supplier_cperson, s.gst_number as supplier_gstin FROM `purchase_order_list` p inner join `supplier_list` s on p.supplier_id = s.id WHERE p.id = '{$_GET['id']}'");
if($qry->num_rows > 0){
    foreach($qry->fetch_array() as $k => $v){
        $$k = $v;
    }
}

$qry = $conn->query("SELECT * FROM receiving_list where id = '{$_GET['id']}'");
if($qry->num_rows >0){
    foreach($qry->fetch_array() as $k => $v){
        $$k = $v;
    }
    if($from_order == 1){
        $po_qry = $conn->query("SELECT p.*, s.name as supplier, s.address as supplier_address, s.contact as supplier_contact, s.email as supplier_email, s.cperson as supplier_cperson, s.gst_number as supplier_gstin FROM `purchase_order_list` p inner join `supplier_list` s on p.supplier_id = s.id where p.id= '{$form_id}' ");
        if($po_qry->num_rows >0){
            foreach($po_qry->fetch_array() as $k => $v){
                if(!isset($$k))
                $$k = $v;
            }
        }
    }else{
        $qry = $conn->query("SELECT b.*, s.name as supplier, s.address as supplier_address, s.contact as supplier_contact, s.email as supplier_email, s.cperson as supplier_cperson, s.gst_number as supplier_gstin, p.po_code FROM back_order_list b inner join supplier_list s on b.supplier_id = s.id inner join purchase_order_list p on b.po_id = p.id  where b.id = '{$form_id}'");
            if($qry->num_rows >0){
                foreach($qry->fetch_array() as $k => $v){
                    if($k == 'id')
                    $k = 'bo_id';
                    if(!isset($$k))
                    $$k = $v;
                }
            }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receiving Details</title>
    <style>
        .center-text {
            text-align: center;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-container .date {
            text-align: right;
        }
        .table-sm td, .table-sm th {
            padding: .3rem;
        }
        .form-control-sm {
            height: calc(1.5em + .5rem + 2px);
            padding: .25rem .5rem;
            font-size: .875rem;
            line-height: 1.5;
            border-radius: .2rem;
        }
        @media print {
            .print-header {
                text-align: center;
                margin-bottom: 20px;
            }
            .print-header h1 {
                font: 40pt 'Monotype Corsiva';                
                margin: 0;
            }
            .print-header p {
                font: 12pt 'Cambria';
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .print-address {
                display: block !important;
                white-space: pre-wrap;
            }

            .term-cond {
                font-size: 11pt;
                font-weight: bold;
            }

        .italic{
            font-style: italic;
        }
        }        
    </style>

<!-- Address -->
<style>
    .d-flex {
        display: flex;
    }
    .justify-content-start {
        justify-content: flex-start;
    }
    .address-container {
        gap: 20px; /* Add some space between the address blocks */
    }
    .address-block {
        width: 45%; /* Adjust the width as needed */
    }
    .print-address {
        display: block;
    }
    @media print {
        .no-print {
            display: none !important;
        }
        .print-address {
            display: block !important;
            white-space: pre-wrap;
        }
        .d-flex {
            display: flex;
        }
        .justify-content-start {
            justify-content: flex-start;
        }
        .address-container {
            gap: 20px; /* Add some space between the address blocks */
        }
        .address-block {
            width: 45%; /* Adjust the width as needed */
        }
    }
</style>

</head>
<body>
    <div class="print-header center-text">
        <h1>Hugopharm Technologies Pvt. Ltd.</h1>
        <p style="margin: 0; padding: 0;">Regd Office : 8, Jogani Industrial Estate, 541 Senapati Bapat Marg, Dadar (W), Mumbai 400028</p>
        <p style="margin: 0; padding: 0;">Tel : 9869415083 Email : purchase.sbpanchal@gmail.com</p>
        <p>Works : Plot No TS 20, MIDC Phase 2, Sagaon, Manpada Road, Dombivli (E) 421203</p>
    </div>
    <div class="card card-outline card-primary">
        <div class="card-header">
            <div class="header-container">
                <div>
                    <label class="control-label text-info">Purchase Order No.</label> <?php echo $po_code?>                    
                </div>
                <div class="date">
                    <label class="control-label text-info">Date: </label> <?php echo date('d-m-Y'); ?>
                </div>
                <div>
                    <label class="control-label text-info">Internal Ref No:</label> <?php echo isset($internal_ref_no) ? $internal_ref_no : '' ?>                   
                </div>
            </div>
        </div>
        <div class="card-body" id="print_out">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <form action="" method="post">
                            <table class="table table-bordered table-sm">
                                <tbody>
                                    <tr>
                                        <td><strong>Vendor Name:</strong></td>
                                        <td><?php echo isset($supplier) ? $supplier : '' ?></td>                                        
                                    </tr>
                                    <tr>
                                        <td><strong>Address:</strong></td>
                                        <td><?php echo isset($supplier_address) ? $supplier_address : '' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Contact:</strong></td>
                                        <td><?php echo isset($supplier_contact) ? $supplier_contact : '' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo isset($supplier_email) ? $supplier_email : '' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Contact Person:</strong></td>
                                        <td><?php echo isset($supplier_cperson) ? $supplier_cperson : '' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>GST In:</strong></td>
                                        <td><?php echo isset($supplier_gstin) ? $supplier_gstin : '' ?></td>
                                    </tr>                                    
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
                <label>Dear Sir/ Madam,</label>
                <p>We are glad to place an order for the following:</p>
                <table class="table table-striped table-bordered table-sm" id="list">
                    <colgroup>
                        <col width="10%">
                        <col width="10%">
                        <col width="30%">
                        <col width="25%">
                        <col width="25%">
                    </colgroup>
                    <thead>
                        <tr class="text-light bg-navy">
                            <th class="text-center py-1 px-2">Qty</th>
                            <th class="text-center py-1 px-2">Unit</th>
                            <th class="text-center py-1 px-2">Item</th>
                            <th class="text-center py-1 px-2">Cost</th>
                            <th class="text-center py-1 px-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        $qry = $conn->query("SELECT s.*,i.name,i.description FROM `stock_list` s inner join item_list i on s.item_id = i.id where s.id in ({$stock_ids})");
                        while($row = $qry->fetch_assoc()):
                            $total += $row['total']
                        ?>
                        <tr>
                            <td class="py-1 px-2 text-center"><?php echo number_format($row['quantity'],2) ?></td>
                            <td class="py-1 px-2 text-center"><?php echo ($row['unit']) ?></td>
                            <td class="py-1 px-2">
                                <?php echo $row['name'] ?> <br>
                                <?php echo $row['description'] ?>
                            </td>
                            <td class="py-1 px-2 text-right"><?php echo number_format($row['price']) ?></td>
                            <td class="py-1 px-2 text-right"><?php echo number_format($row['total']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-right py-1 px-2" colspan="4">Sub Total</th>
                            <th class="text-right py-1 px-2 sub-total"><?php echo number_format($total,2)  ?></th>
                        </tr>
                        <tr>
                            <th class="text-right py-1 px-2" colspan="4">Discount <?php echo isset($discount_perc) ? $discount_perc : 0 ?>%</th>
                            <th class="text-right py-1 px-2 discount"><?php echo isset($discount) ? number_format($discount,2) : 0 ?></th>
                        </tr>
                        <tr>
                            <th class="text-right py-1 px-2" colspan="4">Tax <?php echo isset($tax_perc) ? $tax_perc : 0 ?>%</th>
                            <th class="text-right py-1 px-2 tax"><?php echo isset($tax) ? number_format($tax,2) : 0 ?></th>
                        </tr>
                        <tr>
                            <th class="text-right py-1 px-2" colspan="4">Total</th>
                            <th class="text-right py-1 px-2 grand-total"><?php echo isset($amount) ? number_format($amount,2) : 0 ?></th>
                        </tr>
                    </tfoot>
                </table>
                

<!-- New Field and Dropdown for Bill Delivery -->
<div class="form-group mt-3">
    <div class="d-flex justify-content-start address-container">
        <div class="address-block">
            <label for="bill_delivery" class="control-label">Generate bill with the name of and material delivery to:</label>
            <p id="bill_delivery">
                Hugopharm Technologies Pvt. Ltd<br>
                Plot No. TS 20, MIDC Phase 2,<br>
                Man pada Road, Sagaon,<br>
                Besides Sekhsaria Chemicals/ Arch Pharma<br>
                Dombivli (E) - 421 203<br>
                GSTIN/UIN : 27AACCH1711N1ZM
            </p>
            <!-- Hidden div to display the selected address for bill delivery printing -->
            <div id="print_address_bill" class="print-address"></div>
        </div>
        <div class="address-block">
            <label for="invoice_delivery" class="control-label">Invoice delivery to:</label>
            <select id="invoice_delivery" name="invoice_delivery" class="no-print" onchange="updatePrintAddress('invoice_delivery', 'print_address_invoice')" required>
                <option value="" disabled selected>Select an option</option>
                <option value="Hugopharm Technologies Pvt. Ltd\nPlot No. TS 20, MIDC Phase 2,\nMan pada Road, Sagaon,\nBesides Sekhsaria Chemicals/ Arch Pharma\nDombivli (E) - 421 203\nGSTIN/UIN : 27AACCH1711N1ZM">Hugopharm Technologies Pvt. Ltd(DOM)</option>
                <option value="S.B. Panchal & Company\n8, Jogani Industrial Estate,\n541 Senapati Bapat Marg,\nDadar (W), Mumbai 400 028\nGSTIN/UIN : 27AAAFS5950K1ZW">Hugopharm Technologies Pvt. Ltd(DDR)</option>
            </select>
            <!-- Hidden div to display the selected address for invoice delivery printing -->
            <div id="print_address_invoice" class="print-address"></div>
        </div>
    </div>
</div>

           

<label>Terms & Conditions:</label>
<p>1) Tax :-  GST As Applicable <br>
2) Payment :- 
    <select id="payment" name="payment" class="form-control-sm no-print" onchange="updatePrintValue('payment', 'print_payment')" required>
        <option value="Against Delivery" selected>Against Delivery</option>        
        <option value="Net 30">Net 30days</option>
        <option value="Net 45">Net 45days</option>
        <option value="Net 60">Net 60days</option>
        <option value="30/70">30% advance 70% against delivery</option>
        <option value="50/50">50% advance 50% against delivery</option>
        <option value="100/PI">100% Advance against PI</option>
    </select>
    <span id="print_payment" class="print-value">Against Delivery</span>
<br>
3) Delivery :- Vendor Scope <br>
4) Delivery period :- 
    <select id="delivery_period" name="delivery_period" class="form-control-sm no-print" onchange="updatePrintValue('delivery_period', 'print_delivery_period')" required>
        <option value="2-4 Weeks" selected>2-4 Weeks</option>
        <option value="4-6 Weeks">4-6 Weeks</option>
        <option value="6-8 Weeks">6-8 Weeks</option>
        <option value="8-10 Weeks">8-10 Weeks</option>
        <option value="10-15 Weeks">10-15 Weeks</option>
        <option value="15-20 Weeks">15-20 Weeks</option>
        <option value="20-25 Weeks">20-25Weeks</option>
        <option value="Immediate">Immediate</option>
    </select>
    <span id="print_delivery_period" class="print-value">2-4 Weeks</span>
<br>
5) Warranty :- 1 year against manufacturing defect</p>

<h5 class="term-cond">Scanned Copies of All Certificates to be email to purchase.sbpanchal@gmail.com<br>
    Hard Copies of All Calibration Certificates to be delivered to billing address</h5>

<p>Yours truly,<br>
    <label>For</label> Hugopharm Technologies Pvt. Ltd.</p>
    <p class="italic">Hiren Panchal</p>

    <div class="col-md-6">
             <div class="form-group no-print">
                <label for="remarks" class="text-info control-label">Remarks</label>
                <p><?php echo isset($remarks) ? $remarks : '' ?></p>
            </div>
        </div>

<div class="card-footer py-1 text-center no-print">
    <button class="btn btn-flat btn-success" type="button" id="print">Print</button>
    <a class="btn btn-flat btn-primary" href="<?php echo base_url.'/admin?page=receiving/manage_receiving&id='.(isset($id) ? $id : '') ?>">Edit</a>
    <a class="btn btn-flat btn-dark" href="<?php echo base_url.'/admin?page=receiving' ?>">Back To List</a>
</div>



<script>
function updatePrintAddress(dropdownId, printDivId) {
    var selectedOption = document.getElementById(dropdownId).value;
    document.getElementById(printDivId).innerText = selectedOption.replace(/\\n/g, '\n');
}

function updatePrintValue(dropdownId, printSpanId) {
    var selectedOption = document.getElementById(dropdownId).value;
    document.getElementById(printSpanId).innerText = selectedOption;
}

document.getElementById('print').addEventListener('click', function() {
    var billDelivery = document.getElementById('bill_delivery').value;
    var invoiceDelivery = document.getElementById('invoice_delivery').value;

    if (!billDelivery || !invoiceDelivery) {
        alert('Please select an option for both dropdowns before printing.');
        return;
    }

    window.print();
});

// Initialize the print values with the default selections
document.addEventListener('DOMContentLoaded', function() {
    updatePrintValue('payment', 'print_payment');
    updatePrintValue('delivery_period', 'print_delivery_period');
});
</script>
</body>
</html>