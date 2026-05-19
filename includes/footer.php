
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
        
        // Close sidebar when clicking overlay on mobile
        // already handled by onclick="toggleSidebar()" on overlay
    </script>

<!-- Footer -->
<footer style="
    margin-left: 70px;
    background: #ffffff;
    padding: 24px 0;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        color: #6b7280;
        font-size: 14px;
        font-weight: 500;
        text-align: center;
    ">
        © 2026 
        <span style="color: #6366f1; font-weight: 700;"><?= APP_NAME ?></span>. 
        Developed by <span style="color: #1f2937; font-weight: 600;">Fadhil Cahya Hilmi</span>
    </p>
</footer>
</body>
</html>