<?php
require_once __DIR__ . '/php/db_connect.php';
require_once __DIR__ . '/php/helpers.php';

// Require login
if (!isset($_SESSION['user_id'])) {
	$_SESSION['login_errors'] = ['Please log in to view your inbox.'];
	header('Location: login.php');
	exit;
}

$userId = (int)$_SESSION['user_id'];

// Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
	$nid = (int)($_POST['nid'] ?? 0);
	if ($nid > 0) {
		$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
		$stmt->bind_param('ii', $nid, $userId);
		$stmt->execute();
		$stmt->close();
	}
	header('Location: inbox.php');
	exit;
}

// Mark all read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all'])) {
	$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
	$stmt->bind_param('i', $userId);
	$stmt->execute();
	$stmt->close();
	header('Location: inbox.php');
	exit;
}

// Fetch notifications
$stmt = $conn->prepare("SELECT id, type, reference_id, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
$notifications = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

renderHead('Inbox | PhoneFix+');
renderNav();
?>
<style>
.inbox-page{padding:2rem 0}
.inbox-list{display:flex;flex-direction:column;gap:1rem}
.note{border:1px solid #e5e7eb;border-radius:12px;padding:1rem;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.03)}
.note.unread{border-color:#2563eb33;background:#f8fbff}
.note-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem}
.note-title{margin:0;font-weight:600}
.note-meta{color:#64748b;font-size:.9rem}
.note-actions{display:flex;gap:.5rem}
.btn-sm{padding:.35rem .6rem;border:1px solid #e5e7eb;border-radius:8px;background:#fff;cursor:pointer}
.mark-all{margin-bottom:1rem;display:flex;justify-content:flex-end}
</style>
<main class="page inbox-page">
	<section class="container">
		<div class="mark-all">
			<form method="post">
				<button class="btn-sm" name="mark_all" type="submit">Mark all as read</button>
			</form>
		</div>
		<div class="inbox-list">
			<?php if (empty($notifications)): ?>
				<div class="note">
					<div class="note-header">
						<h3 class="note-title">No messages yet</h3>
					</div>
					<p>We’ll notify you here when your bookings or orders are updated.</p>
				</div>
			<?php else: ?>
				<?php foreach ($notifications as $n): ?>
					<div class="note <?php echo (int)$n['is_read'] === 0 ? 'unread':''; ?>">
						<div class="note-header">
							<h3 class="note-title"><?php echo htmlspecialchars($n['title']); ?></h3>
							<div class="note-meta"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($n['created_at']))); ?></div>
						</div>
						<div class="note-body">
							<p><?php echo nl2br(htmlspecialchars($n['message'])); ?></p>
						</div>
						<div class="note-actions">
							<?php if ((int)$n['is_read'] === 0): ?>
								<form method="post">
									<input type="hidden" name="nid" value="<?php echo (int)$n['id']; ?>">
									<button class="btn-sm" name="mark_read" type="submit">Mark as read</button>
								</form>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
renderFooter();
?>


