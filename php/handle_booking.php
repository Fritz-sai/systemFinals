<?php
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../booking.php');
    exit;
}
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_errors'] = ['Please log in to place an order or book'];
    header('Location: ../login.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$phoneModel = trim($_POST['phone_model'] ?? '');
$issue = trim($_POST['issue'] ?? '');
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';

$errors = [];

if ($name === '') {
    $errors[] = 'Name is required.';
}

if ($contact === '') {
    $errors[] = 'Contact information is required.';
}

if ($phoneModel === '') {
    $errors[] = 'Phone model is required.';
}

if ($issue === '') {
    $errors[] = 'Please describe the issue.';
}

if (!$date) {
    $errors[] = 'Please select a date.';
}

if (!$time) {
    $errors[] = 'Please select a time.';
}

if ($errors) {
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_old'] = [
        'name' => $name,
        'contact' => $contact,
        'phone_model' => $phoneModel,
        'issue' => $issue,
        'date' => $date,
        'time' => $time
    ];
    header('Location: ../booking.php');
    exit;
}

$stmt = $conn->prepare('INSERT INTO bookings (name, contact, phone_model, issue, date, time) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->bind_param('ssssss', $name, $contact, $phoneModel, $issue, $date, $time);

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare('INSERT INTO bookings (user_id, name, contact, phone_model, issue, date, time) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('issssss', $userId, $name, $contact, $phoneModel, $issue, $date, $time);

if ($stmt->execute()) {
    $_SESSION['booking_success'] = 'Your booking has been received! We will confirm shortly.';
    unset($_SESSION['booking_old']);
    header('Location: ../booking.php');
    exit;
}

$_SESSION['booking_errors'] = ['Unable to submit booking right now. Please try again later.'];
header('Location: ../booking.php');
exit;

