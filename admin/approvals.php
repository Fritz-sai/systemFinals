<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$flash = pull_admin_flash();

// Handle approvals/rejections
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$type = post('type');
	$id = (int)post('id');
	$action = post('action');
	$reason = trim((string)post('reason', ''));

	if ($type === 'booking' && $id > 0) {
		$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$booking = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if (!$booking) {
			set_admin_flash('error', 'Booking not found.');
			header('Location: /systemFinals/admin/approvals.php');
			exit;
		}

		$bookingUserId = (int)($booking['user_id'] ?? 0);
		// Try to map contact email to user if booking isn't linked
		if ($bookingUserId <= 0 && !empty($booking['contact']) && filter_var($booking['contact'], FILTER_VALIDATE_EMAIL)) {
			$maybeUserId = findUserIdByEmail($conn, $booking['contact']);
			if ($maybeUserId > 0) {
				$bookingUserId = $maybeUserId;
				$upd = $conn->prepare("UPDATE bookings SET user_id = ? WHERE id = ?");
				$upd->bind_param('ii', $bookingUserId, $id);
				$upd->execute();
				$upd->close();
			}
		}

		if ($action === 'approve') {
			$stmt = $conn->prepare("UPDATE bookings SET status = 'approved', status_message = NULL WHERE id = ?");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->close();
			if ($bookingUserId > 0) {
				$title = 'Booking Approved';
				$message = "Your booking for {$booking['phone_model']} on {$booking['date']} at {$booking['time']} has been approved. We look forward to serving you!";
				createNotification($conn, $bookingUserId, $title, $message, 'booking');
			}
			set_admin_flash('success', 'Booking approved.');
		} elseif ($action === 'reject') {
			if ($reason === '') {
				set_admin_flash('error', 'Please provide a reason for the rejection.');
				header('Location: /systemFinals/admin/approvals.php');
				exit;
			}
			$stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled', status_message = ? WHERE id = ?");
			$stmt->bind_param('si', $reason, $id);
			$stmt->execute();
			$stmt->close();

			if (!empty($booking['contact']) && filter_var($booking['contact'], FILTER_VALIDATE_EMAIL)) {
				$message = "Hello {$booking['name']},\n\nWe regret to inform you that your booking request for {$booking['phone_model']} on {$booking['date']} at {$booking['time']} was rejected.\n\nReason: {$reason}\n\nPlease feel free to submit another booking or contact us for assistance.";
				sendNotificationEmail($booking['contact'], 'Booking Update', $message);
			}

			if ($bookingUserId > 0) {
				$title = 'Booking Rejected';
				$message = "We're sorry, but your booking for {$booking['phone_model']} on {$booking['date']} at {$booking['time']} was rejected.\nReason: {$reason}";
				createNotification($conn, $bookingUserId, $title, $message, 'booking');
			}

			set_admin_flash('success', 'Booking rejected and customer notified.');
		}
	} elseif ($type === 'order' && $id > 0) {
		// Approve => move into processing; Reject => cancelled
		$stmt = $conn->prepare("SELECT o.id, o.user_id, o.quantity, o.status, u.email, u.name AS customer_name, p.name AS product_name FROM orders o JOIN users u ON u.id = o.user_id JOIN products p ON p.id = o.product_id WHERE o.id = ?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$order = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if (!$order) {
			set_admin_flash('error', 'Order not found.');
			header('Location: /systemFinals/admin/approvals.php');
			exit;
		}

		if ($action === 'approve') {
			$stmt = $conn->prepare("UPDATE orders SET status = 'processing', order_status = 'pending', status_message = NULL WHERE id = ?");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->close();
			$title = 'Order Approved';
			$message = "Your order for {$order['product_name']} is now being processed. We will keep you updated!";
			createNotification($conn, (int)$order['user_id'], $title, $message, 'order');
			set_admin_flash('success', 'Order approved and marked as processing.');
		} elseif ($action === 'reject') {
			if ($reason === '') {
				set_admin_flash('error', 'Please provide a reason for the rejection.');
				header('Location: /systemFinals/admin/approvals.php');
				exit;
			}
			$stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', order_status = 'cancelled', status_message = ? WHERE id = ?");
			$stmt->bind_param('si', $reason, $id);
			$stmt->execute();
			$stmt->close();

			if (!empty($order['email'])) {
				$message = "Hello {$order['customer_name']},\n\nYour order for {$order['product_name']} was rejected.\n\nReason: {$reason}\n\nYou can place a new order anytime or contact our support for help.";
				sendNotificationEmail($order['email'], 'Order Update', $message);
			}

			$title = 'Order Rejected';
			$message = "Unfortunately your order for {$order['product_name']} was rejected.\nReason: {$reason}";
			createNotification($conn, (int)$order['user_id'], $title, $message, 'order');

			set_admin_flash('success', 'Order rejected and customer notified.');
		}
	}

	header('Location: /systemFinals/admin/approvals.php');
	exit;
}

