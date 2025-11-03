# Store In-charge Module - API Documentation

## Overview
Complete API implementation for Store In-charge functionality in TrackIt system.

## Database Setup
Before using the APIs, run the SQL schema:
```bash
# In MySQL/phpMyAdmin
SOURCE d:\Xampp\htdocs\trackit\sql\store_incharge_tables.sql;
```

This creates 5 tables:
- `deliveries` - Delivery tracking and courier integration
- `delivery_tracking_history` - Status timeline for deliveries
- `product_barcodes` - Barcode generation and batch tracking
- `returns` - Return request workflow and inspection
- `low_stock_alerts` - Automated stock notifications

---

## API Endpoints

### 1. Booking Requests API
**File:** `api/store_incharge/booking_requests.php`

#### GET - Fetch Booking Requests
```
GET /api/store_incharge/booking_requests.php?status=Pending
```
- Fetches bookings assigned to Store In-charge or unassigned
- Filter by status: `Pending`, `Confirmed`
- Priority sorting: Urgent → High → Normal → Low

#### PUT - Confirm/Reject Booking
```json
{
  "booking_id": 5,
  "action": "confirm"  // or "reject"
}
```
- **Confirm:** Validates stock, assigns to user, logs history
- **Reject:** Restores stock, updates status, logs reason

---

### 2. Deliveries API
**File:** `api/store_incharge/deliveries.php`

#### GET - Fetch Deliveries
```
GET /api/store_incharge/deliveries.php?status=In Transit
GET /api/store_incharge/deliveries.php?id=10
GET /api/store_incharge/deliveries.php?tracking=DTDC-1704067200-9876
```
- Fetch all deliveries with pagination
- Get specific delivery by ID
- Track by tracking number (public)

#### POST - Create Delivery
```json
{
  "booking_id": 5,
  "courier_name": "DTDC",
  "tracking_number": "DTDC12345",  // Optional, auto-generated
  "dispatch_date": "2024-01-15",
  "expected_delivery_date": "2024-01-20",
  "delivery_notes": "Handle with care"
}
```
- Auto-generates tracking number if not provided
- Updates booking status to `Processing`
- Creates initial tracking history entry

#### PUT - Update Delivery Status
```json
{
  "delivery_id": 10,
  "delivery_status": "Delivered",
  "location": "Customer doorstep",
  "status_description": "Package delivered successfully",
  "proof_of_delivery": "https://cloudinary.com/image.jpg"
}
```
- Updates delivery status (Dispatched → In Transit → Out for Delivery → Delivered)
- Logs tracking history
- If `Delivered`: updates booking status, sets actual delivery date, updates customer stats

---

### 3. Barcodes API
**File:** `api/store_incharge/barcodes.php`

#### GET - Scan/Fetch Barcodes
```
GET /api/store_incharge/barcodes.php?scan=PROD-SKU123-1704067200-9876
GET /api/store_incharge/barcodes.php?product_id=5
GET /api/store_incharge/barcodes.php?status=Active
```
- **Scan:** Returns product details for scanned barcode
- **Product:** Get all barcodes for specific product
- **All:** Paginated list with filtering

#### POST - Generate Barcode
```json
{
  "product_id": 5,
  "batch_number": "BATCH-2024-001",
  "manufacturing_date": "2024-01-01",
  "expiry_date": "2025-01-01",
  "quantity_per_batch": 100,
  "warehouse_location": "A-12-03",
  "notes": "Winter stock"
}
```
- Auto-generates unique barcode: `PROD-{SKU}-{TIMESTAMP}-{RANDOM}`
- Tracks batch information and expiry dates

#### PUT - Update Barcode
```json
{
  "barcode_id": 10,
  "status": "Expired",  // Active, Expired, Damaged, Recalled
  "warehouse_location": "B-15-07",
  "notes": "Moved to damaged goods"
}
```
- Change barcode status
- Update warehouse location
- Auto-expires if past expiry date

---

### 4. Returns API
**File:** `api/store_incharge/returns.php`

#### GET - Fetch Returns
```
GET /api/store_incharge/returns.php?status=Pending
GET /api/store_incharge/returns.php?id=10
GET /api/store_incharge/returns.php?return_number=RET-1704067200-9876
```
- Fetch returns with filtering
- Get specific return details
- Track by return number

#### POST - Create Return Request
```json
{
  "booking_id": 5,
  "return_reason": "Defective",  // Defective, Damaged, Wrong Product, Changed Mind, etc.
  "quantity_returned": 1,
  "customer_comments": "Screen not working",
  "damage_images": ["url1.jpg", "url2.jpg"]
}
```
- Auto-generates return number: `RET-{TIMESTAMP}-{RANDOM}`
- Updates booking status to `Return Requested`

#### PUT - Update Return Status
```json
{
  "return_id": 10,
  "action": "approve"  // approve, reject, inspect, restock, complete
}
```

**Actions:**
1. **Approve:** Accept return request
2. **Reject:** Deny return with reason
3. **Inspect:** Record product condition
   ```json
   {
     "return_id": 10,
     "action": "inspect",
     "condition_on_return": "Damaged",  // Good, Acceptable, Damaged, Unusable
     "inspection_notes": "Screen cracked, frame intact",
     "restocking_fee": 100.00,
     "damage_images": ["inspect1.jpg"]
   }
   ```
4. **Restock:** Add product back to inventory (only if Good/Acceptable)
5. **Complete:** Mark as refunded, update customer stats

---

### 5. Low Stock Alerts API
**File:** `api/store_incharge/alerts.php`

