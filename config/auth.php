<?php
require_once __DIR__ . '/session.php';

function siteUrl($path = '') {
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $basePath = '';

    if ($documentRoot && strpos($projectRoot, $documentRoot) === 0) {
        $basePath = substr($projectRoot, strlen($documentRoot));
    }

    $basePath = trim($basePath, '/');
    $path = ltrim($path, '/');

    return ($basePath ? '/' . $basePath : '') . ($path ? '/' . $path : '');
}

/** Is a student currently logged in? */
function isStudentLoggedIn() {
    return isset($_SESSION['user_id']);
}

/** Is an admin currently logged in? */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/** Force student login, otherwise redirect */
function requireStudentLogin() {
    if (!isStudentLoggedIn()) {
        header('Location: ' . siteUrl('login.php'));
        exit;
    }
}

/** Force admin login, otherwise redirect */
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . siteUrl('admin/login.php'));
        exit;
    }
}

/** Simple CSRF-lite helper: escape output */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
