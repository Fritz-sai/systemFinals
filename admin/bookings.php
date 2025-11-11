<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'update_booking_status') {
	$id = (int)post('id');
	$status = post('status');
	$allowed = ['pending','approved','done','in_progress','completed','cancelled'];
	if ($id > 0 && in_array($status, $allowed, true)) {
		// Normalize 'done' as 'completed' for existing schema
		$mapped = $status === 'done' ? 'completed' : $status;
		$stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
		$stmt->bind_param('si', $mapped, $id);
		$stmt->execute();
		$stmt->close();
	}
	header("Location: /systemFinals/admin/bookings.php?" . http_build_query($_GET));
	exit;
}

// Filters by range
$range = get('range', 'week');
if ($range === 'week') {
	$start = (new DateTime())->modify('monday this week')->format('Y-m-d');
} elseif ($range === 'month') {
	$start = date('Y-m-01');
} else {
	$start = '1970-01-01';
}

$stmt = $conn->prepare("SELECT * FROM bookings WHERE DATE(created_at) >= ? AND status != 'cancelled' ORDER BY created_at DESC");
$stmt->bind_param('s', $start);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<main class="admin-main">
	<div class="section-header">
		<h2>Bookings</h2>
		<form class="search-row" method="get">
			<select class="input" name="range">
				<option value="week" <?php echo $range==='week'?'selected':''; ?>>This Week</option>
				<option value="month" <?php echo $range==='month'?'selected':''; ?>>This Month</option>
				<option value="all" <?php echo $range==='all'?'selected':''; ?>>All Time</option>
			</select>
			<button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Apply</button>
		</form>
	</div>

	<div class="card" style="overflow-x:auto;">
		<table class="table">
			<thead>
				<tr>
					<th>Date</th>
					<th>Customer</th>
					<th>Service</th>
					<th>Issue</th>
					<th>Status</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($bookings as $b): ?>
				<tr>
					<td><?php echo htmlspecialchars(date('Y-m-d', strtotime($b['created_at']))); ?></td>
					<td><?php echo htmlspecialchars($b['name']); ?> <div class="sub"><?php echo htmlspecialchars($b['contact']); ?></div></td>
					<td><?php echo htmlspecialchars($b['phone_model']); ?></td>
					<td><?php echo htmlspecialchars($b['issue']); ?></td>
					<td>
						<span class="status-badge status-<?php echo htmlspecialchars(str_replace('_','-',$b['status'])); ?>">
							<?php echo htmlspecialchars(ucwords(str_replace('_',' ',$b['status']))); ?>
						</span>
					</td>
					<td>
						<form method="post" class="actions">
							<input type="hidden" name="action" value="update_booking_status">
							<input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
							<select class="input" name="status">
								<?php foreach (['pending','approved','in_progress','done','completed','cancelled'] as $st): ?>
									<option value="<?php echo $st; ?>" <?php echo $b['status']===$st?'selected':''; ?>>
										<?php echo ucwords(str_replace('_',' ',$st)); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<button class="btn btn-primary" type="submit">Update</button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
				<?php if (empty($bookings)): ?>
					<tr><td colspan="6">No bookings found.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
