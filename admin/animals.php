<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

// Ensure user is an admin
if (empty($_SESSION['user_id']) OR !isset($_SESSION['role']) OR $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Handle Bulk Actions (Partial updates or Bulk Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' AND isset($_POST['apply_bulk'])) {
    $selected_ids = $_POST['selected_animals'] ?? [];
    $bulk_kennel = (int)($_POST['bulk_kennel'] ?? 0);
    $bulk_status = trim($_POST['bulk_status'] ?? '');
    $bulk_health = trim($_POST['bulk_health'] ?? '');

    if (!empty($selected_ids)) {
        $ids = array_map('intval', $selected_ids);
        $idList = implode(',', $ids);
        $adminId = (int)$_SESSION['user_id'];

        $conn->begin_transaction();
        try {
            // 1. Update Kennel if chosen
            if ($bulk_kennel > 0) {
                foreach ($ids as $aid) {
                    $conn->query("UPDATE kennel_assignments SET date_out = NOW() WHERE animal_id = $aid AND date_out IS NULL");
                    $assignStmt = $conn->prepare("INSERT INTO kennel_assignments (animal_id, kennel_id, moved_by, date_in) VALUES (?, ?, ?, NOW())");
                    $assignStmt->bind_param("iii", $aid, $bulk_kennel, $adminId);
                    $assignStmt->execute();
                }
            }

            // 2. Update Status if chosen
            if ($bulk_status !== '') {
                $statusStmt = $conn->prepare("UPDATE animals SET status = ? WHERE animal_id IN ($idList)");
                $statusStmt->bind_param("s", $bulk_status);
                $statusStmt->execute();
            }

            // 3. Update Health Condition if chosen
            if ($bulk_health !== '') {
                $healthStmt = $conn->prepare("UPDATE animals SET health_condition = ? WHERE animal_id IN ($idList)");
                $healthStmt->bind_param("s", $bulk_health);
                $healthStmt->execute();
            }

            $conn->commit();
            $success = "Selected animal records updated successfully!";
        } catch (Throwable $ex) {
            $conn->rollback();$error = "Bulk update failed: " . $ex->getMessage();
        }
    } else {
        $error = "Please select at least one animal.";
    }
}

// Handle Bulk Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' AND isset($_POST['bulk_delete'])) {
    $selected_ids =$_POST['selected_animals'] ?? [];
    if (!empty($selected_ids)) {$ids = array_map('intval', $selected_ids);$conn->begin_transaction();
        try {
            foreach ($ids as $animalId) {$conn->query("DELETE FROM adoption_foster_apps WHERE animal_id = $animalId");
                $conn->query("DELETE FROM kennel_assignments WHERE animal_id = $animalId");
                $conn->query("DELETE FROM animals WHERE animal_id = $animalId");
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {$photoFile = '../assets/img/animals/' . $animalId . '.' .$ext;
                    if (file_exists($photoFile)) unlink($photoFile);
                }
            }
            $conn->commit();
            header('Location: animals.php?deleted=1');
            exit;
        } catch (Throwable $ex) {
            $conn->rollback();$error = "Bulk delete failed: " . $ex->getMessage();
        }
    } else {
        $error = "Please select at least one animal to delete.";
    }
}

// Handle Single Delete Animal safely
if (isset($_GET['delete'])) {
    $animalId = (int)$_GET['delete'];

    $conn->begin_transaction();
    try {
        $stmtApp =$conn->prepare("DELETE FROM adoption_foster_apps WHERE animal_id = ?");
        $stmtApp->bind_param("i", $animalId);$stmtApp->execute();

        $stmtKennel =$conn->prepare("DELETE FROM kennel_assignments WHERE animal_id = ?");
        $stmtKennel->bind_param("i", $animalId);$stmtKennel->execute();

        $stmtAnimal =$conn->prepare("DELETE FROM animals WHERE animal_id = ?");
        $stmtAnimal->bind_param("i", $animalId);$stmtAnimal->execute();

        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {$photoFile = '../assets/img/animals/' . $animalId . '.' .$ext;
            if (file_exists($photoFile)) unlink($photoFile);
        }

        $conn->commit();
        header('Location: animals.php?deleted=1');
        exit;
    } catch (Throwable $ex) {
        $conn->rollback();$error = "Could not delete animal: " . $ex->getMessage();
    }
}

if (isset($_GET['deleted']))$success = "Animal record deleted successfully.";
if (isset($_GET['added']))$success = "New animal added successfully!";

