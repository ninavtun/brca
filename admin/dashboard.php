<?php
require '../config/db.php';
require '../includes/functions.php';

// Ensure user is logged in and is an admin
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$pageTitle = 'Admin Dashboard';
$active    = 'admin';
require '../includes/header.php';
?>

<div class="account">
    <aside class="side-menu" aria-label="Admin Menu">
        <a href="dashboard.php" class="is-active">Dashboard Overview</a>
        <a href="animals.php">Manage Animals</a>
        <a href="animals_add.php">Add New Animal</a>
        <a href="kennels.php">Kennels</a>
        <a href="applications.php">Applications</a>
        <a href="schedules.php">Schedules</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <div class="panel">
            <h2>Admin Dashboard</h2>
            <p class="sub">Welcome back, <?= e($user['first_name'] ?? 'Admin') ?>! Manage platform records and rescue operations below.</p>
        </div>

        <div class="panel">
            <h2>Quick Actions</h2>
            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                <a href="animals_add.php" class="btn btn-auto">Add Animal Record</a>
                <a href="animals.php" class="btn btn-auto btn-outline">View All Animals</a>
                <a href="applications.php" class="btn btn-auto" style="background: var(--teal, #2a9d8f); color: #fff; text-decoration: none;">Manage Applications</a>
            </div>
        </div>
    </section>
</div>

<?php require '../includes/footer.php'; ?>