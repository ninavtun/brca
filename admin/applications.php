<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

// Ensure user is an admin
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

// Dynamically fetch the exact primary key column name for adoption_foster_apps
$pk_col = 'id';
$pk_result = $conn->query("SHOW KEYS FROM adoption_foster_apps WHERE Key_name = 'PRIMARY'");
if ($pk_result && $row = $pk_result->fetch_assoc()) {
    $pk_col = $row['Column_name'];
}

// Handle status updates for Adoption/Foster apps if submitted
if (isset($_POST['update_adoption_status'])) {
    $appId = (int)$_POST['app_id'];
    $newStatus = trim($_POST['status']);
    if (in_array($newStatus, array('Pending', 'Approved', 'Rejected'))) {
        $stmt = $conn->prepare("UPDATE adoption_foster_apps SET status = ? WHERE {$pk_col} = ?");
        $stmt->bind_param("si", $newStatus, $appId);
        if ($stmt->execute()) {
            $success = "Adoption application status updated successfully.";
        } else {
            $error = "Failed to update status.";
        }
    }
}

// Handle status updates for Volunteer apps if submitted
if (isset($_POST['update_volunteer_status'])) {
    $appId = (int)$_POST['app_id'];
    $newStatus = trim($_POST['status']);
    if (in_array($newStatus, array('Pending', 'Approved', 'Rejected'))) {
        $stmt = $conn->prepare("UPDATE volunteer_applications SET status = ? WHERE application_id = ?");
        $stmt->bind_param("si", $newStatus, $appId);
        if ($stmt->execute()) {
            $success = "Volunteer application status updated successfully.";
        } else {
            $error = "Failed to update status.";
        }
    }
}

// Active Tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'adoption';

// --- Adoption & Foster Filters ---
$q_adopt = trim(isset($_GET['q_adopt']) ? $_GET['q_adopt'] : '');
$status_adopt = trim(isset($_GET['status_adopt']) ? $_GET['status_adopt'] : '');
$date_adopt = trim(isset($_GET['date_adopt']) ? $_GET['date_adopt'] : '');

$where_adopt = array();
$params_adopt = array();
$types_adopt = '';

if ($q_adopt !== '') {
    $where_adopt[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR a.name LIKE ?)";
    $searchTerm = "%{$q_adopt}%";
    $params_adopt[] = $searchTerm;
    $params_adopt[] = $searchTerm;
    $params_adopt[] = $searchTerm;
    $params_adopt[] = $searchTerm;
    $types_adopt .= 'ssss';
}
if ($status_adopt !== '') {
    $where_adopt[] = "afa.status = ?";
    $params_adopt[] = $status_adopt;
    $types_adopt .= 's';
}
if ($date_adopt !== '') {
    $searchDateVal = $date_adopt;
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_adopt, $matches)) {
        $searchDateVal = $matches[3] . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT);
    }
    
    $where_adopt[] = "(
        afa.application_date LIKE ? OR 
        DATE_FORMAT(afa.application_date, '%Y-%m-%d') LIKE ? OR
        DATE_FORMAT(afa.application_date, '%Y') LIKE ? OR
        DATE_FORMAT(afa.application_date, '%m') LIKE ? OR
        DATE_FORMAT(afa.application_date, '%M') LIKE ? OR
        DATE_FORMAT(afa.application_date, '%b') LIKE ?
    )";
    $dateSearch = "%{$searchDateVal}%";
    $params_adopt[] = $dateSearch;
    $params_adopt[] = $dateSearch;
    $params_adopt[] = $dateSearch;
    $params_adopt[] = $dateSearch;
    $params_adopt[] = $dateSearch;
    $params_adopt[] = $dateSearch;
    $types_adopt .= 'ssssss';
}

$sql_adopt = "SELECT afa.*, u.first_name, u.last_name, a.name AS animal_name, a.animal_id 
              FROM adoption_foster_apps afa
              JOIN users u ON afa.user_id = u.user_id
              JOIN animals a ON afa.animal_id = a.animal_id";
if (!empty($where_adopt)) {
    $sql_adopt .= " WHERE " . implode(" AND ", $where_adopt);
}
$sql_adopt .= " ORDER BY afa.application_date DESC";

$stmt = $conn->prepare($sql_adopt);
if (!empty($params_adopt)) {
    $stmt->bind_param($types_adopt, ...$params_adopt);
}
$stmt->execute();
$adoption_apps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


