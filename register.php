<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

if (isStudentLoggedIn()) {
    header('Location: ' . siteUrl('student/dashboard.php'));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $college  = trim($_POST['college'] ?? '');
    $role     = trim($_POST['role'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = 'Name, email and password are required.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, college) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $email, $hash, $role, $college]);

        $_SESSION['user_id']   = $pdo->lastInsertId();
        $_SESSION['user_name'] = $name;
        header('Location: ' . siteUrl('student/dashboard.php'));
        exit;
    }
}

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>
<div class="ib-auth-wrapper">
    <div class="ib-auth-box">
        <h2>Create your account</h2>
        <?php foreach ($errors as $err): ?>
            <div class="ib-alert ib-alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>
        <form method="POST">
            <div class="ib-form-group">
                <label>Full Name</label>
                <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
            </div>
            <div class="ib-form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="ib-form-group">
                <label>College</label>
                <input type="text" name="college" value="<?= e($_POST['college'] ?? '') ?>">
            </div>
            <div class="ib-form-group">
                <label>Target Job Role</label>
                <input type="text" name="role" placeholder="e.g. Software Engineer" value="<?= e($_POST['role'] ?? '') ?>">
            </div>
            <div class="ib-form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="ib-form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="ib-btn" style="width:100%">Register</button>
        </form>
        <p class="switch">Already have an account? <a href="<?= e(siteUrl('login.php')) ?>">Login</a></p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
