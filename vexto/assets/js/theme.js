/**
 * Theme Manager
 * Handles light/dark theme switching and persistence
 */

class ThemeManager {
    constructor() {
        this.STORAGE_KEY = 'vexto_theme';
        this.LIGHT_THEME = 'light';
        this.DARK_THEME = 'dark';
        this.init();
    }

    /**
     * Initialize theme manager
     */
    init() {
        const savedTheme = this.getSavedTheme();
        const preferredTheme = this.getPreferredTheme();
        const theme = savedTheme || preferredTheme;
        
        this.setTheme(theme);
        this.setupThemeToggle();
    }

    /**
     * Get saved theme from localStorage
     */
    getSavedTheme() {
        return localStorage.getItem(this.STORAGE_KEY);
    }

    /**
     * Get preferred theme from system
     */
    getPreferredTheme() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return this.DARK_THEME;
        }
        return this.LIGHT_THEME;
    }

    /**
     * Set theme
     */
    setTheme(theme) {
        if (theme === this.DARK_THEME) {
            document.documentElement.setAttribute('data-theme', this.DARK_THEME);
            localStorage.setItem(this.STORAGE_KEY, this.DARK_THEME);
            this.updateThemeToggle(true);
        } else {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem(this.STORAGE_KEY, this.LIGHT_THEME);
            this.updateThemeToggle(false);
        }
    }

    /**
     * Toggle theme
     */
    toggleTheme() {
        const currentTheme = this.getCurrentTheme();
        const newTheme = currentTheme === this.LIGHT_THEME ? this.DARK_THEME : this.LIGHT_THEME;
        this.setTheme(newTheme);
    }

    /**
     * Get current theme
     */
    getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') || this.LIGHT_THEME;
    }

    /**
     * Setup theme toggle button
     */
    setupThemeToggle() {
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => this.toggleTheme());
        }
    }

    /**
     * Update theme toggle button state
     */
    updateThemeToggle(isDark) {
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-pressed', isDark);
            toggleBtn.innerHTML = isDark ? '☀️' : '🌙';
        }
    }
}

// Initialize theme manager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new ThemeManager();
    });
} else {
    new ThemeManager();
}
