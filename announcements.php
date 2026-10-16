<?php
require 'config/db.php';
require 'includes/functions.php';

$types   = announcementTypes();
$type    = in_array($_GET['type'] ?? '', $types, true) ? $_GET['type'] : '';
$perPage = 6;
$page    = max(1, (int)($_GET['page'] ?? 1));

// How many announcements match?
if ($type !== '') {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM announcements WHERE announcement_type = ?");
    $stmt->bind_param("s", $type);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM announcements");
}
$stmt->execute();
$total  = (int)$stmt->get_result()->fetch_assoc()['c'];
$pages  = max(1, (int)ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

// Get this page of results
if ($type !== '') {
    $stmt = $conn->prepare(
        "SELECT announcement_id, title, content, announcement_type, image, date_posted
         FROM announcements WHERE announcement_type = ?
         ORDER BY date_posted DESC, announcement_id DESC LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("sii", $type, $perPage, $offset);
} else {
    $stmt = $conn->prepare(
        "SELECT announcement_id, title, content, announcement_type, image, date_posted
         FROM announcements
         ORDER BY date_posted DESC, announcement_id DESC LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Builds a link that keeps the current filter
function pageUrl($type, $page)
{
    $q = [];
    if ($type !== '') $q['type'] = $type;
    if ($page > 1)    $q['page'] = $page;
    return 'announcements.php' . ($q ? '?' . http_build_query($q) : '');
}

$pageTitle = 'Announcements';
$active    = 'announcements';
require 'includes/header.php';
?>

<h1>Announcements</h1>
<p class="lead">Rescue updates, events, and urgent needs from the Centre.</p>

<form method="get" action="announcements.php" class="ann-filter">
    <label for="type">Show</label>
    <select id="type" name="type" onchange="this.form.submit()">
        <option value="">All announcements</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-sm" type="submit">Apply</button></noscript>
    <?php if ($type !== ''): ?>
        <a href="announcements.php">Clear filter</a>
    <?php endif; ?>
    <span class="ann-count"><?= $total ?> <?= $total === 1 ? 'announcement' : 'announcements' ?></span>
</form>

<?php if (!$items): ?>
    <div class="panel">
        <p class="empty">No announcements<?= $type !== '' ? ' of this type' : '' ?> yet. Check back soon.</p>
    </div>
<?php else: ?>
    <div class="ann-grid">
        <?php foreach ($items as $a): ?>
            <?php
            $link = 'announcement.php?id=' . (int)$a['announcement_id'];
            $tone = str_replace('badge-', '', announcementBadge($a['announcement_type']));
            ?>
            <article class="ann-card tone-<?= e($tone) ?>">
                <?php if ($a['image']): ?>
                    <a class="ann-media" href="<?= e($link) ?>" tabindex="-1" aria-hidden="true">
                        <img src="uploads/<?= e($a['image']) ?>" alt="">
                    </a>
                <?php endif; ?>
                <div class="ann-text">
                    <div class="ann-meta">
                        <span class="badge <?= announcementBadge($a['announcement_type']) ?>"><?= e($a['announcement_type'] ?: 'Update') ?></span>
                        <time datetime="<?= e(date('Y-m-d', strtotime($a['date_posted']))) ?>"><?= e(date('d M Y', strtotime($a['date_posted']))) ?></time>
                    </div>
                    <h2><a href="<?= e($link) ?>"><?= e($a['title']) ?></a></h2>
                    <p><?= e(excerpt($a['content'])) ?></p>
                    <a class="more" href="<?= e($link) ?>">Read more</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Pages">
            <?php if ($page > 1): ?><a href="<?= e(pageUrl($type, $page - 1)) ?>">Previous</a><?php endif; ?>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="is-active" aria-current="page"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= e(pageUrl($type, $i)) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $pages): ?><a href="<?= e(pageUrl($type, $page + 1)) ?>">Next</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>