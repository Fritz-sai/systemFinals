<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'update_status') {
	$id = (int)post('id');
	$status = post('status');
	$allowed = ['pending','out_for_delivery','delivered','received'];
	if ($id > 0 && in_array($status, $allowed, true)) {
		$stmt = $conn->prepare("UPDATE orders SET order_status=? WHERE id=?");
		$stmt->bind_param('si', $status, $id);
		$stmt->execute();
		$stmt->close();
	}
	header("Location: /systemFinals/admin/orders.php?" . http_build_query($_GET));
	exit;
}

// Filters
$dateFrom = get('from');
$dateTo = get('to');
$where = "1=1";
$params = [];
$types = '';
if ($dateFrom) { $where .= " AND DATE(order_date) >= ?"; $params[] = $dateFrom; $types .= 's'; }
if ($dateTo)   { $where .= " AND DATE(order_date) <= ?"; $params[] = $dateTo;   $types .= 's'; }

$sql = "SELECT o.*, u.name as customer_name, p.name as product_name 
FROM orders o 
JOIN users u ON u.id=o.user_id 
JOIN products p ON p.id=o.product_id
WHERE $where
ORDER BY o.order_date DESC";

if ($params) {
	$stmt = $conn->prepare($sql);
	$stmt->bind_param($types, ...$params);
	$stmt->execute();
	$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	$stmt->close();
} else {
	$res = $conn->query($sql);
	$orders = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
	if ($res) $res->close();
}
?>

<main class="admin-main">
	<div class="section-header">
		<h2>Orders</h2>
		<form class="search-row" method="get">
			<label>From <input class="input" type="date" name="from" value="<?php echo htmlspecialchars($dateFrom ?? ''); ?>"></label>
			<label>To <input class="input" type="date" name="to" value="<?php echo htmlspecialchars($dateTo ?? ''); ?>"></label>
			<button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
			<a class="btn" href="/systemFinals/admin/orders.php">Reset</a>
		</form>
	</div>

	<div class="card" style="overflow-x:auto;">
		<table class="table">
			<thead>
				<tr>
					<th>Date</th>
					<th>Customer</th>
					<th>Product</th>
					<th>Qty</th>
					<th>Total</th>
					<th>Status</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($orders as $o): ?>
				<tr>
					<td><?php echo htmlspecialchars(date('Y-m-d', strtotime($o['order_date']))); ?></td>
					<td><?php echo htmlspecialchars($o['customer_name']); ?></td>
					<td><?php echo htmlspecialchars($o['product_name']); ?></td>
					<td><?php echo (int)$o['quantity']; ?></td>
					<td><?php echo format_currency($o['total']); ?></td>
					<td>
						<span class="status-badge status-<?php echo htmlspecialchars(str_replace('_','-',$o['order_status'])); ?>">
							<?php echo htmlspecialchars(ucwords(str_replace('_',' ',$o['order_status']))); ?>
						</span>
					</td>
					<td>
						<form method="post" class="actions">
							<input type="hidden" name="action" value="update_status">
							<input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
							<select class="input" name="status">
								<?php foreach (['pending','out_for_delivery','delivered','received'] as $st): ?>
									<option value="<?php echo $st; ?>" <?php echo $o['order_status']===$st?'selected':''; ?>>
										<?php echo ucwords(str_replace('_',' ',$st)); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<button class="btn btn-primary" type="submit">Update</button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
				<?php if (empty($orders)): ?>
					<tr><td colspan="7">No orders found.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


