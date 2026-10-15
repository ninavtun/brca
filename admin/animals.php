<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

$statusList  = ['Available', 'Foster', 'Pending', 'Medical', 'Adopted'];
$speciesList = ['Dog', 'Cat'];
$error   = '';
$preview = null;

// Returns an error message if the kennel can't take these animals, or ''
function kennelSpaceError($conn, $kennelId, $ids)
{
    $kennelId = (int)$kennelId;
    $in = implode(',', array_map('intval', $ids));
    $k = $conn->query("SELECT kennel_name, capacity FROM kennels WHERE kennel_id = $kennelId")->fetch_assoc();
    if (!$k) return 'Please choose a valid kennel.';
    $occupied = (int)$conn->query(
        "SELECT COUNT(*) c FROM kennel_assignments WHERE kennel_id = $kennelId AND date_out IS NULL"
    )->fetch_assoc()['c'];
    $already = (int)$conn->query(
        "SELECT COUNT(*) c FROM kennel_assignments
         WHERE kennel_id = $kennelId AND date_out IS NULL AND animal_id IN ($in)"
    )->fetch_assoc()['c'];
    $moving = count($ids) - $already;
    $free   = (int)$k['capacity'] - $occupied;
    if ($moving > $free) {
        return "Kennel {$k['kennel_name']} has only $free free space(s), but $moving animal(s) would move in.";
    }
    return '';
}

$kennels = $conn->query(
    "SELECT k.kennel_id, k.kennel_name, k.capacity, COUNT(ka.assignment_id) AS occupied
     FROM kennels k
     LEFT JOIN kennel_assignments ka ON ka.kennel_id = k.kennel_id AND ka.date_out IS NULL
     GROUP BY k.kennel_id ORDER BY k.kennel_id"
)->fetch_all(MYSQLI_ASSOC);

// ---------- Bulk edit (preview, then apply) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step     = $_POST['step'] ?? '';
    $ids      = array_values(array_unique(array_filter(array_map('intval', $_POST['ids'] ?? []))));
    $kennelId = (int)($_POST['bulk_kennel'] ?? 0);
    $status   = $_POST['bulk_status'] ?? '';
    $note     = trim($_POST['bulk_note'] ?? '');

    if (!$ids) {
        $error = 'Select at least one animal first.';
    } elseif (!$kennelId && $status === '' && $note === '') {
        $error = 'Choose at least one change: a kennel, a status, or a health note.';
    } elseif ($status !== '' && !in_array($status, $statusList)) {
        $error = 'Invalid status.';
    } elseif ($kennelId) {
        $error = kennelSpaceError($conn, $kennelId, $ids);
    }

    if (!$error && $step === 'preview') {
        $in = implode(',', $ids);
        $names = $conn->query(
            "SELECT animal_id, name, species FROM animals WHERE animal_id IN ($in) ORDER BY animal_id"
        )->fetch_all(MYSQLI_ASSOC);
        $kennelName = '';
        foreach ($kennels as $k) {
            if ($k['kennel_id'] == $kennelId) $kennelName = $k['kennel_name'];
        }
        $preview = [
            'ids' => $ids,
            'animals' => $names,
            'kennelId' => $kennelId,
            'kennelName' => $kennelName,
            'status' => $status,
            'note' => $note
        ];
    }

    if (!$error && $step === 'apply') {
        $in      = implode(',', $ids);
        $adminId = (int)$_SESSION['user_id'];
        $conn->begin_transaction();
        try {
            if ($kennelId) {
                $conn->query("SELECT kennel_id FROM kennels WHERE kennel_id = $kennelId FOR UPDATE");
                $err = kennelSpaceError($conn, $kennelId, $ids);
                if ($err) throw new Exception($err);

                $cur   = $conn->prepare("SELECT assignment_id, kennel_id FROM kennel_assignments
                                         WHERE animal_id = ? AND date_out IS NULL FOR UPDATE");
                $close = $conn->prepare("UPDATE kennel_assignments SET date_out = NOW() WHERE assignment_id = ?");
                $ins   = $conn->prepare("INSERT INTO kennel_assignments (animal_id, kennel_id, moved_by) VALUES (?,?,?)");
                foreach ($ids as $id) {
                    $cur->bind_param("i", $id);
                    $cur->execute();
                    $row = $cur->get_result()->fetch_assoc();
                    if ($row && $row['kennel_id'] == $kennelId) continue; // already there
                    if ($row) {
                        $close->bind_param("i", $row['assignment_id']);
                        $close->execute();
                    }
                    $ins->bind_param("iii", $id, $kennelId, $adminId);
                    $ins->execute();
                }
            }
            if ($status !== '') {
                $stmt = $conn->prepare("UPDATE animals SET status = ? WHERE animal_id IN ($in)");
                $stmt->bind_param("s", $status);
                $stmt->execute();
            }
            if ($note !== '') {
                $stmt = $conn->prepare("INSERT INTO health_records (animal_id, record_date, note) VALUES (?, CURDATE(), ?)");
                foreach ($ids as $id) {
                    $stmt->bind_param("is", $id, $note);
                    $stmt->execute();
                }
            }
            $conn->commit();
            header('Location: animals.php?updated=' . count($ids));
            exit;
        } catch (Throwable $ex) {
            $conn->rollback();
            $error = $ex->getMessage();
        }
    }
}

