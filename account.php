<?php
require 'config/db.php';
require 'includes/functions.php';

// Must be logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php?next=account.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get user profile info
$stmt = $conn->prepare("SELECT first_name, last_name, email, role, contact_number, address, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user's adoption/foster applications safely
$apps = [];
$stmt = $conn->prepare("
    SELECT a.app_id, a.type, a.status, a.created_at, an.name AS animal_name 
    FROM adoption_foster_apps a
    LEFT JOIN animals an ON a.animal_id = an.animal_id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $apps[] = $row;
    }
}

// Get user's volunteer applications safely
$volApps = [];
$stmt = $conn->prepare("SELECT vol_app_id, status, created_at FROM volunteer_applications WHERE user_id = ? ORDER BY created_at DESC");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $volApps[] = $row;
    }
}

$pageTitle = 'My Account';
$active    = 'account';
require 'includes/header.php';
?>
<div class="account">
    <aside class="side-menu" aria-label="Account Menu">
        <a href="account.php" class="is-active">My Profile & Applications</a>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
            <a href="admin/dashboard.php">Admin Dashboard</a>
        <?php endif; ?>
        <a href="logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <div class="panel">
            <h2>Welcome back, <?= e($user['first_name'] ?? 'User') ?>!</h2>
            <p class="sub"><?= e($user['email'] ?? '') ?> &middot; Member since <?= date('M Y', strtotime($user['created_at'] ?? 'now')) ?></p>
            <hr style="border:0; border-top:1px solid var(--line); margin: 16px 0;">
            <p><strong>Contact Number:</strong> <?= e(!empty($user['contact_number']) ? $user['contact_number'] : 'Not provided') ?></p>
            <p><strong>Address:</strong> <?= e(!empty($user['address']) ? $user['address'] : 'Not provided') ?></p>
        </div>

        <div class="panel">
            <h2>Adoption & Foster Applications</h2>
            <?php if (empty($apps)): ?>
                <p class="empty">You have not submitted any adoption or foster applications yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="list">
                        <thead>
                            <tr>
                                <th>App ID</th>
                                <th>Type</th>
                                <th>Animal</th>
                                <th>Submitted</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apps as $a): ?>
                                <tr>
                                    <td>#<?= e($a['app_id']) ?></td>
                                    <td><?= e(ucfirst($a['type'])) ?></td>
                                    <td><?= e($a['animal_name'] ?: 'General') ?></td>
                                    <td><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                                    <td><span class="badge <?= statusClass($a['status']) ?>"><?= e(ucfirst($a['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2>Volunteer Applications</h2>
            <?php if (empty($volApps)): ?>
                <p class="empty">You have not submitted any volunteer applications yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="list">
                        <thead>
                            <tr>
                                <th>App ID</th>
                                <th>Submitted</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($volApps as $v): ?>
                                <tr>
                                    <td>#<?= e($v['vol_app_id']) ?></td>
                                    <td><?= date('M d, Y', strtotime($v['created_at'])) ?></td>
                                    <td><span class="badge <?= statusClass($v['status']) ?>"><?= e(ucfirst($v['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require 'includes/footer.php'; ?>