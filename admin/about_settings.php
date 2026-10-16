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

// Create about_settings table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS about_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
)");

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'about_subtitle' => trim($_POST['about_subtitle'] ?? ''),
        'mission_text' => trim($_POST['mission_text'] ?? ''),
        
        'card1_title' => trim($_POST['card1_title'] ?? ''),
        'card1_desc' => trim($_POST['card1_desc'] ?? ''),
        'card2_title' => trim($_POST['card2_title'] ?? ''),
        'card2_desc' => trim($_POST['card2_desc'] ?? ''),
        'card3_title' => trim($_POST['card3_title'] ?? ''),
        'card3_desc' => trim($_POST['card3_desc'] ?? ''),

        'visit_text' => trim($_POST['visit_text'] ?? ''),
        
        'social_facebook' => trim($_POST['social_facebook'] ?? ''),
        'social_instagram' => trim($_POST['social_instagram'] ?? ''),
        'social_twitter' => trim($_POST['social_twitter'] ?? ''),
        'social_tiktok' => trim($_POST['social_tiktok'] ?? ''),
        'social_whatsapp' => trim($_POST['social_whatsapp'] ?? '')
    ];

    foreach ($fields as $key => $val) {
        $stmt = $conn->prepare("REPLACE INTO about_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $key, $val);
        $stmt->execute();
    }

    $success = "About Us page and social links updated successfully!";
}

// Fetch current settings
$res = $conn->query("SELECT * FROM about_settings");
$settings = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$pageTitle = 'About Page Settings';
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
        <a href="home_settings.php">Home Page Settings</a>
        <a href="about_settings.php" class="is-active">About Page Settings</a>
        <a href="../account.php">Back to User Account</a>
        <a href="../logout.php" class="logout">Log Out</a>
    </aside>

    <section>
        <div class="panel" style="max-width: 800px; margin: 0 auto;">
            <h2>About Us Page Settings</h2>
            <p class="sub">Customize your mission statement, pillars, visit info, and social media links.</p>

            <?php if ($success): ?>
                <div style="background: #e6f4ea; color: #137333; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 6px;">Page Subtitle / Lead Text</label>
                    <input type="text" name="about_subtitle" value="<?= htmlspecialchars($settings['about_subtitle'] ?? 'Dedicated to the rescue, rehabilitation, and rehoming of animals in need across the Kingdom.') ?>" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px;">
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 6px;">Our Mission Description</label>
                    <textarea name="mission_text" rows="4" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px;"><?= htmlspecialchars($settings['mission_text'] ?? 'The Bahrain Rescue Centre for Animals (BRCA) is a non-profit organization committed to providing safe haven, medical care, and rehabilitation for abandoned, abused, and neglected animals. We strive to find loving permanent homes for every animal under our care while promoting responsible pet ownership within our community.') ?></textarea>
                </div>

                <!-- What We Do Cards -->
                <div style="background: #f8fafc; border: 1px solid var(--line); padding: 20px; border-radius: 10px; display: flex; flex-direction: column; gap: 15px;">
                    <h3 style="margin-top: 0; color: var(--navy); font-size: 1.1rem;">What We Do (Cards)</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <input type="text" name="card1_title" value="<?= htmlspecialchars($settings['card1_title'] ?? 'Rescue & Rehabilitation') ?>" placeholder="Card 1 Title" style="padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                        <input type="text" name="card2_title" value="<?= htmlspecialchars($settings['card2_title'] ?? 'Adoption Program') ?>" placeholder="Card 2 Title" style="padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <textarea name="card1_desc" rows="2" placeholder="Card 1 Description" style="padding: 8px; border: 1px solid var(--line); border-radius: 6px;"><?= htmlspecialchars($settings['card1_desc'] ?? 'Providing emergency rescue operations, shelter spaces, nutritional support, and veterinary care to recovering animals.') ?></textarea>
                        <textarea name="card2_desc" rows="2" placeholder="Card 2 Description" style="padding: 8px; border: 1px solid var(--line); border-radius: 6px;"><?= htmlspecialchars($settings['card2_desc'] ?? 'Carefully matching rescued cats and dogs with compassionate individuals and families through our transparent adoption workflow.') ?></textarea>
                    </div>

                    <div style="margin-top: 10px;">
                        <input type="text" name="card3_title" value="<?= htmlspecialchars($settings['card3_title'] ?? 'Community Engagement') ?>" placeholder="Card 3 Title" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px; margin-bottom: 8px;">
                        <textarea name="card3_desc" rows="2" placeholder="Card 3 Description" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;"><?= htmlspecialchars($settings['card3_desc'] ?? 'Empowering volunteers and supporters through donation drives, community updates, and collaborative animal welfare schedules.') ?></textarea>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 6px;">Visit & Support Us Text</label>
                    <textarea name="visit_text" rows="3" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px;"><?= htmlspecialchars($settings['visit_text'] ?? 'We are located in Hamala, Bahrain. Whether you want to adopt a companion, volunteer your time, or contribute to our ongoing rescue feeds and donation drives, every bit of support makes a direct difference in an animal\'s life.') ?></textarea>
                </div>

                <!-- Social Links Section -->
                <div style="background: #f8fafc; border: 1px solid var(--line); padding: 20px; border-radius: 10px; display: flex; flex-direction: column; gap: 15px;">
                    <h3 style="margin-top: 0; color: var(--navy); font-size: 1.1rem;">Social Media Links</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Facebook URL</label>
                            <input type="url" name="social_facebook" value="<?= htmlspecialchars($settings['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/..." style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Instagram URL</label>
                            <input type="url" name="social_instagram" value="<?= htmlspecialchars($settings['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/..." style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Twitter / X URL</label>
                            <input type="url" name="social_twitter" value="<?= htmlspecialchars($settings['social_twitter'] ?? '') ?>" placeholder="https://twitter.com/..." style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">TikTok URL</label>
                            <input type="url" name="social_tiktok" value="<?= htmlspecialchars($settings['social_tiktok'] ?? '') ?>" placeholder="https://tiktok.com/..." style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">WhatsApp Link or Number</label>
                        <input type="text" name="social_whatsapp" value="<?= htmlspecialchars($settings['social_whatsapp'] ?? '') ?>" placeholder="https://wa.me/..." style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px;">
                    </div>
                </div>

                <button type="submit" class="btn" style="background: var(--teal, #2a9d8f); color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px;">Save About Settings</button>
            </form>
        </div>
    </section>
</div>

<?php require '../includes/footer.php'; ?>