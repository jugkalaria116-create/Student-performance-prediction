<?php
/**
 * includes/functions.php
 * -----------------------------------------------------------
 * Shared helper functions used across multiple pages:
 *   - the prediction formula
 *   - performance classification
 *   - CSRF token generation/validation
 *   - small formatting/escaping helpers
 * -----------------------------------------------------------
 */

// Model version stored with every prediction. Change this string if
// you ever tweak the formula, so old and new predictions stay traceable.
define('MODEL_VERSION', 'v1.0');

/**
 * Escape a string for safe HTML output.
 * Short wrapper around htmlspecialchars() so we don't repeat the
 * same three arguments everywhere.
 */
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Calculate predicted marks from the five input factors.
 *
 * Study hours (0-24) are normalised to a 0-100 scale first,
 * because the other four inputs are already percentages.
 *
 * Weights:
 *   Study Hours Score  20%
 *   Attendance         20%
 *   Previous Marks     30%
 *   Assignment Marks   15%
 *   Internal Marks     15%
 *
 * @return float predicted marks, rounded to 2 decimals, clamped 0-100
 */
function calculatePrediction(
    float $studyHoursPerDay,
    float $attendance,
    float $previousMarks,
    float $assignmentMarks,
    float $internalMarks
): float {
    // Normalise study hours (max useful value treated as 10 hrs/day = 100)
    $studyHoursScore = ($studyHoursPerDay / 10) * 100;
    if ($studyHoursScore > 100) {
        $studyHoursScore = 100;
    }

    $prediction =
        ($studyHoursScore * 0.20) +
        ($attendance       * 0.20) +
        ($previousMarks    * 0.30) +
        ($assignmentMarks  * 0.15) +
        ($internalMarks    * 0.15);

    // Safety clamp - formula shouldn't exceed these, but guard anyway
    if ($prediction < 0)   { $prediction = 0; }
    if ($prediction > 100) { $prediction = 100; }

    return round($prediction, 2);
}

/**
 * Classify predicted marks into a performance band.
 */
function classifyPerformance(float $predictedMarks): string
{
    if ($predictedMarks >= 80) return 'Excellent';
    if ($predictedMarks >= 60) return 'Good';
    if ($predictedMarks >= 40) return 'Average';
    return 'Poor';
}

/**
 * Determine Pass/Fail from predicted marks.
 */
function determineResult(float $predictedMarks): string
{
    return $predictedMarks >= 40 ? 'Pass' : 'Fail';
}

/**
 * Return the CSS class used for a status badge (performance or result).
 */
function badgeClass(string $status): string
{
    $map = [
        'Excellent' => 'badge badge-excellent',
        'Good'      => 'badge badge-good',
        'Average'   => 'badge badge-average',
        'Poor'      => 'badge badge-poor',
        'Pass'      => 'badge badge-pass',
        'Fail'      => 'badge badge-fail',
    ];
    return $map[$status] ?? 'badge';
}

/* ---------------------------------------------------------------
 * CSRF protection
 * We use one token per session (simple, works fine even with
 * multiple tabs open, which matters for a live demo).
 * ------------------------------------------------------------- */

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field for use inside a <form>.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

/**
 * Validate a submitted CSRF token. Call this at the top of every
 * POST handler before touching the database.
 */
function verifyCsrf(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submitted);
}

/**
 * Flash messages: store a message in the session, show it once,
 * then remove it. Used for "Student added successfully" style alerts.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
