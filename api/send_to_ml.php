<?php
/**
 * Local rule-based reference evaluation. The Flask endpoint implements the same
 * policy for optional API consumers; mock submissions do not depend on cURL or Flask.
 */

function evaluationTokens($text) {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/\blast\s+in\s+first\s+out\b/', ' lifo ', $text);
    $text = preg_replace('/\bfirst\s+in\s+first\s+out\b/', ' fifo ', $text);
    $text = str_replace(['contiguously', 'contiguous'], ' contiguous ', $text);
    preg_match_all('/[a-z]+|\d+(?:\.\d+)?/', $text, $matches);

    $stopWords = array_fill_keys(explode(' ', 'a an the is are was were be been being of to for in on at by with from as it this that these those into than then which who how what when where why and or but so because while whereas meaning mean also can may might should would could will do does did has have had its their your our i we you they he she them us me my very more most some such only each all about after before across using used use another related together within both each per if no yes'), true);
    $tokens = [];
    foreach ($matches[0] as $token) {
        if (strlen($token) > 4 && substr($token, -3) === 'ies') {
            $token = substr($token, 0, -3) . 'y';
        } elseif (strlen($token) > 4 && substr($token, -3) === 'ing') {
            $token = substr($token, 0, -3);
        } elseif (strlen($token) > 3 && substr($token, -2) === 'ed') {
            $token = substr($token, 0, -2);
        } elseif (strlen($token) > 3 && substr($token, -1) === 's') {
            $token = substr($token, 0, -1);
        }
        if (!isset($stopWords[$token])) {
            $tokens[] = $token;
        }
    }
    return $tokens;
}

function evaluationClauses($text) {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/\blast\s+in\s+first\s+out\b/', ' lifo ', $text);
    $text = preg_replace('/\bfirst\s+in\s+first\s+out\b/', ' fifo ', $text);
    $text = str_replace(['contiguously', 'contiguous'], ' contiguous ', $text);
    return preg_split('/[.!?;]+|\b(?:which\s+means|while|whereas|but|and|so|because|meaning)\b/', $text, -1, PREG_SPLIT_NO_EMPTY);
}

function evaluationIsMeaningful($answer) {
    $answer = strtolower(trim((string)$answer));
    if ($answer === '') {
        return false;
    }
    $compact = preg_replace('/[^a-z0-9]/', '', $answer);
    if (preg_match('/^(.)\1{5,}$/', $compact)) {
        return false;
    }

    preg_match_all('/[a-z]+/', $answer, $letters);
    $letterText = implode('', $letters[0]);
    if (strlen($letterText) >= 8 && preg_match_all('/[aeiou]/', $letterText) / strlen($letterText) < 0.12) {
        return false;
    }
    return count(evaluationTokens($answer)) > 0;
}

function evaluationConcepts($referenceAnswer) {
    $concepts = [];
    foreach (evaluationClauses($referenceAnswer) as $clause) {
        $tokens = evaluationTokens($clause);
        if (count($tokens) === 1 && in_array($tokens[0], ['no', 'yes'], true)) {
            continue;
        }
        if ($tokens) {
            $concepts[] = ['text' => trim($clause), 'tokens' => array_values(array_unique($tokens))];
        }
    }
    return $concepts;
}

function evaluationContrastTokens($text) {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/\blast\s+in\s+first\s+out\b/', ' lifo ', $text);
    $text = preg_replace('/\bfirst\s+in\s+first\s+out\b/', ' fifo ', $text);
    preg_match_all('/[a-z]+|\d+(?:\.\d+)?/', $text, $matches);
    return $matches[0];
}

function evaluationHasSharedPhrase($referenceTokens, $answerClauses) {
    if (count($referenceTokens) === 1) {
        foreach ($answerClauses as $clause) {
            if (in_array($referenceTokens[0], $clause, true)) {
                return true;
            }
        }
        return false;
    }
    for ($i = 0; $i < count($referenceTokens) - 1; $i++) {
        foreach ($answerClauses as $clause) {
            for ($j = 0; $j < count($clause) - 1; $j++) {
                if ($referenceTokens[$i] === $clause[$j] && $referenceTokens[$i + 1] === $clause[$j + 1]) {
                    return true;
                }
            }
        }
    }
    return false;
}

