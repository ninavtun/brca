<?php
// Set these BEFORE including this file (all optional):
//   $pageTitle  - text for the browser tab
//   $active     - which nav link to highlight: home, adopt, volunteer, donate, announcements, about, admin, account
//   $base       - path back to the project root: '' for root pages, '../' for pages in /admin
require_once __DIR__ . '/functions.php';

// Automatically detect if we are in a subfolder like /admin/ if $base isn't set manually
if (!isset($base)) {
    $inAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
    $base    = $inAdmin ? '../' : '';
}

$pageTitle = $pageTitle ?? 'Bahrain Rescue Centre for Animals';
$active    = $active ?? '';
$loggedIn  = !empty($_SESSION['user_id']);
$isAdmin   = ($_SESSION['role'] ?? '') === 'admin';

$nav = [
    'home'          => ['Home',         'index.php'],
    'adopt'         => ['Adopt',        'adopt.php'],
    'volunteer'     => ['Volunteer',    'volunteer.php'],
    'donate'        => ['Donate',       'donate.php'],
    'announcements' => ['Announcements', 'announcements.php'],
    'about'         => ['About',        'about.php'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e($base) ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= e($base) ?>assets/css/announcements.css">
    <link rel="icon" type="image/png" href="<?= e($base) ?>assets/img/logo.png">
</head>

<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="<?= e($base) ?>index.php" style="display:flex;align-items:center;">
                <img src="<?= e($base) ?>assets/img/logo.png" alt="BRCA - Bahrain Rescue Centre for Animals" height="48" style="height:48px;width:auto;display:block;">
            </a>

            <nav class="nav" id="main-nav" aria-label="Main">
                <?php foreach ($nav as $key => [$label, $href]): ?>
                    <a href="<?= e($base . $href) ?>" class="<?= $active === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
                <?php if ($loggedIn): ?>
                    <?php if ($isAdmin): ?>
                        <a href="<?= e($base) ?>admin/dashboard.php" class="<?= $active === 'admin' ? 'is-active' : '' ?>">Admin</a>
                    <?php endif; ?>
                    <a class="btn-pill <?= $active === 'account' ? 'is-active' : '' ?>" href="<?= e($base) ?>account.php">My Account</a>
                <?php else: ?>
                    <a class="btn-pill <?= $active === 'login' ? 'is-active' : '' ?>" href="<?= e($base) ?>login.php">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="page">