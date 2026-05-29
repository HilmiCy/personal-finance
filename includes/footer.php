
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function toggleTheme(isDark) {
            if (isDark) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
                if (document.getElementById('checkbox')) {
                    document.getElementById('checkbox').checked = true;
                }
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
                if (document.getElementById('checkbox')) {
                    document.getElementById('checkbox').checked = false;
                }
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'dark' && document.getElementById('checkbox')) {
                document.getElementById('checkbox').checked = true;
            }
        });
    </script>

<footer class="main-footer">
    <div class="text-center py-3">
        <p class="mb-0" style="color: var(--muted); font-size: 14px; font-weight: 500;">
            &copy; 2026
            <span style="font-weight: 700; color: var(--fg);"><?= APP_NAME ?></span>.
            Developed by <span style="font-weight: 600; color: var(--fg);">Fadhil Cahya Hilmi</span>
        </p>
    </div>
</footer>
</body>
</html>
