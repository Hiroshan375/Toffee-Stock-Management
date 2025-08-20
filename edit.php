<?php
require_once 'config.php';
require_once 'functions.php';

$message = '';
$error = '';
$id = $_GET['id'] ?? 0;

if (!$id) {
    header("Location: index.php");
    exit();
}

$conn = getConnection();
$toffee = null;

// Fetch existing toffee
$stmt = $conn->prepare("SELECT * FROM toffees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$toffee = $result->fetch_assoc();
$stmt->close();

if (!$toffee) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $price = $_POST['price'] ?? 0;
    
    if (empty($name) || empty($quantity) || empty($price)) {
        $error = 'Please fill all required fields';
    } else {
        // Handle file upload
        $image_path = $toffee['image_path']; // Keep existing image
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_path = $upload_dir . $file_name;
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($_FILES['image']['tmp_name']);
            
            if (in_array($file_type, $allowed_types)) {
                // Delete old image if exists
                if ($toffee['image_path'] && file_exists($toffee['image_path'])) {
                    unlink($toffee['image_path']);
                }
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = $target_path;
                }
            }
        }
        
        $stmt = $conn->prepare("UPDATE toffees SET name = ?, quantity = ?, price = ?, image_path = ? WHERE id = ?");
        $stmt->bind_param("sidsi", $name, $quantity, $price, $image_path, $id);
        
        if ($stmt->execute()) {
            $message = 'Toffee updated successfully!';
            header("Location: index.php");
            exit();
        } else {
            $error = 'Error updating toffee: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Toffee</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Edit Toffee</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="toffee-form">
            <div class="form-group">
                <label for="name">Toffee Name *</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($toffee['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="quantity">Current Quantity *</label>
                <input type="number" id="quantity" name="quantity" value="<?php echo $toffee['quantity']; ?>" min="0" required>
                
            </div>
            
            <div class="form-group">
                <label for="price">Price per item (Rs.) *</label>
                <input type="number" id="price" name="price" value="<?php echo $toffee['price']; ?>" step="0.01" min="0" required>
                
            </div>
            
            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image" accept="image/*">
                <?php if ($toffee['image_path'] && file_exists($toffee['image_path'])): ?>
                    <div class="current-image">
                        <p>Current image:</p>
                        <img src="<?php echo $toffee['image_path']; ?>" alt="Current image" style="max-width: 200px; border: 1px solid #ddd; padding: 5px;">
                        <p style="font-size: 12px; color: #666;">Leave empty to keep current image</p>
                    </div>
                <?php else: ?>
                    <p style="font-size: 12px; color: #666;">No image uploaded</p>
                <?php endif; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Toffee</button>
                <a href="index.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
