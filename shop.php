<?php
require_once __DIR__ . '/php/helpers.php';
require_once __DIR__ . '/php/admin_functions.php';
require_once __DIR__ . '/php/db_connect.php';

$products = getProducts($conn);

// Get reviews for each product
foreach ($products as &$product) {
    $stmt = $conn->prepare('
        SELECT r.*, u.name as user_name 
        FROM reviews r 
        JOIN users u ON u.id = r.user_id 
        WHERE r.product_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 5
    ');
    $stmt->bind_param('i', $product['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $product['reviews'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    
    // Calculate average rating
    $avgStmt = $conn->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?');
    $avgStmt->bind_param('i', $product['id']);
    $avgStmt->execute();
    $avgResult = $avgStmt->get_result();
    $avgData = $avgResult->fetch_assoc();
    $product['avg_rating'] = $avgData ? round((float)$avgData['avg_rating'], 1) : 0;
    $product['review_count'] = $avgData ? (int)$avgData['review_count'] : 0;
    $avgStmt->close();
}
unset($product);

renderHead('Shop Accessories | PhoneFix+');
renderNav();
renderFlashMessages([
    'cart_success' => 'success',
    'cart_errors' => 'error'
]);
?>

<main class="page">
    <section class="page-header">
        <div class="container">
            <h1>Accessory Shop</h1>
            <p>Curated essentials to protect and power your devices.</p>
        </div>
    </section>

    <section class="container products-grid">
        <?php if ($products): ?>
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        
                        <!-- Rating Display -->
                        <?php if ($product['review_count'] > 0): ?>
                            <div style="margin: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <div style="display: flex; gap: 0.125rem;">
                                    <?php 
                                    $avgRating = $product['avg_rating'];
                                    for ($i = 1; $i <= 5; $i++): 
                                        $starColor = $i <= $avgRating ? '#fbbf24' : '#d1d5db';
                                    ?>
                                        <span style="color: <?php echo $starColor; ?>; font-size: 0.875rem;">⭐</span>
                                    <?php endfor; ?>
                                </div>
                                <a href="reviews.php?product_id=<?php echo (int)$product['id']; ?>" style="font-size: 0.875rem; color: #667eea; text-decoration: none;">
                                    <?php echo number_format($avgRating, 1); ?> (<?php echo $product['review_count']; ?> review<?php echo $product['review_count'] !== 1 ? 's' : ''; ?>)
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <span class="price">$<?php echo number_format((float) $product['price'], 2); ?></span>
                        <form action="php/handle_cart.php" method="POST" class="cart-form">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                            <label for="qty-<?php echo (int) $product['id']; ?>">Qty</label>
                            <input id="qty-<?php echo (int) $product['id']; ?>" type="number" name="quantity" value="1" min="1">
                            <button type="submit" class="btn-primary">Add to Cart</button>
                        </form>
                        
                        <!-- Reviews Preview -->
                        
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No products available yet.</p>
        <?php endif; ?>
    </section>
</main>

<?php
renderFooter();
?>

