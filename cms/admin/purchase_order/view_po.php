<?php
// DEBUG: Enable error reporting for troubleshooting (remove/comment out in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function number_format_indian($num)
{
    $num = number_format($num, 2, '.', '');
    $x = explode('.', $num);
    $int = $x[0];
    $dec = isset($x[1]) ? $x[1] : '00';
    $last3 = substr($int, -3);
    $rest = substr($int, 0, -3);
    if ($rest != '') {
        $rest = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest);
        $int = $rest . "," . $last3;
    }
    return $int . "." . $dec;
}

$qry = $conn->query("SELECT p.*, s.name as supplier, s.address as supplier_address, s.contact as supplier_contact, 
    s.email as supplier_email, s.cperson as supplier_cperson, s.gst_number as supplier_gstin 
    FROM `purchase_order_list` p 
    inner join `supplier_list` s on p.supplier_id = s.id 
    WHERE p.id = '{$_GET['id']}'");
if (!$qry) {
    die('Query error (purchase_order_list): ' . $conn->error);
}
if ($qry->num_rows > 0) {
    foreach ($qry->fetch_array() as $k => $v) {
        $$k = $v;
    }
}

// Determine company type from DB
$company_type = 'SBP';
if (isset($company)) {
    $c = strtolower($company);
    if (strpos($c, 'hugo') !== false) {
        $company_type = 'HUGO';
    }
}

// Fetch items from po_items table
$item_query = $conn->query("SELECT po_items.*, item_list.name as item_name, item_list.description as item_description 
    FROM po_items 
    JOIN item_list ON po_items.item_id = item_list.id 
    WHERE po_items.po_id = '{$id}'");
if (!$item_query) {
    die('Query error (po_items): ' . $conn->error);
}
$items = [];
while ($row = $item_query->fetch_assoc()) {
    $items[] = $row;
}
?>

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

    .table-sm td,
    .table-sm th {
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

        .italic {
            font-style: italic;
        }

        #authorized_signatory {
            display: none !important;
        }

        #signatory_display {
            display: inline !important;
            font-style: italic;
        }
    }

    .sbp-logo {
        height: auto;
        width: auto;
        max-width: 100%;
        object-fit: contain;
        display: block;
    }

    .header-left {
        height: 100px;
        display: flex;
        align-items: center;
    }

    .hugo-logo {
        height: 130px;
        width: auto;
        max-width: 160px;
        object-fit: contain;
        display: block;
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
        gap: 20px;
        /* Add some space between the address blocks */
    }

    .address-block {
        width: 45%;
        /* Adjust the width as needed */
    }

    .print-address {
        display: block;
    }

    .header-middle {
        text-align: center;
    }

    .header-right {
        text-align: left;
        position: relative;
        padding-left: 20px;
    }

    .header-right::before {
        content: '';
        position: absolute;
        left: 9px;
        top: 0;
        bottom: 0;
        width: 1px;
        border-left: 2px dotted rgb(47, 84, 150);
    }

    .box {
        border: 1px solid #000;
        padding: 3px;
        padding-bottom: 0;
        display: inline-block;
        margin-right: 10px;
        font-style: Arial;
    }

    .print-header {
        color: rgb(47, 84, 150);
        text-align: center;
        margin-bottom: 20px;
    }

    .print-header h1 {
        font: 70px 'Raleway SemiBold';
        margin: 0;
    }

    .print-header p {
        font: 27px 'Garamond';
        margin: 0;
        padding: 0;
    }

    .reduced-spacing {
        margin-top: -15px;
        display: block;
    }

    .signature-block {
        margin-top: -10px;
    }

    .signature-block h5 {
        margin-top: 0;
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
            gap: 20px;
            /* Add some space between the address blocks */
        }

        .address-block {
            width: 45%;
            /* Adjust the width as needed */
        }

        .po-address-block {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        #list {
            page-break-inside: auto;
            break-inside: auto;
        }

        #list tbody {
            page-break-inside: auto;
            break-inside: auto;
        }

        #list tfoot {
            display: table-row-group;
        }

        .print-page-break {
            page-break-before: always;
        }
    }
