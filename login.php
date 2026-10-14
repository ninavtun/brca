<?php
require 'config/db.php';
require 'includes/functions.php';

$next = safeNext($_GET['next'] ?? ($_POST['next'] ?? ''), '');

// Already logged in? Send them where they belong
if (!empty($_SESSION['user_id'])) {
    $home = ($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'index.php';
    header('Location: ' . ($next !== '' ? $next : $home));
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!csrfValid()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($email === '' || $pass === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, first_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($pass, $user['password'])) {
            session_regenerate_id(true);   // new session ID after login
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['first_name'];
            $_SESSION['role']    = $user['role'];

            if ($user['role'] === 'admin') {
                $go = 'admin/dashboard.php';
            } else {
                $go = $next !== '' ? $next : 'index.php';
            }
            header('Location: ' . $go);
            exit;
        }
        // Same message for wrong email or wrong password, on purpose
        $error = 'Incorrect email or password.';
    }
}

$pageTitle = 'Login';
require 'includes/header.php';
?>
<div class="auth-card">
    <div class="auth-side">
        <div class="paw" aria-hidden="true">&#128062;</div>
        <h2>Welcome to BRCA</h2>
        <p>Log in to track your adoption, foster, and volunteer applications.</p>
    </div>
    <div class="auth-form">
        <div class="auth-tabs">
            <a href="login.php<?= $next !== '' ? '?next=' . e(urlencode($next)) : '' ?>" class="is-active">Login</a>
            <a href="register.php">Register</a>
        </div>

        <?php if (isset($_GET['registered'])): ?>
            <div class="msg ok">Account created. You can log in now.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="you@example.com" autocomplete="email" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>

            <button class="btn" type="submit">Login</button>
        </form>

        <p class="auth-foot">New here? <a href="register.php">Register an account</a></p>
    </div>
</div>
<?php require 'includes/footer.php'; ?>