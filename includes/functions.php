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

// ---- Step 2: Announcement helper function ----
if (!function_exists('getAllAnnouncements')) {
    function getAllAnnouncements($conn, $limit = null) {
        $sql = "SELECT * FROM announcements ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        $result = $conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

// ---- Announcements ----
function announcementTypes()
{
    return ['Rescue Update', 'Event', 'Urgent Need', 'Donation Drive'];
}

function announcementBadge($type)
{
    switch ($type) {
        case 'Urgent Need':    return 'badge-red';
        case 'Event':          return 'badge-blue';
        case 'Donation Drive': return 'badge-green';
        default:               return 'badge-amber';
    }
}

// Shortens long text for preview cards
function excerpt($text, $max = 150)
{
    $text = trim(preg_replace('/\s+/', ' ', (string)$text));
    if (mb_strlen($text) <= $max) return $text;
    return rtrim(mb_substr($text, 0, $max)) . '...';
}