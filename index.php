<?php
require_once __DIR__ . '/config/auth.php';

if (isStudentLoggedIn()) {
    header('Location: ' . siteUrl('student/dashboard.php'));
    exit;
}
if (isAdminLoggedIn()) {
    header('Location: ' . siteUrl('admin/dashboard.php'));
    exit;
}
$pageTitle = 'Welcome';
include __DIR__ . '/includes/header.php';
?>
<main class="landing-page">
    <section class="hero">
        <div class="hero-copy">
            <span class="eyebrow">Professional placement prep</span>
            <h1>Crack your interviews with real experience-based practice.</h1>
            <p>Interview Buddy brings together verified interview experiences, curated technical questions, and AI-powered mock interview preparation so you can build confidence and improve faster.</p>
            <div class="hero-actions">
                <a href="<?= e(siteUrl('login.php')) ?>" class="ib-btn">Student Login</a>
                <a href="<?= e(siteUrl('register.php')) ?>" class="ib-btn ib-btn-outline">Register</a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-card">
                <h3>Why Interview Buddy?</h3>
                <ul>
                    <li>Real interview questions from past students</li>
                    <li>AI-backed mock interview evaluation</li>
                    <li>Track progress, scores and strengths</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="features">
        <h2>Built for serious interview preparation</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <h3>Verified questions</h3>
                <p>Practice with questions submitted by students and approved by mentors.</p>
            </div>
            <div class="feature-card">
                <h3>Mock interview scoring</h3>
                <p>Submit answers and get instant feedback to improve your delivery.</p>
            </div>
            <div class="feature-card">
                <h3>Personal progress</h3>
                <p>See your mock test history, average score, and areas to improve.</p>
            </div>
        </div>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
