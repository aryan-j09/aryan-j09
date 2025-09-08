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
    $parts = explode('.', number_format($num, 2, '.', ''));
    $whole = $parts[0];
    $decimal = $parts[1];
    $formatted = preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", $whole);
    return $formatted . "." . $decimal;
}
$id = isset($_GET['id']) ? $_GET['id'] : null;
$po = [];

// If editing, fetch existing PO data
if ($id) {
    $qry = $conn->query("
    SELECT 
        po.*,
        pil.*,
        c.company_name as client_name,
        DATE_FORMAT(pil.po_date_created, '%d-%b-%Y') as po_date,
        DATEDIFF(po.expected_delivery, pil.po_date_created) DIV 7 as delivery_weeks,
        po.credit_received,
        pil.credit_payment_amount,
        pil.credit_payment_days,
        pil.inspection_payment_type,
        pil.currency
    FROM purchase_orders po 
    LEFT JOIN proforma_invoice_list pil ON po.po_code = pil.po_code 
    LEFT JOIN clients c ON pil.client_id = c.id
    WHERE po.id = '$id'
    GROUP BY po.id
");
    $po = $qry->fetch_assoc();
}

// Fetch proforma invoices for dropdown with requirements from items
$proforma_qry = $conn->query("
    SELECT 
        p.*,
        c.company_name as client_name,
        DATE_FORMAT(p.po_date_created, '%d-%b-%Y') as po_date,
        GROUP_CONCAT(pi.description ORDER BY pi.id ASC SEPARATOR '\n') as requirements
    FROM proforma_invoice_list p
    LEFT JOIN clients c ON p.client_id = c.id
    LEFT JOIN proforma_invoice_items pi ON p.id = pi.proforma_invoice_id
    WHERE p.po_code NOT IN (SELECT po_code FROM purchase_orders WHERE id != COALESCE('$id', 0))
    GROUP BY p.id
");
?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><?= $id ? 'Update' : 'Create' ?> PO Factory Details</h3>
    </div>
    <div class="card-body">
        <form id="po_form">
            <input type="hidden" name="id" value="<?= $id ?? '' ?>">

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="po_code">PO Number</label>
                        <?php if ($id): ?>
                            <input type="text" class="form-control" name="po_code" value="<?= $po['po_code'] ?>" readonly>
                        <?php else: ?>
                            <select class="form-control select2" name="po_code" id="po_code" required>
                                <option value="">Select PO</option>
                                <?php while ($row = $proforma_qry->fetch_assoc()): ?>
                                    <option value="<?= $row['po_code'] ?>"
                                        data-info='<?= json_encode($row) ?>'>
                                        <?= $row['po_code'] ?> - <?= $row['client_name'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Client Name</label>
                        <input type="text" class="form-control" id="client_name" readonly
                            value="<?= $po['client_name'] ?? '' ?>">
                        <input type="hidden" name="client_id" id="client_id"
                            value="<?= $po['client_id'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>PO Date</label>
                        <input type="text" class="form-control" id="po_date" readonly
                            value="<?= $po['po_date'] ?? '' ?>">
                    </div>
                </div>
            </div>

            <div class="row mt-3">

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Delivery(In weeks)</label>
                        <input type="number" name="delivery_weeks" id="delivery_weeks" class="form-control" min="1"
                            value="<?= $po['delivery_weeks'] ?? '' ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Expected Delivery</label>
                        <input type="text" id="delivery_date" class="form-control" readonly>
                        <input type="hidden" name="expected_delivery" id="expected_delivery">
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>PO Document</label>
                        <div class="input-group">
                            <input type="file" name="po_file" id="po_file"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <?php if ($id && !empty($po['po_file'])): ?>
                                <div class="input-group-append">
                                    <a class="btn btn-outline-secondary" href="<?= base_url ?>uploads/po_files/<?= $po['po_file'] ?>" target="_blank">
                                        View Current
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>E-way Bill</label>
                        <div class="input-group">
                            <input type="file" name="eway_file" id="eway_file"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <?php if ($id && !empty($po['eway_file'])): ?>
                                <div class="input-group-append">
                                    <a class="btn btn-outline-secondary" href="<?= base_url ?>uploads/eway_file/<?= $po['eway_file'] ?>" target="_blank">
                                        View Current
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>LR Copy</label>
                        <div class="input-group">
                            <input type="file" name="lr_file" id="lr_file"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <?php if ($id && !empty($po['lr_file'])): ?>
                                <div class="input-group-append">
                                    <a class="btn btn-outline-secondary" href="<?= base_url ?>uploads/lr_file/<?= $po['lr_file'] ?>" target="_blank">
                                        View Current
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Quotation</label>
                        <div class="input-group">
                            <input type="file" name="quotation_file" id="quotation_file"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <?php if ($id && !empty($po['quotation_file'])): ?>
                                <div class="input-group-append">
                                    <a class="btn btn-outline-secondary" href="<?= base_url ?>uploads/quotation_files/<?= $po['quotation_file'] ?>" target="_blank">
                                        View Current
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG</small>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Requirement</label>
                <textarea name="requirement" class="form-control" rows="2" required><?= $po['requirement'] ?? '' ?></textarea>
            </div>

            <div class="form-group">
                <label>Specifications</label>
                <textarea name="specification" class="form-control" rows="9"><?= $po['specification'] ?? '' ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div id="tax_details" class="border p-2">
                        <h5 class="border-bottom">Amount Details</h5>
                        <div id="amount_breakdown"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div id="payment_details" class="border p-2">
                        <h5 class="border-bottom">Payment Terms</h5>
                        <div id="payment_breakdown"></div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="form-group">
                                    <label>TDS Amount (<?= getCurrencySymbol($po['currency'] ?? 'INR') ?>)</label>
                                    <input type="number" name="tds_amount" class="form-control"
                                        value="<?= $po['tds_amount'] ?? '' ?>"
                                        min="0" step="0.01"
                                        placeholder="Enter TDS amount">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control"><?= $po['remarks'] ?? '' ?></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?php echo base_url ?>admin/?page=po_details" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize select2
        if (!('<?= $id ?>')) {
            $('#po_code').select2({
                width: '100%'
            }).on('change', function() {
                const data = $(this).find(':selected').data('info');
                if (data) populateFields(data);
            });
        }

        // ADD Summernote initialization
        $('textarea[name="specification"]').summernote({
            height: 400,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

        $('#po_form').submit(function(e) {
            e.preventDefault();

            let formData = new FormData(this);

            // Set Summernote content to formData
            formData.set('specification', $('textarea[name="specification"]').summernote('code'));

            // Log form data for debugging
            console.log('Form Data:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_po_details",
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: () => start_loader(),
                success: function(resp) {
                    end_loader();
                    if (resp.status == 'success') {
                        location.href = _base_url_ + "admin/?page=po_details/view_po_details&id=" + resp.id;
                        alert_toast('Data successfully saved', 'success');
                    } else {
                        alert_toast(resp.msg || "An error occurred", 'error');
                    }
                },
                error: function(xhr, status, error) {
                    end_loader();
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                    alert_toast("An error occurred", 'error');
                }
            });
        });

        // If editing, calculate delivery date on load
        if ('<?= $id ?>') calculateDeliveryDate();

        // Handle delivery weeks change
        $('#delivery_weeks').on('input', calculateDeliveryDate);

        // If editing, populate all fields
        if ('<?= $id ?>') {
            const poData = <?= json_encode($po) ?>;

            // Populate payment received fields
            if (poData.advance_payment > 0) {
                $('[name="advance_received"]').val(poData.advance_received || 0).trigger('input');
            }
            if (poData.inspection_payment > 0) {
                $('[name="inspection_received"]').val(poData.inspection_received || 0).trigger('input');
            }
            if (poData.installation_payment > 0) {
                $('[name="installation_received"]').val(poData.installation_received || 0).trigger('input');
            }
            if (poData.credit_payment_amount > 0) { // Add this block
                $('[name="credit_received"]').val(poData.credit_received || 0).trigger('input');
            }

            // Generate amount breakdown
            const amountHtml = generateAmountBreakdown(poData);
            $('#amount_breakdown').html(amountHtml);

            // Generate payment terms with existing values
            const paymentHtml = generatePaymentTerms(poData);
            $('#payment_breakdown').html(paymentHtml);

            // Initialize handlers
            initializePaymentHandlers();

            // Calculate delivery date
            calculateDeliveryDate();
        }
    });

    function populateFields(data) {
        // Basic field population
        $('#client_id').val(data.client_id);
        $('#client_name').val(data.client_name);
        $('#po_date').val(data.po_date);
        $('[name="requirement"]').val(data.requirements);

        // Generate amount details HTML
        const amountHtml = generateAmountBreakdown(data);
        $('#amount_breakdown').html(amountHtml);

        // Generate payment terms HTML
        const paymentHtml = generatePaymentTerms(data);
        $('#payment_breakdown').html(paymentHtml);

        initializePaymentHandlers();
    }

    function getCurrencySymbolJS(currency) {
        switch ((currency || 'INR').toUpperCase()) {
            case 'USD':
                return '$';
            case 'EUR':
                return '€';
            case 'INR':
            default:
                return '₹';
        }
    }

    function formatNumber(num) {
        if (isNaN(num)) return '0.00';
        const parts = parseFloat(num).toFixed(2).split('.');
        let whole = parts[0];
        const decimal = parts[1];
        whole = whole.replace(/(\d+?)(?=(\d\d)+(\d)(?!\d))/g, "$1,");
        return whole + '.' + decimal;
    }

    function generateAmountBreakdown(data) {
        const symbol = getCurrencySymbolJS(data.currency);
        return `
        <div>Sub Total: ${symbol}${formatNumber(data.sub_total)}</div>
        ${data.cgst > 0 ? `<div>CGST (${data.cgst}%): ${symbol}${formatNumber(data.cgst_amount)}</div>` : ''}
        ${data.sgst > 0 ? `<div>SGST (${data.sgst}%): ${symbol}${formatNumber(data.sgst_amount)}</div>` : ''}
        ${data.tax > 0 ? `<div>IGST (${data.tax}%): ${symbol}${formatNumber(data.tax_amount)}</div>` : ''}
        ${data.packing_forwarding > 0 ? `<div>P&F (${data.packing_forwarding}%): ${symbol}${formatNumber(data.packing_forwarding_amount)}</div>` : ''}
        ${data.freight > 0 ? `<div>Freight: ${symbol}${formatNumber(data.freight)}</div>` : ''}
    `;
    }

    function generatePaymentTerms(data) {
        const symbol = getCurrencySymbolJS(data.currency);
        let html = `<div class="font-weight-bold mb-3">Total Amount: ${symbol}${formatNumber(data.total_amount)}</div>`;

        // Determine if it's inspection or delivery based on payment terms
        const inspectionLabel = data.inspection_payment_type === 'delivery' ? 'Delivery' : 'Inspection';

        const payments = [{
                type: 'advance',
                label: 'Advance'
            },
            {
                type: 'inspection',
                label: inspectionLabel
            },
            {
                type: 'installation',
                label: 'Installation'
            },
            {
                type: 'credit',
                label: `${data.credit_payment_days || 0} Days Credit`
            }
        ];

        payments.forEach(({
            type,
            label
        }) => {
            if (type === 'credit') {
                if (data.credit_payment_amount > 0) {
                    const received = data.credit_received || 0;
                    html += generateCreditPaymentInput(label, data.credit_payment_amount, received, symbol);
                }
            } else {
                const percentage = data[`${type}_payment`];
                const expected = data[`${type}_payment_amount`];

                if (percentage > 0) {
                    const received = data[`${type}_received`] || 0;
                    html += generatePaymentInput(type, label, percentage, expected, received, symbol);
                }
            }
        });

        return html;
    }

    function generateCreditPaymentInput(label, expected, existingValue = 0, symbol = '₹') {
        return `
    <div class="mb-2">
        <div class="mb-1">${label}</div>
        <div class="input-group input-group-sm">
            <input type="number" name="credit_received" class="form-control payment-received" 
                max="${expected}" data-expected="${expected}" 
                value="${existingValue}" step="0.01"
                required>
            <div class="input-group-append">
                <span class="input-group-text">/</span>
            </div>
            <input type="text" class="form-control" value="${symbol}${formatNumber(expected)}" readonly>
        </div>
        <input type="hidden" name="credit_payment_amount" value="${expected}">
    </div>`;
    }

    function generatePaymentInput(type, label, percentage, expected, existingValue = 0, symbol = '₹') {
        return `
    <div class="mb-2">
        <div class="mb-1">${label} (${percentage}%)</div>
        <div class="input-group input-group-sm">
            <input type="number" name="${type}_received" class="form-control payment-received" 
                max="${expected}" data-expected="${expected}" 
                value="${existingValue}" data-target="${type}_payment" step="0.01"
                required>
            <div class="input-group-append">
                <span class="input-group-text">/</span>
            </div>
            <input type="text" class="form-control" value="${symbol}${formatNumber(expected)}" readonly>
            <div class="input-group-append">
                <span class="input-group-text payment-percentage">(${((existingValue / expected) * 100).toFixed(2)}%)</span>
            </div>
        </div>
        <input type="hidden" name="${type}_payment_amount" value="${expected}">
    </div>`;
    }

    function initializePaymentHandlers() {
        $('.payment-received').on('input', function() {
            const value = parseFloat($(this).val()) || 0;
            const expected = parseFloat($(this).data('expected'));
            const percentage = ((value / expected) * 100).toFixed(2);

            $(this).closest('.input-group')
                .find('.payment-percentage')
                .text(`(${percentage}%)`);

            if (Math.round(value * 100) > Math.round(expected * 100)) {
                $(this).val((Math.floor(expected * 100) / 100).toFixed(2));
                $(this).trigger('input');
            }
        });
    }

    function calculateDeliveryDate() {
        const poDate = $('#po_date').val();
        const weeks = $('#delivery_weeks').val();
        if (poDate && weeks) {
            const date = new Date(poDate.split('-').reverse().join('-'));
            date.setDate(date.getDate() + (weeks * 7));

            // Format for display
            $('#delivery_date').val(formatDate(date));

            // Format for database storage (YYYY-MM-DD)
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            $('#expected_delivery').val(`${year}-${month}-${day}`);
        }
    }

    function formatDate(date) {
        return date.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }).replace(/ /g, '-');
    }
</script>