<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration (you'll need to set this up)
$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "your_database";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $delivery_date = htmlspecialchars($_POST['delivery_date']);
    $delivery_time = htmlspecialchars($_POST['delivery_time']);
    $ice_type = htmlspecialchars($_POST['ice_type']);
    $bag_size = htmlspecialchars($_POST['bag_size']);
    $quantity = intval($_POST['quantity']);
    $delivery_address = htmlspecialchars($_POST['delivery_address']);
    $delivery_suburb = htmlspecialchars($_POST['delivery_suburb']);
    $delivery_city = htmlspecialchars($_POST['delivery_city']);
    $delivery_instructions = htmlspecialchars($_POST['delivery_instructions']);
    $customer_name = htmlspecialchars($_POST['customer_name']);
    $customer_email = htmlspecialchars($_POST['customer_email']);
    $customer_phone = htmlspecialchars($_POST['customer_phone']);
    $business_name = htmlspecialchars($_POST['business_name']);
    $order_type = htmlspecialchars($_POST['order_type']);
    
    // Calculate price based on selections
    $base_prices = [
        '3kg' => 25,
        '5kg' => 35,
        '10kg' => 45,
        '20kg' => 85
    ];
    
    $price_multipliers = [
        'crystal' => 1.4,
        'crushed' => 1.25,
        'cube' => 1.0,
        'block' => 0.9
    ];
    
    $base_price = $base_prices[$bag_size] ?? 0;
    $multiplier = $price_multipliers[$ice_type] ?? 1.0;
    $unit_price = round($base_price * $multiplier);
    $total_amount = $unit_price * $quantity;
    
    // Apply contract discounts
    if ($order_type == 'weekly-contract') {
        $total_amount *= 0.85; // 15% discount
    } elseif ($order_type == 'monthly-contract') {
        $total_amount *= 0.80; // 20% discount
    }
    
    $total_amount = round($total_amount);
    
    // Database connection and insertion
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "INSERT INTO ice_orders (
            delivery_date, delivery_time, ice_type, bag_size, quantity, 
            unit_price, total_amount, delivery_address, delivery_suburb, 
            delivery_city, delivery_instructions, customer_name, customer_email, 
            customer_phone, business_name, order_type, order_date
        ) VALUES (
            :delivery_date, :delivery_time, :ice_type, :bag_size, :quantity,
            :unit_price, :total_amount, :delivery_address, :delivery_suburb,
            :delivery_city, :delivery_instructions, :customer_name, :customer_email,
            :customer_phone, :business_name, :order_type, NOW()
        )";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':delivery_date' => $delivery_date,
            ':delivery_time' => $delivery_time,
            ':ice_type' => $ice_type,
            ':bag_size' => $bag_size,
            ':quantity' => $quantity,
            ':unit_price' => $unit_price,
            ':total_amount' => $total_amount,
            ':delivery_address' => $delivery_address,
            ':delivery_suburb' => $delivery_suburb,
            ':delivery_city' => $delivery_city,
            ':delivery_instructions' => $delivery_instructions,
            ':customer_name' => $customer_name,
            ':customer_email' => $customer_email,
            ':customer_phone' => $customer_phone,
            ':business_name' => $business_name,
            ':order_type' => $order_type
        ]);
        
        $order_id = $conn->lastInsertId();
        $success_message = "Order placed successfully! Order ID: #" . $order_id;
        
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
    
    // Email notification (optional)
    $to = "ice@thegreatsuccess.co.za";
    $subject = "New Ice Order - The Great Success";
    $message = "
    New Ice Order Received:
    
    Order Details:
    - Delivery Date: $delivery_date
    - Preferred Time: $delivery_time
    - Ice Type: $ice_type
    - Bag Size: $bag_size
    - Quantity: $quantity
    - Total Amount: R$total_amount
    
    Delivery Information:
    - Address: $delivery_address
    - Suburb: $delivery_suburb
    - City: $delivery_city
    - Instructions: $delivery_instructions
    
    Customer Information:
    - Name: $customer_name
    - Email: $customer_email
    - Phone: $customer_phone
    - Business: $business_name
    - Order Type: $order_type
    ";
    
    $headers = "From: $customer_email";
    
    
    mail($to, $subject, $message, $headers);
}
?>