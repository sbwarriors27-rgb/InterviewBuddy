<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([(int)$_GET['delete']]);
    header('Location: manage_students.php');
    exit;
}

$students = $pdo->query("SELECT u.*, 
        (SELECT COUNT(*) FROM mock_tests m WHERE m.user_id = u.user_id) AS mock_count,
        (SELECT COUNT(*) FROM interview_experiences e WHERE e.user_id = u.user_id) AS exp_count
        FROM users u ORDER BY u.created_at DESC")->fetchAll();

$pageTitle = 'Manage Students';
$sidebarType = 'admin';
$activePage = 'manage_students';
$extraCss = ['admin.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>Manage Students</h2>
        <div class="ib-card">
            <table class="ib-table">
                <tr><th>Name</th><th>Email</th><th>College</th><th>Experiences</th><th>Mock Tests</th><th>Joined</th><th></th></tr>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['email']) ?></td>
                    <td><?= e($s['college'] ?: '—') ?></td>
                    <td><?= $s['exp_count'] ?></td>
                    <td><?= $s['mock_count'] ?></td>
                    <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                    <td><a href="?delete=<?= $s['user_id'] ?>" data-confirm="Delete this student and all their data?" class="ib-btn ib-btn-sm ib-btn-danger">Delete</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$students): ?><tr><td colspan="7" style="text-align:center;color:var(--muted)">No students yet.</td></tr><?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
