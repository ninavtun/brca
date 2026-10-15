<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php?next=volunteer.php');
    exit;
}

$schedule_id = intval($_GET['schedule_id'] ?? 0);
if (!$schedule_id) {
    header('Location: volunteer.php');
    exit;
}

// Fetch schedule details
$sched_stmt = $conn->prepare("SELECT * FROM volunteer_schedules WHERE schedule_id = ?");
$sched_stmt->bind_param("i", $schedule_id);
$sched_stmt->execute();
$schedule = $sched_stmt->get_result()->fetch_assoc();

if (!$schedule) {
    header('Location: volunteer.php');
    exit;
}

$message = '';
$error = '';

// Handle form submission matching exact table columns
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    // Insert into volunteer_applications using only valid columns
    $stmt = $conn->prepare("INSERT INTO volunteer_applications (user_id, schedule_id, status, application_date) VALUES (?, ?, 'Pending', NOW())");
    $stmt->bind_param("ii", $user_id, $schedule_id);
    
    if ($stmt->execute()) {
        header('Location: account.php?success=1');
        exit;
    } else {
        $error = "Failed to submit your application. Please try again: " . $conn->error;
    }
}

$pageTitle = 'Volunteer Application';
$active = 'volunteer';
require 'includes/header.php';
?>

<div style="max-width: 700px; margin: 40px auto; padding: 0 20px;">
    <h2>Volunteer Application Form</h2>
    <p style="color: var(--muted); margin-bottom: 24px;">
        Applying for schedule: <strong><?= date('d M Y', strtotime($schedule['volunteer_date'])) ?></strong> (<?= e($schedule['start_time']) ?> – <?= e($schedule['end_time']) ?>)
    </p>

    <?php if ($error): ?>
        <div style="background: #fce8e6; color: #c5221f; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" style="background: #fff; padding: 30px; border-radius: 12px; border: 1px solid var(--line); box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: bold; margin-bottom: 6px; color: var(--navy);">Why do you want to volunteer with BRCA?</label>
            <textarea name="experience" rows="4" required placeholder="Tell us a bit about your experience with animals or why you'd love to help..." style="width: 100%; padding: 12px; border: 1px solid var(--line); border-radius: 8px; font-family: inherit;"></textarea>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-weight: bold; margin-bottom: 6px; color: var(--navy);">Any additional notes or special requirements?</label>
            <textarea name="availability_notes" rows="3" placeholder="Optional details..." style="width: 100%; padding: 12px; border: 1px solid var(--line); border-radius: 8px; font-family: inherit;"></textarea>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn" style="background: var(--teal, #2a9d8f); color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;">Submit Application</button>
            <a href="volunteer.php" style="background: #e2e8f0; color: #475569; padding: 12px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center;">Cancel</a>
        </div>
    </form>
</div>

<?php require 'includes/footer.php'; ?>