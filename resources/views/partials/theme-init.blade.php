<script>
    (function () {
        var match = document.cookie.match(/(?:^|; )theme=(dark|light)/);
        var theme = match ? match[1] : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
