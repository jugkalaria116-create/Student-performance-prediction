<?php
/**
 * result.php
 * -----------------------------------------------------------
 * Reads ?id=X, validates it, re-queries the database (never
 * trusts data passed through the URL/GET), and displays the
 * saved prediction.
 * -----------------------------------------------------------
 */
$pageTitle = 'Prediction Result';
$activePage = 'predict';
require_once __DIR__ . '/includes/header.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$record = null;
if ($id !== false && $id !== null && $id > 0) {
    $stmt = $pdo->prepare("
        SELECT sp.*, s.name AS student_name, s.email AS student_email, c.course_name
        FROM student_performance sp
        JOIN students s ON s.id = sp.student_id
        LEFT JOIN courses c ON c.id = s.course_id
        WHERE sp.id = ?
    ");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
}

require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
    <?php $pageDescription = ''; require_once __DIR__ . '/includes/navbar.php'; ?>

    <?php if (!$record): ?>
        <div class="card-box">
            <div class="empty-state">
                <i class="bi bi-exclamation-triangle"></i>
                Prediction record not found. It may have been deleted, or the link is invalid.
            </div>
            <div class="form-actions" style="justify-content:center;">
                <a href="predict.php" class="btn btn-primary-solid">New Prediction</a>
                <a href="index.php" class="btn btn-outline">Back to Dashboard</a>
            </div>
        </div>
    <?php else: ?>

        <div class="result-hero">
            <div class="hint" style="text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); font-weight:600;">
                Predicted Marks
            </div>
            <div class="result-score"><?= h($record['predicted_marks']) ?> <span>/ 100</span></div>
            <div class="result-badges">
                <span class="<?= badgeClass($record['performance']) ?>"><?= h($record['performance']) ?></span>
                <span class="<?= badgeClass($record['result']) ?>"><?= strtoupper(h($record['result'])) ?></span>
            </div>
        </div>

        <div class="card-box" style="margin-bottom:20px;">
            <h2 class="section-title"><i class="bi bi-person-badge"></i> Student</h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="value"><?= h($record['student_name']) ?></div>
                    <div class="label">Name</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= h($record['course_name'] ?? '—') ?></div>
                    <div class="label">Course</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= h($record['semester']) ?></div>
                    <div class="label">Semester</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= h($record['student_email'] ?? '—') ?></div>
                    <div class="label">Email</div>
                </div>
            </div>
        </div>

        <div class="card-box" style="margin-bottom:20px;">
            <h2 class="section-title"><i class="bi bi-input-cursor-text"></i> Input Summary</h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="value"><?= h($record['study_hours_per_day']) ?></div>
                    <div class="label">Study Hours / Day</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= h($record['attendance']) ?>%</div>
                    <div class="label">Attendance</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= h($record['previous_marks']) ?>%</div>
                    <div class="label">Previous Marks</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= h($record['assignment_marks']) ?>%</div>
                    <div class="label">Assignment Marks</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= h($record['internal_marks']) ?>%</div>
                    <div class="label">Internal Marks</div>
                </div>
            </div>
        </div>

        <div class="card-box">
            <h2 class="section-title"><i class="bi bi-clipboard-check"></i> Prediction Details</h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="value"><?= h($record['model_version']) ?></div>
                    <div class="label">Model Version</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= h(date('d M Y, h:i A', strtotime($record['created_at']))) ?></div>
                    <div class="label">Created</div>
                </div>
            </div>

            <div class="disclaimer-box">
                <i class="bi bi-info-circle"></i>
                <span>This prediction was generated using the educational Weighted-Factor Prediction Model <?= h($record['model_version']) ?>. It is not a scientifically validated machine-learning model.</span>
            </div>

            <div class="form-actions">
                <a href="predict.php" class="btn btn-primary-solid"><i class="bi bi-plus-lg"></i> New Prediction</a>
                <a href="student_view.php?id=<?= (int) $record['student_id'] ?>" class="btn btn-outline"><i class="bi bi-person"></i> View Student</a>
                <a href="index.php" class="btn btn-outline"><i class="bi bi-speedometer2"></i> Back to Dashboard</a>
            </div>
        </div>

    <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
