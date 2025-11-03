# Moderator Module - Complete Implementation Guide

## 📋 Overview
The Moderator module is a comprehensive system for managing customer bookings, inquiries, and lead management with advanced features.

## ✨ Features Implemented

### 1. **Dashboard**
- Real-time statistics (Total Bookings, Confirmed, Pending, Customers)
- Alert notifications for low stock and pending reminders
- Quick action cards for common tasks

### 2. **Booking Management**
- ✅ Create, view, edit, and update bookings
- ✅ Real-time product availability check
- ✅ Automatic booking number generation (BK-001, BK-002, etc.)
- ✅ Status tracking (Pending → Confirmed → Processing → Ready → Delivered)
- ✅ Priority levels (Low, Normal, High, Urgent)
- ✅ Booking history timeline
- ✅ Filter by status and search
- ✅ Export bookings to CSV

### 3. **Customer Management**
- ✅ Add, edit, view, and delete customers
- ✅ Customer history with past bookings
- ✅ Total spent and order statistics
- ✅ Customer feedback tracking
- ✅ Search and filter customers
- ✅ Export customer data and history to CSV

### 4. **Product Availability**
- ✅ Real-time stock checking
- ✅ Low stock alerts (visual indicators)
- ✅ Stock status: In Stock / Low Stock / Out of Stock
- ✅ Automatic availability checks during booking creation
- ✅ Reserved quantity calculation

### 5. **Booking Reminder System**
- ✅ Automatic reminder creation for pending bookings
- ✅ Reminder types: Confirmation, Follow-up, Delivery, Payment
- ✅ Urgency indicators (Overdue, Due Soon, Upcoming)
- ✅ Mark reminders as sent
- ✅ Pending reminders dashboard

### 6. **Data Export**
- ✅ Export bookings to CSV (with filters)
- ✅ Export customers to CSV
- ✅ Export customer history to CSV
- ✅ Date range filtering for reports

## 🗄️ Database Setup

### Step 1: Create Tables
Run the following SQL files in order:

```bash
# 1. Create main tables (if not already created)
mysql -u root trackit < sql/users.sql

# 2. Create moderator tables
mysql -u root trackit < sql/moderator_tables.sql

# 3. Insert sample data (optional, for testing)
mysql -u root trackit < sql/moderator_sample_data.sql
```

### Step 2: Verify Tables
The following tables should be created:
- `customers` - Customer information
- `products` - Product catalog with stock
- `bookings` - Booking/order records
- `booking_reminders` - Automated reminders
- `customer_feedback` - Customer feedback and ratings
- `booking_history` - Audit trail for booking changes

## 📁 File Structure

```
trackit/
├── sql/
│   ├── moderator_tables.sql          # Database schema
│   └── moderator_sample_data.sql     # Sample data for testing
├── api/
│   └── moderator/
│       ├── customers.php             # Customer CRUD API
│       ├── bookings.php              # Booking management API
│       ├── products.php              # Product availability API
│       ├── reminders.php             # Reminder management API
│       └── export.php                # Data export API
└── main/
    └── pages/
        ├── moderator_enhanced.php    # Main moderator page
        ├── css/
        │   └── moderator_enhanced.css # Enhanced styling
        └── js/
            └── moderator_enhanced.js  # Complete functionality
```

## 🚀 Usage Guide

### Accessing the Moderator Dashboard
1. Log in as a Moderator user
2. Navigate to: `http://localhost/trackit/main/pages/moderator_enhanced.php`

### Creating a New Booking
1. Click "New Booking" button or Quick Action card
2. Select customer (or create new customer inline)
3. Select product - availability is checked automatically
4. Enter quantity, adjust price if needed
5. Set status, priority, and dates
6. Add notes (customer notes and internal notes)
7. Click "Create Booking"

### Managing Customers
1. Go to "Customers" tab
2. Click "Add Customer" to create new
3. Click "View History" icon to see customer's booking history
4. Click "Edit" to modify customer details
5. Use search and filters to find specific customers

### Checking Product Availability
1. Go to "Products" tab
2. View stock status with color indicators:
   - 🟢 Green: In Stock
   - 🟠 Orange: Low Stock
   - 🔴 Red: Out of Stock
3. Search products by name, SKU, or category

### Managing Reminders
1. Go to "Reports" tab
2. View pending reminders sorted by urgency
3. Click "Mark Sent" when you follow up with customers
4. Overdue reminders are highlighted in red

### Exporting Data
1. On any list page (Bookings/Customers), click "Export" button
2. CSV file will download with current filters applied
3. For customer history: View customer → Click "Export History"

## 🎨 User Interface Features

### Modal System
- Large modals for forms with multiple fields
- Smooth animations
- Click outside to close
- Validation before submission

### Real-time Feedback
- Toast notifications for all actions
- Loading spinners while fetching data
- Error messages with helpful context

### Responsive Design
- Mobile-friendly interface
- Collapsible sidebar on mobile
- Touch-friendly buttons and controls

### Pagination
- 10 items per page (configurable)
- Page numbers with ellipsis for long lists
- "Previous" and "Next" buttons
- Item count display

## 🔧 Configuration

