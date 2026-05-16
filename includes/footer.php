
    <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Mobile Menu Toggle Script -->
    <script>
        // Tambahkan toggle untuk mobile jika diperlukan
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar.style.transform === 'translateX(0px)') {
                sidebar.style.transform = 'translateX(-100%)';
            } else {
                sidebar.style.transform = 'translateX(0px)';
            }
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggle = event.target.classList.contains('menu-toggle') || 
                                   event.target.closest('.menu-toggle');
            
            if (window.innerWidth <= 768 && !isClickInsideSidebar && !isClickOnToggle) {
                sidebar.style.transform = 'translateX(-100%)';
            }
        });
    </script>

<!-- Footer -->
<footer style="
    width: 100%;
    background: #ffffff;
    padding: 18px 0;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: center;
    align-items: center;
">
    <p style="
        margin: 0;
        color: #6b7280;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        letter-spacing: 0.3px;
        text-align: center;
    ">
        © 2026 
        
        <span style="
            color: #111827;
            font-weight: 600;
        ">
            Fadhil Cahya Hilmi
        </span>. 
        
        All rights reserved.
    </p>
</footer>
</body>
</html>