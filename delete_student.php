<?php
/**
 * delete_student.php
 * -----------------------------------------------------------
 * Deletes a student. Only accepts POST requests (never GET),
 * and requires a valid CSRF token. Because student_performance
 * has ON DELETE CASCADE, related prediction records are removed
 * automatically by MySQL.
 * -----------------------------------------------------------
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

// Reject anything that isn't a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: students.php');
    exit;
}

if (!verifyCsrf()) {
    setFlash('error', 'Invalid request. Please try the delete action again.');
    header('Location: students.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlash('error', 'Invalid student ID.');
    header('Location: students.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        setFlash('success', 'Student deleted successfully.');
    } else {
        setFlash('error', 'Student not found.');
    }
} catch (PDOException $e) {
    error_log('Delete student failed: ' . $e->getMessage());
    setFlash('error', 'Unable to delete the student. Please try again.');
}

header('Location: students.php');
exit;
