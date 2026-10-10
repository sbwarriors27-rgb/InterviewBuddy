<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireStudentLogin();

$user_id = $_SESSION['user_id'];

$companyFilter = $_GET['company'] ?? '';
$subjectFilter = $_GET['subject'] ?? '';
$companyFilter = is_scalar($companyFilter) ? trim((string)$companyFilter) : '';
$subjectFilter = is_scalar($subjectFilter) ? trim((string)$subjectFilter) : '';

$questionCount = 0;
$questions = [];
$companies = [];
$subjects = [];
$databaseError = false;

try {
    $hasQuestionCompanyId = $pdo->query("SHOW COLUMNS FROM questions LIKE 'company_id'")->fetch() !== false;
    $companyColumn = $hasQuestionCompanyId
        ? 'COALESCE(q.company_id, e.company_id)'
        : 'e.company_id';
    $joinSql = " FROM questions q
                 LEFT JOIN interview_experiences e ON q.exp_id = e.exp_id
                 LEFT JOIN companies c ON " . $companyColumn . " = c.company_id
                 LEFT JOIN subjects s ON q.subject_id = s.subject_id";
    $whereSql = " WHERE q.status = 'Approved'";
    $filterParams = [];

    if ($companyFilter !== '') {
        $whereSql .= " AND " . $companyColumn . " = ?";
        $filterParams[] = $companyFilter;
    }
    if ($subjectFilter !== '') {
        $whereSql .= " AND q.subject_id = ?";
        $filterParams[] = $subjectFilter;
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*)" . $joinSql . $whereSql);
    $countStmt->execute($filterParams);
    $questionCount = (int)$countStmt->fetchColumn();

    $sql = "SELECT q.question_id, q.question, q.type, s.subject_name, c.company_name,
                   a.ideal_answer,
                   EXISTS(SELECT 1 FROM bookmarks b WHERE b.user_id=? AND b.question_id=q.question_id) AS bookmarked"
           . $joinSql
           . " LEFT JOIN answers a ON a.question_id = q.question_id"
           . $whereSql
           . " ORDER BY q.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$user_id], $filterParams));
    $questions = $stmt->fetchAll();

    $companies = getAllCompanies($pdo);
    $subjects = getAllSubjects($pdo);
} catch (PDOException $error) {
    error_log('Search questions query failed: ' . $error->getMessage());
    $databaseError = true;
}

$pageTitle = 'Search Questions';
$sidebarType = 'student';
$activePage = 'search_questions';
$extraCss = ['search_questions.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ib-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="ib-content">
        <section class="ib-question-filters" aria-label="Filter interview questions">
            <form method="GET" class="ib-card ib-question-filter-form">
                <div class="ib-form-group">
                    <label for="company-filter">Company</label>
                    <select id="company-filter" name="company">
                        <option value="">All</option>
                        <?php foreach ($companies as $c): ?>
                        <option value="<?= e($c['company_id']) ?>" <?= $companyFilter == $c['company_id'] ? 'selected' : '' ?>><?= e($c['company_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ib-form-group">
                    <label for="subject-filter">Subject</label>
                    <select id="subject-filter" name="subject">
                        <option value="">All</option>
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?= e($s['subject_id']) ?>" <?= $subjectFilter == $s['subject_id'] ? 'selected' : '' ?>><?= e($s['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="ib-btn ib-question-apply" type="submit">Apply Filters</button>
            </form>
        </section>

        <section aria-labelledby="interview-questions-heading">
            <div class="ib-question-heading">
                <div>
                    <h1 id="interview-questions-heading">Interview Questions</h1>
                    <p>Explore questions by company and subject to prepare for interviews.</p>
                </div>
                <?php if ($databaseError): ?>
                <p class="ib-question-count" role="alert">Questions could not be loaded. Please try again later.</p>
                <?php else: ?>
                <p class="ib-question-count" aria-live="polite">
                    <strong><?= $questionCount ?></strong>
                    <?= $questionCount === 1 ? 'question found' : 'questions found' ?>
                </p>
                <?php endif; ?>
            </div>

            <?php if ($databaseError): ?>
            <div class="ib-card ib-question-empty" role="alert">
                Interview questions are temporarily unavailable.
            </div>
            <?php elseif ($questions): ?>
            <div class="ib-question-grid">
                <?php foreach ($questions as $q): ?>
                <article class="ib-card ib-question-card">
                    <div class="ib-question-card-top">
                        <span class="ib-question-id">Question #<?= e($q['question_id']) ?></span>
                        <span class="ib-question-type"><?= e($q['type']) ?></span>
                    </div>
                    <h2 class="ib-question-text"><?= e($q['question']) ?></h2>
                    <dl class="ib-question-details">
                        <div>
                            <dt>Company</dt>
                            <dd><?= e($q['company_name'] ?? '—') ?></dd>
                        </div>
                        <div>
                            <dt>Subject</dt>
                            <dd><?= e($q['subject_name'] ?? '—') ?></dd>
                        </div>
                    </dl>
                    <div class="ib-question-actions">
                        <?php if (trim((string)($q['ideal_answer'] ?? '')) !== ''): ?>
                        <details class="ib-question-answer">
                            <summary class="ib-btn ib-btn-sm ib-btn-outline">View Answer</summary>
                            <div class="ib-question-answer-text"><?= e($q['ideal_answer']) ?></div>
                        </details>
                        <?php endif; ?>
                        <form method="POST" action="<?= e(siteUrl('api/toggle_bookmark.php')) ?>">
                            <input type="hidden" name="question_id" value="<?= e($q['question_id']) ?>">
                            <input type="hidden" name="redirect" value="search_questions.php">
                            <button type="submit" class="ib-btn ib-btn-sm <?= $q['bookmarked'] ? 'ib-btn-success' : 'ib-btn-outline' ?>" aria-label="<?= $q['bookmarked'] ? 'Remove bookmark for' : 'Bookmark' ?> question #<?= e($q['question_id']) ?>">
                                <?= $q['bookmarked'] ? '★ Remove Bookmark' : '☆ Bookmark' ?>
                            </button>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="ib-card ib-question-empty" role="status">
                No approved questions match these filters.
            </div>
            <?php endif; ?>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