// ---------- Search and filters ----------
$q        = trim($_GET['q'] ?? '');
$fSpecies = in_array($_GET['species'] ?? '', $speciesList) ? $_GET['species'] : '';
$fStatus  = in_array($_GET['status'] ?? '', $statusList) ? $_GET['status'] : '';
$fKennel  = (int)($_GET['kennel'] ?? 0);

$where = [];
$types = '';
$params = [];
if ($q !== '') {
    $idNum = preg_match('/^(brca-?)?0*(\d+)$/i', $q, $m) ? (int)$m[2] : 0;
    $where[] = "(a.name LIKE ? OR a.animal_id = ?)";
    $types .= 'si';
    $params[] = '%' . $q . '%';
    $params[] = $idNum;
}
if ($fSpecies !== '') {
    $where[] = "a.species = ?";
    $types .= 's';
    $params[] = $fSpecies;
}
if ($fStatus !== '') {
    $where[] = "a.status = ?";
    $types .= 's';
    $params[] = $fStatus;
}
if ($fKennel) {
    $where[] = "k.kennel_id = ?";
    $types .= 'i';
    $params[] = $fKennel;
}

$sql = "SELECT a.animal_id, a.name, a.species, a.status, a.photo, a.is_published, k.kennel_name
        FROM animals a
        LEFT JOIN kennel_assignments ka ON ka.animal_id = a.animal_id AND ka.date_out IS NULL
        LEFT JOIN kennels k ON k.kennel_id = ka.kennel_id"
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . " ORDER BY a.animal_id DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$animals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Animals';
$active    = 'admin';
require '../includes/header.php';
?>

