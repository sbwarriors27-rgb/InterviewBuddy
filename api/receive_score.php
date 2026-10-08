<?php
/**
 * Lightweight JSON endpoint: scores a single response immediately (used for
 * optional live-feedback features). Not required for the main mock-interview
 * flow, which scores everything in bulk via evaluateMockTest() at submission time.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/send_to_ml.php';

header('Content-Type: application/json');

if (!isStudentLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$response_id = (int)($_POST['response_id'] ?? 0);

$stmt = $pdo->prepare("SELECT r.response_id, r.student_answer, a.ideal_answer
                        FROM responses r
                        JOIN mock_tests m ON r.mock_id = m.mock_id
                        JOIN questions q ON r.question_id = q.question_id
                        LEFT JOIN answers a ON a.question_id = q.question_id
                        WHERE r.response_id = ? AND m.user_id = ?");
$stmt->execute([$response_id, $_SESSION['user_id']]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Response not found']);
    exit;
}

$result = evaluateAnswer($row['student_answer'], $row['ideal_answer']);

$upd = $pdo->prepare("UPDATE responses SET score=?, feedback=? WHERE response_id=?");
$upd->execute([$result['score'], $result['feedback'], $response_id]);

echo json_encode($result);
