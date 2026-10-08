<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireStudentLogin();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT m.*, c.company_name FROM mock_tests m
                        JOIN companies c ON m.company_id = c.company_id
                        WHERE m.user_id = ? ORDER BY m.start_time DESC");
$stmt->execute([$user_id]);
$mocks = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT q.question, q.type, q.difficulty, c.company_name
                        FROM bookmarks b
                        JOIN questions q ON b.question_id = q.question_id
                        JOIN interview_experiences e ON q.exp_id = e.exp_id
                        JOIN companies c ON e.company_id = c.company_id
                        WHERE b.user_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$bookmarks = $stmt->fetchAll();

$pageTitle = 'History';
$sidebarType = 'student';
$activePage = 'history';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>Interview History</h2>
        <div class="ib-card">
            <table class="ib-table">
                <tr><th>Company</th><th>Role</th><th>Score</th><th>Status</th><th>Date</th><th></th></tr>
                <?php foreach ($mocks as $m): ?>
                <tr>
                    <td><?= e($m['company_name']) ?></td>
                    <td><?= e($m['role']) ?></td>
                    <td><?= $m['score'] !== null ? round($m['score']).'%' : '—' ?></td>
                    <td><span class="badge <?= $m['status']=='Completed'?'badge-approved':'badge-pending' ?>"><?= e($m['status']) ?></span></td>
                    <td><?= date('d M Y', strtotime($m['start_time'])) ?></td>
                    <td>
                        <?php if ($m['status']=='Completed'): ?>
                            <a href="result.php?mock_id=<?= $m['mock_id'] ?>">View Result</a>
                        <?php else: ?>
                            <a href="mock_interview.php?mock_id=<?= $m['mock_id'] ?>&q=0">Resume</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$mocks): ?><tr><td colspan="6" style="text-align:center; color:var(--muted)">No mock tests yet.</td></tr><?php endif; ?>
            </table>
        </div>

        <h2>Bookmarked Questions</h2>
        <div class="ib-card">
            <table class="ib-table">
                <tr><th>Question</th><th>Company</th><th>Type</th><th>Difficulty</th></tr>
                <?php foreach ($bookmarks as $b): ?>
                <tr>
                    <td><?= e($b['question']) ?></td>
                    <td><?= e($b['company_name']) ?></td>
                    <td><?= e($b['type']) ?></td>
                    <td><?= e($b['difficulty']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$bookmarks): ?><tr><td colspan="4" style="text-align:center; color:var(--muted)">No bookmarks yet.</td></tr><?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
