<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

// Approve / reject a whole experience submission
if (isset($_GET['action'], $_GET['exp_id'])) {
    $exp_id = (int)$_GET['exp_id'];
    if ($_GET['action'] === 'approve') {
        $pdo->prepare("UPDATE interview_experiences SET status='Approved' WHERE exp_id=?")->execute([$exp_id]);
        // Questions stay 'Pending' individually so admin can categorize + add ideal answers next
    } elseif ($_GET['action'] === 'reject') {
        $pdo->prepare("UPDATE interview_experiences SET status='Rejected' WHERE exp_id=?")->execute([$exp_id]);
        $pdo->prepare("UPDATE questions SET status='Rejected' WHERE exp_id=?")->execute([$exp_id]);
    }
    header('Location: pending_questions.php');
    exit;
}

$stmt = $pdo->query("SELECT e.*, u.name AS student_name, c.company_name
                      FROM interview_experiences e
                      JOIN users u ON e.user_id = u.user_id
                      JOIN companies c ON e.company_id = c.company_id
                      WHERE e.status = 'Pending'
                      ORDER BY e.created_at DESC");
$pending = $stmt->fetchAll();

$viewId = isset($_GET['view']) ? (int)$_GET['view'] : null;
$viewQuestions = [];
if ($viewId) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE exp_id = ? ORDER BY type");
    $stmt->execute([$viewId]);
    $viewQuestions = $stmt->fetchAll();
}

$pageTitle = 'Pending Experiences';
$sidebarType = 'admin';
$activePage = 'pending_questions';
$extraCss = ['admin.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>Interview Experiences (Pending)</h2>
        <div class="ib-card">
            <table class="ib-table">
                <tr><th>Student</th><th>Company</th><th>Role</th><th>Date</th><th>Action</th></tr>
                <?php foreach ($pending as $p): ?>
                <tr>
                    <td><?= e($p['student_name']) ?></td>
                    <td><?= e($p['company_name']) ?></td>
                    <td><?= e($p['role']) ?></td>
                    <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                    <td class="action-buttons">
                        <a href="?view=<?= $p['exp_id'] ?>" class="ib-btn ib-btn-sm ib-btn-outline">View</a>
                        <a href="?action=approve&exp_id=<?= $p['exp_id'] ?>" class="ib-btn ib-btn-sm ib-btn-success">Approve</a>
                        <a href="?action=reject&exp_id=<?= $p['exp_id'] ?>" data-confirm="Reject this whole submission?" class="ib-btn ib-btn-sm ib-btn-danger">Reject</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$pending): ?><tr><td colspan="5" style="text-align:center;color:var(--muted)">Nothing pending 🎉</td></tr><?php endif; ?>
            </table>
        </div>

        <?php if ($viewId): ?>
        <div class="ib-card">
            <h3>Experience #<?= $viewId ?> — Questions (<?= count($viewQuestions) ?>)</h3>
            <?php
            $counts = ['Technical'=>0,'HR'=>0,'Aptitude'=>0,'Coding'=>0];
            foreach ($viewQuestions as $q) $counts[$q['type']]++;
            ?>
            <p style="color:var(--muted)">
                Questions (<?= count($viewQuestions) ?>) &nbsp;
                HR Questions (<?= $counts['HR'] ?>) &nbsp;
                Aptitude Questions (<?= $counts['Aptitude'] ?>)
            </p>
            <ol>
                <?php foreach ($viewQuestions as $q): ?>
                    <li><strong>[<?= e($q['type']) ?>]</strong> <?= e($q['question']) ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
