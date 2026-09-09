<?php
/**
 * student_view.php
 * -----------------------------------------------------------
 * Single dedicated student details page (replaces separate
 * "view" and "view performance" pages). Shows profile info,
 * a performance summary, the full prediction history table,
 * and a semester-vs-predicted-marks line chart.
 *
 * Because multiple predictions can exist per semester, EVERY
 * prediction is plotted as its own point on the chart (ordered
 * by created_at), rather than collapsing to one point per semester.
 * -----------------------------------------------------------
 */
$pageTitle = 'Student Details';
$activePage = 'students';
require_once __DIR__ . '/includes/header.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    setFlash('error', 'Invalid student ID.');
    header('Location: students.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.*, c.course_name
    FROM students s
    LEFT JOIN courses c ON c.id = s.course_id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('error', 'Student not found.');
    header('Location: students.php');
    exit;
}

// Full performance history, oldest to newest (semester, then created_at)
$historyStmt = $pdo->prepare("
    SELECT * FROM student_performance
    WHERE student_id = ?
    ORDER BY semester ASC, created_at ASC
");
$historyStmt->execute([$id]);
$history = $historyStmt->fetchAll();

// Summary stats
$totalPredictions = count($history);
$passCount = count(array_filter($history, fn($r) => $r['result'] === 'Pass'));
$failCount = $totalPredictions - $passCount;
$avgMarks = $totalPredictions > 0
    ? round(array_sum(array_column($history, 'predicted_marks')) / $totalPredictions, 2)
    : 0;
$bestMarks = $totalPredictions > 0 ? max(array_column($history, 'predicted_marks')) : 0;
$latest = $totalPredictions > 0 ? end($history) : null;

// Chart data: every prediction as its own point, labelled "Sem X (date)"
$chartLabels = [];
$chartValues = [];
foreach ($history as $row) {
    $chartLabels[] = 'Sem ' . $row['semester'] . ' (' . date('d M', strtotime($row['created_at'])) . ')';
    $chartValues[] = (float) $row['predicted_marks'];
}

$initials = strtoupper(substr($student['name'], 0, 1));

require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
    <?php $pageDescription = 'Full profile and prediction history.'; require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="card-box" style="margin-bottom:20px;">
        <div class="profile-header">
            <div class="profile-avatar"><?= h($initials) ?></div>
            <div>
                <h2 style="margin:0 0 4px; font-size:19px;"><?= h($student['name']) ?></h2>
                <div class="hint">
                    <?= h($student['course_name'] ?? 'No course assigned') ?> &middot;
                    Semester <?= h($student['semester']) ?> &middot;
                    <?= h($student['email'] ?? 'No email') ?>
                </div>
            </div>
            <div style="margin-left:auto;">
                <a href="edit_student.php?id=<?= (int) $student['id'] ?>" class="btn btn-outline btn-sm"><i class="bi bi-pencil"></i> Edit</a>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-item">
                <div class="value"><?= $totalPredictions ?></div>
                <div class="label">Predictions</div>
            </div>
            <div class="summary-item">
                <div class="value"><?= h($avgMarks) ?></div>
                <div class="label">Average Marks</div>
            </div>
            <div class="summary-item">
                <div class="value"><?= h($bestMarks) ?></div>
                <div class="label">Best Marks</div>
            </div>
            <div class="summary-item">
                <div class="value"><?= $latest ? h($latest['predicted_marks']) : '—' ?></div>
                <div class="label">Latest Prediction</div>
            </div>
            <div class="summary-item">
                <div class="value" style="color:var(--success);"><?= $passCount ?></div>
                <div class="label">Pass Count</div>
            </div>
            <div class="summary-item">
                <div class="value" style="color:var(--danger);"><?= $failCount ?></div>
                <div class="label">Fail Count</div>
            </div>
        </div>
    </div>

    <?php if (empty($history)): ?>
        <div class="card-box">
            <div class="empty-state">
                <i class="bi bi-graph-up"></i>
                No performance records yet for this student.
                <br><a href="predict.php">Create a prediction</a> to get started.
            </div>
        </div>
    <?php else: ?>

    <div class="card-box chart-card" style="margin-bottom:20px;">
        <h2 class="section-title"><i class="bi bi-graph-up"></i> Semester vs Predicted Marks</h2>
        <canvas id="historyChart"></canvas>
    </div>

    <h2 class="section-title"><i class="bi bi-clock-history"></i> Performance History</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Semester</th><th>Study Hrs</th><th>Attendance</th><th>Previous</th>
                    <th>Assignment</th><th>Internal</th><th>Predicted</th>
                    <th>Performance</th><th>Result</th><th>Model</th><th>Date</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $row): ?>
                <tr>
                    <td><?= h($row['semester']) ?></td>
                    <td><?= h($row['study_hours_per_day']) ?></td>
                    <td><?= h($row['attendance']) ?>%</td>
                    <td><?= h($row['previous_marks']) ?>%</td>
                    <td><?= h($row['assignment_marks']) ?>%</td>
                    <td><?= h($row['internal_marks']) ?>%</td>
                    <td><strong><?= h($row['predicted_marks']) ?></strong></td>
                    <td><span class="<?= badgeClass($row['performance']) ?>"><?= h($row['performance']) ?></span></td>
                    <td><span class="<?= badgeClass($row['result']) ?>"><?= h($row['result']) ?></span></td>
                    <td><?= h($row['model_version']) ?></td>
                    <td><?= h(date('d M Y', strtotime($row['created_at']))) ?></td>
                    <td><a class="btn btn-outline btn-sm" href="result.php?id=<?= (int) $row['id'] ?>"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php if (!empty($history)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('historyChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Predicted Marks',
                data: <?= json_encode($chartValues) ?>,
                borderColor: '#2f5fdb',
                backgroundColor: 'rgba(47,95,219,0.12)',
                fill: true,
                tension: 0.3,
                pointRadius: 4
            }]
        },
        options: {
            scales: { y: { beginAtZero: true, max: 100 } },
            plugins: { legend: { display: false } }
        }
    });
});
</script>
<?php endif; ?>
