<?php
// Assuming you have a database connection here

// Replace these credentials with your actual database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "strolley";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$rfid_card_id = $_GET['rfid_card_id'];

// Perform your database query to check if the customer exists
$sql = "SELECT * FROM rfid_cards WHERE rfid_card_id = '$rfid_card_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Customer found
    echo "found";
} else {
    // Customer not found
    echo "not_found";
}

// Close the database connection
$conn->close();
?>
