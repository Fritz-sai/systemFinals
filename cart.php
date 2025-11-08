<?php
require_once __DIR__ . '/php/helpers.php';

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += (float) $item['price'] * (int) $item['quantity'];
}

// Get delivery option from session
$deliveryOption = $_SESSION['delivery_option'] ?? 'pickup';
$shippingFee = 0;
if ($deliveryOption === 'delivery') {
    $shippingFee = $subtotal < 1000 ? 100 : 0;
}
$total = $subtotal + $shippingFee;

renderHead('Your Cart | PhoneFix+');
renderNav();
renderFlashMessages([
    'cart_success' => 'success',
    'cart_errors' => 'error'
]);
?>

<main class="page cart-page">
    <section class="container">
        <div class="cart-header">
            <h1 class="cart-title">My Cart</h1>
            <a href="shop.php" class="continue-shopping">← Continue shopping</a>
        </div>

        <?php if ($cart): ?>
        <div class="cart-layout">
            <div class="cart-main">
                <div class="cart-table-wrapper">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>PRODUCT</th>
                                <th>PRICE</th>
                                <th>QTY</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart as $item): 
                                $itemTotal = (float) $item['price'] * (int) $item['quantity'];
                            ?>
                            <tr class="cart-table-row">
                                <td class="cart-product">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-product-image">
                                    <div class="cart-product-details">
                                        <div class="cart-product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="cart-product-id">ID: <?php echo (int) $item['product_id']; ?></div>
                                    </div>
                                </td>
                                <td class="cart-price">
                                    <div class="price-amount">₱<?php echo number_format((float) $item['price'], 2); ?></div>
                                </td>
                                <td class="cart-quantity">
                                    <form action="php/handle_cart.php" method="POST" class="quantity-form">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo (int) $item['quantity']; ?>" min="1" class="quantity-input" onchange="this.form.submit()">
                                    </form>
                                </td>
                                <td class="cart-total-cell">
                                    <span class="item-total">₱<?php echo number_format($itemTotal, 2); ?></span>
                                    <form action="php/handle_cart.php" method="POST" class="remove-form">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                                        <button type="submit" class="remove-btn" title="Remove item">×</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="delivery-options-section">
                    <h3 class="delivery-title">Choose shipping mode:</h3>
                    <form id="delivery-form" action="php/handle_cart.php" method="POST" class="delivery-form">
                        <input type="hidden" name="action" value="update_delivery">
                        <div class="delivery-option">
                            <label class="delivery-label">
                                <input type="radio" name="delivery_option" value="pickup" <?php echo $deliveryOption === 'pickup' ? 'checked' : ''; ?>>
                                <span class="delivery-text">
                                    <strong>Store pickup (In 20 min)</strong> • <span class="delivery-cost">FREE</span>
                                </span>
                            </label>
                        </div>
                        <div class="delivery-option">
                            <label class="delivery-label">
                                <input type="radio" name="delivery_option" value="delivery" <?php echo $deliveryOption === 'delivery' ? 'checked' : ''; ?>>
                                <span class="delivery-text">
                                    <strong>Delivery at home (Under 2 - 4 day)</strong> • <span class="delivery-cost" id="shipping-fee-text"><?php echo $shippingFee > 0 ? '₱' . number_format($shippingFee, 2) : 'FREE'; ?></span>
                                </span>
                            </label>
                            <?php if ($deliveryOption === 'delivery'): ?>
                            <div class="delivery-address">
                                Delivery address will be collected during checkout
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="cart-summary-sidebar">
                <div class="summary-card">
                    <div class="summary-row">
                        <span>SUBTOTAL TTC</span>
                        <strong id="cart-subtotal">₱<?php echo number_format($subtotal, 2); ?></strong>
                    </div>
                    <div class="summary-row" id="shipping-row" style="<?php echo $deliveryOption === 'pickup' ? 'display: none;' : ''; ?>">
                        <span>SHIPPING</span>
                        <strong id="shipping-fee"><?php echo $shippingFee > 0 ? '₱' . number_format($shippingFee, 2) : 'Free'; ?></strong>
                    </div>
                    <div class="summary-row summary-total">
                        <span>TOTAL</span>
                        <strong id="cart-total">₱<?php echo number_format($total, 2); ?></strong>
                    </div>
                    <form action="php/handle_cart.php" method="POST" id="checkout-form" class="checkout-form">
                        <input type="hidden" name="action" value="checkout">
                        <input type="hidden" name="delivery_option" id="checkout-delivery-option" value="<?php echo htmlspecialchars($deliveryOption); ?>">
                        <input type="hidden" name="shipping_fee" id="checkout-shipping-fee" value="<?php echo $shippingFee; ?>">
                        <input type="hidden" name="total" id="checkout-total" value="<?php echo $total; ?>">
                        <button type="submit" class="checkout-btn">
                            Checkout
                            <span class="checkout-total">₱<?php echo number_format($total, 2); ?></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div class="card empty-cart">
                <p>Your cart is empty. Explore our <a href="shop.php">accessories</a> or <a href="booking.php">book a repair</a>.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const deliveryForm = document.getElementById('delivery-form');
    const deliveryRadios = document.querySelectorAll('input[name="delivery_option"]');
    const shippingRow = document.getElementById('shipping-row');
    const shippingFeeEl = document.getElementById('shipping-fee');
    const shippingFeeText = document.getElementById('shipping-fee-text');
    const subtotalEl = document.getElementById('cart-subtotal');
    const totalEl = document.getElementById('cart-total');
    const checkoutForm = document.getElementById('checkout-form');
    const checkoutDeliveryOption = document.getElementById('checkout-delivery-option');
    const checkoutShippingFee = document.getElementById('checkout-shipping-fee');
    const checkoutTotal = document.getElementById('checkout-total');

    // Get subtotal from the displayed value
    function getSubtotal() {
        const subtotalText = subtotalEl.textContent.replace('₱', '').replace(/,/g, '');
        return parseFloat(subtotalText) || 0;
    }

    function updateShippingAndTotal() {
        const subtotal = getSubtotal();
        const selectedOption = document.querySelector('input[name="delivery_option"]:checked');
        if (!selectedOption) return;
        
        const optionValue = selectedOption.value;
        let shippingFee = 0;
        const deliveryOption = selectedOption.closest('.delivery-option');
        let deliveryAddress = deliveryOption ? deliveryOption.querySelector('.delivery-address') : null;

        if (optionValue === 'delivery') {
            shippingFee = subtotal < 1000 ? 100 : 0;
            shippingRow.style.display = 'flex';
            shippingFeeEl.textContent = shippingFee > 0 ? '₱' + shippingFee.toFixed(2) : 'Free';
            shippingFeeText.textContent = shippingFee > 0 ? '₱' + shippingFee.toFixed(2) : 'FREE';
            
            // Show delivery address if it exists
            if (deliveryAddress) {
                deliveryAddress.style.display = 'block';
            } else {
                // Create delivery address if it doesn't exist
                const deliveryOptionDiv = selectedOption.closest('.delivery-option');
                if (deliveryOptionDiv && !deliveryOptionDiv.querySelector('.delivery-address')) {
                    const addressDiv = document.createElement('div');
                    addressDiv.className = 'delivery-address';
                    addressDiv.textContent = 'Delivery address will be collected during checkout';
                    deliveryOptionDiv.appendChild(addressDiv);
                }
            }
        } else {
            shippingRow.style.display = 'none';
            shippingFeeText.textContent = 'FREE';
            
            // Hide delivery address
            if (deliveryAddress) {
                deliveryAddress.style.display = 'none';
            }
        }

        const total = subtotal + shippingFee;
        totalEl.textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        // Update checkout button total
        const checkoutTotalSpan = document.querySelector('.checkout-total');
        if (checkoutTotalSpan) {
            checkoutTotalSpan.textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Update hidden fields for checkout
        checkoutDeliveryOption.value = optionValue;
        checkoutShippingFee.value = shippingFee;
        checkoutTotal.value = total;
    }

    // Handle delivery option change
    let isUpdating = false;
    deliveryRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (isUpdating) return;
            updateShippingAndTotal();
            // Auto-submit to save selection in session (with small delay to show update)
            isUpdating = true;
            setTimeout(() => {
                deliveryForm.submit();
            }, 100);
        });
    });

    // Initial calculation on page load
    updateShippingAndTotal();
});
</script>

<?php
renderFooter();
?>

