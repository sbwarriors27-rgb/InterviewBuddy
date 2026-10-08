<?php
/**
 * Database connection (PDO)
 * Edit these values to match your XAMPP / MySQL setup.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'interviewbuddy');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() .
        "<br>Make sure XAMPP's MySQL is running and the 'interviewbuddy' database has been imported.");
}
