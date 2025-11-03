# TrackIt - Complete Setup Guide

## 📋 Overview
TrackIt is a multi-tenant inventory and order management system with role-based access control. Each **Owner** has their own isolated workspace where they can manage their team members (Moderator, Accountant, Admin In-charge, Store In-charge).

## 🎯 Key Features Implemented

### ✅ Multi-Tenant Architecture
- Each Owner has a separate workspace
- Owner's team members are isolated from other owners
- No data leakage between different owner spaces

### ✅ Role-Based Access Control
- **Owner**: Creates and manages team members, oversees all operations
- **Moderator**: Handles customer bookings and inquiries
- **Accountant**: Manages payments, refunds, and financial records
- **Admin In-charge**: Oversees inventory, GRN, and supplier management
- **Store In-charge**: Manages delivery, barcode scanning, and logistics

### ✅ Authentication System
- Signup creates **Owner** accounts only
- Login redirects users to role-specific dashboards
- Session management with security features

### ✅ Modern UI/UX
- Professional, responsive design
- Mobile-friendly dashboards
- Consistent styling across all pages

---

## 🚀 Installation Steps

### 1. Database Setup

#### Option A: Import the SQL file
1. Open phpMyAdmin
2. Create a database named `trackit`
3. Import the file: `sql/users.sql`

#### Option B: Run SQL manually
```sql
CREATE DATABASE trackit;
USE trackit;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('Owner','Moderator','Accountant','Admin In-charge','Store In-charge') NOT NULL DEFAULT 'Owner',
  `owner_id` int(11) DEFAULT NULL COMMENT 'References the owner user_id. NULL for Owners',
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `oauth_provider` varchar(50) DEFAULT NULL,
  `oauth_uid` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  CONSTRAINT `fk_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2. Configure Database Connection

Edit `config/config.php` if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'trackit');
```

### 3. Start XAMPP
- Start Apache
- Start MySQL

### 4. Access the Application
Navigate to: `http://localhost/trackit/auth/auth.php`

---

## 👥 User Flow

### Creating an Owner Account
1. Go to `http://localhost/trackit/auth/auth.php`
2. Click **Sign up**
3. Fill in the form (Name, Email, Password)
4. Click **Create account**
5. After successful signup, click **Sign in**
6. Login with your credentials
7. You'll be redirected to the **Owner Dashboard**

### Owner: Adding Team Members
1. Login as Owner
2. Click **User Management** in the sidebar
3. Click **Add New User** button
4. Fill in the form:
   - Full Name
   - Email Address
   - Role (Moderator, Accountant, Admin In-charge, Store In-charge)
   - Password
5. Click **Create User**

### Team Members: Logging In
1. Go to `http://localhost/trackit/auth/auth.php`
2. Login with the credentials provided by the Owner
3. You'll be redirected to your role-specific dashboard:
   - Moderator → `main/pages/moderator.php`
   - Accountant → `main/pages/accountant.php`
   - Admin In-charge → `main/pages/admin_in-charge.php`
   - Store In-charge → `main/pages/store_in-charge.php`

---

## 📁 Project Structure

```
trackit/
├── auth/
│   ├── auth.php          # Login/Signup page
│   ├── auth.js           # Authentication JavaScript
│   ├── login.php         # Login handler (role-based redirect)
│   ├── signup.php        # Signup handler (Owner only)
│   └── css/
│       └── auth.css      # Authentication styling
├── config/
│   └── config.php        # Database config & helper functions
├── main/
│   ├── owner_dashboard.php    # Owner dashboard
│   ├── add_user.php           # Add user handler
│   ├── delete_user.php        # Delete user handler
│   ├── logout.php             # Logout handler
│   ├── css/
│   │   ├── styles.css         # Main dashboard styles
│   │   └── dashboard-common.js # Common JS functions
│   └── pages/
│       ├── moderator.php           # Moderator dashboard
│       ├── accountant.php          # Accountant dashboard
│       ├── admin_in-charge.php     # Admin In-charge dashboard
│       ├── store_in-charge.php     # Store In-charge dashboard
│       ├── css/
│       │   ├── moderator.css
│       │   ├── accountant.css
│       │   ├── admin_in-charge.css
│       │   └── store_in-charge.css
│       └── js/
│           ├── moderator.js
│           ├── accountant.js
│           ├── admin_in-charge.js
│           └── store_in-charge.js
└── sql/
    └── users.sql         # Database schema
```

