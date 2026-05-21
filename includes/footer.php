
    <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Mobile Menu Toggle Script -->
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

        // Sync checkbox on load
        window.addEventListener('DOMContentLoaded', () => {
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'dark') {
                if (document.getElementById('checkbox')) {
                    document.getElementById('checkbox').checked = true;
                }
            }
        });
    </script>

<!-- Footer -->
<footer style="
    margin-left: 70px;
    background: var(--bg-sidebar);
    padding: 24px 0;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: center;
    align-items: center;
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s ease;
" class="main-footer">
    <style>
        @media (max-width: 768px) {
            .main-footer {
                margin-left: 0 !important;
            }
        }
    </style>
    <p style="
        margin: 0;
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 500;
        text-align: center;
    ">
        © 2026 
        <span style="color: var(--accent-primary); font-weight: 700;"><?= APP_NAME ?></span>. 
        Developed by <span style="color: var(--text-main); font-weight: 600;">Fadhil Cahya Hilmi</span>
    </p>
</footer>
</body>
</html>