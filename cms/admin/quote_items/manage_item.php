<?php
$id = isset($_GET['id']) ? $_GET['id'] : '';
$title = $id ? "Edit Item" : "Add New Item";

if(isset($_POST['submit'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    
    // Handle multiple image uploads
    $image_paths = [];
    if(!empty($_FILES['item_images']['name'][0])) {
        // Absolute path for file storage
        $target_dir = dirname(dirname(dirname(__FILE__))) . '/uploads/quote_files/';
        
        // Create directory if it doesn't exist
        if(!is_dir($target_dir)) {
            if(!mkdir($target_dir, 0777, true)) {
                throw new Exception("Failed to create upload directory: " . $target_dir);
            }
        }
        
        foreach($_FILES['item_images']['tmp_name'] as $key => $tmp_name) {
            if($_FILES['item_images']['error'][$key] == 0) {
                $file_extension = strtolower(pathinfo($_FILES["item_images"]["name"][$key], PATHINFO_EXTENSION));
                $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                // Ensure only images are uploaded
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if(!in_array($file_extension, $allowed_types)) {
                    continue;
                }
                
                // Move the uploaded file
                if(move_uploaded_file($tmp_name, $target_file)) {
                    // Store relative path for database
                    $image_paths[] = "uploads/quote_files/" . $new_filename;
                    
                    // Set proper permissions
                    chmod($target_file, 0644);
                } else {
                    error_log("Failed to move uploaded file: " . error_get_last()['message']);
                }
            }
        }
    }
    $image_path = !empty($image_paths) ? implode(',', $image_paths) : '';

    if($id) {
        if($image_path) {
            $stmt = $conn->prepare("UPDATE quote_items SET name=?, description=?, image_path=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $description, $image_path, $id);
        } else {
            $stmt = $conn->prepare("UPDATE quote_items SET name=?, description=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $description, $id);
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO quote_items (name, description, image_path) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $description, $image_path);
    }
    
    if($stmt->execute()) {
        $item_id = $id ?: $conn->insert_id;
        
        // Handle attributes
        if(isset($_POST['attr_name']) && isset($_POST['attr_value'])) {
            if($id) $conn->query("DELETE FROM quote_item_attributes WHERE quote_item_id=$item_id");
            
            $attr_stmt = $conn->prepare("INSERT INTO quote_item_attributes (quote_item_id, attribute_name, attribute_value) VALUES (?, ?, ?)");
            foreach($_POST['attr_name'] as $key => $attr_name) {
                if(empty($attr_name)) continue;
                $attr_value = $_POST['attr_value'][$key];
                $attr_stmt->bind_param("iss", $item_id, $attr_name, $attr_value);
                $attr_stmt->execute();
            }
        }
        
        // Handle prices
        if(isset($_POST['price_desc']) && isset($_POST['price_amount'])) {
            if($id) $conn->query("DELETE FROM quote_item_prices WHERE quote_item_id=$item_id");
            
            $price_stmt = $conn->prepare("INSERT INTO quote_item_prices (quote_item_id, description, price) VALUES (?, ?, ?)");
            foreach($_POST['price_desc'] as $key => $price_desc) {
                if(empty($price_desc)) continue;
                $price_amount = filter_var($_POST['price_amount'][$key], FILTER_VALIDATE_FLOAT);
                // Validate price is within acceptable range
                if($price_amount === false || $price_amount < 0 || $price_amount > 999999.99) {
                    continue; // Skip invalid prices
                }
                $price_stmt->bind_param("isd", $item_id, $price_desc, $price_amount);
                $price_stmt->execute();
            }
        }
        
        // Handle accessories
        if(isset($_POST['acc_name']) && isset($_POST['acc_price'])) {
            if($id) $conn->query("DELETE FROM quote_item_accessories WHERE quote_item_id=$item_id");
            
            $acc_stmt = $conn->prepare("INSERT INTO quote_item_accessories (quote_item_id, name, price) VALUES (?, ?, ?)");
            foreach($_POST['acc_name'] as $key => $acc_name) {
                if(empty($acc_name)) continue;
                $acc_price = filter_var($_POST['acc_price'][$key], FILTER_VALIDATE_FLOAT);
                // Validate price is within acceptable range
                if($acc_price === false || $acc_price < 0 || $acc_price > 999999.99) {
                    continue; // Skip invalid prices
                }
                $acc_stmt->bind_param("isd", $item_id, $acc_name, $acc_price);
                $acc_stmt->execute();
            }
        }
        exit;
    }
}

// Fetch item data if editing
$item = null;
if($id) {
    $result = $conn->query("SELECT * FROM quote_items WHERE id=$id");
    $item = $result->fetch_assoc();
    
    $attributes = $conn->query("SELECT * FROM quote_item_attributes WHERE quote_item_id=$id");
    $prices = $conn->query("SELECT * FROM quote_item_prices WHERE quote_item_id=$id");
    $accessories = $conn->query("SELECT * FROM quote_item_accessories WHERE quote_item_id=$id");
}
?>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><?php echo $title; ?></h3>
    </div>
    
    <form id="item-form" method="post" enctype="multipart/form-data">
        <?php if($id): ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php endif; ?>
        <div class="card-body">
            <ul class="nav nav-tabs" id="itemTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab">Basic Details</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="specs-tab" data-toggle="tab" href="#specs" role="tab">Technical Specifications</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pricing-tab" data-toggle="tab" href="#pricing" role="tab">Pricing Details</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="accessories-tab" data-toggle="tab" href="#accessories" role="tab">Accessories</a>
                </li>
            </ul>

            <div class="tab-content p-3" id="itemTabsContent">
                <!-- Basic Details Tab -->
                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo $item ? $item['name'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo $item ? $item['description'] : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Images</label>
                        <div id="image_container">
                            <div class="row mb-2">
                                <div class="col-5">
                                    <input type="file" name="item_images[]" accept="image/*" onchange="updateFileName(this)">
                                </div>
                                <div class="col-5">
                                    <input type="text" name="image_descriptions[]" class="form-control" placeholder="Image Description">
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-danger btn-sm remove-btn" style="display:none">Remove</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addImageField()">Add Image</button>
                        
                        <?php if($item): ?>
                        <div class="mt-3">
                            <label>Current Images:</label>
                            <div class="d-flex flex-wrap">
                                <?php 
                                $images = $conn->query("SELECT * FROM quote_item_images WHERE quote_item_id = $id");
                                while($img = $images->fetch_assoc()): 
                                ?>
                                <div class="d-flex align-items-center position-relative mr-2 mb-2 p-2 border rounded bg-light" style="min-width:180px; min-height:48px;">
    <div class="flex-grow-1 text-center small text-muted" style="word-break:break-word;">
        <?php echo htmlspecialchars($img['description']); ?>
    </div>
    <button type="button" class="btn btn-danger btn-sm ml-2 d-flex align-items-center justify-content-center"
            style="height:32px; width:32px;"
            onclick="deleteImage(<?php echo $img['id']; ?>, this)">
        <i class="fa fa-times"></i>
    </button>
</div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Technical Specifications Tab -->
                <div class="tab-pane fade" id="specs" role="tabpanel">
                    <div id="specifications_container">
                        <?php if($id): 
                            $attributes = $conn->query("SELECT * FROM quote_item_attributes WHERE quote_item_id = $id");
                            while($attr = $attributes->fetch_assoc()): 
                        ?>
                        <div class="row mb-2">
                            <div class="col-3">
                                <input type="text" name="attr_name[]" class="form-control" value="<?php echo htmlspecialchars($attr['attribute_name']); ?>">
                            </div>
                            <div class="col-7">
                                <textarea name="attr_value[]" class="tinymce-editor"><?php echo $attr['attribute_value']; ?></textarea>
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-danger btn-sm remove-btn">Remove</button>
                            </div>
                        </div>
                        <?php endwhile; endif; ?>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addAttribute()">Add Specification</button>
                </div>

                <!-- Pricing Details Tab -->
                <div class="tab-pane fade" id="pricing" role="tabpanel">
                    <div id="prices_container">
                        <?php 
                        if($id) {
                            $prices = $conn->query("SELECT * FROM quote_item_prices WHERE quote_item_id = $id");
                            while($price = $prices->fetch_assoc()): 
                        ?>
                        <div class="row mb-2">
                            <div class="col-3">
                                <input type="number" name="price_amount[]" class="form-control" value="<?php echo $price['price']; ?>">
                            </div>
                            <div class="col-7">
                                <textarea name="price_desc[]" class="tinymce-editor"><?php echo $price['description']; ?></textarea>
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-danger btn-sm remove-btn">Remove</button>
                            </div>
                        </div>
                        <?php 
                            endwhile; 
                        }
                        ?>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addPrice()">Add Price Option</button>
                </div>

                <!-- Accessories Tab -->
                <!-- Replace the Accessories Tab content -->
<div class="tab-pane fade" id="accessories" role="tabpanel">
    <div id="accessories_container">
        <?php if($id): 
            $accessories = $conn->query("SELECT * FROM quote_item_accessories WHERE quote_item_id = $id");
            while($acc = $accessories->fetch_assoc()): 
        ?>
        <div class="row mb-2">
            <div class="col-3">
                <input type="number" name="acc_price[]" class="form-control" value="<?php echo $acc['price']; ?>" placeholder="Price">
            </div>
            <div class="col-7">
                <textarea name="acc_name[]" class="tinymce-editor"><?php echo $acc['name']; ?></textarea>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger btn-sm remove-btn">Remove</button>
            </div>
        </div>
        <?php endwhile; endif; ?>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="addAccessory()">Add Accessory</button>
</div>
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" name="submit" class="btn btn-primary">Save</button>
            <a href="?page=quote_items" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
// Initialize Summernote for all editors
$(document).ready(function() {
    // Initialize Summernote for all editors on page load
    initSummernote();
});

function initSummernote(selector = '.tinymce-editor') {
    $(selector).summernote({
        height: 250,
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
}

function addAttribute() {
    const html = `
        <div class="row mb-2">
            <div class="col-3">
                <input type="text" name="attr_name[]" class="form-control" placeholder="Specification Name" required>
            </div>
            <div class="col-7">
                <textarea name="attr_value[]" class="tinymce-editor" required></textarea>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger btn-sm remove-btn">Remove</button>
            </div>
        </div>
    `;
    $('#specifications_container').append(html);
    initSummernote('#specifications_container .tinymce-editor:last');
}

function addPrice() {
    const html = `
        <div class="row mb-2">
            <div class="col-3">
                <input type="number" name="price_amount[]" class="form-control" placeholder="Amount" required>
            </div>
            <div class="col-7">
                <textarea name="price_desc[]" class="tinymce-editor" required></textarea>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger btn-sm remove-btn">Remove</button>
            </div>
        </div>
    `;
    $('#prices_container').append(html);
    initSummernote('#prices_container .tinymce-editor:last');
}

function addAccessory() {
    const html = `
        <div class="row mb-2">
            <div class="col-3">
                <input type="number" name="acc_price[]" class="form-control" placeholder="Price" required>
            </div>
            <div class="col-7">
                <textarea name="acc_name[]" class="tinymce-editor" required></textarea>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger btn-sm remove-btn">Remove</button>
            </div>
        </div>
    `;
    $('#accessories_container').append(html);
    initSummernote('#accessories_container .tinymce-editor:last');
}

// Replace the addImageField function
function addImageField() {
    const html = `
        <div class="row mb-2">
            <div class="col-5">
                <input type="file" name="item_images[]" accept="image/*" onchange="updateFileName(this)">
            </div>
            <div class="col-5">
                <input type="text" name="image_descriptions[]" class="form-control" placeholder="Image Description">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger btn-sm remove-btn">Remove</button>
            </div>
        </div>
    `;
    document.getElementById('image_container').insertAdjacentHTML('beforeend', html);
    
    // Show remove button for first field if there's more than one
    const fields = document.getElementById('image_container').children;
    if(fields.length > 1) {
        fields[0].querySelector('.remove-btn').style.display = 'block';
    }
}

// Add the deleteImage function
function deleteImage(imageId, button) {
    if(confirm('Are you sure you want to delete this image?')) {
        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=delete_quote_image',
            type: 'POST',
            data: { id: imageId },
            dataType: 'json',
            success: function(resp) {
                if(resp.status == 'success') {
                    $(button).closest('.position-relative').remove();
                    alert_toast(resp.msg, 'success');
                } else {
                    alert_toast(resp.msg, 'error');
                }
            },
            error: function(xhr, status, error) {
                alert_toast('An error occurred: ' + error, 'error');
                console.log(xhr.responseText);
            }
        });
    }
}

// Update the form submission to handle TinyMCE content
$('#item-form').submit(function(e) {
    e.preventDefault();

    // Save Summernote content before form submission
    // (Summernote already updates textarea, but for safety, you can do this)
    $('.tinymce-editor').each(function() {
        var $this = $(this);
        $this.val($this.summernote('code'));
    });

    var formData = new FormData(this);

    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=save_quote_item',
        type: 'POST',
        data: formData,
        processData: false, 
        contentType: false,
        dataType: 'json',
        success: function(resp) {
            if(resp.status == 'success') {
                location.href = resp.redirect;
            } else {
                alert_toast(resp.msg, 'error');
            }
        },
        error: function(xhr, status, error) {
            alert_toast('An error occurred: ' + error, 'error');
            console.log(xhr.responseText);
        }
    });
});

// Fix for remove buttons - using event delegation
document.addEventListener('click', function(e) {
    if(e.target && e.target.classList.contains('remove-btn')) {
        const row = e.target.closest('.row');
        // Destroy Summernote instance before removing
        $(row).find('.tinymce-editor').each(function() {
            if ($(this).hasClass('summernote-initialized')) {
                $(this).summernote('destroy');
            }
        });
        row.remove();
    }
});
</script>