// --- Volunteer Filters ---
$q_vol = trim(isset($_GET['q_vol']) ? $_GET['q_vol'] : '');
$status_vol = trim(isset($_GET['status_vol']) ? $_GET['status_vol'] : '');
$date_vol = trim(isset($_GET['date_vol']) ? $_GET['date_vol'] : '');

$where_vol = array();
$params_vol = array();
$types_vol = '';

if ($q_vol !== '') {
    $where_vol[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
    $searchTermVol = "%{$q_vol}%";
    $params_vol[] = $searchTermVol;
    $params_vol[] = $searchTermVol;
    $params_vol[] = $searchTermVol;
    $types_vol .= 'sss';
}
if ($status_vol !== '') {
    $where_vol[] = "va.status = ?";
    $params_vol[] = $status_vol;
    $types_vol .= 's';
}
if ($date_vol !== '') {
    $searchDateVolVal = $date_vol;
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_vol, $matches)) {
        $searchDateVolVal = $matches[3] . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT);
    }

    $where_vol[] = "(
        va.application_date LIKE ? OR 
        DATE_FORMAT(va.application_date, '%Y-%m-%d') LIKE ? OR
        DATE_FORMAT(va.application_date, '%Y') LIKE ? OR
        DATE_FORMAT(va.application_date, '%m') LIKE ? OR
        DATE_FORMAT(va.application_date, '%M') LIKE ? OR
        DATE_FORMAT(va.application_date, '%b') LIKE ?
    )";
    $dateSearchVol = "%{$searchDateVolVal}%";
    $params_vol[] = $dateSearchVol;
    $params_vol[] = $dateSearchVol;
    $params_vol[] = $dateSearchVol;
    $params_vol[] = $dateSearchVol;
    $params_vol[] = $dateSearchVol;
    $params_vol[] = $dateSearchVol;
    $types_vol .= 'ssssss';
}

$sql_vol = "SELECT va.*, u.first_name, u.last_name 
            FROM volunteer_applications va
            JOIN users u ON va.user_id = u.user_id";
if (!empty($where_vol)) {
    $sql_vol .= " WHERE " . implode(" AND ", $where_vol);
}
$sql_vol .= " ORDER BY va.application_date DESC";

$stmt_vol = $conn->prepare($sql_vol);
if (!empty($params_vol)) {
    $stmt_vol->bind_param($types_vol, ...$params_vol);
}
$stmt_vol->execute();
$volunteer_apps = $stmt_vol->get_result()->fetch_all(MYSQLI_ASSOC);

// Dynamically fetch exact primary key column for volunteer_applications as well
$pk_vol_col = 'application_id';
$pk_vol_result = $conn->query("SHOW KEYS FROM volunteer_applications WHERE Key_name = 'PRIMARY'");
if ($pk_vol_result && $row_v = $pk_vol_result->fetch_assoc()) {
    $pk_vol_col = $row_v['Column_name'];
}

$pageTitle = 'Applications';
$active = 'admin';
require '../includes/header.php';
?>

