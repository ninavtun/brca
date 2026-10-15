<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

$types = announcementTypes();
$error = '';

// ---------- Delete ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['id'] ?? 0);
    if (!csrfValid()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $stmt = $conn->prepare("SELECT image FROM announcements WHERE announcement_id = ?");
        $stmt->bind_param("i", $delId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            $stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ?");
            $stmt->bind_param("i", $delId);
            $stmt->execute();
            if (!empty($row['image'])) {
                @unlink(__DIR__ . '/../uploads/' . basename($row['image']));
            }
        }
        header('Location: announcements.php?deleted=1');
        exit;
    }
}

// ---------- Search and filter ----------
$q     = trim($_GET['q'] ?? '');
$fType = in_array($_GET['type'] ?? '', $types, true) ? $_GET['type'] : '';

$where = [];
$bindTypes = '';
$params = [];
if ($q !== '') {
    $where[] = "title LIKE ?";
    $bindTypes .= 's';
    $params[] = '%' . $q . '%';
}
if ($fType !== '') {
    $where[] = "announcement_type = ?";
    $bindTypes .= 's';
    $params[] = $fType;
}

$sql = "SELECT announcement_id, title, announcement_type, image, date_posted FROM announcements"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY date_posted DESC, announcement_id DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($bindTypes, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Announcements';
$active    = 'admin';
require '../includes/header.php';
?>

<div class="account">
    <aside class="side-menu" aria-label="Admin Menu">
        <a href="dashboard.php">Dashboard Overview</a>
        <a href="animals.php">Manage Animals</a>
        <a href="animals_add.php">Add New Animal</a>
        <a href="kennels.php">Kennels</a>
        <a href="announcements.php" class="is-active">Announcements</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <?php if (isset($_GET['saved'])): ?>
            <div class="msg ok" role="status">Announcement saved.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="msg ok" role="status">Announcement deleted.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="panel">
            <h2>Announcements</h2>
            <p class="sub">Post rescue updates, events, urgent needs, and donation drives. They show on the public Announcements page.</p>
            <a href="announcements_add.php" class="btn btn-auto">+ New announcement</a>

            <form method="get" class="filters">
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by title" aria-label="Search by title">
                <select name="type" aria-label="Filter by type">
                    <option value="">All types</option>
                    <?php foreach ($types as $t): ?>
                        <option <?= $fType === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm" type="submit">Filter</button>
                <?php if ($q !== '' || $fType !== ''): ?>
                    <a href="announcements.php">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="panel">
            <?php if (!$rows): ?>
                <p class="empty">No announcements found.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="list">
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <?php if ($r['image']): ?>
                                        <img class="thumb" src="../uploads/<?= e($r['image']) ?>" alt="">
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= e($r['title']) ?></strong></td>
                                <td><span class="badge <?= announcementBadge($r['announcement_type']) ?>"><?= e($r['announcement_type'] ?: 'Update') ?></span></td>
                                <td><?= e(date('d M Y', strtotime($r['date_posted']))) ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-sm btn-outline" href="announcements_add.php?id=<?= (int)$r['announcement_id'] ?>">Edit</a>
                                        <form method="post" class="inline-form" onsubmit="return confirm('Delete this announcement? This cannot be undone.');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$r['announcement_id'] ?>">
                                            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require '../includes/footer.php'; ?>