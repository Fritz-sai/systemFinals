<?php
require_once __DIR__ . '/php/helpers.php';
require_once __DIR__ . '/php/admin_functions.php';

$products = getProducts($conn);

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
                        <span class="price">$<?php echo number_format((float) $product['price'], 2); ?></span>
                        <form action="php/handle_cart.php" method="POST" class="cart-form">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                            <label for="qty-<?php echo (int) $product['id']; ?>">Qty</label>
                            <input id="qty-<?php echo (int) $product['id']; ?>" type="number" name="quantity" value="1" min="1">
                            <button type="submit" class="btn-primary">Add to Cart</button>
                        </form>
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

