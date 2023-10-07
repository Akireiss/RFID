<?php
// Include necessary database connection code
include "db_conn.php"; // You'll need to create this file with your database credentials.

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the RFID card ID from the POST request
$rfidCardId = $_POST['rfid_card_id'];

// Insert the RFID card ID into the database
$sql = "INSERT INTO rfid_cards (rfid_card_id) VALUES ('$rfidCardId')";

if ($conn->query($sql) === TRUE) {
    $response = array("message" => "RFID card ID saved successfully.");
} else {
    $response = array("message" => "Error: " . $sql . "<br>" . $conn->error);
}

// Return a JSON response
header('Content-Type: application/json');
echo json_encode($response);

// Close the database connection
$conn->close();
?>






