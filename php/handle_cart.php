<?php
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../cart.php');
    exit;
}

$action = $_POST['action'] ?? '';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {
    case 'update_delivery':
        $deliveryOption = $_POST['delivery_option'] ?? 'pickup';
        if (in_array($deliveryOption, ['pickup', 'delivery'])) {
            $_SESSION['delivery_option'] = $deliveryOption;
        }
        break;

    case 'add':
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['login_errors'] = ['Please log in to place an order or book'];
            header('Location: ../login.php');
            exit;
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        if ($productId <= 0) {
            $_SESSION['cart_errors'] = ['Invalid product selection.'];
            break;
        }

        $stmt = $conn->prepare('SELECT id, name, price, image FROM products WHERE id = ?');
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $product = $result->fetch_assoc()) {
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$productId] = [
                    'product_id' => $product['id'],
                    'name' => $product['name'],
                    'price' => (float) $product['price'],
                    'image' => $product['image'],
                    'quantity' => $quantity
                ];
            }
            $_SESSION['cart_success'] = 'Product added to cart!';
        } else {
            $_SESSION['cart_errors'] = ['Product not found.'];
        }
        $stmt->close();
        break;

    case 'update':
        if (isset($_POST['product_id'], $_POST['quantity'])) {
            $id = (int) $_POST['product_id'];
            $qty = max(1, (int) $_POST['quantity']);
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['quantity'] = $qty;
                $_SESSION['cart_success'] = 'Cart updated successfully.';
            }
        } else {
            $updates = $_POST['quantities'] ?? [];
            foreach ($updates as $id => $qty) {
                $id = (int) $id;
                $qty = max(1, (int) $qty);
                if (isset($_SESSION['cart'][$id])) {
                    $_SESSION['cart'][$id]['quantity'] = $qty;
                }
            }
            $_SESSION['cart_success'] = 'Cart updated successfully.';
        }
        break;

    case 'remove':
        $productId = (int) ($_POST['product_id'] ?? 0);
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['cart_success'] = 'Product removed from cart.';
        }
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        $_SESSION['cart_success'] = 'Cart cleared.';
        break;

    case 'checkout':
        if (empty($_SESSION['cart'])) {
            $_SESSION['cart_errors'] = ['Your cart is empty.'];
            break;
        }

        if (!isset($_SESSION['user_id'])) {
            $_SESSION['cart_errors'] = ['Please log in to complete your purchase.'];
            header('Location: ../login.php');
            exit;
        }

        // Get delivery option and shipping fee from POST (or session as fallback)
        $deliveryOption = $_POST['delivery_option'] ?? $_SESSION['delivery_option'] ?? 'pickup';
        $shippingFee = isset($_POST['shipping_fee']) ? (float) $_POST['shipping_fee'] : 0;
        $orderTotal = isset($_POST['total']) ? (float) $_POST['total'] : 0;

        // Store delivery info in session for reference
        $_SESSION['checkout_delivery_option'] = $deliveryOption;
        $_SESSION['checkout_shipping_fee'] = $shippingFee;
        $_SESSION['checkout_total'] = $orderTotal;

        $userId = (int) $_SESSION['user_id'];
        $conn->begin_transaction();
        try {
            $orderStmt = $conn->prepare('INSERT INTO orders (user_id, product_id, quantity, total, status) VALUES (?, ?, ?, ?, ?)');
            $status = 'pending';
            foreach ($_SESSION['cart'] as $item) {
                $quantity = (int) $item['quantity'];
                $itemTotal = (float) $item['price'] * $quantity;
                $orderStmt->bind_param('iiids', $userId, $item['product_id'], $quantity, $itemTotal, $status);
                $orderStmt->execute();
            }
            $orderStmt->close();
            $conn->commit();
            
            // Clear cart and delivery option after successful checkout
            $_SESSION['cart'] = [];
            unset($_SESSION['delivery_option']);
            $_SESSION['cart_success'] = 'Thank you! Your order has been placed.';
        } catch (Throwable $e) {
            $conn->rollback();
            $_SESSION['cart_errors'] = ['Unable to process checkout. Please try again.'];
        }
        break;

    default:
        $_SESSION['cart_errors'] = ['Unsupported cart action.'];
}

// Redirect based on action: add goes back to shop, others go to cart
if ($action === 'add') {
    header('Location: ../shop.php');
} else {
    header('Location: ../cart.php');
}
exit;

