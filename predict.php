<?php
/**
 * predict.php
 * -----------------------------------------------------------
 * Shows the prediction form and, on POST, validates the input,
 * calculates the prediction in PHP, inserts it into MySQL, and
 * redirects to result.php?id=X (never trusts values sent back
 * from the browser and never puts prediction data in the URL).
 * -----------------------------------------------------------
 */
$pageTitle = 'Predict Performance';
$pageDescription = 'Enter a student\'s academic factors to generate a predicted score.';
$activePage = 'predict';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$old = [
    'student_id' => '', 'semester' => '', 'study_hours_per_day' => '',
    'attendance' => '', 'previous_marks' => '', 'assignment_marks' => '', 'internal_marks' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. CSRF check first, before touching anything else
    if (!verifyCsrf()) {
        $errors[] = 'Your session expired or the form was tampered with. Please try again.';
    } else {

        // 2. Collect + keep raw values so the form can be re-filled on error
        $old['student_id'] = $_POST['student_id'] ?? '';
        $old['semester'] = $_POST['semester'] ?? '';
        $old['study_hours_per_day'] = $_POST['study_hours_per_day'] ?? '';
        $old['attendance'] = $_POST['attendance'] ?? '';
        $old['previous_marks'] = $_POST['previous_marks'] ?? '';
        $old['assignment_marks'] = $_POST['assignment_marks'] ?? '';
        $old['internal_marks'] = $_POST['internal_marks'] ?? '';

        // 3. Server-side validation (never trust the browser)
        $studentId = filter_var($old['student_id'], FILTER_VALIDATE_INT);
        if ($studentId === false || $studentId <= 0) {
            $errors[] = 'Please select a valid student.';
        } else {
            // Confirm the student actually exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE id = ?");
            $check->execute([$studentId]);
            if ((int) $check->fetchColumn() === 0) {
                $errors[] = 'Selected student does not exist.';
            }
        }

        $semester = filter_var($old['semester'], FILTER_VALIDATE_INT);
        if ($semester === false || $semester < 1 || $semester > 12) {
            $errors[] = 'Semester must be between 1 and 12.';
        }

        $numericFields = [
            'study_hours_per_day' => [0, 24, 'Study hours per day'],
            'attendance'          => [0, 100, 'Attendance'],
            'previous_marks'      => [0, 100, 'Previous marks'],
            'assignment_marks'    => [0, 100, 'Assignment marks'],
            'internal_marks'      => [0, 100, 'Internal marks'],
        ];
        $values = [];
        foreach ($numericFields as $field => [$min, $max, $label]) {
            $val = filter_var($old[$field], FILTER_VALIDATE_FLOAT);
            if ($val === false || $val < $min || $val > $max) {
                $errors[] = "$label must be a number between $min and $max.";
            } else {
                $values[$field] = $val;
            }
        }

        // 4. If everything is valid, calculate + save
        if (empty($errors)) {
            $predicted = calculatePrediction(
                $values['study_hours_per_day'],
                $values['attendance'],
                $values['previous_marks'],
                $values['assignment_marks'],
                $values['internal_marks']
            );
            $performance = classifyPerformance($predicted);
            $result = determineResult($predicted);

            try {
                $insert = $pdo->prepare("
                    INSERT INTO student_performance
                        (student_id, semester, study_hours_per_day, attendance, previous_marks,
                         assignment_marks, internal_marks, predicted_marks, performance, result, model_version)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert->execute([
                    $studentId, $semester,
                    $values['study_hours_per_day'], $values['attendance'], $values['previous_marks'],
                    $values['assignment_marks'], $values['internal_marks'],
                    $predicted, $performance, $result, MODEL_VERSION,
                ]);

                $newId = (int) $pdo->lastInsertId();
                setFlash('success', 'Prediction generated successfully.');
                header('Location: result.php?id=' . $newId);
                exit;

            } catch (PDOException $e) {
                error_log('Prediction insert failed: ' . $e->getMessage());
                $errors[] = 'Unable to save the prediction. Please try again.';
            }
        }
    }
}

// Load students for the dropdown (with course name for clarity)
$students = $pdo->query("
    SELECT s.id, s.name, c.course_name
    FROM students s
    LEFT JOIN courses c ON c.id = s.course_id
    ORDER BY s.name
")->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="card-box form-card">
        <h2 class="section-title"><i class="bi bi-graph-up-arrow"></i> Prediction Form</h2>

        <?php if (!empty($errors)): ?>
            <div class="inline-alert error">
                <ul style="margin:0; padding-left:18px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= h($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($students)): ?>
            <div class="inline-alert info">
                No students found. <a href="students.php">Add a student first</a> before creating a prediction.
            </div>
        <?php else: ?>

        <form method="POST" action="predict.php" id="predictForm" novalidate>
            <?= csrfField() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="student_id">Student</label>
                    <select class="form-control" id="student_id" name="student_id" required>
                        <option value="">-- Select student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= (string) $old['student_id'] === (string) $s['id'] ? 'selected' : '' ?>>
                                <?= h($s['name']) ?><?= $s['course_name'] ? ' (' . h($s['course_name']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-error" id="student_id_error"></span>
                </div>

                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select class="form-control" id="semester" name="semester" required>
                        <option value="">-- Select semester --</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= (string) $old['semester'] === (string) $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <span class="hint">Validated between 1 and 12 for general academic use.</span>
                    <span class="form-error" id="semester_error"></span>
                </div>

                <div class="form-group">
                    <label for="study_hours_per_day">Study Hours Per Day</label>
                    <input type="number" class="form-control" id="study_hours_per_day" name="study_hours_per_day"
                           min="0" max="24" step="0.01" value="<?= h($old['study_hours_per_day']) ?>" required>
                    <span class="hint">0 to 24 hours</span>
                    <span class="form-error" id="study_hours_per_day_error"></span>
                </div>

                <div class="form-group">
                    <label for="attendance">Attendance (%)</label>
                    <input type="number" class="form-control" id="attendance" name="attendance"
                           min="0" max="100" step="0.01" value="<?= h($old['attendance']) ?>" required>
                    <span class="form-error" id="attendance_error"></span>
                </div>

                <div class="form-group">
                    <label for="previous_marks">Previous Marks (%)</label>
                    <input type="number" class="form-control" id="previous_marks" name="previous_marks"
                           min="0" max="100" step="0.01" value="<?= h($old['previous_marks']) ?>" required>
                    <span class="form-error" id="previous_marks_error"></span>
                </div>

                <div class="form-group">
                    <label for="assignment_marks">Assignment Marks (%)</label>
                    <input type="number" class="form-control" id="assignment_marks" name="assignment_marks"
                           min="0" max="100" step="0.01" value="<?= h($old['assignment_marks']) ?>" required>
                    <span class="form-error" id="assignment_marks_error"></span>
                </div>

                <div class="form-group">
                    <label for="internal_marks">Internal Marks (%)</label>
                    <input type="number" class="form-control" id="internal_marks" name="internal_marks"
                           min="0" max="100" step="0.01" value="<?= h($old['internal_marks']) ?>" required>
                    <span class="form-error" id="internal_marks_error"></span>
                </div>

                <div class="form-group full">
                    <label>Live Preview (approximate, calculated on server for real)</label>
                    <div id="livePreview" class="hint">Fill in the fields above to see an estimate.</div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary-solid"><i class="bi bi-magic"></i> Predict Performance</button>
                <a href="index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
