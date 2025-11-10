<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Quick stats (week/month)
$now = new DateTime();
$startOfWeek = (clone $now)->modify('monday this week')->format('Y-m-d 00:00:00');
$startOfMonth = $now->format('Y-m-01 00:00:00');

// Products sold (use delivered/received and completed)
$qWeek = $conn->prepare("SELECT COALESCE(SUM(quantity),0) qty FROM orders WHERE order_date >= ? AND (order_status IN ('delivered','received') OR status='completed')");
$qWeek->bind_param('s', $startOfWeek);
$qWeek->execute();
$qtyWeek = (int)($qWeek->get_result()->fetch_assoc()['qty'] ?? 0);
$qWeek->close();
$qMonth = $conn->prepare("SELECT COALESCE(SUM(quantity),0) qty FROM orders WHERE order_date >= ? AND (order_status IN ('delivered','received') OR status='completed')");
$qMonth->bind_param('s', $startOfMonth);
$qMonth->execute();
$qtyMonth = (int)($qMonth->get_result()->fetch_assoc()['qty'] ?? 0);
$qMonth->close();

// Bookings total + pending/done (week/month)
$bWeek = $conn->prepare("SELECT 
	SUM(status='pending') pending,
	SUM(status IN ('completed','done')) done,
	COUNT(*) total
FROM bookings WHERE created_at >= ?");
$bWeek->bind_param('s', $startOfWeek);
$bWeek->execute();
$bW = $bWeek->get_result()->fetch_assoc() ?: ['pending'=>0,'done'=>0,'total'=>0];
$bWeek->close();
$bMonth = $conn->prepare("SELECT 
	SUM(status='pending') pending,
	SUM(status IN ('completed','done')) done,
	COUNT(*) total
FROM bookings WHERE created_at >= ?");
$bMonth->bind_param('s', $startOfMonth);
$bMonth->execute();
$bM = $bMonth->get_result()->fetch_assoc() ?: ['pending'=>0,'done'=>0,'total'=>0];
$bMonth->close();

// Orders total
$ordersTotal = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'] ?? 0;
// Users total (active only)
$usersTotal = $conn->query("SELECT COUNT(*) c FROM users WHERE COALESCE(active,1)=1")->fetch_assoc()['c'] ?? 0;
?>

<main class="admin-main">
	<div class="section-header">
		<h2>Dashboard</h2>
	</div>

	<section class="cards grid-3">
		<div class="card">
			<h3><i class="fa-solid fa-box icon"></i> Products Sold</h3>
			<div class="value"><?php echo $qtyWeek; ?> <span class="sub">this week</span></div>
			<div class="sub">This month: <?php echo $qtyMonth; ?></div>
		</div>
		<div class="card">
			<h3><i class="fa-solid fa-calendar-check icon"></i> Bookings</h3>
			<div class="value"><?php echo (int)$bW['total']; ?> <span class="sub">this week</span></div>
			<div class="sub">This month: <?php echo (int)$bM['total']; ?></div>
		</div>
		<div class="card">
			<h3><i class="fa-solid fa-clock icon"></i> Pending Bookings</h3>
			<div class="value"><?php echo (int)$bW['pending']; ?> <span class="sub">this week</span></div>
			<div class="sub">This month: <?php echo (int)$bM['pending']; ?></div>
		</div>
		<div class="card">
			<h3><i class="fa-solid fa-check-circle icon"></i> Done Bookings</h3>
			<div class="value"><?php echo (int)$bW['done']; ?> <span class="sub">this week</span></div>
			<div class="sub">This month: <?php echo (int)$bM['done']; ?></div>
		</div>
		<div class="card">
			<h3><i class="fa-solid fa-receipt icon"></i> Total Orders</h3>
			<div class="value"><?php echo (int)$ordersTotal; ?></div>
		</div>
		<div class="card">
			<h3><i class="fa-solid fa-users icon"></i> Total Users</h3>
			<div class="value"><?php echo (int)$usersTotal; ?></div>
		</div>
	</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
