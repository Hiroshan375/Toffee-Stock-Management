<?php
require_once 'config.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    $conn = getConnection();
    
    // Get image path before deletion
    $stmt = $conn->prepare("SELECT image_path FROM toffees WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $toffee = $result->fetch_assoc();
    $stmt->close();
    
    // Delete image if exists
    if ($toffee && $toffee['image_path'] && file_exists($toffee['image_path'])) {
        unlink($toffee['image_path']);
    }
    
    // Delete record
    $stmt = $conn->prepare("DELETE FROM toffees WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
}

header("Location: index.php");
exit();
?>
