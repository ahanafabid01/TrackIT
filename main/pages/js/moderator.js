// Moderator Dashboard JavaScript

// Sidebar Toggle
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');

if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
}

// Navigation
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
    if (window.innerWidth <= 768) {
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

// Booking Modal
function openBookingModal() {
    alert('Booking form modal would open here. Full implementation coming soon!');
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Moderator dashboard loaded successfully');
});