function evaluateAnswer($studentAnswer, $idealAnswer, $question = '') {
    $studentAnswer = trim((string)($studentAnswer ?? ''));
    $idealAnswer = trim((string)($idealAnswer ?? ''));
    $question = trim((string)($question ?? ''));

    if ($studentAnswer === '') {
        return [
            'score' => 0.0,
            'status' => 'evaluated',
            'feedback' => 'No answer was submitted. The answer receives 0/100.',
            'correct_concepts' => [],
            'missing_concepts' => [],
        ];
    }
    if (!evaluationIsMeaningful($studentAnswer)) {
        return [
            'score' => 0.0,
            'status' => 'evaluated',
            'feedback' => 'Your response does not contain a meaningful answer to the question. Review the reference answer and try again. Score: 0/100.',
            'correct_concepts' => [],
            'missing_concepts' => [],
        ];
    }
    if ($idealAnswer === '') {
        return [
            'score' => null,
            'status' => 'unavailable',
            'feedback' => 'Evaluation unavailable — reference answer missing.',
            'correct_concepts' => [],
            'missing_concepts' => [],
        ];
    }

    $concepts = evaluationConcepts($idealAnswer);
    $answerTokens = evaluationTokens($studentAnswer);
    $answerTokenSet = array_fill_keys($answerTokens, true);
    $answerClauses = [];
    foreach (evaluationClauses($studentAnswer) as $clause) {
        $tokens = evaluationTokens($clause);
        if ($tokens) {
            $answerClauses[] = $tokens;
        }
    }
    $referenceTokenSet = [];
    foreach ($concepts as $concept) {
        foreach ($concept['tokens'] as $token) {
            $referenceTokenSet[$token] = true;
        }
    }
    if (count($referenceTokenSet) < 2) {
        return [
            'score' => null,
            'status' => 'unavailable',
            'feedback' => 'Evaluation unavailable — the reference answer does not contain enough detail for a reliable rubric.',
            'correct_concepts' => [],
            'missing_concepts' => [],
        ];
    }

    $answerContrastSet = array_fill_keys(evaluationContrastTokens($studentAnswer), true);
    $referenceContrastSet = array_fill_keys(evaluationContrastTokens($idealAnswer), true);
    $contradictions = [
        ['lifo', 'fifo'], ['yes', 'no'], ['same', 'different'],
        ['increase', 'decrease'], ['higher', 'lower'], ['more', 'less'],
        ['before', 'after'], ['reliable', 'unreliable'],
        ['ordered', 'unordered'], ['true', 'false'],
    ];
    $referenceHasContradiction = false;
    $answerHasContradiction = false;
    foreach ($contradictions as $pair) {
        if (isset($referenceContrastSet[$pair[0]], $answerContrastSet[$pair[1]]) ||
            isset($referenceContrastSet[$pair[1]], $answerContrastSet[$pair[0]])) {
            $answerHasContradiction = true;
        }
        if (isset($referenceContrastSet[$pair[0]], $referenceContrastSet[$pair[1]])) {
            $referenceHasContradiction = true;
        }
    }
    $contradictory = $answerHasContradiction && !$referenceHasContradiction;

    $correctConcepts = [];
    $missingConcepts = [];
    $creditedTokens = 0;
    $totalReferenceTokens = 0;
    foreach ($concepts as $concept) {
        $tokens = $concept['tokens'];
        $totalReferenceTokens += count($tokens);
        $matched = array_values(array_filter($tokens, function ($token) use ($answerTokenSet) {
            return isset($answerTokenSet[$token]);
        }));
        $phrase = evaluationHasSharedPhrase($tokens, $answerClauses);
        $requiredMatches = max(2, (int)ceil(count($tokens) * 0.5));
        $supported = count($matched) >= $requiredMatches &&
            ($phrase || count($matched) >= (int)ceil(count($tokens) * 0.75));

        if ($supported) {
            $correctConcepts[] = $concept['text'];
            $creditedTokens += count($matched);
        }
        if (!$supported || count($matched) < count($tokens)) {
            $missingConcepts[] = $concept['text'];
        }
    }

    $matchedAnswerTokens = count(array_intersect_key($answerTokenSet, $referenceTokenSet));
    $precision = count($answerTokenSet) ? $matchedAnswerTokens / count($answerTokenSet) : 0;
    $score = $contradictory || !$totalReferenceTokens
        ? 0.0
        : round(100 * ($creditedTokens / $totalReferenceTokens) * $precision, 1);
    $score = min(100.0, max(0.0, $score));

    $questionContext = $question !== '' ? 'For "' . $question . '", ' : '';
    if ($score >= 90) {
        $opening = $questionContext . 'your answer covers nearly all concepts in the reference.';
    } elseif ($score > 0) {
        $opening = $questionContext . 'your answer demonstrates some of the concepts in the reference, but is incomplete.';
    } else {
        $opening = $questionContext . 'your answer does not demonstrate the key concepts in the reference.';
    }
    $correctText = $correctConcepts ? implode('; ', $correctConcepts) : 'none identified';
    $missingText = $missingConcepts ? implode('; ', $missingConcepts) : 'none identified';
    $feedback = $opening . ' Score: ' . number_format($score, 1, '.', '') . '/100. Correct concepts: ' . $correctText . '. Missing or unclear concepts: ' . $missingText . '.';
    if ($contradictory) {
        $feedback .= ' The answer also states a concept that conflicts with the reference.';
    }

    return [
        'score' => $score,
        'status' => 'evaluated',
        'feedback' => $feedback,
        'correct_concepts' => $correctConcepts,
        'missing_concepts' => $missingConcepts,
    ];
}

