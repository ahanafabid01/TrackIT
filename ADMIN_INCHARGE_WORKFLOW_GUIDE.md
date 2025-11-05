# 📦 Admin In-Charge Workflow Guide

## Table of Contents
1. [Overview](#overview)
2. [What is GRN?](#what-is-grn)
3. [Complete Workflow Process](#complete-workflow-process)
4. [How Everything Connects](#how-everything-connects)
5. [Real-World Example](#real-world-example)
6. [Database Structure](#database-structure)

---

## Overview

**Admin In-charge** is responsible for managing the **"back-end"** of inventory:
- Receiving goods from suppliers
- Recording what comes into the warehouse
- Managing stock levels and batches
- Maintaining supplier relationships
- Tracking costs and quality

Think of it as the **"Receiving Department"** in a company.

---

## What is GRN?

### **GRN = Goods Received Note**

A GRN is an official document that says:
> "We received products from a supplier on [date], and here's what we got."

### Why is GRN Important?

1. **Proof of Delivery** - Legal record that goods arrived
2. **Quality Check** - Did we get what we ordered?
3. **Payment Trigger** - Invoice verification before paying supplier
4. **Inventory Update** - Adds products to your stock
5. **Audit Trail** - Track every item that enters your business

### GRN Document Contains:
```
GRN Number: GRN-2025-001
Date Received: November 6, 2025
Supplier: Tech World Ltd
Invoice Number: INV-TW-2025-045

Items Received:
- Product: Laptop Stand
- Quantity Ordered: 50 units
- Quantity Received: 50 units
- Quantity Accepted: 48 units (2 damaged)
- Unit Cost: 1,800 BDT
- Batch Number: B25-LS-001
- Manufacturing Date: Oct 15, 2025

Status: Verified ✓
Approved By: Admin In-charge
```

---

## Complete Workflow Process

### **Step 1: Supplier Setup**
Before anything, you need suppliers in your system.

```
Action: Add Supplier
Button: "Add Supplier" (Suppliers Page)

Information Needed:
├── Company Name: "Tech World Ltd"
├── Contact Person: "Mr. Karim Ahmed"
├── Email: karim@techworld.com
├── Phone: +880 1712345678
├── Address: House 45, Road 12, Gulshan, Dhaka
├── Payment Terms: "Net 30" (pay within 30 days)
├── Credit Limit: 500,000 BDT
└── Rating: 4.5/5.0

Result: Supplier created with ID → Used in GRN
```

---

### **Step 2: Create Purchase Order (Optional)**
*This step happens outside the system (via email/phone)*

```
You contact supplier: "We want to buy 50 Laptop Stands"
Supplier sends: Invoice/Quotation
You approve: Place order
```

---

### **Step 3: Create GRN (Goods Arrive)**
Products arrive at your warehouse. Admin In-charge creates GRN.

```
Action: Create GRN
Button: "Create GRN" (GRN Page)

Step 1: Select Supplier
└── Choose: "Tech World Ltd"

Step 2: Enter Invoice Details
├── Invoice Number: INV-TW-2025-045
├── Invoice Date: November 1, 2025
└── Received Date: November 2, 2025

Step 3: Add Items (Can add multiple products)
For Each Item:
├── Product: Select "Laptop Stand"
├── Batch Number: B25-LS-001 (auto-generated)
├── Quantity Received: 50
├── Quantity Accepted: 48 (2 damaged, rejected)
├── Unit Cost: 1,800 BDT
├── Manufacturing Date: Oct 15, 2025
├── Expiry Date: (if applicable)
├── Warehouse Location: A-12-03
└── Condition: New/Used/Damaged

Step 4: GRN Summary
├── Total Items: 1 type of product
├── Total Quantity: 48 units (accepted)
├── Total Amount: 86,400 BDT (48 × 1,800)
├── Tax (15%): 12,960 BDT
└── Net Amount: 99,360 BDT

Step 5: Save GRN
Status: "Pending" → Needs verification
```

**What Happens in Database:**
```sql
-- 1. GRN record created
INSERT INTO grn (grn_number, supplier_id, invoice_number, status)
VALUES ('GRN-2025-001', 1, 'INV-TW-2025-045', 'Pending');

-- 2. GRN Items created
INSERT INTO grn_items (grn_id, product_id, batch_number, quantity_received)
VALUES (1, 5, 'B25-LS-001', 48);

-- 3. Product Batch created
INSERT INTO product_batches (product_id, batch_number, quantity_received)
VALUES (5, 'B25-LS-001', 48);

-- 4. Inventory Audit Log
INSERT INTO inventory_audit_logs (action_type, reference_type)
VALUES ('Stock In', 'GRN');
```

---

### **Step 4: Verify GRN**
Admin In-charge or senior staff verifies the GRN.

```
Action: Verify GRN
Button: "Verify" (in GRN table row)

Checks:
├── Are quantities correct?
├── Is quality acceptable?
├── Does invoice match goods received?
└── Are costs accurate?

Status Changes: "Pending" → "Verified"
```

---

### **Step 5: Approve GRN**
Final approval updates inventory.

```
Action: Approve GRN
Button: "Approve" (in GRN table row)

What Happens:
1. ✅ Product stock increases (0 → 48)
2. ✅ Batch becomes "Active"
3. ✅ Supplier performance logged
4. ✅ Payment status tracked
5. ✅ Stock alerts checked

Status Changes: "Verified" → "Approved"

Database Updates:
UPDATE products 
SET stock_quantity = stock_quantity + 48
WHERE id = 5;

UPDATE product_batches
SET status = 'Active'
WHERE batch_number = 'B25-LS-001';
```

---

### **Step 6: Stock Management (Ongoing)**

#### **Add Stock (Without GRN)**
Sometimes you add stock manually (internal transfers, returns, adjustments).

```
Action: Add Stock
Button: "Add Stock" (Inventory Page)

When to Use:
├── Stock returns from customers
├── Found missing inventory
├── Internal production
└── Manual adjustments

Process:
├── Select Product
├── Enter Quantity
├── Generate/Enter Batch Number
├── Enter Unit Cost
├── Add Warehouse Location
└── Provide Reason

Result: Stock increases + Audit log created
Note: This does NOT create a GRN (no supplier involved)
```

---

## How Everything Connects

### **The Relationship Chain**

```
┌─────────────┐
│  SUPPLIER   │ (Who you buy from)
└──────┬──────┘
       │ provides goods
       ↓
┌─────────────┐
│     GRN     │ (Record of receiving goods)
└──────┬──────┘
       │ contains
       ↓
┌─────────────┐
│  GRN ITEMS  │ (Individual products in that delivery)
└──────┬──────┘
       │ creates
       ↓
┌─────────────┐
│PRODUCT BATCH│ (Specific batch with manufacturing date, cost)
└──────┬──────┘
       │ updates
       ↓
┌─────────────┐
│  PRODUCTS   │ (Main product with total stock)
└──────┬──────┘
       │ tracked by
       ↓
┌─────────────┐
│ AUDIT LOGS  │ (Every change recorded)
└─────────────┘
```

### **Example Flow:**

```
1. Supplier "Tech World Ltd" 
   ↓
2. Sends 50 Laptop Stands with Invoice INV-TW-2025-045
   ↓
3. You create GRN-2025-001
   ↓
4. GRN creates Batch B25-LS-001
   ↓
5. Batch contains:
   - 48 units (2 damaged rejected)
   - Cost: 1,800 BDT each
   - Mfg Date: Oct 15, 2025
   - Location: Warehouse A-12-03
   ↓
6. Product "Laptop Stand" stock updates: 0 → 48
   ↓
7. Audit log records: "Stock In via GRN-2025-001"
```

---

## Real-World Example

### **Scenario: Your Electronics Shop**

#### **Month 1: Setup**
```
Day 1: Add Supplier
- Tech World Ltd (laptops, accessories)
- Rating: 4.5/5
- Payment: Net 30 days
- Credit Limit: 500,000 BDT
```

#### **Month 2: First Order**
```
Day 5: Order placed (via email)
- 50 Laptop Stands
- 100 Wireless Mice
- 30 USB Hubs

Day 7: Goods arrive at warehouse
```

#### **Day 7: Create GRN**
```
GRN-2025-001
Supplier: Tech World Ltd
Invoice: INV-TW-2025-045

Item 1:
- Product: Laptop Stand
- Batch: B25-LS-001
- Received: 50 | Accepted: 48 (2 damaged)
- Cost: 1,800 BDT/unit
- Location: A-12-03

Item 2:
- Product: Wireless Mouse
- Batch: B25-WM-001
- Received: 100 | Accepted: 98 (2 missing)
- Cost: 800 BDT/unit
- Location: B-05-11

Item 3:
- Product: USB Hub
- Batch: B25-UH-001
- Received: 30 | Accepted: 30
- Cost: 2,400 BDT/unit
- Location: A-18-07

Total: 176 items accepted
Status: Pending
```

#### **Day 8: Verification**
```
Admin In-charge checks:
✓ Physical count matches
✓ Invoice matches
✓ Quality acceptable
✗ 2 mice missing - noted
✗ 2 laptop stands damaged - rejected

Action: Mark as "Verified"
```

#### **Day 8: Approval**
```
Owner/Senior Admin approves:
Action: Click "Approve"

System automatically:
1. Updates inventory:
   - Laptop Stand: 0 → 48
   - Wireless Mouse: 0 → 98
   - USB Hub: 0 → 30

2. Creates 3 active batches

3. Logs supplier performance:
   - Delivery: On-time ✓
   - Quality: 97.8% (176/180)
   - Rating: 4.5/5

4. Sets payment due date: Dec 7, 2025 (30 days)
```

#### **Day 10: Customer Orders**
```
Moderator creates booking:
- Customer wants 2 Laptop Stands
- System assigns from Batch B25-LS-001
- Stock: 48 → 46
- Batch tracking: 2 sold from this batch
```

#### **Day 15: More Stock Needed**
```
You receive 20 more Laptop Stands from DIFFERENT supplier
- Create NEW GRN (GRN-2025-002)
- Different batch: B25-LS-002
- Different cost: 1,900 BDT (price increased)

Now you have TWO batches:
- B25-LS-001: 46 units @ 1,800 BDT (from Tech World)
- B25-LS-002: 20 units @ 1,900 BDT (from new supplier)

Total Stock: 66 units
```

---

## Database Structure

### **Key Tables and Their Purpose**

#### 1. **suppliers**
```sql
Stores: Supplier information
Purpose: Know who you buy from
Fields:
- company_name
- contact_person
- email, phone
- payment_terms (Net 30/60)
- credit_limit
- rating (performance score)
```

#### 2. **grn** (Goods Received Notes)
```sql
Stores: Each delivery/receipt
Purpose: Official record of receiving goods
Fields:
- grn_number (GRN-2025-001)
- supplier_id (links to supplier)
- invoice_number (supplier's invoice)
- total_amount
- status (Pending/Verified/Approved)
- received_date
```

#### 3. **grn_items**
```sql
Stores: Individual products in each GRN
Purpose: Details of what was received
Fields:
- grn_id (links to GRN)
- product_id (which product)
- batch_number (B25-LS-001)
- quantity_received
- quantity_accepted
- unit_cost
- manufacturing_date
- expiry_date
```

#### 4. **product_batches**
```sql
Stores: Each batch of products
Purpose: Track groups received together
Fields:
- batch_number (unique identifier)
- product_id (which product)
- grn_id (which GRN created it)
- quantity_received
- quantity_available (current stock)
- quantity_sold (how many sold)
- unit_cost (cost at time of purchase)
- manufacturing_date
- expiry_date
- warehouse_location
- status (Active/Expired/Depleted)
```

#### 5. **products**
```sql
Stores: Main product information
Purpose: Product catalog
Fields:
- name
- sku
- stock_quantity (total from ALL batches)
- low_stock_threshold
- selling_price
```

#### 6. **inventory_audit_logs**
```sql
Stores: Every stock change
Purpose: Complete history/accountability
Fields:
- action_type (Stock In/Stock Out/Adjustment)
- reference_type (GRN/Booking/Manual)
- reference_id (links to GRN/Booking)
- quantity_before
- quantity_change
- quantity_after
- performed_by (user ID)
- reason
- timestamp
```

---

## Key Concepts Explained

### **Why Batch Numbers?**

**Problem:** You buy 100 laptops in January and 100 more in March.
- January batch: 50,000 BDT each
- March batch: 52,000 BDT each (price increased)

**Without Batch Tracking:**
- Can't tell which laptop cost what
- Can't track when each arrived
- If there's a defect, can't identify affected units

**With Batch Tracking:**
- Batch B25-LAP-001: 100 laptops @ 50,000 BDT (Jan batch)
- Batch B25-LAP-002: 100 laptops @ 52,000 BDT (Mar batch)
- Each sale: "Sold 1 from B25-LAP-001" → Know exact cost

### **GRN vs Add Stock**

| Feature | Create GRN | Add Stock |
|---------|-----------|-----------|
| **Supplier involved?** | ✓ Yes | ✗ No |
| **Creates batch?** | ✓ Yes | ✓ Yes |
| **Needs invoice?** | ✓ Yes | ✗ No |
| **Payment tracking?** | ✓ Yes | ✗ No |
| **Supplier performance?** | ✓ Yes | ✗ No |
| **When to use?** | Receiving from supplier | Internal adjustments |

### **Workflow States**

```
GRN Lifecycle:
┌─────────┐    Verify    ┌──────────┐    Approve    ┌──────────┐
│ Pending │ ──────────→  │ Verified │ ───────────→  │ Approved │
└─────────┘              └──────────┘               └──────────┘
    ↓                         ↓                          ↓
No stock                 No stock                  Stock updated!
update yet               update yet                Batch active
```

---

## Admin In-Charge Dashboard Features

### **1. Dashboard Page**
```
Shows:
├── Total Inventory Value
├── GRN This Month
├── Low Stock Alerts
├── Active Suppliers
└── Quick Actions
```

### **2. Inventory Page**
```
Shows:
├── All products with stock levels
├── Batch information
├── Stock status (In Stock/Low/Out)
└── Actions:
    ├── View Details
    ├── Add Stock (manual adjustment)
    └── Generate Barcode
```

### **3. GRN Page**
```
Shows:
├── All GRNs with status
├── Supplier info
├── Total amounts
└── Actions:
    ├── View Details
    ├── Verify GRN
    ├── Approve GRN
    └── Filter by status/date
```

### **4. Suppliers Page**
```
Shows:
├── All suppliers
├── Performance ratings
├── Payment terms
└── Actions:
    ├── Add Supplier
    ├── View Details
    └── Track Performance
```

---

## Common Questions

### **Q1: Why do I need GRN if I'm just adding stock?**
**A:** GRN is for official supplier deliveries. It:
- Links to supplier for payment tracking
- Creates legal proof of receipt
- Tracks supplier performance
- Needed for accounts/audit

For internal adjustments (returns, found items), use "Add Stock" instead.

### **Q2: Can I have multiple batches of the same product?**
**A:** Yes! That's the point. Example:
- Product: "iPhone 15"
- Batch 1: 50 units @ 80,000 BDT (from Supplier A, Jan 2025)
- Batch 2: 30 units @ 82,000 BDT (from Supplier B, Feb 2025)
- Total Stock: 80 units (but tracked separately by batch)

### **Q3: What if I don't know the batch number?**
**A:** System auto-generates it! Format: `B[Year]-[Product Code]-[Unique ID]`
Example: `B25-PLS-847392`

### **Q4: How do I know which batch to sell from?**
**A:** System uses FIFO (First In, First Out) by default:
- Oldest batch sells first
- Or you can manually select batch

### **Q5: Do I need manufacturing/expiry dates?**
**A:** Depends on product type:
- Electronics: Usually no expiry
- Food/Medicine: REQUIRED
- Cosmetics: Recommended
- Books/Stationery: Not needed

---

## Complete Process Summary

```
┌─────────────────────────────────────────────────────────┐
│                    ADMIN IN-CHARGE FLOW                 │
└─────────────────────────────────────────────────────────┘

1. SETUP
   └── Add Suppliers to system

2. ORDERING (Outside system)
   └── Contact supplier, place order

3. RECEIVING (GRN Creation)
   ├── Goods arrive at warehouse
   ├── Create GRN with supplier + invoice details
   ├── Add all items received
   ├── Generate batch numbers
   ├── Note any damages/missing items
   └── Save as "Pending"

4. VERIFICATION
   ├── Check physical goods vs GRN
   ├── Verify invoice matches
   ├── Confirm quality
   └── Mark as "Verified"

5. APPROVAL
   ├── Final check
   ├── Click "Approve"
   └── System updates:
       ├── Inventory stock ↑
       ├── Batches created
       ├── Supplier performance logged
       └── Payment tracking started

6. ONGOING MANAGEMENT
   ├── Monitor stock levels
   ├── Check alerts (low stock, expiry)
   ├── Add stock (manual adjustments)
   ├── Generate barcodes
   └── Track supplier performance

7. REPORTING
   ├── Stock valuation reports
   ├── GRN history
   ├── Supplier performance
   └── Audit trails
```

---

## Tips for Admin In-Charge

### **✅ Best Practices**

1. **Always verify physical count** before approving GRN
2. **Take photos** of damaged goods for records
3. **Check expiry dates** on receiving
4. **Organize warehouse** by batch numbers
5. **Update immediately** - don't delay GRN approval
6. **Monitor alerts** daily
7. **Rate suppliers** honestly for future decisions

### **❌ Common Mistakes to Avoid**

1. ❌ Approving GRN without physical verification
2. ❌ Forgetting to reject damaged items
3. ❌ Using same batch number for different deliveries
4. ❌ Not recording actual costs
5. ❌ Ignoring expiry dates
6. ❌ Missing warehouse location

---

## Need Help?

**If you see "No products available":**
- Products must exist before creating GRN
- Use "Add New Product" feature first
- Or contact system admin

**If GRN approval fails:**
- Check if product exists
- Verify batch number is unique
- Ensure quantities are valid

**For training or questions:**
- Review this guide
- Check database sample data
- Test with small quantities first

---

**Last Updated:** November 6, 2025  
**Version:** 1.0  
**For:** TrackIt Inventory Management System
