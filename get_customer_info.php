<?php
include 'db_conn.php'; // Include your database connection script

$rfid_card_id = $_GET['rfid_card_id'];
$customerName = 'Customer Not Found'; // Default customer name
$balance = '0.00'; // Default balance value with decimal

// Query the database to fetch the customer's name and balance by joining the rfid_cards and customer tables
$sql = "SELECT c.first_name, c.last_name, COALESCE(r.balance, 0) as balance FROM rfid_cards r
        LEFT JOIN customer c ON r.customer_id = c.id
        WHERE r.rfid_card_id = ?";

$stmt = $con->prepare($sql);
$stmt->bind_param("s", $rfid_card_id); // Bind the parameter to rfid_card_id

if ($stmt->execute()) {
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($firstName, $lastName, $rawBalance); // Bind balance
        $stmt->fetch();
        $customerName = $firstName . ' ' . $lastName;

        // Format balance with 2 decimal places and add ' Php' suffix
        $balance = 'Php ' . number_format($rawBalance, 2);
    }
} else {
    // Check for database errors
    echo "Database error: " . mysqli_error($con);
}

$stmt->close();

// Create an array with the customer's name and balance
$response = array(
    'name' => $customerName,
    'balance' => $balance // Include the balance in the response
);

// Send the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
