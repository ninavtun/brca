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

// Helper function to normalize flexible time inputs (e.g., "9am", "9:00 am", "12 pm")
function normalizeTime($timeStr) {
    $timeStr = trim($timeStr);
    if (empty($timeStr)) return '';
    
    // Try parsing with PHP's strtotime
    $timestamp = strtotime($timeStr);
    if ($timestamp !== false) {
        return date('h:i A', $timestamp);
    }
    return $timeStr; // fallback to original string if custom
}

// Handle Add Schedule Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $raw_date = trim($_POST['volunteer_date']);
    $start_time = normalizeTime($_POST['start_time']);
    $end_time = normalizeTime($_POST['end_time']);
    $slots = intval($_POST['slots_available']);

    // Parse flexible date strings like "October 9 2026" or "11 Nov 2026"
    $date_timestamp = strtotime($raw_date);
    $date = $date_timestamp ? date('Y-m-d', $date_timestamp) : '';

    if (!empty($date) && !empty($start_time) && !empty($end_time) && $slots > 0) {
        $stmt = $conn->prepare("INSERT INTO volunteer_schedules (volunteer_date, start_time, end_time, slots_available, status) VALUES (?, ?, ?, ?, 'Active')");
        $stmt->bind_param("sssi", $date, $start_time, $end_time, $slots);
        if ($stmt->execute()) {
            $message = "Volunteer schedule added successfully!";
        } else {
            $error = "Error adding schedule.";
        }
    } else {
        $error = "Please fill in all fields with valid dates/times.";
    }
}

// Handle Edit Schedule Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_schedule'])) {
    $edit_id = intval($_POST['schedule_id']);
    $raw_date = trim($_POST['volunteer_date']);
    $start_time = normalizeTime($_POST['start_time']);
    $end_time = normalizeTime($_POST['end_time']);
    $slots = intval($_POST['slots_available']);
    $status = trim($_POST['status']);

    $date_timestamp = strtotime($raw_date);
    $date = $date_timestamp ? date('Y-m-d', $date_timestamp) : '';

    if ($edit_id > 0 && !empty($date) && !empty($start_time) && !empty($end_time) && $slots > 0) {
        $stmt = $conn->prepare("UPDATE volunteer_schedules SET volunteer_date = ?, start_time = ?, end_time = ?, slots_available = ?, status = ? WHERE schedule_id = ?");
        $stmt->bind_param("sssisi", $date, $start_time, $end_time, $slots, $status, $edit_id);
        if ($stmt->execute()) {
            header('Location: schedules.php?updated=1');
            exit;
        } else {
            $error = "Error updating schedule.";
        }
    } else {
        $error = "Please fill in all fields correctly to update.";
    }
}

if (isset($_GET['updated'])) {
    $message = "Volunteer schedule updated successfully!";
}

// Handle Delete Schedule safely by deleting linked applications first
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Delete dependent volunteer applications first to satisfy foreign key constraints
    $conn->query("DELETE FROM volunteer_applications WHERE schedule_id = $id");
    
    // Now delete the schedule
    if ($conn->query("DELETE FROM volunteer_schedules WHERE schedule_id = $id")) {
        header('Location: schedules.php');
        exit;
    } else {
        $error = "Error deleting schedule.";
    }
}