#### GET - Fetch Alerts
```
GET /api/store_incharge/alerts.php?status=Active
GET /api/store_incharge/alerts.php?type=Out of Stock
GET /api/store_incharge/alerts.php?check=true  // Auto-generate alerts
GET /api/store_incharge/alerts.php?id=10
```
- Fetch alerts with filtering
- **check=true:** Scans all products and creates alerts automatically
- Priority sorting: Out of Stock → Critical → Low Stock

#### POST - Create Manual Alert
```json
{
  "product_id": 5,
  "threshold_level": 20,
  "notified_users": [2, 3, 5]  // User IDs
}
```
- Creates alert for specific product
- Auto-determines alert type based on quantity

#### PUT - Update Alert Status
```json
{
  "alert_id": 10,
  "action": "acknowledge"  // acknowledge or resolve
}
```

**Resolve:**
```json
{
  "alert_id": 10,
  "action": "resolve",
  "resolution_notes": "Stock replenished - 500 units added"
}
```

---

## Status Workflows

### Booking Flow
```
Pending → Confirmed → Processing → Delivered
        ↓
      Rejected (stock restored)
```

### Delivery Flow
```
Dispatched → In Transit → Out for Delivery → Delivered
           ↓
         Failed/Returned
```

### Return Flow
```
Pending → Approved → Inspected → Restocked → Completed
        ↓
      Rejected (no refund)
```

### Alert Flow
```
Active → Acknowledged → Resolved
```

---

## Features Implemented

### ✅ Booking Verification
- Confirm bookings with stock validation
- Reject bookings with stock restoration
- Priority-based sorting
- Auto-assignment to Store In-charge

### ✅ Delivery Management
- Create deliveries with auto-generated tracking numbers
- Update delivery status with location tracking
- Track delivery history timeline
- Proof of delivery upload support
- Customer statistics update on delivery

### ✅ Barcode System
- Auto-generate unique barcodes
- Batch tracking with expiry dates
- Warehouse location management
- Barcode scanning for product lookup
- Auto-expire based on dates

### ✅ Returns Handling
- Multi-step return workflow
- Damage image support (JSON array)
- Product inspection with condition tracking
- Conditional restocking (Good/Acceptable only)
- Restocking fee calculation
- Customer stats adjustment on refund

### ✅ Stock Alerts
- Auto-detect low stock (Low/Critical/Out of Stock)
- Manual alert creation
- Acknowledgment workflow
- Resolution tracking
- Priority-based display

---

## Next Steps

### Frontend Integration (store_in-charge.js)
1. Create booking verification UI
2. Delivery management dashboard
3. Barcode scanner integration (HTML5 camera or hardware)
4. Returns inspection form with image upload
5. Alert notifications panel

### Additional Features
1. **Courier API Integration:**
   - Delhivery/DTDC/BlueDart tracking APIs
   - Auto-update delivery status
   - Store API responses in `courier_api_response` JSON field

2. **Image Uploads:**
   - Cloudinary integration for damage reports
   - Proof of delivery images
   - Barcode label generation (PDF)

3. **Export Tools:**
   - CSV/Excel reports for deliveries
   - Returns summary reports
   - Stock alert reports

4. **Notifications:**
   - Email/SMS on low stock
   - Real-time alerts to Admin In-charge
   - Customer delivery notifications

5. **Barcode Printing:**
   - Generate printable barcode labels (PDF)
   - Batch printing for multiple products

---

## Testing Checklist

### Booking Requests
- [ ] Confirm booking with sufficient stock
- [ ] Confirm booking with insufficient stock (should fail)
- [ ] Reject booking (stock should restore)
- [ ] Filter by status and priority

### Deliveries
- [ ] Create delivery with auto-generated tracking
- [ ] Update status through full workflow
- [ ] Upload proof of delivery
- [ ] Track by tracking number

### Barcodes
- [ ] Generate unique barcodes for product
- [ ] Scan barcode to get product info
- [ ] Auto-expire on past expiry date
- [ ] Update warehouse location

### Returns
- [ ] Create return request
- [ ] Approve/reject return
- [ ] Inspect with condition and images
- [ ] Restock (Good condition)
- [ ] Block restock (Damaged condition)
- [ ] Complete and verify customer stats update

### Alerts
- [ ] Auto-generate alerts for low stock products
- [ ] Create manual alert
- [ ] Acknowledge alert
- [ ] Resolve alert with notes

---

## Error Handling

All APIs return consistent error responses:
```json
{
  "success": false,
  "error": "Error message here"
}
```

Common errors:
- `401 Unauthorized` - Not logged in or wrong role
- `404 Not Found` - Resource doesn't exist
- `400 Bad Request` - Missing required fields
- `409 Conflict` - Duplicate entry (barcode, delivery, return)

---

## Security Notes

1. **Role-based Access:** All APIs require `Store In-charge` or `Owner` role
2. **Owner Isolation:** All queries filter by `owner_id` to prevent cross-tenant access
3. **Stock Validation:** Stock checks before confirm/restock operations
4. **JSON Sanitization:** All JSON fields properly encoded/decoded
5. **SQL Injection Prevention:** All queries use prepared statements with bind_param

---

## Database Maintenance

### Auto-expire Barcodes (Run daily via cron)
```sql
UPDATE product_barcodes 
SET status = 'Expired', auto_expired = 1 
WHERE expiry_date < CURDATE() AND status = 'Active';
```

### Clean Old Alerts (Run weekly)
```sql
DELETE FROM low_stock_alerts 
WHERE alert_status = 'Resolved' 
AND resolved_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Archive Old Returns (Run monthly)
```sql
-- Move completed returns older than 1 year to archive table
INSERT INTO returns_archive SELECT * FROM returns 
WHERE return_status = 'Completed' 
AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

DELETE FROM returns 
WHERE return_status = 'Completed' 
AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

---

## Contact & Support
Module created for TrackIt Inventory Management System
Store In-charge module handles complete warehouse operations workflow.
