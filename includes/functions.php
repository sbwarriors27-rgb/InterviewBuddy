<?php
require_once __DIR__ . '/../config/database.php';

function getAllCompanies($pdo) {
    return $pdo->query("SELECT * FROM companies ORDER BY company_name")->fetchAll();
}

function getAllSubjects($pdo) {
    return $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();
}

function getTopicsBySubject($pdo, $subject_id) {
    $stmt = $pdo->prepare("SELECT * FROM topics WHERE subject_id = ? ORDER BY topic_name");
    $stmt->execute([$subject_id]);
    return $stmt->fetchAll();
}

function getStudentById($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/** Basic stats used on the student dashboard */
function getStudentStats($pdo, $user_id) {
    $stats = [
        'total_mocks'  => 0,
        'avg_score'    => 0,
        'highest_score'=> 0,
        'questions_solved' => 0,
    ];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS c, AVG(score) AS avg, MAX(score) AS max
                            FROM mock_tests WHERE user_id = ? AND status = 'Completed'");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row) {
        $stats['total_mocks']   = (int)$row['c'];
        $stats['avg_score']     = $row['avg'] ? round($row['avg'], 1) : 0;
        $stats['highest_score'] = $row['max'] ? round($row['max'], 1) : 0;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM responses r
                            JOIN mock_tests m ON r.mock_id = m.mock_id
                            WHERE m.user_id = ? AND r.student_answer IS NOT NULL AND r.student_answer <> ''");
    $stmt->execute([$user_id]);
    $stats['questions_solved'] = (int)$stmt->fetch()['c'];

    return $stats;
}

/** Subject-wise average score for a student, used for dashboard pie/bar chart */
function getSubjectWisePerformance($pdo, $user_id) {
    $sql = "SELECT s.subject_name, AVG(r.score) AS avg_score
            FROM responses r
            JOIN questions q ON r.question_id = q.question_id
            JOIN subjects s ON q.subject_id = s.subject_id
            JOIN mock_tests m ON r.mock_id = m.mock_id
            WHERE m.user_id = ? AND r.score IS NOT NULL
            GROUP BY s.subject_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}