// Get Search & Filter parameters
$search = trim($_GET['search'] ?? '');
$filter_species = trim($_GET['species'] ?? '');
$filter_kennel = trim($_GET['kennel'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

// Build Query with Filters
$query = "
    SELECT a.*, k.kennel_name 
    FROM animals a 
    LEFT JOIN kennel_assignments ka ON ka.animal_id = a.animal_id AND ka.date_out IS NULL
    LEFT JOIN kennels k ON k.kennel_id = ka.kennel_id
    WHERE 1=1
";
$params = [];$types = '';

if ($search !== '') {
    $searchTerm = "%{$search}%";
    $query .= " AND (a.name LIKE ? OR a.animal_id LIKE ? OR a.species LIKE ? OR k.kennel_name LIKE ?)";
    $types .= 'ssss';
    array_push($params, $searchTerm,$searchTerm, $searchTerm,$searchTerm);
}

if ($filter_species !== '') {$query .= " AND a.species = ?";
    $types .= 's';
    $params[] =$filter_species;
}

if ($filter_kennel !== '') {$query .= " AND k.kennel_id = ?";
    $types .= 'i';
    $params[] = (int)$filter_kennel;
}

if ($filter_status !== '') {$query .= " AND a.status = ?";
    $types .= 's';
    $params[] =$filter_status;
}

$query .= " ORDER BY a.animal_id DESC";

if (!empty($params)) {$stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);$stmt->execute();
    $result =$stmt->get_result();
} else {
    $result = $conn->query($query);
}

$animals = [];
if ($result) {
    while ($row =$result->fetch_assoc()) $animals[] =$row;
}

// Fetch kennels for dropdowns & filters
$kennels =$conn->query("SELECT kennel_id, kennel_name FROM kennels WHERE kennel_name IS NOT NULL AND kennel_name != '' ORDER BY kennel_name")->fetch_all(MYSQLI_ASSOC);
$speciesList =$conn->query("SELECT DISTINCT species FROM animals WHERE species IS NOT NULL AND species != '' ORDER BY species")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Animals';$active = 'admin';
require '../includes/header.php';
?>

<div class="account">
    <aside class="side-menu" aria-label="Admin Menu">
        <a href="dashboard.php">Dashboard Overview</a>
        <a href="animals.php" class="is-active">Manage Animals</a>
        <a href="animals_add.php">Add New Animal</a>
        <a href="kennels.php">Kennels</a>
        <a href="announcements.php">Announcements</a>
        <a href="applications.php">Applications</a>
        <a href="schedules.php">Schedules</a>
        <a href="donation.php">Donation Settings</a>
        <a href="home_settings.php">Home Page Settings</a>
        <a href="about_settings.php">About Page Settings</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <div class="panel" style="max-width: 1050px; margin: 0 auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <h2>Manage Animals</h2>
                    <p class="sub" style="margin: 0;">Search, filter, and update animal records.</p>
                </div>
                <a href="animals_add.php" class="btn" style="background: var(--teal, #2a9d8f); color: #fff; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600;">+ Add Animal</a>
            </div>

            <?php if ($success): ?>
                <div style="background: #e6f4ea; color: #137333; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: #fce8e6; color: #c5221f; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Search and Filter Bar -->
            <form method="GET" style="display: flex; gap: 12px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or ID" style="padding: 9px 14px; border: 1px solid var(--line); border-radius: 6px; flex: 1; min-width: 220px; background: #fff;">
                
                <select name="species" style="padding: 9px 12px; border: 1px solid var(--line); border-radius: 6px; background: #fff;">
                    <option value="">Species</option>
                    <?php foreach ($speciesList as$s): ?>
                        <option value="<?= htmlspecialchars($s['species']) ?>" <?= $filter_species === $s['species'] ? 'selected' : '' ?>><?= htmlspecialchars($s['species']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="kennel" style="padding: 9px 12px; border: 1px solid var(--line); border-radius: 6px; background: #fff;">
                    <option value="">Kennel</option>
                    <?php foreach ($kennels as$k): ?>
                        <option value="<?= (int)$k['kennel_id'] ?>" <?= $filter_kennel == $k['kennel_id'] ? 'selected' : '' ?>>Kennel <?= htmlspecialchars($k['kennel_name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="status" style="padding: 9px 12px; border: 1px solid var(--line); border-radius: 6px; background: #fff;">
                    <option value="">Status</option>
                    <?php foreach (['Available', 'Foster', 'Pending', 'Medical', 'Adopted'] as $st): ?>
                        <option value="<?= $st ?>" <?= $filter_status === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" style="background: var(--navy); color: #fff; border: none; padding: 9px 16px; border-radius: 6px; font-weight: 600; cursor: pointer;">Filter</button>
                <?php if ($search !== '' ||$filter_species !== '' || $filter_kennel !== '' ||$filter_status !== ''): ?>
                    <a href="animals.php" style="color: #64748b; font-size: 0.9rem; text-decoration: underline;">Reset</a>
                <?php endif; ?>
            </form>

            <form method="POST" id="animalsForm">
                
                <!-- Bulk Action Toolbar -->
                <div id="bulkToolbar" style="display: none; align-items: center; gap: 12px; background: #fffbeb; border: 2px solid #fcd34d; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); flex-wrap: wrap;">
                    <div id="selectedCountText" style="font-weight: 700; color: #92400e; font-size: 0.95rem; white-space: nowrap;">0 selected</div>
                    
                    <select name="bulk_kennel" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; font-size: 0.9rem;">
                        <option value="">Move kennel</option>
                        <?php foreach ($kennels as$k): ?>
                            <option value="<?= (int)$k['kennel_id'] ?>">Kennel <?= htmlspecialchars($k['kennel_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="bulk_status" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; font-size: 0.9rem;">
                        <option value="">Set status</option>
                        <option value="Available">Available</option>
                        <option value="Foster">Foster</option>
                        <option value="Pending">Pending</option>
                        <option value="Medical">Medical</option>
                        <option value="Adopted">Adopted</option>
                    </select>

                    <select name="bulk_health" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; font-size: 0.9rem;">
                        <option value="">Health note</option>
                        <option value="Checked and healthy">Checked and healthy</option>
                        <option value="Vaccinated">Vaccinated</option>
                        <option value="Under medical treatment">Under medical treatment</option>
                        <option value="Special care required">Special care required</option>
                    </select>

                    <button type="submit" name="apply_bulk" id="applyBtn" style="background: #0d9488; color: #fff; border: none; padding: 9px 18px; border-radius: 6px; font-weight: 600; cursor: pointer; white-space: nowrap;">Apply to 0</button>
                    
                    <button type="submit" name="bulk_delete" onclick="return confirm('⚠️ Are you sure you want to PERMANENTLY delete all selected animals? This cannot be undone.');" style="background: #dc2626; color: #fff; border: none; padding: 9px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; white-space: nowrap; margin-left: auto;">Delete Selected</button>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--line); color: var(--navy);">
                                <th style="padding: 12px; width: 40px;"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                                <th style="padding: 12px;">ID</th>
                                <th style="padding: 12px;">Photo</th>
                                <th style="padding: 12px;">Name</th>
                                <th style="padding: 12px;">Species</th>
                                <th style="padding: 12px;">Kennel</th>
                                <th style="padding: 12px;">Status</th>
                                <th style="padding: 12px; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($animals)): ?>
                                <?php foreach ($animals as $animal):$aid = (int)$animal['animal_id'];$code = animalCode($aid);$photoPath = '';
                                    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                                        if (file_exists('../assets/img/animals/' . $aid . '.' . $ext)) {$photoPath = '../assets/img/animals/' . $aid . '.' .$ext;
                                            break;
                                        }
                                    }
                                ?>
                                    <tr style="border-bottom: 1px solid var(--line);">
                                        <td style="padding: 12px;"><input type="checkbox" name="selected_animals[]" value="<?= $aid ?>" class="animalCheckbox" onclick="updateToolbar()"></td>
                                        <td style="padding: 12px; font-weight: 700; color: var(--navy);"><?= htmlspecialchars($code) ?></td>
                                        <td style="padding: 12px;">
                                            <?php if ($photoPath): ?>
                                                <img src="<?= $photoPath ?>" alt="" style="width: 38px; height: 38px; object-fit: cover; border-radius: 6px;">
                                            <?php else: ?>
                                                <div style="width: 38px; height: 38px; background: #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">🐾</div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px; font-weight: 600; color: var(--navy);"><?= htmlspecialchars($animal['name']) ?></td>
                                        <td style="padding: 12px; color: #475569;"><?= htmlspecialchars($animal['species']) ?></td>
                                        <td style="padding: 12px; color: #475569;"><?= $animal['kennel_name'] ? htmlspecialchars($animal['kennel_name']) : '<span style="color: #94a3b8;">—</span>' ?></td>
                                        <td style="padding: 12px;">
                                            <span style="background: #e6f4ec; color: #2a7d4f; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;"><?= htmlspecialchars($animal['status'] ?? 'Available') ?></span>
                                        </td>
                                        <td style="padding: 12px; text-align: right; white-space: nowrap;">
                                            <a href="animals_edit.php?id=<?= $aid ?>" style="color: #0d9488; font-weight: 600; text-decoration: none; margin-right: 10px;">Edit</a>
                                            <a href="animals.php?delete=<?= $aid ?>" onclick="return confirm('Are you sure you want to delete this animal?');" style="color: #c5221f; font-weight: 600; text-decoration: none;">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="padding: 20px; text-align: center; color: var(--muted);">No animals found matching your criteria.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.animalCheckbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
    updateToolbar();
}

function updateToolbar() {
    const checkboxes = document.querySelectorAll('.animalCheckbox:checked');
    const toolbar = document.getElementById('bulkToolbar');
    const countText = document.getElementById('selectedCountText');
    const applyBtn = document.getElementById('applyBtn');
    
    const count = checkboxes.length;
    if (count > 0) {
        toolbar.style.display = 'flex';
        countText.innerText = `${count} selected`;
        applyBtn.innerText = `Apply to ${count}`;
    } else {
        toolbar.style.display = 'none';
        document.getElementById('selectAll').checked = false;
    }
}
</script>

<?php require '../includes/footer.php'; ?>