<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

// Ensure user is an admin
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Handle form submission to update donation info
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method_type = trim($_POST['method_type'] ?? 'Bank Transfer');
    $account_name = trim($_POST['account_name'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $admin_id = (int)$_SESSION['user_id'];

    // Handle QR image upload if provided
    $qr_image_path = null;
    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['qr_image']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['qr_image']['name']);
        $upload_dir = '../assets/img/';
        
        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $qr_image_path = $file_name;
        }
    }

    // Check if a row already exists in donation_info
    $check = $conn->query("SELECT * FROM donation_info LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $existing = $check->fetch_assoc();
        $donation_id = $existing['donation_id'];
        
        if ($qr_image_path) {
            $stmt = $conn->prepare("UPDATE donation_info SET admin_id = ?, method_type = ?, account_name = ?, account_number = ?, instructions = ?, qr_image = ? WHERE donation_id = ?");
            $stmt->bind_param("isssssi", $admin_id, $method_type, $account_name, $account_number, $instructions, $qr_image_path, $donation_id);
        } else {
            $stmt = $conn->prepare("UPDATE donation_info SET admin_id = ?, method_type = ?, account_name = ?, account_number = ?, instructions = ? WHERE donation_id = ?");
            $stmt->bind_param("issssi", $admin_id, $method_type, $account_name, $account_number, $instructions, $donation_id);
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO donation_info (admin_id, method_type, account_name, account_number, instructions, qr_image, status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
        $stmt->bind_param("isssss", $admin_id, $method_type, $account_name, $account_number, $instructions, $qr_image_path);
    }

    if ($stmt->execute()) {
        $message = "Donation details updated successfully!";
    } else {
        $error = "Failed to update donation details: " . $conn->error;
    }
}

// Fetch current donation info
$donation = $conn->query("SELECT * FROM donation_info LIMIT 1")->fetch_assoc();

$pageTitle = 'Manage Donations';
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
        <a href="applications.php">Applications</a>
        <a href="schedules.php">Schedules</a>
        <a href="donation.php" class="is-active">Donation Settings</a>
	<a href="about_settings.php">About Page Settings</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <div class="panel">
            <h2>Manage Donation Info & QR Code</h2>
            <p class="sub">Update bank transfer details and upload a new QR code image.</p>

            <?php if ($message): ?>
                <div style="background: #e6f4ea; color: #137333; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= $message ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: #fce8e6; color: #c5221f; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid var(--line);">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 6px; color: var(--navy);">Method Type</label>
                    <input type="text" name="method_type" value="<?= e($donation['method_type'] ?? 'Bank Transfer') ?>" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 6px; color: var(--navy);">Account Name</label>
                    <input type="text" name="account_name" value="<?= e($donation['account_name'] ?? 'Bahrain Rescue Centre for Animals') ?>" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 6px; color: var(--navy);">Account Number / Bank Details</label>
                    <input type="text" name="account_number" value="<?= e($donation['account_number'] ?? 'National Bank of Bahrain (NBB) / BenefitPay') ?>" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 6px; color: var(--navy);">Instructions / Reference</label>
                    <textarea name="instructions" rows="3" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px;"><?= e($donation['instructions'] ?? 'BRCA Animal Support Donation') ?></textarea>
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 6px; color: var(--navy);">Upload QR Code Image</label>
                    <?php if (!empty($donation['qr_image'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../assets/img/<?= e($donation['qr_image']) ?>" alt="Current QR" style="width: 100px; height: 100px; object-fit: contain; border: 1px solid var(--line); border-radius: 6px; padding: 4px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="qr_image" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px; background: #f8fafc;">
                </div>

                <button type="submit" class="btn" style="background: var(--teal, #2a9d8f); color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Changes</button>
            </form>
        </div>
    </section>
</div>

<?php require '../includes/footer.php'; ?>