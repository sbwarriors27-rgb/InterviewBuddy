<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireStudentLogin();

$user_id = $_SESSION['user_id'];

$companyFilter = $_GET['company'] ?? '';
$subjectFilter = $_GET['subject'] ?? '';
$difficultyFilter = $_GET['difficulty'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');

$sql = "SELECT q.question_id, q.question, q.type, q.difficulty, s.subject_name, c.company_name,
               EXISTS(SELECT 1 FROM bookmarks b WHERE b.user_id=? AND b.question_id=q.question_id) AS bookmarked
        FROM questions q
        JOIN interview_experiences e ON q.exp_id = e.exp_id
        JOIN companies c ON e.company_id = c.company_id
        LEFT JOIN subjects s ON q.subject_id = s.subject_id
        WHERE q.status = 'Approved'";
$params = [$user_id];

if ($companyFilter !== '') { $sql .= " AND c.company_id = ?"; $params[] = $companyFilter; }
if ($subjectFilter !== '') { $sql .= " AND q.subject_id = ?"; $params[] = $subjectFilter; }
if ($difficultyFilter !== '') { $sql .= " AND q.difficulty = ?"; $params[] = $difficultyFilter; }
if ($keyword !== '') { $sql .= " AND q.question LIKE ?"; $params[] = "%$keyword%"; }

$sql .= " ORDER BY q.created_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll();

$companies = getAllCompanies($pdo);
$subjects = getAllSubjects($pdo);

$pageTitle = 'Search Questions';
$sidebarType = 'student';
$activePage = 'search_questions';
$extraJs = ['search.js'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ib-content">
        <h2>Search Interview Questions</h2>
        <div class="ib-card">
            <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div class="ib-form-group" style="flex:1; min-width:160px;">
                    <label>Company</label>
                    <select name="company">
                        <option value="">All</option>
                        <?php foreach ($companies as $c): ?>
                        <option value="<?= $c['company_id'] ?>" <?= $companyFilter==$c['company_id']?'selected':'' ?>><?= e($c['company_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ib-form-group" style="flex:1; min-width:160px;">
                    <label>Subject</label>
                    <select name="subject">
                        <option value="">All</option>
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['subject_id'] ?>" <?= $subjectFilter==$s['subject_id']?'selected':'' ?>><?= e($s['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ib-form-group" style="flex:1; min-width:140px;">
                    <label>Difficulty</label>
                    <select name="difficulty">
                        <option value="">All</option>
                        <option <?= $difficultyFilter=='Easy'?'selected':'' ?>>Easy</option>
                        <option <?= $difficultyFilter=='Medium'?'selected':'' ?>>Medium</option>
                        <option <?= $difficultyFilter=='Hard'?'selected':'' ?>>Hard</option>
                    </select>
                </div>
                <div class="ib-form-group" style="flex:2; min-width:200px;">
                    <label>Keyword</label>
                    <input type="text" name="keyword" value="<?= e($keyword) ?>" placeholder="Search question text...">
                </div>
                <button class="ib-btn" type="submit">Filter</button>
            </form>
        </div>

        <div class="ib-card">
            <input type="text" id="liveFilter" placeholder="Quick filter on this page..." style="width:100%; padding:10px; margin-bottom:14px; border:1px solid var(--border); border-radius:8px;">
            <table class="ib-table" id="resultsTable">
                <thead><tr><th>Question</th><th>Company</th><th>Subject</th><th>Type</th><th>Difficulty</th><th>Bookmark</th></tr></thead>
                <tbody>
                <?php foreach ($questions as $q): ?>
                    <tr>
                        <td><?= e($q['question']) ?></td>
                        <td><?= e($q['company_name']) ?></td>
                        <td><?= e($q['subject_name'] ?? '—') ?></td>
                        <td><?= e($q['type']) ?></td>
                        <td><?= e($q['difficulty']) ?></td>
                        <td>
                            <form method="POST" action="<?= e(siteUrl('api/toggle_bookmark.php')) ?>" style="display:inline">
                                <input type="hidden" name="question_id" value="<?= $q['question_id'] ?>">
                                <input type="hidden" name="redirect" value="search_questions.php">
                                <button type="submit" class="ib-btn ib-btn-sm <?= $q['bookmarked']?'ib-btn-success':'ib-btn-outline' ?>">
                                    <?= $q['bookmarked'] ? '★ Saved' : '☆ Save' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$questions): ?>
                    <tr><td colspan="6" style="text-align:center; color:var(--muted)">No questions found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
