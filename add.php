<?php
require_once 'config.php';
require_once 'functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $price = $_POST['price'] ?? 0;
    
    if (empty($name) || empty($quantity) || empty($price)) {
        $error = 'Please fill all required fields';
    } else {
        $conn = getConnection();
        
        // Handle file upload
        $image_path = '';
        // Use image_path from POST if available
        $image_path = $_POST['image_path'] ?? '';

        if ($image_path === '') {
            $image_path = null;
        }

        // If no image_path from POST, try to upload file directly (fallback)
        if ($image_path === null && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_path = $upload_dir . $file_name;
            $relative_path = 'uploads/' . $file_name;

            $file_type = mime_content_type($_FILES['image']['tmp_name']);
            if (strpos($file_type, 'image/') === 0) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = $relative_path;
                }
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO toffees (name, quantity, price, image_path) VALUES (?, ?, ?, ?)");
        // Store relative path for image_path
        $relative_image_path = $image_path ? $image_path : null;
        $stmt->bind_param("sids", $name, $quantity, $price, $relative_image_path);
        
        if ($stmt->execute()) {
            $message = 'Toffee added successfully!';
            header("Location: index.php");
            exit();
        } else {
            $error = 'Error adding toffee: ' . $conn->error;
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Toffee</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Add New Toffee</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="toffee-form">
            <div class="form-group">
                <label for="name">Toffee Name *</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="quantity">Quantity *</label>
                <input type="number" id="quantity" name="quantity" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="price">Price per item *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="image">Image</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Toffee</button>
                <button type="button" class="btn btn-cancel" onclick="window.location.href='index.php'">Cancel</button>
            </div>
</form>
</div>

<script>
var imageInput = document.getElementById('image');
var imagePathInput = document.getElementById('image_path');

if (imageInput) {
    imageInput.addEventListener('change', function() {
        var fileInput = this;
        var formData = new FormData();
        formData.append('image', fileInput.files[0]);

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (imagePathInput) {
                    imagePathInput.value = data.path;
                }
            } else {
                alert('Image upload failed: ' + data.error);
                fileInput.value = '';
            }
        })
        .catch(error => {
            alert('Error uploading image: ' + error);
            fileInput.value = '';
        });
    });
}

document.getElementById('toffeeForm').addEventListener('submit', function(e) {
    var imagePath = document.getElementById('image_path').value;
    if (!imagePath && document.getElementById('image').files.length > 0) {
        e.preventDefault();
        alert('Please wait for the image to finish uploading.');
    }
});
</script>

</body>
</html>
