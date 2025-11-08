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
$products = getProducts($conn);

// Get filter and pagination parameters
$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

// Filter orders by status
$allOrders = getRecentOrders($conn, 1000); // Get all orders
$filteredOrders = $allOrders;
if ($statusFilter !== 'all') {
    $filteredOrders = array_filter($allOrders, function($order) use ($statusFilter) {
        return $order['status'] === $statusFilter;
    });
}
$totalOrders = count($filteredOrders);
$totalPages = ceil($totalOrders / $perPage);
$orders = array_slice($filteredOrders, $offset, $perPage);

// Get current section
$currentSection = $_GET['section'] ?? 'orders';

renderHead('Admin Dashboard | PhoneFix+');
?>
<link rel="stylesheet" href="css/admin.css">
<script defer src="js/admin.js"></script>
<style>
body {
    margin: 0;
    padding: 0;
    background: #f5f7fb;
}
.navbar,
.footer,
.chatbot,
.chatbot-toggle {
    display: none !important;
}
</style>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h1 class="sidebar-logo">PhoneFix+</h1>
        </div>
        <nav class="sidebar-nav">
            <a href="admin.php?section=dashboard" class="nav-item <?php echo $currentSection === 'dashboard' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="2" y="2" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="12" y="2" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="2" y="12" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="12" y="12" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="admin.php?section=orders" class="nav-item <?php echo $currentSection === 'orders' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 2L3 5v12a1 1 0 001 1h12a1 1 0 001-1V5l-2-3H5z" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M3 5h14M8 9v4M12 9v4" stroke="currentColor" stroke-width="1.5"/></svg>
                <span>Order</span>
            </a>
            <a href="admin.php?section=statistics" class="nav-item <?php echo $currentSection === 'statistics' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M10 2v8l6 4" stroke="currentColor" stroke-width="1.5"/></svg>
                <span>Statistic</span>
            </a>
            <a href="admin.php?section=products" class="nav-item <?php echo $currentSection === 'products' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="3" y="3" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M8 8h4M8 12h4" stroke="currentColor" stroke-width="1.5"/></svg>
                <span>Product</span>
            </a>
            <a href="admin.php?section=bookings" class="nav-item <?php echo $currentSection === 'bookings' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M4 4h12v12H4z" stroke="currentColor" stroke-width="1.5"/><path d="M8 8h4M8 12h4" stroke="currentColor" stroke-width="1.5"/></svg>
                <span>Bookings</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="#">Facebook</a>
            <a href="#">Twitter</a>
            <a href="#">Google</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <?php renderFlashMessages([
            'admin_success' => 'success',
            'admin_errors' => 'error'
        ]); ?>
        
        <?php if ($currentSection === 'orders'): ?>

        <!-- Orders Section -->
        <div class="admin-content-card">
            <div class="admin-header">
                <div class="header-left">
                    <h1 class="admin-title">Order</h1>
                    <p class="admin-subtitle"><?php echo $totalOrders; ?> orders found</p>
                </div>
                <div class="header-right">
                    <button class="header-icon-btn" title="Notifications">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 2a6 6 0 016 6v3.586l1.707 1.707A1 1 0 0118 14H2a1 1 0 01-.707-1.707L3 11.586V8a6 6 0 016-6zM10 18a2 2 0 01-2-2h4a2 2 0 01-2 2z" stroke="currentColor" stroke-width="1.5"/></svg>
                    </button>
                    <button class="header-icon-btn" title="Search">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5"/><path d="M13 13l4 4" stroke="currentColor" stroke-width="1.5"/></svg>
                    </button>
                    <div class="user-profile">
                        <div class="profile-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?></div>
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="1.5"/></svg>
                    </div>
                </div>
            </div>

            <div class="admin-filters">
                <div class="filter-tabs">
                    <a href="admin.php?section=orders&status=all" class="filter-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All orders</a>
                    <a href="admin.php?section=orders&status=processing" class="filter-tab <?php echo $statusFilter === 'processing' ? 'active' : ''; ?>">Dispatch</a>
                    <a href="admin.php?section=orders&status=pending" class="filter-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="admin.php?section=orders&status=completed" class="filter-tab <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">Completed</a>
                </div>
                <div class="date-filter">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="2" y="3" width="12" height="11" rx="1" stroke="currentColor" stroke-width="1.5"/><path d="M5 1v4M11 1v4M2 7h12" stroke="currentColor" stroke-width="1.5"/></svg>
                    <span><?php echo date('d M Y'); ?> To <?php echo date('d M Y', strtotime('+3 days')); ?></span>
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="2" y="3" width="12" height="11" rx="1" stroke="currentColor" stroke-width="1.5"/><path d="M5 1v4M11 1v4M2 7h12" stroke="currentColor" stroke-width="1.5"/></svg>
                </div>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Id <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="1.5"/></svg></th>
                            <th>Name <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="1.5"/></svg></th>
                            <th>Address <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="1.5"/></svg></th>
                            <th>Date <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="1.5"/></svg></th>
                            <th>Price <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="1.5"/></svg></th>
                            <th>Status <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="1.5"/></svg></th>
                            <th>Action <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="1.5"/></svg></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $index => $order): 
                            $orderDate = date('d M Y', strtotime($order['order_date']));
                            $statusClass = $order['status'] === 'pending' ? 'pending' : ($order['status'] === 'processing' ? 'dispatch' : ($order['status'] === 'completed' ? 'completed' : 'pending'));
                        ?>
                        <tr class="<?php echo $index === 1 ? 'highlighted' : ''; ?>">
                            <td>#<?php echo (int) $order['id']; ?></td>
                            <td>
                                <div class="order-customer">
                                    <div class="customer-avatar"><?php echo strtoupper(substr($order['customer_name'], 0, 1)); ?></div>
                                    <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?> Address</td>
                            <td><?php echo $orderDate; ?></td>
                            <td>₱<?php echo number_format((float) $order['total'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $statusClass; ?>">
                                    <span class="status-dot"></span>
                                    <?php echo ucfirst($order['status'] === 'processing' ? 'Dispatch' : $order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn" title="Settings">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="1.5" fill="currentColor"/><circle cx="8" cy="3" r="1.5" fill="currentColor"/><circle cx="8" cy="13" r="1.5" fill="currentColor"/></svg>
                                    </button>
                                    <button class="action-btn" title="More">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M8 3v10" stroke="currentColor" stroke-width="1.5"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--color-muted);">No orders found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="admin-pagination">
                <div class="pagination-info">
                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalOrders); ?> of <?php echo $totalOrders; ?>
                </div>
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                    <a href="admin.php?section=orders&status=<?php echo $statusFilter; ?>&page=<?php echo $page - 1; ?>" class="pagination-btn">&lt;</a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 1); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="admin.php?section=orders&status=<?php echo $statusFilter; ?>&page=<?php echo $i; ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="admin.php?section=orders&status=<?php echo $statusFilter; ?>&page=<?php echo $page + 1; ?>" class="pagination-btn">&gt;</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php elseif ($currentSection === 'products'): ?>
        <!-- Products Section -->
        <div class="admin-content-card">
            <div class="admin-header">
                <div class="header-left">
                    <h1 class="admin-title">Products</h1>
                    <p class="admin-subtitle"><?php echo count($products); ?> products found</p>
                </div>
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
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
                            <td>₱<?php echo number_format((float) $product['price'], 2); ?></td>
                            <td>
                                <button type="button" class="btn-link edit-product-btn" data-product-id="<?php echo (int) $product['id']; ?>" data-product-name="<?php echo htmlspecialchars($product['name']); ?>" data-product-description="<?php echo htmlspecialchars($product['description']); ?>" data-product-price="<?php echo htmlspecialchars($product['price']); ?>" data-product-image="<?php echo htmlspecialchars($product['image']); ?>">Edit</button>
                                <form action="admin.php" method="POST" class="delete-product-form" style="display: inline;">
                                    <input type="hidden" name="admin_action" value="delete_product">
                                    <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                    <button type="submit" class="btn-link danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="product-form" id="product-form-section" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--color-border);">
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

        <?php elseif ($currentSection === 'bookings'): ?>
        <!-- Bookings Section -->
        <div class="admin-content-card">
            <div class="admin-header">
                <div class="header-left">
                    <h1 class="admin-title">Bookings</h1>
                    <p class="admin-subtitle"><?php echo count($activeBookings) + count($completedBookings); ?> bookings found</p>
                </div>
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
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
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($booking['status']); ?>">
                                    <span class="status-dot"></span>
                                    <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <form action="admin.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="admin_action" value="mark_booking_completed">
                                    <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
                                    <button type="submit" class="btn-link success">Mark as Completed</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php else: ?>
        <!-- Dashboard Section -->
        <div class="admin-content-card">
            <div class="admin-header">
                <div class="header-left">
                    <h1 class="admin-title">Dashboard</h1>
                    <p class="admin-subtitle">Overview of your business</p>
                </div>
            </div>
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p class="stat-value"><?php echo (int) $stats['orders']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <p class="stat-value">₱<?php echo number_format((float) $stats['revenue'], 2); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>

