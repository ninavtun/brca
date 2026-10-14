<?php
require 'config/db.php';
require 'includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$data = ['first_name' => '', 'last_name' => '', 'email' => '', 'contact_number' => '', 'address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['first_name']     = trim($_POST['first_name'] ?? '');
    $data['last_name']      = trim($_POST['last_name'] ?? '');
    $data['email']          = trim($_POST['email'] ?? '');
    $data['contact_number'] = trim($_POST['contact_number'] ?? '');
    $data['address']        = trim($_POST['address'] ?? '');
    $pass                   = $_POST['password'] ?? '';
    $passConfirm            = $_POST['password_confirm'] ?? '';

    if (!csrfValid()) {
        $error = 'Your session expired. Please try again.';
    } elseif (
        $data['first_name'] === '' || $data['last_name'] === '' ||
        $data['email'] === '' || $pass === ''
    ) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($pass !== $passConfirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $data['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $role = 'user';

            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, contact_number, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $data['first_name'], $data['last_name'], $data['email'], $hash, $role, $data['contact_number'], $data['address']);

            if ($stmt->execute()) {
                header('Location: login.php?registered=1');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
require 'includes/header.php';
?>
<div class="auth-card">
    <div class="auth-side">
        <div class="paw" aria-hidden="true">&#128062;</div>
        <h2>Join BRCA</h2>
        <p>Create an account to submit adoption requests, apply to foster, or volunteer with us.</p>
    </div>
    <div class="auth-form">
        <div class="auth-tabs">
            <a href="login.php">Login</a>
            <a href="register.php" class="is-active">Register</a>
        </div>

        <?php if ($error): ?>
            <div class="msg error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrfField() ?>
            <div class="row">
                <div>
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="<?= e($data['first_name']) ?>" required autofocus>
                </div>
                <div>
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" value="<?= e($data['last_name']) ?>" required>
                </div>
            </div>

            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" value="<?= e($data['email']) ?>" placeholder="you@example.com" required>

            <div class="row">
                <div>
                    <label for="contact_number">Contact Number <span class="opt">(optional)</span></label>
                    <input type="tel" id="contact_number" name="contact_number" value="<?= e($data['contact_number']) ?>">
                </div>
                <div>
                    <label for="address">Address <span class="opt">(optional)</span></label>
                    <input type="text" id="address" name="address" value="<?= e($data['address']) ?>">
                </div>
            </div>

            <div class="row">
                <div>
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <div class="hint">At least 8 characters</div>
                </div>
                <div>
                    <label for="password_confirm">Confirm Password *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
            </div>

            <button class="btn" type="submit">Create Account</button>
        </form>

        <p class="auth-foot">Already have an account? <a href="login.php">Log in</a></p>
    </div>
</div>
<?php require 'includes/footer.php'; ?>