</style>

<div class="header-container d-flex justify-content-between align-items-center print-header">
    <div class="header-left">
        <?php if ($company_type == 'HUGO'): ?>
            <img src="<?php echo base_url; ?>uploads/HUGO.png" alt="Company Logo" class="company-logo hugo-logo">
        <?php else: ?>
            <img src="<?php echo base_url; ?>uploads/SBLetter.png" alt="Company Logo" class="company-logo sbp-logo">
        <?php endif; ?>
    </div>
    <?php if ($company_type == 'HUGO'): ?>
        <div class="header-middle text-center flex-grow-1">
            <h1 style="font-size:70px">HUGOPHARM</h1>
            <p style="font-size:30px; font-style: italic;">Systems Engineered with Mind and Spirit</p>
        </div>
        <div class="header-right text-left">
            <p style="font-size: 18px;">Regd Office: 8, Jogani Industrial Estate,<br>
                541 Senapati Bapat Marg, Dadar (W), Mumbai 400028<br>
                Mob: 9869415083 Email: sales@sbpanchal.com<br>
                Works: Plot No TS 20, MIDC Phase 2, Sagaon,<br>
                Manpada Road, Dombivli (E) 421203</p>
            GSTIN:27AACCH1711N1ZM
        </div>
    <?php endif; ?>
</div>

