<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$target_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

<<<<<<< HEAD
=======
// Fetch all animals and find the matching one in PHP to avoid column name mismatches entirely
>>>>>>> main
$query = "SELECT * FROM animals";
$result = mysqli_query($conn, $query);

$animal = null;
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
<<<<<<< HEAD
=======
        // Check standard possible primary key fields dynamically
>>>>>>> main
        $current_id = $row['id'] ?? $row['animal_id'] ?? $row['pet_id'] ?? reset($row);
        if ($current_id == $target_id) {
            $animal = $row;
            break;
        }
    }
<<<<<<< HEAD
=======
    // If not found by ID, default to the first record
>>>>>>> main
    if (!$animal) {
        mysqli_data_seek($result, 0);
        $animal = mysqli_fetch_assoc($result);
    }
}

<<<<<<< HEAD
=======
// Fallback if table is empty
>>>>>>> main
if (!$animal) {
    $animal = [
        'name' => 'Luna',
        'code' => 'BRCA-0142',
        'species' => 'Cat',
        'breed' => 'Domestic Shorthair',
        'age' => '2 years',
        'sex' => 'Female',
        'health' => 'Vaccinated, healthy',
        'intake_date' => '12 March 2026',
        'status' => 'Available',
        'image' => 'default.jpg',
        'bio' => 'Luna is a sweet and gentle companion who loves quiet afternoons and gentle chin scratches.'
    ];
}

$resolved_id = $animal['id'] ?? $animal['animal_id'] ?? $animal['pet_id'] ?? $target_id;
?>
<<<<<<< HEAD
=======

>>>>>>> main
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($animal['name'] ?? 'Animal Profile'); ?> - Adopt Details</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-page {
            max-width: 950px;
            margin: 40px auto;
            padding: 0 16px;
        }
        .back-link {
            display: inline-block;
            color: var(--teal, #2a9d8f);
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 24px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 40px;
            background: var(--card, #ffffff);
            border: 1px solid var(--line, #d9dfe9);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            align-items: start;
        }
        .profile-img-wrap {
            width: 100%;
            aspect-ratio: 2 / 3;
            border-radius: 12px;
            overflow: hidden;
            background-color: #e2e8f0;
            border: 2px solid #cbd5e1;
        }
        .profile-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .profile-details h1 {
            margin: 0 0 10px 0;
            font-size: 2.2rem;
            color: var(--navy, #1e3a5f);
        }
        .badges {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            align-items: center;
        }
        .badge-id {
            background: var(--bg, #f3f5f9);
            color: var(--navy, #1e3a5f);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-status {
            background: var(--green, #2f855a);
            color: #fff;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .info-table tr {
            border-bottom: 1px solid var(--line, #e2e8f0);
        }
        .info-table th {
            text-align: left;
            padding: 10px 0;
            color: var(--muted, #718096);
            font-weight: 500;
            width: 35%;
        }
        .info-table td {
            padding: 10px 0;
            color: var(--navy, #1e3a5f);
            font-weight: 600;
        }
        .action-buttons {
            display: flex;
            gap: 14px;
        }
        .btn-adopt, .btn-foster {
            flex: 1;
            text-align: center;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-adopt {
            background: var(--teal, #2a9d8f);
            color: #fff;
            border: 2px solid var(--teal, #2a9d8f);
        }
        .btn-adopt:hover {
            background: #238579;
        }
        .btn-foster {
            background: transparent;
            color: var(--teal, #2a9d8f);
            border: 2px solid var(--teal, #2a9d8f);
        }
        .btn-foster:hover {
            background: var(--teal, #2a9d8f);
            color: #fff;
        }
        .bio-section {
            margin-top: 40px;
            background: var(--card, #ffffff);
            border: 1px solid var(--line, #d9dfe9);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .bio-section h3 {
            margin-top: 0;
            color: var(--navy, #1e3a5f);
            font-size: 1.4rem;
        }
        .bio-section p {
            color: var(--muted, #4a5568);
            line-height: 1.6;
            margin-bottom: 0;
        }
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<<<<<<< HEAD
    <?php include 'includes/header.php'; ?>
    <main class="profile-page">
        <a href="adopt.php" class="back-link">&lsaquo; Back to Available Animals</a>
        <div class="profile-container">
            <div class="profile-img-wrap">
                <img src="uploads/<?php echo htmlspecialchars($animal['image'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($animal['name'] ?? 'Pet'); ?>">
            </div>
            <div class="profile-details">
                <h1><?php echo htmlspecialchars($animal['name'] ?? 'Rescue Pet'); ?></h1>
=======

    <?php include 'includes/header.php'; ?>

    <main class="profile-page">
        <a href="adopt.php" class="back-link">&lsaquo; Back to Available Animals</a>

        <div class="profile-container">
            <!-- Animal Photo -->
            <div class="profile-img-wrap">
                <img src="uploads/<?php echo htmlspecialchars($animal['image'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($animal['name'] ?? 'Pet'); ?>">
            </div>

            <!-- Details & Action Buttons -->
            <div class="profile-details">
                <h1><?php echo htmlspecialchars($animal['name'] ?? 'Rescue Pet'); ?></h1>
                
>>>>>>> main
                <div class="badges">
                    <span class="badge-id"><?php echo htmlspecialchars($animal['code'] ?? 'BRCA-0000'); ?></span>
                    <span class="badge-status"><?php echo htmlspecialchars($animal['status'] ?? 'Available'); ?></span>
                </div>
<<<<<<< HEAD
=======

>>>>>>> main
                <table class="info-table">
                    <tr>
                        <th>Species</th>
                        <td><?php echo htmlspecialchars($animal['species'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Breed</th>
                        <td><?php echo htmlspecialchars($animal['breed'] ?? 'Mixed'); ?></td>
                    </tr>
                    <tr>
                        <th>Estimated age</th>
                        <td><?php echo htmlspecialchars($animal['age'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Sex</th>
                        <td><?php echo htmlspecialchars($animal['sex'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Health</th>
                        <td><?php echo htmlspecialchars($animal['health'] ?? 'Checked and healthy'); ?></td>
                    </tr>
                    <tr>
                        <th>Intake date</th>
                        <td><?php echo htmlspecialchars($animal['intake_date'] ?? 'N/A'); ?></td>
                    </tr>
                </table>
<<<<<<< HEAD
=======

>>>>>>> main
                <div class="action-buttons">
                    <a href="apply.php?id=<?php echo $resolved_id; ?>&type=adopt" class="btn-adopt">Apply to Adopt</a>
                    <a href="apply.php?id=<?php echo $resolved_id; ?>&type=foster" class="btn-foster">Apply to Foster</a>
                </div>
            </div>
        </div>
<<<<<<< HEAD
=======

        <!-- About Section -->
>>>>>>> main
        <div class="bio-section">
            <h3>About <?php echo htmlspecialchars($animal['name'] ?? 'this pet'); ?></h3>
            <p><?php echo htmlspecialchars($animal['bio'] ?? 'No additional details provided yet.'); ?></p>
        </div>
    </main>
<<<<<<< HEAD
    <?php include 'includes/footer.php'; ?>
=======

    <?php include 'includes/footer.php'; ?>

>>>>>>> main
</body>
</html>