---

## 🔒 Security Features

### Multi-Tenant Isolation
- Each Owner can only see and manage their own team
- `owner_id` field ensures data segregation
- SQL queries filter by `owner_id` automatically

### Session Management
```php
$_SESSION['user_id']       // Current user ID
$_SESSION['role']          // User role
$_SESSION['owner_id']      // Owner ID (NULL for Owners)
```

### Helper Functions (in config.php)
```php
requireAuth()              // Ensures user is logged in
requireRole(['Owner'])     // Restricts access by role
getOwnerId()              // Gets the owner ID for current user
redirectToDashboard($role) // Redirects to role-specific dashboard
```

---

## 🎨 Customization Guide

### Changing Colors
Edit CSS variables in `auth/css/auth.css` and `main/css/styles.css`:
```css
:root {
    --primary-color: #3b82f6;
    --secondary-color: #8b5cf6;
    --success-color: #10b981;
    --error-color: #ef4444;
}
```

### Adding New Features
1. Create PHP file in appropriate directory
2. Use `requireRole()` for access control
3. Use `getOwnerId()` to filter data by owner
4. Create corresponding CSS/JS files
5. Link them in the PHP file

---

## 🧪 Testing the System

### Test Scenario 1: Multiple Owners
1. Create Owner 1 (email: owner1@example.com)
2. Login as Owner 1, create team members
3. Logout
4. Create Owner 2 (email: owner2@example.com)
5. Login as Owner 2, create team members
6. Verify that Owner 1 cannot see Owner 2's team members

### Test Scenario 2: Role-Based Access
1. Login as Moderator
2. Try to access owner_dashboard.php directly
3. Should be redirected due to `requireRole()` check

### Test Scenario 3: Team Member Login
1. Owner creates a Moderator
2. Moderator logs in
3. Verify redirect to moderator.php
4. Check that moderator can only access their assigned features

---

## 🔧 Troubleshooting

### "Connection failed" error
- Ensure MySQL is running in XAMPP
- Check database credentials in `config/config.php`
- Verify database `trackit` exists

### "Undefined index: role" error
- Clear browser cookies
- Login again
- Ensure you're using the updated `login.php`

### Redirect not working
- Check that `auth.js` has been updated
- Clear browser cache
- Check browser console for JavaScript errors

### Team members not showing in Owner dashboard
- Verify `owner_id` is set correctly in database
- Check that SQL query in `owner_dashboard.php` filters by `owner_id`

---

## 📝 Next Steps

### Recommended Enhancements
1. **Add Email Verification** for new signups
2. **Implement Password Reset** functionality
3. **Add Profile Management** for users
4. **Create Activity Logs** for audit trail
5. **Implement Real-Time Notifications** using WebSockets
6. **Add Data Export** functionality (CSV, Excel, PDF)
7. **Integrate Barcode Scanner** hardware/library
8. **Add API Integration** for courier tracking
9. **Implement Chart Visualizations** using Chart.js
10. **Add File Upload** for delivery proofs and damage reports

### Database Tables to Add
- `bookings` - Customer booking records
- `products` - Product catalog
- `inventory` - Stock management
- `deliveries` - Delivery tracking
- `payments` - Payment records
- `grn` - Goods Received Notes
- `suppliers` - Supplier management
- `returns` - Return management
- `activity_logs` - System audit trail

---

## 📞 Support

For issues or questions:
1. Check the troubleshooting section
2. Review the code comments
3. Check browser console for JavaScript errors
4. Check PHP error logs in XAMPP

---

## 📄 License

This project is proprietary software for TrackIt.

---

## 🎉 Credits

**Developer**: TrackIt Development Team  
**Version**: 1.0.0  
**Last Updated**: November 3, 2025

---

**Congratulations!** Your TrackIt multi-tenant inventory management system is now ready to use! 🚀
