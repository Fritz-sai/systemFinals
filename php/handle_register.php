<?php
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

$errors = [];

if ($name === '') {
    $errors[] = 'Name is required.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email is required.';
}

if (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

if ($errors) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['old_input'] = ['name' => $name, 'email' => $email];
    header('Location: ../register.php');
    exit;
}

$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $_SESSION['register_errors'] = ['Email is already registered. Please login.'];
    $_SESSION['old_input'] = ['name' => $name, 'email' => $email];
    header('Location: ../register.php');
    exit;
}

$stmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = 'customer';

$insert = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
$insert->bind_param('ssss', $name, $email, $hashedPassword, $role);

$success = $insert->execute();
$insert->close();

if ($success) {
    $_SESSION['auth_success'] = 'Registration successful! Please log in.';
    unset($_SESSION['old_input']);
    header('Location: ../login.php');
    exit;
}

$_SESSION['register_errors'] = ['An error occurred while registering. Please try again.'];
$_SESSION['old_input'] = ['name' => $name, 'email' => $email];
header('Location: ../register.php');
exit;

