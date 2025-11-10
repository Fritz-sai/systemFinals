<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Actions: delete, deactivate/activate
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$act = post('action');
	$id = (int)post('id');
	if ($id > 0) {
		if ($act === 'delete') {
			$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->close();
		} elseif ($act === 'deactivate') {
			$stmt = $conn->prepare("UPDATE users SET active=0 WHERE id=?");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->close();
		} elseif ($act === 'activate') {
			$stmt = $conn->prepare("UPDATE users SET active=1 WHERE id=?");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->close();
		}
	}
	header("Location: /systemFinals/admin/users.php");
	exit;
}

$res = $conn->query("SELECT id, name, email, role, created_at, COALESCE(active,1) active FROM users ORDER BY created_at DESC");
$users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->close();
?>

<main class="admin-main">
	<div class="section-header">
		<h2>Users</h2>
	</div>
	<div class="card" style="overflow-x:auto;">
		<table class="table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Email</th>
					<th>Role</th>
					<th>Registered</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($users as $u): ?>
					<tr>
						<td><?php echo htmlspecialchars($u['name']); ?></td>
						<td><?php echo htmlspecialchars($u['email']); ?></td>
						<td><?php echo htmlspecialchars($u['role']); ?></td>
						<td><?php echo htmlspecialchars(date('Y-m-d', strtotime($u['created_at']))); ?></td>
						<td>
							<span class="status-badge <?php echo $u['active'] ? 'status-approved' : 'status-cancelled'; ?>">
								<?php echo $u['active'] ? 'Active' : 'Deactivated'; ?>
							</span>
						</td>
						<td class="actions">
							<?php if ((int)$u['id'] !== (int)($_SESSION['user_id'] ?? -1)): ?>
								<form method="post" onsubmit="return confirm('Delete this user?');">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
									<button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> Delete</button>
								</form>
								<?php if ($u['active']): ?>
									<form method="post">
										<input type="hidden" name="action" value="deactivate">
										<input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
										<button class="btn" type="submit"><i class="fa-solid fa-user-slash"></i> Deactivate</button>
									</form>
								<?php else: ?>
									<form method="post">
										<input type="hidden" name="action" value="activate">
										<input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
										<button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-check"></i> Activate</button>
									</form>
								<?php endif; ?>
							<?php else: ?>
								<span class="sub">You</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php if (empty($users)): ?>
					<tr><td colspan="6">No users found.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


