<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT COUNT(*) FROM interview_experiences WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$from, $to . ' 23:59:59']);
$totalExp = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$from, $to . ' 23:59:59']);
$totalQ = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE status='Approved' AND created_at BETWEEN ? AND ?");
$stmt->execute([$from, $to . ' 23:59:59']);
$approvedQ = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM mock_tests WHERE start_time BETWEEN ? AND ?");
$stmt->execute([$from, $to . ' 23:59:59']);
$mockTests = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT c.company_name, COUNT(e.exp_id) AS cnt FROM interview_experiences e
                      JOIN companies c ON e.company_id = c.company_id GROUP BY c.company_id ORDER BY cnt DESC LIMIT 10");
$byCompany = $stmt->fetchAll();

$pageTitle = 'Reports & Analytics';
$sidebarType = 'admin';
$activePage = 'reports';
$extraCss = ['admin.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>Reports & Analytics</h2>
        <div class="ib-card">
            <form method="GET" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                <div class="ib-form-group"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
                <div class="ib-form-group"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
                <button class="ib-btn" type="submit">Generate Report</button>
            </form>
        </div>

        <div class="ib-stats-grid">
            <div class="ib-stat"><div class="value"><?= $totalExp ?></div><div class="label">Total Experiences</div></div>
            <div class="ib-stat"><div class="value"><?= $totalQ ?></div><div class="label">Total Questions</div></div>
            <div class="ib-stat"><div class="value"><?= $approvedQ ?></div><div class="label">Approved Questions</div></div>
            <div class="ib-stat"><div class="value"><?= $mockTests ?></div><div class="label">Mock Tests Taken</div></div>
        </div>

        <div class="ib-card">
            <h3>Submissions by Company</h3>
            <table class="ib-table">
                <tr><th>Company</th><th>Experiences Submitted</th></tr>
                <?php foreach ($byCompany as $b): ?>
                <tr><td><?= e($b['company_name']) ?></td><td><?= $b['cnt'] ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
