# Store In-charge Module - Database Setup & Integration

## ✅ Changes Made

### 1. **Updated JavaScript (store_in-charge.js)**
- Replaced all hardcoded data with real API calls
- Added `loadDashboardStats()` - Fetches real statistics from database
- Added `loadBookingRequests()` - Loads pending bookings
- Added `renderBookingRequests()` - Dynamically renders booking table
- Added `confirmBooking()` and `rejectBooking()` - Interactive booking management
- Updated `scanBarcode()` - Now calls real barcode API
- Added notification system for user feedback
- Added utility functions for formatting dates and currency

### 2. **Updated HTML (store_in-charge.php)**
- Updated booking requests table with proper columns (added Priority and Date)
- Changed hardcoded table rows to loading spinner
- Data will be populated dynamically via JavaScript

### 3. **Updated CSS (store_in-charge.css)**
- Added badge styles for priority and status (urgent, high, normal, low, pending, confirmed, etc.)
- Added button styles (btn-primary, btn-success, btn-danger, btn-sm)
- Added notification animations (slideIn, slideOut)

### 4. **Fixed SQL Schema (store_incharge_tables.sql)**
Fixed column name mismatches between SQL and API:

**product_barcodes table:**
- `quantity` → `quantity_per_batch`
- `location` → `warehouse_location`
- Added `auto_expired` column

**returns table:**
- Removed unused columns (`delivery_id`, `return_description`, `requested_date`, `approved_date`, `received_date`, `refund_amount`)
- Updated status ENUM to match API: `Pending`, `Approved`, `Rejected`, `Inspected`, `Restocked`, `Completed`
- Updated reason ENUM: `Changed Mind` instead of `Customer Changed Mind`
- Updated condition ENUM: Added `Acceptable` option
- Changed `requested_by` → `created_by`
- Added `customer_comments` column
- Added `restocked_by` and `restocked_at` columns

**low_stock_alerts table:**
- `current_quantity` → `current_stock_level`
- `threshold_quantity` → `threshold_level`
- Added `created_by`, `resolved_by`, and `resolution_notes` columns

### 5. **Fixed API (barcodes.php)**
- Changed `p.quantity` → `p.stock_quantity` to match products table schema

---

## 🚀 Setup Instructions

### Step 1: Drop Existing Tables (if any)
```sql
DROP TABLE IF EXISTS `low_stock_alerts`;
DROP TABLE IF EXISTS `returns`;
DROP TABLE IF EXISTS `product_barcodes`;
DROP TABLE IF EXISTS `delivery_tracking_history`;
DROP TABLE IF EXISTS `deliveries`;
```

### Step 2: Run Updated SQL Schema
```sql
SOURCE d:\Xampp\htdocs\trackit\sql\store_incharge_tables.sql;
```

This will create 5 tables with correct column names:
- ✅ `deliveries`
- ✅ `delivery_tracking_history`
- ✅ `product_barcodes`
- ✅ `returns`
- ✅ `low_stock_alerts`

### Step 3: Test the Dashboard

1. **Login as Store In-charge:**
   - Go to: `http://localhost/trackit/auth/login.php`
   - Use a Store In-charge account

2. **Navigate to Store In-charge Dashboard:**
   - URL: `http://localhost/trackit/main/pages/store_in-charge.php`

3. **Verify Dashboard Statistics:**
   - Should show real counts for:
     - Pending Requests (from bookings table)
     - Deliveries Today (from deliveries table)
     - In Transit (from deliveries table)
     - Returns (from returns table)

4. **Test Booking Requests Tab:**
   - Click "Booking Requests" in sidebar
   - Should load real pending bookings
   - Try "Confirm" button (checks stock availability)
   - Try "Reject" button (restores stock)

5. **Test Barcode Scanner:**
   - Click "Barcode Scanner" in sidebar
   - First, create a barcode via API or database:
     ```sql
     INSERT INTO product_barcodes (product_id, owner_id, barcode, batch_number, quantity_per_batch, status, created_by)
     VALUES (1, 1, 'PROD-TEST-1234567890-1234', 'BATCH-001', 100, 'Active', 1);
     ```
   - Enter barcode: `PROD-TEST-1234567890-1234`
   - Should display product information

---

## 📊 API Endpoints Being Used

