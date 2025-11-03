# 📦 Booking Status Workflow - Complete Guide

## 🔄 Status Flow Diagram

```
┌─────────┐
│ PENDING │ ──────┬──────────> Initial booking created
└─────────┘       │
      │           └──> [REJECTED] (Stock restored)
      │ Confirm
      ▼
┌───────────┐
│ CONFIRMED │ ────────────────> Stock verified & reserved
└───────────┘
      │
      │ Start Processing
      ▼
┌────────────┐
│ PROCESSING │ ──────────────> Picking, packing, preparing
└────────────┘
      │
      │ Mark Ready
      ▼
┌────────┐
│ READY  │ ────────────────────> Ready for dispatch/pickup
└────────┘
      │
      │ Mark Delivered
      ▼
┌───────────┐
│ DELIVERED │ ──────────────────> Completed & delivered
└───────────┘

Note: [CANCELLED] can be triggered from Pending, Confirmed, Processing, or Ready
```

---

## 📊 Status Definitions

### 1️⃣ PENDING
**Description:** New booking request waiting for Store In-charge approval

**Actions Available:**
- ✅ **Confirm** → Validates stock and moves to Confirmed
- ❌ **Reject** → Restores stock and ends workflow

**Business Rules:**
- Stock must be available to confirm
- Can be rejected with reason
- Auto-assigned to Store In-charge on first action

---

### 2️⃣ CONFIRMED
**Description:** Booking approved, stock reserved

**Actions Available:**
- ⚙️ **Start Processing** → Begins order preparation
- 🚫 **Cancel** → Restores stock and ends workflow

**Business Rules:**
- Stock is reserved (not physically deducted yet)
- Cannot be rejected (must be cancelled instead)
- Creates booking history record

---

### 3️⃣ PROCESSING
**Description:** Order being picked, packed, and prepared

**Actions Available:**
- ✅ **Mark Ready** → Order ready for dispatch
- 🚚 **Deliver** → Direct delivery (skip Ready state)

**Business Rules:**
- Physical preparation happening
- Can deliver directly if ready immediately
- Stock tracking continues

**Typical Duration:** 1-4 hours depending on order complexity

---

### 4️⃣ READY
**Description:** Order packed and ready for dispatch/pickup

**Actions Available:**
- 🚚 **Mark Delivered** → Final delivery confirmation
- 🚫 **Cancel** → Restores stock (emergency only)

**Business Rules:**
- Order is physically ready
- Waiting for courier pickup or customer collection
- Can still be cancelled in emergencies

**Typical Duration:** 0-24 hours until dispatch

---

### 5️⃣ DELIVERED
**Description:** Order successfully delivered to customer

**Actions Available:**
- None (final state)

**Business Rules:**
- `delivery_date` timestamp recorded
- No further status changes allowed
- May trigger return workflow if issues arise
- Stock permanently deducted

**Post-Delivery:**
- Customer can create return request
- Return workflow is separate process

---

### ❌ REJECTED
**Description:** Booking rejected during Pending stage

**Actions Available:**
- None (final state)

**Business Rules:**
- Only from Pending status
- Requires rejection reason
- Stock quantity restored immediately
- Rejection reason stored in booking_history

**Common Reasons:**
- Insufficient stock
- Invalid product/customer
- Duplicate booking
- Customer cancellation before confirmation

---

### 🚫 CANCELLED
**Description:** Booking cancelled after confirmation

**Actions Available:**
- None (final state)

**Business Rules:**
- Can cancel from: Confirmed, Processing, Ready
- Cannot cancel: Delivered or Rejected bookings
- Requires cancellation reason
- Stock restored automatically

**Common Reasons:**
- Customer requested cancellation
- Payment failed
- Product discontinued
- Delivery address unreachable
- Emergency stock issues

---

## 🎯 Store In-charge Actions Matrix

| Current Status | Available Actions | Button Color | Icon | Next Status |
|---------------|-------------------|--------------|------|-------------|
| **Pending** | Confirm | 🟢 Green | ✓ | Confirmed |
| | Reject | 🔴 Red | ✗ | Rejected |
| **Confirmed** | Start Processing | 🔵 Blue | ⚙ | Processing |
| | Cancel | 🟡 Yellow | 🚫 | Cancelled |
| **Processing** | Mark Ready | 🟢 Green | ✓ | Ready |
| | Deliver | 🔵 Blue | 🚚 | Delivered |
| **Ready** | Mark Delivered | 🔵 Blue | 🚚 | Delivered |
| | Cancel | 🟡 Yellow | 🚫 | Cancelled |
| **Delivered** | _None_ | - | - | - |
| **Rejected** | _None_ | - | - | - |
| **Cancelled** | _None_ | - | - | - |

---

## 🛠️ API Usage Examples

### Confirm Booking (Pending → Confirmed)
```javascript
await fetch('/api/store_incharge/booking_requests.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        booking_id: 123,
        status: 'Confirmed',
        notes: 'Stock verified, booking approved'
    })
});
```

### Start Processing (Confirmed → Processing)
```javascript
await fetch('/api/store_incharge/booking_requests.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        booking_id: 123,
        status: 'Processing',
        notes: 'Order preparation started'
    })
});
```

### Mark Ready (Processing → Ready)
```javascript
await fetch('/api/store_incharge/booking_requests.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        booking_id: 123,
        status: 'Ready',
        notes: 'Order packed and ready for dispatch'
    })
});
```

### Mark Delivered (Ready → Delivered)
```javascript
await fetch('/api/store_incharge/booking_requests.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        booking_id: 123,
        status: 'Delivered',
        notes: 'Delivered by courier XYZ'
    })
});
```

