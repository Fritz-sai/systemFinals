<?php
require_once __DIR__ . '/php/helpers.php';

$featuredProducts = [];
$result = $conn->query('SELECT * FROM products ORDER BY created_at DESC LIMIT 3');
if ($result) {
    $featuredProducts = $result->fetch_all(MYSQLI_ASSOC);
    $result->close();
}

renderHead('PhoneFix+ | Premium Repairs & Accessories');
renderNav();
renderFlashMessages([
    'auth_success' => 'success',
    'cart_success' => 'success',
    'cart_errors' => 'error'
]);
?>

<main>
    <section class="hero">
        <div class="container hero-content">
            <div>
                <h1>Fast, Reliable Phone Repairs &amp; Stylish Accessories</h1>
                <p>PhoneFix+ brings expert technicians and curated accessories together. Book your repair in minutes and shop essentials that keep your device protected.</p>
                <div class="hero-actions">
                    <a class="btn-primary" href="booking.php">Book a Repair</a>
                    <a class="btn-outline" href="shop.php">Browse Accessories</a>
                </div>
            </div>
            <div class="hero-visual">
                <img src="images/placeholder.png" alt="Phone repair" />
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container features-grid">
            <article>
                <h3>Certified Technicians</h3>
                <p>Our experts diagnose and repair with precision using high-quality parts and modern tools.</p>
            </article>
            <article>
                <h3>Same-Day Repairs</h3>
                <p>Book a time that works for you. Most jobs completed in under two hours.</p>
            </article>
            <article>
                <h3>Premium Accessories</h3>
                <p>Protective cases, chargers, audio gear, and more—all vetted for durability and style.</p>
            </article>
        </div>
    </section>

    <section class="section-light">
        <div class="container">
            <div class="section-header">
                <h2>Featured Accessories</h2>
                <p>Hand-picked gear to keep your phone looking and working like new.</p>
            </div>
            <div class="products-grid">
                <?php if (count($featuredProducts) > 0): ?>
                    <?php foreach ($featuredProducts as $product): ?>
                        <article class="product-card">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p><?php echo htmlspecialchars($product['description']); ?></p>
                                <span class="price">$<?php echo number_format((float) $product['price'], 2); ?></span>
                                <form action="php/handle_cart.php" method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                    <button type="submit" class="btn-primary">Add to Cart</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No featured products available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container cta-content">
            <h2>Need help now?</h2>
            <p>Our technicians are ready. Book a repair and get back to what matters.</p>
            <a class="btn-secondary" href="booking.php">Schedule Service</a>
        </div>
    </section>
</main>

<?php
renderFooter();
?>

