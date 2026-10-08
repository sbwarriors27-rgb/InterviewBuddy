<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

if (isStudentLoggedIn()) {
    header('Location: ' . siteUrl('student/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: ' . siteUrl('student/dashboard.php'));
        exit;
    }
    $error = 'Invalid email or password.';
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="ib-auth-wrapper">
    <div class="ib-auth-box">
        <h2>Welcome back 👋</h2>
        <?php if ($error): ?><div class="ib-alert ib-alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="ib-form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="ib-form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="ib-btn" style="width:100%">Login</button>
        </form>
        <p class="switch">Don't have an account? <a href="<?= e(siteUrl('register.php')) ?>">Register</a></p>
        <p class="switch"><a href="<?= e(siteUrl('admin/login.php')) ?>">Admin Login →</a></p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
