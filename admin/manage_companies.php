<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company'])) {
    $name = trim($_POST['company_name']);
    $website = trim($_POST['website']);
    $about = trim($_POST['about']);
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO companies (company_name, website, about) VALUES (?,?,?)");
        $stmt->execute([$name, $website, $about]);
    }
    header('Location: manage_companies.php');
    exit;
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM companies WHERE company_id = ?")->execute([(int)$_GET['delete']]);
    header('Location: manage_companies.php');
    exit;
}

$companies = getAllCompanies($pdo);

$pageTitle = 'Manage Companies';
$sidebarType = 'admin';
$activePage = 'manage_companies';
$extraCss = ['admin.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>Manage Companies</h2>
        <div class="ib-card">
            <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                <input type="hidden" name="add_company" value="1">
                <div class="ib-form-group" style="flex:1; min-width:160px;"><label>Company Name</label><input type="text" name="company_name" required></div>
                <div class="ib-form-group" style="flex:1; min-width:160px;"><label>Website</label><input type="text" name="website"></div>
                <div class="ib-form-group" style="flex:2; min-width:200px;"><label>About</label><input type="text" name="about"></div>
                <button class="ib-btn" type="submit">+ Add Company</button>
            </form>
        </div>
        <div class="ib-card">
            <table class="ib-table">
                <tr><th>Company Name</th><th>Website</th><th>Action</th></tr>
                <?php foreach ($companies as $c): ?>
                <tr>
                    <td><?= e($c['company_name']) ?></td>
                    <td><?= e($c['website']) ?></td>
                    <td class="action-buttons">
                        <a href="?delete=<?= $c['company_id'] ?>" data-confirm="Delete this company?" class="ib-btn ib-btn-sm ib-btn-danger">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
