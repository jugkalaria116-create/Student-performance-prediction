<?php
/**
 * analytics.php
 * -----------------------------------------------------------
 * Dedicated analytics page (separate from the dashboard).
 * All values and chart data come live from MySQL.
 * -----------------------------------------------------------
 */
$pageTitle = 'Analytics';
$pageDescription = 'Deeper insights into student performance trends.';
$activePage = 'analytics';
require_once __DIR__ . '/includes/header.php';

$totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalPredictions = (int) $pdo->query("SELECT COUNT(*) FROM student_performance")->fetchColumn();

$avgAttendance = $pdo->query("SELECT AVG(attendance) FROM student_performance")->fetchColumn();
$avgPrevious = $pdo->query("SELECT AVG(previous_marks) FROM student_performance")->fetchColumn();
$avgPredicted = $pdo->query("SELECT AVG(predicted_marks) FROM student_performance")->fetchColumn();

$passCount = (int) $pdo->query("SELECT COUNT(*) FROM student_performance WHERE result = 'Pass'")->fetchColumn();
$failCount = (int) $pdo->query("SELECT COUNT(*) FROM student_performance WHERE result = 'Fail'")->fetchColumn();
$passPct = $totalPredictions > 0 ? round(($passCount / $totalPredictions) * 100, 1) : 0;
$failPct = $totalPredictions > 0 ? round(($failCount / $totalPredictions) * 100, 1) : 0;

// Performance distribution
$perfStmt = $pdo->query("SELECT performance, COUNT(*) AS total FROM student_performance WHERE performance IS NOT NULL GROUP BY performance");
$perfCounts = ['Excellent' => 0, 'Good' => 0, 'Average' => 0, 'Poor' => 0];
foreach ($perfStmt->fetchAll() as $row) { $perfCounts[$row['performance']] = (int) $row['total']; }

// Course-wise average
$courseStmt = $pdo->query("
    SELECT c.course_name, AVG(sp.predicted_marks) AS avg_marks
    FROM student_performance sp
    JOIN students s ON s.id = sp.student_id
    JOIN courses c ON c.id = s.course_id
    GROUP BY c.course_name ORDER BY c.course_name
");
$courseRows = $courseStmt->fetchAll();
$courseLabels = array_column($courseRows, 'course_name');
$courseAverages = array_map(fn($v) => round((float) $v, 2), array_column($courseRows, 'avg_marks'));

// Semester-wise average
$semStmt = $pdo->query("
    SELECT semester, AVG(predicted_marks) AS avg_marks
    FROM student_performance
    GROUP BY semester ORDER BY semester
");
$semRows = $semStmt->fetchAll();
$semLabels = array_map(fn($v) => 'Sem ' . $v, array_column($semRows, 'semester'));
$semAverages = array_map(fn($v) => round((float) $v, 2), array_column($semRows, 'avg_marks'));

require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
            <div><div class="stat-value"><?= $totalStudents ?></div><div class="stat-label">Total Students</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan"><i class="bi bi-clipboard-data"></i></div>
            <div><div class="stat-value"><?= $totalPredictions ?></div><div class="stat-label">Total Predictions</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-calendar-check"></i></div>
            <div><div class="stat-value"><?= $avgAttendance !== null ? round((float)$avgAttendance,2) : 0 ?>%</div><div class="stat-label">Avg Attendance</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-journal-text"></i></div>
            <div><div class="stat-value"><?= $avgPrevious !== null ? round((float)$avgPrevious,2) : 0 ?>%</div><div class="stat-label">Avg Previous Marks</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-graph-up"></i></div>
            <div><div class="stat-value"><?= $avgPredicted !== null ? round((float)$avgPredicted,2) : 0 ?></div><div class="stat-label">Avg Predicted Marks</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-percent"></i></div>
            <div><div class="stat-value"><?= h($passPct) ?>%</div><div class="stat-label">Pass Percentage</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-percent"></i></div>
            <div><div class="stat-value"><?= h($failPct) ?>%</div><div class="stat-label">Fail Percentage</div></div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="card-box chart-card">
            <h2 class="section-title"><i class="bi bi-pie-chart"></i> Pass vs Fail Distribution</h2>
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
        <div class="card-box chart-card">
            <h2 class="section-title"><i class="bi bi-calendar3"></i> Semester-wise Average Marks</h2>
            <canvas id="semesterChart"></canvas>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('passFailChart'), {
        type: 'doughnut',
        data: { labels: ['Pass', 'Fail'], datasets: [{ data: [<?= $passCount ?>, <?= $failCount ?>], backgroundColor: ['#16a34a', '#dc2626'] }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('performanceChart'), {
        type: 'bar',
        data: {
            labels: ['Excellent', 'Good', 'Average', 'Poor'],
            datasets: [{
                data: [<?= $perfCounts['Excellent'] ?>, <?= $perfCounts['Good'] ?>, <?= $perfCounts['Average'] ?>, <?= $perfCounts['Poor'] ?>],
                backgroundColor: ['#16a34a', '#2f5fdb', '#d97706', '#dc2626']
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('courseChart'), {
        type: 'bar',
        data: { labels: <?= json_encode($courseLabels) ?>, datasets: [{ data: <?= json_encode($courseAverages) ?>, backgroundColor: '#2f5fdb' }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, max: 100 } } }
    });

    new Chart(document.getElementById('semesterChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($semLabels) ?>,
            datasets: [{ data: <?= json_encode($semAverages) ?>, borderColor: '#0891b2', backgroundColor: 'rgba(8,145,178,0.12)', fill: true, tension: 0.3 }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
    });
});
</script>
