<style>
    .table tbody tr {
        cursor: pointer;
        transition: background-color 0.3s ease;
        user-select: none;           /* Standard */
        -webkit-user-select: none;   /* Chrome/Safari */
        -moz-user-select: none;      /* Firefox */
        -ms-user-select: none;
    }

    .table tbody tr:hover {
        background-color: rgba(0,123,255,0.1);
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Leads Management</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-flat btn-default mr-2" id="filter_btn">
                <span class="fas fa-filter"></span> Filter
            </button>
            <a href="./?page=leads/manage_lead" class="btn btn-flat btn-primary">
                <span class="fas fa-plus"></span> Add New Lead
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <ul class="nav nav-tabs mb-3" id="leadTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="my-leads-tab" data-toggle="tab" href="#my-leads" role="tab">My Leads</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="all-leads-tab" data-toggle="tab" href="#all-leads" role="tab">All Leads</a>
                </li>
            </ul>
            <div class="tab-content" id="leadTabContent">
                <div class="tab-pane fade show active" id="my-leads" role="tabpanel">
                    <div class="mb-2">
                        <a href="<?php echo base_url.'admin/leads/export_my_leads.php'.($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : ''); ?>" class="btn btn-success" target="_blank">
                            <span class="fas fa-file-excel"></span> Export to Excel
                        </a>
                    </div>
                    <table class="table table-striped table-hover" id="my-leads-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Company</th>
                                <th>Activity</th>
                                <th>Contact Person</th>
                                <th>City</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Next Followup</th>
                                <th>First Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            $where = "1=1";

                            // Subquery to get first activity date for each lead
                            $first_activity_subquery = "(SELECT MIN(created_at) FROM lead_activities WHERE lead_id = l.id)";

                            // Filter by first activity date range
                            if(isset($_GET['date_from']) && !empty($_GET['date_from'])) {
                                $date_from = date("Y-m-d", strtotime($_GET['date_from']));
                                $where .= " AND ($first_activity_subquery IS NOT NULL AND DATE($first_activity_subquery) >= '{$date_from}')";
                            }
                            if(isset($_GET['date_to']) && !empty($_GET['date_to'])) {
                                $date_to = date("Y-m-d", strtotime($_GET['date_to']));
                                $where .= " AND ($first_activity_subquery IS NOT NULL AND DATE($first_activity_subquery) <= '{$date_to}')";
                            }
                            if(isset($_GET['status']) && !empty($_GET['status'])) {
                                $status = $conn->real_escape_string($_GET['status']);
                                $where .= " AND l.status = '{$status}'";
                            }

                            // Only leads with at least one activity
                            $qry = $conn->query("SELECT l.*, 
                                (SELECT next_followup 
                                    FROM lead_activities 
                                    WHERE lead_id = l.id 
                                    AND next_followup IS NOT NULL 
                                    ORDER BY next_followup DESC 
                                    LIMIT 1) as next_followup,
                                (SELECT MIN(created_at) 
                                    FROM lead_activities 
                                    WHERE lead_id = l.id) as first_activity_date,
                                (SELECT description 
                                    FROM lead_activities 
                                    WHERE lead_id = l.id 
                                    ORDER BY created_at ASC 
                                    LIMIT 1) as first_activity_description 
                                FROM leads l 
                                WHERE {$where}
                                AND EXISTS (SELECT 1 FROM lead_activities la WHERE la.lead_id = l.id)
                                ORDER BY l.created_at DESC");
                            while($row = $qry->fetch_assoc()):
                            ?>
                            <tr onclick="window.location='?page=leads/view_lead&id=<?php echo $row['id'] ?>'">
                                <td><?php echo $i++; ?></td>
                                <td><?php echo $row['company_name'] ?></td>
                                <td><?php echo $row['first_activity_description'] ? htmlspecialchars(substr($row['first_activity_description'], 0, 50)) . (strlen($row['first_activity_description']) > 50 ? '...' : '') : 'No Activity'; ?></td>
                                <td><?php echo $row['contact_person'] ?></td>
                                <td><?php echo $row['city'] ?></td>
                                <td><?php echo $row['source'] ?></td>
                                <td>
                                    <span class="badge badge-<?php echo getStatusColor($row['status']); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['next_followup'] ? date("d-M-Y h:i A", strtotime($row['next_followup'])) : 'N/A'; ?></td>
                                <td><?php echo $row['first_activity_date'] ? date("d-M-Y", strtotime($row['first_activity_date'])) : 'No Activity'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="all-leads" role="tabpanel">
                    <table class="table table-striped table-hover" id="all-leads-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Company</th>
                                <th>Contact Person</th>
                                <th>City</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            $where = "1=1";
                            if(isset($_GET['date_from']) && !empty($_GET['date_from'])) {
                                $date_from = date("Y-m-d", strtotime($_GET['date_from']));
                                $where .= " AND DATE(l.created_at) >= '{$date_from}'";
                            }
                            if(isset($_GET['date_to']) && !empty($_GET['date_to'])) {
                                $date_to = date("Y-m-d", strtotime($_GET['date_to']));
                                $where .= " AND DATE(l.created_at) <= '{$date_to}'";
                            }
                            if(isset($_GET['status']) && !empty($_GET['status'])) {
                                $status = $conn->real_escape_string($_GET['status']);
                                $where .= " AND l.status = '{$status}'";
                            }

                            // Only leads with NO activity
                            $qry = $conn->query("SELECT l.* 
                                FROM leads l 
                                WHERE {$where}
                                AND NOT EXISTS (SELECT 1 FROM lead_activities la WHERE la.lead_id = l.id)
                                ORDER BY l.created_at DESC");
                            while($row = $qry->fetch_assoc()):
                            ?>
                            <tr onclick="window.location='?page=leads/view_lead&id=<?php echo $row['id'] ?>'">
                                <td><?php echo $i++; ?></td>
                                <td><?php echo $row['company_name'] ?></td>
                                <td><?php echo $row['contact_person'] ?></td>
                                <td><?php echo $row['city'] ?></td>
                                <td><?php echo $row['source'] ?></td>
                                <td>
                                    <span class="badge badge-<?php echo getStatusColor($row['status']); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date("d-M-Y h:i A", strtotime($row['created_at'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function getStatusColor($status) {
    switch($status) {
        case 'new': return 'primary';
        case 'contacted': return 'info';
        case 'negotiation': return 'purple';
        case 'converted': return 'success';
        case 'closed': return 'dark';
        case 'lost': return 'danger';
        default: return 'secondary';
    }
}
?>
<script>
$(document).ready(function(){
    // Initialize DataTable for both tables
    var myLeadsTable = $('#my-leads-table').DataTable({
        "drawCallback": function(settings) {
            $('.dropdown-toggle').dropdown();
        }
    });
    var allLeadsTable = $('#all-leads-table').DataTable({
        "drawCallback": function(settings) {
            $('.dropdown-toggle').dropdown();
        }
    });

    // --- The following code is commented out to disable localStorage UI state ---
    /*
    // Restore active tab
    var lastTab = localStorage.getItem('leads_active_tab');
    if (lastTab) {
        $('#leadTabs a[href="' + lastTab + '"]').tab('show');
    }

    // Restore DataTable page
    var myLeadsPage = localStorage.getItem('my_leads_page');
    if (myLeadsPage) {
        myLeadsTable.page(parseInt(myLeadsPage)).draw('page');
    }
    var allLeadsPage = localStorage.getItem('all_leads_page');
    if (allLeadsPage) {
        allLeadsTable.page(parseInt(allLeadsPage)).draw('page');
    }

    // Save tab and page on tab change
    $('#leadTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var tabId = $(e.target).attr('href');
        localStorage.setItem('leads_active_tab', tabId);

        // Save current page of the active table
        if(tabId === '#my-leads') {
            localStorage.setItem('my_leads_page', myLeadsTable.page());
        } else if(tabId === '#all-leads') {
            localStorage.setItem('all_leads_page', allLeadsTable.page());
        }
    });

    // Save DataTable page on page change
    myLeadsTable.on('page', function() {
        localStorage.setItem('my_leads_page', myLeadsTable.page());
    });
    allLeadsTable.on('page', function() {
        localStorage.setItem('all_leads_page', allLeadsTable.page());
    });

    // Save scroll position before leaving the page
    window.addEventListener('beforeunload', function() {
        localStorage.setItem('leads_scroll', window.scrollY);
    });

    // Restore scroll position on page load
    window.addEventListener('DOMContentLoaded', function() {
        var scroll = localStorage.getItem('leads_scroll');
        if(scroll !== null) {
            window.scrollTo(0, parseInt(scroll));
        }
    });
    */
    // --- End of commented code ---

    // Filter functionality
    $('#filter_btn').click(function(){
        $('#filterModal').modal('show');
    });
    
    $('#apply_filter').click(function(){
        var date_from = $('#date_from').val();
        var date_to = $('#date_to').val();
        var status = $('#status_filter').val();
        
        var currentUrl = './?page=leads';
        var params = [];
        
        if(date_from) params.push('date_from=' + date_from);
        if(date_to) params.push('date_to=' + date_to);
        if(status) params.push('status=' + status);
        
        window.location.href = currentUrl + (params.length ? '&' + params.join('&') : '');
        $('#filterModal').modal('hide');
    });

    $('#reset_filter').click(function(){
        window.location.href = './?page=leads';
        $('#filterModal').modal('hide');
    });

    // Set filter values from URL parameters
    var urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('date_from')) $('#date_from').val(urlParams.get('date_from'));
    if(urlParams.has('date_to')) $('#date_to').val(urlParams.get('date_to'));
    if(urlParams.has('status')) $('#status_filter').val(urlParams.get('status'));
});
</script>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filter Leads</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="filter_form">
                    <div class="form-group">
                        <label for="date_from">Date From:</label>
                        <input type="date" class="form-control" id="date_from">
                    </div>
                    <div class="form-group">
                        <label for="date_to">Date To:</label>
                        <input type="date" class="form-control" id="date_to">
                    </div>
                    <div class="form-group">
                        <label for="status_filter">Status:</label>
                        <select class="form-control" id="status_filter">
                            <option value="">All Status</option>
                            <option value="new">New</option>
                            <option value="contacted">Contacted</option>
                            <option value="negotiation">Negotiation</option>
                            <option value="converted">Converted</option>
                            <option value="closed">Closed</option>
                            <option value="lost">Lost</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="reset_filter">Reset</button>
                <button type="button" class="btn btn-primary" id="apply_filter">Apply Filter</button>
            </div>
        </div>
    </div>
</div>