-- Add 'Return' status to bookings table
-- This allows tracking of bookings that have been returned after delivery

ALTER TABLE `bookings` 
MODIFY COLUMN `status` ENUM(
    'Pending',
    'Confirmed',
    'Processing',
    'Ready',
    'Delivered',
    'Return',
    'Cancelled',
    'Rejected'
) NOT NULL DEFAULT 'Pending';

-- Note: 'Return' status should be used when a delivered item is returned by customer
-- Workflow: Delivered → Return (when product comes back)
