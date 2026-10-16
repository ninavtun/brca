<?php
require '../config/db.php';
require '../includes/functions.php';

if (empty($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';
$animalId = (int)($_GET['id'] ?? 0);

if ($animalId <= 0) {
    header('Location: animals.php');
    exit;
}

$conn->query("ALTER TABLE animals ADD COLUMN IF NOT EXISTS intake_source VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE animals ADD COLUMN IF NOT EXISTS estimated_age VARCHAR(50) DEFAULT NULL");
$conn->query("ALTER TABLE animals ADD COLUMN IF NOT EXISTS dob DATE DEFAULT NULL");
$conn->query("ALTER TABLE animals ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL");

// Fetch animal details & current kennel assignment
$stmt = $conn->prepare("
    SELECT a.*, ka.kennel_id 
    FROM animals a 
    LEFT JOIN kennel_assignments ka ON ka.animal_id = a.animal_id AND ka.date_out IS NULL
    WHERE a.animal_id = ?
");
$stmt->bind_param("i", $animalId);
$stmt->execute();
$animal = $stmt->get_result()->fetch_assoc();

if (!$animal) {
    header('Location: animals.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $species = trim($_POST['species'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $breed = trim($_POST['breed'] ?? '');
    $estimated_age = trim($_POST['estimated_age'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $health_condition = trim($_POST['health_condition'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assign_kennel = (int)($_POST['assign_kennel'] ?? 0);

    if ($name === '' || $species === '') {
        $error = "Name and species are required fields.";
    } else {
        $conn->begin_transaction();
        try {
            $dob_val = ($dob !== '') ? $dob : null;
            $updateStmt = $conn->prepare("UPDATE animals SET name = ?, species = ?, status = ?, breed = ?, estimated_age = ?, dob = ?, sex = ?, health_condition = ?, description = ? WHERE animal_id = ?");
            $updateStmt->bind_param("sssssssssi", $name, $species, $status, $breed, $estimated_age, $dob_val, $sex, $health_condition, $description, $animalId);
            $updateStmt->execute();

            // Update kennel assignment
            $conn->query("UPDATE kennel_assignments SET date_out = NOW() WHERE animal_id = $animalId AND date_out IS NULL");
            if ($assign_kennel > 0) {
                $adminId = (int)$_SESSION['user_id'];
                $assignStmt = $conn->prepare("INSERT INTO kennel_assignments (animal_id, kennel_id, moved_by, date_in) VALUES (?, ?, ?, NOW())");
                $assignStmt->bind_param("iii", $animalId, $assign_kennel, $adminId);
                $assignStmt->execute();
            }

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['photo']['tmp_name'];
                $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $uploadFileDir = '../assets/img/animals/';
                    if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0755, true);
                    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                        $old = $uploadFileDir . $animalId . '.' . $ext;
                        if (file_exists($old)) unlink($old);
                    }
                    move_uploaded_file($fileTmpPath, $uploadFileDir . $animalId . '.' . $fileExtension);
                }
            }

            $conn->commit();
            $success = "Animal record updated successfully.";

            // Re-fetch updated data
            $stmt->execute();
            $animal = $stmt->get_result()->fetch_assoc();
        } catch (Throwable $ex) {
            $conn->rollback();
            $error = "Update failed: " . $ex->getMessage();
        }
    }
}

$kennels = $conn->query("SELECT kennel_id, kennel_name FROM kennels WHERE kennel_name IS NOT NULL AND kennel_name != '' ORDER BY kennel_name")->fetch_all(MYSQLI_ASSOC);

$photoPath = '';
foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
    if (file_exists('../assets/img/animals/' . $animalId . '.' . $ext)) {
        $photoPath = '../assets/img/animals/' . $animalId . '.' . $ext;
        break;
    }
}

$pageTitle = 'Edit Animal';
$active = 'admin';
require '../includes/header.php';
?>

<div class="account">
    <aside class="side-menu" aria-label="Admin Menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="animals.php" class="is-active">Animals</a>
        <a href="animals_add.php">Add New Animal</a>
        <a href="kennels.php">Kennels</a>
        <a href="applications.php">Applications</a>
        <a href="schedules.php">Schedules</a>
        <a href="announcements.php">Announcements</a>
        <a href="donation.php">Donations</a>
        <a href="../account.php">Users / Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <div class="panel" style="max-width: 700px; margin: 0 auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <h2>Edit Animal: <?= htmlspecialchars(animalCode($animalId)) ?></h2>
                    <p class="sub" style="margin:0;">Update animal profile, kennel assignment, and photo.</p>
                </div>
                <a href="animals.php" class="btn btn-outline" style="padding:8px 16px; border:1px solid var(--line); border-radius:6px; text-decoration:none; color:var(--navy); background:#fff;">← Back to List</a>
            </div>

            <?php if ($success): ?>
                <div style="background: #e6f4ea; color: #137333; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: #fce8e6; color: #c5221f; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:15px;">
                <div style="display:flex; align-items:center; gap:20px; background:#f8fafc; padding:15px; border-radius:8px; border:1px solid var(--line);">
                    <div>
                        <?php if ($photoPath): ?>
                            <img src="<?= $photoPath ?>" alt="" style="width:70px; height:70px; object-fit:cover; border-radius:8px;">
                        <?php else: ?>
                            <div style="width:70px; height:70px; background:#e2e8f0; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">🐾</div>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; font-weight:600; margin-bottom:6px;">Update Animal Photo</label>
                        <input type="file" name="photo" accept="image/*" style="width:100%; padding:6px; border:1px solid var(--line); border-radius:6px; background:#fff;">
                    </div>
                </div>

                <div style="display:flex; gap:15px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; font-weight:600; margin-bottom:6px;">Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($animal['name'] ?? '') ?>" required style="width:100%; padding:10px; border:1px solid var(--line); border-radius:6px;">
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; font-weight:600; margin-bottom:6px;">Species</label>
                        <input type="text" name="species" value="<?= htmlspecialchars($animal['species'] ?? '') ?>" required style="width:100%; padding:10px; border:1px solid var(--line); border-radius:6px;">
                    </div>
                </div>

                <div style="display:flex; gap:15px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; font-weight:600; margin-bottom:6px;">Status</label>
                        <select name="status" style="width:100%; padding:10px; border:1px solid var(--line); border-radius:6px;">
                            <?php foreach (['Available', 'Foster', 'Pending', 'Medical', 'Adopted'] as $st): ?>
                                <option value="<?= $st ?>" <?= (($animal['status'] ?? '') === $st) ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; font-weight:600; margin-bottom:6px;">Breed</label>
                        <input type="text" name="breed" value="<?= htmlspecialchars($animal['breed'] ?? '') ?>" style="width:100%; padding:10px; border:1px solid var(--line); border-radius:6px;">
                    </div>
                </div>

                <div style="display:flex; gap:15px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; font-weight:600; margin-bottom:6px;">Estimated Age</label>
                        <input type="text" name="estimated_age" value="<?= htmlspecialchars($animal['estimated_age'] ?? '') ?>" style="width:100%; padding:10px; border:1px solid var(--line); border-radius:6px;">
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; font-weight:600; margin-bottom:6px;">Date of Birth</label>
                        <input type="date" name="dob" value="<?= htmlspecialchars($animal['dob'] ?? '') ?>" style="width:100%; padding:10px; border:1px solid var(--line); border-radius:6px;">
                    </div>
                </div>

                <div style="display:flex; gap:15px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; font-weight:600; margin-bottom:6px;">Sex</label>
                        <select name="sex" style="width:100%; padding:10px; border:1px solid var(--line); border-radius:6px;">
                            <?php foreach (['Unknown', 'Male', 'Female'] as $sx): ?>
                                <option value="<?= $sx ?>" <?= (($animal['sex'] ?? '') === $sx) ? 'selected' : '' ?>><?= $sx ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; font-weight:600; margin-bottom:6px;">Assign Kennel</label>
                        <select name="assign_kennel" style="width:100%; padding:10px; border:1px solid var(--line); border-radius:6px; background:#fff;">
                            <option value="">Unassigned</option>
                            <?php foreach ($kennels as $k): ?>
                                <option value="<?= (int)$k['kennel_id'] ?>" <?= (($animal['kennel_id'] ?? 0) == $k['kennel_id']) ? 'selected' : '' ?>>Kennel <?= htmlspecialchars($k['kennel_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:6px;">Health Condition</label>
                    <input type="text" name="health_condition" value="<?= htmlspecialchars($animal['health_condition'] ?? '') ?>" style="width:100%; padding:10px; border:1px solid var(--line); border-radius:6px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:6px;">Description / Notes</label>
                    <textarea name="description" rows="4" style="width:100%; padding:10px; border:1px solid var(--line); border-radius:6px;"><?= htmlspecialchars($animal['description'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn" style="background:#2a9d8f; color:#fff; border:none; padding:12px; border-radius:8px; font-weight:600; cursor:pointer; margin-top:10px;">Save Changes</button>
            </form>
        </div>
    </section>
</div>

<?php require '../includes/footer.php'; ?>