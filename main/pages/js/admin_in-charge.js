// Admin In-charge Dashboard JavaScript

const navItems = document.querySelectorAll('.nav-item[data-page]');

function showPage(pageName) {
    // Hide all pages
    document.querySelectorAll('.page-content').forEach(page => {
        page.style.display = 'none';
    });
    
    // Show selected page
    const selectedPage = document.getElementById(pageName + 'Page');
    if (selectedPage) {
        selectedPage.style.display = 'block';
    }
    
    // Update active nav
    navItems.forEach(item => {
        if (item.getAttribute('data-page') === pageName) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
    
    // Close sidebar on mobile
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth <= 768 && sidebar) {
        sidebar.classList.remove('active');
    }
}

navItems.forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        const page = item.getAttribute('data-page');
        showPage(page);
    });
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin In-charge dashboard loaded successfully');
});
