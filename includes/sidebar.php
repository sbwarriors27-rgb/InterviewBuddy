<?php
// $sidebarType = 'student' | 'admin', $activePage = current file name without .php
$current = $activePage ?? '';
?>
<aside class="ib-sidebar">
<?php if (($sidebarType ?? '') === 'admin'): ?>
    <ul>
        <li class="<?= $current==='dashboard'?'active':'' ?>"><a href="<?= e(siteUrl('admin/dashboard.php')) ?>">📊 Dashboard</a></li>
        <li class="<?= $current==='manage_students'?'active':'' ?>"><a href="<?= e(siteUrl('admin/manage_students.php')) ?>">🎓 Students</a></li>
        <li class="<?= $current==='manage_companies'?'active':'' ?>"><a href="<?= e(siteUrl('admin/manage_companies.php')) ?>">🏢 Companies</a></li>
        <li class="<?= $current==='pending_questions'?'active':'' ?>"><a href="<?= e(siteUrl('admin/pending_questions.php')) ?>">📝 Pending Experiences</a></li>
        <li class="<?= $current==='approve_questions'?'active':'' ?>"><a href="<?= e(siteUrl('admin/approve_questions.php')) ?>">✅ Approve Questions</a></li>
        <li class="<?= $current==='add_answers'?'active':'' ?>"><a href="<?= e(siteUrl('admin/add_answers.php')) ?>">💡 Ideal Answers</a></li>
        <li class="<?= $current==='reports'?'active':'' ?>"><a href="<?= e(siteUrl('admin/reports.php')) ?>">📈 Reports</a></li>
        <li><a href="<?= e(siteUrl('logout.php')) ?>">🚪 Logout</a></li>
    </ul>
<?php else: ?>
    <ul>
        <li class="<?= $current==='dashboard'?'active':'' ?>"><a href="<?= e(siteUrl('student/dashboard.php')) ?>">📊 Dashboard</a></li>
        <li class="<?= $current==='profile'?'active':'' ?>"><a href="<?= e(siteUrl('student/profile.php')) ?>">👤 My Profile</a></li>
        <li class="<?= $current==='upload_experience'?'active':'' ?>"><a href="<?= e(siteUrl('student/upload_experience.php')) ?>">⬆️ Upload Experience</a></li>
        <li class="<?= $current==='search_questions'?'active':'' ?>"><a href="<?= e(siteUrl('student/search_questions.php')) ?>">🔍 Search Questions</a></li>
        <li class="<?= $current==='mock_interview'?'active':'' ?>"><a href="<?= e(siteUrl('student/mock_interview.php')) ?>">🎤 Mock Interview</a></li>
        <li class="<?= $current==='history'?'active':'' ?>"><a href="<?= e(siteUrl('student/history.php')) ?>">🕘 History</a></li>
        <li class="theme-section">
            <span class="theme-section-label">Appearance</span>
            <div class="theme-options">
                <button type="button" class="theme-option" data-theme-option="light" aria-pressed="false"><span class="theme-option-title"><span class="theme-swatch swatch-light"></span>Light</span></button>
                <button type="button" class="theme-option" data-theme-option="dark" aria-pressed="false"><span class="theme-option-title"><span class="theme-swatch swatch-dark"></span>Dark</span></button>
            </div>
        </li>
        <li><a href="<?= e(siteUrl('logout.php')) ?>">🚪 Logout</a></li>
    </ul>
<?php endif; ?>
</aside>
