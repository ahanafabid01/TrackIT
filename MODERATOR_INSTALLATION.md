# 🚀 Moderator Module - Quick Installation Guide

## Step-by-Step Setup

### 1. Database Setup (5 minutes)

Open phpMyAdmin or MySQL command line and run:

```sql
-- Step 1: Create the database tables
SOURCE d:/Xampp/htdocs/trackit/sql/moderator_tables.sql;

-- Step 2: (Optional) Insert sample data for testing
SOURCE d:/Xampp/htdocs/trackit/sql/moderator_sample_data.sql;
```

Or manually import:
1. Open phpMyAdmin
2. Select `trackit` database
3. Click "Import" tab
4. Choose `moderator_tables.sql` file
5. Click "Go"
6. Repeat for `moderator_sample_data.sql` (optional)

### 2. File Setup (2 minutes)

The following files have been created:

**Database:**
- ✅ `sql/moderator_tables.sql` - Database schema
- ✅ `sql/moderator_sample_data.sql` - Test data

**APIs:**
- ✅ `api/moderator/customers.php` - Customer management
- ✅ `api/moderator/bookings.php` - Booking management
- ✅ `api/moderator/products.php` - Product availability
- ✅ `api/moderator/reminders.php` - Reminder system
- ✅ `api/moderator/export.php` - Data export

**Frontend:**
- ✅ `main/pages/moderator_enhanced.php` - Main page
- ✅ `main/pages/js/moderator_enhanced.js` - JavaScript
- ✅ `main/pages/css/moderator_enhanced.css` - Styles

**To use the enhanced version:**

Option A: Replace existing files
```bash
# Backup current files first
copy main\pages\moderator.php main\pages\moderator_backup.php
copy main\pages\js\moderator.js main\pages\js\moderator_backup.js
copy main\pages\css\moderator.css main\pages\css\moderator_backup.css

# Replace with enhanced versions
copy main\pages\moderator_enhanced.php main\pages\moderator.php
copy main\pages\js\moderator_enhanced.js main\pages\js\moderator.js
copy main\pages\css\moderator_enhanced.css main\pages\css\moderator.css
```

Option B: Use enhanced files directly
- Access: `http://localhost/trackit/main/pages/moderator_enhanced.php`

### 3. Verify Installation (3 minutes)

1. **Check Database:**
```sql
USE trackit;
SHOW TABLES;
-- Should see: customers, products, bookings, booking_reminders, customer_feedback, booking_history
```

2. **Check Sample Data:**
```sql
SELECT COUNT(*) FROM customers;  -- Should return 5
SELECT COUNT(*) FROM products;   -- Should return 6
SELECT COUNT(*) FROM bookings;   -- Should return 7
```

3. **Test Login:**
- Go to: `http://localhost/trackit/auth/login.php`
- Login with Moderator account:
  - Email: `ext.ahanaf.abid@gmail.com`
  - Password: (your password)
- Should redirect to moderator dashboard

### 4. First Time Usage (5 minutes)

1. **Dashboard Overview:**
   - View statistics
   - Check alerts (low stock, pending reminders)
   - Use quick action cards

2. **Create First Booking:**
   - Click "New Booking"
   - Select customer from dropdown
   - Select product (see real-time availability)
   - Enter quantity and verify total
   - Save booking

3. **Add New Customer:**
   - Go to "Customers" tab
   - Click "Add Customer"
   - Fill in details
   - Save customer

4. **Check Product Availability:**
   - Go to "Products" tab
   - View stock levels
   - Filter by availability

5. **Manage Reminders:**
   - Go to "Reports" tab
   - View pending reminders
   - Mark reminders as sent

## 🎯 Quick Test Checklist

- [ ] Database tables created successfully
- [ ] Sample data inserted
- [ ] Can login as Moderator
- [ ] Dashboard loads with statistics
- [ ] Can create new booking
- [ ] Can view customer history
- [ ] Can check product availability
- [ ] Can export data to CSV
- [ ] Reminders display correctly
- [ ] Filters and search work
- [ ] Pagination works
- [ ] Modals open and close
- [ ] Toast notifications appear

## 🔧 Common Issues & Solutions

### "Table doesn't exist" error
**Fix:** Run `moderator_tables.sql` again

### "Cannot connect to database"
**Fix:** Check `config/config.php` for correct credentials

### "Permission denied" on API
**Fix:** Ensure logged in as Moderator role

### Blank page or no data
**Fix:** 
1. Check browser console for errors (F12)
2. Verify `owner_id` matches your user
3. Run sample data SQL if testing

### Export doesn't download
**Fix:** Check PHP error log, ensure headers not sent

## 📋 Production Checklist

Before going live:

- [ ] Remove sample data: `DELETE FROM customers WHERE id <= 5;`
- [ ] Remove sample data: `DELETE FROM products WHERE id <= 6;`
- [ ] Remove sample data: `DELETE FROM bookings WHERE id <= 7;`
- [ ] Update `config.php` with production database
- [ ] Set appropriate file permissions
- [ ] Enable HTTPS
- [ ] Configure backup schedule
- [ ] Test all features with real data
- [ ] Train moderator users
- [ ] Document internal processes

## 🎨 Customization Tips

### Change Colors
Edit `main/pages/css/moderator_enhanced.css`:
```css
:root {
    --primary-color: #3b82f6; /* Change this */
}
```

### Adjust Pagination
Edit `main/pages/js/moderator_enhanced.js`:
```javascript
const url = `${API_BASE}/bookings.php?page=${page}&limit=20`; // Change limit
```

### Modify Booking Statuses
Edit `api/moderator/bookings.php` and update the ENUM values in `moderator_tables.sql`

## 🎉 You're All Set!

The Moderator module is now fully functional with:
✅ Customer management
✅ Booking system with availability checks
✅ Automated reminders
✅ Data export
✅ Real-time stock monitoring
✅ Customer history tracking

### Next Steps:
1. Create real products in the system
2. Start adding actual customers
3. Create bookings and test workflow
4. Monitor reminders and follow up
5. Export reports regularly

## 📚 Documentation
- Full documentation: `MODERATOR_README.md`
- API details: See README "API Endpoints" section
- Troubleshooting: See README "Troubleshooting" section

---

**Need Help?** 
- Check browser console (F12)
- Review PHP error logs
- Consult MODERATOR_README.md

**Happy Managing! 🚀**
