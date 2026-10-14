<?php

function animalCode($id)
{
    return sprintf('BRCA-%04d', $id);
}

// Escape text before printing it into HTML
function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// ---- CSRF helpers (blocks forged form submissions) ----
function csrfToken()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField()
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function csrfValid()
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// ---- Redirect helper: only allow safe, same-site relative pages ----
function safeNext($next, $default)
{
    $next = (string)$next;
    if (preg_match('/^[a-zA-Z0-9_\-\/]+\.php(\?[a-zA-Z0-9_=&\-]*)?$/', $next)) {
        return $next;
    }
    return $default;
}

// ---- Status badge class: pending / approved / rejected ----
function statusClass($status)
{
    $s = strtolower((string)$status);
    if ($s === 'approved') return 'badge-green';
    if ($s === 'rejected') return 'badge-red';
    return 'badge-amber';
}