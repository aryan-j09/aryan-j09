<?php
$user_id = $_SESSION['userdata']['id'];
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

if (!function_exists('format_indian_number')) {
    function format_indian_number($amount)
    {
        $amount = round((float)$amount, 0);
        $is_negative = $amount < 0;
        $amount = abs($amount);
        $amount_str = (string)$amount;
        $len = strlen($amount_str);

        if ($len <= 3) {
            return ($is_negative ? '-' : '') . $amount_str;
        }

        $last_three = substr($amount_str, -3);
        $rest_units = substr($amount_str, 0, -3);
        $rest_formatted = '';

        while (strlen($rest_units) > 2) {
            $rest_formatted = ',' . substr($rest_units, -2) . $rest_formatted;
            $rest_units = substr($rest_units, 0, -2);
        }

        $rest_formatted = $rest_units . $rest_formatted;
        return ($is_negative ? '-' : '') . $rest_formatted . ',' . $last_three;
    }
}

$task_stats = $conn->query("SELECT
        COUNT(*) AS total,
        SUM(priority='high') AS high_count
    FROM tasks
    WHERE assigned_to = '{$user_id}'
        AND status IN ('pending','in_progress')
        AND due_date < '{$now}'");
$task_stats = $task_stats ? $task_stats->fetch_assoc() : ['total' => 0, 'high_count' => 0];

$task_focus = $conn->query("SELECT id, title, due_date, priority
    FROM tasks
    WHERE assigned_to = '{$user_id}'
        AND status IN ('pending','in_progress')
        AND due_date < '{$now}'
    ORDER BY FIELD(priority,'high','medium','low'), due_date ASC
    LIMIT 3");

$payment_stats = $conn->query("SELECT
        COUNT(*) AS total,
        SUM(
            GREATEST(IFNULL(pi.advance_payment_amount,0)-IFNULL(po.advance_received,0),0)
            + GREATEST(IFNULL(pi.inspection_payment_amount,0)-IFNULL(po.inspection_received,0),0)
            + GREATEST(IFNULL(pi.installation_payment_amount,0)-IFNULL(po.installation_received,0),0)
            + GREATEST(IFNULL(pi.credit_payment_amount,0)-IFNULL(po.credit_received,0),0)
        ) AS pending_amount
    FROM purchase_orders po
    LEFT JOIN proforma_invoice_list pi ON pi.po_code = po.po_code
    WHERE (po.status = 'completed' OR po.actual_delivery_date IS NOT NULL)
        AND IFNULL(po.balance_amount,0) > 0
        AND (
            IFNULL(pi.advance_payment_amount,0) > IFNULL(po.advance_received,0)
            OR IFNULL(pi.inspection_payment_amount,0) > IFNULL(po.inspection_received,0)
            OR IFNULL(pi.installation_payment_amount,0) > IFNULL(po.installation_received,0)
            OR IFNULL(pi.credit_payment_amount,0) > IFNULL(po.credit_received,0)
        )");
$payment_stats = $payment_stats ? $payment_stats->fetch_assoc() : ['total' => 0, 'pending_amount' => 0];

$payment_focus = $conn->query("SELECT po.id, po.po_code, c.company_name,
        (
            GREATEST(IFNULL(pi.advance_payment_amount,0)-IFNULL(po.advance_received,0),0)
            + GREATEST(IFNULL(pi.inspection_payment_amount,0)-IFNULL(po.inspection_received,0),0)
            + GREATEST(IFNULL(pi.installation_payment_amount,0)-IFNULL(po.installation_received,0),0)
            + GREATEST(IFNULL(pi.credit_payment_amount,0)-IFNULL(po.credit_received,0),0)
        ) AS pending_amount
    FROM purchase_orders po
    LEFT JOIN clients c ON c.id = po.client_id
    LEFT JOIN proforma_invoice_list pi ON pi.po_code = po.po_code
    WHERE (po.status = 'completed' OR po.actual_delivery_date IS NOT NULL)
        AND IFNULL(po.balance_amount,0) > 0
        AND (
            IFNULL(pi.advance_payment_amount,0) > IFNULL(po.advance_received,0)
            OR IFNULL(pi.inspection_payment_amount,0) > IFNULL(po.inspection_received,0)
            OR IFNULL(pi.installation_payment_amount,0) > IFNULL(po.installation_received,0)
            OR IFNULL(pi.credit_payment_amount,0) > IFNULL(po.credit_received,0)
        )
    ORDER BY pending_amount DESC
    LIMIT 3");

$payment_hover = $conn->query("SELECT po.id, po.po_code, c.company_name,
        (
            GREATEST(IFNULL(pi.advance_payment_amount,0)-IFNULL(po.advance_received,0),0)
            + GREATEST(IFNULL(pi.inspection_payment_amount,0)-IFNULL(po.inspection_received,0),0)
            + GREATEST(IFNULL(pi.installation_payment_amount,0)-IFNULL(po.installation_received,0),0)
            + GREATEST(IFNULL(pi.credit_payment_amount,0)-IFNULL(po.credit_received,0),0)
        ) AS pending_amount
    FROM purchase_orders po
    LEFT JOIN clients c ON c.id = po.client_id
    LEFT JOIN proforma_invoice_list pi ON pi.po_code = po.po_code
    WHERE (po.status = 'completed' OR po.actual_delivery_date IS NOT NULL)
        AND IFNULL(po.balance_amount,0) > 0
        AND (
            IFNULL(pi.advance_payment_amount,0) > IFNULL(po.advance_received,0)
            OR IFNULL(pi.inspection_payment_amount,0) > IFNULL(po.inspection_received,0)
            OR IFNULL(pi.installation_payment_amount,0) > IFNULL(po.installation_received,0)
            OR IFNULL(pi.credit_payment_amount,0) > IFNULL(po.credit_received,0)
        )
    ORDER BY pending_amount DESC");

$delivery_stats = $conn->query("SELECT
        COUNT(*) AS total,
        MAX(DATEDIFF('{$today}', expected_delivery)) AS max_overdue
    FROM purchase_orders
    WHERE status = 'pending'
        AND expected_delivery IS NOT NULL
        AND expected_delivery < '{$today}'
        AND actual_delivery_date IS NULL");
$delivery_stats = $delivery_stats ? $delivery_stats->fetch_assoc() : ['total' => 0, 'max_overdue' => 0];

$delivery_focus = $conn->query("SELECT po.id, po.po_code, c.company_name, expected_delivery,
        DATEDIFF('{$today}', expected_delivery) AS overdue_days
    FROM purchase_orders po
    LEFT JOIN clients c ON c.id = po.client_id
    WHERE po.status = 'pending'
        AND po.expected_delivery IS NOT NULL
        AND po.expected_delivery < '{$today}'
        AND po.actual_delivery_date IS NULL
    ORDER BY overdue_days DESC
    LIMIT 3");

$delivery_hover = $conn->query("SELECT po.id, po.po_code, c.company_name, expected_delivery,
        DATEDIFF('{$today}', expected_delivery) AS overdue_days
    FROM purchase_orders po
    LEFT JOIN clients c ON c.id = po.client_id
    WHERE po.status = 'pending'
        AND po.expected_delivery IS NOT NULL
        AND po.expected_delivery < '{$today}'
        AND po.actual_delivery_date IS NULL
    ORDER BY overdue_days DESC");

$followup_stats = $conn->query("SELECT COUNT(*) AS total
    FROM lead_activities
    WHERE created_by = '{$user_id}'
        AND DATE(next_followup) = '{$today}'");
$followup_stats = $followup_stats ? $followup_stats->fetch_assoc() : ['total' => 0];

$followup_focus = $conn->query("SELECT la.lead_id, la.activity_type, la.next_followup, l.company_name
    FROM lead_activities la
    LEFT JOIN leads l ON l.id = la.lead_id
    WHERE la.created_by = '{$user_id}'
        AND DATE(la.next_followup) = '{$today}'
    ORDER BY la.next_followup ASC
    LIMIT 3");
?>

<style>
    .dashboard-top .kpi-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,.06);
    }
    .dashboard-top .kpi-card.clickable {
        cursor: pointer;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .dashboard-top .kpi-card.clickable:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,.10);
    }
    .dashboard-top .kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 18px;
    }
    .dashboard-top .kpi-number {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1;
    }
    .dashboard-top .focus-card {
        border: 1px solid #eef1f4;
        border-radius: 12px;
    }
    .dashboard-top .focus-item {
        padding: 8px 0;
        border-bottom: 1px dashed #eceff2;
    }
    .dashboard-top .focus-item:last-child { border-bottom: 0; }
    .dashboard-top .subtle { color: #6c757d; font-size: .85rem; }
    .bg-danger-soft { background: #dc3545; }
    .bg-warning-soft { background: #f0ad4e; }
    .bg-info-soft { background: #17a2b8; }
    .bg-primary-soft { background: #007bff; }

    .dashboard-top .search-wrap { position: relative; }
    .dashboard-top .search-results {
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        z-index: 1050;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        margin-top: 6px;
        box-shadow: 0 8px 20px rgba(0,0,0,.08);
        max-height: 420px;
        overflow-y: auto;
        display: none;
    }
    .dashboard-top .search-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 8px;
        padding: 8px;
    }
    .dashboard-top .search-group {
        border: 1px solid #edf0f2;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }
    .dashboard-top .search-group-title {
        font-size: .74rem;
        font-weight: 700;
        color: #6c757d;
        padding: 8px 10px;
        text-transform: uppercase;
        background: #fafbfc;
    }
    .dashboard-top .search-item {
        display: block;
        padding: 8px 10px;
        border-top: 1px dashed #f1f1f1;
        color: #212529;
    }
    .dashboard-top .search-item:hover {
        background: #f8f9fa;
        text-decoration: none;
    }
    .dashboard-top .search-meta {
        color: #6c757d;
        font-size: .8rem;
    }
    .dashboard-top .hover-preview-shell {
        border: 1px solid #e8edf2;
        border-radius: 12px;
        display: none;
    }
    .dashboard-top .hover-preview-list {
        max-height: 320px;
        overflow-y: auto;
        border: 1px solid #eef1f4;
        border-radius: 8px;
    }
    .dashboard-top .hover-preview-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        border-bottom: 1px dashed #edf1f5;
    }
    .dashboard-top .hover-preview-item:last-child {
        border-bottom: 0;
    }
    .dashboard-top .hover-preview-meta {
        color: #6c757d;
        font-size: .82rem;
    }
    .dashboard-top .hover-preview-value {
        font-weight: 600;
        white-space: nowrap;
    }
</style>

<div class="dashboard-top">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Top Alerts</h4>
            <small class="text-muted"><?php echo date('d M Y'); ?></small>
        </div>

        <div class="search-wrap mb-3">
            <input type="text" id="universal-search" class="form-control" placeholder="Search company, PO code, PI code, client, supplier...">
            <div id="universal-search-results" class="search-results"></div>
    </div>

    <div class="row">
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card kpi-card clickable h-100" onclick="window.location.href='./?page=tasks#assigned-to-me'">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="kpi-icon bg-danger-soft"><i class="fas fa-tasks"></i></div>
                        <i class="fas fa-arrow-right text-muted"></i>
                    </div>
                    <div class="kpi-number text-danger"><?php echo (int)$task_stats['total']; ?></div>
                    <div class="font-weight-bold">Overdue Tasks</div>
                    <div class="subtle"><?php echo (int)$task_stats['high_count']; ?> high priority</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card kpi-card clickable h-100" data-preview="payments" data-preview-title="Pending Payment Terms" onclick="window.location.href='./?page=po_details&payment_pending=1'">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="kpi-icon bg-warning-soft"><i class="fas fa-rupee-sign"></i></div>
                        <i class="fas fa-arrow-right text-muted"></i>
                    </div>
                    <div class="kpi-number text-warning"><?php echo (int)$payment_stats['total']; ?></div>
                    <div class="font-weight-bold">Pending Payment Terms</div>
                    <div class="subtle">₹ <?php echo format_indian_number($payment_stats['pending_amount']); ?> pending</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card kpi-card clickable h-100" data-preview="deliveries" data-preview-title="Overdue Deliveries" onclick="window.location.href='./?page=po_details&overdue_delivery=1'">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="kpi-icon bg-info-soft"><i class="fas fa-truck"></i></div>
                        <i class="fas fa-arrow-right text-muted"></i>
                    </div>
                    <div class="kpi-number text-info"><?php echo (int)$delivery_stats['total']; ?></div>
                    <div class="font-weight-bold">Overdue Deliveries</div>
                    <div class="subtle">Max <?php echo (int)$delivery_stats['max_overdue']; ?> days overdue</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card kpi-card clickable h-100" onclick="window.location.href='./?page=leads&today_followups=1'">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="kpi-icon bg-primary-soft"><i class="fas fa-phone"></i></div>
                        <i class="fas fa-arrow-right text-muted"></i>
                    </div>
                    <div class="kpi-number text-primary"><?php echo (int)$followup_stats['total']; ?></div>
                    <div class="font-weight-bold">Today's Follow-ups</div>
                    <div class="subtle">Scheduled for today</div>
                </div>
            </div>
        </div>
    </div>

    <div id="hover-preview-shell" class="card hover-preview-shell mb-3">
        <div class="card-header bg-white border-0 pb-0">
            <h3 id="hover-preview-title" class="card-title mb-0">Details</h3>
        </div>
        <div class="card-body">
            <div id="hover-preview-payments" class="hover-preview-content d-none">
                <div class="hover-preview-list">
                    <?php if($payment_hover && $payment_hover->num_rows): while($row = $payment_hover->fetch_assoc()): ?>
                        <div class="hover-preview-item">
                            <div>
                                <a href="./?page=po_details/view_po_details&id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['po_code']); ?></a>
                                <div class="hover-preview-meta"><?php echo htmlspecialchars((string)$row['company_name']); ?></div>
                            </div>
                            <div class="hover-preview-value text-warning">₹ <?php echo format_indian_number($row['pending_amount']); ?></div>
                        </div>
                    <?php endwhile; else: ?>
                        <div class="p-3 text-muted">No pending payment records.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div id="hover-preview-deliveries" class="hover-preview-content d-none">
                <div class="hover-preview-list">
                    <?php if($delivery_hover && $delivery_hover->num_rows): while($row = $delivery_hover->fetch_assoc()): ?>
                        <div class="hover-preview-item">
                            <div>
                                <a href="./?page=po_details/view_po_details&id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['po_code']); ?></a>
                                <div class="hover-preview-meta"><?php echo htmlspecialchars((string)$row['company_name']); ?></div>
                            </div>
                            <div class="hover-preview-value text-danger"><?php echo (int)$row['overdue_days']; ?> days</div>
                        </div>
                    <?php endwhile; else: ?>
                        <div class="p-3 text-muted">No overdue delivery records.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card focus-card">
        <div class="card-header bg-white border-0 pb-0">
            <h3 class="card-title mb-0">Focus Now</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-3 col-sm-6 mb-3 mb-lg-0">
                    <div class="font-weight-bold mb-2 text-danger">Tasks</div>
                    <?php if($task_focus && $task_focus->num_rows): while($row = $task_focus->fetch_assoc()): ?>
                        <div class="focus-item">
                            <a href="./?page=tasks&id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a>
                            <div class="subtle"><?php echo date('d M, h:i A', strtotime($row['due_date'])); ?></div>
                        </div>
                    <?php endwhile; else: ?><div class="subtle">No urgent tasks</div><?php endif; ?>
                </div>

                <div class="col-lg-3 col-sm-6 mb-3 mb-lg-0">
                    <div class="font-weight-bold mb-2 text-warning">Payments</div>
                    <?php if($payment_focus && $payment_focus->num_rows): while($row = $payment_focus->fetch_assoc()): ?>
                        <div class="focus-item">
                            <a href="./?page=po_details/view_po_details&id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['po_code']); ?></a>
                            <div class="subtle">₹ <?php echo format_indian_number($row['pending_amount']); ?></div>
                        </div>
                    <?php endwhile; else: ?><div class="subtle">No pending payments</div><?php endif; ?>
                </div>

                <div class="col-lg-3 col-sm-6 mb-3 mb-lg-0">
                    <div class="font-weight-bold mb-2 text-info">Deliveries</div>
                    <?php if($delivery_focus && $delivery_focus->num_rows): while($row = $delivery_focus->fetch_assoc()): ?>
                        <div class="focus-item">
                            <a href="./?page=po_details/view_po_details&id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['po_code']); ?></a>
                            <div class="subtle"><?php echo (int)$row['overdue_days']; ?> days overdue</div>
                        </div>
                    <?php endwhile; else: ?><div class="subtle">No overdue deliveries</div><?php endif; ?>
                </div>

                <div class="col-lg-3 col-sm-6">
                    <div class="font-weight-bold mb-2 text-primary">Follow-ups</div>
                    <?php if($followup_focus && $followup_focus->num_rows): while($row = $followup_focus->fetch_assoc()): ?>
                        <div class="focus-item">
                            <a href="./?page=leads/view_lead&id=<?php echo $row['lead_id']; ?>"><?php echo htmlspecialchars((string)$row['company_name']); ?></a>
                            <div class="subtle"><?php echo date('h:i A', strtotime($row['next_followup'])); ?></div>
                        </div>
                    <?php endwhile; else: ?><div class="subtle">No follow-ups today</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    const $input = $('#universal-search');
    const $results = $('#universal-search-results');
    let searchTimer = null;

    function escapeHtml(text){
        return $('<div>').text(text === null || text === undefined ? '' : text).html();
    }

    function appendGroup(title, itemsHtml){
        if(!itemsHtml.length) return '';
        return '<div class="search-group">'
            + '<div class="search-group-title">'+title+'</div>'
            + itemsHtml.join('')
            + '</div>';
    }

    function renderResults(data){
        let html = '';

        const clients = (data.clients || []).map(function(row){
            return '<a class="search-item" href="./?page=clients/view_client&id='+row.id+'">'
                + '<div><strong>'+escapeHtml(row.company_name)+'</strong></div>'
                + '<div class="search-meta">Client • '+escapeHtml(row.contact_person || '')+'</div>'
                + '</a>';
        });

        const suppliers = (data.suppliers || []).map(function(row){
            return '<a class="search-item" href="./?page=maintenance/supplier">'
                + '<div><strong>'+escapeHtml(row.name)+'</strong></div>'
                + '<div class="search-meta">Supplier • '+escapeHtml(row.contact || '')+'</div>'
                + '</a>';
        });

        const pis = (data.proforma_invoices || []).map(function(row){
            return '<a class="search-item" href="./?page=proforma_invoice/view_pi&id='+row.id+'">'
                + '<div><strong>'+escapeHtml(row.po_code)+'</strong></div>'
                + '<div class="search-meta">PI • '+escapeHtml(row.company_name || '')+'</div>'
                + '</a>';
        });

        const pos = (data.po_details || []).map(function(row){
            return '<a class="search-item" href="./?page=po_details/view_po_details&id='+row.id+'">'
                + '<div><strong>'+escapeHtml(row.po_code)+'</strong></div>'
                + '<div class="search-meta">PO • '+escapeHtml(row.company_name || '')+' • '+escapeHtml(row.status || '')+'</div>'
                + '</a>';
        });

        const itemPOs = (data.item_purchase_orders || []).map(function(row){
            const company = row.company ? '&company=' + encodeURIComponent(row.company) : '';
            return '<a class="search-item" href="./?page=purchase_order/view_po&id='+row.id+company+'">'
                + '<div><strong>'+escapeHtml(row.po_code)+'</strong></div>'
                + '<div class="search-meta">Item • '+escapeHtml(row.item_name || '')+'</div>'
                + '<div class="search-meta">Supplier • '+escapeHtml(row.supplier_name || '')+'</div>'
                + '</a>';
        });

        html += appendGroup('Clients', clients);
        html += appendGroup('Suppliers', suppliers);
        html += appendGroup('Proforma Invoices', pis);
        html += appendGroup('PO Details', pos);
        html += appendGroup('Item Purchase Orders', itemPOs);

        if(!html){
            html = '<div class="search-item"><div class="search-meta">No results found</div></div>';
        } else {
            html = '<div class="search-grid">' + html + '</div>';
        }

        $results.html(html).show();
    }

    $input.on('input', function(){
        const q = $(this).val().trim();
        clearTimeout(searchTimer);

        if(q.length < 2){
            $results.hide().empty();
            return;
        }

        searchTimer = setTimeout(function(){
            $.ajax({
                url: _base_url_ + 'classes/Master.php?f=universal_search',
                method: 'POST',
                dataType: 'json',
                data: { q: q },
                success: function(resp){
                    if(resp && resp.status === 'success') renderResults(resp);
                    else $results.html('<div class="search-item"><div class="search-meta">Search failed</div></div>').show();
                },
                error: function(){
                    $results.html('<div class="search-item"><div class="search-meta">Search error</div></div>').show();
                }
            });
        }, 220);
    });

    $(document).on('click', function(e){
        if(!$(e.target).closest('.search-wrap').length){
            $results.hide();
        }
    });

    $input.on('focus', function(){
        if($results.children().length) $results.show();
    });

    const $hoverShell = $('#hover-preview-shell');
    const $hoverTitle = $('#hover-preview-title');
    let hoverHideTimer = null;

    function showHoverPreview(type, title){
        clearTimeout(hoverHideTimer);
        $('.hover-preview-content').addClass('d-none');
        if(type === 'payments') {
            $('#hover-preview-payments').removeClass('d-none');
        } else if(type === 'deliveries') {
            $('#hover-preview-deliveries').removeClass('d-none');
        }
        $hoverTitle.text(title || 'Details');
        $hoverShell.stop(true, true).fadeIn(120);
    }

    function hideHoverPreviewWithDelay(){
        clearTimeout(hoverHideTimer);
        hoverHideTimer = setTimeout(function(){
            $hoverShell.fadeOut(120);
        }, 180);
    }

    $('.kpi-card[data-preview]').on('mouseenter', function(){
        showHoverPreview($(this).data('preview'), $(this).data('preview-title'));
    }).on('mouseleave', function(){
        hideHoverPreviewWithDelay();
    });

    $hoverShell.on('mouseenter', function(){
        clearTimeout(hoverHideTimer);
    }).on('mouseleave', function(){
        hideHoverPreviewWithDelay();
    });
});
</script>