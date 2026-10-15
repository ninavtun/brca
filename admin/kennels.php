<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

$error = '';

// ---- Transfer an animal ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $animalId  = (int)($_POST['animal_id'] ?? 0);
    $newKennel = (int)($_POST['kennel_id'] ?? 0);
    $adminId   = (int)$_SESSION['user_id'];

    $conn->begin_transaction();
    try {
        // Current kennel of this animal
        $stmt = $conn->prepare(
            "SELECT assignment_id, kennel_id FROM kennel_assignments
             WHERE animal_id = ? AND date_out IS NULL FOR UPDATE"
        );
        $stmt->bind_param("i", $animalId);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();

        // Target kennel and how full it is
        $stmt = $conn->prepare(
            "SELECT k.kennel_name, k.capacity,
                    (SELECT COUNT(*) FROM kennel_assignments
                     WHERE kennel_id = k.kennel_id AND date_out IS NULL) AS occupied
             FROM kennels k WHERE k.kennel_id = ?"
        );
        $stmt->bind_param("i", $newKennel);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();

        if (!$target) {
            throw new Exception('Please choose a kennel.');
        }
        if ($current && $current['kennel_id'] == $newKennel) {
            throw new Exception('The animal is already in that kennel.');
        }
        if ($target['occupied'] >= $target['capacity']) {
            throw new Exception('Kennel ' . $target['kennel_name'] . ' is full.');
        }

        if ($current) {
            $stmt = $conn->prepare("UPDATE kennel_assignments SET date_out = NOW() WHERE assignment_id = ?");
            $stmt->bind_param("i", $current['assignment_id']);
            $stmt->execute();
        }
        $stmt = $conn->prepare("INSERT INTO kennel_assignments (animal_id, kennel_id, moved_by) VALUES (?,?,?)");
        $stmt->bind_param("iii", $animalId, $newKennel, $adminId);
        $stmt->execute();

        $conn->commit();
        header('Location: kennels.php?kennel=' . $newKennel . '&moved=1');
        exit;
    } catch (Throwable $ex) {
        $conn->rollback();
        $error = $ex->getMessage();
    }
}

// ---- Data for the page ----
$kennels = $conn->query(
    "SELECT k.kennel_id, k.kennel_name, k.section, k.capacity,
            COUNT(ka.assignment_id) AS occupied
     FROM kennels k
     LEFT JOIN kennel_assignments ka ON ka.kennel_id = k.kennel_id AND ka.date_out IS NULL
     GROUP BY k.kennel_id ORDER BY k.kennel_id"
)->fetch_all(MYSQLI_ASSOC);

$selectedId = (int)($_GET['kennel'] ?? ($kennels[0]['kennel_id'] ?? 0));
$selected = null;
foreach ($kennels as $k) {
    if ($k['kennel_id'] == $selectedId) $selected = $k;
}

