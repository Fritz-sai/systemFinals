<?php
require_once __DIR__ . '/php/helpers.php';

$old = $_SESSION['booking_old'] ?? [];

renderHead('Book a Repair | PhoneFix+');
renderNav();
renderFlashMessages([
    'booking_success' => 'success',
    'booking_errors' => 'error'
]);
?>

<main class="page">
    <section class="page-header">
        <div class="container">
            <h1>Schedule Your Repair</h1>
            <p>Choose a time that works best for you. Our technicians will confirm within minutes.</p>
        </div>
    </section>

    <section class="container form-section">
        <form class="card" action="php/handle_booking.php" method="POST">
            <div class="form-grid">
                <label>
                    <span>Name</span>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($old['name'] ?? ($_SESSION['user_name'] ?? '')); ?>" required>
                </label>
                <label>
                    <span>Contact Number or Email</span>
                    <input type="text" name="contact" value="<?php echo htmlspecialchars($old['contact'] ?? ''); ?>" required>
                </label>
                <label>
                    <span>Phone Model</span>
                    <input type="text" name="phone_model" value="<?php echo htmlspecialchars($old['phone_model'] ?? ''); ?>" required>
                </label>
                <label>
                    <span>Issue Description</span>
                    <textarea name="issue" rows="4" required><?php echo htmlspecialchars($old['issue'] ?? ''); ?></textarea>
                </label>
                <label>
                    <span>Preferred Date</span>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($old['date'] ?? ''); ?>" required>
                </label>
                <label>
                    <span>Preferred Time</span>
                    <input type="time" name="time" value="<?php echo htmlspecialchars($old['time'] ?? ''); ?>" required>
                </label>
            </div>
            <button type="submit" class="btn-primary">Submit Booking</button>
        </form>
    </section>
</main>

<?php
unset($_SESSION['booking_old']);
renderFooter();
?>

