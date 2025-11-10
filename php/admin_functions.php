<?php
require_once __DIR__ . '/db_connect.php';

function getTotalSales(mysqli $conn): array
{
    $summary = ['orders' => 0, 'revenue' => 0.0];
    // Include orders with status delivered and received in sales analysis
    $result = $conn->query("SELECT COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE (order_status IN ('delivered', 'received') OR (order_status IS NULL AND status IN ('pending', 'processing', 'completed')))");
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
    $stmt = $conn->prepare('SELECT o.*, p.name AS product_name, u.name AS customer_name, u.email AS customer_email FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC LIMIT ?');
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $orders;
}

function getAllOrders(mysqli $conn): array
{
    $result = $conn->query('SELECT o.*, p.name AS product_name, p.price AS product_price, u.name AS customer_name, u.email AS customer_email FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC');
    $orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if ($result) {
        $result->close();
    }
    return $orders;
}

function getDeliveredOrders(mysqli $conn): array
{
    $result = $conn->query("SELECT o.*, p.name AS product_name, p.price AS product_price, u.name AS customer_name, u.email AS customer_email FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.user_id = u.id WHERE o.order_status = 'delivered' ORDER BY o.order_date DESC");
    $orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if ($result) {
        $result->close();
    }
    return $orders;
}

function getReceivedOrders(mysqli $conn): array
{
    $result = $conn->query("SELECT o.*, p.name AS product_name, p.price AS product_price, u.name AS customer_name, u.email AS customer_email FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.user_id = u.id WHERE o.order_status = 'received' ORDER BY o.order_date DESC");
    $orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if ($result) {
        $result->close();
    }
    return $orders;
}

function getActiveOrders(mysqli $conn): array
{
    // Get orders that are not delivered or received (for management section)
    $result = $conn->query("SELECT o.*, p.name AS product_name, p.price AS product_price, u.name AS customer_name, u.email AS customer_email FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.user_id = u.id WHERE o.order_status NOT IN ('delivered', 'received') ORDER BY o.order_date DESC");
    $orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if ($result) {
        $result->close();
    }
    return $orders;
}

function getOrderById(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT o.*, p.name AS product_name, p.price AS product_price, u.name AS customer_name, u.email AS customer_email FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $order ?: null;
}