### Dashboard
- `GET ../../api/store_incharge/booking_requests.php?status=Pending`
- `GET ../../api/store_incharge/deliveries.php?limit=100`
- `GET ../../api/store_incharge/returns.php?status=Requested`

### Booking Requests Tab
- `GET ../../api/store_incharge/booking_requests.php?status=Pending`
- `PUT ../../api/store_incharge/booking_requests.php` (confirm/reject)

### Barcode Scanner
- `GET ../../api/store_incharge/barcodes.php?scan={barcode}`

---

## 🧪 Testing Checklist

### Dashboard Statistics
- [ ] Pending Requests count matches database
- [ ] Deliveries Today shows correct count
- [ ] In Transit shows active deliveries
- [ ] Returns count is accurate

### Booking Requests
- [ ] Table loads with real data
- [ ] Priority badges show correct colors
- [ ] Status badges display properly
- [ ] Confirm button works (validates stock)
- [ ] Reject button works (prompts for reason)
- [ ] Success/error notifications appear
- [ ] Table refreshes after action

### Barcode Scanner
- [ ] Input field accepts barcode
- [ ] Enter key triggers scan
- [ ] Valid barcode shows product info
- [ ] Invalid barcode shows error
- [ ] Product details are accurate
- [ ] Expiry date shows if available

---

## 🐛 Common Issues & Solutions

### Issue 1: "Failed to load dashboard statistics"
**Cause:** API endpoints not accessible or database tables missing  
**Solution:** 
1. Check if SQL tables are created
2. Verify API files exist in `api/store_incharge/` folder
3. Check browser console for specific error

### Issue 2: "Booking not found"
**Cause:** No bookings in database or bookings table missing  
**Solution:**
```sql
-- Check if bookings table has data
SELECT * FROM bookings WHERE status = 'Pending';

-- If empty, insert test booking
INSERT INTO bookings (booking_number, owner_id, customer_id, product_id, quantity, unit_price, total_amount, status, booking_date, created_by)
VALUES ('BK-TEST-001', 1, 1, 1, 5, 100.00, 500.00, 'Pending', CURDATE(), 1);
```

### Issue 3: "Product not found" when scanning barcode
**Cause:** No barcodes exist in database  
**Solution:**
```sql
-- Create test barcode
INSERT INTO product_barcodes (product_id, owner_id, barcode, batch_number, quantity_per_batch, status, created_by)
SELECT id, owner_id, CONCAT('PROD-', sku, '-', UNIX_TIMESTAMP(), '-', FLOOR(RAND() * 10000)), 'BATCH-001', 50, 'Active', owner_id
FROM products LIMIT 1;

-- Verify
SELECT * FROM product_barcodes;
```

### Issue 4: "Insufficient stock" when confirming booking
**Cause:** Product stock_quantity is less than booking quantity  
**Solution:**
```sql
-- Update product stock
UPDATE products SET stock_quantity = 100 WHERE id = 1;
```

---

## 📝 Next Steps

### Immediate (Required for basic functionality)
1. ✅ Run SQL schema to create tables
2. ✅ Test dashboard statistics load
3. ✅ Test booking confirmation workflow
4. ✅ Create sample barcodes for testing

### Short-term (Enhance functionality)
1. Add Deliveries tab UI and integration
2. Add Returns tab UI and integration
3. Add Low Stock Alerts tab UI
4. Implement barcode generation button
5. Add export functionality

### Long-term (Advanced features)
1. Integrate courier APIs (Delhivery, DTDC)
2. Add image upload for damage reports
3. Implement barcode label printing (PDF)
4. Add real-time notifications (WebSocket/SSE)
5. Create mobile-responsive scanner interface
6. Add bulk barcode generation
7. Implement automated stock alerts

---

## 🔧 Maintenance Tasks

### Daily
- Check low stock alerts
- Review pending booking requests
- Monitor delivery status

### Weekly
- Generate delivery reports
- Review return statistics
- Clean up expired barcodes

### Monthly
- Archive old deliveries
- Export historical data
- Update stock thresholds

---

## 📞 Support

If you encounter any issues:
1. Check browser console for JavaScript errors
2. Check PHP error logs in XAMPP
3. Verify database connection in `config/config.php`
4. Ensure all API files have correct permissions
5. Check that user has 'Store In-charge' role

---

**Status:** ✅ Ready for database setup and testing
**Last Updated:** November 4, 2025
