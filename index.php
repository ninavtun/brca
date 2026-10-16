<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Home - Bahrain Rescue Centre for Animals';
$active = 'home';

// Ensure tables exist
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

$check_empty = $conn->query("SELECT COUNT(*) as cnt FROM home_sections")->fetch_assoc();
if ($check_empty['cnt'] == 0) {
    $conn->query("INSERT INTO home_sections (section_name, section_type, display_order, is_active) VALUES 
        ('Hero Banner', 'hero', 1, 1),
        ('Available Animals', 'adopt', 2, 1),
        ('Announcements', 'announcements', 3, 1)");
}

// Fetch site settings
$settings_res = $conn->query("SELECT * FROM site_settings");
$site_settings = [];
if ($settings_res) {
    while ($row = $settings_res->fetch_assoc()) {
        $site_settings[$row['setting_key']] = $row['setting_value'];
    }
}
$hero_title = $site_settings['hero_title'] ?? 'Every rescued animal deserves a loving home';
$hero_subtitle = $site_settings['hero_subtitle'] ?? 'Browse animals waiting for a home, volunteer, or support the Centre.';
$btn1_text = $site_settings['hero_btn1_text'] ?? 'View Available Animals';
$btn2_text = $site_settings['hero_btn2_text'] ?? 'Volunteer With Us';
$hero_img = $site_settings['hero_image'] ?? '';
$hero_img_path = 'assets/img/' . $hero_img;

// Fetch active sections
$sections_res = $conn->query("SELECT * FROM home_sections WHERE is_active = 1 ORDER BY display_order ASC");
$active_sections = [];
if ($sections_res) {
    while ($row = $sections_res->fetch_assoc()) $active_sections[] = $row;
}

// Fetch animals and announcements
$animals_result = $conn->query("SELECT * FROM animals WHERE status = 'Available' ORDER BY animal_id DESC LIMIT 3");
$featured_animals = [];
if ($animals_result) {
    while ($row = $animals_result->fetch_assoc()) $featured_animals[] = $row;
}

$announcements_result = $conn->query("SELECT * FROM announcements ORDER BY announcement_id DESC LIMIT 2");
$latest_announcements = [];
if ($announcements_result) {
    while ($row = $announcements_result->fetch_assoc()) $latest_announcements[] = $row;
}

require_once 'includes/header.php';
?>

<style>
    .home-hero {
        background: linear-gradient(135deg, #e6f4ec 0%, #f0fdf4 100%);
        border: 1px solid #d1fae5;
        border-radius: 16px;
        padding: 40px;
        display: grid;
        grid-template-columns: 1.3fr 1fr;
        gap: 30px;
        align-items: center;
        margin-bottom: 40px;
    }
    .home-hero h1 { font-size: 2.5rem; color: var(--navy, #1e3a5f); margin-top: 0; margin-bottom: 15px; line-height: 1.2; }
    .home-hero p { color: var(--muted, #4b5563); font-size: 1.05rem; margin-bottom: 25px; line-height: 1.5; }
    .hero-buttons { display: flex; gap: 12px; flex-wrap: wrap; }
    .hero-img-box { background: #e2e8f0; border-radius: 12px; aspect-ratio: 4 / 3; display: flex; align-items: center; justify-content: center; font-size: 3rem; overflow: hidden; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .section-header h2 { font-size: 1.6rem; color: var(--navy, #1e3a5f); margin: 0; }
    .section-header a { color: var(--teal, #2a9d8f); font-weight: 600; text-decoration: none; }
    .section-header a:hover { text-decoration: underline; }
    .animals-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 40px; }
    .animal-card { background: #fff; border: 1px solid var(--line, #e2e8f0); border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; flex-direction: column; }
    .animal-card-img { width: 100%; aspect-ratio: 4 / 3; border-radius: 8px; background: #e2e8f0; object-fit: cover; margin-bottom: 12px; }
    .animal-card h3 { margin: 0 0 4px 0; font-size: 1.25rem; color: var(--navy, #1e3a5f); }
    .animal-card .meta { color: var(--muted, #64748b); font-size: 0.9rem; margin-bottom: 12px; }
    .animal-card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 10px; }
    .announcements-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .announcement-card { background: #fff; border: 1px solid var(--line, #e2e8f0); border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
    .announcement-badge { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-block; margin-bottom: 10px; text-transform: uppercase; }
    .content-box { background: #fff; border: 1px solid var(--line, #e2e8f0); border-radius: 12px; padding: 25px; margin-bottom: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
    @media (max-width: 768px) { .home-hero { grid-template-columns: 1fr; padding: 24px; } }
</style>

<div class="page-container" style="max-width: 1100px; margin: 30px auto; padding: 0 16px;">
    
    <?php foreach ($active_sections as $sec): ?>
        <?php if ($sec['section_type'] === 'hero'): ?>
            <!-- Hero Section -->
            <section class="home-hero">
                <div>
                    <h1><?= htmlspecialchars($hero_title) ?></h1>
                    <p><?= htmlspecialchars($hero_subtitle) ?></p>
                    <div class="hero-buttons">
                        <a href="adopt.php" class="btn" style="background: var(--teal, #2a9d8f); color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;"><?= htmlspecialchars($btn1_text) ?></a>
                        <a href="volunteer.php" class="btn btn-outline" style="background: #fff; color: var(--teal, #2a9d8f); border: 2px solid var(--teal, #2a9d8f); padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;"><?= htmlspecialchars($btn2_text) ?></a>
                    </div>
                </div>
                <div class="hero-img-box">
                    <?php if ($hero_img && file_exists($hero_img_path)): ?>
                        <img src="<?= $hero_img_path ?>" alt="Hero Banner" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    <?php else: ?>
                        🐾
                    <?php endif; ?>
                </div>
            </section>

        <?php elseif ($sec['section_type'] === 'adopt' || $sec['section_type'] === 'available_animals'): ?>
            <!-- Adopt Section (Available Animals) -->
            <section style="margin-bottom: 45px;">
                <div class="section-header">
                    <h2><?= htmlspecialchars($sec['section_name']) ?></h2>
                    <a href="adopt.php">View all &rarr;</a>
                </div>

                <div class="animals-grid">
                    <?php if (!empty($featured_animals)): ?>
                        <?php foreach ($featured_animals as $animal): 
                            $aid = (int)$animal['animal_id'];
                            $name = htmlspecialchars($animal['name']);
                            $species = htmlspecialchars($animal['species']);
                            $age = htmlspecialchars($animal['estimated_age'] ?? ($animal['age'] ?? 'N/A'));
                            $code = htmlspecialchars(animalCode($aid));
                            $status = htmlspecialchars($animal['status'] ?? 'Available');

                            $photoPath = '';
                            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                                if (file_exists('assets/img/animals/' . $aid . '.' . $ext)) {
                                    $photoPath = 'assets/img/animals/' . $aid . '.' . $ext;
                                    break;
                                }
                            }
                        ?>
                            <div class="animal-card">
                                <?php if ($photoPath): ?>
                                    <img src="<?= $photoPath ?>" alt="<?= $name ?>" class="animal-card-img">
                                <?php else: ?>
                                    <div class="animal-card-img" style="display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #94a3b8;">🐾</div>
                                <?php endif; ?>
                                
                                <h3><?= $name ?></h3>
                                <div class="meta"><?= $species ?> &middot; <?= $age ?></div>
                                
                                <div class="animal-card-footer">
                                    <span class="badge" style="background: #f1f5f9; color: var(--navy); padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600;"><?= $code ?></span>
                                    <span class="badge" style="background: #e6f4ec; color: #2a7d4f; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600;"><?= $status ?></span>
                                </div>
                                <div style="margin-top: 14px;">
                                    <a href="animal_profile.php?id=<?= $aid ?>" class="btn" style="display: block; text-align: center; background: transparent; color: var(--teal, #2a9d8f); border: 2px solid var(--teal, #2a9d8f); padding: 8px; border-radius: 6px; text-decoration: none; font-weight: 600;">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--muted);">No available animals at the moment.</p>
                    <?php endif; ?>
                </div>
            </section>

        <?php elseif ($sec['section_type'] === 'volunteer'): ?>
            <!-- Volunteer Section -->
            <div class="content-box">
                <div class="section-header">
                    <h2><?= htmlspecialchars($sec['section_name']) ?></h2>
                    <a href="volunteer.php">Sign up &rarr;</a>
                </div>
                <p style="color: var(--muted); line-height: 1.6; margin-bottom: 15px;">
                    Join our hands-on volunteer community! Help us care for rescued animals, assist with daily shelter operations, and make a direct difference in their lives.
                </p>
                <a href="volunteer.php" class="btn" style="display: inline-block; background: var(--teal, #2a9d8f); color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600;">View Volunteer Schedules</a>
            </div>

        <?php elseif ($sec['section_type'] === 'donate'): ?>
            <!-- Donate Section -->
            <div class="content-box">
                <div class="section-header">
                    <h2><?= htmlspecialchars($sec['section_name']) ?></h2>
                    <a href="donate.php">Support Us &rarr;</a>
                </div>
                <p style="color: var(--muted); line-height: 1.6; margin-bottom: 15px;">
                    Your contributions directly fund medical care, nutritious food, and safe shelter for rescued animals across Bahrain. Every bit helps!
                </p>
                <a href="donate.php" class="btn" style="display: inline-block; background: var(--teal, #2a9d8f); color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600;">Make a Donation</a>
            </div>

        <?php elseif ($sec['section_type'] === 'announcements'): ?>
            <!-- Announcements Section -->
            <section style="margin-bottom: 40px;">
                <div class="section-header">
                    <h2><?= htmlspecialchars($sec['section_name']) ?></h2>
                    <a href="announcements.php">View all &rarr;</a>
                </div>

                <div class="announcements-grid">
                    <?php if (!empty($latest_announcements)): ?>
                        <?php foreach ($latest_announcements as $announcement): ?>
                            <div class="announcement-card">
                                <span class="announcement-badge"><?= htmlspecialchars($announcement['category'] ?? 'Rescue Update') ?></span>
                                <h3 style="margin: 0 0 8px 0; color: var(--navy, #1e3a5f); font-size: 1.1rem;"><?= htmlspecialchars($announcement['title'] ?? '') ?></h3>
                                <p style="color: var(--muted, #64748b); font-size: 0.95rem; line-height: 1.5; margin-bottom: 0;">
                                    <?= htmlspecialchars(substr($announcement['content'] ?? '', 0, 120)) ?>...
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="announcement-card">
                            <span class="announcement-badge">Rescue Update</span>
                            <h3 style="margin: 0 0 8px 0; color: var(--navy, #1e3a5f); font-size: 1.1rem;">Welcome to the New Bahrain Rescue Centre Portal</h3>
                            <p style="color: var(--muted, #64748b); font-size: 0.95rem; line-height: 1.5; margin-bottom: 0;">
                                Stay tuned for regular updates regarding our rescued animals and upcoming events.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        <?php elseif ($sec['section_type'] === 'about'): ?>
            <!-- About Section -->
            <div class="content-box">
                <div class="section-header">
                    <h2><?= htmlspecialchars($sec['section_name']) ?></h2>
                    <a href="about.php">Learn more &rarr;</a>
                </div>
                <p style="color: var(--muted); line-height: 1.6; margin-bottom: 15px;">
                    The Bahrain Rescue Centre for Animals is dedicated to rescuing, rehabilitating, and rehoming animals in need across Bahrain. Discover our mission, our team, and how we operate.
                </p>
                <a href="about.php" class="btn" style="display: inline-block; background: transparent; color: var(--teal, #2a9d8f); border: 2px solid var(--teal, #2a9d8f); padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600;">About Our Centre</a>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

</div>

<?php require_once 'includes/footer.php'; ?>