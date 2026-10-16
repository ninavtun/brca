<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'About Us';
$active    = 'about';

// Fetch about page settings if available
$about = [];
$result = $conn->query("SELECT * FROM about_settings LIMIT 1");
if ($result && $result->num_rows > 0) {
    $about = $result->fetch_assoc();
}

$defaultMission = "The Bahrain Rescue Centre for Animals (BRCA) is a non-profit organization committed to providing safe haven, medical care, and rehabilitation for abandoned, abused, and neglected animals. We strive to find loving permanent homes for every animal under our care while promoting responsible pet ownership within our community.";

require_once __DIR__ . '/includes/header.php';
?>

<div class="panel" style="max-width: 900px; margin: 40px auto; padding: 30px;">
    <h1 style="color: var(--navy); margin-bottom: 6px;">About Us</h1>
    <p class="sub" style="margin-bottom: 30px;">Dedicated to the rescue, rehabilitation, and rehoming of animals in need across the Kingdom.</p>

    <!-- Our Mission Panel -->
    <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 12px; padding: 25px; margin-bottom: 30px;">
        <h3 style="color: var(--navy); margin-top: 0; margin-bottom: 12px;">Our Mission</h3>
        <p style="color: var(--muted); line-height: 1.6; margin: 0;">
            <?php echo nl2br(htmlspecialchars($about['mission_text'] ?? $defaultMission)); ?>
        </p>
    </div>

    <!-- What We Do Grid -->
    <h3 style="color: var(--navy); margin-bottom: 15px;">What We Do</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 35px;">
        <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 12px; padding: 20px;">
            <h4 style="color: var(--navy); margin-top: 0; margin-bottom: 8px;">Rescue & Rehabilitation</h4>
            <p style="color: var(--muted); font-size: 0.9rem; margin: 0; line-height: 1.5;">Providing emergency rescue operations, shelter spaces, nutritional support, and veterinary care to recovering animals.</p>
        </div>
        <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 12px; padding: 20px;">
            <h4 style="color: var(--navy); margin-top: 0; margin-bottom: 8px;">Adoption Program</h4>
            <p style="color: var(--muted); font-size: 0.9rem; margin: 0; line-height: 1.5;">Carefully matching rescued cats and dogs with compassionate individuals and families through our transparent adoption workflow.</p>
        </div>
        <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 12px; padding: 20px;">
            <h4 style="color: var(--navy); margin-top: 0; margin-bottom: 8px;">Community Engagement</h4>
            <p style="color: var(--muted); font-size: 0.9rem; margin: 0; line-height: 1.5;">Empowering volunteers and supporters through donation drives, community updates, and collaborative animal welfare schedules.</p>
        </div>
    </div>

    <!-- Visit & Support Us -->
    <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 12px; padding: 25px; margin-bottom: 25px;">
        <h3 style="color: var(--navy); margin-top: 0; margin-bottom: 10px;">Visit & Support Us</h3>
        <p style="color: var(--muted); line-height: 1.6; margin-bottom: 20px;">
            We are located in Hamala, Bahrain. Whether you want to adopt a companion, volunteer your time, or contribute to our ongoing rescue feeds and donation drives, every bit of support makes a direct difference in an animal's life.
        </p>
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <a href="volunteer.php" class="btn" style="display: block; text-align: center; background: var(--teal, #2a9d8f); color: #fff; padding: 12px; border-radius: 8px; font-weight: 600; text-decoration: none;">Join as a Volunteer</a>
            <a href="donate.php" class="btn" style="display: block; text-align: center; background: transparent; color: var(--teal, #2a9d8f); border: 2px solid var(--teal, #2a9d8f); padding: 12px; border-radius: 8px; font-weight: 600; text-decoration: none;">Support Our Cause</a>
        </div>
    </div>

    <!-- Connect With Us Online -->
    <h3 style="color: var(--navy); margin-bottom: 12px;">Connect With Us Online</h3>
    <div style="display: flex; flex-direction: column; gap: 10px;">
        <a href="https://instagram.com" target="_blank" style="display: block; text-align: center; background: #fff; color: var(--navy); border: 1px solid var(--line); padding: 12px; border-radius: 8px; font-weight: 600; text-decoration: none;">Instagram</a>
        <a href="https://tiktok.com" target="_blank" style="display: block; text-align: center; background: #fff; color: var(--navy); border: 1px solid var(--line); padding: 12px; border-radius: 8px; font-weight: 600; text-decoration: none;">TikTok</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>