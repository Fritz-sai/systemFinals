<?php
require_once __DIR__ . '/php/helpers.php';

$services = [
    ['title' => 'Screen Replacement', 'description' => 'Cracked or shattered screen? We use premium glass replacements with original feel.', 'price' => 129.99],
    ['title' => 'Battery Replacement', 'description' => 'Restore battery life and performance with genuine replacements.', 'price' => 89.99],
    ['title' => 'Water Damage Treatment', 'description' => 'Complete device diagnostics and ultrasonic cleaning to revive water-damaged phones.', 'price' => 149.99],
    ['title' => 'Charging Port Repair', 'description' => 'Fix loose or unresponsive charging ports and restore fast charging.', 'price' => 79.99],
    ['title' => 'Speaker & Mic Repair', 'description' => 'Crystal clear audio for calls, music, and voice assistants.', 'price' => 69.99],
    ['title' => 'Software Optimization', 'description' => 'Speed tune-ups, data backup, and malware removal.', 'price' => 49.99]
];

renderHead('Services | PhoneFix+');
renderNav();
renderFlashMessages([
    'auth_success' => 'success'
]);
?>

<main class="page">
    <section class="page-header">
        <div class="container">
            <h1>Repair Services</h1>
            <p>Transparent pricing, premium parts, and fast turnaround for every device.</p>
        </div>
    </section>

    <section class="container services-grid">
        <?php foreach ($services as $service): ?>
            <article class="service-card">
                <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                <p><?php echo htmlspecialchars($service['description']); ?></p>
                <span class="price">Starting at $<?php echo number_format($service['price'], 2); ?></span>
                <a class="btn-outline" href="booking.php">Book Now</a>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<?php
renderFooter();
?>

