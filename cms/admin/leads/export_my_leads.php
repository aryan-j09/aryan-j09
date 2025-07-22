<?php

require_once(dirname(__DIR__, 2) . '/config.php'); 

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=my_leads_export_" . date('Ymd_His') . ".xls");

$where = "1=1";

// Subquery for first activity date
$first_activity_subquery = "(SELECT MIN(created_at) FROM lead_activities WHERE lead_id = l.id)";

// Filter by first activity date range (just like index.php)
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
$qry = $conn->query("
    SELECT 
        l.company_name,
        la.created_at AS latest_activity_date,
        la.description AS latest_activity_description,
        CONCAT(u.firstname, ' ', u.lastname) AS latest_activity_user,
        l.contact_person,
        (SELECT MIN(created_at) FROM lead_activities WHERE lead_id = l.id) as first_activity_date,
        (SELECT description FROM lead_activities WHERE lead_id = l.id ORDER BY created_at ASC LIMIT 1) as first_activity_description
    FROM leads l
    INNER JOIN (
        SELECT la1.*
        FROM lead_activities la1
        INNER JOIN (
            SELECT lead_id, MAX(created_at) AS max_created
            FROM lead_activities
            GROUP BY lead_id
        ) la2 ON la1.lead_id = la2.lead_id AND la1.created_at = la2.max_created
    ) la ON la.lead_id = l.id
    LEFT JOIN users u ON la.created_by = u.id
    WHERE {$where}
    AND EXISTS (SELECT 1 FROM lead_activities la2 WHERE la2.lead_id = l.id)
    ORDER BY l.id DESC
");

echo "<table border='1'>";
echo "<tr>
        <th>Company Name</th>
        <th>Contact Person</th>
        <th>First Activity Date</th>
        <th>First Activity Description</th>
        <th>Latest Activity Date</th>
        <th>Latest Activity Time</th>
        <th>Latest Activity Description</th>
        <th>Logged By</th>        
    </tr>";

while($row = $qry->fetch_assoc()) {
    $first_activity_date = $row['first_activity_date'] ? date("d-M-Y", strtotime($row['first_activity_date'])) : '';
    $first_activity_desc = $row['first_activity_description'] ?? '';
    $latest_activity_date = $row['latest_activity_date'] ? date("d-M-Y", strtotime($row['latest_activity_date'])) : '';
    $latest_activity_time = $row['latest_activity_date'] ? date("h:i A", strtotime($row['latest_activity_date'])) : '';
    $latest_activity_desc = $row['latest_activity_description'] ?? '';
    $activity_user = $row['latest_activity_user'] ?? '';
    echo "<tr>";
    echo "<td>".htmlspecialchars($row['company_name'])."</td>";
    echo "<td>".htmlspecialchars($row['contact_person'])."</td>";
    echo "<td>".htmlspecialchars($first_activity_date)."</td>";
    echo "<td>".htmlspecialchars($first_activity_desc)."</td>";
    echo "<td>".htmlspecialchars($latest_activity_date)."</td>";
    echo "<td>".htmlspecialchars($latest_activity_time)."</td>";
    echo "<td>".htmlspecialchars($latest_activity_desc)."</td>";
    echo "<td>".htmlspecialchars($activity_user)."</td>";    
    echo "</tr>";
}
echo "</table>";
?>