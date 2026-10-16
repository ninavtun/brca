<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$query = "SELECT * FROM animals ORDER BY animal_id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adopt Animals - Bahrain Rescue Centre for Animals</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .animals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 45px;
            padding-top: 30px;
        }
        .animal-card {
            background: var(--card, #ffffff);
            border: 1px solid var(--line, #d9dfe9);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        /* --- Centered Card & Proportional Sizing --- */
        .animal-profile-card {
            position: relative;
            width: 82%;
            aspect-ratio: 2 / 3;
            margin: 45px auto 25px auto;
            cursor: pointer;
        }

        .animal-photo-container {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 12px;
            overflow: hidden;
            background-color: #e2e8f0;
            border: 2px solid #cbd5e1;
        }

        .animal-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.3s ease;
        }

        /* --- Exact Frame Positioning matching target design --- */
        .frame {
            position: absolute;
            left: -16px;
            right: -16px;
            width: calc(100% + 32px);
            object-fit: contain;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10;
        }

        /* --- Still Frame (Normal state when NOT hovering) --- */
        .still-frame {
            top: -210px;     
            bottom: -210px;  
            height: calc(100% + 48px);
        }

        /* Hover Top Frame (Cats pop higher on hover) */
        .hover-top {
            top: -50px;
            height: 85px;
            opacity: 0;
        }

        /* Hover Bottom Frame (Paws dangle lower over the bottom edge on hover) */
        .hover-bottom {
            bottom: -55px;
            height: 85px;
            opacity: 0;
        }

        /* Hover Split Animations */
        .animal-profile-card:hover .still-frame {
            opacity: 0;
        }
        .animal-profile-card:hover .hover-top {
            opacity: 1;
            transform: translateY(-16px);
        }
        .animal-profile-card:hover .hover-bottom {
            opacity: 1;
            transform: translateY(16px);
        }
        .animal-profile-card:hover .animal-photo {
            transform: scale(1.03);
        }

        .animal-info h3 { margin: 0 0 4px 0; font-size: 1.25rem; color: var(--navy, #1e3a5f); }
        .animal-info p { margin: 0 0 10px 0; font-size: 0.9rem; color: var(--muted, #718096); }
        .badges { display: flex; gap: 8px; margin-bottom: 14px; align-items: center; }
        .badge-id { background: var(--bg, #f3f5f9); color: var(--navy, #1e3a5f); padding: 3px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        .badge-status { background: var(--green, #2f855a); color: #fff; padding: 3px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        .btn-details {
            display: block; text-align: center; background: transparent; color: var(--teal, #2a9d8f);
            border: 2px solid var(--teal, #2a9d8f); padding: 10px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: all 0.2s ease;
        }
        .btn-details:hover { background: var(--teal, #2a9d8f); color: #fff; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="page" style="padding: 40px 16px; max-width: 1100px; margin: 0 auto;">
        <h2 style="color: var(--navy); margin-bottom: 4px;">Available Animals</h2>
        <p style="color: var(--muted); margin-top: 0; margin-bottom: 30px;">Browse animals that are ready for adoption or fostering.</p>

        <div class="animals-grid">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($animal = mysqli_fetch_assoc($result)): ?>
                    <?php 
                        $aid = (int)($animal['animal_id'] ?? 1);
                        $animal_name = $animal['name'] ?? 'Rescue Pet';
                        $animal_species = $animal['species'] ?? 'Animal';
                        $animal_age = $animal['estimated_age'] ?? ($animal['age'] ?? 'N/A');
                        $animal_code = animalCode($aid);
                        $animal_status = $animal['status'] ?? 'Available';

                        // Check for animal photo using ID and common extensions
                        $photoPath = '';
                        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                            if (file_exists('assets/img/animals/' . $aid . '.' . $ext)) {
                                $photoPath = 'assets/img/animals/' . $aid . '.' . $ext;
                                break;
                            }
                        }
                    ?>
                    <div class="animal-card">
                        <div class="animal-profile-card">
                            <div class="animal-photo-container">
                                <?php if ($photoPath): ?>
                                    <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="<?php echo htmlspecialchars($animal_name); ?>" class="animal-photo">
                                <?php else: ?>
                                    <div class="animal-photo" style="display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #94a3b8;">🐾</div>
                                <?php endif; ?>
                            </div>
                            <img src="assets/img/still_frame.png" alt="" class="frame still-frame">
                            <img src="assets/img/hover_PF_T.png" alt="" class="frame hover-top">
                            <img src="assets/img/hover_PF_B.png" alt="" class="frame hover-bottom">
                        </div>

                        <div class="animal-info">
                            <h3><?php echo htmlspecialchars($animal_name); ?></h3>
                            <p><?php echo htmlspecialchars($animal_species); ?> &bull; <?php echo htmlspecialchars($animal_age); ?></p>
                            <div class="badges">
                                <span class="badge-id"><?php echo htmlspecialchars($animal_code); ?></span>
                                <span class="badge-status"><?php echo htmlspecialchars($animal_status); ?></span>
                            </div>
                            <a href="animal_profile.php?id=<?php echo $aid; ?>" class="btn-details">View Details</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: var(--muted);">No animals currently listed for adoption.</p>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>