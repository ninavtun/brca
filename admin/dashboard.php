<?php
require '../config/db.php';
require '../includes/auth.php';
?>
<h1>Admin Dashboard</h1>
<p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</p>
<a href="../logout.php">Logout</a>