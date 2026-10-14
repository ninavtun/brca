<?php
require '../config/db.php';
require '../includes/auth.php';
?>
<h1>Admin Dashboard</h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</p>
<ul>
    <li><a href="animals.php">Manage Animals</a></li>
    <li><a href="animals_add.php">Add Animal</a></li>
</ul>
<a href="../logout.php">Logout</a>