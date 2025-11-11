<?php
/**
 * Handle Product Reviews
 * 
 * Handles review submission and validation
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['review_errors'] = ['Please log in to submit a review.'];
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    $productId = (int) ($_POST['product_id'] ?? 0);
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim((string) ($_POST['comment'] ?? ''));
    
    $errors = [];
    
    // Validate inputs
    if ($productId <= 0) {
        $errors[] = 'Invalid product.';
    }
    if ($orderId <= 0) {
        $errors[] = 'Invalid order.';
    }
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating must be between 1 and 5.';
    }
    
    // Check if order belongs to user and is delivered
    if ($orderId > 0) {
        $stmt = $conn->prepare('SELECT user_id, product_id, order_status, proof_image FROM orders WHERE id = ?');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if (!$order) {
            $errors[] = 'Order not found.';
        } elseif ($order['user_id'] != $userId) {
            $errors[] = 'You can only review your own orders.';
        } elseif ($order['product_id'] != $productId) {
            $errors[] = 'Product does not match the order.';
        } elseif ($order['order_status'] !== 'delivered') {
            $errors[] = 'You can only review delivered orders.';
        } elseif (empty($order['proof_image'])) {
            $errors[] = 'Order must have proof of delivery before reviewing.';
        }
    }
    
    // Check if user already reviewed this order
    if ($orderId > 0 && empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM reviews WHERE order_id = ?');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = 'You have already reviewed this order.';
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        // Insert review
        $stmt = $conn->prepare('INSERT INTO reviews (user_id, product_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iiiis', $userId, $productId, $orderId, $rating, $comment);
        
        if ($stmt->execute()) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Thank you! Your review has been submitted.']);
                exit;
            }
            $_SESSION['review_success'] = 'Thank you! Your review has been submitted.';
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to submit review. Please try again.']);
                exit;
            }
            $_SESSION['review_errors'] = ['Failed to submit review. Please try again.'];
        }
        $stmt->close();
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
            exit;
        }
        $_SESSION['review_errors'] = $errors;
    }
    
    // For non-AJAX requests, redirect back to orders page
    header('Location: orders.php');
    exit;
}

// If no action, redirect to orders
header('Location: orders.php');
exit;

