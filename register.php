<?php
require_once __DIR__ . '/php/helpers.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = $_SESSION['register_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];

renderHead('Register | PhoneFix+');
renderNav();
renderFlashMessages([
    'register_errors' => 'error'
]);
?>

<main class="page auth-page">
    <section class="container auth-grid">
        <div class="card auth-card">
            <h1>Create an Account</h1>
            <p>Book repairs effortlessly and keep track of your orders.</p>
            <form action="php/handle_register.php" method="POST" class="auth-form">
                <label>
                    <span>Full Name</span>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>" required>
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" required>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required>
                </label>
                <label>
                    <span>Confirm Password</span>
                    <input type="password" name="confirm_password" required>
                </label>
                <button type="submit" class="btn-primary">Sign Up</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="login.php">Log in</a>.</p>
        </div>
        <div class="auth-side card">
            <h2>Member Benefits</h2>
            <ul>
                <li>Track bookings and repair updates</li>
                <li>Exclusive accessory deals</li>
                <li>Faster checkout experience</li>
            </ul>
        </div>
    </section>
</main>

<?php
unset($_SESSION['register_errors'], $_SESSION['old_input']);
renderFooter();
?>

