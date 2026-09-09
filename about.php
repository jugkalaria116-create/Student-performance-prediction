<?php
$pageTitle = 'About';
$pageDescription = 'Project information and prediction model disclaimer.';
$activePage = 'about';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="card-box" style="margin-bottom:20px;">
        <h2 class="section-title"><i class="bi bi-mortarboard-fill"></i> Student Performance Predictor & Analytics System</h2>
        <p><strong>Purpose:</strong> This system helps a teacher or administrator record a student's academic
        factors — study hours, attendance, previous marks, assignment marks, and internal marks — and
        generates an estimated performance score, classification, and pass/fail result. Every prediction
        is saved so performance can be tracked across semesters.</p>

        <p><strong>Technology used:</strong></p>
        <div class="about-tech-list">
            <span class="tech-pill">PHP 8</span>
            <span class="tech-pill">MySQL</span>
            <span class="tech-pill">PDO</span>
            <span class="tech-pill">HTML5</span>
            <span class="tech-pill">CSS3</span>
            <span class="tech-pill">JavaScript</span>
            <span class="tech-pill">Bootstrap 5</span>
            <span class="tech-pill">Chart.js</span>
        </div>
    </div>

    <div class="card-box" style="margin-bottom:20px;">
        <h2 class="section-title"><i class="bi bi-cpu"></i> Prediction Model: Weighted-Factor Model <?= MODEL_VERSION ?></h2>
        <p>Predicted Marks are calculated using a fixed weighted formula:</p>
        <ul>
            <li>Study Hours Score (normalised) &times; 20%</li>
            <li>Attendance &times; 20%</li>
            <li>Previous Marks &times; 30%</li>
            <li>Assignment Marks &times; 15%</li>
            <li>Internal Marks &times; 15%</li>
        </ul>
        <p>Study hours (0&ndash;24) are normalised to a 0&ndash;100 scale first, since the other four
        inputs are already percentages, using: <code>(study_hours / 10) &times; 100</code>, capped at 100.</p>
    </div>

    <div class="card-box">
        <h2 class="section-title"><i class="bi bi-exclamation-triangle"></i> Disclaimer</h2>
        <div class="disclaimer-box">
            <i class="bi bi-info-circle"></i>
            <span>The prediction model is designed for academic demonstration purposes. It uses predefined
            weighted factors and is <strong>not</strong> a scientifically validated or production-grade
            machine learning model. Results should not be used for real academic decision-making.</span>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
