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
		set_admin_flash('success', 'Booking status updated successfully.');
	}
	header("Location: /systemFinals/admin/bookings.php?" . http_build_query($_GET));
	exit;
}

// Separate proof upload action for bookings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'upload_booking_proof') {
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
				$fileName = 'booking_proof_' . $id . '_' . time() . '.' . $fileExtension;
				$filePath = $uploadDir . $fileName;
				$relativePath = 'uploads/proofs/' . $fileName;
				
				// Move uploaded file
				if (move_uploaded_file($file['tmp_name'], $filePath)) {
					// Get current booking to check if there's an old proof image to delete
					$stmt = $conn->prepare('SELECT proof_image FROM bookings WHERE id = ?');
					$stmt->bind_param('i', $id);
					$stmt->execute();
					$result = $stmt->get_result();
					$booking = $result->fetch_assoc();
					$stmt->close();
					
					// Delete old proof image if it exists
					if ($booking && !empty($booking['proof_image']) && file_exists(__DIR__ . '/../' . $booking['proof_image'])) {
						@unlink(__DIR__ . '/../' . $booking['proof_image']);
					}
					
					// Update booking with proof image
					$stmt = $conn->prepare("UPDATE bookings SET proof_image=? WHERE id=?");
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
		$error = 'Invalid booking ID.';
	}
	
	if ($error) {
		set_admin_flash('error', $error);
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

$stmt = $conn->prepare("SELECT * FROM bookings WHERE DATE(created_at) >= ? AND status != 'cancelled' AND NOT (status IN ('completed', 'done') AND proof_image IS NOT NULL AND proof_image != '' AND LENGTH(TRIM(proof_image)) > 0) ORDER BY created_at DESC");
$stmt->bind_param('s', $start);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$flash = pull_admin_flash();
?>

<main class="admin-main">
	<?php if ($flash): ?>
		<div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" style="margin-bottom: 1rem;">
			<?php echo htmlspecialchars($flash['message']); ?>
		</div>
	<?php endif; ?>
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
						<div class="actions" style="display: flex; flex-direction: column; gap: 8px;">
							<!-- Status Update Form -->
							<form method="post" style="display: flex; gap: 8px; align-items: center;">
								<input type="hidden" name="action" value="update_booking_status">
								<input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
								<select class="input" name="status" style="flex: 1;">
									<?php foreach (['pending','approved','in_progress','done','completed','cancelled'] as $st): ?>
										<option value="<?php echo $st; ?>" <?php echo $b['status']===$st?'selected':''; ?>>
											<?php echo ucwords(str_replace('_',' ',$st)); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<button class="btn btn-primary" type="submit">Update Status</button>
							</form>
							
							<!-- Proof Upload Form -->
							<form method="post" enctype="multipart/form-data" style="display: flex; gap: 8px; align-items: center;">
								<input type="hidden" name="action" value="upload_booking_proof">
								<input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
								<input type="file" name="proof_image" accept="image/jpeg,image/jpg,image/png" class="input" style="flex: 1;">
								<button class="btn btn-outline" type="submit">
									<i class="fa-solid fa-upload"></i> Upload Proof
								</button>
							</form>
							
							<?php if (!empty($b['proof_image'])): ?>
								<a href="/systemFinals/<?php echo htmlspecialchars($b['proof_image']); ?>" target="_blank" class="btn btn-outline" style="font-size: 0.875rem; text-align: center;">
									<i class="fa-solid fa-image"></i> View Proof
								</a>
							<?php endif; ?>
						</div>
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
