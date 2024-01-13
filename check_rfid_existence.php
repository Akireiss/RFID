<?php
// Connect to your database (replace these details with your actual database credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "strolley";

// Create connection
$con = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Retrieve RFID from POST data
$rfid = $_GET['rfid_card_id'];

// SQL query to check if RFID exists in the database
$sql = "SELECT COUNT(*) as count FROM rfid_cards WHERE rfid_card_id = '$rfid'";
$result = $con->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $rfidExists = $row['count'] > 0;

    // Return true or false based on RFID existence
    echo $rfidExists ? 'true' : 'false';
} else {
    echo 'false'; // Return false in case of an error
}

$con->close();
?>
