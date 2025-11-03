<?php
/**
 * One-time script to update customer statistics for existing data
 * Run this once to populate total_orders and total_spent for all customers
 */

require_once '../../config/config.php';

// Check if user is logged in and is Owner or Admin
requireRole(['Owner', 'Admin In-charge']);

header('Content-Type: application/json');

try {
    // Update all customers' statistics
    $query = "
        UPDATE customers c
        SET 
            total_orders = (
                SELECT COUNT(*) 
                FROM bookings b 
                WHERE b.customer_id = c.id
            ),
            total_spent = (
                SELECT COALESCE(SUM(total_amount), 0)
                FROM bookings b 
                WHERE b.customer_id = c.id 
                AND b.status = 'Delivered'
            )
    ";
    
    if ($conn->query($query)) {
        // Get count of updated customers
        $affected = $conn->affected_rows;
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully updated statistics for $affected customers",
            'updated_count' => $affected
        ]);
    } else {
        throw new Exception('Failed to update customer statistics');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
