<?php
require '../config/db.php';
require '../includes/functions.php';

// Ensure user is logged in and is an admin
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle Status Updates (Approve / Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $app_id = intval($_GET['id']);
    $new_status = ($_GET['action'] === 'approve') ? 'Approved' : 'Rejected';
    $conn->query("UPDATE adoption_foster_apps SET status = '$new_status' WHERE application_id = $app_id");
    header('Location: applications.php');
    exit;
}

// Filter tab
$type_tab = $_GET['tab'] ?? 'adoption';
$status_filter = $_GET['status'] ?? 'Pending';

// Fetch Adoption & Foster Applications
$apps_query = "
    SELECT a.application_id, a.application_type, a.status, a.application_date, 
           u.first_name, u.last_name, an.name AS animal_name, an.animal_id 
    FROM adoption_foster_apps a
    JOIN users u ON a.user_id = u.user_id
    LEFT JOIN animals an ON a.animal_id = an.animal_id
    ORDER BY a.application_date DESC
";
$apps_result = $conn->query($apps_query);

$pageTitle = 'Manage Applications';
$active    = 'admin';
require '../includes/header.php';
?>

<div class="account">
    <!-- Admin Sidebar Menu -->
    <aside class="side-menu" aria-label="Admin Menu">
        <a href="dashboard.php">Dashboard Overview</a>
        <a href="animals.php">Manage Animals</a>
        <a href="animals_add.php">Add New Animal</a>
        <a href="kennels.php">Kennels</a>
        <a href="applications.php" class="is-active">Applications</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <!-- Main Content Section -->
    <section>
        <div class="panel">
            <h2>Applications</h2>
            <p class="sub">Review adoption, foster, and volunteer requests.</p>

            <!-- Adoption vs Volunteer Tabs -->
            <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                <a href="applications.php?tab=adoption" class="tab-btn" style="background: var(--teal, #2a9d8f); color: #fff; padding: 8px 16px; border-radius: 20px; font-weight: 600; text-decoration: none; font-size: 0.9rem;">Adoption & Foster</a>
                <a href="applications.php?tab=volunteer" class="tab-btn" style="background: #e2e8f0; color: #475569; padding: 8px 16px; border-radius: 20px; font-weight: 600; text-decoration: none; font-size: 0.9rem;">Volunteer</a>
            </div>

            <!-- Table -->
            <div class="table-wrap">
                <table class="list" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Animal</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($apps_result && $apps_result->num_rows > 0): ?>
                            <?php while ($row = $apps_result->fetch_assoc()): ?>
                                <?php 
                                    $status = $row['status'] ?? 'Pending';
                                    $status_class = statusClass($status);
                                ?>
                                <tr>
                                    <td><strong><?= e($row['first_name'] . ' ' . $row['last_name']) ?></strong></td>
                                    <td><?= e($row['animal_name'] ? $row['animal_name'] . ' • BRCA-0' . $row['animal_id'] : 'General') ?></td>
                                    <td><?= e($row['application_type']) ?></td>
                                    <td><?= date('d M Y', strtotime($row['application_date'])) ?></td>
                                    <td><span class="badge <?= $status_class ?>"><?= e($status) ?></span></td>
                                    <td>
                                        <?php if (strcasecmp($status, 'Pending') === 0): ?>
                                            <div style="display: flex; gap: 8px;">
                                                <a href="applications.php?action=approve&id=<?= $row['application_id'] ?>" style="width: 28px; height: 28px; background: #2a9d8f; color: #fff; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold;" title="Approve">&#10003;</a>
                                                <a href="applications.php?action=reject&id=<?= $row['application_id'] ?>" style="width: 28px; height: 28px; background: #e53e3e; color: #fff; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold;" title="Reject">&#10005;</a>
                                            </div>
                                        <?php else: ?>
                                            <a href="#" style="color: var(--teal, #2a9d8f); font-weight: 600; text-decoration: none;">View</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--muted); padding: 40px;">No applications found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php require '../includes/footer.php'; ?>