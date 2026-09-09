<?php
/**
 * includes/sidebar.php
 * -----------------------------------------------------------
 * Left navigation sidebar. $activePage is set by each page
 * before including this file, so the correct nav link gets
 * highlighted (e.g. $activePage = 'dashboard').
 * -----------------------------------------------------------
 */
$activePage = $activePage ?? '';

function navLink(string $key, string $href, string $icon, string $label, string $active): string
{
    $isActive = ($key === $active) ? ' active' : '';
    return '<a href="' . $href . '" class="nav-link' . $isActive . '">
                <i class="bi ' . $icon . '"></i>
                <span>' . $label . '</span>
            </a>';
}
?>
<button class="sidebar-toggle d-lg-none" id="sidebarToggle" aria-label="Toggle menu">
    <i class="bi bi-list"></i>
</button>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-mortarboard-fill"></i>
        <div>
            <span class="brand-title">Student Performance</span>
            <span class="brand-subtitle">Predictor & Analytics</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?= navLink('dashboard', 'index.php', 'bi-speedometer2', 'Dashboard', $activePage) ?>
        <?= navLink('predict', 'predict.php', 'bi-graph-up-arrow', 'Predict Performance', $activePage) ?>
        <?= navLink('students', 'students.php', 'bi-people', 'Students', $activePage) ?>
        <?= navLink('analytics', 'analytics.php', 'bi-bar-chart-line', 'Analytics', $activePage) ?>
        <?= navLink('about', 'about.php', 'bi-info-circle', 'About', $activePage) ?>
    </nav>

    <div class="sidebar-footer">
        <i class="bi bi-shield-check"></i>
        <span>Local demo &middot; no login required</span>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
