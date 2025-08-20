<?php
// upload.php - Handles file uploads separately

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_path = $upload_dir . $file_name;
        $relative_path = 'uploads/' . $file_name;

        // Allow all image types
        $file_type = mime_content_type($_FILES['image']['tmp_name']);

        if (strpos($file_type, 'image/') === 0) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                echo json_encode(['success' => true, 'path' => $relative_path]);
                exit;
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid file type.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}
?>
