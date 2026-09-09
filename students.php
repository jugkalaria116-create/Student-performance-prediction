<?php
/**
 * students.php
 * -----------------------------------------------------------
 * Lists all students with search + filter, and shows each
 * student's latest prediction (correctly picked using the most
 * recent created_at, since multiple predictions can exist).
 * -----------------------------------------------------------
 */
$pageTitle = 'Students';
$pageDescription = 'Manage student records, search, and filter.';
$activePage = 'students';
require_once __DIR__ . '/includes/header.php';

// ---- Read filters from GET (safe: used only in prepared statement) ----
$search = trim($_GET['search'] ?? '');
$courseFilter = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
$semesterFilter = filter_input(INPUT_GET, 'semester', FILTER_VALIDATE_INT);

// ---- Build query dynamically but safely with prepared statement placeholders ----
// "Latest prediction" per student is found with a correlated subquery that
// picks the performance row with the most recent created_at (ties broken by id).
$sql = "
    SELECT
        s.id, s.name, s.email, s.semester,
        c.course_name,
        latest.predicted_marks, latest.performance, latest.result
    FROM students s
    LEFT JOIN courses c ON c.id = s.course_id
    LEFT JOIN student_performance latest
        ON latest.id = (
            SELECT sp2.id FROM student_performance sp2
            WHERE sp2.student_id = s.id
            ORDER BY sp2.created_at DESC, sp2.id DESC
            LIMIT 1
        )
    WHERE 1 = 1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (s.name LIKE ? OR s.email LIKE ?) ";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($courseFilter) {
    $sql .= " AND s.course_id = ? ";
    $params[] = $courseFilter;
}
if ($semesterFilter) {
    $sql .= " AND s.semester = ? ";
    $params[] = $semesterFilter;
}

$sql .= " ORDER BY s.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Courses for the filter dropdown
$courses = $pdo->query("SELECT id, course_name FROM courses ORDER BY course_name")->fetchAll();

require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="card-box" style="margin-bottom:20px;">
        <div class="filter-bar">
            <form method="GET" action="students.php" style="display:flex; gap:12px; flex-wrap:wrap; flex:1;">
                <div class="form-group" style="flex:2; min-width:220px;">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" class="form-control"
                           placeholder="Search by name or email" value="<?= h($search) ?>">
                </div>
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select id="course_id" name="course_id" class="form-control">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= $courseFilter == $c['id'] ? 'selected' : '' ?>>
                                <?= h($c['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester" class="form-control">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= $semesterFilter == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group" style="justify-content:flex-end;">
                    <label>&nbsp;</label>
                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn btn-outline"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="students.php" class="btn btn-plain">Reset</a>
                    </div>
                </div>
            </form>
            <a href="add_student.php" class="btn btn-primary-solid"><i class="bi bi-person-plus"></i> Add Student</a>
        </div>
    </div>

    <div class="table-wrap">
        <?php if (empty($students)): ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                No students found. <a href="add_student.php">Add your first student</a>.
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Email</th><th>Course</th><th>Semester</th>
                    <th>Latest Prediction</th><th>Performance</th><th>Result</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td>#<?= (int) $s['id'] ?></td>
                    <td><?= h($s['name']) ?></td>
                    <td><?= h($s['email'] ?? '—') ?></td>
                    <td><?= h($s['course_name'] ?? '—') ?></td>
                    <td><?= h($s['semester']) ?></td>
                    <td><?= $s['predicted_marks'] !== null ? h($s['predicted_marks']) : '—' ?></td>
                    <td><?= $s['performance'] ? '<span class="' . badgeClass($s['performance']) . '">' . h($s['performance']) . '</span>' : '—' ?></td>
                    <td><?= $s['result'] ? '<span class="' . badgeClass($s['result']) . '">' . h($s['result']) . '</span>' : '—' ?></td>
                    <td class="table-actions">
                        <a class="btn btn-outline btn-sm" href="student_view.php?id=<?= (int) $s['id'] ?>" title="View"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-outline btn-sm" href="edit_student.php?id=<?= (int) $s['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                        <form action="delete_student.php" method="POST" class="js-confirm-delete" data-name="<?= h($s['name']) ?>" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                            <button type="submit" class="btn btn-outline btn-sm" title="Delete" style="color:var(--danger);"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
