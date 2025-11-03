// Store In-charge Dashboard JavaScript

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

// Barcode Scanner Function
function scanBarcode() {
    const barcodeInput = document.getElementById('barcodeInput');
    const barcodeResult = document.getElementById('barcodeResult');
    const barcode = barcodeInput.value.trim();
    
    if (!barcode) {
        alert('Please enter or scan a barcode');
        return;
    }
    
    // Simulate barcode lookup (replace with actual API call)
    // For demo purposes, showing mock data
    document.getElementById('productName').textContent = 'Sample Product';
    document.getElementById('productId').textContent = barcode;
    document.getElementById('productStock').textContent = '150 units';
    document.getElementById('productStatus').textContent = 'In Stock';
    
    barcodeResult.style.display = 'block';
    
    // In production, you would make an API call here:
    /*
    fetch(`/api/products/barcode/${barcode}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('productName').textContent = data.name;
            document.getElementById('productId').textContent = data.id;
            document.getElementById('productStock').textContent = data.stock + ' units';
            document.getElementById('productStatus').textContent = data.status;
            barcodeResult.style.display = 'block';
        })
        .catch(error => {
            alert('Product not found');
            console.error('Error:', error);
        });
    */
}

// Allow Enter key to trigger scan
document.addEventListener('DOMContentLoaded', function() {
    const barcodeInput = document.getElementById('barcodeInput');
    if (barcodeInput) {
        barcodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                scanBarcode();
            }
        });
    }
    
    console.log('Store In-charge dashboard loaded successfully');
});