/**
 * Evaluates every response in a mock test, stores per-question scores/feedback,
 * aggregates subject-wise + overall scores, and writes the results row.
 */
function evaluateMockTest($pdo, $mock_id) {
    $stmt = $pdo->prepare("SELECT r.response_id, r.student_answer, q.question, q.question_id, q.subject_id,
                                   s.subject_name, a.ideal_answer
                            FROM responses r
                            JOIN questions q ON r.question_id = q.question_id
                            LEFT JOIN subjects s ON q.subject_id = s.subject_id
                            LEFT JOIN answers a ON a.question_id = q.question_id
                            WHERE r.mock_id = ?");
    $stmt->execute([$mock_id]);
    $rows = $stmt->fetchAll();

    $updateStmt = $pdo->prepare("UPDATE responses SET score = ?, feedback = ? WHERE response_id = ?");

    $subjectScores = [];
    $evaluatedScores = [];
    $pdo->beginTransaction();
    try {
        foreach ($rows as $row) {
            $result = evaluateAnswer($row['student_answer'], $row['ideal_answer'], $row['question']);
            if ($result['score'] !== null && (!is_numeric($result['score']) || $result['score'] < 0 || $result['score'] > 100)) {
                throw new UnexpectedValueException('The evaluator returned a score outside 0-100.');
            }
            $updateStmt->execute([$result['score'], $result['feedback'], $row['response_id']]);

            if ($result['score'] === null) {
                continue;
            }
            $score = (float)$result['score'];
            $evaluatedScores[] = $score;
            $subj = $row['subject_name'] ?? 'General';
            $subjectScores[$subj][] = $score;
        }

        $overall = $evaluatedScores ? round(array_sum($evaluatedScores) / count($evaluatedScores), 1) : null;

        $subjectWise = [];
        foreach ($subjectScores as $subj => $scores) {
            $subjectWise[$subj] = round(array_sum($scores) / count($scores), 1);
        }
        arsort($subjectWise);
        $strengths = $subjectWise ? array_slice(array_keys($subjectWise), 0, 2) : [];
        $weaknesses = $subjectWise ? array_slice(array_keys(array_reverse($subjectWise, true)), 0, 2) : [];

        $suggestions = [];
        foreach ($weaknesses as $w) {
            $suggestions[] = "Revise $w concepts — practice more questions in this area.";
        }
        if (!$evaluatedScores) {
            $suggestions[] = 'Add approved reference answers to the question bank before evaluating this mock interview.';
        } elseif (!$suggestions) {
            $suggestions[] = 'Keep practicing regularly to maintain your performance.';
        }

        $stmt = $pdo->prepare("SELECT result_id FROM results WHERE mock_id = ?");
        $stmt->execute([$mock_id]);
        $exists = $stmt->fetch();

        $subjectWiseJson = json_encode($subjectWise);
        $strengthsStr = $strengths ? implode(', ', $strengths) : 'N/A';
        $weaknessesStr = $weaknesses ? implode(', ', $weaknesses) : 'N/A';
        $suggestionsStr = implode(' ', $suggestions);

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE results SET overall_score=?, subject_wise_score=?, strengths=?, weaknesses=?, suggestions=? WHERE mock_id=?");
            $stmt->execute([$overall, $subjectWiseJson, $strengthsStr, $weaknessesStr, $suggestionsStr, $mock_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO results (mock_id, overall_score, subject_wise_score, strengths, weaknesses, suggestions) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$mock_id, $overall, $subjectWiseJson, $strengthsStr, $weaknessesStr, $suggestionsStr]);
        }

        $stmt = $pdo->prepare("UPDATE mock_tests SET status='Completed', end_time=NOW(), score=? WHERE mock_id=?");
        $stmt->execute([$overall, $mock_id]);
        $pdo->commit();
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }

    return $overall;
}
