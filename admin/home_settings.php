<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

// Ensure user is an admin
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Create tables if not exist
$conn->query("CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
)");

$conn->query("CREATE TABLE IF NOT EXISTS home_sections (
    section_id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL,
    section_type VARCHAR(50) NOT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
)");

// Seed default sections if empty
$check_empty = $conn->query("SELECT COUNT(*) as cnt FROM home_sections")->fetch_assoc();
if ($check_empty['cnt'] == 0) {
    $conn->query("INSERT INTO home_sections (section_name, section_type, display_order, is_active) VALUES 
        ('Hero Banner', 'hero', 1, 1),
        ('Available Animals', 'adopt', 2, 1),
        ('Announcements', 'announcements', 3, 1)");
}

// Handle Reordering via AJAX
if (isset($_POST['update_order']) && isset($_POST['order'])) {
    $orderArray = json_decode($_POST['order'], true);
    if (is_array($orderArray)) {
        foreach ($orderArray as $index => $id) {
            $id = (int)$id;
            $index = (int)$index;
            $conn->query("UPDATE home_sections SET display_order = $index WHERE section_id = $id");
        }
        echo json_encode(['status' => 'success']);
        exit;
    }
}

// Handle Add Section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
    $sec_name = trim($_POST['section_name'] ?? '');
    $sec_type = trim($_POST['section_type'] ?? 'adopt');
    
    if ($sec_name !== '') {
        $maxOrderRes = $conn->query("SELECT MAX(display_order) as max_ord FROM home_sections")->fetch_assoc();
        $newOrder = ((int)($maxOrderRes['max_ord'] ?? 0)) + 1;

        $stmt = $conn->prepare("INSERT INTO home_sections (section_name, section_type, display_order, is_active) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("ssi", $sec_name, $sec_type, $newOrder);
        if ($stmt->execute()) {
            $success = "New section added successfully!";
        } else {
            $error = "Failed to add section.";
        }
    } else {
        $error = "Section name is required.";
    }
}

// Handle Delete Section
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $conn->query("DELETE FROM home_sections WHERE section_id = $delId");
    header('Location: home_settings.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) {
    $success = "Section deleted successfully.";
}

// Handle Toggle Active/Hidden
if (isset($_GET['toggle'])) {
    $toggleId = (int)$_GET['toggle'];
    $conn->query("UPDATE home_sections SET is_active = 1 - is_active WHERE section_id = $toggleId");
    header('Location: home_settings.php');
    exit;
}

// Handle Hero settings save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_hero'])) {
    $hero_title = trim($_POST['hero_title'] ?? '');
    $hero_subtitle = trim($_POST['hero_subtitle'] ?? '');
    $btn1_text = trim($_POST['btn1_text'] ?? '');
    $btn2_text = trim($_POST['btn2_text'] ?? '');

    $settings = [
        'hero_title' => $hero_title,
        'hero_subtitle' => $hero_subtitle,
        'hero_btn1_text' => $btn1_text,
        'hero_btn2_text' => $btn2_text
    ];

    foreach ($settings as $key => $val) {
        $stmt = $conn->prepare("REPLACE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $key, $val);
        $stmt->execute();
    }

    if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['hero_image']['tmp_name'];
        $fileExtension = strtolower(pathinfo($_FILES['hero_image']['name'], PATHINFO_EXTENSION));
        if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $uploadFileDir = '../assets/img/';
            if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0755, true);
            $dest_path = $uploadFileDir . 'hero_banner.' . $fileExtension;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $stmt = $conn->prepare("REPLACE INTO site_settings (setting_key, setting_value) VALUES ('hero_image', ?)");
                $imgName = 'hero_banner.' . $fileExtension;
                $stmt->bind_param("s", $imgName);
                $stmt->execute();
            }
        }
    }
    $success = "Hero banner settings updated successfully!";
}

