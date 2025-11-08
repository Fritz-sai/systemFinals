<?php
require_once __DIR__ . '/php/admin_functions.php';
require_once __DIR__ . '/php/upload_handler.php';
require_once __DIR__ . '/php/helpers.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    $_SESSION['login_errors'] = ['Please log in with an admin account to access the dashboard.'];
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['admin_action'] ?? '';
    
    // Handle booking completion
    if ($action === 'mark_booking_completed') {
        $bookingId = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
        if ($bookingId > 0) {
            if (markBookingCompleted($conn, $bookingId)) {
                $_SESSION['admin_success'] = ['Booking marked as completed successfully.'];
            } else {
                $_SESSION['admin_errors'] = ['Unable to update booking status.'];
            }
        }
        header('Location: admin.php');
        exit;
    }

    // Handle product operations
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
    $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : null;
    $image = 'images/placeholder.png'; // Default

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleProductImageUpload($_FILES['image']);
        if ($uploadResult['success']) {
            $image = $uploadResult['path'];
        } elseif ($uploadResult['error']) {
            $_SESSION['admin_errors'] = [$uploadResult['error']];
            header('Location: admin.php' . ($productId ? '?edit=' . $productId : ''));
            exit;
        }
    } elseif ($productId) {
        // Keep existing image when updating without new upload
        $existingProduct = getProductById($conn, $productId);
        if ($existingProduct) {
            $image = $existingProduct['image'];
        }
    }

    switch ($action) {
        case 'add_product':
            if ($name && $description && $price > 0) {
                if (addProduct($conn, ['name' => $name, 'description' => $description, 'price' => $price, 'image' => $image])) {
                    $_SESSION['admin_success'] = ['Product added successfully.'];
                } else {
                    $_SESSION['admin_errors'] = ['Unable to add product.'];
                }
            } else {
                $_SESSION['admin_errors'] = ['Please provide a name, description, and valid price.'];
            }
            break;

        case 'update_product':
            if ($productId && $name && $description && $price > 0) {
                if (updateProduct($conn, $productId, ['name' => $name, 'description' => $description, 'price' => $price, 'image' => $image])) {
                    $_SESSION['admin_success'] = ['Product updated successfully.'];
                } else {
                    $_SESSION['admin_errors'] = ['Unable to update product.'];
                }
            } else {
                $_SESSION['admin_errors'] = ['Please provide complete product details.'];
            }
            break;

        case 'delete_product':
            if ($productId) {
                // Get product to delete its image
                $product = getProductById($conn, $productId);
                if ($product && deleteProduct($conn, $productId)) {
                    // Delete associated image file
                    if ($product['image'] && strpos($product['image'], 'uploads/products/') === 0) {
                        deleteProductImage($product['image']);
                    }
                    $_SESSION['admin_success'] = ['Product deleted successfully.'];
                } else {
                    $_SESSION['admin_errors'] = ['Unable to delete product.'];
                }
            }
            break;
    }

    header('Location: admin.php');
    exit;
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $editProduct = getProductById($conn, (int) $_GET['edit']);
}

$stats = getTotalSales($conn);
$activeBookings = getActiveBookings($conn);
$completedBookings = getCompletedBookings($conn);
$orders = getRecentOrders($conn);
$allOrders = getAllOrders($conn); // Get all orders for management section
$products = getProducts($conn);

// Sales Analytics Data
$salesComparison = getSalesComparison($conn);
$ordersByStatus = getOrdersByStatus($conn);
$topProducts = getTopSellingProducts($conn, 5);
$monthlySales = getSalesByMonth($conn, 6);
$dailySales = getDailySalesLast30Days($conn);

// Enhanced Dashboard Data
$monthComparison = getMonthComparison($conn);
$topProductsByQuantity = getTopProductsByQuantity($conn, 5, 'this_month');
$recentTransactions = getRecentTransactions($conn, 10);

renderHead('Admin Dashboard | PhoneFix+');
renderNav();
renderFlashMessages([
    'admin_success' => 'success',
    'admin_errors' => 'error'
]);
?>
<link rel="stylesheet" href="css/admin.css">
<link rel="stylesheet" href="css/admin_orders.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script defer src="js/admin.js"></script>
<script defer src="js/admin_orders.js"></script>

