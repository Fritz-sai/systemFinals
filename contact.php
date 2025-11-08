<?php
require_once __DIR__ . '/php/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $message) {
        $_SESSION['contact_success'] = 'Thanks for reaching out! Our team will respond shortly.';
    } else {
        $_SESSION['contact_errors'] = ['Please fill in all required fields.'];
    }

    header('Location: contact.php');
    exit;
}

renderHead('Contact Us | PhoneFix+');
renderNav();
renderFlashMessages([
    'contact_success' => 'success',
    'contact_errors' => 'error'
]);
?>

<main class="page">
    <section class="page-header">
        <div class="container">
            <h1>Get in Touch</h1>
            <p>Questions about a device or accessory? We&apos;re here to help.</p>
        </div>
    </section>

    <section class="container contact-grid">
        <div class="card contact-info">
            <h3>Visit Us</h3>
            <p>123 Mobile Ave, Suite 200<br>San Francisco, CA 94107</p>
            <h3>Support</h3>
            <p>Email: support@phonefixplus.com<br>Phone: +1 (555) 987-6543</p>
            <h3>Hours</h3>
            <p>Mon - Sat: 9:00 AM - 7:00 PM<br>Sun: 10:00 AM - 5:00 PM</p>
        </div>
        <form class="card contact-form" method="POST" action="contact.php">
            <label>
                <span>Name</span>
                <input type="text" name="name" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" required>
            </label>
            <label>
                <span>Message</span>
                <textarea name="message" rows="5" required></textarea>
            </label>
            <button type="submit" class="btn-primary">Send Message</button>
        </form>
    </section>
</main>

<?php
renderFooter();
?>

