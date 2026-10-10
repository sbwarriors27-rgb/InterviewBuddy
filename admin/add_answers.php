<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();

$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_answer'])) {
    $question_id = (int)$_POST['question_id'];
    $ideal = trim($_POST['ideal_answer']);
    $exists = $pdo->prepare("SELECT answer_id FROM answers WHERE question_id=?");
    $exists->execute([$question_id]);
    if ($exists->fetch()) {
        $pdo->prepare("UPDATE answers SET ideal_answer=?, approved_by=? WHERE question_id=?")->execute([$ideal, $admin_id, $question_id]);
    } else {
        $pdo->prepare("INSERT INTO answers (question_id, ideal_answer, approved_by) VALUES (?,?,?)")->execute([$question_id, $ideal, $admin_id]);
    }
    header('Location: add_answers.php');
    exit;
}

$company = $_GET['company'] ?? '';
$subject = $_GET['subject'] ?? '';

$sql = "SELECT q.question_id, q.question, q.type, q.difficulty, e.exp_id,
               COALESCE(c.company_name, 'Unlinked question') AS company_name,
               s.subject_name, a.ideal_answer
        FROM questions q
        LEFT JOIN interview_experiences e ON q.exp_id = e.exp_id
        LEFT JOIN companies c ON q.company_id = c.company_id
        LEFT JOIN subjects s ON q.subject_id = s.subject_id
        LEFT JOIN answers a ON a.question_id = q.question_id
        WHERE q.status = 'Approved'";
$params = [];
if ($company !== '') { $sql .= " AND c.company_id = ?"; $params[] = $company; }
if ($subject !== '') { $sql .= " AND q.subject_id = ?"; $params[] = $subject; }
$sql .= " ORDER BY (a.ideal_answer IS NULL OR TRIM(a.ideal_answer) = '') DESC, q.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll();

$companies = getAllCompanies($pdo);
$subjects = getAllSubjects($pdo);

$pageTitle = 'Question Bank';
$sidebarType = 'admin';
$activePage = 'add_answers';
$extraCss = ['admin.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>Question Bank</h2>
        <div class="ib-card">
            <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div class="ib-form-group" style="flex:1;"><label>Company</label>
                    <select name="company">
                        <option value="">All</option>
                        <?php foreach ($companies as $c): ?>
                        <option value="<?= $c['company_id'] ?>" <?= $company==$c['company_id']?'selected':'' ?>><?= e($c['company_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ib-form-group" style="flex:1;"><label>Subject</label>
                    <select name="subject">
                        <option value="">All</option>
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['subject_id'] ?>" <?= $subject==$s['subject_id']?'selected':'' ?>><?= e($s['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="ib-btn" type="submit">Search</button>
            </form>
        </div>

        <?php foreach ($questions as $q): ?>
        <div class="ib-card">
            <p style="color:var(--muted); margin:0 0 6px;"><?= e($q['company_name']) ?> · <?= e($q['subject_name'] ?? '—') ?> · <?= e($q['type']) ?> · <?= e($q['difficulty']) ?><?= !$q['exp_id'] ? ' · Unlinked from an experience' : '' ?></p>
            <h3 style="margin-top:0;">Q. <?= e($q['question']) ?></h3>
            <form method="POST">
                <input type="hidden" name="save_answer" value="1">
                <input type="hidden" name="question_id" value="<?= $q['question_id'] ?>">
                <div class="ib-form-group">
                    <textarea name="ideal_answer" placeholder="Ideal answer..."><?= e($q['ideal_answer']) ?></textarea>
                </div>
                <button class="ib-btn" type="submit"><?= $q['ideal_answer'] ? 'Update Answer' : 'Save Answer' ?></button>
            </form>
        </div>
        <?php endforeach; ?>
        <?php if (!$questions): ?><div class="ib-card"><p style="color:var(--muted)">No approved questions found.</p></div><?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
