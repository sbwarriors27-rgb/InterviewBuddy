<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireStudentLogin();

$user_id = $_SESSION['user_id'];
$mock_id = $_GET['mock_id'] ?? null;

// ---------- STEP 1: start a new mock test ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_mock'])) {
    $company_id = (int)$_POST['company_id'];
    $role       = trim($_POST['job_role']);
    $numQ       = max(3, min(15, (int)$_POST['num_questions']));

    $stmt = $pdo->prepare("SELECT question_id FROM questions q
                            JOIN interview_experiences e ON q.exp_id = e.exp_id
                            WHERE q.status='Approved' AND e.company_id = ?
                            ORDER BY RAND() LIMIT ?");
    $stmt->bindValue(1, $company_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $numQ, PDO::PARAM_INT);
    $stmt->execute();
    $qIds = array_column($stmt->fetchAll(), 'question_id');

    // Fallback: if company doesn't have enough approved questions, top up with any approved question
    if (count($qIds) < $numQ) {
        $need = $numQ - count($qIds);
        $exclude = $qIds ? implode(',', array_map('intval', $qIds)) : '0';
        $stmt2 = $pdo->prepare("SELECT question_id FROM questions WHERE status='Approved' AND question_id NOT IN ($exclude) ORDER BY RAND() LIMIT $need");
        $stmt2->execute();
        $qIds = array_merge($qIds, array_column($stmt2->fetchAll(), 'question_id'));
    }

    if (!$qIds) {
        $error = "No approved questions are available yet for mock interviews. Please check back later.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO mock_tests (user_id, company_id, role, total_questions, status) VALUES (?,?,?,?, 'In Progress')");
        $stmt->execute([$user_id, $company_id, $role, count($qIds)]);
        $mock_id = $pdo->lastInsertId();

        $insR = $pdo->prepare("INSERT INTO responses (mock_id, question_id) VALUES (?,?)");
        foreach ($qIds as $qid) $insR->execute([$mock_id, $qid]);

        header("Location: " . siteUrl("student/mock_interview.php?mock_id=$mock_id&q=0"));
        exit;
    }
}

// ---------- STEP 2: save an answer for the current question ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_answer'])) {
    $response_id = (int)$_POST['response_id'];
    $answer = trim($_POST['answer'] ?? '');
    $stmt = $pdo->prepare("UPDATE responses SET student_answer = ? WHERE response_id = ?
                            AND mock_id IN (SELECT mock_id FROM mock_tests WHERE user_id = ?)");
    $stmt->execute([$answer, $response_id, $user_id]);

    $nextQ = (int)$_POST['next_index'];
    $mid = (int)$_POST['mock_id'];

    if (isset($_POST['finish'])) {
        $ownership = $pdo->prepare("SELECT mock_id FROM mock_tests WHERE mock_id = ? AND user_id = ?");
        $ownership->execute([$mid, $user_id]);
        if (!$ownership->fetchColumn()) {
            http_response_code(403);
            exit('You are not allowed to submit this mock interview.');
        }
        require_once __DIR__ . '/../api/send_to_ml.php';
        evaluateMockTest($pdo, $mid);
        header("Location: " . siteUrl("student/result.php?mock_id=$mid"));
        exit;
    }
    header("Location: " . siteUrl("student/mock_interview.php?mock_id=$mid&q=$nextQ"));
    exit;
}

// ---------- Render: question screen ----------
if ($mock_id) {
    $stmt = $pdo->prepare("SELECT m.*, c.company_name FROM mock_tests m JOIN companies c ON m.company_id=c.company_id WHERE m.mock_id=? AND m.user_id=?");
    $stmt->execute([$mock_id, $user_id]);
    $mock = $stmt->fetch();

    if (!$mock) { header('Location: ' . siteUrl('student/mock_interview.php')); exit; }

    $stmt = $pdo->prepare("SELECT r.response_id, r.student_answer, q.question, q.type, q.difficulty
                            FROM responses r JOIN questions q ON r.question_id = q.question_id
                            WHERE r.mock_id = ? ORDER BY r.response_id");
    $stmt->execute([$mock_id]);
    $responses = $stmt->fetchAll();

    $idx = max(0, min(count($responses) - 1, (int)($_GET['q'] ?? 0)));
    $current = $responses[$idx];

    $pageTitle = 'Mock Interview';
    $sidebarType = 'student';
    $activePage = 'mock_interview';
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/navbar.php';
    ?>
    <div class="ib-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <div class="ib-content">
            <div class="ib-card" style="max-width:720px;">
                <div style="display:flex; justify-content:space-between; color:var(--muted); font-size:14px;">
                    <span>Company: <strong><?= e($mock['company_name']) ?></strong></span>
                    <span>Role: <strong><?= e($mock['role']) ?></strong></span>
                </div>
                <h3 style="margin-top:18px;">Q<?= $idx+1 ?>. <?= e($current['question']) ?></h3>
                <span class="badge badge-pending"><?= e($current['type']) ?> · <?= e($current['difficulty']) ?></span>

                <form method="POST" style="margin-top:16px;">
                    <input type="hidden" name="save_answer" value="1">
                    <input type="hidden" name="mock_id" value="<?= $mock_id ?>">
                    <input type="hidden" name="response_id" value="<?= $current['response_id'] ?>">
                    <div class="ib-form-group">
                        <textarea name="answer" rows="6" placeholder="Write your answer here..."><?= e($current['student_answer']) ?></textarea>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <?php if ($idx > 0): ?>
                            <button type="submit" name="next_index" value="<?= $idx-1 ?>" class="ib-btn ib-btn-outline">Previous</button>
                        <?php else: ?><span></span><?php endif; ?>

                        <?php if ($idx < count($responses)-1): ?>
                            <button type="submit" name="next_index" value="<?= $idx+1 ?>" class="ib-btn">Next</button>
                        <?php else: ?>
                            <button type="submit" name="finish" value="1" class="ib-btn ib-btn-success">Submit Test</button>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="ib-question-nav">
                    <?php foreach ($responses as $i => $r): ?>
                        <a href="?mock_id=<?= $mock_id ?>&q=<?= $i ?>">
                            <button type="button" class="<?= $i==$idx?'current':'' ?> <?= $r['student_answer']?'answered':'' ?>"><?= $i+1 ?></button>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <?php
    exit;
}

// ---------- Render: start-mock selection form ----------
$companies = getAllCompanies($pdo);
$pageTitle = 'Mock Interview';
$sidebarType = 'student';
$activePage = 'mock_interview';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <div class="ib-card" style="max-width:520px;">
            <h2>Start a Mock Interview</h2>
            <?php if (!empty($error)): ?><div class="ib-alert ib-alert-error"><?= e($error) ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="start_mock" value="1">
                <div class="ib-form-group">
                    <label>Company</label>
                    <select name="company_id" required>
                        <?php foreach ($companies as $c): ?>
                        <option value="<?= $c['company_id'] ?>"><?= e($c['company_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ib-form-group">
                    <label>Job Role</label>
                    <input type="text" name="job_role" placeholder="e.g. Software Engineer" required>
                </div>
                <div class="ib-form-group">
                    <label>Number of Questions</label>
                    <input type="number" name="num_questions" value="10" min="3" max="15">
                </div>
                <button type="submit" class="ib-btn" style="width:100%;">Start Mock Interview</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
