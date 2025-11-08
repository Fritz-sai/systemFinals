<?php
require_once __DIR__ . '/db_connect.php';

function renderHead(string $pageTitle = 'Phone Repair & Accessories'): void
{
    $title = htmlspecialchars($pageTitle);
    echo "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>{$title}</title>\n    <link rel=\"stylesheet\" href=\"css/style.css\">\n    <script defer src=\"js/script.js\"></script>\n</head>\n<body>";
}

function renderNav(): void
{
    $isLoggedIn = isset($_SESSION['user_id']);
    $role = $_SESSION['user_role'] ?? 'customer';
    echo '<header class="navbar">
        <div class="container nav-container">
            <div class="logo"><a href="index.php">PhoneFix+</a></div>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="services.php">Services</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="booking.php">Book Repair</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="cart.php">Cart</a></li>';

    if ($isLoggedIn) {
        echo '<li class="nav-user">Hi, ' . htmlspecialchars($_SESSION['user_name'] ?? 'Customer') . '</li>';
        echo '<li><a href="orders.php">My Orders</a></li>';
        if ($role === 'admin') {
            echo '<li><a href="admin.php">Admin</a></li>';
        }
        echo '<li><a href="login.php?logout=1" class="btn-outline">Logout</a></li>';
    } else {
        echo '<li><a href="login.php">Login</a></li>';
        echo '<li><a href="register.php" class="btn-primary">Sign Up</a></li>';
    }

    echo '        </ul>
            </nav>
            <button class="nav-toggle" aria-label="Open navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>';
}

function renderFooter(): void
{
    echo '<footer class="footer">
        <div class="container footer-content">
            <div>
                <h3>PhoneFix+</h3>
                <p>Reliable phone repairs and the latest accessories to keep you connected.</p>
            </div>
            <div>
                <h4>Contact</h4>
                <p>Email: support@phonefixplus.com</p>
                <p>Phone: +1 (555) 987-6543</p>
            </div>
            <div>
                <h4>Follow Us</h4>
                <div class="social-links">
                    <a href="#">Facebook</a>
                    <a href="#">Instagram</a>
                    <a href="#">Twitter</a>
                </div>
            </div>
        </div>
        <p class="footer-copy">&copy; ' . date('Y') . ' PhoneFix+. All rights reserved.</p>
    </footer>
    <div id="chatbot" class="chatbot">
        <div class="chatbot-header">
            <h4>PhoneFix+ Assistant</h4>
            <button id="chatbot-close" aria-label="Close chatbot">&times;</button>
        </div>
        <div class="chatbot-body">
            <p>Hello! Need help booking a repair or finding accessories? Ask me anything.</p>
            <ul>
                <li>"How do I book a repair?"</li>
                <li>"Show me screen protectors."</li>
            </ul>
        </div>
        <div class="chatbot-input">
            <input type="text" placeholder="Type your question..." aria-label="Chatbot input" />
            <button>Send</button>
        </div>
    </div>
    <button id="chatbot-toggle" class="chatbot-toggle" aria-label="Open chatbot">Chat with us</button>
</body>
</html>';
}

function renderFlashMessages(array $messageKeys): void
{
    echo '<div class="container flash-container">';
    foreach ($messageKeys as $key => $classes) {
        if (!empty($_SESSION[$key])) {
            $messages = (array) $_SESSION[$key];
            foreach ($messages as $message) {
                echo '<div class="flash-message ' . htmlspecialchars($classes) . '">' . htmlspecialchars($message) . '</div>';
            }
            unset($_SESSION[$key]);
        }
    }
    echo '</div>';
}

?>

