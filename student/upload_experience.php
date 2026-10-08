<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireStudentLogin();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = trim($_POST['company_name'] ?? '');
    $role        = trim($_POST['job_role'] ?? '');
    $rounds      = (int)($_POST['rounds'] ?? 1);
    $difficulty  = $_POST['difficulty'] ?? 'Medium';
    $tips        = trim($_POST['tips'] ?? '');

    $technical = array_filter(array_map('trim', explode("\n", $_POST['technical_questions'] ?? '')));
    $hr        = array_filter(array_map('trim', explode("\n", $_POST['hr_questions'] ?? '')));
    $aptitude  = array_filter(array_map('trim', explode("\n", $_POST['aptitude_questions'] ?? '')));
    $coding    = array_filter(array_map('trim', explode("\n", $_POST['coding_questions'] ?? '')));

    if ($companyName === '' || $role === '') {
        $errors[] = 'Company name and job role are required.';
    }
    if (!$technical && !$hr && !$aptitude && !$coding) {
        $errors[] = 'Please add at least one question.';
    }

    if (!$errors) {
        // Find or create the company
        $stmt = $pdo->prepare("SELECT company_id FROM companies WHERE company_name = ?");
        $stmt->execute([$companyName]);
        $company = $stmt->fetch();
        if ($company) {
            $company_id = $company['company_id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO companies (company_name) VALUES (?)");
            $stmt->execute([$companyName]);
            $company_id = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("INSERT INTO interview_experiences (user_id, company_id, role, rounds, tips, difficulty, status)
                                VALUES (?,?,?,?,?,?,'Pending')");
        $stmt->execute([$user_id, $company_id, $role, $rounds, $tips, $difficulty]);
        $exp_id = $pdo->lastInsertId();

        $qStmt = $pdo->prepare("INSERT INTO questions (exp_id, question, type, difficulty, status) VALUES (?,?,?,?, 'Pending')");
        foreach ($technical as $q) $qStmt->execute([$exp_id, $q, 'Technical', $difficulty]);
        foreach ($hr as $q)        $qStmt->execute([$exp_id, $q, 'HR', $difficulty]);
        foreach ($aptitude as $q)  $qStmt->execute([$exp_id, $q, 'Aptitude', $difficulty]);
        foreach ($coding as $q)    $qStmt->execute([$exp_id, $q, 'Coding', $difficulty]);

        $success = true;
    }
}

$companies = getAllCompanies($pdo);

$pageTitle = 'Upload Interview Experience';
$sidebarType = 'student';
$activePage = 'upload_experience';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <div class="ib-card" style="max-width:720px;">
            <h2>Upload Interview Experience</h2>
            <?php if ($success): ?>
                <div class="ib-alert ib-alert-success">Thanks! Your experience has been submitted and is pending admin review.</div>
            <?php endif; ?>
            <?php foreach ($errors as $err): ?>
                <div class="ib-alert ib-alert-error"><?= e($err) ?></div>
            <?php endforeach; ?>

            <form method="POST">
                <div class="ib-form-group">
                    <label>Company Name</label>
                    <input list="companyList" name="company_name" required placeholder="Select or type a new company">
                    <datalist id="companyList">
                        <?php foreach ($companies as $c): ?>
                        <option value="<?= e($c['company_name']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="ib-form-group">
                    <label>Job Role</label>
                    <input type="text" name="job_role" required placeholder="e.g. Software Engineer">
                </div>
                <div class="ib-form-group">
                    <label>Interview Rounds</label>
                    <input type="number" name="rounds" min="1" value="3">
                </div>
                <div class="ib-form-group">
                    <label>Difficulty Level</label>
                    <select name="difficulty">
                        <option>Easy</option>
                        <option selected>Medium</option>
                        <option>Hard</option>
                    </select>
                </div>
                <div class="ib-form-group">
                    <label>Technical Questions (one per line)</label>
                    <textarea name="technical_questions" placeholder="What is normalization in DBMS?"></textarea>
                </div>
                <div class="ib-form-group">
                    <label>HR Questions (one per line)</label>
                    <textarea name="hr_questions" placeholder="Tell me about yourself"></textarea>
                </div>
                <div class="ib-form-group">
                    <label>Aptitude Questions (one per line)</label>
                    <textarea name="aptitude_questions"></textarea>
                </div>
                <div class="ib-form-group">
                    <label>Coding Questions (one per line)</label>
                    <textarea name="coding_questions"></textarea>
                </div>
                <div class="ib-form-group">
                    <label>Tips / Preparation Advice</label>
                    <textarea name="tips" placeholder="Share tips for future students..."></textarea>
                </div>
                <button type="submit" class="ib-btn">Submit Experience</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
