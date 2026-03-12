
<?php 
if(isset($_GET['id']) && $_GET['id'] > 0){
    $user = $conn->query("SELECT * FROM users where id ='{$_GET['id']}'");
    foreach($user->fetch_array() as $k =>$v){
        $meta[$k] = $v;
    }
}

$editing_user_id = isset($meta['id']) ? (int)$meta['id'] : 0;
$selected_type = isset($meta['type']) ? (int)$meta['type'] : 2;
$assignable_modules = cms_assignable_user_modules();
$assignable_catalog = array_intersect_key(cms_module_catalog(), array_flip($assignable_modules));
$selected_modules = array();
if($editing_user_id > 0){
    $selected_modules = cms_get_user_access_modules($conn, $editing_user_id, $selected_type);
}else{
	$selected_modules = $assignable_modules;
}
$selected_modules_lookup = array_flip($selected_modules);
?>
<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
	<div class="card-body">
		<div class="container-fluid">
			<div id="msg"></div>
			<form action="" id="manage-user">	
				<input type="hidden" name="id" value="<?php echo isset($meta['id']) ? $meta['id']: '' ?>">
				<div class="form-group col-6">
					<label for="name">First Name</label>
					<input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo isset($meta['firstname']) ? $meta['firstname']: '' ?>" required>
				</div>
				<div class="form-group col-6">
					<label for="name">Last Name</label>
					<input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo isset($meta['lastname']) ? $meta['lastname']: '' ?>" required>
				</div>
				<div class="form-group col-6">
					<label for="username">Username</label>
					<input type="text" name="username" id="username" class="form-control" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>" required  autocomplete="off">
				</div>
				<div class="form-group col-6">
					<label for="password">Password</label>
					<input type="password" name="password" id="password" class="form-control" value="" autocomplete="off" <?php echo isset($meta['id']) ? "": 'required' ?>>
                    <?php if(isset($_GET['id'])): ?>
					<small class="text-info"><i>Leave this blank if you dont want to change the password.</i></small>
                    <?php endif; ?>
				</div>
				<div class="form-group col-6">
					<label for="type">User Type</label>
					<select name="type" id="type" class="custom-select" value="<?php echo isset($meta['type']) ? $meta['type']: '' ?>" required>
						<option value="1" <?php echo $selected_type === 1 ? 'selected': '' ?>>Admin</option>
						<option value="2" <?php echo $selected_type === 2 ? 'selected': '' ?>>User</option>
					</select>
				</div>
				<div class="form-group col-12" id="module-access-wrap">
					<label class="mb-2">Module Access (for User type)</label>
					<div class="border rounded p-3" style="max-height: 220px; overflow-y:auto;">
						<div class="row">
							<?php foreach($assignable_catalog as $module_key => $module_label): ?>
							<div class="col-md-4 col-sm-6">
								<div class="custom-control custom-checkbox mb-2">
									<input class="custom-control-input module-access-input" type="checkbox" id="module_<?php echo $module_key ?>" name="access_modules[]" value="<?php echo $module_key ?>" <?php echo isset($selected_modules_lookup[$module_key]) ? 'checked' : '' ?>>
									<label for="module_<?php echo $module_key ?>" class="custom-control-label"><?php echo $module_label ?></label>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
					<small class="text-muted">Admins always have complete access. For User type, choose modules to allow.</small>
				</div>
				<div class="form-group col-6">
					<label for="" class="control-label">Avatar</label>
					<div class="custom-file">
		              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))">
		              <label class="custom-file-label" for="customFile">Choose file</label>
		            </div>
				</div>
				<div class="form-group col-6 d-flex justify-content-center">
					<img src="<?php echo validate_image(isset($meta['avatar']) ? $meta['avatar'] :'') ?>" alt="" id="cimg" class="img-fluid img-thumbnail">
				</div>
			</form>
		</div>
	</div>
	<div class="card-footer">
			<div class="col-md-12">
				<div class="row">
					<button class="btn btn-sm btn-primary mr-2" form="manage-user">Save</button>
					<a class="btn btn-sm btn-secondary" href="./?page=user/list">Cancel</a>
				</div>
			</div>
		</div>
</div>
<style>
	img#cimg{
		height: 15vh;
		width: 15vh;
		object-fit: cover;
		border-radius: 100% 100%;
	}
</style>
<script>
	$(function(){
		$('.select2').select2({
			width:'resolve'
		})
		toggleModuleAccessByType();
	})

	function toggleModuleAccessByType(){
		var selectedType = $('#type').val();
		if(selectedType === '1'){
			$('#module-access-wrap').hide();
			$('.module-access-input').prop('disabled', true);
		}else{
			$('#module-access-wrap').show();
			$('.module-access-input').prop('disabled', false);
		}
	}

	$('#type').on('change', function(){
		toggleModuleAccessByType();
	});
	function displayImg(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	$('#cimg').attr('src', e.target.result);
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
	$('#manage-user').submit(function(e){
		e.preventDefault();
		var _this = $(this)
		start_loader()
		$.ajax({
			url:_base_url_+'classes/Users.php?f=save',
			data: new FormData($(this)[0]),
		    cache: false,
		    contentType: false,
		    processData: false,
		    method: 'POST',
		    type: 'POST',
			success:function(resp){
				if(resp ==1){
					location.href = './?page=user/list';
				}else{
					$('#msg').html('<div class="alert alert-danger">Username already exist</div>')
					$("html, body").animate({ scrollTop: 0 }, "fast");
				}
                end_loader()
			}
		})
	})

</script>