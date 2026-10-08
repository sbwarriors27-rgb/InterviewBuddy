<?php
/**
 * Utility page: open this in your browser (e.g. http://localhost/InterviewBuddy/database/generate_admin_hash.php)
 * to generate a bcrypt hash for a new admin password, then run:
 *   UPDATE admin SET password = '<hash>' WHERE email = 'admin@interviewbuddy.com';
 * in phpMyAdmin. Delete this file once you're done for security.
 */
$password = $_GET['password'] ?? 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "<h3>Bcrypt hash for password: " . htmlspecialchars($password) . "</h3>";
echo "<code style='background:#eee;padding:10px;display:block;'>" . htmlspecialchars($hash) . "</code>";
echo "<p>Tip: append ?password=yourpassword to the URL to hash a different password.</p>";
