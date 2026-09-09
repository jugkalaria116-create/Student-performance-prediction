<?php
/**
 * index.php - Dashboard
 * -----------------------------------------------------------
 * Shows overall statistics, a table of recent predictions,
 * and summary charts. Everything here is pulled live from MySQL.
 * -----------------------------------------------------------
 */
$pageTitle = 'Dashboard';
$pageDescription = 'Overview of students, predictions, and performance trends.';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';

// ---- Stat cards ----
$totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalPredictions = (int) $pdo->query("SELECT COUNT(*) FROM student_performance")->fetchColumn();
$passCount = (int) $pdo->query("SELECT COUNT(*) FROM student_performance WHERE result = 'Pass'")->fetchColumn();
$failCount = (int) $pdo->query("SELECT COUNT(*) FROM student_performance WHERE result = 'Fail'")->fetchColumn();
$avgPredicted = $pdo->query("SELECT AVG(predicted_marks) FROM student_performance")->fetchColumn();
$avgPredicted = $avgPredicted !== null ? round((float) $avgPredicted, 2) : 0;
$passPercentage = $totalPredictions > 0 ? round(($passCount / $totalPredictions) * 100, 1) : 0;

// ---- Recent predictions table (latest 8) ----
$recentStmt = $pdo->query("
    SELECT sp.id, s.name AS student_name, c.course_name, sp.semester,
           sp.predicted_marks, sp.performance, sp.result, sp.model_version, sp.created_at
    FROM student_performance sp
    JOIN students s ON s.id = sp.student_id
    LEFT JOIN courses c ON c.id = s.course_id
    ORDER BY sp.created_at DESC
    LIMIT 8
");
$recentPredictions = $recentStmt->fetchAll();

// ---- Chart data: performance distribution ----
$perfStmt = $pdo->query("
    SELECT performance, COUNT(*) AS total
    FROM student_performance
    WHERE performance IS NOT NULL
    GROUP BY performance
");
$perfCounts = ['Excellent' => 0, 'Good' => 0, 'Average' => 0, 'Poor' => 0];
foreach ($perfStmt->fetchAll() as $row) {
    $perfCounts[$row['performance']] = (int) $row['total'];
}

// ---- Chart data: course-wise average predicted marks ----
$courseStmt = $pdo->query("
    SELECT c.course_name, AVG(sp.predicted_marks) AS avg_marks
    FROM student_performance sp
    JOIN students s ON s.id = sp.student_id
    JOIN courses c ON c.id = s.course_id
    GROUP BY c.course_name
    ORDER BY c.course_name
");
$courseRows = $courseStmt->fetchAll();
$courseLabels = array_column($courseRows, 'course_name');
$courseAverages = array_map(fn($v) => round((float) $v, 2), array_column($courseRows, 'avg_marks'));

require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value"><?= $totalStudents ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan"><i class="bi bi-clipboard-data"></i></div>
            <div>
                <div class="stat-value"><?= $totalPredictions ?></div>
                <div class="stat-label">Total Predictions</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-value"><?= $passCount ?></div>
                <div class="stat-label">Pass Count</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
            <div>
                <div class="stat-value"><?= $failCount ?></div>
                <div class="stat-label">Fail Count</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-graph-up"></i></div>
            <div>
                <div class="stat-value"><?= h($avgPredicted) ?></div>
                <div class="stat-label">Average Predicted Marks</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-percent"></i></div>
            <div>
                <div class="stat-value"><?= h($passPercentage) ?>%</div>
                <div class="stat-label">Pass Percentage</div>
            </div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="card-box chart-card">
            <h2 class="section-title"><i class="bi bi-pie-chart"></i> Pass vs Fail</h2>
            <canvas id="passFailChart"></canvas>
        </div>
        <div class="card-box chart-card">
            <h2 class="section-title"><i class="bi bi-bar-chart"></i> Performance Distribution</h2>
            <canvas id="performanceChart"></canvas>
        </div>
        <div class="card-box chart-card">
            <h2 class="section-title"><i class="bi bi-mortarboard"></i> Course-wise Average Marks</h2>
            <canvas id="courseChart"></canvas>
        </div>
    </div>

    <h2 class="section-title"><i class="bi bi-clock-history"></i> Recent Predictions</h2>
    <div class="table-wrap">
        <?php if (empty($recentPredictions)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                No predictions yet. <a href="predict.php">Create your first prediction</a>.
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th><th>Course</th><th>Semester</th>
                    <th>Predicted Marks</th><th>Performance</th><th>Result</th>
                    <th>Model</th><th>Date</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentPredictions as $row): ?>
                <tr>
                    <td><?= h($row['student_name']) ?></td>
                    <td><?= h($row['course_name'] ?? '—') ?></td>
                    <td><?= h($row['semester']) ?></td>
                    <td><?= h($row['predicted_marks']) ?></td>
                    <td><span class="<?= badgeClass($row['performance']) ?>"><?= h($row['performance']) ?></span></td>
                    <td><span class="<?= badgeClass($row['result']) ?>"><?= h($row['result']) ?></span></td>
                    <td><?= h($row['model_version']) ?></td>
                    <td><?= h(date('d M Y', strtotime($row['created_at']))) ?></td>
                    <td class="table-actions">
                        <a class="btn btn-outline btn-sm" href="result.php?id=<?= (int) $row['id'] ?>"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Pass vs Fail doughnut chart
    new Chart(document.getElementById('passFailChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pass', 'Fail'],
            datasets: [{
                data: [<?= $passCount ?>, <?= $failCount ?>],
                backgroundColor: ['#16a34a', '#dc2626']
            }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });

    // Performance distribution bar chart
    new Chart(document.getElementById('performanceChart'), {
        type: 'bar',
        data: {
            labels: ['Excellent', 'Good', 'Average', 'Poor'],
            datasets: [{
                label: 'Students',
                data: [
                    <?= $perfCounts['Excellent'] ?>,
                    <?= $perfCounts['Good'] ?>,
                    <?= $perfCounts['Average'] ?>,
                    <?= $perfCounts['Poor'] ?>
                ],
                backgroundColor: ['#16a34a', '#2f5fdb', '#d97706', '#dc2626']
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    // Course-wise average marks
    new Chart(document.getElementById('courseChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($courseLabels) ?>,
            datasets: [{
                label: 'Average Predicted Marks',
                data: <?= json_encode($courseAverages) ?>,
                backgroundColor: '#2f5fdb'
            }]
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, max: 100 } }
        }
    });
});
</script>
