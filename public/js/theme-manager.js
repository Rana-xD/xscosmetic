(function(window, document) {
    var storageKey = 'xcosmetic-theme';
    var root = document.documentElement;

    function getStoredTheme() {
        try {
            var storedTheme = window.localStorage ? window.localStorage.getItem(storageKey) : null;
            return storedTheme === 'dark' || storedTheme === 'light' ? storedTheme : null;
        } catch (error) {
            return null;
        }
    }

    function getPreferredTheme() {
        return getStoredTheme() || 'light';
    }

    function syncThemeStylesheet(theme) {
        var themeLink = document.getElementById('app-theme-stylesheet');

        if (!themeLink) {
            return;
        }

        var nextHref = theme === 'dark'
            ? themeLink.getAttribute('data-dark-href')
            : themeLink.getAttribute('data-light-href');

        if (nextHref && themeLink.getAttribute('href') !== nextHref) {
            themeLink.setAttribute('href', nextHref);
        }
    }

    function updateToggleState(theme) {
        var toggles = document.querySelectorAll('[data-theme-toggle]');
        var nextTheme = theme === 'dark' ? 'light' : 'dark';
        var labelText = nextTheme === 'dark' ? 'Dark Mode' : 'Light Mode';
        var titleText = nextTheme === 'dark' ? 'Switch to dark mode' : 'Switch to light mode';

        Array.prototype.forEach.call(toggles, function(toggle) {
            var label = toggle.querySelector('[data-theme-toggle-label]');
            var icon = toggle.querySelector('[data-theme-toggle-icon]');

            toggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            toggle.setAttribute('aria-label', titleText);
            toggle.setAttribute('title', titleText);

            if (label) {
                label.textContent = labelText;
            }

            if (icon) {
                icon.classList.remove('fa-moon-o');
                icon.classList.remove('fa-sun-o');
                icon.classList.add(nextTheme === 'dark' ? 'fa-moon-o' : 'fa-sun-o');
            }
        });
    }

    function setRootTheme(theme) {
        root.setAttribute('data-theme', theme);
        root.classList.remove('theme-light');
        root.classList.remove('theme-dark');
        root.classList.add('theme-' + theme);
        window.__APP_THEME__ = theme;
    }

    function persistTheme(theme) {
        try {
            if (window.localStorage) {
                window.localStorage.setItem(storageKey, theme);
            }
        } catch (error) {
            // Ignore storage failures and keep the theme applied for this page.
        }
    }

    function applyTheme(theme, options) {
        var nextTheme = theme === 'dark' ? 'dark' : 'light';

        setRootTheme(nextTheme);
        syncThemeStylesheet(nextTheme);
        updateToggleState(nextTheme);

        if (!options || options.persist !== false) {
            persistTheme(nextTheme);
        }
    }

    function toggleTheme() {
        applyTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    }

    function bindToggles() {
        var toggles = document.querySelectorAll('[data-theme-toggle]');

        Array.prototype.forEach.call(toggles, function(toggle) {
            toggle.addEventListener('click', function(event) {
                event.preventDefault();
                toggleTheme();
            });
        });
    }

    function init() {
        applyTheme(window.__APP_THEME__ || getPreferredTheme(), { persist: false });
        bindToggles();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.AppTheme = {
        getTheme: function() {
            return root.getAttribute('data-theme') || getPreferredTheme();
        },
        setTheme: function(theme) {
            applyTheme(theme);
        },
        toggle: toggleTheme
    };
})(window, document);
