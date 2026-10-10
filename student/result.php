<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireStudentLogin();

$user_id = $_SESSION['user_id'];
$mock_id = (int)($_GET['mock_id'] ?? 0);

$stmt = $pdo->prepare("SELECT m.*, c.company_name, res.overall_score, res.subject_wise_score,
                               res.strengths, res.weaknesses, res.suggestions
                        FROM mock_tests m
                        JOIN companies c ON m.company_id = c.company_id
                        LEFT JOIN results res ON res.mock_id = m.mock_id
                        WHERE m.mock_id = ? AND m.user_id = ?");
$stmt->execute([$mock_id, $user_id]);
$mock = $stmt->fetch();

if (!$mock) {
    header('Location: ' . siteUrl('student/history.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT r.*, q.question, q.type, a.ideal_answer FROM responses r
                        JOIN questions q ON r.question_id = q.question_id
                        LEFT JOIN answers a ON a.question_id = q.question_id
                        WHERE r.mock_id = ?");
$stmt->execute([$mock_id]);
$responses = $stmt->fetchAll();
$hasLegacyScores = false;
$hasUnevaluatedAnswers = false;
foreach ($responses as $response) {
    if (strpos((string)($response['feedback'] ?? ''), 'scored as neutral') !== false) {
        $hasLegacyScores = true;
    }
    if ($response['score'] === null) {
        $hasUnevaluatedAnswers = true;
    }
}

$subjectWise = $mock['subject_wise_score'] ? json_decode($mock['subject_wise_score'], true) : [];
$subjectWise = is_array($subjectWise) ? $subjectWise : [];
$pct = $mock['overall_score'] !== null ? number_format((float)$mock['overall_score'], 1, '.', '') : null;

$pageTitle = 'Mock Test Result';
$sidebarType = 'student';
$activePage = 'history';
$extraCss = ['dashboard.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>Your Mock Test Performance</h2>
        <p style="color:var(--muted)"><?= e($mock['company_name']) ?> — <?= e($mock['role']) ?></p>
        <?php if ($hasLegacyScores): ?>
            <div class="ib-alert ib-alert-error">This result includes a score from the previous evaluator, which assigned neutral marks when a reference answer was missing. That score is not a reliable correctness assessment; retake the interview for reference-based scoring.</div>
        <?php endif; ?>

        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <div class="ib-card" style="flex:1; min-width:220px; text-align:center;">
                <div class="score-ring" data-pct="<?= $pct !== null ? e($pct) : '0' ?>"><span><?= $pct !== null ? e($pct) . '%' : 'N/A' ?></span></div>
                <p style="margin-top:10px; color:var(--muted)">Overall Score</p>
                <?php if ($pct === null): ?><p>Evaluation unavailable — no answers had a usable reference answer.</p><?php endif; ?>
                <?php if ($pct !== null && $hasUnevaluatedAnswers): ?><p>Unevaluated answers are excluded from this average.</p><?php endif; ?>
            </div>
            <div class="ib-card" style="flex:1; min-width:220px;">
                <h3>Subject-wise Score</h3>
                <ul class="perf-list">
                    <?php foreach ($subjectWise as $subj => $score): ?>
                        <li><span><?= e($subj) ?></span><span><?= number_format((float)$score, 1) ?>%</span></li>
                    <?php endforeach; ?>
                    <?php if (!$subjectWise): ?><li>No subject data available.</li><?php endif; ?>
                </ul>
            </div>
            <div class="ib-card" style="flex:1; min-width:220px;">
                <p><strong>💪 Strengths:</strong><br><?= e($mock['strengths'] ?? 'N/A') ?></p>
                <p><strong>⚠️ Weaknesses:</strong><br><?= e($mock['weaknesses'] ?? 'N/A') ?></p>
                <p><strong>💡 Suggestions:</strong><br><?= e($mock['suggestions'] ?? 'N/A') ?></p>
            </div>
        </div>

        <div class="ib-card">
            <h3>Detailed Answers</h3>
            <table class="ib-table">
                <tr><th>Question</th><th>Your Answer</th><th>Score</th><th>Feedback</th><th>Reference Answer</th></tr>
                <?php foreach ($responses as $r): ?>
                <tr>
                    <td><?= e($r['question']) ?></td>
                    <td><?= e($r['student_answer'] ?: '—') ?></td>
                    <td><?= $r['score'] !== null ? number_format((float)$r['score'], 1).'%' : 'Not evaluated' ?></td>
                    <td><?= strpos((string)($r['feedback'] ?? ''), 'scored as neutral') !== false
                        ? 'Legacy evaluation — this score used the previous neutral-default method and is not reliable.'
                        : e($r['feedback'] ?? '') ?></td>
                    <td><?= trim((string)($r['ideal_answer'] ?? '')) !== ''
                        ? e($r['ideal_answer'])
                        : ($r['score'] === null ? 'Evaluation unavailable — reference answer missing.' : 'No reference answer was saved.') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <a href="<?= e(siteUrl('student/mock_interview.php')) ?>" class="ib-btn">Take Another Mock Test</a>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
