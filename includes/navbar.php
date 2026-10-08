<nav class="ib-navbar">
    <div class="ib-navbar-brand">
        <a href="<?= e(siteUrl('student/dashboard.php')) ?>">🎯 Interview Buddy</a>
    </div>
    <div class="ib-navbar-user">
        <?php if (isStudentLoggedIn()): ?>
            <span>Hi, <?= e($_SESSION['user_name']) ?></span>
            <a href="<?= e(siteUrl('logout.php')) ?>" class="ib-btn ib-btn-sm">Logout</a>
        <?php endif; ?>
    </div>
</nav>
