-- Create Store In-charge User
-- Run this if you don't have a Store In-charge user yet

-- Check existing users first
SELECT id, name, email, role, owner_id FROM users;

-- Create Store In-charge user (Password: password)
INSERT INTO users (name, email, password, role, owner_id, status, created_at)
VALUES (
  'Store Manager',
  'store@trackit.com',
  '$2y$10$JooP0F7doRrn5kXtJYQf9OPPkJmcNePu9ZYxG4YKRY4Kgm8tNsLta',
  'Store In-charge',
  1, -- Owner ID (change if needed)
  'Active',
  NOW()
);

-- Verify the user was created
SELECT id, name, email, role, owner_id, status FROM users WHERE role = 'Store In-charge';

-- Login credentials:
-- Email: store@trackit.com
-- Password: password