### Reject Booking (Pending → Rejected)
```javascript
await fetch('/api/store_incharge/booking_requests.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        booking_id: 123,
        status: 'Rejected',
        notes: 'Insufficient stock - only 5 units available'
    })
});
```

### Cancel Booking (Any → Cancelled)
```javascript
await fetch('/api/store_incharge/booking_requests.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        booking_id: 123,
        status: 'Cancelled',
        notes: 'Customer requested cancellation'
    })
});
```

---

## 🔒 Status Transition Validations

### API Enforced Rules

**Pending Status:**
- ✅ Can confirm if stock available
- ✅ Can reject with reason
- ❌ Cannot skip to Processing/Ready/Delivered

**Confirmed Status:**
- ✅ Can start processing
- ✅ Can cancel
- ❌ Cannot reject (use cancel instead)
- ❌ Cannot go back to Pending

**Processing Status:**
- ✅ Can mark ready
- ✅ Can deliver directly
- ❌ Cannot go back to Confirmed
- ❌ Cannot reject

**Ready Status:**
- ✅ Can mark delivered
- ✅ Can cancel (emergency only)
- ❌ Cannot go back to Processing

**Delivered/Rejected/Cancelled:**
- ❌ No status changes allowed (final states)

---

## 📈 Stock Management Logic

### When Stock is Deducted:
- **Booking Created (by Moderator):** Stock immediately deducted
- **Booking Confirmed:** Stock validation only (already deducted)
- **Booking Delivered:** Stock permanently removed

### When Stock is Restored:
- **Booking Rejected:** Full quantity restored
- **Booking Cancelled:** Full quantity restored (from Confirmed/Processing/Ready)

### Stock Validation:
- **Confirm Action:** Checks if `product.stock_quantity >= booking.quantity`
- If insufficient → Error: "Insufficient stock. Available: X units"

---

## 🔔 Notifications & History

### Booking History Records
Every status change creates a record in `booking_history` table:

```sql
INSERT INTO booking_history (
    booking_id, 
    previous_status, 
    new_status, 
    changed_by, 
    notes, 
    created_at
) VALUES (?, ?, ?, ?, ?, NOW());
```

**Stored Information:**
- Previous status
- New status
- User who made the change
- Optional notes/reason
- Timestamp

---

## 🎨 UI Badge Colors

| Status | Background | Text Color | CSS Class |
|--------|-----------|-----------|-----------|
| Pending | Yellow | Orange | `badge-pending` |
| Confirmed | Green | Dark Green | `badge-confirmed` |
| Processing | Blue | Dark Blue | `badge-processing` |
| Ready | Indigo | Purple | `badge-ready` |
| Delivered | Green | Dark Green | `badge-delivered` |
| Rejected | Red | Dark Red | `badge-rejected` |
| Cancelled | Gray | Dark Gray | `badge-cancelled` |

---

## 📱 Frontend Implementation

### Dynamic Button Rendering
```javascript
switch(booking.status) {
    case 'Pending':
        // Show: Confirm, Reject
        break;
    case 'Confirmed':
        // Show: Start Processing, Cancel
        break;
    case 'Processing':
        // Show: Mark Ready, Deliver
        break;
    case 'Ready':
        // Show: Mark Delivered, Cancel
        break;
    case 'Cancelled':
    case 'Rejected':
    case 'Delivered':
        // Show: Status label only (no actions)
        break;
}
```

---

## 🧪 Testing Scenarios

### Test Case 1: Normal Workflow
1. Create booking (Pending)
2. Confirm → Verify stock deducted
3. Start Processing
4. Mark Ready
5. Mark Delivered → Check delivery_date

### Test Case 2: Rejection
1. Create booking (Pending)
2. Reject with reason → Verify stock restored

### Test Case 3: Cancellation
1. Create booking (Pending)
2. Confirm
3. Cancel → Verify stock restored

### Test Case 4: Insufficient Stock
1. Create booking with quantity > available stock
2. Try to confirm → Should fail with error

### Test Case 5: Invalid Transition
1. Create booking (Pending)
2. Try to mark as Delivered directly → Should fail

---

## 💡 Best Practices

### For Store In-charge:

1. **Always Add Notes:** Provide context for status changes
2. **Verify Stock:** Check physical stock before confirming
3. **Update Promptly:** Move orders through workflow quickly
4. **Handle Cancellations Carefully:** Ensure customer communication
5. **Use Ready Status:** Don't skip directly to Delivered

### For Developers:

1. **Transaction Safety:** Use database transactions for status changes
2. **Audit Trail:** Always log to booking_history
3. **Validation:** Enforce status transition rules in API
4. **Stock Consistency:** Always restore stock on cancellation/rejection
5. **Error Messages:** Provide clear, actionable error messages

---

## 📊 Dashboard Filtering

### Load Active Bookings:
```javascript
// Shows: Pending, Confirmed, Processing, Ready, Cancelled
?status=Pending,Confirmed,Processing,Ready,Cancelled
```

### Load Completed Bookings:
```javascript
// Shows: Delivered, Rejected
?status=Delivered,Rejected
```

### Load Specific Status:
```javascript
// Example: Only Processing orders
?status=Processing
```

---

## 🚀 Quick Reference

**Full Status Sequence (Happy Path):**
```
Pending → Confirmed → Processing → Ready → Delivered
```

**Rejection Path:**
```
Pending → Rejected
```

**Cancellation Paths:**
```
Confirmed → Cancelled
Processing → Cancelled
Ready → Cancelled
```

**Final States (No further changes):**
- ✅ Delivered
- ❌ Rejected
- 🚫 Cancelled

---

**Documentation Updated:** November 4, 2025  
**API Version:** 1.0  
**Status:** Production Ready ✅
