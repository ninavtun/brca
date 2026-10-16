<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

// Ensure user is an admin
if (empty($_SESSION['user_id']) OR !isset($_SESSION['role']) OR$_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$error = '';

// Check columns and create table structures if needed
$conn->query("ALTER TABLE animals ADD COLUMN IF NOT EXISTS intake_source VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE animals ADD COLUMN IF NOT EXISTS estimated_age VARCHAR(50) DEFAULT NULL");
$conn->query("ALTER TABLE animals ADD COLUMN IF NOT EXISTS dob DATE DEFAULT NULL");
$conn->query("ALTER TABLE animals ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL");

// Handle AJAX duplicate check request
if (isset($_GET['check_name'])) {$n = trim($_GET['check_name']);$response = [];
    if ($n !== '') {
        $stmt =$conn->prepare("
            SELECT a.animal_id, a.name, a.species, a.status, k.kennel_name 
            FROM animals a 
            LEFT JOIN kennel_assignments ka ON ka.animal_id = a.animal_id AND ka.date_out IS NULL
            LEFT JOIN kennels k ON k.kennel_id = ka.kennel_id
            WHERE LOWER(a.name) = LOWER(?)
        ");
        $stmt->bind_param("s", $n);$stmt->execute();
        $res =$stmt->get_result();
        while ($row = $res->fetch_assoc()) {$response[] = [
                'id' => animalCode($row['animal_id']),
                'name' => $row['name'],
                'species' => $row['species'] ?? 'Animal',
                'status' => $row['status'] ?? 'Available',
                'kennel' => $row['kennel_name'] ? 'Kennel ' .$row['kennel_name'] : 'No kennel assigned'
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $species = trim($_POST['species'] ?? '');
    $breed = trim($_POST['breed'] ?? '');
    $estimated_age = trim($_POST['estimated_age'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $sex = trim($_POST['sex'] ?? 'Unknown');
    $health_condition = trim($_POST['health_condition'] ?? '');
    $intake_date = trim($_POST['intake_date'] ?? date('Y-m-d'));
    $intake_source = trim($_POST['intake_source'] ?? '');$description = trim($_POST['description'] ?? '');$assign_kennel = (int)($_POST['assign_kennel'] ?? 0);$status = 'Available';

    if ($name === '' OR $species === '') {$error = "Name and species are required fields.";
    } else {
        $conn->begin_transaction();
        try {
            $stmt =$conn->prepare("INSERT INTO animals (name, species, status, breed, estimated_age, dob, sex, health_condition, intake_date, intake_source, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $dob_val = ($dob !== '') ?$dob : null;
            $stmt->bind_param("sssssssssss", $name,$species, $status,$breed, $estimated_age,$dob_val, $sex,$health_condition, $intake_date,$intake_source, $description);$stmt->execute();
            $newId =$conn->insert_id;

            if ($assign_kennel > 0) {
                $adminId = (int)$_SESSION['user_id'];
                $assignStmt =$conn->prepare("INSERT INTO kennel_assignments (animal_id, kennel_id, moved_by, date_in) VALUES (?, ?, ?, NOW())");
                $assignStmt->bind_param("iii", $newId,$assign_kennel, $adminId);$assignStmt->execute();
            }

            if (isset($_FILES['photo']) AND$_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath =$_FILES['photo']['tmp_name'];
                $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'webp'])) {$uploadFileDir = '../assets/img/animals/';
                    if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0755, true);
                    move_uploaded_file($fileTmpPath,$uploadFileDir . $newId . '.' .$fileExtension);
                }
            }

            $conn->commit();
            header('Location: animals.php?added=1');
            exit;
        } catch (Throwable $ex) {
            $conn->rollback();$error = "Error saving animal: " . $ex->getMessage();
        }
    }
}

$kennels =$conn->query("SELECT kennel_id, kennel_name FROM kennels WHERE kennel_name IS NOT NULL AND kennel_name != '' ORDER BY kennel_name")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Add New Animal';$active = 'admin';
require '../includes/header.php';
?>

<div class="account">
    <aside class="side-menu" aria-label="Admin Menu">
        <a href="dashboard.php">Dashboard Overview</a>
        <a href="animals.php">Manage Animals</a>
        <a href="animals_add.php" class="is-active">Add New Animal</a>
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
        <div style="display: flex; gap: 24px; align-items: flex-start;">
            
            <div class="panel" style="flex: 1; margin: 0;">
                <h2>Add New Animal</h2>
                <p class="sub">The animal ID is generated automatically when you save.</p>

                <?php if ($error): ?>
                    <div style="background: #fce8e6; color: #c5221f; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="addAnimalForm" style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <div style="display: inline-block; background: #eef2f6; color: #1e3a5f; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; border: 1px solid #cbd5e1;">
                            ID: auto-generated on save
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Name</label>
                        <input type="text" name="name" id="animalNameInput" required autocomplete="off" style="width: 100%; padding: 10px; border: 1px solid #d9dfe9; border-radius: 8px;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Species</label>
                            <select name="species" required style="width: 100%; padding: 10px; border: 1px solid #d9dfe9; border-radius: 8px; background: #fff;">
                                <option value="">Species</option>
                                <option value="Cat">Cat</option>
                                <option value="Dog">Dog</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Breed</label>
                            <input type="text" name="breed" placeholder="Breed" style="width: 100%; padding: 10px; border: 1px solid #d9dfe9; border-radius: 8px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Estimated age</label>
                            <input type="text" name="estimated_age" placeholder="Estimated age" style="width: 100%; padding: 10px; border: 1px solid #d9dfe9; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Date of Birth</label>
                            <input type="date" name="dob" style="width: 100%; padding: 10px; border: 1px solid #d9dfe9; border-radius: 8px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Sex</label>
                            <select name="sex" style="width: 100%; padding: 10px; border: 1px solid #d9dfe9; border-radius: 8px; background: #fff;">
                                <option value="">Sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Unknown">Unknown</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Health condition</label>
                            <select name="health_condition" style="width: 100%; padding: 10px; border: 1px solid #d9dfe9; border-radius: 8px; background: #fff;">
                                <option value="">Health condition</option>
                                <option value="Checked and healthy">Checked and healthy</option>
                                <option value="Vaccinated">Vaccinated</option>
                                <option value="Under medical treatment">Under medical treatment</option>
                                <option value="Special care required">Special care required</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Intake date</label>
                            <input type="date" name="intake_date" value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 10px; border: 1px solid #d9dfe9; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Intake source</label>
                            <select name="intake_source" style="width: 100%; padding: 10px; border: 1px solid #d9dfe9; border-radius: 8px; background: #fff;">
                                <option value="">Source</option>
                                <option value="Surrender">Surrender</option>
                                <option value="Rescue">Rescue</option>
                                <option value="Stray">Stray</option>
                                <option value="Transfer">Transfer</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Assign kennel</label>
                        <select name="assign_kennel" style="width: 100%; padding: 10px; border: 1px solid #d9dfe9; border-radius: 8px; background: #fff;">
                            <option value="">Select kennel</option>
                            <?php foreach ($kennels as$k): ?>
                                <option value="<?= (int)$k['kennel_id'] ?>">Kennel <?= htmlspecialchars($k['kennel_name']) ?> (<?= $k['occupied'] ?>/<?=$k['capacity'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Description / Notes</label>
                        <textarea name="description" rows="4" placeholder="Enter animal bio, personality, or rescue background..." style="width: 100%; padding: 10px; border: 1px solid #d9dfe9; border-radius: 8px;"></textarea>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem;">Photo</label>
                        <div style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 25px; text-align: center; background: #f8fafc; position: relative; cursor: pointer;">
                            <span style="color: #64748b; font-size: 0.9rem;">Drop a photo here or click to upload</span>
                            <input type="file" name="photo" accept="image/*" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 10px;">
                        <button type="submit" class="btn" style="flex: 1; background: #2a9d8f; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; text-align: center;">Save Animal</button>
                        <a href="animals.php" class="btn" style="flex: 1; background: #fff; color: #334155; border: 1px solid #d9dfe9; padding: 12px; border-radius: 8px; font-weight: 600; text-decoration: none; text-align: center;">Cancel</a>
                    </div>
                </form>
            </div>

            <div id="duplicateWarningPanel" style="display: none; width: 340px; background: #fdf2f2; border: 2px solid #f87171; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 8px; color: #b91c1c; font-weight: 700; margin-bottom: 8px; font-size: 1.05rem;">
                    <span>⚠️</span> Similar name found
                </div>
                <p id="duplicateMessageCount" style="color: #7f1d1d; font-size: 0.85rem; margin-bottom: 14px; line-height: 1.4;"></p>
                
                <div id="duplicateListContainer" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; max-height: 320px; overflow-y: auto;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button type="button" onclick="document.getElementById('addAnimalForm').submit();" style="background: #2a9d8f; color: #fff; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%;">Create as New Animal</button>
                    <button type="button" onclick="hideDuplicatePanel();" style="background: #fff; color: #334155; border: 1px solid #cbd5e1; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%;">Cancel</button>
                </div>
            </div>

        </div>
    </section>
</div>

<script>
const nameInput = document.getElementById('animalNameInput');
const warningPanel = document.getElementById('duplicateWarningPanel');
const msgCount = document.getElementById('duplicateMessageCount');
const listContainer = document.getElementById('duplicateListContainer');

let typingTimer;
nameInput.addEventListener('input', function() {
    clearTimeout(typingTimer);
    const val = this.value.trim();
    if (val.length < 2) {
        hideDuplicatePanel();
        return;
    }

    typingTimer = setTimeout(() => {
        fetch('animals_add.php?check_name=' + encodeURIComponent(val))
            .then(res => res.json())
            .then(data => {
                if (data && data.length > 0) {
                    showDuplicates(val, data);
                } else {
                    hideDuplicatePanel();
                }
            });
    }, 300);
});

function showDuplicates(name, items) {
    nameInput.style.borderColor = '#f87171';
    msgCount.innerText = `${items.length} animal${items.length > 1 ? 's' : ''} named "${name}" already exist. Check first:`;
    
    listContainer.innerHTML = '';
    items.forEach(item => {
        const card = document.createElement('div');
        card.style.cssText = 'background: #fff; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px; display: flex; gap: 12px; align-items: center;';
        card.innerHTML = `
            <div style="width: 40px; height: 40px; background: #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">🐾</div>
            <div style="flex: 1; font-size: 0.85rem;">
                <div style="font-weight: 700; color: #1e3a5f;">${item.name} &middot; ${item.species}</div>
                <div style="color: #64748b; font-weight: 600; margin: 2px 0; font-size: 0.8rem;">${item.id}</div>
                <div style="color: #475569; font-size: 0.75rem;">${item.kennel} &middot; ${item.status}</div>
                <a href="animals_edit.php?id=${item.id.replace('BRCA-', '')}" target="_blank" style="color: #2a9d8f; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 4px;">View details &rarr;</a>
            </div>
        `;
        listContainer.appendChild(card);
    });

    warningPanel.style.display = 'block';
}

function hideDuplicatePanel() {
    nameInput.style.borderColor = '#d9dfe9';
    warningPanel.style.display = 'none';
}
</script>

<?php require '../includes/footer.php'; ?>