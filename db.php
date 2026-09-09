<?php
/**
 * db.php
 * -----------------------------------------------------------
 * Reusable PDO database connection.
 * Every page in this project includes this file to talk to MySQL.
 *
 * We use PDO (PHP Data Objects) instead of the old mysqli/mysql
 * functions because PDO:
 *   - supports prepared statements (protects against SQL injection)
 *   - throws exceptions on errors, which we can catch cleanly
 *   - works the same way regardless of database driver
 * -----------------------------------------------------------
 */

// ---- Database configuration (XAMPP defaults) ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_predictor');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP's default MySQL root password is empty

// Data Source Name - tells PDO which driver, host, db and charset to use
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

// Options that control how PDO behaves
$options = [
    // Throw exceptions on DB errors instead of silently failing
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Return rows as associative arrays, e.g. $row['name']
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Use real prepared statements (safer, more correct behaviour)
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create the PDO connection object. $pdo is what every other
    // page will use to run queries.
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Never show raw database errors to the user (security rule).
    // Log the real error for the developer, show a friendly message instead.
    error_log('Database connection failed: ' . $e->getMessage());
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
            <h2>Database connection failed</h2>
            <p>Please make sure XAMPP\'s MySQL service is running and that the
            <strong>student_predictor</strong> database has been imported.</p>
         </div>');
}
