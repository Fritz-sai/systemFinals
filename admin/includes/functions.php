<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../../php/admin_functions.php';

// Auth guard for admin pages
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

function ensure_admin(): void
{
	if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
		header('Location: /systemFinals/login.php');
		exit;
	}
}

// One-time lightweight schema hardening for admin panel features
function admin_bootstrap_schema(mysqli $conn): void
{
	// users.active column for deactivate feature
	$check = $conn->query("SHOW COLUMNS FROM users LIKE 'active'");
	if ($check && $check->num_rows == 0) {
		$conn->query("ALTER TABLE users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1");
	}
	if ($check) {
		$check->close();
	}
	// products.category, products.stock (optional enhancements)
	$check = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
	if ($check && $check->num_rows == 0) {
		$conn->query("ALTER TABLE products ADD COLUMN category VARCHAR(100) NULL");
	}
	if ($check) $check->close();
	$check = $conn->query("SHOW COLUMNS FROM products LIKE 'stock'");
	if ($check && $check->num_rows == 0) {
		$conn->query("ALTER TABLE products ADD COLUMN stock INT NOT NULL DEFAULT 0");
	}
	if ($check) $check->close();
	$check = $conn->query("SHOW COLUMNS FROM orders LIKE 'status_message'");
	if ($check && $check->num_rows == 0) {
		$conn->query("ALTER TABLE orders ADD COLUMN status_message TEXT NULL");
	}
	if ($check) $check->close();
	$check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'status_message'");
	if ($check && $check->num_rows == 0) {
		$conn->query("ALTER TABLE bookings ADD COLUMN status_message TEXT NULL");
	}
	if ($check) $check->close();
	$check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'user_id'");
	if ($check && $check->num_rows == 0) {
		$conn->query("ALTER TABLE bookings ADD COLUMN user_id INT NULL AFTER id");
		$conn->query("ALTER TABLE bookings ADD CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
	}
	if ($check) $check->close();
}

admin_bootstrap_schema($conn);

function format_currency($amount): string
{
	return '$' . number_format((float)$amount, 2);
}

function post($key, $default = null)
{
	return $_POST[$key] ?? $default;
}

function get($key, $default = null)
{
	return $_GET[$key] ?? $default;
}

function sendNotificationEmail(string $to, string $subject, string $message): void
{
	if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
		return;
	}

	$headers = [];
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'Content-type: text/plain; charset=utf-8';
	$from = 'no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
	$headers[] = 'From: ' . $from;
	$headers[] = 'Reply-To: ' . $from;

	// Suppress potential warnings if mail() is not configured
	@mail($to, $subject, $message, implode("\r\n", $headers));
}

function set_admin_flash(string $type, string $message): void
{
	$_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function pull_admin_flash(): ?array
{
	$flash = $_SESSION['admin_flash'] ?? null;
	if (isset($_SESSION['admin_flash'])) {
		unset($_SESSION['admin_flash']);
	}
	return $flash;
}

function createNotification(mysqli $conn, int $userId, string $title, string $message, string $type = 'system', ?int $referenceId = null): void
{
	if ($userId <= 0) {
		return;
	}
	$stmt = $conn->prepare("INSERT INTO notifications (user_id, type, reference_id, title, message) VALUES (?, ?, ?, ?, ?)");
	$stmt->bind_param('isiss', $userId, $type, $referenceId, $title, $message);
	$stmt->execute();
	$stmt->close();
}

function createNotificationDetailed(mysqli $conn, int $userId, string $type, ?int $referenceId, string $title, string $message): void
{
	createNotification($conn, $userId, $title, $message, $type, $referenceId);
}
function findUserIdByEmail(mysqli $conn, string $email): int
{
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return 0;
	}
	$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
	$stmt->bind_param('s', $email);
	$stmt->execute();
	$res = $stmt->get_result();
	$user = $res ? $res->fetch_assoc() : null;
	$stmt->close();
	return $user ? (int)$user['id'] : 0;
}



?>


