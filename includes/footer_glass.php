</div> </div> </div> <footer class="footer mt-auto py-3 bg-white border-top">
    <div class="container text-center">
        <span class="text-muted">© 2026 Bahir Dar Textile Share Company (BDTSC). All rights reserved.</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');

    // Prevent body scroll when sidebar is open on mobile
    if (window.innerWidth <= 991.98) {
        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : 'auto';
    }
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.querySelector('.mobile-menu-toggle');

    if (window.innerWidth <= 991.98) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target) && overlay.contains(event.target)) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    }
});

// Close sidebar on window resize if desktop
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (window.innerWidth > 991.98) {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
});
</script>

</body>
</html>