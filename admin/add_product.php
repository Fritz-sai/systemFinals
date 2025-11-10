<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$editingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = null;
if ($editingId > 0) {
	$stmt = $conn->prepare("SELECT id, name, description, price, image, category, stock FROM products WHERE id = ?");
	$stmt->bind_param('i', $editingId);
	$stmt->execute();
	$editing = $stmt->get_result()->fetch_assoc();
	$stmt->close();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = trim((string)post('name'));
	$price = (float)post('price');
	$description = trim((string)post('description'));
	$category = trim((string)post('category'));
	$stock = (int)post('stock', 0);
	$imagePath = $editing['image'] ?? 'images/placeholder.png';

	// Validate
	if ($name === '' || $price <= 0 || $description === '') {
		$error = 'Please fill in all required fields.';
	} else {
		// Handle image upload if provided
		if (!empty($_FILES['image']['name'])) {
			$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/jpg' => 'jpg'];
			if (!isset($allowed[$_FILES['image']['type']])) {
				$error = 'Invalid image type. Allowed: JPG, PNG.';
			} elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
				$error = 'Image too large. Max 2MB.';
			} else {
				$ext = $allowed[$_FILES['image']['type']];
				$fname = 'admin/assets/uploads/' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
				$abs = __DIR__ . '/../' . $fname; // admin/ + ../ => project root
				$dir = dirname($abs);
				if (!is_dir($dir)) {
					mkdir($dir, 0777, true);
				}
				if (move_uploaded_file($_FILES['image']['tmp_name'], $abs)) {
					$imagePath = $fname;
				} else {
					$error = 'Failed to upload image.';
				}
			}
		}
	}

	if ($error === '') {
		if ($editing) {
			$stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, image=?, category=?, stock=? WHERE id=?");
			$stmt->bind_param('ssdsiii', $name, $description, $price, $imagePath, $category, $stock, $editingId);
			$stmt->execute();
			$stmt->close();
		} else {
			$stmt = $conn->prepare("INSERT INTO products (name, description, price, image, category, stock) VALUES (?, ?, ?, ?, ?, ?)");
			$stmt->bind_param('ssdsii', $name, $description, $price, $imagePath, $category, $stock);
			$stmt->execute();
			$stmt->close();
		}
		header("Location: /systemFinals/admin/products.php");
		exit;
	}
}
?>

<main class="admin-main">
	<div class="section-header">
		<h2><?php echo $editing ? 'Edit Product' : 'Add Product'; ?></h2>
		<div class="actions">
			<a class="btn" href="/systemFinals/admin/products.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
		</div>
	</div>

	<?php if ($error): ?>
		<div class="card" style="border-color:#ef4444;color:#fecaca;">
			<?php echo htmlspecialchars($error); ?>
		</div>
	<?php endif; ?>

	<div class="card form-card">
		<form method="post" enctype="multipart/form-data">
			<div class="form-row">
				<div>
					<div class="label">Name</div>
					<input class="input" type="text" name="name" required value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>">
				</div>
				<div>
					<div class="label">Price</div>
					<input class="input" type="number" name="price" step="0.01" min="0" required value="<?php echo htmlspecialchars($editing['price'] ?? ''); ?>">
				</div>
			</div>
			<div class="form-row">
				<div>
					<div class="label">Category</div>
					<input class="input" type="text" name="category" value="<?php echo htmlspecialchars($editing['category'] ?? ''); ?>">
				</div>
				<div>
					<div class="label">Stock</div>
					<input class="input" type="number" name="stock" min="0" value="<?php echo htmlspecialchars((string)($editing['stock'] ?? 0)); ?>">
				</div>
			</div>
			<div class="form-row-1">
				<div>
					<div class="label">Description</div>
					<textarea class="input" name="description" required><?php echo htmlspecialchars($editing['description'] ?? ''); ?></textarea>
				</div>
			</div>
			<div class="form-row">
				<div>
					<div class="label">Image</div>
					<input class="input" type="file" name="image" accept="image/*">
				</div>
				<div>
					<div class="label">Preview</div>
					<?php if ($editing && !empty($editing['image'])): ?>
						<img src="/systemFinals/<?php echo htmlspecialchars($editing['image']); ?>" alt="" style="width:96px;height:96px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
					<?php else: ?>
						<div class="sub">No image selected</div>
					<?php endif; ?>
				</div>
			</div>
			<div class="section-header" style="margin:16px 0 0;">
				<button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
			</div>
		</form>
	</div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


