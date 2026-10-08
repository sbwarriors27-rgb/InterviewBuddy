    <footer class="ib-footer">
        <p>&copy; <?= date('Y') ?> Interview Buddy — Centralized Placement Preparation Platform</p>
    </footer>
    <script src="<?= e(siteUrl('assets/js/script.js')) ?>"></script>
    <?php if (!empty($extraJs)): foreach ($extraJs as $js): ?>
    <script src="<?= e(siteUrl('assets/js/' . $js)) ?>"></script>
    <?php endforeach; endif; ?>
</body>
</html>