// Fetch pending bookings (awaiting approval)
$pendingBookings = [];
$resB = $conn->query("SELECT id, user_id, name, contact, phone_model, issue, date, time, status, created_at FROM bookings WHERE status = 'pending' ORDER BY created_at DESC");
if ($resB) {
	$pendingBookings = $resB->fetch_all(MYSQLI_ASSOC);
	$resB->close();
}

// Fetch orders awaiting approval: use status 'pending' as awaiting approval
$pendingOrders = [];
$resO = $conn->query("SELECT o.id, o.user_id, o.order_date, o.quantity, o.total, o.status, o.order_status, u.name AS customer_name, p.name AS product_name
FROM orders o
JOIN users u ON u.id = o.user_id
JOIN products p ON p.id = o.product_id
WHERE o.status = 'pending'
ORDER BY o.order_date DESC");
if ($resO) {
	$pendingOrders = $resO->fetch_all(MYSQLI_ASSOC);
	$resO->close();
}
?>

<main class="admin-main">
	<div class="section-header">
		<h2>Approvals</h2>
	</div>
	<?php if ($flash): ?>
		<div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
			<?php echo htmlspecialchars($flash['message']); ?>
		</div>
	<?php endif; ?>

	<div class="grid-2">
		<div class="card">
			<h3>Pending Bookings</h3>
			<div style="margin-top:12px; overflow-x:auto;">
				<table class="table">
					<thead>
						<tr>
							<th>Requested</th>
							<th>Customer</th>
							<th>Service</th>
							<th>Issue</th>
							<th>Schedule</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($pendingBookings as $b): ?>
						<tr>
							<td><?php echo htmlspecialchars(date('Y-m-d', strtotime($b['created_at']))); ?></td>
							<td><?php echo htmlspecialchars($b['name']); ?><div class="sub"><?php echo htmlspecialchars($b['contact']); ?></div></td>
							<td><?php echo htmlspecialchars($b['phone_model']); ?></td>
							<td><?php echo htmlspecialchars($b['issue']); ?></td>
							<td><?php echo htmlspecialchars($b['date'] . ' ' . $b['time']); ?></td>
							<td class="actions">
								<form method="post">
									<input type="hidden" name="type" value="booking">
									<input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
									<input type="hidden" name="action" value="approve">
									<button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i> Approve</button>
								</form>
								<form method="post" class="reject-form">
									<input type="hidden" name="type" value="booking">
									<input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
									<input type="hidden" name="action" value="reject">
									<input type="hidden" name="reason" class="reason-input" value="">
									<button class="btn btn-danger" type="submit"><i class="fa-solid fa-xmark"></i> Reject</button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
						<?php if (empty($pendingBookings)): ?>
							<tr><td colspan="6">No pending bookings.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="card">
			<h3>Pending Orders</h3>
			<div style="margin-top:12px; overflow-x:auto;">
				<table class="table">
					<thead>
						<tr>
							<th>Date</th>
							<th>Customer</th>
							<th>Product</th>
							<th>Qty</th>
							<th>Total</th>
							<th>Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($pendingOrders as $o): ?>
						<tr>
							<td><?php echo htmlspecialchars(date('Y-m-d', strtotime($o['order_date']))); ?></td>
							<td><?php echo htmlspecialchars($o['customer_name']); ?></td>
							<td><?php echo htmlspecialchars($o['product_name']); ?></td>
							<td><?php echo (int)$o['quantity']; ?></td>
							<td><?php echo format_currency($o['total']); ?></td>
							<td><span class="status-badge status-pending">Pending</span></td>
							<td class="actions">
								<form method="post">
									<input type="hidden" name="type" value="order">
									<input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
									<input type="hidden" name="action" value="approve">
									<button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i> Approve</button>
								</form>
								<form method="post" class="reject-form">
									<input type="hidden" name="type" value="order">
									<input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
									<input type="hidden" name="action" value="reject">
									<input type="hidden" name="reason" class="reason-input" value="">
									<button class="btn btn-danger" type="submit"><i class="fa-solid fa-xmark"></i> Reject</button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
						<?php if (empty($pendingOrders)): ?>
							<tr><td colspan="7">No pending orders.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('.reject-form').forEach(form => {
		form.addEventListener('submit', (event) => {
			const reason = prompt('Enter the rejection reason (this will be sent to the customer):');
			if (!reason || !reason.trim()) {
				event.preventDefault();
				alert('Rejection reason is required.');
				return;
			}
			const input = form.querySelector('.reason-input');
			if (input) {
				input.value = reason.trim();
			}
		});
	});
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


