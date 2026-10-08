(function () {
    const THEME_STORAGE_KEY = 'interviewBuddyTheme';
    const DEFAULT_THEME = 'blue';
    const SUPPORTED_THEMES = ['blue', 'light', 'dark'];

    function applyTheme(themeName) {
        const safeTheme = SUPPORTED_THEMES.includes(themeName) ? themeName : DEFAULT_THEME;
        document.documentElement.setAttribute('data-theme', safeTheme);

        document.querySelectorAll('[data-theme-option]').forEach(function (button) {
            const isActive = button.getAttribute('data-theme-option') === safeTheme;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        try {
            localStorage.setItem(THEME_STORAGE_KEY, safeTheme);
        } catch (error) {
            // Ignore storage errors so the app still works when localStorage is unavailable.
        }
    }

    function getSavedTheme() {
        try {
            const saved = localStorage.getItem(THEME_STORAGE_KEY);
            return saved && SUPPORTED_THEMES.includes(saved) ? saved : DEFAULT_THEME;
        } catch (error) {
            return DEFAULT_THEME;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const savedTheme = getSavedTheme();
        applyTheme(savedTheme);

        document.querySelectorAll('[data-theme-option]').forEach(function (button) {
            button.addEventListener('click', function () {
                const theme = this.getAttribute('data-theme-option');
                applyTheme(theme);
            });
        });
    });

    document.querySelectorAll('.score-ring').forEach(function (el) {
        el.style.setProperty('--pct', el.dataset.pct || 0);
    });

    document.querySelectorAll('.ib-alert').forEach(function (el) {
        setTimeout(function () { el.style.display = 'none'; }, 4000);
    });

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });
})();