<div class="account">
    <aside class="side-menu" aria-label="Admin Menu">
        <a href="dashboard.php">Dashboard Overview</a>
        <a href="animals.php">Manage Animals</a>
        <a href="animals_add.php">Add New Animal</a>
        <a href="kennels.php">Kennels</a>
        <a href="announcements.php">Announcements</a>
        <a href="applications.php" class="is-active">Applications</a>
        <a href="schedules.php">Schedules</a>
	<a href="home_settings.php">Home Page Settings</a>
	<a href="about_settings.php">About Page Settings</a>
        <a href="donation.php">Donation Settings</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <div class="panel">
            <h2>Applications</h2>
            <p class="sub">Review adoption, foster, and volunteer requests.</p>

            <?php if ($success): ?>
                <p style="color:#2a7d4f; font-weight:600; margin-bottom:12px;"><?php echo $success; ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p style="color:#c0392b; font-weight:600; margin-bottom:12px;"><?php echo $error; ?></p>
            <?php endif; ?>

            <!-- Navigation Tabs -->
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <a href="applications.php?tab=adoption" class="btn btn-auto <?php echo $tab === 'adoption' ? '' : 'btn-outline'; ?>" style="<?php echo $tab === 'adoption' ? 'background:var(--teal, #2a9d8f); color:#fff; border:none;' : ''; ?>">Adoption & Foster</a>
                <a href="applications.php?tab=volunteer" class="btn btn-auto <?php echo $tab === 'volunteer' ? '' : 'btn-outline'; ?>" style="<?php echo $tab === 'volunteer' ? 'background:var(--teal, #2a9d8f); color:#fff; border:none;' : ''; ?>">Volunteer</a>
            </div>

            <?php if ($tab === 'adoption'): ?>
                <!-- Adoption & Foster Search & Filter Form -->
                <form method="get" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px;">
                    <input type="hidden" name="tab" value="adoption">
                    <input name="q_adopt" value="<?php echo htmlspecialchars($q_adopt); ?>" placeholder="Search applicant or animal" style="flex:2; min-width:180px; padding:8px; border:1px solid var(--line); border-radius:6px;">
                    <select name="status_adopt" style="flex:1; min-width:120px; padding:8px; border:1px solid var(--line); border-radius:6px;">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $status_adopt === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $status_adopt === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $status_adopt === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                    <input type="text" name="date_adopt" value="<?php echo htmlspecialchars($date_adopt); ?>" placeholder="Month (Oct), Year (2026), or Date (10/15/2026)" style="flex:1; min-width:210px; padding:8px; border:1px solid var(--line); border-radius:6px;">
                    <button class="btn btn-auto" type="submit" style="background:var(--teal); color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer;">Filter</button>
                    <a class="btn btn-auto btn-outline" href="applications.php?tab=adoption" style="padding:8px 16px; border:1px solid var(--line); border-radius:6px; text-decoration:none; color:var(--navy);">Reset</a>
                </form>

                <div style="overflow-x:auto">
                    <table style="width:100%; border-collapse:collapse">
                        <tr style="text-align:left; border-bottom:2px solid var(--line);">
                            <th style="padding:10px;">Applicant</th>
                            <th style="padding:10px;">Animal</th>
                            <th style="padding:10px;">Type</th>
                            <th style="padding:10px;">Date Applied</th>
                            <th style="padding:10px;">Status</th>
                            <th style="padding:10px;">Actions</th>
                        </tr>
                        <?php if (empty($adoption_apps)): ?>
                            <tr><td colspan="6" style="padding:20px; text-align:center; color:var(--muted);">No adoption or foster applications found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($adoption_apps as $app): 
                                $app_id = isset($app[$pk_col]) ? (int)$app[$pk_col] : (isset($app['id']) ? (int)$app['id'] : 0);
                                $applicant_name = htmlspecialchars($app['first_name'] . ' ' . $app['last_name']);
                                $animal_display = htmlspecialchars($app['animal_name']) . ' &middot; ' . htmlspecialchars(animalCode($app['animal_id']));
                                $app_type = htmlspecialchars(isset($app['application_type']) ? $app['application_type'] : 'Adopt');
                                $app_date = !empty($app['application_date']) ? date('d M Y', strtotime($app['application_date'])) : 'N/A';
                                $status = htmlspecialchars($app['status']);
                                
                                $badgeBg = '#fef5e0; color:#b9770e;';
                                if ($app['status'] === 'Approved') {
                                    $badgeBg = '#e6f4ec; color:#2a7d4f;';
                                } elseif ($app['status'] === 'Rejected') {
                                    $badgeBg = '#fdecea; color:#c0392b;';
                                }
                            ?>
                                <tr style="border-top:1px solid #dde3ea;">
                                    <td style="padding:10px; font-weight:500;"><?php echo $applicant_name; ?></td>
                                    <td style="padding:10px;"><?php echo $animal_display; ?></td>
                                    <td style="padding:10px;"><?php echo $app_type; ?></td>
                                    <td style="padding:10px;"><?php echo $app_date; ?></td>
                                    <td style="padding:10px;">
                                        <span style="padding:4px 10px; border-radius:12px; font-size:0.85rem; font-weight:600; background:<?php echo $badgeBg; ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                    <td style="padding:10px;">
                                        <div style="display:flex; gap:6px; align-items:center;">
                                            <form method="post" style="margin:0; display:inline;">
                                                <input type="hidden" name="app_id" value="<?php echo $app_id; ?>">
                                                <input type="hidden" name="status" value="Approved">
                                                <button type="submit" name="update_adoption_status" title="Approve" style="background:#2a7d4f; color:#fff; border:none; width:28px; height:28px; border-radius:4px; cursor:pointer;">✓</button>
                                            </form>
                                            <form method="post" style="margin:0; display:inline;">
                                                <input type="hidden" name="app_id" value="<?php echo $app_id; ?>">
                                                <input type="hidden" name="status" value="Rejected">
                                                <button type="submit" name="update_adoption_status" title="Reject" style="background:#c0392b; color:#fff; border:none; width:28px; height:28px; border-radius:4px; cursor:pointer;">✕</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>

            <?php else: ?>
                <!-- Volunteer Search & Filter Form -->
                <form method="get" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px;">
                    <input type="hidden" name="tab" value="volunteer">
                    <input name="q_vol" value="<?php echo htmlspecialchars($q_vol); ?>" placeholder="Search volunteer name" style="flex:2; min-width:180px; padding:8px; border:1px solid var(--line); border-radius:6px;">
                    <select name="status_vol" style="flex:1; min-width:120px; padding:8px; border:1px solid var(--line); border-radius:6px;">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $status_vol === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $status_vol === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $status_vol === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                    <input type="text" name="date_vol" value="<?php echo htmlspecialchars($date_vol); ?>" placeholder="Month (Oct), Year (2026), or Date (10/15/2026)" style="flex:1; min-width:210px; padding:8px; border:1px solid var(--line); border-radius:6px;">
                    <button class="btn btn-auto" type="submit" style="background:var(--teal); color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer;">Filter</button>
                    <a class="btn btn-auto btn-outline" href="applications.php?tab=volunteer" style="padding:8px 16px; border:1px solid var(--line); border-radius:6px; text-decoration:none; color:var(--navy);">Reset</a>
                </form>

                <div style="overflow-x:auto">
                    <table style="width:100%; border-collapse:collapse">
                        <tr style="text-align:left; border-bottom:2px solid var(--line);">
                            <th style="padding:10px;">Applicant</th>
                            <th style="padding:10px;">Date Applied</th>
                            <th style="padding:10px;">Status</th>
                            <th style="padding:10px;">Actions</th>
                        </tr>
                        <?php if (empty($volunteer_apps)): ?>
                            <tr><td colspan="4" style="padding:20px; text-align:center; color:var(--muted);">No volunteer applications found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($volunteer_apps as $vapp): 
                                $vapp_id = isset($vapp[$pk_vol_col]) ? (int)$vapp[$pk_vol_col] : (isset($vapp['application_id']) ? (int)$vapp['application_id'] : 0);
                                $v_name = htmlspecialchars($vapp['first_name'] . ' ' . $vapp['last_name']);
                                $v_date = !empty($vapp['application_date']) ? date('d M Y', strtotime($vapp['application_date'])) : 'N/A';
                                $v_status = htmlspecialchars($vapp['status']);
                                
                                $badgeBgVol = '#fef5e0; color:#b9770e;';
                                if ($vapp['status'] === 'Approved') {
                                    $badgeBgVol = '#e6f4ec; color:#2a7d4f;';
                                } elseif ($vapp['status'] === 'Rejected') {
                                    $badgeBgVol = '#fdecea; color:#c0392b;';
                                }
                            ?>
                                <tr style="border-top:1px solid #dde3ea;">
                                    <td style="padding:10px; font-weight:500;"><?php echo $v_name; ?></td>
                                    <td style="padding:10px;"><?php echo $v_date; ?></td>
                                    <td style="padding:10px;">
                                        <span style="padding:4px 10px; border-radius:12px; font-size:0.85rem; font-weight:600; background:<?php echo $badgeBgVol; ?>">
                                            <?php echo $v_status; ?>
                                        </span>
                                    </td>
                                    <td style="padding:10px;">
                                        <div style="display:flex; gap:6px; align-items:center;">
                                            <form method="post" style="margin:0; display:inline;">
                                                <input type="hidden" name="app_id" value="<?php echo $vapp_id; ?>">
                                                <input type="hidden" name="status" value="Approved">
                                                <button type="submit" name="update_volunteer_status" title="Approve" style="background:#2a7d4f; color:#fff; border:none; width:28px; height:28px; border-radius:4px; cursor:pointer;">✓</button>
                                            </form>
                                            <form method="post" style="margin:0; display:inline;">
                                                <input type="hidden" name="app_id" value="<?php echo $vapp_id; ?>">
                                                <input type="hidden" name="status" value="Rejected">
                                                <button type="submit" name="update_volunteer_status" title="Reject" style="background:#c0392b; color:#fff; border:none; width:28px; height:28px; border-radius:4px; cursor:pointer;">✕</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<?php require '../includes/footer.php'; ?>