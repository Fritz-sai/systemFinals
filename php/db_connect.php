<?php
// Database connection and initialization logic

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'phonerepair_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass);
    $conn->set_charset('utf8mb4');

    try {
        $conn->select_db($dbName);
    } catch (mysqli_sql_exception $e) {
        if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            throw $e;
        }
        $conn->select_db($dbName);
    }
} catch (mysqli_sql_exception $exception) {
    die('Database connection failed: ' . $exception->getMessage());
}

// Create tables if they do not exist
$tableQueries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        contact VARCHAR(50) NOT NULL,
        phone_model VARCHAR(100) NOT NULL,
        issue TEXT NOT NULL,
        date DATE NOT NULL,
        time TIME NOT NULL,
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        description TEXT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255) DEFAULT 'images/placeholder.png',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB"
];

foreach ($tableQueries as $query) {
    if (!$conn->query($query)) {
        die('Failed to initialize database tables: ' . $conn->error);
    }
}

// Seed data for admin user and initial products/bookings
function seedAdminUser(mysqli $conn): void
{
    $email = 'admin@phonerepair.com';
    $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $check->bind_param('s', $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $name = 'Site Admin';
        $password = password_hash('Admin@123', PASSWORD_DEFAULT);
        $role = 'admin';
        $insert = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $insert->bind_param('ssss', $name, $email, $password, $role);
        $insert->execute();
        $insert->close();
    }

    $check->close();
}

function seedProducts(mysqli $conn): void
{
    $result = $conn->query('SELECT COUNT(*) as count FROM products');
    $count = $result ? (int) $result->fetch_assoc()['count'] : 0;
    if ($result) {
        $result->close();
    }

    if ($count > 0) {
        return;
    }

    $products = [
        ['Premium Screen Protector', 'Ultra-clear tempered glass screen protector with edge-to-edge coverage.', 19.99, 'images/placeholder.png'],
        ['Fast Wireless Charger', '15W fast wireless charging pad with USB-C compatibility.', 39.99, 'images/placeholder.png'],
        ['Protective Case', 'Slim shockproof case available in multiple colors.', 24.99, 'images/placeholder.png'],
        ['Noise Cancelling Earbuds', 'Wireless earbuds with active noise cancellation and 24-hour battery life.', 79.99, 'images/placeholder.png']
    ];

    $stmt = $conn->prepare('INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)');
    foreach ($products as $product) {
        $stmt->bind_param('ssds', $product[0], $product[1], $product[2], $product[3]);
        $stmt->execute();
    }
    $stmt->close();
}

function seedBookings(mysqli $conn): void
{
    $result = $conn->query('SELECT COUNT(*) as count FROM bookings');
    $count = $result ? (int) $result->fetch_assoc()['count'] : 0;
    if ($result) {
        $result->close();
    }

    if ($count > 0) {
        return;
    }

    $bookings = [
        ['Alex Johnson', '+1 555-1234', 'iPhone 13 Pro', 'Cracked screen replacement', date('Y-m-d', strtotime('+1 day')), '10:00', 'pending'],
        ['Maria Chen', '+1 555-9876', 'Samsung Galaxy S22', 'Battery drains quickly', date('Y-m-d', strtotime('+2 days')), '14:30', 'in_progress']
    ];

    $stmt = $conn->prepare('INSERT INTO bookings (name, contact, phone_model, issue, date, time, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($bookings as $booking) {
        $stmt->bind_param('sssssss', $booking[0], $booking[1], $booking[2], $booking[3], $booking[4], $booking[5], $booking[6]);
        $stmt->execute();
    }
    $stmt->close();
}

seedAdminUser($conn);
seedProducts($conn);
seedBookings($conn);

// Ensure sessions are started once per request
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