$inKennel = [];
if ($selected) {
    $stmt = $conn->prepare(
        "SELECT a.animal_id, a.name, a.species, a.status
         FROM kennel_assignments ka JOIN animals a ON a.animal_id = ka.animal_id
         WHERE ka.kennel_id = ? AND ka.date_out IS NULL ORDER BY a.name"
    );
    $stmt->bind_param("i", $selectedId);
    $stmt->execute();
    $inKennel = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$history = $conn->query(
    "SELECT ka.date_in, a.animal_id, a.name, k.kennel_name AS to_kennel,
            (SELECT k2.kennel_name FROM kennel_assignments p
             JOIN kennels k2 ON k2.kennel_id = p.kennel_id
             WHERE p.animal_id = ka.animal_id AND p.assignment_id < ka.assignment_id
             ORDER BY p.assignment_id DESC LIMIT 1) AS from_kennel,
            CONCAT(u.first_name, ' ', u.last_name) AS moved_by
     FROM kennel_assignments ka
     JOIN animals a ON a.animal_id = ka.animal_id
     JOIN kennels k ON k.kennel_id = ka.kennel_id
     LEFT JOIN users u ON u.user_id = ka.moved_by
     ORDER BY ka.assignment_id DESC LIMIT 50"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Kennels';
$active    = 'admin';
require '../includes/header.php';
?>

<div class="account">
    <!-- Updated Admin Sidebar -->
    <aside class="side-menu" aria-label="Admin Menu">
        <a href="dashboard.php">Dashboard Overview</a>
        <a href="animals.php">Manage Animals</a>
        <a href="animals_add.php">Add New Animal</a>
        <a href="kennels.php" class="is-active">Kennels</a>
        <a href="applications.php">Applications</a>
        <a href="schedules.php">Schedules</a>
	<a href="donation.php">Donation Settings</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <div class="panel">
            <h2>Kennels</h2>
            <p class="sub">See which animals are in which kennel. Click a kennel to view and move its animals.</p>

            <?php if (isset($_GET['moved'])): ?>
                <p style="color:#2a7d4f"><b>Transfer saved.</b></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p style="color:#c0392b"><b><?= e($error) ?></b></p>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); gap:10px; margin-top:12px">
                <?php foreach ($kennels as $k):
                    $full = $k['occupied'] >= $k['capacity'];
                    $half = $k['occupied'] >= $k['capacity'] / 2;
                    $color = $full ? '#c0392b' : ($half ? '#b9770e' : '#2a7d4f');
                    $bg    = $full ? '#fdecea' : ($half ? '#fef5e0' : '#e6f4ec');
                    $sel   = $k['kennel_id'] == $selectedId ? 'outline:3px solid #1e3a5f;' : '';
                ?>
                    <a href="kennels.php?kennel=<?= (int)$k['kennel_id'] ?>"
                        style="display:block; padding:10px; border:2px solid <?= $color ?>; background:<?= $bg ?>; border-radius:10px; text-decoration:none; color:<?= $color ?>; <?= $sel ?>">
                        <b><?= e($k['kennel_name']) ?></b><br>
                        <?= (int)$k['occupied'] ?> / <?= (int)$k['capacity'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($selected): ?>
            <div class="panel">
                <h2>Kennel <?= e($selected['kennel_name']) ?></h2>
                <p class="sub"><?= e($selected['section']) ?> &middot; <?= (int)$selected['occupied'] ?> / <?= (int)$selected['capacity'] ?> occupied</p>

                <?php if (!$inKennel): ?>
                    <p>This kennel is empty.</p>
                <?php else: ?>
                    <table style="width:100%; border-collapse:collapse">
                        <tr style="text-align:left">
                            <th>ID</th>
                            <th>Name</th>
                            <th>Species</th>
                            <th>Status</th>
                            <th>Move to</th>
                        </tr>
                        <?php foreach ($inKennel as $a): ?>
                            <tr style="border-top:1px solid #dde3ea">
                                <td><?= e(animalCode($a['animal_id'])) ?></td>
                                <td><?= e($a['name']) ?></td>
                                <td><?= e($a['species']) ?></td>
                                <td><?= e($a['status']) ?></td>
                                <td>
                                    <form method="post" style="display:flex; gap:6px">
                                        <input type="hidden" name="animal_id" value="<?= (int)$a['animal_id'] ?>">
                                        <select name="kennel_id" required>
                                            <option value="">Kennel...</option>
                                            <?php foreach ($kennels as $k):
                                                if ($k['kennel_id'] == $selectedId) continue; ?>
                                                <option value="<?= (int)$k['kennel_id'] ?>" <?= $k['occupied'] >= $k['capacity'] ? 'disabled' : '' ?>>
                                                    <?= e($k['kennel_name']) ?> (<?= (int)$k['occupied'] ?>/<?= (int)$k['capacity'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-auto" type="submit">Move</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="panel">
            <h2>Transfer History</h2>
            <p class="sub">The last 50 kennel placements and moves.</p>
            <div style="overflow-x:auto">
                <table style="width:100%; border-collapse:collapse">
                    <tr style="text-align:left">
                        <th>Date</th>
                        <th>Animal</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Moved by</th>
                    </tr>
                    <?php foreach ($history as $h): ?>
                        <tr style="border-top:1px solid #dde3ea">
                            <td><?= e(date('d M Y, H:i', strtotime($h['date_in']))) ?></td>
                            <td><?= e($h['name']) ?> &middot; <?= e(animalCode($h['animal_id'])) ?></td>
                            <td><?= e($h['from_kennel'] ?? 'Intake') ?></td>
                            <td><?= e($h['to_kennel']) ?></td>
                            <td><?= e($h['moved_by'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </section>
</div>

<?php require '../includes/footer.php'; ?>