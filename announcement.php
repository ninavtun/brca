<?php
require 'config/db.php';
require 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare(
    "SELECT title, content, announcement_type, image, date_posted
     FROM announcements WHERE announcement_id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$a = $stmt->get_result()->fetch_assoc();

if (!$a) {
    http_response_code(404);
}

$pageTitle = $a ? $a['title'] : 'Announcement not found';
$active    = 'announcements';
require 'includes/header.php';
?>

<p><a href="announcements.php">&larr; Back to announcements</a></p>

<?php if (!$a): ?>
    <div class="panel">
        <h2>Announcement not found</h2>
        <p class="empty">It may have been removed. <a href="announcements.php">See all announcements</a>.</p>
    </div>
<?php else: ?>
    <article class="ann-detail tone-<?= e(str_replace('badge-', '', announcementBadge($a['announcement_type']))) ?>">
        <?php if ($a['image']): ?>
            <img src="uploads/<?= e($a['image']) ?>" alt="">
        <?php endif; ?>
        <div class="ann-content">
            <div class="ann-meta">
                <span class="badge <?= announcementBadge($a['announcement_type']) ?>"><?= e($a['announcement_type'] ?: 'Update') ?></span>
                <time datetime="<?= e(date('Y-m-d', strtotime($a['date_posted']))) ?>"><?= e(date('d M Y', strtotime($a['date_posted']))) ?></time>
            </div>
            <h1><?= e($a['title']) ?></h1>
            <p><?= nl2br(e($a['content'])) ?></p>
        </div>
    </article>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>