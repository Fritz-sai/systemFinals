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
        user_id INT NULL,
        name VARCHAR(100) NOT NULL,
        contact VARCHAR(50) NOT NULL,
        phone_model VARCHAR(100) NOT NULL,
        issue TEXT NOT NULL,
        date DATE NOT NULL,
        time TIME NOT NULL,
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        status_message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
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
        delivery_type ENUM('pickup', 'delivery') DEFAULT 'pickup',
        shipping_fee DECIMAL(10,2) DEFAULT 0,
        proof_image VARCHAR(255) NULL,
        order_status ENUM('pending', 'out_for_delivery', 'delivered', 'received') DEFAULT 'pending',
        status_message TEXT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('order', 'booking', 'general') DEFAULT 'general',
        reference_id INT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        order_id INT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
        UNIQUE KEY unique_order_review (order_id)
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

// Update orders table to add new columns if they don't exist
function updateOrdersTable(mysqli $conn): void
{
    // Check if columns exist and add them if they don't
    $columnsToAdd = [
        "delivery_type" => "ALTER TABLE orders ADD COLUMN delivery_type ENUM('pickup', 'delivery') DEFAULT 'pickup'",
        "shipping_fee" => "ALTER TABLE orders ADD COLUMN shipping_fee DECIMAL(10,2) DEFAULT 0",
        "proof_image" => "ALTER TABLE orders ADD COLUMN proof_image VARCHAR(255) NULL",
        "order_status" => "ALTER TABLE orders ADD COLUMN order_status ENUM('pending', 'out_for_delivery', 'delivered', 'received') DEFAULT 'pending'",
        "status_message" => "ALTER TABLE orders ADD COLUMN status_message TEXT NULL"
    ];

    foreach ($columnsToAdd as $column => $query) {
        $check = $conn->query("SHOW COLUMNS FROM orders LIKE '$column'");
        if ($check && $check->num_rows == 0) {
            $conn->query($query);
        }
        if ($check) {
            $check->close();
        }
    }
}

function updateBookingsTable(mysqli $conn): void
{
    $columnsToAdd = [
        "status_message" => "ALTER TABLE bookings ADD COLUMN status_message TEXT NULL",
        "user_id" => "ALTER TABLE bookings ADD COLUMN user_id INT NULL AFTER id",
        "proof_image" => "ALTER TABLE bookings ADD COLUMN proof_image VARCHAR(255) NULL"
    ];

    foreach ($columnsToAdd as $column => $sql) {
        $check = $conn->query("SHOW COLUMNS FROM bookings LIKE '$column'");
        if ($check && $check->num_rows == 0) {
            $conn->query($sql);
            if ($column === 'user_id') {
                // Add foreign key if not present
                $fkCheck = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='bookings' AND COLUMN_NAME='user_id' AND REFERENCED_TABLE_NAME='users'");
                if ($fkCheck && $fkCheck->num_rows == 0) {
                    $conn->query("ALTER TABLE bookings ADD CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
                }
                if ($fkCheck) {
                    $fkCheck->close();
                }
            }
        }
        if ($check) {
            $check->close();
        }
    }
}

function updateNotificationsTable(mysqli $conn): void
{
    $columnsToAdd = [
        "reference_id" => "ALTER TABLE notifications ADD COLUMN reference_id INT NULL AFTER type",
        "type" => "ALTER TABLE notifications MODIFY COLUMN type ENUM('order','booking','general') DEFAULT 'general'"
    ];

    foreach ($columnsToAdd as $column => $sql) {
        $check = $conn->query("SHOW COLUMNS FROM notifications LIKE '$column'");
        if ($check && $check->num_rows == 0) {
            $conn->query($sql);
        }
        if ($check) {
            $check->close();
        }
    }
}
seedAdminUser($conn);
seedProducts($conn);
seedBookings($conn);
updateOrdersTable($conn);
updateBookingsTable($conn);
updateNotificationsTable($conn);

// Ensure sessions are started once per request
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

