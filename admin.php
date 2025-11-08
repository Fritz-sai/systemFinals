<?php
require_once __DIR__ . '/php/admin_functions.php';
require_once __DIR__ . '/php/helpers.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    $_SESSION['login_errors'] = ['Please log in with an admin account to access the dashboard.'];
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['admin_action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
    $image = trim($_POST['image'] ?? 'images/placeholder.png');
    $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : null;

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
                if (deleteProduct($conn, $productId)) {
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
$bookings = getAllBookings($conn);
$orders = getRecentOrders($conn);
$products = getProducts($conn);

renderHead('Admin Dashboard | PhoneFix+');
renderNav();
renderFlashMessages([
    'admin_success' => 'success',
    'admin_errors' => 'error'
]);
?>

<main class="page admin-page">
    <section class="page-header">
        <div class="container">
            <h1>Admin Dashboard</h1>
            <p>Manage sales, bookings, and inventory in one place.</p>
        </div>
    </section>

    <section class="container admin-grid">
        <div class="card stats">
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

        <div class="card bookings">
            <h2>Bookings</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Device</th>
                            <th>Issue</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['phone_model']); ?></td>
                                <td><?php echo htmlspecialchars($booking['issue']); ?></td>
                                <td><?php echo htmlspecialchars($booking['date']); ?> @ <?php echo htmlspecialchars($booking['time']); ?></td>
                                <td><span class="status status-<?php echo htmlspecialchars($booking['status']); ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$bookings): ?>
                            <tr><td colspan="5">No bookings yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card products">
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
                                    <a class="btn-link" href="admin.php?edit=<?php echo (int) $product['id']; ?>">Edit</a>
                                    <form action="admin.php" method="POST" onsubmit="return confirm('Delete this product?');">
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

            <div class="product-form">
                <h3><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h3>
                <form action="admin.php" method="POST" class="grid-form">
                    <input type="hidden" name="admin_action" value="<?php echo $editProduct ? 'update_product' : 'add_product'; ?>">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="product_id" value="<?php echo (int) $editProduct['id']; ?>">
                    <?php endif; ?>
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" required>
                    </label>
                    <label>
                        <span>Description</span>
                        <textarea name="description" rows="3" required><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                    </label>
                    <label>
                        <span>Price</span>
                        <input type="number" min="0" step="0.01" name="price" value="<?php echo htmlspecialchars($editProduct['price'] ?? ''); ?>" required>
                    </label>
                    <label>
                        <span>Image Path</span>
                        <input type="text" name="image" value="<?php echo htmlspecialchars($editProduct['image'] ?? 'images/placeholder.png'); ?>">
                    </label>
                    <button type="submit" class="btn-primary"><?php echo $editProduct ? 'Update Product' : 'Add Product'; ?></button>
                    <?php if ($editProduct): ?>
                        <a class="btn-link" href="admin.php">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="card orders">
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
    </section>
</main>

<?php
renderFooter();
?>

