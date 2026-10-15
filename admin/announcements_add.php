<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

$types   = announcementTypes();
$id      = (int)($_GET['id'] ?? 0);
$editing = $id > 0;
$errors  = [];
$f = ['title' => '', 'announcement_type' => $types[0], 'content' => '', 'image' => ''];

// Editing: load the existing announcement
if ($editing) {
    $stmt = $conn->prepare("SELECT title, announcement_type, content, image FROM announcements WHERE announcement_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        header('Location: announcements.php');
        exit;
    }
    $f['title']             = $row['title'];
    $f['announcement_type'] = $row['announcement_type'] ?: $types[0];
    $f['content']           = $row['content'];
    $f['image']             = $row['image'] ?? '';
}
$oldImage = $f['image'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $f['title']             = trim($_POST['title'] ?? '');
        $f['announcement_type'] = $_POST['announcement_type'] ?? '';
        $f['content']           = trim($_POST['content'] ?? '');
        $removeImage            = !empty($_POST['remove_image']);

        if ($f['title'] === '') {
            $errors[] = 'Please enter a title.';
        } elseif (mb_strlen($f['title']) > 150) {
            $errors[] = 'Title must be 150 characters or fewer.';
        }
        if (!in_array($f['announcement_type'], $types, true)) {
            $errors[] = 'Please choose a type.';
        }
        if ($f['content'] === '') {
            $errors[] = 'Please write the announcement text.';
        }

        // Image (optional)
        $newImage = $oldImage;
        if ($removeImage) {
            $newImage = '';
        }
        if (!$errors && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file  = $_FILES['image'];
            $info  = ($file['error'] === UPLOAD_ERR_OK) ? @getimagesize($file['tmp_name']) : false;
            $kinds = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'The image could not be uploaded.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Image must be 2 MB or smaller.';
            } elseif (!$info || !isset($kinds[$info[2]])) {
                $errors[] = 'Image must be a JPG, PNG, or WEBP file.';
            } else {
                $newName = bin2hex(random_bytes(8)) . '.' . $kinds[$info[2]];
                move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/' . $newName);
                $newImage = $newName;
            }
        }

        // Save
        if (!$errors) {
            $img = $newImage !== '' ? $newImage : null;
            if ($editing) {
                $stmt = $conn->prepare(
                    "UPDATE announcements SET title = ?, content = ?, announcement_type = ?, image = ?
                     WHERE announcement_id = ?"
                );
                $stmt->bind_param("ssssi", $f['title'], $f['content'], $f['announcement_type'], $img, $id);
            } else {
                $adminId = (int)$_SESSION['user_id'];
                $stmt = $conn->prepare(
                    "INSERT INTO announcements (admin_id, title, content, announcement_type, image)
                     VALUES (?,?,?,?,?)"
                );
                $stmt->bind_param("issss", $adminId, $f['title'], $f['content'], $f['announcement_type'], $img);
            }
            $stmt->execute();

            // Remove the old image file if it was replaced or removed
            if ($oldImage !== '' && $oldImage !== $newImage) {
                @unlink(__DIR__ . '/../uploads/' . basename($oldImage));
            }
            header('Location: announcements.php?saved=1');
            exit;
        }
    }
}

$pageTitle = $editing ? 'Edit Announcement' : 'New Announcement';
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
        <div class="panel">
            <h2><?= $editing ? 'Edit announcement' : 'New announcement' ?></h2>
            <p class="sub">This will appear on the public Announcements page.</p>

            <?php foreach ($errors as $err): ?>
                <div class="msg error" role="alert"><?= e($err) ?></div>
            <?php endforeach; ?>

            <form method="post" enctype="multipart/form-data">
                <?= csrfField() ?>

                <label for="title">Title</label>
                <input id="title" name="title" maxlength="150" value="<?= e($f['title']) ?>" required>

                <label for="announcement_type">Type</label>
                <select id="announcement_type" name="announcement_type">
                    <?php foreach ($types as $t): ?>
                        <option <?= $f['announcement_type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="content">Announcement text</label>
                <textarea id="content" name="content" rows="8" required><?= e($f['content']) ?></textarea>

                <label for="image">Image <span class="opt">(optional, JPG/PNG/WEBP, max 2 MB)</span></label>
                <?php if ($editing && $oldImage !== ''): ?>
                    <img class="preview-img" src="../uploads/<?= e($oldImage) ?>" alt="Current image">
                    <label class="check"><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
                    <p class="hint">Choose a file below only to replace it.</p>
                <?php endif; ?>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">

                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:20px;">
                    <button class="btn btn-auto" type="submit" style="margin-top:0"><?= $editing ? 'Save changes' : 'Post announcement' ?></button>
                    <a class="btn btn-auto btn-outline" href="announcements.php" style="margin-top:0">Cancel</a>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require '../includes/footer.php'; ?>