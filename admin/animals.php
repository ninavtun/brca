<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

$animals = $conn->query(
    "SELECT a.animal_id, a.name, a.species, a.status, a.photo, a.is_published, k.kennel_name
     FROM animals a
     LEFT JOIN kennel_assignments ka ON ka.animal_id = a.animal_id AND ka.date_out IS NULL
     LEFT JOIN kennels k ON k.kennel_id = ka.kennel_id
     ORDER BY a.animal_id DESC"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Animals - BRCA Admin</title>
</head>

<body>
    <p><a href="dashboard.php">&larr; Dashboard</a></p>
    <h1>Manage Animals</h1>
    <p><a href="animals_add.php">+ Add Animal</a></p>
    <table border="1" cellpadding="6">
        <tr>
            <th>ID</th>
            <th>Photo</th>
            <th>Name</th>
            <th>Species</th>
            <th>Kennel</th>
            <th>Status</th>
            <th>Published</th>
        </tr>
        <?php foreach ($animals as $a): ?>
            <tr>
                <td><?= e(animalCode($a['animal_id'])) ?></td>
                <td><?php if ($a['photo']): ?><img src="../uploads/<?= e($a['photo']) ?>" width="50"><?php endif; ?></td>
                <td><?= e($a['name']) ?></td>
                <td><?= e($a['species']) ?></td>
                <td><?= e($a['kennel_name'] ?? '-') ?></td>
                <td><?= e($a['status']) ?></td>
                <td><?= $a['is_published'] ? 'Yes' : 'No' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>