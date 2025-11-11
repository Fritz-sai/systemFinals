<?php
/**
 * Customer Orders Page
 * 
 * Displays all orders for the logged-in customer
 * Shows order details, status, and proof of delivery if available
 */

require_once __DIR__ . '/php/db_connect.php';
require_once __DIR__ . '/php/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_errors'] = ['Please log in to view your orders.'];
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Get all orders for the current user (excluding rejected orders)
$stmt = $conn->prepare('
    SELECT o.*, 
           p.name AS product_name, 
           p.price AS product_price,
           p.image AS product_image
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ?
      AND o.status != \'cancelled\'
      AND o.order_status != \'cancelled\'
    ORDER BY o.order_date DESC
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

renderHead('My Orders | PhoneFix+');
renderNav();
renderFlashMessages([
    'cart_success' => 'success',
    'cart_errors' => 'error',
    'review_success' => 'success',
    'review_errors' => 'error'
]);
?>
<link rel="stylesheet" href="css/orders.css">
<script defer src="js/orders.js"></script>

<main class="page orders-page">
    <section class="page-header">
        <div class="container">
            <h1>My Orders</h1>
            <p>View your order history and delivery proofs.</p>
            <div class="page-actions">
                <a href="order_history.php" class="btn-outline">View Completed Orders with Proof</a>
            </div>
        </div>
    </section>

    <section class="container">
        <?php if (empty($orders)): ?>
            <div class="card empty-orders">
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h2>No Orders Yet</h2>
                    <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                    <a href="shop.php" class="btn-primary">Browse Products</a>
                </div>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="card order-card" data-order-id="<?php echo (int) $order['id']; ?>">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Order #<?php echo (int) $order['id']; ?></h3>
                                <span class="order-date"><?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></span>
                            </div>
                            <div class="order-status-badge">
                                <span class="status status-<?php echo htmlspecialchars($order['order_status'] ?? 'pending'); ?>">
                                    <?php 
                                    $status = $order['order_status'] ?? 'pending';
                                    echo ucfirst(str_replace('_', ' ', $status)); 
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="order-body">
                            <div class="order-product">
                                <div class="product-image">
                                    <img src="<?php echo htmlspecialchars($order['product_image']); ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>">
                                </div>
                                <div class="product-details">
                                    <h4><?php echo htmlspecialchars($order['product_name']); ?></h4>
                                    <div class="product-meta">
                                        <span>Quantity: <strong><?php echo (int) $order['quantity']; ?></strong></span>
                                        <span>Price: <strong>$<?php echo number_format((float) $order['product_price'], 2); ?></strong></span>
                                    </div>
                                </div>
                            </div>

                            <div class="order-summary">
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <strong>$<?php echo number_format((float) $order['product_price'] * (int) $order['quantity'], 2); ?></strong>
                                </div>
                                <?php if (!empty($order['shipping_fee']) && (float) $order['shipping_fee'] > 0): ?>
                                    <div class="summary-row">
                                        <span>Shipping Fee:</span>
                                        <strong>$<?php echo number_format((float) $order['shipping_fee'], 2); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <div class="summary-row total">
                                    <span>Total:</span>
                                    <strong>$<?php echo number_format((float) $order['total'], 2); ?></strong>
                                </div>
                            </div>

                            <div class="order-delivery">
                                <div class="delivery-info">
                                    <span class="delivery-label">Delivery Type:</span>
                                    <span class="delivery-type delivery-<?php echo htmlspecialchars($order['delivery_type'] ?? 'pickup'); ?>">
                                        <?php echo ucfirst($order['delivery_type'] ?? 'pickup'); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if (!empty($order['proof_image'])): ?>
                                <div class="order-proof">
                                    <button type="button" class="btn-primary view-proof-btn" data-proof-path="/systemFinals/<?php echo htmlspecialchars($order['proof_image']); ?>">
                                        📷 View Proof of Delivery
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php 
                            // Check if order is delivered with proof and if user hasn't reviewed yet
                            $hasReviewed = false;
                            if ($order['order_status'] === 'delivered' && !empty($order['proof_image'])) {
                                $checkStmt = $conn->prepare('SELECT id FROM reviews WHERE order_id = ?');
                                $checkStmt->bind_param('i', $order['id']);
                                $checkStmt->execute();
                                $checkResult = $checkStmt->get_result();
                                $hasReviewed = $checkResult->num_rows > 0;
                                $checkStmt->close();
                            }
                            
                            if ($order['order_status'] === 'delivered' && !empty($order['proof_image']) && !$hasReviewed): ?>
                                <div class="order-review-section" id="review-section-<?php echo (int) $order['id']; ?>" style="margin-top: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                                    <h4 style="margin: 0 0 0.75rem 0; font-size: 1rem;">Review This Product</h4>
                                    <form method="POST" action="php/handle_reviews.php" class="review-form" data-order-id="<?php echo (int) $order['id']; ?>">
                                        <input type="hidden" name="action" value="submit_review">
                                        <input type="hidden" name="product_id" value="<?php echo (int) $order['product_id']; ?>">
                                        <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                                        
                                        <div style="margin-bottom: 0.75rem;">
                                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Rating (Click to select):</label>
                                            <div class="rating-input" data-order-id="<?php echo (int) $order['id']; ?>" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <label class="star-label" data-rating="<?php echo $i; ?>" style="cursor: pointer; font-size: 2rem; color: #d1d5db; transition: all 0.2s; user-select: none;">
                                                        <input type="radio" name="rating" value="<?php echo $i; ?>" required style="display: none;">
                                                        <span class="star">⭐</span>
                                                    </label>
                                                <?php endfor; ?>
                                                <span class="rating-text" style="margin-left: 0.5rem; color: #6b7280; font-weight: 500; min-width: 100px;">Select rating</span>
                                            </div>
                                        </div>
                                        
                                        <div style="margin-bottom: 0.75rem;">
                                            <label for="review-comment-<?php echo (int) $order['id']; ?>" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Your Review:</label>
                                            <textarea 
                                                id="review-comment-<?php echo (int) $order['id']; ?>" 
                                                name="comment" 
                                                rows="3" 
                                                placeholder="Share your experience with this product..."
                                                style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-family: inherit; resize: vertical;"
                                            ></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn-primary" style="width: 100%;">Submit Review</button>
                                    </form>
                                </div>
                            <?php elseif ($hasReviewed): ?>
                                <div class="review-submitted" style="margin-top: 1rem; padding: 0.75rem; background: #d1fae5; border-radius: 8px; color: #065f46; text-align: center;">
                                    ✓ You have already reviewed this product
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($order['status_message']) && strtolower($order['status'] ?? '') === 'cancelled'): ?>
                            <div class="order-status-message">
                                <strong>Update from the team</strong>
                                <?php echo nl2br(htmlspecialchars($order['status_message'])); ?>
                            </div>
                        <?php elseif (!empty($order['status_message']) && strtolower($order['order_status'] ?? '') === 'cancelled'): ?>
                            <div class="order-status-message">
                                <strong>Update from the team</strong>
                                <?php echo nl2br(htmlspecialchars($order['status_message'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<!-- View Proof Modal -->
<div id="viewProofModal" class="modal">
    <div class="modal-content modal-content-large">
        <div class="modal-header">
            <h3>Proof of Delivery</h3>
            <button type="button" class="modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="proof-viewer">
                <img id="proof_viewer_img" src="" alt="Proof of delivery">
            </div>
        </div>
    </div>
</div>

<?php
renderFooter();
?>

