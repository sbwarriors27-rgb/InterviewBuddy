<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireStudentLogin();

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $college = trim($_POST['college'] ?? '');
    $role    = trim($_POST['role'] ?? '');

    $stmt = $pdo->prepare("UPDATE users SET name=?, college=?, role=? WHERE user_id=?");
    $stmt->execute([$name, $college, $role, $user_id]);
    $_SESSION['user_name'] = $name;

    if (!empty($_POST['new_password'])) {
        $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE user_id=?");
        $stmt->execute([$hash, $user_id]);
    }
    $message = 'Profile updated successfully.';
}

$student = getStudentById($pdo, $user_id);

$pageTitle = 'My Profile';
$sidebarType = 'student';
$activePage = 'profile';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>My Profile</h2>
        <?php if ($message): ?><div class="ib-alert ib-alert-success"><?= e($message) ?></div><?php endif; ?>
        <div class="ib-card" style="max-width:480px;">
            <form method="POST">
                <div class="ib-form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= e($student['name']) ?>" required>
                </div>
                <div class="ib-form-group">
                    <label>Email (cannot be changed)</label>
                    <input type="email" value="<?= e($student['email']) ?>" disabled>
                </div>
                <div class="ib-form-group">
                    <label>College</label>
                    <input type="text" name="college" value="<?= e($student['college']) ?>">
                </div>
                <div class="ib-form-group">
                    <label>Target Role</label>
                    <input type="text" name="role" value="<?= e($student['role']) ?>">
                </div>
                <div class="ib-form-group">
                    <label>New Password (leave blank to keep current)</label>
                    <input type="password" name="new_password">
                </div>
                <button class="ib-btn" type="submit">Save Changes</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
