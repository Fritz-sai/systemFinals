<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Filters
$dateFrom = get('from');
$dateTo = get('to');
$type = get('type', 'all'); // 'all', 'bookings', 'orders'

$whereBookings = "status IN ('completed', 'done')";
$whereOrders = "order_status = 'delivered' AND status != 'cancelled' AND proof_image IS NOT NULL AND proof_image != ''";

$paramsBookings = [];
$paramsOrders = [];
$typesBookings = '';
$typesOrders = '';

if ($dateFrom) {
	$whereBookings .= " AND DATE(created_at) >= ?";
	$whereOrders .= " AND DATE(order_date) >= ?";
	$paramsBookings[] = $dateFrom;
	$paramsOrders[] = $dateFrom;
	$typesBookings .= 's';
	$typesOrders .= 's';
}
if ($dateTo) {
	$whereBookings .= " AND DATE(created_at) <= ?";
	$whereOrders .= " AND DATE(order_date) <= ?";
	$paramsBookings[] = $dateTo;
	$paramsOrders[] = $dateTo;
	$typesBookings .= 's';
	$typesOrders .= 's';
}

// Fetch completed bookings
$bookings = [];
if ($type === 'all' || $type === 'bookings') {
	$sqlBookings = "SELECT 
		id,
		'booking' as transaction_type,
		name as customer_name,
		contact,
		phone_model,
		issue,
		date,
		time,
		status,
		created_at as transaction_date,
		NULL as total,
		NULL as quantity,
		NULL as product_name
	FROM bookings 
	WHERE $whereBookings
	ORDER BY created_at DESC";
	
	if ($paramsBookings) {
		$stmt = $conn->prepare($sqlBookings);
		$stmt->bind_param($typesBookings, ...$paramsBookings);
		$stmt->execute();
		$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
		$stmt->close();
	} else {
		$res = $conn->query($sqlBookings);
		$bookings = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
		if ($res) $res->close();
	}
}

// Fetch delivered orders
$orders = [];
if ($type === 'all' || $type === 'orders') {
	$sqlOrders = "SELECT 
		o.id,
		'order' as transaction_type,
		u.name as customer_name,
		u.email as contact,
		NULL as phone_model,
		NULL as issue,
		NULL as date,
		NULL as time,
		o.order_status as status,
		o.order_date as transaction_date,
		o.total,
		o.quantity,
		p.name as product_name,
		o.proof_image
	FROM orders o
	JOIN users u ON u.id = o.user_id
	JOIN products p ON p.id = o.product_id
	WHERE $whereOrders
	ORDER BY o.order_date DESC";
	
	if ($paramsOrders) {
		$stmt = $conn->prepare($sqlOrders);
		$stmt->bind_param($typesOrders, ...$paramsOrders);
		$stmt->execute();
		$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
		$stmt->close();
	} else {
		$res = $conn->query($sqlOrders);
		$orders = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
		if ($res) $res->close();
	}
}

// Merge and sort by date
$transactions = array_merge($bookings, $orders);
usort($transactions, function($a, $b) {
	return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
});

// Calculate totals
$totalBookings = count($bookings);
$totalOrders = count($orders);
$totalRevenue = array_sum(array_column($orders, 'total'));
?>