<main class="page admin-page">
    <section class="page-header">
        <div class="container">
            <h1>Admin Dashboard</h1>
            <p>Manage sales, bookings, and inventory in one place.</p>
        </div>
    </section>

    <section class="container admin-grid">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <nav class="sidebar-nav">
                <h3>Quick Navigation</h3>
                <ul>
                    <li><a href="#sales-overview" class="sidebar-link active" data-section="sales-overview">
                        <span class="icon">📊</span>
                        <span>Sales Overview</span>
                    </a></li>
                    <li><a href="#sales-analysis" class="sidebar-link" data-section="sales-analysis">
                        <span class="icon">📈</span>
                        <span>Sales Analysis</span>
                    </a></li>
                    <li><a href="#products-section" class="sidebar-link" data-section="products-section">
                        <span class="icon">📦</span>
                        <span>Products</span>
                    </a></li>
                    <li><a href="#orders-section" class="sidebar-link" data-section="orders-section">
                        <span class="icon">🛒</span>
                        <span>Recent Orders</span>
                    </a></li>
                    <li><a href="#orders-management-section" class="sidebar-link" data-section="orders-management-section">
                        <span class="icon">📋</span>
                        <span>Orders Management</span>
                    </a></li>
                    <li><a href="#bookings-section" class="sidebar-link" data-section="bookings-section">
                        <span class="icon">📅</span>
                        <span>Bookings</span>
                    </a></li>
                </ul>
            </nav>
        </aside>

        <div class="admin-content">
        <!-- Enhanced Dashboard Summary Cards -->
        <div class="card dashboard-summary" id="dashboard-summary">
            <div class="dashboard-header">
                <h2>Dashboard Overview</h2>
                <div class="dashboard-controls">
                    <select id="period-filter" class="period-filter">
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="all_time">All Time</option>
                    </select>
                    <button type="button" class="btn-primary btn-sm" id="export-btn">Export Report</button>
                </div>
            </div>
            
            <div class="summary-cards-grid">
                <div class="summary-card">
                    <div class="card-icon">✅</div>
                    <div class="card-content">
                        <span class="card-label">Orders Sold</span>
                        <strong class="card-value" id="summary-sold-orders"><?php echo $monthComparison['this_month']['sold_orders']; ?></strong>
                        <span class="card-change <?php echo $monthComparison['changes']['sold_orders'] >= 0 ? 'positive' : 'negative'; ?>" id="summary-sold-orders-change">
                            <?php echo $monthComparison['changes']['sold_orders'] >= 0 ? '+' : ''; ?><?php echo $monthComparison['changes']['sold_orders']; ?>%
                        </span>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-icon">⏳</div>
                    <div class="card-content">
                        <span class="card-label">Pending Orders</span>
                        <strong class="card-value" id="summary-pending-orders"><?php echo $monthComparison['this_month']['pending_orders']; ?></strong>
                        <span class="card-change <?php echo $monthComparison['changes']['pending_orders'] >= 0 ? 'positive' : 'negative'; ?>" id="summary-pending-orders-change">
                            <?php echo $monthComparison['changes']['pending_orders'] >= 0 ? '+' : ''; ?><?php echo $monthComparison['changes']['pending_orders']; ?>%
                        </span>
                        <small class="card-hint">Items to prepare for delivery</small>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-icon">📊</div>
                    <div class="card-content">
                        <span class="card-label">Quantity Sold</span>
                        <strong class="card-value" id="summary-quantity"><?php echo $monthComparison['this_month']['sold_quantity']; ?></strong>
                        <span class="card-change <?php echo $monthComparison['changes']['quantity'] >= 0 ? 'positive' : 'negative'; ?>" id="summary-quantity-change">
                            <?php echo $monthComparison['changes']['quantity'] >= 0 ? '+' : ''; ?><?php echo $monthComparison['changes']['quantity']; ?>%
                        </span>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-icon">📦</div>
                    <div class="card-content">
                        <span class="card-label">Pending Quantity</span>
                        <strong class="card-value" id="summary-pending-quantity"><?php echo $monthComparison['this_month']['pending_quantity']; ?></strong>
                        <small class="card-hint">Items needed for delivery</small>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-icon">💰</div>
                    <div class="card-content">
                        <span class="card-label">Total Sales</span>
                        <strong class="card-value" id="summary-sales">$<?php echo number_format($monthComparison['this_month']['sales'], 2); ?></strong>
                        <span class="card-change <?php echo $monthComparison['changes']['sales'] >= 0 ? 'positive' : 'negative'; ?>" id="summary-sales-change">
                            <?php echo $monthComparison['changes']['sales'] >= 0 ? '+' : ''; ?><?php echo $monthComparison['changes']['sales']; ?>%
                        </span>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-icon">📅</div>
                    <div class="card-content">
                        <span class="card-label">Total Bookings</span>
                        <strong class="card-value" id="summary-bookings"><?php echo $monthComparison['this_month']['bookings']; ?></strong>
                        <span class="card-change <?php echo $monthComparison['changes']['bookings'] >= 0 ? 'positive' : 'negative'; ?>" id="summary-bookings-change">
                            <?php echo $monthComparison['changes']['bookings'] >= 0 ? '+' : ''; ?><?php echo $monthComparison['changes']['bookings']; ?>%
                        </span>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-icon">📈</div>
                    <div class="card-content">
                        <span class="card-label">Total Profit</span>
                        <strong class="card-value" id="summary-profit">$<?php echo number_format($monthComparison['this_month']['profit'], 2); ?></strong>
                        <span class="card-change <?php echo $monthComparison['changes']['profit'] >= 0 ? 'positive' : 'negative'; ?>" id="summary-profit-change">
                            <?php echo $monthComparison['changes']['profit'] >= 0 ? '+' : ''; ?><?php echo $monthComparison['changes']['profit']; ?>%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Month Comparison Chart -->
       

        <!-- Top Products Pie Chart and Table -->
        

        <!-- Recent Transactions -->
        <div class="card recent-transactions-card">
            <h3>Recent Transactions</h3>
            <div class="table-wrapper">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('M j, Y g:i A', strtotime($transaction['order_date'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                <td>$<?php echo number_format((float) $transaction['total'], 2); ?></td>
                                <td>
                                    <span class="status status-<?php echo htmlspecialchars($transaction['order_status'] ?? $transaction['status'] ?? 'pending'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $transaction['order_status'] ?? $transaction['status'] ?? 'pending')); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentTransactions)): ?>
                            <tr><td colspan="5">No recent transactions.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card stats" id="sales-overview">
            <h2>Sales Overview</h2>
            <div class="stats-row">
                <div>
                    <span>Total Orders</span>
                    <strong><?php echo (int) $stats['orders']; ?></strong>
                </div>
                <div>
                    <span>Total Revenue</span>
                    <strong>$<?php echo number_format((float) $stats['revenue'], 2); ?></strong>
                </div>
            </div>
        </div>

        <!-- Sales Analysis Dashboard -->
       

        <div class="card bookings" id="bookings-section">
            <h2>Active Bookings</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Device</th>
                            <th>Issue</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeBookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['contact']); ?></td>
                                <td><?php echo htmlspecialchars($booking['phone_model']); ?></td>
                                <td><?php echo htmlspecialchars($booking['issue']); ?></td>
                                <td><?php echo htmlspecialchars($booking['date']); ?> @ <?php echo htmlspecialchars($booking['time']); ?></td>
                                <td><span class="status status-<?php echo htmlspecialchars($booking['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?></span></td>
                                <td class="actions">
                                    <form action="admin.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="admin_action" value="mark_booking_completed">
                                        <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
                                        <button type="submit" class="btn-link success">Mark as Completed</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$activeBookings): ?>
                            <tr><td colspan="7">No active bookings.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($completedBookings)): ?>
        <div class="card bookings completed-bookings">
            <h2>Completed Bookings</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Device</th>
                            <th>Issue</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedBookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['contact']); ?></td>
                                <td><?php echo htmlspecialchars($booking['phone_model']); ?></td>
                                <td><?php echo htmlspecialchars($booking['issue']); ?></td>
                                <td><?php echo htmlspecialchars($booking['date']); ?> @ <?php echo htmlspecialchars($booking['time']); ?></td>
                                <td><span class="status status-<?php echo htmlspecialchars($booking['status']); ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="card products" id="products-section">
            <h2>Products</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>$<?php echo number_format((float) $product['price'], 2); ?></td>
                                <td class="actions">
                                    <button type="button" class="btn-link edit-product-btn" data-product-id="<?php echo (int) $product['id']; ?>" data-product-name="<?php echo htmlspecialchars($product['name']); ?>" data-product-description="<?php echo htmlspecialchars($product['description']); ?>" data-product-price="<?php echo htmlspecialchars($product['price']); ?>" data-product-image="<?php echo htmlspecialchars($product['image']); ?>">Edit</button>
                                    <form action="admin.php" method="POST" class="delete-product-form" style="display: inline;">
                                        <input type="hidden" name="admin_action" value="delete_product">
                                        <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                        <button type="submit" class="btn-link danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$products): ?>
                            <tr><td colspan="3">No products found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="product-form" id="product-form-section">
                <h3>Add New Product</h3>
                <form action="admin.php" method="POST" enctype="multipart/form-data" class="grid-form" id="product-form">
                    <input type="hidden" name="admin_action" value="add_product" id="form-action">
                    <input type="hidden" name="product_id" value="" id="form-product-id">
                    <input type="hidden" name="keep_existing_image" value="0" id="keep-existing-image">
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" id="form-name" required>
                    </label>
                    <label>
                        <span>Description</span>
                        <textarea name="description" id="form-description" rows="3" required></textarea>
                    </label>
                    <label>
                        <span>Price</span>
                        <input type="number" min="0" step="0.01" name="price" id="form-price" required>
                    </label>
                    <label>
                        <span>Product Image</span>
                        <input type="file" name="image" id="form-image" accept="image/jpeg,image/jpg,image/png">
                        <small class="file-hint">Accepted formats: JPG, JPEG, PNG. Max size: 2MB</small>
                        <div id="current-image-preview" style="display: none; margin-top: 0.5rem;">
                            <img src="" alt="Current image" style="max-width: 200px; border-radius: 8px; margin-top: 0.5rem;">
                            <p style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--color-muted);">Current image (leave file empty to keep)</p>
                        </div>
                    </label>
                    <button type="submit" class="btn-primary" id="form-submit-btn">Add Product</button>
                    <button type="button" class="btn-link" id="form-cancel-btn" style="display: none;">Cancel</button>
                </form>
            </div>
        </div>

        <div class="card orders" id="orders-section">
            <h2>Recent Orders</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo (int) $order['quantity']; ?></td>
                                <td>$<?php echo number_format((float) $order['total'], 2); ?></td>
                                <td><span class="status status-<?php echo htmlspecialchars($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$orders): ?>
                            <tr><td colspan="5">No orders yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Orders Management Section -->
        <div class="card orders-management" id="orders-management-section">
            <h2>Orders Management</h2>
            <p class="section-description">Manage all orders, update status, and upload delivery proofs.</p>
            
            <div class="table-wrapper">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Delivery Type</th>
                            <th>Order Status</th>
                            <th>Proof</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allOrders as $order): ?>
                            <tr data-order-id="<?php echo (int) $order['id']; ?>">
                                <td>#<?php echo (int) $order['id']; ?></td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo (int) $order['quantity']; ?></td>
                                <td>$<?php echo number_format((float) $order['total'], 2); ?></td>
                                <td>
                                    <span class="delivery-type delivery-<?php echo htmlspecialchars($order['delivery_type'] ?? 'pickup'); ?>">
                                        <?php echo ucfirst($order['delivery_type'] ?? 'pickup'); ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="order-status-select" data-order-id="<?php echo (int) $order['id']; ?>" data-current-status="<?php echo htmlspecialchars($order['order_status'] ?? 'pending'); ?>">
                                        <option value="pending" <?php echo ($order['order_status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="out_for_delivery" <?php echo ($order['order_status'] ?? 'pending') === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                        <option value="delivered" <?php echo ($order['order_status'] ?? 'pending') === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="received" <?php echo ($order['order_status'] ?? 'pending') === 'received' ? 'selected' : ''; ?>>Received</option>
                                    </select>
                                </td>
                                <td>
                                    <?php if (!empty($order['proof_image'])): ?>
                                        <button type="button" class="btn-link view-proof-btn" data-proof-path="<?php echo htmlspecialchars($order['proof_image']); ?>">
                                            View Proof
                                        </button>
                                    <?php else: ?>
                                        <span class="no-proof">No proof</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn-primary btn-sm upload-proof-btn" data-order-id="<?php echo (int) $order['id']; ?>">
                                        Upload Proof
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($allOrders)): ?>
                            <tr><td colspan="9">No orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </section>
</main>

<!-- Proof Upload Modal -->
<div id="proofUploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Upload Proof of Delivery</h3>
            <button type="button" class="modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="proofUploadForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_proof">
                <input type="hidden" name="order_id" id="proof_order_id">
                
                <div class="form-group">
                    <label for="proof_image">Proof Image (JPG, PNG, Max 5MB)</label>
                    <input type="file" name="proof_image" id="proof_image" accept="image/jpeg,image/jpg,image/png" required>
                    <small class="file-hint">Accepted formats: JPG, JPEG, PNG. Maximum file size: 5MB</small>
                    <div id="proof_preview" class="proof-preview" style="display: none;">
                        <img id="proof_preview_img" src="" alt="Proof preview">
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="auto_update_status" id="auto_update_status" value="1" checked>
                        <span>Automatically update order status to "Delivered" after upload</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Upload Proof</button>
                    <button type="button" class="btn-secondary modal-close">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

<script>
// Pass PHP data to JavaScript
const salesData = {
    dailySales: <?php echo json_encode($dailySales); ?>,
    monthlySales: <?php echo json_encode($monthlySales); ?>,
    ordersByStatus: <?php echo json_encode($ordersByStatus); ?>,
    topProducts: <?php echo json_encode($topProducts); ?>,
    monthComparison: <?php echo json_encode($monthComparison); ?>,
    topProductsByQuantity: <?php echo json_encode($topProductsByQuantity); ?>
};
</script>
<script defer src="js/dashboard.js"></script>

<?php
renderFooter();
?>

