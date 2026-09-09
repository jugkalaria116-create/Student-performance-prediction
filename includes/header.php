<?php
/**
 * includes/header.php
 * -----------------------------------------------------------
 * Starts the session, loads DB connection + helpers, and prints
 * the opening <html><head> section plus the top of the layout.
 *
 * Every page includes this file FIRST, before any HTML output,
 * so session_start() always runs before anything is sent to the browser.
 *
 * Each page must set $pageTitle before including this file.
 * -----------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Student Performance Predictor';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> | Student Performance Predictor</title>

<!-- Bootstrap 5 (layout helpers only - visual design comes from style.css) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<!-- Custom design system -->
<link rel="stylesheet" href="<?= isset($basePath) ? $basePath : '' ?>style.css">
</head>
<body>

<?php if ($flash): ?>
<div class="alert-toast alert-<?= h($flash['type']) ?>" id="flashAlert">
    <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?>"></i>
    <span><?= h($flash['message']) ?></span>
    <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
</div>
<?php endif; ?>

<div class="app-shell">