### API Configuration
APIs are located in `api/moderator/` and use:
- RESTful design (GET, POST, PUT, DELETE)
- JSON request/response format
- Session-based authentication
- Owner-based data isolation

### Customization Options

**In `moderator_enhanced.js`:**
```javascript
// Change items per page
const response = await fetch(`${API_BASE}/bookings.php?page=${page}&limit=20`); // Default: 10

// Modify search debounce time
searchBookings.timeout = setTimeout(() => loadBookings(1), 300); // Default: 500ms
```

**In `moderator_enhanced.css`:**
```css
/* Change primary color */
:root {
    --primary-color: #3b82f6; /* Blue */
}

/* Adjust modal sizes */
.modal-large {
    max-width: 1200px; /* Default: 900px */
}
```

## 🔐 Security Features

1. **Role-based Access Control**
   - Only Moderators and Owners can access
   - User ID validation in all APIs

2. **Owner Data Isolation**
   - All queries filtered by `owner_id`
   - No cross-owner data access

3. **Input Validation**
   - Required field validation
   - Data type checking
   - SQL injection prevention (prepared statements)

4. **Business Logic Validation**
   - Stock availability checks before booking
   - Prevent deletion of customers with active bookings
   - Status change validation

## 📊 API Endpoints

### Customers API (`/api/moderator/customers.php`)
```
GET    ?page=1&limit=10&status=Active&search=john  # List customers
GET    ?id=5                                       # Get customer details
GET    ?history=1&customer_id=5                   # Get customer history
POST   {name, phone, email, ...}                  # Create customer
PUT    {id, name, phone, ...}                     # Update customer
DELETE {id}                                        # Delete customer
```

### Bookings API (`/api/moderator/bookings.php`)
```
GET    ?page=1&limit=10&status=Pending&search=BK  # List bookings
GET    ?id=5                                       # Get booking details
GET    ?stats=1                                    # Get booking statistics
POST   {customer_id, product_id, quantity, ...}   # Create booking
PUT    {id, status, ...}                          # Update booking
DELETE {id}                                        # Delete booking
```

### Products API (`/api/moderator/products.php`)
```
GET    ?page=1&limit=10&status=Active             # List products
GET    ?id=5                                       # Get product details
GET    ?availability=1&id=5                       # Check product availability
GET    ?search=laptop                             # Search products
```

### Reminders API (`/api/moderator/reminders.php`)
```
GET    ?pending=1                                  # Get pending reminders
GET    ?booking_id=5                              # Get booking reminders
POST   {booking_id, reminder_type, ...}           # Create reminder
PUT    {id, status: 'Sent'}                       # Mark reminder as sent
DELETE {id}                                        # Delete reminder
```

### Export API (`/api/moderator/export.php`)
```
GET    ?type=bookings&format=csv&status=Pending   # Export bookings
GET    ?type=customers&format=csv&status=Active   # Export customers
GET    ?type=customer_history&customer_id=5       # Export customer history
```

## 🐛 Troubleshooting

### Issue: "Loading..." never completes
**Solution:** Check browser console for errors. Verify:
- Database connection in `config/config.php`
- API files are accessible
- User has correct role permissions

### Issue: "Failed to load bookings"
**Solution:** 
- Check if tables exist: `SHOW TABLES LIKE '%booking%'`
- Verify owner_id matches logged-in user
- Check Apache error logs

### Issue: Export not working
**Solution:**
- Ensure `export.php` has correct permissions
- Check if PHP can write to temp directory
- Verify no output before headers in PHP files

### Issue: Modal doesn't open
**Solution:**
- Check JavaScript console for errors
- Ensure `#modalContainer` element exists
- Verify JavaScript file is loaded

## 📈 Future Enhancements

### Planned Features
- [ ] PDF export support
- [ ] Excel export with formatting
- [ ] Email integration for reminders
- [ ] SMS notifications
- [ ] Advanced analytics and charts
- [ ] Bulk operations (bulk status update, bulk delete)
- [ ] Custom fields for bookings
- [ ] Attachment support (upload invoices, documents)
- [ ] WhatsApp integration
- [ ] Calendar view for bookings

### Performance Optimizations
- [ ] Implement caching for frequently accessed data
- [ ] Add database indexes for common queries
- [ ] Lazy loading for large lists
- [ ] WebSocket for real-time updates

## 💡 Tips & Best Practices

1. **Regular Backups**
   - Export customer and booking data weekly
   - Keep database backups

2. **Data Hygiene**
   - Regularly review and update customer information
   - Archive old completed bookings
   - Clear sent reminders periodically

3. **Training**
   - Train moderators on status workflow
   - Document internal processes
   - Use internal notes for handoffs

4. **Monitoring**
   - Check pending reminders daily
   - Monitor low stock alerts
   - Review booking statistics weekly

## 📞 Support

For issues or questions:
1. Check this README
2. Review browser console for JavaScript errors
3. Check Apache/PHP error logs
4. Verify database connectivity

## 🎉 Credits

Built with:
- PHP 8.2
- MySQL/MariaDB
- Vanilla JavaScript (no frameworks!)
- Font Awesome icons
- Custom CSS (no Bootstrap!)

---

**Version:** 1.0.0  
**Last Updated:** November 3, 2025  
**Author:** TrackIt Development Team
