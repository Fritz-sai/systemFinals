<?php
/**
 * Handle Orders Management
 * 
 * This file handles:
 * - Proof image uploads with validation
 * - Order status updates
 * - Returning success/error messages for admin UI
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/helpers.php';

// Ensure user is logged in as admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'upload_proof':
        handleProofUpload($conn);
        break;
    
    case 'update_status':
        handleStatusUpdate($conn);
        break;
    
    case 'get_order':
        getOrderDetails($conn);
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
}

/**
 * Handle proof image upload
 * Validates file type, size, and uploads to /uploads/proofs/
 * Updates order with proof_image path and optionally sets status to 'delivered'
 */
function handleProofUpload(mysqli $conn): void
{
    $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
        exit;
    }

    // Check if file was uploaded
    if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error occurred']);
        exit;
    }

    $file = $_FILES['proof_image'];
    
    // Validate file type (JPG, PNG only)
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG and PNG images are allowed.']);
        exit;
    }

    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit.']);
        exit;
    }

    // Create uploads/proofs directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/proofs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'proof_' . $orderId . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    $relativePath = 'uploads/proofs/' . $fileName;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
        exit;
    }

    // Get current order to check if there's an old proof image to delete
    $stmt = $conn->prepare('SELECT proof_image FROM orders WHERE id = ?');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    // Delete old proof image if it exists
    if ($order && !empty($order['proof_image']) && file_exists(__DIR__ . '/../' . $order['proof_image'])) {
        @unlink(__DIR__ . '/../' . $order['proof_image']);
    }

    // Update order with proof image path
    // Optionally update status to 'delivered' if auto-update is enabled
    $autoUpdateStatus = isset($_POST['auto_update_status']) && $_POST['auto_update_status'] === '1';
    $newStatus = $autoUpdateStatus ? 'delivered' : null;

    if ($newStatus) {
        $stmt = $conn->prepare('UPDATE orders SET proof_image = ?, order_status = ? WHERE id = ?');
        $stmt->bind_param('ssi', $relativePath, $newStatus, $orderId);
    } else {
        $stmt = $conn->prepare('UPDATE orders SET proof_image = ? WHERE id = ?');
        $stmt->bind_param('si', $relativePath, $orderId);
    }

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Proof image uploaded successfully' . ($autoUpdateStatus ? ' and order status updated to Delivered' : ''),
            'proof_path' => $relativePath,
            'order_id' => $orderId
        ]);
    } else {
        $stmt->close();
        // Delete uploaded file if database update failed
        @unlink($filePath);
        echo json_encode(['success' => false, 'error' => 'Failed to update order in database']);
    }
}

/**
 * Handle order status update
 * Updates order_status field in the orders table
 */
function handleStatusUpdate(mysqli $conn): void
{
    $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $newStatus = $_POST['order_status'] ?? '';

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
        exit;
    }

    $allowedStatuses = ['pending', 'out_for_delivery', 'delivered', 'received'];
    if (!in_array($newStatus, $allowedStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid order status']);
        exit;
    }

    $stmt = $conn->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
    $stmt->bind_param('si', $newStatus, $orderId);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully',
            'order_id' => $orderId,
            'new_status' => $newStatus
        ]);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'error' => 'Failed to update order status']);
    }
}

/**
 * Get order details by ID
 * Returns order information including customer and product details
 */
function getOrderDetails(mysqli $conn): void
{
    $orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
        exit;
    }

    $stmt = $conn->prepare('
        SELECT o.*, 
               u.name AS customer_name, 
               u.email AS customer_email,
               p.name AS product_name,
               p.price AS product_price
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN products p ON o.product_id = p.id
        WHERE o.id = ?
    ');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if ($order) {
        echo json_encode(['success' => true, 'order' => $order]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
    }
}

?>

