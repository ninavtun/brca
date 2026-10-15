<?php
require '../config/db.php';
require '../includes/functions.php';

// Ensure user is logged in and is an admin
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$type_tab = $_GET['tab'] ?? 'adoption';
$is_volunteer = ($type_tab === 'volunteer');

// Handle Status Updates (Approve / Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $app_id = intval($_GET['id']);
    $action = $_GET['action'];
    $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
    
    if ($is_volunteer) {
        // If approving, decrease available slots by 1 for this schedule
        if ($action === 'approve') {
            // First, get the schedule_id for this volunteer application
            $sched_stmt = $conn->prepare("SELECT schedule_id, status FROM volunteer_applications WHERE volunteer_application_id = ?");
            $sched_stmt->bind_param("i", $app_id);
            $sched_stmt->execute();
            $app_data = $sched_stmt->get_result()->fetch_assoc();
            
            // Only decrement if it wasn't already approved
            if ($app_data && $app_data['status'] !== 'Approved') {
                $schedule_id = $app_data['schedule_id'];
                $conn->query("UPDATE volunteer_schedules SET slots_available = GREATEST(0, slots_available - 1) WHERE schedule_id = $schedule_id");
            }
        }
        
        $conn->query("UPDATE volunteer_applications SET status = '$new_status' WHERE volunteer_application_id = $app_id");
    } else {
        $conn->query("UPDATE adoption_foster_apps SET status = '$new_status' WHERE application_id = $app_id");
    }
    header('Location: applications.php?tab=' . $type_tab);
    exit;
}

// Fetch applications based on active tab
if ($is_volunteer) {
    $apps_query = "
        SELECT va.volunteer_application_id AS application_id, va.status, va.application_date, 
               u.first_name, u.last_name, vs.volunteer_date, vs.start_time, vs.end_time 
        FROM volunteer_applications va
        JOIN users u ON va.user_id = u.user_id
        LEFT JOIN volunteer_schedules vs ON va.schedule_id = vs.schedule_id
        ORDER BY va.application_date DESC
    ";
} else {
    $apps_query = "
        SELECT a.application_id, a.application_type, a.status, a.application_date, 
               u.first_name, u.last_name, an.name AS animal_name, an.animal_id 
        FROM adoption_foster_apps a
        JOIN users u ON a.user_id = u.user_id
        LEFT JOIN animals an ON a.animal_id = an.animal_id
        ORDER BY a.application_date DESC
    ";
}
$apps_result = $conn->query($apps_query);

$pageTitle = 'Manage Applications';
$active    = 'admin';
require '../includes/header.php';
?>

<div class="account">
    <aside class="side-menu" aria-label="Admin Menu">
        <a href="dashboard.php">Dashboard Overview</a>
        <a href="animals.php">Manage Animals</a>
        <a href="animals_add.php">Add New Animal</a>
        <a href="kennels.php">Kennels</a>
        <a href="applications.php" class="is-active">Applications</a>
        <a href="schedules.php">Schedules</a>
	<a href="donation.php">Donation Settings</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <div class="panel">
            <h2>Applications</h2>
            <p class="sub">Review adoption, foster, and volunteer requests.</p>

            <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                <a href="applications.php?tab=adoption" style="background: <?= !$is_volunteer ? 'var(--teal, #2a9d8f)' : '#e2e8f0' ?>; color: <?= !$is_volunteer ? '#fff' : '#475569' ?>; padding: 8px 16px; border-radius: 20px; font-weight: 600; text-decoration: none; font-size: 0.9rem;">Adoption & Foster</a>
                <a href="applications.php?tab=volunteer" style="background: $is_volunteer ? 'var(--teal, #2a9d8f)' : '#e2e8f0' ?>; color: <?= $is_volunteer ? '#fff' : '#475569' ?>; padding: 8px 16px; border-radius: 20px; font-weight: 600; text-decoration: none; font-size: 0.9rem;">Volunteer</a>
            </div>

            <div class="table-wrap">
                <table class="list" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <?php if ($is_volunteer): ?>
                                <th>Schedule</th>
                            <?php else: ?>
                                <th>Animal</th>
                                <th>Type</th>
                            <?php endif; ?>
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
                                    <?php if ($is_volunteer): ?>
                                        <td><?= e(date('d M Y', strtotime($row['volunteer_date'])) . ' (' . $row['start_time'] . ')') ?></td>
                                    <?php else: ?>
                                        <td><?= e($row['animal_name'] ? $row['animal_name'] . ' • BRCA-0' . $row['animal_id'] : 'General') ?></td>
                                        <td><?= e($row['application_type']) ?></td>
                                    <?php endif; ?>
                                    <td><?= date('d M Y', strtotime($row['application_date'])) ?></td>
                                    <td><span class="badge <?= $status_class ?>"><?= e($status) ?></span></td>
                                    <td>
                                        <?php if (strcasecmp($status, 'Pending') === 0): ?>
                                            <div style="display: flex; gap: 8px;">
                                                <a href="applications.php?action=approve&id=<?= $row['application_id'] ?>&tab=<?= $type_tab ?>" style="width: 28px; height: 28px; background: #2a9d8f; color: #fff; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold;" title="Approve">&#10003;</a>
                                                <a href="applications.php?action=reject&id=<?= $row['application_id'] ?>&tab=<?= $type_tab ?>" style="width: 28px; height: 28px; background: #e53e3e; color: #fff; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold;" title="Reject">&#10005;</a>
                                            </div>
                                        <?php else: ?>
                                            <a href="#" style="color: var(--teal, #2a9d8f); font-weight: 600; text-decoration: none;">View</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $is_volunteer ? '5' : '6' ?>" style="text-align: center; color: var(--muted); padding: 40px;">No applications found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php require '../includes/footer.php'; ?>