<div class="card card-outline card-primary">
    <div class="py-1 text-right no-print">
        <button class="btn btn-flat btn-success" type="button" id="print">Print</button>
        <a class="btn btn-flat btn-primary" href="<?php echo base_url . '/admin?page=purchase_order/manage_po&id=' . (isset($id) ? $id : '') ?>">Edit</a>
        <a class="btn btn-flat btn-dark" href="<?php echo base_url . '/admin?page=purchase_order' ?>">Back To List</a>
    </div>
    <div class="card-header">
        <div class="header-container">
            <div>
                <label class="control-label text-info">Purchase Order No: </label> <?php echo $po_code ?>
            </div>
            <div>
                <label class="control-label text-info">Internal Ref No:</label> <?php echo isset($internal_ref_no) ? $internal_ref_no : '' ?>
            </div>
            <div class="date">
                <label class="control-label text-info">Date: </label> <?php echo date("d-M-Y", strtotime($created_at)) ?>
            </div>
        </div>
    </div>
    <div class="card-body" id="print_out">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <form action="" method="post">
                        <h5><strong>To,</strong></h5>
                        <table class="table table-bordered table-sm">
                            <tbody>
                                <?php if (isset($supplier) && !empty($supplier)): ?>
                                    <tr>
                                        <td><strong>Vendor Name:</strong></td>
                                        <td><?php echo $supplier ?></td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (isset($supplier_address) && !empty($supplier_address)): ?>
                                    <tr>
                                        <td><strong>Address:</strong></td>
                                        <td><?php echo $supplier_address ?></td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (isset($supplier_contact) && !empty($supplier_contact)): ?>
                                    <tr>
                                        <td><strong>Contact:</strong></td>
                                        <td><?php echo $supplier_contact ?></td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (isset($supplier_email) && !empty($supplier_email)): ?>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo $supplier_email ?></td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (isset($supplier_cperson) && !empty($supplier_cperson)): ?>
                                    <tr>
                                        <td><strong>Contact Person:</strong></td>
                                        <td><?php echo $supplier_cperson ?></td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (isset($supplier_gstin) && !empty($supplier_gstin)): ?>
                                    <tr>
                                        <td><strong>GST In:</strong></td>
                                        <td><?php echo $supplier_gstin ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
            <label>Dear Sir/ Madam,</label>
            <p>We are glad to place an order for the following:</p>
            <table class="table table-striped table-bordered table-sm" id="list">
                <colgroup>
                    <col width="5%">
                    <col width="50%">
                    <col width="13.5%">
                    <col width="10%">
                    <col width="8%">
                    <col width="18.5%">
                </colgroup>
                <thead>
                    <tr class="text-light bg-navy">
                        <th class="text-center py-1 px-2">Sr.</th>
                        <th class="text-center py-1 px-2">Item</th>
                        <th class="text-center py-1 px-2">Price</th>
                        <th class="text-center py-1 px-2">Qty.</th>
                        <th class="text-center py-1 px-2">Discount</th>
                        <th class="text-center py-1 px-2">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($items) > 0): ?>
                        <?php foreach ($items as $index => $item): ?>
                            <?php
                            // Fetch item details from item_list table
                            $itemId = $item['item_id'];
                            $query = "SELECT name, description FROM item_list WHERE id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $itemId);
                            $stmt->execute();
                            $stmt->bind_result($name, $description);
                            $stmt->fetch();
                            $itemDetails = ['name' => $name, 'description' => $description];

                            // Calculate the discount amount for a single item
                            $per_item_discount_amount = ($item['amount'] * $item['discount']) / 100;

                            // Calculate the final price per item after discount
                            $discounted_price_per_item = $item['amount'] - $per_item_discount_amount;
                            $stmt->close();
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $index + 1; ?>.</td>
                                <td class="py-1 px-2">
                                    <?php if ($company_type == 'HUGO'): ?>
                                        <?php echo $itemDetails['name'] ?><br><?php echo nl2br($itemDetails['description']) ?>
                                    <?php else: ?>
                                        <?php echo $itemDetails['name'] ?>(<?php echo nl2br($itemDetails['description']) ?>)
                                    <?php endif; ?>
                                </td>
                                <td class="py-1 px-2 text-right"><?php echo number_format_indian($item['amount'], 2) ?></td>
                                <td class="py-1 px-2 text-right"><?php echo number_format($item['quantity']) ?>(<?php echo $item['unit'] ?>)</td>
                                <td class="py-1 px-2 text-right">
                                    <?php echo number_format_indian($discounted_price_per_item, 2) ?>
                                    (<?php echo number_format($item['discount'], 2) ?>%)
                                </td>
                                <td class="py-1 px-2 text-right"><?php echo number_format_indian($item['total_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php

                $po_id = $_GET['id'];
                $query = "SELECT sub_total, tax, tax_amount, cgst, cgst_amount, sgst, sgst_amount, grand_total, final_discounted_price FROM purchase_order_list WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $po_id);
                $stmt->execute();
                $stmt->bind_result($sub_total, $tax, $tax_amount, $cgst, $cgst_amount, $sgst, $sgst_amount, $grand_total, $final_discounted_price);
                $stmt->fetch();
                $invoice = [
                    'sub_total' => $sub_total,
                    'tax' => $tax,
                    'tax_amount' => $tax_amount,
                    'cgst' => $cgst,
                    'cgst_amount' => $cgst_amount,
                    'sgst' => $sgst,
                    'sgst_amount' => $sgst_amount,
                    'grand_total' => $grand_total,
                    'final_discounted_price' => $final_discounted_price
                ];
                $stmt->close();
                ?>
                <tfoot>
                    <tr>
                        <th class="text-right" colspan="5">Sub Total:</th>
                        <td class="text-right"><?php echo number_format_indian($invoice['sub_total'], 2); ?></td>
                    </tr>
                    <tr>
                        <?php if ($invoice['tax'] > 0): ?>
                            <th class="text-right" colspan="5">IGST (<?php echo $invoice['tax']; ?>%):</th>
                            <td class="text-right"><?php echo number_format_indian($invoice['tax_amount'], 2); ?></td>
                        <?php endif; ?>
                    </tr>
                    <tr>
                        <?php if ($invoice['cgst'] > 0): ?>
                            <th class="text-right" colspan="5">CGST (<?php echo $invoice['cgst']; ?>%):</th>
                            <td class="text-right"><?php echo number_format_indian($invoice['cgst_amount'], 2); ?></td>
                        <?php endif; ?>
                    </tr>
                    <tr>
                        <?php if ($invoice['sgst'] > 0): ?>
                            <th class="text-right" colspan="5">SGST (<?php echo $invoice['sgst']; ?>%):</th>
                            <td class="text-right"><?php echo $invoice['sgst_amount']; ?></td>
                        <?php endif; ?>
                    </tr>
                    <tr>
                        <th class="text-right" style="color: red;" colspan="5"><u>Grand Total:</u></th>
                        <th class="text-right"><?php echo number_format_indian($invoice['grand_total'], 2); ?></th>
                    </tr>
                    <tr>
                        <?php if ($invoice['final_discounted_price'] > 0): ?>
                            <th class="text-right" colspan="5">Discounted Price:</th>
                            <th class="text-right"><?php echo number_format_indian($invoice['final_discounted_price'], 2); ?></th>
                        <?php endif; ?>
                    </tr>
                </tfoot>
            </table>

            <div class="po-address-block">
                <div class="form-group mt-3">
                    <div class="d-flex justify-content-start address-container">
                        <div class="address-block">
                            <label for="bill_delivery" class="control-label">Generate bill in the name of:</label>
                            <p id="bill_delivery">
                                <?php if ($company_type == 'HUGO'): ?>
                                    <u>Hugopharm Technologies Pvt. Ltd.</u><br>
                                    Plot No. TS 20, MIDC Phase 2,<br>
                                    Man pada Road, Sagaon,<br>
                                    Besides Sekhsaria Chemicals/ Arch Pharma<br>
                                    Dombivli (E) - 421 203<br>
                                    GSTIN/UIN : 27AACCH1711N1ZM
                                <?php else: ?>
                                    <u>S.B. Panchal & Company</u><br>
                                    8, Jogani Industrial Estate,<br>
                                    541, Senapati Bapat Marg,<br>
                                    Dadar (West), Mumbai - 400 028<br>
                                    GSTIN/UIN : 27AAAFS5950K1ZW
                                <?php endif; ?>
                            </p>
                            <!-- Hidden div to display the selected address for bill delivery printing -->
                            <div id="print_address_bill" class="print-address"></div>
                        </div>
                        <div class="address-block">
                            <label class="control-label">Material delivery to:</label>
                            <p>
                                <?php
                                if ($material_delivery == 'DOM') {
                                    if ($company_type == 'HUGO') {
                                        echo "<u>Hugopharm Technologies Pvt. Ltd.</u><br>
                                             Plot No. TS 20, MIDC Phase 2,<br>
                                             Man pada Road, Sagaon,<br>
                                             Besides Sekhsaria Chemicals/ Arch Pharma<br>
                                             Dombivli (E) - 421 203";
                                    } else {
                                        echo "<u>S.B. Panchal & Company</u><br>
                                             C/O S.B. Panchal & Company,<br>
                                             Plot No. TS 20, MIDC Phase 2,<br>
                                             Man pada Road, Sagaon,<br>
                                             Besides Sekhsaria Chemicals/ Arch Pharma<br>
                                             Dombivli (E) - 421 203";
                                    }
                                } else if ($material_delivery == 'DDR') {
                                    if ($company_type == 'HUGO') {
                                        echo "<u>Hugopharm Technologies Pvt. Ltd.</u><br>
                                             8, Jogani Industrial Estate,<br>
                                             541 Senapati Bapat Marg,<br>
                                             Dadar (W), Mumbai 400 028";
                                    } else {
                                        echo "<u>S.B. Panchal & Company</u><br>
                                             8, Jogani Industrial Estate,<br>
                                             541 Senapati Bapat Marg,<br>
                                             Dadar (W), Mumbai 400 028";
                                    }
                                } else if ($material_delivery == 'SELF') {
                                    echo "Self Pickup";
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <label class="reduced-spacing">Terms & Conditions:</label>
                <p>
                    1) Tax :- GST As Applicable <br>
                    2) Payment :- <?php echo isset($payment_terms) ? $payment_terms : ''; ?><br>
                    3) Freight :- <?php echo isset($freight) ? $freight : ''; ?><br>
                    4) Delivery period :- <?php echo isset($delivery_period) ? $delivery_period : ''; ?><br>
                    5) Packing & Forwarding :- <?php echo isset($packing_forwarding) ? $packing_forwarding : ''; ?><br>
                    6) Warranty :- 1 year against manufacturing defect
                </p>

                <h5 class="term-cond">
                    Scanned Copies of All Certificates to be email to purchase.sbpanchal@gmail.com<br>
                    Hard Copies of All Calibration Certificates to be delivered to billing address
                </h5>

                <p>Yours truly,<br>
                    <label>For <?php echo ($company_type == 'HUGO') ? 'Hugopharm Technologies Pvt. Ltd.' : 'S.B. Panchal & Company'; ?></label>
                </p>
                <div class="signature-block">
                    <h5><strong><?php echo isset($authorized_signatory) ? $authorized_signatory : ''; ?></strong></h5>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group no-print">
                            <label for="remarks" class="text-info control-label">Remarks</label>
                            <p><?php echo isset($remarks) ? $remarks : '' ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($spec_sheet)): ?>
        <div class="print-page-break"></div>
        <div class="card mt-4" id="spec-sheet-section">
            <div class="card-header">
                <h4 class="mb-0">Spec Sheet</h4>
            </div>
            <div class="card-body">
                <div>
                    <?php echo $spec_sheet; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row mt-4 no-print">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Purchase Order Timeline</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#purchaseOrderTimelineModal">
                        <i class="fas fa-plus"></i> Add Timeline Entry
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php
                    $timeline_qry = $conn->query("SELECT pt.*, CONCAT(u.firstname, ' ', u.lastname) as created_by_name 
                        FROM purchase_order_timeline pt 
                        LEFT JOIN users u ON u.id = pt.created_by 
                        WHERE pt.po_id = '{$id}' 
                        ORDER BY pt.step_date DESC");
                    while ($row = $timeline_qry->fetch_assoc()):
                    ?>
                        <div>
                            <i class="fas fa-file-alt bg-blue"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i>
                                    <?= date("M d, Y h:i A", strtotime($row['step_date'])) ?>
                                </span>
                                <h3 class="timeline-header d-flex justify-content-between align-items-center">
                                    <div><?= ucwords(str_replace('_', ' ', $row['step_name'])) ?></div>
                                    <div>
                                        <button type="button" class="btn btn-xs btn-info edit_purchase_order_timeline"
                                            data-id="<?= $row['id'] ?>"
                                            data-step_name="<?= htmlspecialchars($row['step_name']) ?>"
                                            data-step_date="<?= $row['step_date'] ?>"
                                            data-remarks="<?= htmlspecialchars($row['remarks']) ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-danger delete_purchase_order_timeline"
                                            data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </h3>
                                <div class="timeline-body">
                                    <?php if (!empty($row['remarks'])): ?>
                                        <?= nl2br($row['remarks']) ?>
                                    <?php endif; ?>
                                    <?php
                                    $files_qry = $conn->query("SELECT * FROM purchase_order_timeline_files WHERE timeline_id = '{$row['id']}'");
                                    if ($files_qry->num_rows > 0):
                                    ?>
                                        <div class="mt-2">
                                            <strong>Attachments:</strong>
                                            <?php while ($file = $files_qry->fetch_assoc()): ?>
                                                <div class="mt-1">
                                                    <?php if (!empty($file['description'])): ?>
                                                        <span class="text-muted"><?= $file['description'] ?>: </span>
                                                    <?php endif; ?>
                                                    <a href="<?= base_url . $file['file_path'] ?>" class="btn btn-xs btn-info" target="_blank">
                                                        <i class="fas fa-eye"></i> View File
                                                    </a>
                                                    <a href="<?= base_url . $file['file_path'] ?>" class="btn btn-xs btn-success" download>
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-footer">
                                    Added by: <?= $row['created_by_name'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Timeline Modal -->
<div class="modal fade" id="purchaseOrderTimelineModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Timeline Entry</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="purchase-order-timeline-form" enctype="multipart/form-data">
                <input type="hidden" name="po_id" value="<?= $id ?>">
                <input type="hidden" name="timeline_id" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Step Name</label>
                        <input type="text" name="step_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Date & Time</label>
                        <input type="datetime-local" name="step_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Attachments</label>
                        <div id="purchase-order-document-container">
                            <div class="document-row mb-2">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <input type="text" name="document_description[]" class="form-control form-control-sm" placeholder="Document description">
                                    </div>
                                    <div class="col-md-7">
                                        <input type="file" name="documents[]" class="form-control-file">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-sm btn-danger remove-document">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-info mt-2" id="add-purchase-order-document">
                            <i class="fas fa-plus"></i> Add Another Document
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('success');
        if (success === 'true') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Purchase order saved successfully!',
                showConfirmButton: false,
                timer: 2000,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        }
    });

    document.getElementById('print').addEventListener('click', function() {
        window.print();
    });

    $(function() {
        // Add document row
        $('#add-purchase-order-document').click(function() {
            $('#purchase-order-document-container').append(`
            <div class="document-row mb-2">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="document_description[]" class="form-control form-control-sm" placeholder="Document description">
                    </div>
                    <div class="col-md-7">
                        <input type="file" name="documents[]" class="form-control-file">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-danger remove-document">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);
        });

        // Remove document row
        $(document).on('click', '.remove-document', function() {
            $(this).closest('.document-row').remove();
        });

        // Submit timeline form
        $('#purchase-order-timeline-form').submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: '<?= base_url ?>classes/Master.php?f=save_purchase_order_timeline',
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function(resp) {
                    if (resp.status == 'success') {
                        location.reload();
                    } else {
                        alert(resp.msg || "An error occurred");
                    }
                },
                error: function() {
                    alert("Failed to save timeline entry");
                }
            });
        });

        // Edit timeline
        $('.edit_purchase_order_timeline').click(function() {
            var id = $(this).data('id');
            var step_name = $(this).data('step_name');
            var step_date = $(this).data('step_date');
            var remarks = $(this).data('remarks');
            $('#purchase-order-timeline-form')[0].reset();
            $('#purchase-order-timeline-form input[name="timeline_id"]').val(id);
            $('#purchase-order-timeline-form input[name="step_name"]').val(step_name);
            $('#purchase-order-timeline-form input[name="step_date"]').val(step_date.replace(' ', 'T'));
            $('#purchase-order-timeline-form textarea[name="remarks"]').val(remarks);
            $('#purchaseOrderTimelineModal').modal('show');
        });

        $('.delete_purchase_order_timeline').click(function() {
            var id = $(this).data('id');
            _conf("Are you sure you want to delete this timeline entry?", "delete_purchase_order_timeline_confirmed", [id]);
        });
    });

    function delete_purchase_order_timeline_confirmed(id) {
        $.ajax({
            url: '<?= base_url ?>classes/Master.php?f=delete_purchase_order_timeline',
            method: "POST",
            data: {
                id: id
            },
            dataType: "json",
            success: function(resp) {
                if (resp.status == 'success') {
                    location.reload();
                } else {
                    alert_toast(resp.msg || "An error occurred", 'error');
                }
            },
            error: function() {
                alert_toast("Failed to delete timeline entry", 'error');
            }
        });
    }
</script>