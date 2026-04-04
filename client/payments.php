<?php
/**
 * Calculate rental cost for agricultural equipment
 * 
 * @param float $daily_rate Daily rental rate in BDT
 * @param int $days Number of days rented
 * @param string $duration_type 'daily', 'weekly', 'monthly'
 * @return float Total cost
 */
function calculateRentalCost($daily_rate, $days, $duration_type = 'daily') {
    $base_cost = $daily_rate * $days;
    
    // Apply discounts based on duration
    if ($duration_type == 'weekly' || $days >= 7) {
        // 10% discount for weekly rental
        $discount = 0.10;
        $base_cost = $base_cost * (1 - $discount);
    } elseif ($duration_type == 'monthly' || $days >= 30) {
        // 20% discount for monthly rental
        $discount = 0.20;
        $base_cost = $base_cost * (1 - $discount);
    } elseif ($days >= 60) {
        // 30% discount for seasonal rental
        $discount = 0.30;
        $base_cost = $base_cost * (1 - $discount);
    }
    
    return round($base_cost, 2);
}

/**
 * Calculate total bill with all charges
 * 
 * @param float $daily_rate Daily rate
 * @param int $days Number of days
 * @param bool $include_operator Whether operator is included
 * @param bool $include_delivery Whether delivery is needed
 * @param int $late_days Late return days (if any)
 * @return array Breakdown of charges
 */
function calculateTotalBill($daily_rate, $days, $include_operator = false, $include_delivery = false, $late_days = 0) {
    // Base rental cost
    $rental_cost = calculateRentalCost($daily_rate, $days, 'daily');
    
    // Operator fee (BDT 800 per day)
    $operator_fee = $include_operator ? ($days * 800) : 0;
    
    // Delivery fee
    $delivery_fee = $include_delivery ? 1000 : 0;
    
    // Late return penalty (2x daily rate per extra day)
    $late_penalty = $late_days * ($daily_rate * 2);
    
    // Fuel surcharge (5% of rental cost)
    $fuel_surcharge = $rental_cost * 0.05;
    
    // Total
    $total = $rental_cost + $operator_fee + $delivery_fee + $late_penalty + $fuel_surcharge;
    
    return [
        'rental_cost' => $rental_cost,
        'operator_fee' => $operator_fee,
        'delivery_fee' => $delivery_fee,
        'late_penalty' => $late_penalty,
        'fuel_surcharge' => $fuel_surcharge,
        'total' => $total
    ];
}

// Example usage:
// Tractor daily rate = 1500 BDT, rented for 5 days
$bill = calculateTotalBill(1500, 5, false, true, 0);
echo "Rental Cost: BDT " . number_format($bill['rental_cost'], 2) . "\n";
echo "Delivery Fee: BDT " . number_format($bill['delivery_fee'], 2) . "\n";
echo "Total: BDT " . number_format($bill['total'], 2);
?>