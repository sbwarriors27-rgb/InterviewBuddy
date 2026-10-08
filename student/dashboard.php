<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireStudentLogin();

$user_id = $_SESSION['user_id'];
$stats = getStudentStats($pdo, $user_id);
$subjectPerf = getSubjectWisePerformance($pdo, $user_id);

$stmt = $pdo->prepare("SELECT m.*, c.company_name FROM mock_tests m
                        JOIN companies c ON m.company_id = c.company_id
                        WHERE m.user_id = ? ORDER BY m.start_time DESC LIMIT 5");
$stmt->execute([$user_id]);
$recentMocks = $stmt->fetchAll();

$pageTitle = 'Dashboard';
$sidebarType = 'student';
$activePage = 'dashboard';
$extraCss = ['dashboard.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>Hello, <?= e($_SESSION['user_name']) ?> 👋</h2>
        <p style="color:var(--muted)">Ready to crack your dream job?</p>

        <div class="ib-stats-grid">
            <div class="ib-stat"><div class="value"><?= $stats['total_mocks'] ?></div><div class="label">Mock Tests Taken</div></div>
            <div class="ib-stat"><div class="value"><?= $stats['avg_score'] ?>%</div><div class="label">Average Score</div></div>
            <div class="ib-stat"><div class="value"><?= $stats['highest_score'] ?>%</div><div class="label">Highest Score</div></div>
            <div class="ib-stat"><div class="value"><?= $stats['questions_solved'] ?></div><div class="label">Questions Solved</div></div>
        </div>

        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <div class="ib-card" style="flex:2; min-width:320px;">
                <h3>Recent Mock Tests</h3>
                <?php if (!$recentMocks): ?>
                    <p style="color:var(--muted)">No mock tests yet. <a href="<?= e(siteUrl('student/mock_interview.php')) ?>">Take one now →</a></p>
                <?php else: ?>
                <table class="ib-table">
                    <tr><th>Company</th><th>Role</th><th>Score</th><th>Date</th><th></th></tr>
                    <?php foreach ($recentMocks as $m): ?>
                    <tr>
                        <td><?= e($m['company_name']) ?></td>
                        <td><?= e($m['role']) ?></td>
                        <td><?= $m['score'] !== null ? round($m['score'],1).'%' : '—' ?></td>
                        <td><?= date('d M Y', strtotime($m['start_time'])) ?></td>
                        <td><a href="<?= e(siteUrl('student/result.php?mock_id=' . $m['mock_id'])) ?>">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>
            <div class="ib-card" style="flex:1; min-width:260px;">
                <h3>Subject-wise Performance</h3>
                <?php if (!$subjectPerf): ?>
                    <p style="color:var(--muted)">Take a mock interview to see your breakdown.</p>
                <?php else: ?>
                <ul class="perf-list">
                    <?php foreach ($subjectPerf as $sp): ?>
                    <li><span><?= e($sp['subject_name']) ?></span><span><?= round($sp['avg_score'],1) ?>%</span></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