function markBookingCompleted(mysqli $conn, int $bookingId): bool
{
    $stmt = $conn->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    $status = 'completed';
    $stmt->bind_param('si', $status, $bookingId);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function getActiveBookings(mysqli $conn): array
{
    $result = $conn->query("SELECT * FROM bookings WHERE status != 'completed' ORDER BY created_at DESC");
    $bookings = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if ($result) {
        $result->close();
    }
    return $bookings;
}

function getCompletedBookings(mysqli $conn): array
{
    $result = $conn->query("SELECT * FROM bookings WHERE status = 'completed' ORDER BY created_at DESC");
    $bookings = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if ($result) {
        $result->close();
    }
    return $bookings;
}

// Sales Analytics Functions
function getSalesByDateRange(mysqli $conn, string $startDate, string $endDate): array
{
    // Include delivered and received orders in sales analysis
    $stmt = $conn->prepare("SELECT DATE(order_date) AS date, COUNT(*) AS order_count, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE order_date BETWEEN ? AND ? AND (order_status IN ('delivered', 'received') OR (order_status IS NULL AND status IN ('pending', 'processing', 'completed'))) GROUP BY DATE(order_date) ORDER BY date ASC");
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $sales = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $sales;
}

function getSalesByMonth(mysqli $conn, int $months = 6): array
{
    // Include delivered and received orders in sales analysis
    $stmt = $conn->prepare("SELECT DATE_FORMAT(order_date, '%Y-%m') AS month, COUNT(*) AS order_count, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH) AND (order_status IN ('delivered', 'received') OR (order_status IS NULL AND status IN ('pending', 'processing', 'completed'))) GROUP BY DATE_FORMAT(order_date, '%Y-%m') ORDER BY month ASC");
    $stmt->bind_param('i', $months);
    $stmt->execute();
    $result = $stmt->get_result();
    $sales = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $sales;
}

function getOrdersByStatus(mysqli $conn): array
{
    $result = $conn->query("SELECT status, COUNT(*) AS count, COALESCE(SUM(total), 0) AS revenue FROM orders GROUP BY status");
    $stats = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if ($result) {
        $result->close();
    }
    return $stats;
}

function getTopSellingProducts(mysqli $conn, int $limit = 5): array
{
    // Include delivered and received orders in sales analysis
    $stmt = $conn->prepare("SELECT p.id, p.name, p.price, COUNT(o.id) AS order_count, COALESCE(SUM(o.quantity), 0) AS total_quantity, COALESCE(SUM(o.total), 0) AS total_revenue FROM products p LEFT JOIN orders o ON p.id = o.product_id AND (o.order_status IN ('delivered', 'received') OR (o.order_status IS NULL AND o.status IN ('pending', 'processing', 'completed'))) GROUP BY p.id, p.name, p.price ORDER BY total_revenue DESC, order_count DESC LIMIT ?");
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $products;
}

function getSalesComparison(mysqli $conn): array
{
    // Current month - Include delivered and received orders
    $currentMonth = $conn->query("SELECT COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') AND (order_status IN ('delivered', 'received') OR (order_status IS NULL AND status IN ('pending', 'processing', 'completed')))");
    $current = $currentMonth ? $currentMonth->fetch_assoc() : ['orders' => 0, 'revenue' => 0];
    if ($currentMonth) $currentMonth->close();
    
    // Last month - Include delivered and received orders
    $lastMonth = $conn->query("SELECT COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m') AND (order_status IN ('delivered', 'received') OR (order_status IS NULL AND status IN ('pending', 'processing', 'completed')))");
    $last = $lastMonth ? $lastMonth->fetch_assoc() : ['orders' => 0, 'revenue' => 0];
    if ($lastMonth) $lastMonth->close();
    
    // Calculate percentage changes
    $orderChange = $last['orders'] > 0 ? (($current['orders'] - $last['orders']) / $last['orders']) * 100 : ($current['orders'] > 0 ? 100 : 0);
    $revenueChange = $last['revenue'] > 0 ? (($current['revenue'] - $last['revenue']) / $last['revenue']) * 100 : ($current['revenue'] > 0 ? 100 : 0);
    
    return [
        'current' => [
            'orders' => (int)$current['orders'],
            'revenue' => (float)$current['revenue']
        ],
        'last' => [
            'orders' => (int)$last['orders'],
            'revenue' => (float)$last['revenue']
        ],
        'order_change' => round($orderChange, 1),
        'revenue_change' => round($revenueChange, 1)
    ];
}

function getDailySalesLast30Days(mysqli $conn): array
{
    // Include delivered and received orders in sales analysis
    $result = $conn->query("SELECT DATE(order_date) AS date, COUNT(*) AS order_count, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND (order_status IN ('delivered', 'received') OR (order_status IS NULL AND status IN ('pending', 'processing', 'completed'))) GROUP BY DATE(order_date) ORDER BY date ASC");
    $sales = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if ($result) {
        $result->close();
    }
    return $sales;
}

// Enhanced Dashboard Analytics Functions
function getDashboardSummary(mysqli $conn, string $period = 'this_month'): array
{
    $whereClause = '';
    switch ($period) {
        case 'this_month':
            $whereClause = "DATE_FORMAT(order_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
            break;
        case 'last_month':
            $whereClause = "DATE_FORMAT(order_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')";
            break;
        case 'all_time':
            $whereClause = "1=1";
            break;
    }

    // Sold Orders (delivered/received) - for completed sales
    $soldOrdersQuery = "SELECT COUNT(*) AS sold_orders, COALESCE(SUM(total), 0) AS sold_sales, COALESCE(SUM(quantity), 0) AS sold_quantity FROM orders WHERE $whereClause AND (order_status IN ('delivered', 'received') OR (order_status IS NULL AND status = 'completed'))";
    $soldOrdersResult = $conn->query($soldOrdersQuery);
    $soldOrdersData = $soldOrdersResult ? $soldOrdersResult->fetch_assoc() : ['sold_orders' => 0, 'sold_sales' => 0, 'sold_quantity' => 0];
    if ($soldOrdersResult) $soldOrdersResult->close();

    // Pending Orders (for delivery preparation)
    $pendingOrdersQuery = "SELECT COUNT(*) AS pending_orders, COALESCE(SUM(quantity), 0) AS pending_quantity FROM orders WHERE $whereClause AND (order_status IN ('pending', 'out_for_delivery') OR (order_status IS NULL AND status IN ('pending', 'processing')))";
    $pendingOrdersResult = $conn->query($pendingOrdersQuery);
    $pendingOrdersData = $pendingOrdersResult ? $pendingOrdersResult->fetch_assoc() : ['pending_orders' => 0, 'pending_quantity' => 0];
    if ($pendingOrdersResult) $pendingOrdersResult->close();

    // Total Orders and Sales (all statuses)
    $totalOrdersQuery = "SELECT COUNT(*) AS total_orders, COALESCE(SUM(total), 0) AS total_sales, COALESCE(SUM(quantity), 0) AS total_quantity FROM orders WHERE $whereClause AND (order_status IN ('delivered', 'received', 'pending', 'out_for_delivery') OR (order_status IS NULL AND status IN ('pending', 'processing', 'completed')))";
    $totalOrdersResult = $conn->query($totalOrdersQuery);
    $totalOrdersData = $totalOrdersResult ? $totalOrdersResult->fetch_assoc() : ['total_orders' => 0, 'total_sales' => 0, 'total_quantity' => 0];
    if ($totalOrdersResult) $totalOrdersResult->close();

    // Bookings - Separate pending and completed
    $bookingsWhere = '';
    switch ($period) {
        case 'this_month':
            $bookingsWhere = "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
            break;
        case 'last_month':
            $bookingsWhere = "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')";
            break;
        case 'all_time':
            $bookingsWhere = "1=1";
            break;
    }
    
    // Pending bookings (not completed)
    $pendingBookingsQuery = "SELECT COUNT(*) AS bookings FROM bookings WHERE $bookingsWhere AND status != 'completed'";
    $pendingBookingsResult = $conn->query($pendingBookingsQuery);
    $pendingBookingsData = $pendingBookingsResult ? $pendingBookingsResult->fetch_assoc() : ['bookings' => 0];
    if ($pendingBookingsResult) $pendingBookingsResult->close();
    
    // Completed bookings
    $completedBookingsQuery = "SELECT COUNT(*) AS bookings FROM bookings WHERE $bookingsWhere AND status = 'completed'";
    $completedBookingsResult = $conn->query($completedBookingsQuery);
    $completedBookingsData = $completedBookingsResult ? $completedBookingsResult->fetch_assoc() : ['bookings' => 0];
    if ($completedBookingsResult) $completedBookingsResult->close();
    
    // Total bookings
    $totalBookingsQuery = "SELECT COUNT(*) AS bookings FROM bookings WHERE $bookingsWhere";
    $totalBookingsResult = $conn->query($totalBookingsQuery);
    $totalBookingsData = $totalBookingsResult ? $totalBookingsResult->fetch_assoc() : ['bookings' => 0];
    if ($totalBookingsResult) $totalBookingsResult->close();

    // Profit (assuming 30% profit margin, or can be calculated from cost if available)
    $profit = (float)$soldOrdersData['sold_sales'] * 0.30; // 30% profit margin

    return [
        'sold_orders' => (int)$soldOrdersData['sold_orders'],
        'pending_orders' => (int)$pendingOrdersData['pending_orders'],
        'total_orders' => (int)$totalOrdersData['total_orders'],
        'sold_quantity' => (int)$soldOrdersData['sold_quantity'],
        'pending_quantity' => (int)$pendingOrdersData['pending_quantity'],
        'total_quantity' => (int)$totalOrdersData['total_quantity'],
        'sales' => (float)$soldOrdersData['sold_sales'],
        'total_sales' => (float)$totalOrdersData['total_sales'],
        'bookings' => (int)$totalBookingsData['bookings'],
        'pending_bookings' => (int)$pendingBookingsData['bookings'],
        'completed_bookings' => (int)$completedBookingsData['bookings'],
        'profit' => round($profit, 2)
    ];
}

function getMonthComparison(mysqli $conn): array
{
    $thisMonth = getDashboardSummary($conn, 'this_month');
    $lastMonth = getDashboardSummary($conn, 'last_month');

    // Calculate percentage changes
    $soldOrdersChange = $lastMonth['sold_orders'] > 0 ? (($thisMonth['sold_orders'] - $lastMonth['sold_orders']) / $lastMonth['sold_orders']) * 100 : ($thisMonth['sold_orders'] > 0 ? 100 : 0);
    $pendingOrdersChange = $lastMonth['pending_orders'] > 0 ? (($thisMonth['pending_orders'] - $lastMonth['pending_orders']) / $lastMonth['pending_orders']) * 100 : ($thisMonth['pending_orders'] > 0 ? 100 : 0);
    $quantityChange = $lastMonth['sold_quantity'] > 0 ? (($thisMonth['sold_quantity'] - $lastMonth['sold_quantity']) / $lastMonth['sold_quantity']) * 100 : ($thisMonth['sold_quantity'] > 0 ? 100 : 0);
    $salesChange = $lastMonth['sales'] > 0 ? (($thisMonth['sales'] - $lastMonth['sales']) / $lastMonth['sales']) * 100 : ($thisMonth['sales'] > 0 ? 100 : 0);
    $bookingsChange = $lastMonth['bookings'] > 0 ? (($thisMonth['bookings'] - $lastMonth['bookings']) / $lastMonth['bookings']) * 100 : ($thisMonth['bookings'] > 0 ? 100 : 0);
    $pendingBookingsChange = $lastMonth['pending_bookings'] > 0 ? (($thisMonth['pending_bookings'] - $lastMonth['pending_bookings']) / $lastMonth['pending_bookings']) * 100 : ($thisMonth['pending_bookings'] > 0 ? 100 : 0);
    $completedBookingsChange = $lastMonth['completed_bookings'] > 0 ? (($thisMonth['completed_bookings'] - $lastMonth['completed_bookings']) / $lastMonth['completed_bookings']) * 100 : ($thisMonth['completed_bookings'] > 0 ? 100 : 0);
    $profitChange = $lastMonth['profit'] > 0 ? (($thisMonth['profit'] - $lastMonth['profit']) / $lastMonth['profit']) * 100 : ($thisMonth['profit'] > 0 ? 100 : 0);

    return [
        'this_month' => $thisMonth,
        'last_month' => $lastMonth,
        'changes' => [
            'sold_orders' => round($soldOrdersChange, 1),
            'pending_orders' => round($pendingOrdersChange, 1),
            'quantity' => round($quantityChange, 1),
            'sales' => round($salesChange, 1),
            'bookings' => round($bookingsChange, 1),
            'pending_bookings' => round($pendingBookingsChange, 1),
            'completed_bookings' => round($completedBookingsChange, 1),
            'profit' => round($profitChange, 1)
        ]
    ];
}

function getTopProductsByQuantity(mysqli $conn, int $limit = 5, string $period = 'this_month'): array
{
    $whereClause = '';
    switch ($period) {
        case 'this_month':
            $whereClause = "DATE_FORMAT(o.order_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
            break;
        case 'last_month':
            $whereClause = "DATE_FORMAT(o.order_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')";
            break;
        case 'all_time':
            $whereClause = "1=1";
            break;
    }

    $query = "SELECT 
                p.id,
                p.name,
                p.price,
                COALESCE(SUM(o.quantity), 0) AS quantity_sold,
                COALESCE(SUM(o.total), 0) AS total_revenue,
                COUNT(o.id) AS order_count
              FROM products p
              LEFT JOIN orders o ON p.id = o.product_id 
                AND (o.order_status IN ('delivered', 'received') OR (o.order_status IS NULL AND o.status IN ('pending', 'processing', 'completed')))
                AND $whereClause
              GROUP BY p.id, p.name, p.price
              HAVING quantity_sold > 0
              ORDER BY quantity_sold DESC, total_revenue DESC
              LIMIT ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    // Calculate total revenue for percentage calculation
    $totalRevenue = array_sum(array_column($products, 'total_revenue'));

    // Add percentage share
    foreach ($products as &$product) {
        $product['percentage'] = $totalRevenue > 0 ? round(($product['total_revenue'] / $totalRevenue) * 100, 2) : 0;
        $product['quantity_sold'] = (int)$product['quantity_sold'];
        $product['total_revenue'] = (float)$product['total_revenue'];
    }

    return $products;
}

function getRecentTransactions(mysqli $conn, int $limit = 10): array
{
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.order_date,
            o.total,
            o.order_status,
            o.status,
            p.name AS product_name,
            u.name AS customer_name,
            'order' AS type
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE (o.order_status IN ('delivered', 'received') OR (o.order_status IS NULL AND o.status IN ('pending', 'processing', 'completed')))
        ORDER BY o.order_date DESC
        LIMIT ?
    ");
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $transactions;
}

function getLowStockProducts(mysqli $conn, int $threshold = 5): array
{
    // Since products table doesn't have stock, we'll check products with no recent sales
    // or we can add a stock column. For now, return empty or add stock column
    $result = $conn->query("SELECT * FROM products WHERE 1=0"); // Placeholder - no stock column yet
    return [];
}

?>




