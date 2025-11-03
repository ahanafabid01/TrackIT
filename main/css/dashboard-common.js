// Common Dashboard JavaScript Functions
// Used across all dashboards (Owner, Moderator, Accountant, Admin In-charge, Store In-charge)

// Sidebar Toggle
function initSidebarToggle() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initSidebarToggle();
});
