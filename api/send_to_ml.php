<?php
/**
 * Bridge between the PHP app and the Python/Flask ML evaluation service.
 * Call evaluateMockTest($pdo, $mock_id) once a student finishes a mock interview.
 */

define('ML_API_URL', 'http://127.0.0.1:5000/evaluate');

/**
 * Sends one answer + ideal answer pair to the Flask ML API and returns
 * ['score' => float 0-100, 'feedback' => string].
 * Falls back to a simple local similarity check if the ML service is unreachable,
 * so the app keeps working even if Flask isn't running.
 */
function evaluateAnswer($studentAnswer, $idealAnswer) {
    $payload = json_encode([
        'student_answer' => $studentAnswer ?? '',
        'ideal_answer'   => $idealAnswer ?? '',
    ]);

    $ch = curl_init(ML_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);
    $response = curl_exec($ch);
    $ok = !curl_errno($ch);
    curl_close($ch);

    if ($ok && $response) {
        $data = json_decode($response, true);
        if (isset($data['score'])) {
            return ['score' => (float)$data['score'], 'feedback' => $data['feedback'] ?? ''];
        }
    }

    // ---- Fallback: local keyword-overlap similarity (used only if Flask is offline) ----
    return localFallbackEvaluation($studentAnswer, $idealAnswer);
}

function localFallbackEvaluation($studentAnswer, $idealAnswer) {
    $studentAnswer = trim($studentAnswer ?? '');
    $idealAnswer   = trim($idealAnswer ?? '');

    if ($studentAnswer === '') {
        return ['score' => 0, 'feedback' => 'No answer was submitted for this question.'];
    }
    if ($idealAnswer === '') {
        return ['score' => 50, 'feedback' => 'No ideal answer on file yet — scored as neutral.'];
    }

    $studentWords = array_unique(array_filter(preg_split('/\W+/', strtolower($studentAnswer))));
    $idealWords   = array_unique(array_filter(preg_split('/\W+/', strtolower($idealAnswer))));
    $overlap = count(array_intersect($studentWords, $idealWords));
    $score = $idealWords ? min(100, round(($overlap / count($idealWords)) * 100)) : 50;

    $feedback = $score >= 70
        ? 'Good match with the expected answer.'
        : ($score >= 40 ? 'Partially correct — some key points are missing.' : 'Answer differs significantly from the ideal answer. Review this topic.');

    return ['score' => $score, 'feedback' => $feedback];
}

/**
 * Evaluates every response in a mock test, stores per-question scores/feedback,
 * aggregates subject-wise + overall scores, and writes the results row.
 */
function evaluateMockTest($pdo, $mock_id) {
    $stmt = $pdo->prepare("SELECT r.response_id, r.student_answer, q.question_id, q.subject_id,
                                   s.subject_name, a.ideal_answer
                            FROM responses r
                            JOIN questions q ON r.question_id = q.question_id
                            LEFT JOIN subjects s ON q.subject_id = s.subject_id
                            LEFT JOIN answers a ON a.question_id = q.question_id
                            WHERE r.mock_id = ?");
    $stmt->execute([$mock_id]);
    $rows = $stmt->fetchAll();

    $updateStmt = $pdo->prepare("UPDATE responses SET score = ?, feedback = ? WHERE response_id = ?");

    $subjectScores = []; // subject_name => [scores]
    $totalScore = 0;
    $count = 0;

    foreach ($rows as $row) {
        $result = evaluateAnswer($row['student_answer'], $row['ideal_answer']);
        $updateStmt->execute([$result['score'], $result['feedback'], $row['response_id']]);

        $totalScore += $result['score'];
        $count++;

        $subj = $row['subject_name'] ?? 'General';
        $subjectScores[$subj][] = $result['score'];
    }

    $overall = $count ? round($totalScore / $count, 1) : 0;

    $subjectWise = [];
    foreach ($subjectScores as $subj => $scores) {
        $subjectWise[$subj] = round(array_sum($scores) / count($scores), 1);
    }
    arsort($subjectWise);
    $strengths  = array_slice(array_keys($subjectWise), 0, 2);
    $weaknesses = array_slice(array_keys(array_reverse($subjectWise, true)), 0, 2);

    $suggestions = [];
    foreach ($weaknesses as $w) {
        $suggestions[] = "Revise $w concepts — practice more questions in this area.";
    }
    if (!$suggestions) $suggestions[] = 'Keep practicing regularly to maintain your performance.';

    // Save aggregated results (upsert)
    $stmt = $pdo->prepare("SELECT result_id FROM results WHERE mock_id = ?");
    $stmt->execute([$mock_id]);
    $exists = $stmt->fetch();

    $subjectWiseJson = json_encode($subjectWise);
    $strengthsStr    = $strengths ? implode(', ', $strengths) : 'N/A';
    $weaknessesStr   = $weaknesses ? implode(', ', $weaknesses) : 'N/A';
    $suggestionsStr  = implode(' ', $suggestions);

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE results SET overall_score=?, subject_wise_score=?, strengths=?, weaknesses=?, suggestions=? WHERE mock_id=?");
        $stmt->execute([$overall, $subjectWiseJson, $strengthsStr, $weaknessesStr, $suggestionsStr, $mock_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO results (mock_id, overall_score, subject_wise_score, strengths, weaknesses, suggestions) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$mock_id, $overall, $subjectWiseJson, $strengthsStr, $weaknessesStr, $suggestionsStr]);
    }

    $stmt = $pdo->prepare("UPDATE mock_tests SET status='Completed', end_time=NOW(), score=? WHERE mock_id=?");
    $stmt->execute([$overall, $mock_id]);

    return $overall;
}
