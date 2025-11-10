<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete_product') {
	$id = (int)post('id');
	if ($id > 0) {
		$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->close();
	}
	header("Location: /systemFinals/admin/products.php");
	exit;
}

$result = $conn->query("SELECT id, name, description, price, image, category, stock FROM products ORDER BY created_at DESC");
$products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
if ($result) $result->close();
?>

<main class="admin-main">
	<div class="section-header">
		<h2>Products</h2>
		<div class="actions">
			<a class="btn btn-primary" href="/systemFinals/admin/add_product.php"><i class="fa-solid fa-plus"></i> Add Product</a>
		</div>
	</div>

	<div class="card">
		<div class="search-row">
			<input class="input" type="text" id="searchInput" placeholder="Search products...">
		</div>
		<div style="margin-top:12px; overflow-x:auto;">
			<table class="table" id="productsTable">
				<thead>
					<tr>
						<th>Image</th>
						<th>Name</th>
						<th>Price</th>
						<th>Category</th>
						<th>Stock</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($products as $p): ?>
						<tr>
							<td><img src="/systemFinals/<?php echo htmlspecialchars($p['image']); ?>" alt=""></td>
							<td><?php echo htmlspecialchars($p['name']); ?></td>
							<td><?php echo format_currency($p['price']); ?></td>
							<td><?php echo htmlspecialchars($p['category'] ?? '—'); ?></td>
							<td><?php echo (int)($p['stock'] ?? 0); ?></td>
							<td class="actions">
								<a class="btn btn-outline" href="/systemFinals/admin/add_product.php?id=<?php echo (int)$p['id']; ?>"><i class="fa-solid fa-pen"></i> Edit</a>
								<form method="post" onsubmit="return confirm('Delete this product?');">
									<input type="hidden" name="action" value="delete_product">
									<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
									<button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if (empty($products)): ?>
						<tr><td colspan="6">No products found.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</main>

<script>
const searchInput = document.getElementById('searchInput');
const table = document.getElementById('productsTable');
if (searchInput && table) {
	searchInput.addEventListener('input', () => {
		const term = searchInput.value.toLowerCase();
		table.querySelectorAll('tbody tr').forEach(tr => {
			tr.style.display = tr.innerText.toLowerCase().includes(term) ? '' : 'none';
		});
	});
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
