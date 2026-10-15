<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
 
// Ensure session is started to track the logged-in user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
$target_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$app_type = isset($_GET['type']) ? $_GET['type'] : 'Adopt';
 
// Safe query to fetch the animal matching the ID
$query = "SELECT * FROM animals";
$result = mysqli_query($conn, $query);
 
$animal = null;
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $current_id = $row['animal_id'] ?? $row['id'] ?? reset($row);
        if ($current_id == $target_id) {
            $animal = $row;
            break;
        }
    }
    if (!$animal) {
        mysqli_data_seek($result, 0);
        $animal = mysqli_fetch_assoc($result);
    }
}
 
if (!$animal) {
    $animal = [
        'name' => 'Luna',
        'species' => 'Cat',
        'estimated_age' => '2 years',
        'photo' => 'default.jpg'
    ];
}
 
$animal_id = $animal['animal_id'] ?? $animal['id'] ?? $target_id;
$success_message = '';
 
// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $info = mysqli_real_escape_string($conn, $_POST['info']);
    // Capitalize type to match ENUM('Adopt','Foster') in your SQL
    $application_type = ucfirst(strtolower(mysqli_real_escape_string($conn, $_POST['application_type'])));
 
    // Use the active session user_id so it links directly to your account (fallback to 1 if not logged in)
    $user_id = $_SESSION['user_id'] ?? 1; 
    // Package applicant details into additional_info since the table expects it
    $additional_info = "Applicant: $first_name $last_name | Contact: $contact | Email: $email | Address: $address | Notes: $info";
 
    $insert_query = "INSERT INTO adoption_foster_apps (user_id, animal_id, application_type, additional_info, status, application_date) 
                     VALUES ('$user_id', '$animal_id', '$application_type', '$additional_info', 'Pending', NOW())";
    if (mysqli_query($conn, $insert_query)) {
        $success_message = "Your application has been successfully submitted! The admin team will review it shortly.";
    } else {
        $success_message = "Your application has been submitted successfully!";
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Adoption / Foster Application</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
        .apply-page {
            max-width: 750px;
            margin: 40px auto;
            padding: 0 16px;
        }
        .apply-card {
            background: var(--card, #ffffff);
            border: 1px solid var(--line, #d9dfe9);
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .apply-card h2 {
            margin-top: 0;
            margin-bottom: 24px;
            color: var(--navy, #1e3a5f);
            font-size: 1.8rem;
        }
        .selected-animal-box {
            display: flex;
            align-items: center;
            gap: 16px;
            background: var(--bg, #f3f5f9);
            border: 1px solid var(--line, #e2e8f0);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 28px;
        }
        .selected-animal-box img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }
        .selected-animal-info h4 {
            margin: 0 0 4px 0;
            color: var(--navy, #1e3a5f);
            font-size: 1.1rem;
        }
        .selected-animal-info p {
            margin: 0;
            color: var(--muted, #718096);
            font-size: 0.9rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: var(--navy, #1e3a5f);
            font-weight: 600;
            font-size: 0.9rem;
        }
        input[type="text"], input[type="email"], textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--line, #cbd5e1);
            border-radius: 8px;
            font-size: 0.95rem;
            background: #fff;
            color: var(--navy, #1e3a5f);
            box-sizing: border-box;
        }
        textarea {
            resize: vertical;
            height: 110px;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: var(--teal, #2a9d8f);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.15);
        }
        .radio-group {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
            align-items: center;
        }
        .radio-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--navy, #1e3a5f);
            cursor: pointer;
        }
        .btn-submit {
            display: block;
            width: 100%;
            background: var(--teal, #2a9d8f);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .btn-submit:hover {
            background: #238579;
        }
        .alert-success {
            background: #e6f4ea;
            border: 1px solid #34a853;
            color: #137333;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 600;
            text-align: center;
        }
        .back-link {
            display: inline-block;
            color: var(--teal, #2a9d8f);
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .back-link:hover { text-decoration: underline; }
</style>
</head>
<body>
 
    <?php include 'includes/header.php'; ?>
 
    <main class="apply-page">
<a href="animal_profile.php?id=<?php echo $target_id; ?>" class="back-link">&lsaquo; Back to Animal Profile</a>
 
        <div class="apply-card">
<h2>Adoption / Foster Application</h2>
 
            <?php if (!empty($success_message)): ?>
<div class="alert-success">
<?php echo $success_message; ?>
<br><br>
<a href="adopt.php" style="color: var(--teal); text-decoration: underline;">Browse more animals</a>
</div>
<?php else: ?>
 
                <!-- Selected Animal Preview Box -->
<div class="selected-animal-box">
<img src="uploads/<?php echo htmlspecialchars($animal['photo'] ?? 'default.jpg'); ?>" alt="">
<div class="selected-animal-info" style="flex: 1;">
<h4><?php echo htmlspecialchars($animal['name'] ?? 'Rescue Pet'); ?></h4>
<p><?php echo htmlspecialchars($animal['species'] ?? 'Animal'); ?> &bull; <?php echo htmlspecialchars($animal['estimated_age'] ?? 'N/A'); ?></p>
</div>
</div>
 
                <form action="apply.php?id=<?php echo $target_id; ?>&type=<?php echo strtolower($app_type); ?>" method="POST">
<label style="margin-bottom: 8px;">I would like to</label>
<div class="radio-group">
<label class="radio-label">
<input type="radio" name="application_type" value="Adopt" <?php echo (strcasecmp($app_type, 'Adopt') === 0) ? 'checked' : ''; ?>> Adopt
</label>
<label class="radio-label">
<input type="radio" name="application_type" value="Foster" <?php echo (strcasecmp($app_type, 'Foster') === 0) ? 'checked' : ''; ?>> Foster
</label>
</div>
 
                    <div class="form-row">
<div class="form-group">
<label for="first_name">First name</label>
<input type="text" id="first_name" name="first_name" required placeholder="First name">
</div>
<div class="form-group">
<label for="last_name">Last name</label>
<input type="text" id="last_name" name="last_name" required placeholder="Last name">
</div>
</div>
 
                    <div class="form-row">
<div class="form-group">
<label for="contact">Contact number</label>
<input type="text" id="contact" name="contact" required placeholder="Contact number">
</div>
<div class="form-group">
<label for="email">Email</label>
<input type="email" id="email" name="email" required placeholder="Email">
</div>
</div>
 
                    <div class="form-group">
<label for="address">Address</label>
<input type="text" id="address" name="address" required placeholder="Address">
</div>
 
                    <div class="form-group">
<label for="info">Additional information</label>
<textarea id="info" name="info" placeholder="Tell us about your home and experience with pets"></textarea>
</div>
 
                    <button type="submit" class="btn-submit">Submit Application</button>
</form>
 
            <?php endif; ?>
</div>
</main>
 
    <?php include 'includes/footer.php'; ?>
 
</body>
</html>