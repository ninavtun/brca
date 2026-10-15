<?php
require '../config/db.php';
require '../includes/functions.php';

// Ensure user is logged in and is an admin
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Handle Add Schedule Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $date = trim($_POST['volunteer_date']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $slots = intval($_POST['slots_available']);

    if (!empty($date) && !empty($start_time) && !empty($end_time) && $slots > 0) {
        $stmt = $conn->prepare("INSERT INTO volunteer_schedules (volunteer_date, start_time, end_time, slots_available, status) VALUES (?, ?, ?, ?, 'Active')");
        $stmt->bind_param("sssi", $date, $start_time, $end_time, $slots);
        if ($stmt->execute()) {
            $message = "Volunteer schedule added successfully!";
        } else {
            $error = "Error adding schedule.";
        }
    } else {
        $error = "Please fill in all fields correctly.";
    }
}

// Handle Delete Schedule
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM volunteer_schedules WHERE schedule_id = $id");
    header('Location: schedules.php');
    exit;
}

// Fetch all schedules
$schedules_result = $conn->query("SELECT * FROM volunteer_schedules ORDER BY volunteer_date ASC");

$pageTitle = 'Manage Schedules';
$active    = 'admin';
require '../includes/header.php';
?>

<div class="account">
    <aside class="side-menu" aria-label="Admin Menu">
        <a href="dashboard.php">Dashboard Overview</a>
        <a href="animals.php">Manage Animals</a>
        <a href="animals_add.php">Add New Animal</a>
        <a href="kennels.php">Kennels</a>
        <a href="applications.php">Applications</a>
        <a href="schedules.php" class="is-active">Schedules</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <div class="panel">
            <h2>Manage Volunteer Schedules</h2>
            <p class="sub">Add and manage upcoming volunteer slots and dates.</p>

            <?php if ($message): ?>
                <div style="background: #e6f4ea; color: #137333; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= $message ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: #fce8e6; color: #c5221f; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= $error ?></div>
            <?php endif; ?>

            <!-- Add Schedule Form -->
            <form method="POST" style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--line); margin-bottom: 30px;">
                <h3 style="margin-top: 0; font-size: 1.1rem; color: var(--navy);">Add New Schedule</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 100px auto; gap: 12px; align-items: end;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">Date</label>
                        <input type="date" name="volunteer_date" required style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">Start Time</label>
                        <input type="text" name="start_time" placeholder="e.g. 9:00 AM" required style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">End Time</label>
                        <input type="text" name="end_time" placeholder="e.g. 12:00 PM" required style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">Slots</label>
                        <input type="number" name="slots_available" min="1" value="5" required style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                    </div>
                    <div>
                        <button type="submit" name="add_schedule" class="btn" style="background: var(--teal, #2a9d8f); color: #fff; border: none; padding: 9px 18px; border-radius: 6px; font-weight: 600; cursor: pointer;">Add</button>
                    </div>
                </div>
            </form>

            <!-- Schedules Table -->
            <div class="table-wrap">
                <table class="list" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Available Slots</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($schedules_result && $schedules_result->num_rows > 0): ?>
                            <?php while ($sched = $schedules_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= date('d M Y', strtotime($sched['volunteer_date'])) ?></strong></td>
                                    <td><?= e($sched['start_time']) ?> – <?= e($sched['end_time']) ?></td>
                                    <td><span class="badge" style="background: #e6f4ea; color: #137333; padding: 4px 10px; border-radius: 20px;"><?= $sched['slots_available'] ?> slots</span></td>
                                    <td><?= e($sched['status']) ?></td>
                                    <td>
                                        <a href="schedules.php?delete=<?= $sched['schedule_id'] ?>" onclick="return confirm('Are you sure you want to delete this schedule?');" style="color: #e53e3e; font-weight: 600; text-decoration: none;">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--muted); padding: 30px;">No volunteer schedules created yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php require '../includes/footer.php'; ?>