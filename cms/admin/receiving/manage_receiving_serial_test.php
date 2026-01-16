<?php
// Quick test to see if form loads and responds to AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'test') {
        echo json_encode(['status' => 'ok', 'message' => 'Form is working']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
    }
    exit;
}
?>
<div class="container-fluid">
    <div class="alert alert-info">
        <h5>Serial Number Receiving System</h5>
        <p>Loading...</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="testForm()">Test Connection</button>
</div>

<script>
function testForm() {
    $.post('<?php echo base_url; ?>admin/?page=receiving/manage_receiving_serial', {action: 'test'}, function(response) {
        console.log(response);
        if (response.status === 'ok') {
            alert('Form is working!');
            location.reload();
        }
    }, 'json');
}
</script>