$sections = $conn->query("SELECT * FROM home_sections ORDER BY display_order ASC")->fetch_all(MYSQLI_ASSOC);
$settings_res = $conn->query("SELECT * FROM site_settings");
$settings = [];
if ($settings_res) {
    while ($row = $settings_res->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$pageTitle = 'Home Page Settings';
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
        <a href="donation.php">Donation Settings</a>
        <a href="home_settings.php" class="is-active">Home Page Settings</a>
	<a href="about_settings.php">About Page Settings</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <div class="panel" style="max-width: 800px; margin: 0 auto;">
            <h2>Home Page Settings</h2>
            <p class="sub">Manage menu-tab sections, drag to reorder their layout, and customize your hero banner.</p>

            <?php if ($success): ?>
                <div style="background: #e6f4ea; color: #137333; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: #fce8e6; color: #c5221f; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= $error ?></div>
            <?php endif; ?>

            <!-- Section Order & Management Panel -->
            <div style="background: #f8fafc; border: 1px solid var(--line); padding: 20px; border-radius: 12px; margin-bottom: 30px;">
                <h3 style="margin-top: 0; color: var(--navy);">Home Sections Layout (Drag to Reorder)</h3>
                <p style="color: var(--muted); font-size: 0.9rem; margin-bottom: 15px;">Drag the section items up or down to rearrange how they appear on the live home page.</p>
                
                <ul id="sortableSections" style="list-style: none; padding: 0; margin: 0 0 20px 0; display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($sections as $sec): ?>
                        <li draggable="true" data-id="<?= (int)$sec['section_id'] ?>" style="display: flex; justify-content: space-between; align-items: center; background: #fff; border: 1px solid var(--line); padding: 12px 16px; border-radius: 8px; cursor: grab; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span style="color: #cbd5e1; font-weight: bold; font-size: 1.2rem;">☰</span>
                                <div>
                                    <strong><?= htmlspecialchars($sec['section_name']) ?></strong>
                                    <span style="font-size: 0.8rem; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 4px; margin-left: 8px;"><?= htmlspecialchars($sec['section_type']) ?></span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <a href="home_settings.php?toggle=<?= (int)$sec['section_id'] ?>" style="font-size: 0.85rem; font-weight: 600; text-decoration: none; padding: 4px 10px; border-radius: 6px; background: <?= $sec['is_active'] ? '#e6f4ea; color: #137333;' : '#fef3c7; color: #d97706;' ?>;">
                                    <?= $sec['is_active'] ? 'Active' : 'Hidden' ?>
                                </a>
                                <a href="home_settings.php?delete=<?= (int)$sec['section_id'] ?>" onclick="return confirm('Are you sure you want to delete this section?');" style="color: #c5221f; font-weight: 600; text-decoration: none; font-size: 0.9rem;">Delete</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Add New Section Form -->
                <form method="POST" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; border-top: 1px solid var(--line); padding-top: 15px;">
                    <div style="flex: 2; min-width: 220px;">
                        <input type="text" name="section_name" placeholder="Section Display Title (e.g. Available Animals)" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 6px; background: #fff; font-size: 0.95rem;">
                    </div>
                    <div style="flex: 1; min-width: 160px;">
                        <select name="section_type" style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 6px; background: #fff; font-size: 0.95rem;">
                            <option value="hero">Hero Banner</option>
                            <option value="adopt">Adopt (Available Animals)</option>
                            <option value="volunteer">Volunteer</option>
                            <option value="donate">Donate</option>
                            <option value="announcements">Announcements</option>
                            <option value="about">About</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" name="add_section" class="btn" style="background: var(--teal, #2a9d8f); color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; white-space: nowrap;">+ Add Section</button>
                    </div>
                </form>
            </div>

            <!-- Hero Banner Customization Form -->
            <form method="POST" enctype="multipart/form-data" style="background: #fff; border: 1px solid var(--line); padding: 25px; border-radius: 12px; display: flex; flex-direction: column; gap: 20px;">
                <h3 style="margin-top: 0; color: var(--navy);">Hero Banner Content</h3>
                
                <div style="display: flex; align-items: center; gap: 20px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid var(--line);">
                    <div>
                        <?php 
                        $heroImg = $settings['hero_image'] ?? '';
                        $heroImgPath = '../assets/img/' . $heroImg;
                        if ($heroImg && file_exists($heroImgPath)): 
                        ?>
                            <img src="<?= $heroImgPath ?>" alt="Hero Banner" style="width: 110px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid var(--line);">
                        <?php else: ?>
                            <div style="width: 110px; height: 80px; background: #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">🖼️</div>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px;">Hero Banner Image</label>
                        <input type="file" name="hero_image" accept="image/*" style="width: 100%; padding: 6px; border: 1px solid var(--line); border-radius: 6px; background: #fff;">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 6px;">Hero Title Heading</label>
                    <input type="text" name="hero_title" value="<?= htmlspecialchars($settings['hero_title'] ?? 'Every rescued animal deserves a loving home') ?>" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px;">
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 6px;">Hero Subtitle Description</label>
                    <textarea name="hero_subtitle" rows="3" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px;"><?= htmlspecialchars($settings['hero_subtitle'] ?? 'Browse animals waiting for a home, volunteer, or support the Centre.') ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px;">Primary Button Text</label>
                        <input type="text" name="btn1_text" value="<?= htmlspecialchars($settings['hero_btn1_text'] ?? 'View Available Animals') ?>" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px;">Secondary Button Text</label>
                        <input type="text" name="btn2_text" value="<?= htmlspecialchars($settings['hero_btn2_text'] ?? 'Volunteer With Us') ?>" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px;">
                    </div>
                </div>

                <button type="submit" name="save_hero" class="btn" style="background: var(--teal, #2a9d8f); color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px;">Save Hero Settings</button>
            </form>
        </div>
    </section>
</div>

<script>
const list = document.getElementById('sortableSections');
let draggedItem = null;

list.addEventListener('dragstart', (e) => {
    draggedItem = e.target.closest('li');
    e.dataTransfer.effectAllowed = 'move';
});

list.addEventListener('dragover', (e) => {
    e.preventDefault();
    const targetItem = e.target.closest('li');
    if (targetItem && targetItem !== draggedItem) {
        const rect = targetItem.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
        list.insertBefore(draggedItem, next ? targetItem.nextSibling : targetItem);
    }
});

list.addEventListener('dragend', () => {
    const items = list.querySelectorAll('li');
    const order = Array.from(items).map(item => item.getAttribute('data-id'));
    
    fetch('home_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'update_order=1&order=' + encodeURIComponent(JSON.stringify(order))
    });
});
</script>

<?php require '../includes/footer.php'; ?>