<main class="admin-main">
	<div class="section-header">
		<h2>Transaction History</h2>
		<form class="search-row" method="get">
			<select class="input" name="type">
				<option value="all" <?php echo $type==='all'?'selected':''; ?>>All Transactions</option>
				<option value="bookings" <?php echo $type==='bookings'?'selected':''; ?>>Bookings Only</option>
				<option value="orders" <?php echo $type==='orders'?'selected':''; ?>>Orders Only</option>
			</select>
			<label>From <input class="input" type="date" name="from" value="<?php echo htmlspecialchars($dateFrom ?? ''); ?>"></label>
			<label>To <input class="input" type="date" name="to" value="<?php echo htmlspecialchars($dateTo ?? ''); ?>"></label>
			<button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
			<a class="btn" href="/systemFinals/admin/transaction_history.php">Reset</a>
		</form>
	</div>

	<!-- Summary Cards -->
	<div class="grid-3" style="margin-bottom: 2rem;">
		<div class="card">
			<h3>Completed Bookings</h3>
			<div class="value" style="font-size: 2rem; margin-top: 0.5rem;"><?php echo $totalBookings; ?></div>
		</div>
		<div class="card">
			<h3>Delivered Orders</h3>
			<div class="value" style="font-size: 2rem; margin-top: 0.5rem;"><?php echo $totalOrders; ?></div>
		</div>
		<div class="card">
			<h3>Total Revenue</h3>
			<div class="value" style="font-size: 2rem; margin-top: 0.5rem;"><?php echo format_currency($totalRevenue); ?></div>
		</div>
	</div>

	<div class="card" style="overflow-x:auto;">
		<table class="table">
			<thead>
				<tr>
					<th>Date</th>
					<th>Type</th>
					<th>Customer</th>
					<th>Details</th>
					<th>Status</th>
					<th>Amount</th>
					<th>Proof</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($transactions as $t): ?>
				<tr>
					<td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($t['transaction_date']))); ?></td>
					<td>
						<span class="status-badge <?php echo $t['transaction_type'] === 'booking' ? 'status-approved' : 'status-delivered'; ?>">
							<?php echo $t['transaction_type'] === 'booking' ? '📅 Booking' : '📦 Order'; ?>
						</span>
					</td>
					<td>
						<?php echo htmlspecialchars($t['customer_name']); ?>
						<?php if ($t['contact']): ?>
							<div class="sub"><?php echo htmlspecialchars($t['contact']); ?></div>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($t['transaction_type'] === 'booking'): ?>
							<strong><?php echo htmlspecialchars($t['phone_model']); ?></strong>
							<div class="sub"><?php echo htmlspecialchars($t['issue']); ?></div>
							<?php if ($t['date'] && $t['time']): ?>
								<div class="sub">Scheduled: <?php echo htmlspecialchars($t['date'] . ' ' . $t['time']); ?></div>
							<?php endif; ?>
						<?php else: ?>
							<strong><?php echo htmlspecialchars($t['product_name']); ?></strong>
							<div class="sub">Qty: <?php echo (int)$t['quantity']; ?></div>
						<?php endif; ?>
					</td>
					<td>
						<span class="status-badge status-<?php echo htmlspecialchars(str_replace('_','-',$t['status'])); ?>">
							<?php echo htmlspecialchars(ucwords(str_replace('_',' ',$t['status']))); ?>
						</span>
					</td>
					<td>
						<?php if ($t['transaction_type'] === 'order' && $t['total']): ?>
							<strong><?php echo format_currency($t['total']); ?></strong>
						<?php else: ?>
							<span class="sub">—</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($t['transaction_type'] === 'order' && !empty($t['proof_image'])): ?>
							<button type="button" class="btn btn-outline view-proof-btn" data-proof-path="/systemFinals/<?php echo htmlspecialchars($t['proof_image']); ?>" style="font-size: 0.875rem;">
								<i class="fa-solid fa-image"></i> View Proof
							</button>
						<?php else: ?>
							<span class="sub">—</span>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
				<?php if (empty($transactions)): ?>
					<tr><td colspan="7">No completed transactions found.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</main>

<!-- View Proof Modal -->
<div id="viewProofModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
	<div class="modal-content" style="background-color: #fff; margin: 5% auto; padding: 0; border: none; border-radius: 8px; width: 90%; max-width: 800px; max-height: 90vh; overflow: auto;">
		<div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid #e5e7eb;">
			<h3 style="margin: 0;">Proof of Delivery</h3>
			<button type="button" class="modal-close" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
		</div>
		<div style="padding: 1rem; text-align: center;">
			<img id="proof_viewer_img" src="" alt="Proof of delivery" style="max-width: 100%; height: auto; border-radius: 4px;">
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const viewProofButtons = document.querySelectorAll('.view-proof-btn');
	const viewProofModal = document.getElementById('viewProofModal');
	const proofViewerImg = document.getElementById('proof_viewer_img');
	const modalClose = document.querySelector('#viewProofModal .modal-close');
	
	// View Proof Button Handlers
	viewProofButtons.forEach(btn => {
		btn.addEventListener('click', () => {
			const proofPath = btn.getAttribute('data-proof-path');
			if (proofPath) {
				proofViewerImg.src = proofPath;
				viewProofModal.style.display = 'block';
			}
		});
	});
	
	// Close modal
	if (modalClose) {
		modalClose.addEventListener('click', () => {
			viewProofModal.style.display = 'none';
		});
	}
	
	// Close modal when clicking outside
	if (viewProofModal) {
		viewProofModal.addEventListener('click', (e) => {
			if (e.target === viewProofModal) {
				viewProofModal.style.display = 'none';
			}
		});
	}
	
	// Close modal with Escape key
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && viewProofModal.style.display === 'block') {
			viewProofModal.style.display = 'none';
		}
	});
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

