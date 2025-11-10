<?php
require_once __DIR__ . '/functions.php';
ensure_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin Panel</title>
	<link rel="stylesheet" href="/systemFinals/admin/assets/css/style.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
	<header class="admin-header">
		<div class="branding">
			<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
				<i class="fa-solid fa-bars"></i>
			</button>
			<h1>Admin</h1>
		</div>
		<div class="header-actions">
			<span class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
			<a class="btn btn-light" href="/systemFinals/login.php?logout=1">
				<i class="fa-solid fa-right-from-bracket"></i> Logout
			</a>
		</div>
	</header>


