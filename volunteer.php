<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Fetch live schedules from volunteer_schedules table
$schedules_query = "SELECT * FROM volunteer_schedules WHERE volunteer_date >= CURDATE() ORDER BY volunteer_date ASC";
$schedules_result = $conn->query($schedules_query);

$pageTitle = 'Volunteer With Us';
$active = 'volunteer';
require 'includes/header.php';
?>

<div style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
    <h2>Volunteer With Us</h2>
    <p style="color: var(--muted); margin-bottom: 30px;">Help us care for rescued cats and dogs.</p>

    <h3 style="margin-bottom: 20px; color: var(--navy);">Available Schedules</h3>

    <?php if ($schedules_result && $schedules_result->num_rows > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
            <?php while ($sched = $schedules_result->fetch_assoc()): ?>
                <div style="background: #fff; border: 1px solid var(--line, #d9dfe9); border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="font-size: 1.1rem; font-weight: bold; color: var(--navy); margin-bottom: 6px;">
                            <?= date('d F Y', strtotime($sched['volunteer_date'])) ?>
                        </div>
                        <div style="color: var(--muted); font-size: 0.95rem; margin-bottom: 16px;">
                            <?= e($sched['start_time']) ?> – <?= e($sched['end_time']) ?>
                        </div>
                        <div style="display: inline-block; background: #e6f4ea; color: #137333; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-bottom: 20px;">
                            <?= $sched['slots_available'] ?> slots left
                        </div>
                    </div>
                    <!-- Directs to the application form page via GET -->
                    <form action="volunteer_apply.php" method="GET">
                        <input type="hidden" name="schedule_id" value="<?= $sched['schedule_id'] ?>">
                        <button type="submit" class="btn" style="width: 100%; background: var(--teal, #2a9d8f); color: #fff; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer;">Apply</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="background: #fff; padding: 40px; text-align: center; border-radius: 12px; border: 1px solid var(--line); color: var(--muted);">
            No active volunteer schedules are available right now. Please check back later!
        </div>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>