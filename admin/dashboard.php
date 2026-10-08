<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$totalStudents  = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingExp     = $pdo->query("SELECT COUNT(*) FROM interview_experiences WHERE status='Pending'")->fetchColumn();
$totalQuestions = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$approvedQ      = $pdo->query("SELECT COUNT(*) FROM questions WHERE status='Approved'")->fetchColumn();

$stmt = $pdo->query("SELECT e.exp_id, u.name AS student, c.company_name, e.role, e.created_at, e.status
                      FROM interview_experiences e
                      JOIN users u ON e.user_id = u.user_id
                      JOIN companies c ON e.company_id = c.company_id
                      ORDER BY e.created_at DESC LIMIT 8");
$recent = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';
$sidebarType = 'admin';
$activePage = 'dashboard';
$extraCss = ['admin.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>Admin Dashboard</h2>
        <div class="ib-stats-grid">
            <div class="ib-stat"><div class="value"><?= $totalStudents ?></div><div class="label">Total Students</div></div>
            <div class="ib-stat"><div class="value"><?= $pendingExp ?></div><div class="label">Pending Experiences</div></div>
            <div class="ib-stat"><div class="value"><?= $totalQuestions ?></div><div class="label">Total Questions</div></div>
            <div class="ib-stat"><div class="value"><?= $approvedQ ?></div><div class="label">Approved Questions</div></div>
        </div>

        <div class="ib-card">
            <h3>Recent Activities</h3>
            <table class="ib-table">
                <tr><th>Student</th><th>Company</th><th>Role</th><th>Date</th><th>Status</th></tr>
                <?php foreach ($recent as $r): ?>
                <tr>
                    <td><?= e($r['student']) ?></td>
                    <td><?= e($r['company_name']) ?></td>
                    <td><?= e($r['role']) ?></td>
                    <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                    <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= e($r['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?><tr><td colspan="5" style="text-align:center;color:var(--muted)">No submissions yet.</td></tr><?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
