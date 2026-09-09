<?php
/**
 * includes/navbar.php
 * -----------------------------------------------------------
 * Top bar shown above the page content area.
 * Each page sets $pageTitle and optionally $pageDescription
 * before including this file.
 * -----------------------------------------------------------
 */
$pageDescription = $pageDescription ?? '';
?>
<header class="topbar">
    <div>
        <h1 class="topbar-title"><?= h($pageTitle) ?></h1>
        <?php if ($pageDescription): ?>
            <p class="topbar-desc"><?= h($pageDescription) ?></p>
        <?php endif; ?>
    </div>
    <div class="topbar-actions">
        <a href="predict.php" class="btn btn-primary-solid">
            <i class="bi bi-plus-lg"></i> New Prediction
        </a>
    </div>
</header>
