/**
 * SIMRAN Theme Switcher (Light / Dark Mode)
 */
(function () {
    const THEME_KEY = 'simran-theme-pref';

    function getPreferredTheme() {
        const saved = localStorage.getItem(THEME_KEY);
        if (saved) return saved;
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        const icon = document.getElementById('theme-icon');
        if (icon) {
            icon.textContent = theme === 'dark' ? '☀️' : '🌙';
        }
        const text = document.getElementById('theme-text');
        if (text) {
            text.textContent = theme === 'dark' ? 'Light' : 'Dark';
        }
    }

    const currentTheme = getPreferredTheme();
    applyTheme(currentTheme);

    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('theme-toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                const current = document.documentElement.getAttribute('data-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem(THEME_KEY, next);
                applyTheme(next);

                // Re-render chart colors if chart exists
                if (window.simranChart) {
                    window.updateChartTheme && window.updateChartTheme();
                }
            });
        }
    });
})();
