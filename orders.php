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

// Get all orders for the current user
$stmt = $conn->prepare('
    SELECT o.*, 
           p.name AS product_name, 
           p.price AS product_price,
           p.image AS product_image
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ?
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
    'cart_errors' => 'error'
]);
?>
<link rel="stylesheet" href="css/orders.css">
<script defer src="js/orders.js"></script>

<main class="page orders-page">
    <section class="page-header">
        <div class="container">
            <h1>My Orders</h1>
            <p>View your order history and delivery proofs.</p>
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
                                    <button type="button" class="btn-primary view-proof-btn" data-proof-path="<?php echo htmlspecialchars($order['proof_image']); ?>">
                                        📷 View Proof of Delivery
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
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

