<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Status update (simple - no proof requirement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'update_status') {
	$id = (int)post('id');
	$status = post('status');
	$allowed = ['pending','out_for_delivery','delivered','received'];
	if ($id > 0 && in_array($status, $allowed, true)) {
		$stmt = $conn->prepare("UPDATE orders SET order_status=? WHERE id=?");
		$stmt->bind_param('si', $status, $id);
		$stmt->execute();
		$stmt->close();
		set_admin_flash('success', 'Order status updated successfully.');
	}
	header("Location: /systemFinals/admin/orders.php?" . http_build_query($_GET));
	exit;
}

// Separate proof upload action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'upload_proof') {
	$id = (int)post('id');
	$error = '';
	
	if ($id > 0) {
		if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
			$error = 'Please select a proof photo to upload.';
		} else {
			$file = $_FILES['proof_image'];
			
			// Validate file type (JPG, PNG only)
			$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
			$fileType = mime_content_type($file['tmp_name']);
			
			if (!in_array($fileType, $allowedTypes)) {
				$error = 'Invalid file type. Only JPG and PNG images are allowed.';
			} elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB max
				$error = 'File size exceeds 5MB limit.';
			} else {
				// Create uploads/proofs directory if it doesn't exist
				$uploadDir = __DIR__ . '/../uploads/proofs/';
				if (!is_dir($uploadDir)) {
					mkdir($uploadDir, 0755, true);
				}
				
				// Generate unique filename
				$fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
				$fileName = 'proof_' . $id . '_' . time() . '.' . $fileExtension;
				$filePath = $uploadDir . $fileName;
				$relativePath = 'uploads/proofs/' . $fileName;
				
				// Move uploaded file
				if (move_uploaded_file($file['tmp_name'], $filePath)) {
					// Get current order to check if there's an old proof image to delete
					$stmt = $conn->prepare('SELECT proof_image FROM orders WHERE id = ?');
					$stmt->bind_param('i', $id);
					$stmt->execute();
					$result = $stmt->get_result();
					$order = $result->fetch_assoc();
					$stmt->close();
					
					// Delete old proof image if it exists
					if ($order && !empty($order['proof_image']) && file_exists(__DIR__ . '/../' . $order['proof_image'])) {
						@unlink(__DIR__ . '/../' . $order['proof_image']);
					}
					
					// Update order with proof image
					$stmt = $conn->prepare("UPDATE orders SET proof_image=? WHERE id=?");
					$stmt->bind_param('si', $relativePath, $id);
					$stmt->execute();
					$stmt->close();
					set_admin_flash('success', 'Proof photo uploaded successfully.');
				} else {
					$error = 'Failed to upload proof image.';
				}
			}
		}
	} else {
		$error = 'Invalid order ID.';
	}
	
	if ($error) {
		set_admin_flash('error', $error);
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
  AND o.status != 'cancelled' 
  AND o.order_status != 'cancelled'
  AND NOT (o.order_status = 'delivered' AND o.proof_image IS NOT NULL AND o.proof_image != '' AND LENGTH(TRIM(o.proof_image)) > 0)
ORDER BY o.order_date DESC";

$flash = pull_admin_flash();

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
	<?php if ($flash): ?>
		<div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" style="margin-bottom: 1rem;">
			<?php echo htmlspecialchars($flash['message']); ?>
		</div>
	<?php endif; ?>
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
						<div class="actions" style="display: flex; flex-direction: column; gap: 8px;">
							<!-- Status Update Form -->
							<form method="post" style="display: flex; gap: 8px; align-items: center;">
								<input type="hidden" name="action" value="update_status">
								<input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
								<select class="input" name="status" style="flex: 1;">
									<?php foreach (['pending','out_for_delivery','delivered','received'] as $st): ?>
										<option value="<?php echo $st; ?>" <?php echo $o['order_status']===$st?'selected':''; ?>>
											<?php echo ucwords(str_replace('_',' ',$st)); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<button class="btn btn-primary" type="submit">Update Status</button>
							</form>
							
							<!-- Proof Upload Form -->
							<form method="post" enctype="multipart/form-data" style="display: flex; gap: 8px; align-items: center;">
								<input type="hidden" name="action" value="upload_proof">
								<input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
								<input type="file" name="proof_image" accept="image/jpeg,image/jpg,image/png" class="input" style="flex: 1;">
								<button class="btn btn-outline" type="submit">
									<i class="fa-solid fa-upload"></i> Upload Proof
								</button>
							</form>
							
							<?php if (!empty($o['proof_image'])): ?>
								<a href="/systemFinals/<?php echo htmlspecialchars($o['proof_image']); ?>" target="_blank" class="btn btn-outline" style="font-size: 0.875rem; text-align: center;">
									<i class="fa-solid fa-image"></i> View Proof
								</a>
							<?php endif; ?>
						</div>
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


