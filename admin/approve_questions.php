<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_question'])) {
    $question_id = (int)$_POST['question_id'];
    $subject_id  = $_POST['subject_id'] ?: null;
    $topic_id    = $_POST['topic_id'] ?: null;
    $difficulty  = $_POST['difficulty'];
    $ideal       = trim($_POST['ideal_answer'] ?? '');

    $stmt = $pdo->prepare("UPDATE questions SET subject_id=?, topic_id=?, difficulty=?, status='Approved' WHERE question_id=?");
    $stmt->execute([$subject_id, $topic_id, $difficulty, $question_id]);

    if ($ideal !== '') {
        $exists = $pdo->prepare("SELECT answer_id FROM answers WHERE question_id=?");
        $exists->execute([$question_id]);
        if ($exists->fetch()) {
            $pdo->prepare("UPDATE answers SET ideal_answer=?, approved_by=? WHERE question_id=?")->execute([$ideal, $admin_id, $question_id]);
        } else {
            $pdo->prepare("INSERT INTO answers (question_id, ideal_answer, approved_by) VALUES (?,?,?)")->execute([$question_id, $ideal, $admin_id]);
        }
    }
    header('Location: approve_questions.php');
    exit;
}

if (isset($_GET['reject'])) {
    $pdo->prepare("UPDATE questions SET status='Rejected' WHERE question_id=?")->execute([(int)$_GET['reject']]);
    header('Location: approve_questions.php');
    exit;
}

// Only questions whose parent experience was already approved by admin
$stmt = $pdo->query("SELECT q.*, c.company_name FROM questions q
                      JOIN interview_experiences e ON q.exp_id = e.exp_id
                      JOIN companies c ON e.company_id = c.company_id
                      WHERE q.status = 'Pending' AND e.status = 'Approved'
                      ORDER BY q.created_at DESC");
$pendingQuestions = $stmt->fetchAll();

$subjects = getAllSubjects($pdo);

$pageTitle = 'Approve Questions';
$sidebarType = 'admin';
$activePage = 'approve_questions';
$extraCss = ['admin.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>Approve Questions & Add Ideal Answers</h2>
        <?php if (!$pendingQuestions): ?>
            <div class="ib-card"><p style="color:var(--muted)">No questions waiting for approval right now.</p></div>
        <?php endif; ?>

        <?php foreach ($pendingQuestions as $q): ?>
        <div class="ib-card">
            <p style="color:var(--muted); margin:0 0 6px;"><?= e($q['company_name']) ?> · <?= e($q['type']) ?></p>
            <h3 style="margin-top:0;">Q. <?= e($q['question']) ?></h3>
            <form method="POST">
                <input type="hidden" name="approve_question" value="1">
                <input type="hidden" name="question_id" value="<?= $q['question_id'] ?>">
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <div class="ib-form-group" style="flex:1; min-width:160px;">
                        <label>Subject</label>
                        <select name="subject_id">
                            <option value="">— None —</option>
                            <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['subject_id'] ?>"><?= e($s['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ib-form-group" style="flex:1; min-width:160px;">
                        <label>Difficulty</label>
                        <select name="difficulty">
                            <option <?= $q['difficulty']=='Easy'?'selected':'' ?>>Easy</option>
                            <option <?= $q['difficulty']=='Medium'?'selected':'' ?>>Medium</option>
                            <option <?= $q['difficulty']=='Hard'?'selected':'' ?>>Hard</option>
                        </select>
                    </div>
                </div>
                <div class="ib-form-group">
                    <label>Ideal Answer (Add / Verify)</label>
                    <textarea name="ideal_answer" placeholder="Write the model/ideal answer used to evaluate students..."></textarea>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="ib-btn ib-btn-success">Approve Question</button>
                    <a href="?reject=<?= $q['question_id'] ?>" data-confirm="Reject this question?" class="ib-btn ib-btn-danger">Reject Question</a>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
