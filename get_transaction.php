<?php
include 'db_conn.php';

$rfid_card_id = $_GET['rfid_card_id'];
$transactions = array();

$sql = "SELECT t.t_amount AS amount, t.created_at AS timestamp 
        FROM transaction t
        LEFT JOIN rfid_cards r ON t.customer_id = r.customer_id
        WHERE r.rfid_card_id = ?
        ORDER BY t.created_at DESC
        LIMIT 10";

$stmt = $con->prepare($sql);
$stmt->bind_param("s", $rfid_card_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();

    $timestampTotalAmounts = array(); // Array to store total amounts for each timestamp

    while ($row = $result->fetch_assoc()) {
        $timestamp = date("Y-m-d H:i:s", strtotime($row['timestamp']));
        $timestampTotalAmounts[$timestamp] = isset($timestampTotalAmounts[$timestamp])
            ? $timestampTotalAmounts[$timestamp] + $row['amount']
            : $row['amount'];
    }

    // Process the timestampTotalAmounts array to create entries for each timestamp with total amounts
    foreach ($timestampTotalAmounts as $timestamp => $totalAmount) {
        $transactions[] = array(
            'timestamp' => $timestamp,
            'amount' => number_format($totalAmount), // Display total amount
            'transaction_type' => 'Purchase'
        );
    }
} else {
    echo "Database error: " . mysqli_error($con);
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode($transactions);
?>
