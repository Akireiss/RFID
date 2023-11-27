<?php
// Include your database connection details here
include_once('../db_conn.php');

// Check if the barcode is provided in the request
if (isset($_GET['barcode'])) {
    // Get the barcode from the request
    $barcode = $_GET['barcode'];

    // Create a MySQLi connection with the correct parameters
    $mysqli = new mysqli('localhost', 'root', '', 'strolley');

    // Check for a successful connection
    if ($mysqli->connect_error) {
        die('Database connection failed: ' . $mysqli->connect_error);
    }

    // Prepare and execute a SQL query to fetch product data based on the barcode
    $sql = "SELECT item_description, quantity, unit_price, weight FROM products WHERE barcode = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $barcode);
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Check if product data was found
    if ($result->num_rows > 0) {
        $productData = $result->fetch_assoc();
        // Return product data as JSON
        header('Content-Type: application/json');
        echo json_encode($productData);
    } else {
        // Return an error message if product data was not found
        echo json_encode(['error' => 'Product not found']);
    }

    // Close the database connection
    $stmt->close();
    $mysqli->close();
} else {
    // Handle the case where the barcode is not provided in the request
    echo json_encode(['error' => 'Barcode not provided']);
}
?>
