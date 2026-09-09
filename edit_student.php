<?php
/**
 * edit_student.php
 * -----------------------------------------------------------
 * Form to edit an existing student's name, email, course, semester.
 * Editing a student never touches their performance history.
 * -----------------------------------------------------------
 */
$pageTitle = 'Edit Student';
$activePage = 'students';
require_once __DIR__ . '/includes/header.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    setFlash('error', 'Invalid student ID.');
    header('Location: students.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('error', 'Student not found.');
    header('Location: students.php');
    exit;
}

$errors = [];
$old = [
    'name' => $student['name'],
    'email' => $student['email'] ?? '',
    'course_id' => $student['course_id'],
    'semester' => $student['semester'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Your session expired or the form was tampered with. Please try again.';
    } else {
        $old['name'] = trim($_POST['name'] ?? '');
        $old['email'] = trim($_POST['email'] ?? '');
        $old['course_id'] = $_POST['course_id'] ?? '';
        $old['semester'] = $_POST['semester'] ?? '';

        if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) {
            $errors[] = 'Name is required and must be under 100 characters.';
        }

        $emailToStore = null;
        if ($old['email'] !== '') {
            if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            } else {
                $emailToStore = $old['email'];
                // Unique check excludes the current student's own row
                $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE email = ? AND id != ?");
                $check->execute([$emailToStore, $id]);
                if ((int) $check->fetchColumn() > 0) {
                    $errors[] = 'This email is already used by another student.';
                }
            }
        }

        $courseId = filter_var($old['course_id'], FILTER_VALIDATE_INT);
        if ($courseId === false || $courseId <= 0) {
            $errors[] = 'Please select a course.';
        } else {
            $check = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE id = ?");
            $check->execute([$courseId]);
            if ((int) $check->fetchColumn() === 0) {
                $errors[] = 'Selected course does not exist.';
            }
        }

        $semester = filter_var($old['semester'], FILTER_VALIDATE_INT);
        if ($semester === false || $semester < 1 || $semester > 12) {
            $errors[] = 'Semester must be between 1 and 12.';
        }

        if (empty($errors)) {
            try {
                $update = $pdo->prepare("UPDATE students SET name = ?, email = ?, course_id = ?, semester = ? WHERE id = ?");
                $update->execute([$old['name'], $emailToStore, $courseId, $semester, $id]);
                setFlash('success', 'Student updated successfully.');
                header('Location: students.php');
                exit;
            } catch (PDOException $e) {
                error_log('Update student failed: ' . $e->getMessage());
                $errors[] = 'Unable to update the student. Please check the entered information.';
            }
        }
    }
}

$courses = $pdo->query("SELECT id, course_name FROM courses ORDER BY course_name")->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
    <?php $pageDescription = 'Update this student\'s information.'; require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="card-box form-card">
        <h2 class="section-title"><i class="bi bi-pencil-square"></i> Edit Student #<?= (int) $id ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="inline-alert error">
                <ul style="margin:0; padding-left:18px;">
                    <?php foreach ($errors as $err): ?><li><?= h($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit_student.php?id=<?= (int) $id ?>" id="studentForm" novalidate>
            <?= csrfField() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= h($old['name']) ?>" required>
                    <span class="form-error" id="name_error"></span>
                </div>
                <div class="form-group">
                    <label for="email">Email (optional)</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= h($old['email']) ?>">
                    <span class="form-error" id="email_error"></span>
                </div>
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select class="form-control" id="course_id" name="course_id" required>
                        <option value="">-- Select course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (string) $old['course_id'] === (string) $c['id'] ? 'selected' : '' ?>>
                                <?= h($c['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select class="form-control" id="semester" name="semester" required>
                        <option value="">-- Select semester --</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= (string) $old['semester'] === (string) $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <span class="form-error" id="semester_error"></span>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary-solid"><i class="bi bi-check-lg"></i> Update Student</button>
                <a href="students.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
