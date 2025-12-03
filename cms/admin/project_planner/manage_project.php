<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

global $conn;
if(!isset($conn) || !$conn){
	echo "<div class='alert alert-danger'>Database connection (\$conn) is not available. Check config.php and DB connection.</div>";
	return;
}
if(isset($_GET['id']) && $_GET['id'] > 0){
	$id = intval($_GET['id']);
	$sql = "SELECT * FROM `project_planner` WHERE id = '{$id}' LIMIT 1";
	$qry = $conn->query($sql);
	if($qry === false){
		echo "<div class='alert alert-danger'>Database query error: " . htmlspecialchars($conn->error) . "</div>";
		return;
	}
	if($qry->num_rows > 0){
		foreach($qry->fetch_assoc() as $k => $v){
			$$k=$v;
		}

        // Fetch associated POs for an existing project
        $po_ids = [];
        if(isset($id)){
            // Fetch Supplier POs
            $po_qry = $conn->query("SELECT po_id FROM `project_po_list` WHERE project_id = '{$id}'");
            $supplier_pos = $po_qry->fetch_all(MYSQLI_ASSOC);
            $po_ids = array_column($supplier_pos, 'po_id');
        }
	}
}
?>
<div class="card card-outline card-info">
	<div class="card-header">
		<h3 class="card-title"><?php echo isset($id) ? "Update Project Details": "New Project" ?></h3>
	</div>
	<div class="card-body">
		<form action="" id="project-form">
			<input type="hidden" name ="id" value="<?php echo isset($id) ? $id : '' ?>">
			<div class="form-group">
				<label for="name" class="control-label">Project Name</label>
				<input type="text" name="name" id="name" class="form-control rounded-0" value="<?php echo isset($name) ? $name : ''; ?>" required>
			</div>
			<div class="form-group">
				<label for="client_id" class="control-label">Client</label>
				<select name="client_id" id="client_id" class="form-control select2" required>
					<option value="" disabled selected>Select a client</option>
					<?php 
						$client_qry = $conn->query("SELECT id, company_name FROM `clients` order by `company_name` asc");
						while($row = $client_qry->fetch_assoc()):
					?>
					<option value="<?php echo $row['id'] ?>" <?php echo isset($client_id) && $client_id == $row['id'] ? 'selected' : '' ?>><?php echo $row['company_name'] ?></option>
					<?php endwhile; ?>
				</select>
			</div>
			<div class="form-group">
				<label for="description" class="control-label">Description</label>
				<textarea name="description" id="description" class="form-control form-control-sm rounded-0 summernote"><?php echo isset($description) ? $description : '' ?></textarea>
			</div>
			<div class="form-group">
				<label for="po_ids" class="control-label">Supplier Purchase Orders</label>
				<select name="supplier_po_ids[]" id="po_ids" class="form-control select2" multiple="multiple">
					<?php 
						// Exclude POs that are already assigned to another project
						$current_project_id = isset($id) ? $id : 0;
						$po_qry = $conn->query("SELECT pol.id, pol.po_code FROM `purchase_order_list` pol 
												LEFT JOIN `project_po_list` ppl ON pol.id = ppl.po_id
												WHERE ppl.project_id IS NULL OR ppl.project_id = '{$current_project_id}'
												ORDER BY pol.po_code ASC");
						while($row = $po_qry->fetch_assoc()):
					?>
					<option value="<?php echo $row['id'] ?>" <?php echo isset($po_ids) && in_array($row['id'], $po_ids) ? 'selected' : '' ?>><?php echo $row['po_code'] ?></option>
					<?php endwhile; ?>
				</select>
			</div>
			<div id="po-items-container">
				<!-- PO items will be loaded here via AJAX -->
				<div class="text-center text-muted">Select a Purchase Order to see its items.</div>
			</div>
			<style>
				#po-items-container .card-body { padding: 0.5rem; }
				#po-items-container .table { margin-bottom: 0; }
			</style>
		</form>
	</div>
	<div class="card-footer">
		<button class="btn btn-flat btn-primary" form="project-form">Save</button>
		<a class="btn btn-flat btn-default" href="?page=project_planner">Cancel</a>
	</div>
</div>
<script>
  
	$(document).ready(function(){
        $('#client_id').select2({
            placeholder:"Select a client",
            width: '100%',
            allowClear: true,
        });
        $('#po_ids').select2({
            placeholder:"Select Purchase Order(s)",
            width: '100%',
            allowClear: true,
        });
        $('.summernote').summernote({
		        height: 250,
		        toolbar: [
		            [ 'style', [ 'style' ] ],
		            [ 'font', [ 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear'] ],
		            [ 'fontname', [ 'fontname' ] ],
		            [ 'fontsize', [ 'fontsize' ] ],
		            [ 'color', [ 'color' ] ],
		            [ 'para', [ 'ol', 'ul', 'paragraph', 'height' ] ],
		            [ 'table', [ 'table' ] ],
		            [ 'view', [ 'undo', 'redo', 'fullscreen', 'codeview', 'help' ] ]
		        ]
        })

        function load_po_items(){
            var supplier_po_ids = $('#po_ids').val();
            if(supplier_po_ids && supplier_po_ids.length > 0){
                start_loader();
                $.ajax({
                    url: _base_url_ + "classes/Master.php?f=get_po_items",
                    method: "POST",
                    data: { supplier_po_ids: supplier_po_ids },
                    dataType: "json",
                    error: err => {
                        console.log(err);
                        alert_toast("An error occurred while fetching PO items.", "error");
                        end_loader();
                    },
                    success: function(resp){
                        if(resp.status == 'success' && resp.html.trim() !== ""){
                            $('#po-items-container').html(resp.html);
                        } else if (resp.status == 'success') {
                            $('#po-items-container').html('<div class="text-center text-muted">No items found for the selected Purchase Orders.</div>');
                        } else {
                            alert_toast("Failed to load PO items.", "error");
                        }
                        end_loader();
                    }
                });
            } else {
                $('#po-items-container').html('<div class="text-center text-muted">Select a Purchase Order to see its items.</div>');
            }
        }

        // Load items on page load if POs are already selected
        if($('#po_ids').val().length > 0){
            load_po_items();
        }

        $('#po_ids').on('change', function(){
            load_po_items();
        });

		$('#project-form').submit(function(e){
			e.preventDefault();
            var _this = $(this)
			 $('.err-msg').remove();
			start_loader();
			$.ajax({
				url:_base_url_+"classes/Master.php?f=save_project",
				data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
				error:err=>{
					console.log(err)
					alert_toast("An error occured",'error');
					end_loader();
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						location.href = _base_url_+"admin/?page=project_planner/view_project&id="+resp.project_id;
					}else{
						alert_toast("An error occured",'error');
						end_loader()
					}
				}
			})
		})
	})

</script>