// Check if we are editing a specific schedule
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $conn->prepare("SELECT * FROM volunteer_schedules WHERE schedule_id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_data = $edit_stmt->get_result()->fetch_assoc();
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
        <a href="announcements.php">Announcements</a>
        <a href="applications.php">Applications</a>
        <a href="schedules.php" class="is-active">Schedules</a>
	<a href="home_settings.php">Home Page Settings</a>
	<a href="about_settings.php">About Page Settings</a>
	<a href="donation.php">Donation Settings</a>
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

            <?php if ($edit_data): ?>
                <!-- Edit Schedule Form -->
                <form method="POST" style="background: #fef9e7; border: 1px solid #f9e79f; padding: 20px; border-radius: 12px; margin-bottom: 30px;">
                    <h3 style="margin-top: 0; font-size: 1.1rem; color: #b7950b;">Edit Schedule ID: <?= (int)$edit_data['schedule_id'] ?></h3>
                    <input type="hidden" name="schedule_id" value="<?= (int)$edit_data['schedule_id'] ?>">
                    <div style="display: grid; grid-template-columns: 1.2fr 1fr 1fr 90px 120px auto; gap: 12px; align-items: end;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">Date (e.g. Oct 9 2026)</label>
                            <input type="text" name="volunteer_date" value="<?= htmlspecialchars($edit_data['volunteer_date']) ?>" required style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">Start Time</label>
                            <input type="text" name="start_time" value="<?= htmlspecialchars($edit_data['start_time']) ?>" placeholder="e.g. 9am" required style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">End Time</label>
                            <input type="text" name="end_time" value="<?= htmlspecialchars($edit_data['end_time']) ?>" placeholder="e.g. 12pm" required style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">Slots</label>
                            <input type="number" name="slots_available" min="1" value="<?= (int)$edit_data['slots_available'] ?>" required style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">Status</label>
                            <select name="status" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                                <option value="Active" <?= $edit_data['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Closed" <?= $edit_data['status'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" name="edit_schedule" class="btn" style="background: #b7950b; color: #fff; border: none; padding: 9px 14px; border-radius: 6px; font-weight: 600; cursor: pointer;">Update</button>
                            <a href="schedules.php" class="btn" style="background: #cbd5e1; color: #334155; text-decoration: none; padding: 9px 14px; border-radius: 6px; font-weight: 600; display: inline-block;">Cancel</a>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <!-- Add Schedule Form -->
                <form method="POST" style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--line); margin-bottom: 30px;">
                    <h3 style="margin-top: 0; font-size: 1.1rem; color: var(--navy);">Add New Schedule</h3>
                    <div style="display: grid; grid-template-columns: 1.2fr 1fr 1fr 90px auto; gap: 12px; align-items: end;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">Date (e.g. October 9 2026)</label>
                            <input type="text" name="volunteer_date" placeholder="e.g. Oct 9 2026" required style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">Start Time</label>
                            <input type="text" name="start_time" placeholder="e.g. 9am or 9:00 AM" required style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">End Time</label>
                            <input type="text" name="end_time" placeholder="e.g. 12pm or 12:00 PM" required style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
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
            <?php endif; ?>

            <!-- Schedules Table -->
            <div class="table-wrap">
                <table class="list" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--line);">
                            <th style="padding: 10px;">Date</th>
                            <th style="padding: 10px;">Time</th>
                            <th style="padding: 10px;">Available Slots</th>
                            <th style="padding: 10px;">Status</th>
                            <th style="padding: 10px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($schedules_result && $schedules_result->num_rows > 0): ?>
                            <?php while ($sched = $schedules_result->fetch_assoc()): ?>
                                <tr style="border-top: 1px solid #dde3ea;">
                                    <td style="padding: 10px;"><strong><?= date('d M Y', strtotime($sched['volunteer_date'])) ?></strong></td>
                                    <td style="padding: 10px;"><?= e($sched['start_time']) ?> – <?= e($sched['end_time']) ?></td>
                                    <td style="padding: 10px;"><span class="badge" style="background: #e6f4ea; color: #137333; padding: 4px 10px; border-radius: 20px;"><?= $sched['slots_available'] ?> slots</span></td>
                                    <td style="padding: 10px;"><?= e($sched['status']) ?></td>
                                    <td style="padding: 10px; white-space: nowrap;">
                                        <a href="schedules.php?edit=<?= (int)$sched['schedule_id'] ?>" style="color: #2a9d8f; font-weight: 600; text-decoration: none; margin-right: 12px;">Edit</a>
                                        <a href="schedules.php?delete=<?= (int)$sched['schedule_id'] ?>" onclick="return confirm('Are you sure you want to delete this schedule? This will also remove any linked applications.');" style="color: #e53e3e; font-weight: 600; text-decoration: none;">Delete</a>
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