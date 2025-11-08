<?php
require_once __DIR__ . '/db_connect.php';

function getTotalSales(mysqli $conn): array
{
    $summary = ['orders' => 0, 'revenue' => 0.0];
    $result = $conn->query("SELECT COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE status IN ('pending', 'processing', 'completed')");
    if ($result) {
        $row = $result->fetch_assoc();
        $summary['orders'] = (int) ($row['orders_count'] ?? 0);
        $summary['revenue'] = (float) ($row['revenue'] ?? 0);
        $result->close();
    }
    return $summary;
}

function getRecentBookings(mysqli $conn, int $limit = 5): array
{
    $stmt = $conn->prepare('SELECT * FROM bookings ORDER BY created_at DESC LIMIT ?');
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $bookings;
}

function getAllBookings(mysqli $conn): array
{
    $result = $conn->query('SELECT * FROM bookings ORDER BY created_at DESC');
    $bookings = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if ($result) {
        $result->close();
    }
    return $bookings;
}

function getProducts(mysqli $conn): array
{
    $result = $conn->query('SELECT * FROM products ORDER BY created_at DESC');
    $products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if ($result) {
        $result->close();
    }
    return $products;
}

function getProductById(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $product ?: null;
}

function addProduct(mysqli $conn, array $data): bool
{
    $stmt = $conn->prepare('INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssds', $data['name'], $data['description'], $data['price'], $data['image']);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function updateProduct(mysqli $conn, int $id, array $data): bool
{
    $stmt = $conn->prepare('UPDATE products SET name = ?, description = ?, price = ?, image = ? WHERE id = ?');
    $stmt->bind_param('ssdsi', $data['name'], $data['description'], $data['price'], $data['image'], $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function deleteProduct(mysqli $conn, int $id): bool
{
    $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
    $stmt->bind_param('i', $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function getRecentOrders(mysqli $conn, int $limit = 10): array
{
    $stmt = $conn->prepare('SELECT o.*, p.name AS product_name, u.name AS customer_name FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC LIMIT ?');
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $orders;
}

?>


