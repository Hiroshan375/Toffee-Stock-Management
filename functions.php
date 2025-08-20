<?php
// Utility functions

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatPrice($price) {
    return number_format($price, 2);
}

function getTotalCost($quantity, $price) {
    return $quantity * $price;
}

// Function to handle image upload
function uploadImage($file, $existing_path = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return $existing_path;
    }
    
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($file['name']);
    $target_path = $upload_dir . $file_name;
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        return $existing_path;
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Delete old image if exists
        if ($existing_path && file_exists($existing_path)) {
            unlink($existing_path);
        }
        return $target_path;
    }
    
    return $existing_path;
}
?>
