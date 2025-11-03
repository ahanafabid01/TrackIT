# 🚀 Store In-charge Setup & Troubleshooting Guide

## ⚠️ CRITICAL: Check This First!

### Step 1: Verify You're Logged In as Store In-charge

1. **Test Your Session:**
   - Open: `http://localhost/trackit/api/store_incharge/test_connection.php`
   - You should see:
     ```json
     {
       "success": true,
       "message": "Connection successful!",
       "session": {
         "user_role": "Store In-charge" or "Owner"
       }
     }
     ```

2. **If you see an error:**
   - `"User not logged in"` → Login first at `/auth/login.php`
   - `"User does not have Store In-charge or Owner role"` → Create a Store In-charge user

---

## 🔧 Step 2: Create Store In-charge User (If Needed)

### Option A: Via Owner Dashboard
1. Login as Owner
2. Go to User Management
3. Add New User with role = "Store In-charge"

### Option B: Direct SQL Insert
```sql
-- Check existing users
SELECT id, name, email, role FROM users;

-- Create Store In-charge user
INSERT INTO users (name, email, password, role, owner_id, status)
VALUES (
  'Store Manager',
  'store@example.com',
  '$2y$10$JooP0F7doRrn5kXtJYQf9OPPkJmcNePu9ZYxG4YKRY4Kgm8tNsLta', -- Password: password
  'Store In-charge',
  1, -- Replace with your owner_id
  'Active'
);
```

---

## 📊 Step 3: Verify Database Data

### Check Pending Bookings
```sql
SELECT * FROM bookings WHERE status = 'Pending';
```

**Expected Result:** Should show pending bookings (you have 6 in your database)

### Check If Tables Exist
```sql
SHOW TABLES LIKE '%delivery%';
SHOW TABLES LIKE '%barcode%';
SHOW TABLES LIKE '%return%';
SHOW TABLES LIKE '%alert%';
```

**Expected Result:**
- ✅ deliveries
- ✅ delivery_tracking_history
- ✅ product_barcodes
- ✅ returns
- ✅ low_stock_alerts

---

## 🐛 Step 4: Debug Loading Issues

### Open Browser Console (F12)
1. Go to: `http://localhost/trackit/main/pages/store_in-charge.php`
2. Press `F12` → Go to "Console" tab
3. Look for these logs:

**✅ Success Logs:**
```
Store In-charge dashboard loaded successfully
Loading dashboard stats...
Bookings response: {success: true, bookings: Array(6)}
Dashboard stats updated: {pendingRequests: 6, ...}
```

**❌ Error Logs (Common Issues):**

#### Error 1: "Failed to fetch"
**Cause:** API path wrong or server not running  
**Fix:** 
- Make sure XAMPP Apache is running
- Check URL: `http://localhost/trackit/api/store_incharge/booking_requests.php`

#### Error 2: "Unauthorized" or "User not found"
**Cause:** Not logged in or wrong role  
**Fix:**
1. Login at: `http://localhost/trackit/auth/login.php`
2. Use Store In-charge credentials

#### Error 3: "Product not found" (for barcode)
**Cause:** No barcodes in database yet  
**Fix:** Create a barcode first (see Step 5)

---

## 📦 Step 5: Create Test Barcode

```sql
-- Insert test barcode for first product
INSERT INTO product_barcodes 
(product_id, owner_id, barcode, batch_number, quantity_per_batch, warehouse_location, status, created_by)
VALUES 
(1, 1, 'PROD-SKU001-1730755200-1234', 'BATCH-001', 50, 'A-12-03', 'Active', 1);

-- Verify
SELECT pb.*, p.name as product_name 
FROM product_barcodes pb 
LEFT JOIN products p ON pb.product_id = p.id;
```

**Test Barcode:** `PROD-SKU001-1730755200-1234`

---

## ✅ Step 6: Test Each Feature

### 1. Dashboard Statistics
- **URL:** `http://localhost/trackit/main/pages/store_in-charge.php`
- **Expected:**
  - Pending Requests: 6
  - Deliveries Today: 0
  - In Transit: 0
  - Returns: 0

### 2. Booking Requests Tab
- Click "Booking Requests" in sidebar
- **Expected:** Table with 6 pending bookings
- **Test Confirm:** Click "Confirm" button → Stock should be validated
- **Test Reject:** Click "Reject" button → Stock should be restored

### 3. Barcode Scanner
- Click "Barcode Scanner" in sidebar
- Enter: `PROD-SKU001-1730755200-1234`
- Press Enter or click "Search"
- **Expected:** Product details displayed

---

## 🔍 API Endpoints Status Check

Run these in browser to verify each API works:

| Endpoint | URL | Expected Result |
|----------|-----|-----------------|
| **Test Connection** | `/api/store_incharge/test_connection.php` | `{"success": true}` |
| **Booking Requests** | `/api/store_incharge/booking_requests.php?status=Pending` | Array of 6 bookings |
| **Deliveries** | `/api/store_incharge/deliveries.php` | Empty array (no deliveries yet) |
| **Barcodes Scan** | `/api/store_incharge/barcodes.php?scan=PROD-SKU001-1730755200-1234` | Product details |
| **Returns** | `/api/store_incharge/returns.php` | Empty array (no returns yet) |
| **Alerts** | `/api/store_incharge/alerts.php` | Empty array (no alerts yet) |

---

## 🔐 Login Credentials

**If you need to login:**
- **Email:** Any existing user email from database
- **Password:** `password` (for test accounts with hash `$2y$10$JooP0F7doRrn5kXtJYQf9OPPkJmcNePu9ZYxG4YKRY4Kgm8tNsLta`)

---

## 📋 Complete Setup Checklist

- [ ] XAMPP Apache & MySQL running
- [ ] Logged in as Store In-charge or Owner
- [ ] Test connection API returns success
- [ ] Database has 6 pending bookings
- [ ] Browser console shows no errors
- [ ] Dashboard loads with correct statistics
- [ ] Booking Requests tab shows 6 bookings
- [ ] Barcode created and can be scanned

---

## 🆘 Still Not Working?

### Get Debug Info:
1. Open: `http://localhost/trackit/api/store_incharge/test_connection.php`
2. Copy the JSON response
3. Open browser console (F12)
4. Copy all red error messages

**Then provide:**
- The test_connection.php response
- Browser console errors
- Which specific feature is not working

---

## 📞 Quick Fixes

### "Loading booking requests..." Never Ends
**Check:**
1. Browser Console → Look for exact error
2. Test API directly: `/api/store_incharge/booking_requests.php?status=Pending`
3. Verify you're logged in as Store In-charge

### Dashboard Shows All Zeros
**Cause:** No data in database yet  
**Fix:** Your database HAS data! This means API isn't being called. Check browser console for errors.

### Barcode Scanner Says "Product not found"
**Cause:** No barcodes in database  
**Fix:** Run the INSERT query from Step 5 above

---

**Last Updated:** November 4, 2025  
**Status:** All APIs created and column names fixed ✅
