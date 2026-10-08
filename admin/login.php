<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

if (isAdminLoggedIn()) {
    header('Location: ' . siteUrl('admin/dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']   = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['name'];
        header('Location: ' . siteUrl('admin/dashboard.php'));
        exit;
    }
    $error = 'Invalid admin credentials.';
}

$pageTitle = 'Admin Login';
include __DIR__ . '/../includes/header.php';
?>
<div class="ib-auth-wrapper">
    <div class="ib-auth-box">
        <h2>Admin Login</h2>
        <?php if ($error): ?><div class="ib-alert ib-alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="ib-form-group">
                <label>Email</label>
                <input type="email" name="email" required value="admin@interviewbuddy.com">
            </div>
            <div class="ib-form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="ib-btn" style="width:100%">Login</button>
        </form>
        <p class="switch"><a href="<?= e(siteUrl('login.php')) ?>">← Student Login</a></p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
