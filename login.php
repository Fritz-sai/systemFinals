<?php
require_once __DIR__ . '/php/helpers.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
    $_SESSION['auth_success'] = 'You have been logged out successfully.';
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$loginErrors = $_SESSION['login_errors'] ?? [];
$registerSuccess = $_SESSION['auth_success'] ?? null;

renderHead('Login | PhoneFix+');
renderNav();
renderFlashMessages([
    'auth_success' => 'success',
    'login_errors' => 'error'
]);
?>

<main class="page auth-page">
    <section class="container auth-grid">
        <div class="card auth-card">
            <h1>Welcome Back</h1>
            <p>Log in to track your repairs, orders, and manage bookings.</p>
            <form action="php/handle_login.php" method="POST" class="auth-form">
                <label>
                    <span>Email</span>
                    <input type="email" name="email" required>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required>
                </label>
                <button type="submit" class="btn-primary">Log In</button>
            </form>
            <p class="auth-switch">New here? <a href="register.php">Create an account</a>.</p>
        </div>
        <div class="auth-side card">
            <h2>PhoneFix+</h2>
            <p>Manage your repair bookings, checkout faster, and access exclusive accessories curated for your device.</p>
        </div>
    </section>
</main>

<?php
unset($_SESSION['login_errors'], $_SESSION['auth_success']);
renderFooter();
?>

