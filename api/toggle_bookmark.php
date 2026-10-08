<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
requireStudentLogin();

$user_id = $_SESSION['user_id'];
$question_id = (int)($_POST['question_id'] ?? 0);
$redirect = $_POST['redirect'] ?? 'search_questions.php';

$stmt = $pdo->prepare("SELECT bookmark_id FROM bookmarks WHERE user_id=? AND question_id=?");
$stmt->execute([$user_id, $question_id]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare("DELETE FROM bookmarks WHERE bookmark_id=?")->execute([$existing['bookmark_id']]);
} else {
    $pdo->prepare("INSERT INTO bookmarks (user_id, question_id) VALUES (?,?)")->execute([$user_id, $question_id]);
}

header("Location: " . siteUrl('student/' . basename($redirect)));
exit;
