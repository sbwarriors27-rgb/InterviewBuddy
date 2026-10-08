<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' | Interview Buddy' : 'Interview Buddy' ?></title>
<link rel="stylesheet" href="<?= e(siteUrl('assets/css/style.css')) ?>">
<?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
<link rel="stylesheet" href="<?= e(siteUrl('assets/css/' . $css)) ?>">
<?php endforeach; endif; ?>
</head>
<body>
