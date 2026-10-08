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

$stmt = $pdo->prepare("SELECT r.*, q.question, q.type FROM responses r
                        JOIN questions q ON r.question_id = q.question_id
                        WHERE r.mock_id = ?");
$stmt->execute([$mock_id]);
$responses = $stmt->fetchAll();

$subjectWise = $mock['subject_wise_score'] ? json_decode($mock['subject_wise_score'], true) : [];
$pct = round($mock['overall_score'] ?? 0);

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

        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <div class="ib-card" style="flex:1; min-width:220px; text-align:center;">
                <div class="score-ring" data-pct="<?= $pct ?>"><span><?= $pct ?>%</span></div>
                <p style="margin-top:10px; color:var(--muted)">Overall Score</p>
            </div>
            <div class="ib-card" style="flex:1; min-width:220px;">
                <h3>Subject-wise Score</h3>
                <ul class="perf-list">
                    <?php foreach ($subjectWise as $subj => $score): ?>
                        <li><span><?= e($subj) ?></span><span><?= $score ?>%</span></li>
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
                <tr><th>Question</th><th>Your Answer</th><th>Score</th><th>Feedback</th></tr>
                <?php foreach ($responses as $r): ?>
                <tr>
                    <td><?= e($r['question']) ?></td>
                    <td><?= e($r['student_answer'] ?: '—') ?></td>
                    <td><?= $r['score'] !== null ? round($r['score']).'%' : '—' ?></td>
                    <td><?= e($r['feedback'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <a href="<?= e(siteUrl('student/mock_interview.php')) ?>" class="ib-btn">Take Another Mock Test</a>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
