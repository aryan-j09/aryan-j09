<?php

function getCurrencySymbol($currency)
{
    switch (strtoupper($currency)) {
        case 'USD':
            return '$';
        case 'EUR':
            return '€';
        case 'INR':
        default:
            return '₹';
    }
}

function formatIndianMoney($num)
{
    // Separate the decimal part
    $parts = explode('.', number_format($num, 2, '.', ''));
    $whole = $parts[0];
    $decimal = $parts[1];

    // Format the whole number part in Indian format
    $formatted = preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $whole);

    // Return only the formatted number with decimals, NO currency symbol
    return $formatted . "." . $decimal;
}

// Add this with other helper functions at the top
function getVerifierName($user_id)
{
    global $conn;
    $fullname = '';
    $stmt = $conn->prepare("SELECT CONCAT(firstname, ' ', lastname) as fullname FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($fullname);
    if ($stmt->fetch()) {
        $result = ['fullname' => $fullname];
        $stmt->close();
        return $result['fullname'];
    }
    $stmt->close();
    return 'Unknown User';
}

if (!isset($_GET['id'])) {
    echo '<script>alert("PO ID is required"); location.href="./?page=po_details";</script>';
    exit;
}
$id = $_GET['id'];

// Add error checking for query
$qry = $conn->query("
    SELECT 
        po.*,
        pil.*,
        pil.id as pi_id,
        pil.freight_note,
        c.company_name as client,
        c.billing_address as client_address,
        c.shipping_address,
        c.gst_number,
        c.contact_person,
        c.contact_no,
        c.cperson_acc,
        c.cperson_no_acc,
        c.cperson_pur,
        c.cperson_no_pur,
        c.email,
        pil.inspection_payment_type,
        DATE_FORMAT(pil.po_date_created, '%d-%b-%Y') as po_date,
        GROUP_CONCAT(pi.description ORDER BY pi.id ASC SEPARATOR '\n') as specifications,
        (po.advance_received + po.inspection_received + po.installation_received + po.credit_received) as total_received,
        (COALESCE(po.advance_excess, 0) + COALESCE(po.inspection_excess, 0) + COALESCE(po.installation_excess, 0) + COALESCE(po.credit_excess, 0)) as total_excess,
        po.verified_by,
        po.verified_at,
        po.shortfall_is_tds,
        (
            GREATEST(0, COALESCE(pil.advance_payment_amount, 0) - COALESCE(po.advance_received, 0)) +
            GREATEST(0, COALESCE(pil.inspection_payment_amount, 0) - COALESCE(po.inspection_received, 0)) +
            GREATEST(0, COALESCE(pil.installation_payment_amount, 0) - COALESCE(po.installation_received, 0)) +
            GREATEST(0, COALESCE(pil.credit_payment_amount, 0) - COALESCE(po.credit_received, 0))
        ) as total_shortfall,
        po.balance_amount
    FROM purchase_orders po 
    LEFT JOIN proforma_invoice_list pil ON po.po_code = pil.po_code 
    LEFT JOIN clients c ON pil.client_id = c.id
    LEFT JOIN proforma_invoice_items pi ON pil.id = pi.proforma_invoice_id
    WHERE po.id = '$id'
    GROUP BY po.id
") or die("Query failed: " . $conn->error);

// Check if record exists
if ($qry->num_rows == 0) {
    echo '<script>alert("PO not found"); location.href="./?page=po_details";</script>';
    exit;
}

$po = $qry->fetch_assoc();

// Add null checks before displaying data

// Add this near the top where you fetch data
$completed_steps = [];
$steps_qry = $conn->query("SELECT step_name FROM po_timeline WHERE po_id = '{$id}'");
while ($step = $steps_qry->fetch_assoc()) {
    $completed_steps[] = $step['step_name'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PO Details</title>
    <style>
        /* Add this to your CSS file */
        .file-popup {
            --popup-bg: #f8f9fa;
            --popup-border: #dee2e6;
            --popup-shadow: rgba(0, 0, 0, 0.15);
            --text-color: #333;
            --hover-bg: #007bff;
            --hover-color: #fff;
            user-select: none;
            /* Prevent text selection */
        }

        .file-popup {
            display: inline-block;
            position: relative;
        }

        .file-popup input {
            display: none;
        }

        .file-burger {
            padding: 6px 12px;
            /* Match Bootstrap btn padding */
            height: 31px;
            /* Match Bootstrap btn height */
            font-size: 14px;
            /* Match Bootstrap btn font-size */
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fff;
            /* Match btn-flat background */
            border: 1px solid #ddd;
            /* Add border like other buttons */
            border-radius: 0;
            /* Match btn-flat style */
            color: #343a40;
            /* Match default button text color */
        }

        /* Update hover state to match Bootstrap buttons */
        .file-burger:hover {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
        }

        /* Optional: Match the active state of other buttons */
        .file-burger:active {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        /* Optional: If you want to adjust the icon size */
        .file-burger .fas {
            font-size: 14px;
            /* Match icon size with other buttons */
        }

        .file-popup-window {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: var(--popup-bg);
            border: 1px solid var(--popup-border);
            border-radius: 4px;
            box-shadow: 0 2px 4px var(--popup-shadow);
            visibility: hidden;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            z-index: 1000;
            min-width: 200px;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .file-popup-window legend {
            padding: 8px 16px;
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            border-bottom: 1px solid var(--popup-border);
            margin-bottom: 0;
            width: 100%;
        }

        .file-popup-window ul {
            list-style: none;
            padding: 8px 0;
            margin: 0;
        }

        .file-popup-window ul li a {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            color: var(--text-color);
            text-decoration: none;
            gap: 8px;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .file-popup-window ul li a:hover {
            background: var(--hover-bg);
            color: var(--hover-color);
        }

        .file-popup-window ul li a svg {
            width: 16px;
            height: 16px;
        }

        .file-popup-window hr {
            margin: 8px 0;
            border: 0;
            border-top: 1px solid var(--popup-border);
        }

        .file-popup input:checked~.file-popup-window {
            visibility: visible;
            opacity: 1;
            transform: translateY(0);
        }

        /* Add this new style to prevent text cursor on the checkbox */
        .file-popup input[type="checkbox"] {
            cursor: default;
        }
    </style>
</head>

<body>
    <div class="card card-outline card-primary">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="box">
                    <label class="control-label text-info">PO No: </label> <?= $po['po_code'] ?? 'N/A' ?>
                    <label class="control-label text-info date">PO Date: </label> <?= $po['po_date'] ?? 'N/A' ?>
                    <label class="control-label text-info date">Expected Delivery: </label> <?= $po['expected_delivery'] ? date('d-M-Y', strtotime($po['expected_delivery'])) : 'N/A' ?>
                    <label class="control-label text-info">Delivery: </label>
                    <?php
                    if ($po['actual_delivery_date']) {
                        echo '<span class="badge badge-success">' . date('d-M-Y', strtotime($po['actual_delivery_date'])) . '</span>';
                    } else {
                        $today = strtotime(date('Y-m-d'));
                        $delivery_date = strtotime($po['expected_delivery']);
                        $days_left = ceil(($delivery_date - $today) / (60 * 60 * 24));

                        if ($days_left < 0) {
                            echo "<span class='badge badge-danger'>Overdue by " . abs($days_left) . " days</span>";
                        } elseif ($days_left == 0) {
                            echo "<span class='badge badge-warning'>Due Today</span>";
                        } else {
                            echo "<span class='badge badge-warning'>{$days_left} days left</span>";
                        }
                    }
                    ?>
                </div>
                <div class="card-tools no-print">
                    <?php
                    if (isset($_SESSION['userdata']) && $_SESSION['userdata']['type'] == '1'):
                        $proforma_view_page = (($po['company'] ?? '') === 'S.B. Panchal') ? 'sbp_pi' : 'view_pi';
                        // Check if any document exists
                        $has_documents = !empty($po['po_file']) ||
                            !empty($po['bill_file']) ||
                            !empty($po['challan_file']) ||
                            !empty($po['eway_file']) ||
                            !empty($po['lr_file']) ||
                            !empty($po['quotation_file']) ||
                            !empty($po['pi_id']);

                        if ($has_documents):
                    ?>
                            <label class="file-popup">
                                <input type="checkbox">
                                <div class="file-burger btn btn-flat btn-default btn-sm" tabindex="0">
                                    <span class="fas fa-file-alt"></span> Documents
                                </div>
                                <nav class="file-popup-window">
                                    <legend>Document Actions</legend>
                                    <ul>
                                        <?php if (!empty($po['po_file'])): ?>
                                            <li>
                                                <a href="<?= base_url ?>uploads/po_files/<?= $po['po_file'] ?>" target="_blank">
                                                    <svg stroke-linejoin="round" stroke-linecap="round" stroke-width="2" stroke="currentColor" fill="none" viewBox="0 0 24 24" height="14" width="14">
                                                        <path d="M14 2H6a2 0 0 0-2 2v16a2 0 0 0 2 2h12a2 0 0 0 2-2V8z"></path>
                                                        <polyline points="14 2 14 8 20 8"></polyline>
                                                    </svg>
                                                    <span>PO Document</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (!empty($po['bill_file'])): ?>
                                            <li>
                                                <a href="<?= base_url ?>uploads/invoices/<?= $po['bill_file'] ?>" target="_blank">
                                                    <svg stroke-linejoin="round" stroke-linecap="round" stroke-width="2" stroke="currentColor" fill="none" viewBox="0 0 24 24" height="14" width="14">
                                                        <path d="M14 2H6a2 0 0 0-2 2v16a2 0 0 0 2 2h12a2 0 0 0 2-2V8z"></path>
                                                        <polyline points="14 2 14 8 20 8"></polyline>
                                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                                    </svg>
                                                    <span>Bill</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (!empty($po['challan_file'])): ?>
                                            <li>
                                                <a href="<?= base_url ?>uploads/invoices/<?= $po['challan_file'] ?>" target="_blank">
                                                    <svg stroke-linejoin="round" stroke-linecap="round" stroke-width="2" stroke="currentColor" fill="none" viewBox="0 0 24 24" height="14" width="14">
                                                        <path d="M14 2H6a2 0 0 0-2 2v16a2 0 0 0 2 2h12a2 0 0 0 2-2V8z"></path>
                                                        <polyline points="14 2 14 8 20 8"></polyline>
                                                    </svg>
                                                    <span>Challan</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (!empty($po['eway_file'])): ?>
                                            <li>
                                                <a href="<?= base_url ?>uploads/eway_file/<?= $po['eway_file'] ?>" target="_blank">
                                                    <svg stroke-linejoin="round" stroke-linecap="round" stroke-width="2" stroke="currentColor" fill="none" viewBox="0 0 24 24" height="14" width="14">
                                                        <path d="M14 2H6a2 0 0 0-2 2v16a2 0 0 0 2 2h12a2 0 0 0 2-2V8z"></path>
                                                        <polyline points="14 2 14 8 20 8"></polyline>
                                                    </svg>
                                                    <span>E-way Bill</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (!empty($po['lr_file'])): ?>
                                            <li>
                                                <a href="<?= base_url ?>uploads/lr_file/<?= $po['lr_file'] ?>" target="_blank">
                                                    <svg stroke-linejoin="round" stroke-linecap="round" stroke-width="2" stroke="currentColor" fill="none" viewBox="0 0 24 24" height="14" width="14">
                                                        <path d="M14 2H6a2 0 0 0-2 2v16a2 0 0 0 2 2h12a2 0 0 0 2-2V8z"></path>
                                                        <polyline points="14 2 14 8 20 8"></polyline>
                                                    </svg>
                                                    <span>LR Copy</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (!empty($po['quotation_file'])): ?>
                                            <li>
                                                <a href="<?= base_url ?>uploads/quotation_files/<?= $po['quotation_file'] ?>" target="_blank">
                                                    <svg stroke-linejoin="round" stroke-linecap="round" stroke-width="2" stroke="currentColor" fill="none" viewBox="0 0 24 24" height="14" width="14">
                                                        <path d="M14 2H6a2 0 0 0-2 2v16a2 0 0 0 2 2h12a2 0 0 0 2-2V8z"></path>
                                                        <polyline points="14 2 14 8 20 8"></polyline>
                                                    </svg>
                                                    <span>Quotation</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (!empty($po['pi_id'])): ?>
                                            <li>
                                                <a href="<?= base_url ?>admin/?page=proforma_invoice/<?= $proforma_view_page ?>&id=<?= $po['pi_id'] ?>" target="_blank">
                                                    <svg stroke-linejoin="round" stroke-linecap="round" stroke-width="2" stroke="currentColor" fill="none" viewBox="0 0 24 24" height="14" width="14">
                                                        <path d="M14 2H6a2 0 0 0-2 2v16a2 0 0 0 2 2h12a2 0 0 0 2-2V8z"></path>
                                                        <polyline points="14 2 14 8 20 8"></polyline>
                                                    </svg>
                                                    <span>Proforma Invoice</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </label>
                    <?php
                        endif;
                    endif;
                    ?>
                    <a href="?page=po_details/manage_po_details&id=<?= $id ?>" class="btn btn-flat btn-primary btn-sm">
                        <span class="fas fa-edit"></span> Edit
                    </a>
                    <a href="?page=po_details" class="btn btn-flat btn-default btn-sm">
                        <span class="fas fa-arrow-left"></span> Back
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row border">
                <div class="col-md-6 pt-2">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr>
                                <td><strong>Client:</strong></td>
                                <td><?= $po['client'] ?? 'N/A' ?></td>
                            </tr>
                            <tr>
                                <td><strong>GST No.:</strong></td>
                                <td><?= $po['gst_number'] ?? 'N/A' ?></td>
                            <tr>
                                <td><strong>Billing Address:</strong></td>
                                <td><?= $po['client_address'] ?? 'N/A' ?></td>
                            </tr>
                            <tr>
                                <td><strong>Shipping Address:</strong></td>
                                <td><?= $po['shipping_address'] ?? 'N/A' ?></td>
                            </tr>
                            <?php if (!empty($po['contact_person'])): ?>
                                <tr>
                                    <td><strong>Cont. End User:</strong></td>
                                    <td>
                                        <?= $po['contact_person'] ?>
                                        <?= !empty($po['contact_no']) ? ' - ' . $po['contact_no'] : '' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($po['cperson_acc'])): ?>
                                <tr>
                                    <td><strong>Cont. Accounts:</strong></td>
                                    <td>
                                        <?= $po['cperson_acc'] ?>
                                        <?= !empty($po['cperson_no_acc']) ? ' - ' . $po['cperson_no_acc'] : '' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($po['cperson_pur'])): ?>
                                <tr>
                                    <td><strong>Cont. Purchase:</strong></td>
                                    <td>
                                        <?= $po['cperson_pur'] ?>
                                        <?= !empty($po['cperson_no_pur']) ? ' - ' . $po['cperson_no_pur'] : '' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><?= $po['email'] ?? 'N/A' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6 border-left pt-2">
                    <?php if (isset($_SESSION['userdata']) && $_SESSION['userdata']['type'] == '1'): ?>
                        <div class="mb-2 border p-1">
                            <dt class="border-bottom">Amount Details:</dt>
                            <dd>
                                <div>Net Amount: <?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po['sub_total'] ?? 0) ?></div>
                                <?php if (($po['packing_forwarding'] ?? 0) > 0): ?>
                                    <div>P&F (<?= $po['packing_forwarding'] ?>%): <?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po['packing_forwarding_amount'] ?? 0) ?></div>
                                <?php elseif (($po['packing_forwarding_amount'] ?? 0) > 0): ?>
                                    <div>P&F: <?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po['packing_forwarding_amount'] ?? 0) ?></div>
                                <?php endif; ?>
                                <?php if (($po['freight'] ?? 0) > 0): ?>
                                    <div>Freight: <?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po['freight'] ?? 0) ?></div>
                                <?php endif; ?>
                                <?php if (($po['cgst'] ?? 0) > 0): ?>
                                    <div>CGST (<?= $po['cgst'] ?>%): <?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po['cgst_amount'] ?? 0) ?></div>
                                <?php endif; ?>
                                <?php if (($po['sgst'] ?? 0) > 0): ?>
                                    <div>SGST (<?= $po['sgst'] ?>%): <?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po['sgst_amount'] ?? 0) ?></div>
                                <?php endif; ?>
                                <?php if (($po['tax'] ?? 0) > 0): ?>
                                    <div>IGST (<?= $po['tax'] ?>%): <?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po['tax_amount'] ?? 0) ?></div>
                                <?php endif; ?>
                                <div class="mt-2 border-top">
                                    <strong>Total Amount: <?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po['total_amount'] ?? 0) ?></strong>
                                </div>
                            </dd>
                        </div>

                        <div class="mb-2 border p-1">
                            <dt class="border-bottom">Payment Terms:</dt>
                            <dd>
                                <?php if (!empty($po['freight_note'])): ?>
                                    <div class=" mb-2 pt-1">
                                        <strong>Freight Scope:</strong> <?= nl2br($po['freight_note']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php
                                $payments = [
                                    ['type' => 'advance', 'label' => 'Advance'],
                                    ['type' => 'inspection', 'label' => ($po['inspection_payment_type'] === 'delivery' ? 'Delivery' : 'Inspection')],
                                    ['type' => 'installation', 'label' => 'Installation'],
                                    ['type' => 'credit', 'label' => $po['credit_payment_days'] . ' Days Credit']
                                ];

                                foreach ($payments as $payment):
                                    $type = $payment['type'];

                                    // Special handling for credit payment
                                    if ($type === 'credit') {
                                        if (($po['credit_payment_amount'] ?? 0) > 0):
                                            $expected = $po['credit_payment_amount'];
                                            $received = $po['credit_received'] ?? 0;
                                            $percentage = ($expected / $po['total_amount']) * 100;

                                            // Determine payment status and badge color
                                            if ($received == 0) {
                                                $status = 'Pending';
                                                $badge = 'danger';
                                            } elseif ($received >= $expected) {
                                                $status = 'Received';
                                                $badge = 'success';
                                            } else {
                                                // Check if shortfall should be attributed to TDS
                                                if ($po['shortfall_is_tds'] == 1) {
                                                    $status = 'Deducted as TDS';
                                                    $badge = 'info';
                                                } else {
                                                    $status = getCurrencySymbol($po['currency'] ?? 'INR') . ' ' . formatIndianMoney($received) . ' received';
                                                    $badge = 'warning';
                                                }
                                            }
                                ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="flex-grow-1" style="max-width: 80%">
                                                    <?= $payment['label'] ?> -
                                                    <span class="text-muted"><?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($expected) ?></span>
                                                    <?php if (($po['credit_excess'] ?? 0) > 0): ?>
                                                        <span class="badge badge-warning ml-1">
                                                            <i class="fas fa-exclamation-triangle"></i> Excess: <?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po['credit_excess']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="badge badge-<?= $badge ?> ml-1"><?= $status ?></span>
                                            </div>
                                        <?php
                                        endif;
                                    } else {
                                        // Existing payment types handling
                                        if (($po["{$type}_payment"] ?? 0) > 0):
                                            $expected = $po["{$type}_payment_amount"] ?? 0;
                                            $received = $po["{$type}_received"] ?? 0;

                                            // Determine payment status and badge color
                                            if ($received == 0) {
                                                $status = 'Pending';
                                                $badge = 'danger';
                                            } elseif ($received >= $expected) {
                                                $status = 'Received';
                                                $badge = 'success';
                                            } else {
                                                // Check if shortfall should be attributed to TDS
                                                if ($po['shortfall_is_tds'] == 1) {
                                                    $status = 'Deducted as TDS';
                                                    $badge = 'info';
                                                } else {
                                                    $status = getCurrencySymbol($po['currency'] ?? 'INR') . ' ' . formatIndianMoney($received) . ' received';
                                                    $badge = 'warning';
                                                }
                                            }

                                            // Add bank guarantee indicators
                                            $bg_text = '';
                                            if ($type === 'advance' && ($po['abg_required'] ?? 0)) {
                                                $bg_text = '<span class="badge badge-info ml-1">Against ABG</span>';
                                            } elseif ($type === 'installation' && ($po['pbg_required'] ?? 0)) {
                                                $bg_text = '<span class="badge badge-info ml-1">Against PBG</span>';
                                            }
                                        ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="flex-grow-1" style="max-width: 80%">
                                                    <?= $payment['label'] ?> (<?= $po["{$type}_payment"] ?>%) -
                                                    <span class="text-muted"><?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($expected) ?></span>
                                                    <?= $bg_text ?>
                                                    <?php if (($po["{$type}_excess"] ?? 0) > 0): ?>
                                                        <span class="badge badge-warning ml-1">
                                                            <i class="fas fa-exclamation-triangle"></i> Excess: <?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po["{$type}_excess"]) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="badge badge-<?= $badge ?> ml-1"><?= $status ?></span>
                                            </div>
                                <?php
                                        endif;
                                    }
                                endforeach;
                                ?>
                            </dd>
                        </div>

                        <div class="mb-2 border p-1">
                            <dt class="border-bottom">Payment status:</dt>
                            <?php 
                            // Use pre-calculated balance_amount from database
                            $tds_amount = ($po['shortfall_is_tds'] == 1 && ($po['total_shortfall'] ?? 0) > 0) ? $po['total_shortfall'] : 0;
                            $actual_balance = $po['balance_amount'] ?? 0;
                            ?>
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="width: 100%;">
                                    <div class="mb-2">
                                        <strong>Contract Total:</strong> 
                                        <span class="text-info"><?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po['total_amount'] ?? 0) ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Total Paid:</strong> 
                                        <span class="<?= ($po['total_received'] ?? 0) >= ($po['total_amount'] ?? 0) ? 'text-success' : 'text-primary' ?>"><?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($po['total_received'] ?? 0) ?></span>
                                    </div>
                                    <?php if ($tds_amount > 0): ?>
                                        <div class="mb-2">
                                            <strong>TDS Deducted:</strong> 
                                            <span class="text-info"><?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($tds_amount) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <hr class="my-2">
                                    <div class="mb-2">
                                        <strong>Balance Due:</strong> 
                                        <span class="<?= $actual_balance > 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= getCurrencySymbol($po['currency'] ?? 'INR') ?> <?= formatIndianMoney($actual_balance) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mt-4 border">
                <div class="col-md-12 pt-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="m-0">Requirements & Specifications</h5>
                        <div class="no-print">
                            <?php if (isset($_SESSION['userdata'])): ?>
                                <?php
                                // Calculate current content hash
                                $current_hash = hash('sha256', $po['requirement'] . $po['specification']);
                                $is_verified = $po['requirements_verified'] && ($current_hash === $po['requirements_hash']);
                                ?>

                                <?php if (!$is_verified): ?>
                                    <button type="button" class="btn btn-sm btn-success" id="verifyBtn">
                                        <i class="fas fa-check"></i>
                                        <?= $po['requirements_verified'] ? 'Re-Acknowledge Changes' : 'Acknowledge & Verify' ?>
                                    </button>
                                    <?php if ($po['last_content_update']): ?>
                                        <small class="text-muted ml-2">
                                            Content updated on <?= date('d-M-Y H:i', strtotime($po['last_content_update'])) ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-success">
                                        <i class="fas fa-check-circle"></i>
                                        Verified by <?= getVerifierName($po['verified_by']) ?>
                                        on <?= date('d-M-Y H:i', strtotime($po['verified_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <dt>Requirement:</dt>
                    <dd class="mb-4"><?= nl2br($po['requirement'] ?? 'N/A') ?></dd>

                    <?php if (!empty($po['specification'])): ?>
                        <dt>Specifications:</dt>
                        <dd class="mb-4">
                            <?= html_entity_decode($po['specification']) ?>
                        </dd>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($po['remarks']): ?>
                <div class="row mt-4">
                    <div class="col-md-12 border">
                        <dt>Remarks:</dt>
                        <dd><?= nl2br($po['remarks'] ?? 'N/A') ?></dd>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($po['status'] == 'completed'): ?>
                <div class="row mt-4">
                    <div class="col-md-12 border p-3">
                        <dt>Completion Details:</dt>
                        <dd>
                            <div class="d-flex align-items-center mt-2">
                                <div class="mr-4">
                                    <strong>Delivery Date:</strong>
                                    <?= date('d-M-Y', strtotime($po['actual_delivery_date'])) ?>
                                </div>
                                <?php if (!empty($po['bill_file'])): ?>
                                    <a href="<?= base_url . 'uploads/invoices/' . $po['bill_file'] ?>"
                                        class="btn btn-sm btn-info mr-2"
                                        target="_blank">
                                        <i class="fas fa-file-invoice"></i> View Bill
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($po['challan_file'])): ?>
                                    <a href="<?= base_url . 'uploads/invoices/' . $po['challan_file'] ?>"
                                        class="btn btn-sm btn-info"
                                        target="_blank">
                                        <i class="fas fa-file-alt"></i> View Challan
                                    </a>
                                <?php endif; ?>
                            </div>
                        </dd>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
<script>
    var _base_url_ = '<?= base_url ?>'; // Note the underscore at the end
</script>
<script>
    $(document).ready(function() {
        $('#verifyBtn').click(function() {
            if (confirm('Are you sure you want to verify these requirements? This action cannot be undone.')) {
                $.ajax({
                    url: _base_url_ + "classes/Master.php?f=verify_requirements",
                    type: 'POST',
                    data: {
                        po_id: '<?= $id ?>'
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.status == 'success') {
                            location.reload();
                        } else {
                            alert_toast(resp.msg || "An error occurred", 'error');
                        }
                    },
                    error: function() {
                        alert_toast("Failed to verify requirements", 'error');
                    }
                });
            }
        });

        // Single edit_timeline handler
        $('.edit_timeline').click(function() {
            var id = $(this).data('id');
            var step_name = $(this).data('step_name');
            var step_date = $(this).data('step_date');
            var remarks = $(this).data('remarks');
            var files = $(this).data('files');

            // Reset form and clear files
            $('#timeline-form')[0].reset();
            $('#document-container').empty();

            // Add hidden ID field
            $('#timeline-form input[name="id"]').remove();
            $('#timeline-form').prepend(`<input type="hidden" name="id" value="${id}">`);

            // Set form values
            $('select[name="step_name"]').val(step_name);
            if (!$('select[name="step_name"]').val()) {
                $('select[name="step_name"]').val('other');
                $('.custom-step-name').show();
                $('input[name="custom_step_name"]').val(step_name);
            }

            // Format and set date
            var date = new Date(step_date);
            var formatted_date = date.getFullYear() + '-' +
                String(date.getMonth() + 1).padStart(2, '0') + '-' +
                String(date.getDate()).padStart(2, '0') + 'T' +
                String(date.getHours()).padStart(2, '0') + ':' +
                String(date.getMinutes()).padStart(2, '0');
            $('input[name="step_date"]').val(formatted_date);
            $('textarea[name="remarks"]').val(remarks);

            // Show existing files
            if (files && files.length > 0) {
                files.forEach(function(file) {
                    $('#document-container').append(`
                <div class="document-row mb-2">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" value="${file.description || ''}" readonly>
                        </div>
                        <div class="col-md-7">
                            <div class="d-flex align-items-center">
                                <span class="mr-2">${file.file_path.split('/').pop()}</span>
                                <a href="${_base_url_}${file.file_path}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-danger remove-existing" data-id="${file.id}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>`);
                });
            }

            // Add empty file upload row
            $('#document-container').append(`
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
        </div>`);

            // Update title and show modal
            $('#timelineModal .modal-title').text('Edit Timeline Entry');
            $('#timelineModal').modal('show');
        });
    });
</script>
<!-- Add this after your existing rows -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card card-outline card-primary no-print">
            <div class="card-header">
                <h3 class="card-title">Purchase Order Timeline</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#timelineModal">
                        <i class="fas fa-plus"></i> Add Timeline Entry
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php
                    $timeline_qry = $conn->query("SELECT pt.*, CONCAT(u.firstname, ' ', u.lastname) as created_by_name 
                                                FROM po_timeline pt 
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
                                        <button type="button" class="btn btn-xs btn-info edit_timeline"
                                            data-id="<?= $row['id'] ?>"
                                            data-step_name="<?= $row['step_name'] ?>"
                                            data-step_date="<?= $row['step_date'] ?>"
                                            data-remarks="<?= htmlspecialchars($row['remarks']) ?>"
                                            data-po_id="<?= $id ?>"
                                            data-files='<?= htmlspecialchars(json_encode($conn->query("SELECT id, file_path, description FROM po_timeline_files WHERE timeline_id = '{$row['id']}'")->fetch_all(MYSQLI_ASSOC)), ENT_QUOTES) ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-danger delete_timeline"
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
                                    // Get files for this timeline entry
                                    $files_qry = $conn->query("SELECT * FROM po_timeline_files WHERE timeline_id = '{$row['id']}'");
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
<div class="modal fade" id="timelineModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Timeline Entry</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="timeline-form" enctype="multipart/form-data">
                <input type="hidden" name="po_id" value="<?= $id ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Step Name</label>
                        <select name="step_name" class="form-control" id="step_name" required>
                            <option value="">Select Step</option>
                            <?php if ($po['abg_required']): ?>
                                <optgroup label="Advance Bank Guarantee Steps">
                                    <option value="abg_format_approved" data-category="abg" data-order="1">ABG Format Approved</option>
                                    <option value="abg_sent" data-category="abg" data-order="2"
                                        <?= !in_array('abg_format_approved', $completed_steps) ? 'disabled' : '' ?>>
                                        ABG Sent to Client
                                    </option>
                                    <option value="abg_payment" data-category="abg" data-order="3" data-type="advance"
                                        <?= !in_array('abg_sent', $completed_steps) ? 'disabled' : '' ?>>
                                        Payment Against ABG
                                    </option>
                                    <option value="abg_received_back" data-category="abg" data-order="4"
                                        <?= !in_array('abg_payment', $completed_steps) ? 'disabled' : '' ?>>
                                        Original ABG Received Back
                                    </option>
                                </optgroup>
                            <?php endif; ?>

                            <?php if ($po['pbg_required']): ?>
                                <optgroup label="Performance Bank Guarantee Steps">
                                    <option value="pbg_format_approved" data-category="pbg" data-order="1">PBG Format Approved</option>
                                    <option value="pbg_sent" data-category="pbg" data-order="2"
                                        <?= !in_array('pbg_format_approved', $completed_steps) ? 'disabled' : '' ?>>
                                        PBG Sent to Client
                                    </option>
                                    <option value="pbg_payment" data-category="pbg" data-order="3" data-type="installation"
                                        <?= !in_array('pbg_sent', $completed_steps) ? 'disabled' : '' ?>>
                                        Payment Against PBG
                                    </option>
                                    <option value="pbg_received_back" data-category="pbg" data-order="4"
                                        <?= !in_array('pbg_payment', $completed_steps) ? 'disabled' : '' ?>>
                                        Original PBG Received Back
                                    </option>
                                </optgroup>
                            <?php endif; ?>
                            <option value="proforma_sent">Proforma Invoice Sent</option>
                            <option value="GA_drawing">GA Drawing</option>
                            <option value="utility_summary">Utility Summary</option>
                            <option value="installation_report">Installation Report</option>
                            <option value="fat">Factory Acceptance Test</option>
                            <option value="dispatch">Material Dispatch</option>
                            <option value="delivery">Material Delivered</option>

                            <?php if (($po['advance_payment'] ?? 0) > 0):
                                $advance_pending = $po['advance_payment_amount'] - ($po['advance_received'] ?? 0);
                                if ($advance_pending > 0):
                            ?>
                                    <option value="advance_payment" data-amount="<?= $advance_pending ?>" data-type="advance">
                                        Advance Payment (<?= $po['advance_payment'] ?>% - Balance: <?= formatIndianMoney($advance_pending) ?>)
                                    </option>
                            <?php
                                endif;
                            endif; ?>

                            <?php if (($po['inspection_payment'] ?? 0) > 0):
                                $inspection_pending = $po['inspection_payment_amount'] - ($po['inspection_received'] ?? 0);
                                if ($inspection_pending > 0):
                            ?>
                                    <option value="inspection_payment" data-amount="<?= $inspection_pending ?>" data-type="inspection">
                                        <?= $po['inspection_payment_type'] === 'delivery' ? 'Delivery' : 'Inspection' ?> Payment
                                        (<?= $po['inspection_payment'] ?>% - Balance: <?= formatIndianMoney($inspection_pending) ?>)
                                    </option>
                            <?php
                                endif;
                            endif; ?>

                            <?php if (($po['installation_payment'] ?? 0) > 0):
                                $installation_pending = $po['installation_payment_amount'] - ($po['installation_received'] ?? 0);
                                if ($installation_pending > 0):
                            ?>
                                    <option value="installation_payment" data-amount="<?= $installation_pending ?>" data-type="installation">
                                        Installation Payment (<?= $po['installation_payment'] ?>% - Balance: <?= formatIndianMoney($installation_pending) ?>)
                                    </option>
                            <?php
                                endif;
                            endif; ?>

                            <?php if (($po['credit_payment_amount'] ?? 0) > 0):
                                $credit_pending = $po['credit_payment_amount'] - ($po['credit_received'] ?? 0);
                                if ($credit_pending > 0):
                            ?>
                                    <option value="credit_payment" data-amount="<?= $credit_pending ?>" data-type="credit">
                                        Credit Payment (<?= $po['credit_payment_days'] ?> Days - Balance: <?= formatIndianMoney($credit_pending) ?>)
                                    </option>
                            <?php
                                endif;
                            endif; ?>

                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group custom-step-name" style="display:none;">
                        <label>Custom Step Name</label>
                        <input type="text" name="custom_step_name" class="form-control">
                    </div>
                    <div class="form-group payment-amount-group" style="display:none;">
                        <label>Payment Amount Received (<?= getCurrencySymbol($po['currency'] ?? 'INR') ?>)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><?= getCurrencySymbol($po['currency'] ?? 'INR') ?></span>
                            </div>
                            <input type="number" name="payment_amount" class="form-control" step="0.01">
                            <input type="hidden" name="payment_type">
                            <div class="input-group-append">
                                <span class="input-group-text">/ <span class="max-amount"></span></span>
                            </div>
                        </div>
                        <small class="form-text text-muted">Enter the amount received</small>
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
                        <div id="document-container">
                            <div class="document-row mb-2">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <input type="text" name="document_description[]"
                                            class="form-control form-control-sm"
                                            placeholder="Document description">
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
                        <button type="button" class="btn btn-sm btn-info mt-2" id="add-document">
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
    $('.delete_timeline').click(function() {
        const id = $(this).data('id');

        _conf("Are you sure you want to delete this timeline entry?", "delete_po_timeline", [id]);
    });

    // Add the delete_po_timeline function
    function delete_po_timeline(id) {
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_po_timeline",
            method: "POST",
            data: {
                id: id
            },
            dataType: "json",
            error: err => {
                console.log(err);
                alert_toast("An error occurred", 'error');
                end_loader();
            },
            success: function(resp) {
                if (resp.status == 'success') {
                    location.reload();
                } else {
                    alert_toast(resp.msg || "An error occurred", 'error');
                }
                end_loader();
            }
        });
    }

    $(document).ready(function() {
        // Add this function to handle payment steps
        function handlePaymentStep() {
            var selectedOption = $('select[name="step_name"] option:selected');
            var stepName = selectedOption.val();

            // Check if the selected step is a payment step
            if (stepName.includes('_payment')) {
                var amount = selectedOption.data('amount');
                var paymentType = selectedOption.data('type');

                // For ABG/PBG payments, calculate remaining amount
                if (stepName === 'abg_payment') {
                    var totalAmount = <?= $po['advance_payment_amount'] ?? 0 ?>;
                    var receivedAmount = <?= $po['advance_received'] ?? 0 ?>;
                    amount = totalAmount - receivedAmount;
                    paymentType = 'advance';
                } else if (stepName === 'pbg_payment') {
                    var totalAmount = <?= $po['installation_payment_amount'] ?? 0 ?>;
                    var receivedAmount = <?= $po['installation_received'] ?? 0 ?>;
                    amount = totalAmount - receivedAmount;
                    paymentType = 'installation';
                }

                if (amount > 0) {
                    $('.payment-amount-group').show();
                    $('.max-amount').text('<?= getCurrencySymbol($po['currency'] ?? 'INR') ?> ' + amount.toLocaleString('en-IN'));
                    $('input[name="payment_amount"]').attr('max', amount);
                    $('input[name="payment_type"]').val(paymentType);
                } else {
                    $('.payment-amount-group').hide();
                    alert_toast("Full payment already received", 'warning');
                    $('select[name="step_name"]').val('');
                }
            } else {
                $('.payment-amount-group').hide();
                $('input[name="payment_amount"]').val('');
                $('input[name="payment_type"]').val('');
            }
        }

        // Add this to your existing document ready function
        $('select[name="step_name"]').change(function() {
            handlePaymentStep();
        });

        // Form submission handler
        $('#timeline-form').submit(function(e) {
            e.preventDefault();
            start_loader();

            var formData = new FormData(this);

            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_po_timeline",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                error: err => {
                    console.log(err);
                    alert_toast("An error occurred", 'error');
                    end_loader();
                },
                success: function(resp) {
                    if (resp.status == 'success') {
                        location.reload();
                    } else {
                        alert_toast(resp.msg || "An error occurred", 'error');
                    }
                    end_loader();
                }
            });
        });
    });
</script>