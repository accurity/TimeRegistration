export function initThemeToggle() {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            document.cookie = `theme=${isDark ? 'dark' : 'light'}; path=/; max-age=31536000; samesite=lax`;
        });
    });
}
