<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

$errors  = [];
$matches = [];

$speciesList = ['Dog', 'Cat'];
$sexList     = ['Male', 'Female', 'Unknown'];

$f = [
    'name' => '',
    'species' => '',
    'breed' => '',
    'estimated_age' => '',
    'sex' => 'Unknown',
    'health_condition' => '',
    'description' => '',
    'intake_date' => date('Y-m-d'),
    'intake_source' => '',
    'kennel_id' => '',
    'is_published' => 0,
    'photo' => ''
];

// Kennels with how many animals are in each right now
$kennels = $conn->query(
    "SELECT k.kennel_id, k.kennel_name, k.section, k.capacity,
            COUNT(ka.assignment_id) AS occupied
     FROM kennels k
     LEFT JOIN kennel_assignments ka
            ON ka.kennel_id = k.kennel_id AND ka.date_out IS NULL
     GROUP BY k.kennel_id
     ORDER BY k.kennel_id"
)->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (
        [
            'name',
            'species',
            'breed',
            'estimated_age',
            'sex',
            'health_condition',
            'description',
            'intake_date',
            'intake_source',
            'kennel_id'
        ] as $key
    ) {
        $f[$key] = trim($_POST[$key] ?? '');
    }
    $f['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $confirmDuplicate  = isset($_POST['confirm_duplicate']);

    // Photo already uploaded on a previous submit (when the warning was shown)
    $existing = $_POST['photo_existing'] ?? '';
    if (
        preg_match('/^[a-f0-9]{16}\.(jpg|png|webp)$/', $existing)
        && file_exists(__DIR__ . '/../uploads/' . $existing)
    ) {
        $f['photo'] = $existing;
    }

    // Validation
    if ($f['name'] === '')                       $errors[] = 'Name is required.';
    if (!in_array($f['species'], $speciesList))  $errors[] = 'Please choose a species.';
    if (!in_array($f['sex'], $sexList))          $errors[] = 'Please choose a sex.';

    $d = DateTime::createFromFormat('Y-m-d', $f['intake_date']);
    if (!$d || $d->format('Y-m-d') !== $f['intake_date']) {
        $errors[] = 'Please enter a valid intake date.';
    }

    $chosenKennel = null;
    foreach ($kennels as $k) {
        if ($k['kennel_id'] == $f['kennel_id']) $chosenKennel = $k;
    }
    if (!$chosenKennel) {
        $errors[] = 'Please choose a kennel.';
    } elseif ($chosenKennel['occupied'] >= $chosenKennel['capacity']) {
        $errors[] = 'Kennel ' . $chosenKennel['kennel_name'] . ' is full. Choose another.';
    }

    // Photo upload (optional)
    if (!$errors && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['photo'];
        $info = ($file['error'] === UPLOAD_ERR_OK) ? @getimagesize($file['tmp_name']) : false;
        $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The photo could not be uploaded.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Photo must be 2 MB or smaller.';
        } elseif (!$info || !isset($types[$info[2]])) {
            $errors[] = 'Photo must be a JPG, PNG, or WEBP image.';
        } else {
            $newName = bin2hex(random_bytes(8)) . '.' . $types[$info[2]];
            move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/' . $newName);
            $f['photo'] = $newName;
        }
    }

    // Duplicate-name check
    if (!$errors && !$confirmDuplicate) {
        $stmt = $conn->prepare(
            "SELECT a.animal_id, a.name, a.species, a.status, a.photo, k.kennel_name
             FROM animals a
             LEFT JOIN kennel_assignments ka
                    ON ka.animal_id = a.animal_id AND ka.date_out IS NULL
             LEFT JOIN kennels k ON k.kennel_id = ka.kennel_id
             WHERE a.name = ?"   // not case-sensitive: Luna = luna
        );
        $stmt->bind_param("s", $f['name']);
        $stmt->execute();
        $matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Save
    if (!$errors && !$matches) {
        $conn->begin_transaction();
        try {
            $photo = $f['photo'] ?: null;
            $stmt = $conn->prepare(
                "INSERT INTO animals (name, species, breed, estimated_age, sex,
                  health_condition, description, intake_date, intake_source, photo, is_published)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->bind_param(
                "ssssssssssi",
                $f['name'],
                $f['species'],
                $f['breed'],
                $f['estimated_age'],
                $f['sex'],
                $f['health_condition'],
                $f['description'],
                $f['intake_date'],
                $f['intake_source'],
                $photo,
                $f['is_published']
            );
            $stmt->execute();
            $animalId = $conn->insert_id;

            $kennelId = (int)$f['kennel_id'];
            $adminId  = (int)$_SESSION['user_id'];
            $stmt = $conn->prepare(
                "INSERT INTO kennel_assignments (animal_id, kennel_id, moved_by) VALUES (?,?,?)"
            );
            $stmt->bind_param("iii", $animalId, $kennelId, $adminId);
            $stmt->execute();

            $conn->commit();
            header('Location: animals_add.php?saved=' . $animalId);
            exit;
        } catch (Throwable $ex) {
            $conn->rollback();
            $errors[] = 'Could not save the animal. Please try again.';
        }
    }
}
?>
<?php $pageTitle = 'Add Animal';
$active = 'admin';
require '../includes/header.php'; ?>
<div class="panel">

    <p><a href="dashboard.php">&larr; Dashboard</a> | <a href="animals.php">All animals</a></p>
    <h1>Add New Animal</h1>
    <p>The animal ID is generated automatically when you save.</p>

    <?php if (isset($_GET['saved'])): ?>
        <p style="color:green">Saved! New animal ID: <b><?= e(animalCode((int)$_GET['saved'])) ?></b></p>
    <?php endif; ?>

    <?php foreach ($errors as $err): ?>
        <p style="color:red"><?= e($err) ?></p>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="photo_existing" value="<?= e($f['photo']) ?>">

        <?php if ($matches): ?>
            <div style="border:2px solid red; padding:10px; margin-bottom:15px">
                <h3 style="color:red">&#9888; Similar name found</h3>
                <p><?= count($matches) ?> animal(s) named &ldquo;<?= e($f['name']) ?>&rdquo; already exist. Check first:</p>
                <?php foreach ($matches as $m): ?>
                    <div style="border:1px solid #999; padding:8px; margin:6px 0">
                        <?php if ($m['photo']): ?>
                            <img src="../uploads/<?= e($m['photo']) ?>" width="80" align="left" style="margin-right:10px">
                        <?php endif; ?>
                        <b><?= e($m['name']) ?></b> &middot; <?= e($m['species']) ?><br>
                        ID: <b><?= e(animalCode($m['animal_id'])) ?></b><br>
                        Kennel: <?= e($m['kennel_name'] ?? 'None') ?> &middot; <?= e($m['status']) ?>
                        <div style="clear:both"></div>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="confirm_duplicate" value="1">Create as New Animal</button>
                <a href="animals_add.php">Cancel</a>
            </div>
        <?php endif; ?>

        <p>Name:<br><input name="name" value="<?= e($f['name']) ?>" required></p>

        <p>Species:<br>
            <select name="species" required>
                <option value="">Choose...</option>
                <?php foreach ($speciesList as $s): ?>
                    <option <?= $f['species'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>Breed:<br><input name="breed" value="<?= e($f['breed']) ?>"></p>
        <p>Estimated age (for example 2 yrs, 6 mo):<br><input name="estimated_age" value="<?= e($f['estimated_age']) ?>"></p>

        <p>Sex:<br>
            <select name="sex">
                <?php foreach ($sexList as $s): ?>
                    <option <?= $f['sex'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>Health condition:<br><input name="health_condition" value="<?= e($f['health_condition']) ?>"></p>
        <p>Description:<br><textarea name="description" rows="3" cols="40"><?= e($f['description']) ?></textarea></p>
        <p>Intake date:<br><input type="date" name="intake_date" value="<?= e($f['intake_date']) ?>" required></p>
        <p>Intake source:<br><input name="intake_source" value="<?= e($f['intake_source']) ?>" placeholder="Rescued, surrendered..."></p>

        <p>Assign kennel:<br>
            <select name="kennel_id" required>
                <option value="">Select kennel...</option>
                <?php foreach ($kennels as $k): ?>
                    <option value="<?= $k['kennel_id'] ?>" <?= $f['kennel_id'] == $k['kennel_id'] ? 'selected' : '' ?>>
                        <?= e($k['kennel_name']) ?> (<?= $k['occupied'] ?>/<?= $k['capacity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>Photo (JPG, PNG, or WEBP, max 2 MB):<br>
            <?php if ($f['photo']): ?>
                <img src="../uploads/<?= e($f['photo']) ?>" width="80"><br>Photo kept. Choose a file only to replace it.<br>
            <?php endif; ?>
            <input type="file" name="photo" accept="image/*">
        </p>

        <p><label><input type="checkbox" name="is_published" <?= $f['is_published'] ? 'checked' : '' ?>>
                Publish on the website for adoption/fostering</label></p>

        <button type="submit">Save Animal</button>
    </form>
</div>
<?php require '../includes/footer.php'; ?>