<?php
require 'config/db.php';
require 'includes/functions.php';

$pageTitle = 'Home';
$active    = 'home';
require 'includes/header.php';

$loggedIn = !empty($_SESSION['user_id']);
?>

<div style="text-align: center; padding: 40px 20px;">
    <h1>Welcome to Bahrain Rescue Centre for Animals</h1>
    <p style="color: var(--muted); max-width: 600px; margin: 12px auto 24px;">
        Dedicated to rescuing, rehabilitating, and rehoming animals in need across Bahrain. Join our mission today by adopting, fostering, or volunteering.
    </p>
    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
        <a href="adopt.php" class="btn btn-auto">Adopt a Pet</a>
        <?php if ($loggedIn): ?>
            <a href="account.php" class="btn btn-auto btn-outline">Go to My Account</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-auto btn-outline">Log In</a>
            <a href="register.php" class="btn btn-auto btn-outline">Register Account</a>
        <?php endif; ?>
    </div>
</div>

<?php require 'includes/footer.php'; ?>