<div class="account">
    <aside class="side-menu" aria-label="Admin Menu">
        <a href="dashboard.php">Dashboard Overview</a>
        <a href="animals.php" class="is-active">Manage Animals</a>
        <a href="animals_add.php">Add New Animal</a>
        <a href="kennels.php">Kennels</a>
        <a href="announcements.php">Announcements</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <?php if ($preview): ?>
            <!-- Confirmation step -->
            <div class="panel">
                <h2>Confirm bulk update</h2>
                <p class="sub"><b><?= count($preview['ids']) ?></b> animal(s) will be changed:</p>
                <ul>
                    <?php if ($preview['kennelId']): ?>
                        <li>Move to kennel <b><?= e($preview['kennelName']) ?></b></li>
                    <?php endif; ?>
                    <?php if ($preview['status'] !== ''): ?>
                        <li>Set status to <b><?= e($preview['status']) ?></b></li>
                    <?php endif; ?>
                    <?php if ($preview['note'] !== ''): ?>
                        <li>Add health note: <b><?= e($preview['note']) ?></b></li>
                    <?php endif; ?>
                </ul>
                <p>
                    <?php foreach ($preview['animals'] as $a): ?>
                        <?= e($a['name']) ?> (<?= e(animalCode($a['animal_id'])) ?>)<br>
                    <?php endforeach; ?>
                </p>
                <form method="post" style="display:flex; gap:10px; margin-top:12px">
                    <input type="hidden" name="step" value="apply">
                    <?php foreach ($preview['ids'] as $id): ?>
                        <input type="hidden" name="ids[]" value="<?= (int)$id ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="bulk_kennel" value="<?= (int)$preview['kennelId'] ?>">
                    <input type="hidden" name="bulk_status" value="<?= e($preview['status']) ?>">
                    <input type="hidden" name="bulk_note" value="<?= e($preview['note']) ?>">
                    <button class="btn btn-auto" type="submit">Confirm and save</button>
                    <a class="btn btn-auto btn-outline" href="animals.php">Cancel</a>
                </form>
            </div>

        <?php else: ?>
            <div class="panel">
                <h2>Manage Animals</h2>
                <p class="sub">Search, filter, and update animal records. Tick several animals to edit them at once.</p>

                <?php if (isset($_GET['updated'])): ?>
                    <p style="color:#2a7d4f"><b><?= (int)$_GET['updated'] ?> animal(s) updated.</b></p>
                <?php endif; ?>
                <?php if ($error): ?>
                    <p style="color:#c0392b"><b><?= e($error) ?></b></p>
                <?php endif; ?>

                <form method="get" style="display:flex; gap:8px; flex-wrap:wrap; margin:12px 0">
                    <input name="q" value="<?= e($q) ?>" placeholder="Search by name or ID" style="flex:2; min-width:160px">
                    <select name="species" style="flex:1; min-width:110px">
                        <option value="">Species</option>
                        <?php foreach ($speciesList as $s): ?>
                            <option <?= $fSpecies === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="kennel" style="flex:1; min-width:110px">
                        <option value="">Kennel</option>
                        <?php foreach ($kennels as $k): ?>
                            <option value="<?= (int)$k['kennel_id'] ?>" <?= $fKennel == $k['kennel_id'] ? 'selected' : '' ?>>
                                <?= e($k['kennel_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" style="flex:1; min-width:110px">
                        <option value="">Status</option>
                        <?php foreach ($statusList as $s): ?>
                            <option <?= $fStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-auto" type="submit">Filter</button>
                    <a class="btn btn-auto btn-outline" href="animals.php">Reset</a>
                </form>

                <form method="post">
                    <input type="hidden" name="step" value="preview">

                    <div style="background:#fef5e0; border:1px solid #e6b800; border-radius:10px; padding:10px; margin-bottom:12px">
                        <b><span id="selCount">0</span> selected</b>
                        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px">
                            <select name="bulk_kennel" style="flex:1; min-width:130px">
                                <option value="">Move to kennel...</option>
                                <?php foreach ($kennels as $k): ?>
                                    <option value="<?= (int)$k['kennel_id'] ?>">
                                        <?= e($k['kennel_name']) ?> (<?= (int)$k['occupied'] ?>/<?= (int)$k['capacity'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="bulk_status" style="flex:1; min-width:130px">
                                <option value="">Set status...</option>
                                <?php foreach ($statusList as $s): ?>
                                    <option><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input name="bulk_note" placeholder="Health note (all selected)" style="flex:2; min-width:160px">
                            <button class="btn btn-auto" type="submit">Review changes</button>
                        </div>
                    </div>

                    <div style="overflow-x:auto">
                        <table style="width:100%; border-collapse:collapse">
                            <tr style="text-align:left">
                                <th><input type="checkbox" id="selectAll" style="width:auto"></th>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Species</th>
                                <th>Kennel</th>
                                <th>Status</th>
                                <th>Published</th>
                            </tr>
                            <?php foreach ($animals as $a): ?>
                                <tr style="border-top:1px solid #dde3ea">
                                    <td><input type="checkbox" name="ids[]" value="<?= (int)$a['animal_id'] ?>" style="width:auto"></td>
                                    <td><?= e(animalCode($a['animal_id'])) ?></td>
                                    <td><?php if ($a['photo']): ?><img src="../uploads/<?= e($a['photo']) ?>" width="50"><?php endif; ?></td>
                                    <td><?= e($a['name']) ?></td>
                                    <td><?= e($a['species']) ?></td>
                                    <td><?= e($a['kennel_name'] ?? '-') ?></td>
                                    <td><?= e($a['status']) ?></td>
                                    <td><?= $a['is_published'] ? 'Yes' : 'No' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <?php if (!$animals): ?><p>No animals match your search.</p><?php endif; ?>
                    <p class="sub">Showing <?= count($animals) ?> animal(s).</p>
                </form>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
    (function() {
        var all = document.getElementById('selectAll');
        if (!all) return;

        function update() {
            document.getElementById('selCount').textContent =
                document.querySelectorAll('input[name="ids[]"]:checked').length;
        }
        all.addEventListener('change', function() {
            document.querySelectorAll('input[name="ids[]"]').forEach(function(c) {
                c.checked = all.checked;
            });
            update();
        });
        document.querySelectorAll('input[name="ids[]"]').forEach(function(c) {
            c.addEventListener('change', update);
        });
    })();
</script>

<?php require '../includes/footer.php'; ?>