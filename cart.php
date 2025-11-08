<?php
require_once __DIR__ . '/php/helpers.php';

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += (float) $item['price'] * (int) $item['quantity'];
}

renderHead('Your Cart | PhoneFix+');
renderNav();
renderFlashMessages([
    'cart_success' => 'success',
    'cart_errors' => 'error'
]);
?>

<main class="page">
    <section class="page-header">
        <div class="container">
            <h1>Your Shopping Cart</h1>
            <p>Review items and complete your purchase.</p>
        </div>
    </section>

    <section class="container cart-section">
        <?php if ($cart): ?>
            <div class="card cart-list">
                <?php foreach ($cart as $item): ?>
                    <article class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="cart-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>$<?php echo number_format((float) $item['price'], 2); ?> each</p>
                        </div>
                        <div class="cart-actions">
                            <form action="php/handle_cart.php" method="POST" class="inline-form">
                                <input type="hidden" name="action" value="update">
                                <label for="qty-<?php echo (int) $item['product_id']; ?>">Qty</label>
                                <input id="qty-<?php echo (int) $item['product_id']; ?>" type="number" min="1" name="quantity" value="<?php echo (int) $item['quantity']; ?>">
                                <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                                <button type="submit" class="btn-outline update-item">Update</button>
                            </form>
                            <form action="php/handle_cart.php" method="POST" class="inline-form">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                                <button type="submit" class="btn-link">Remove</button>
                            </form>
                        </div>
                        <span class="line-total">$<?php echo number_format((float) $item['price'] * (int) $item['quantity'], 2); ?></span>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary card">
                <div class="cart-summary-row">
                    <span>Subtotal</span>
                    <strong>$<?php echo number_format($subtotal, 2); ?></strong>
                </div>
                <div class="cart-checkout">
                    <form action="php/handle_cart.php" method="POST">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn-primary">Checkout</button>
                    </form>
                    <form action="php/handle_cart.php" method="POST">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn-link">Clear Cart</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card empty-cart">
                <p>Your cart is empty. Explore our <a href="shop.php">accessories</a> or <a href="booking.php">book a repair</a>.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php
renderFooter();
?>

