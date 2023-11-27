<?php
session_start();
include 'db_conn.php';

if (isset($_POST['logout'])) {
    $_SESSION = array();
    session_destroy();
    header('Location: index.php');
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "strolley");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

function auditTrail($event_type, $details)
{
    $con = new mysqli('localhost', 'root', '', 'STROLLEY');

    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }

    date_default_timezone_set('Asia/Manila');
    $timestamp = date("Y-m-d H:i:s");
    $id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $user_type = 'Customer'; // Force 'customer' as the default user type for audit logs

    $insertLogQuery = "INSERT INTO audit_log (timestamp, event_type, id, user_type, details) VALUES (?, ?, ?, ?, ?)";
    $auditStmt = $con->prepare($insertLogQuery);
    $auditStmt->bind_param("sssss", $timestamp, $event_type, $id, $user_type, $details);

    if ($auditStmt->execute()) {
        // Audit trail record inserted successfully
    } else {
        // Error inserting audit trail record
    }

    $auditStmt->close();
    $con->close();
}

$rfid_card_id = isset($_GET['rfid_card_id']) ? $_GET['rfid_card_id'] : '';

if (!empty($rfid_card_id)) {
    // Fetch customer information based on the RFID card ID
    $customer_id = getCustomerIdByRfidCard($mysqli, $rfid_card_id);

    if ($customer_id !== null) {
        // Fetch customer name
        $customerInfo = getCustomerInfoById($mysqli, $customer_id);
        $customerName = $customerInfo['first_name'] . ' ' . $customerInfo['last_name'];

        // Display the customer's name on the shop page
        // echo '<div>Welcome, ' . $customerName . '!</div>';
    } else {
        echo '<div>Customer not found.</div>';
    }
} else {
    echo '<div>RFID card ID not provided.</div>';
}

// Function to get customer information by customer ID
function getCustomerInfoById($mysqli, $customer_id)
{
    $sql = "SELECT first_name, last_name FROM customer WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}


function getProductInfoByBarcode($mysqli, $barcode)
{
    $sql = "SELECT * FROM products WHERE barcode = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getCustomerIdByRfidCard($mysqli, $rfidCardId)
{
    $sql = "SELECT customer_id FROM rfid_cards WHERE rfid_card_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $rfidCardId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        return $row['customer_id'];
    } else {
        return null;
    }
}

// Function to deduct total price from total_cost column in products table
function deductTotalPriceFromProducts($mysqli, $totalAmount, $cart)
{
    foreach ($cart as $cartItem) {
        $barcode = $cartItem['barcode'];

        // Deduct total_amount from total_cost column in products table
        $sqlDeductTotalCost = "UPDATE products SET total_cost = GREATEST(total_cost - ?, 0) WHERE barcode = ?";
        $stmtDeductTotalCost = $mysqli->prepare($sqlDeductTotalCost);
        $stmtDeductTotalCost->bind_param("ds", $totalAmount, $barcode);
        $stmtDeductTotalCost->execute();
        $stmtDeductTotalCost->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaction'])) {
    if (isset($_SESSION['cart'])) {
        $rfidCardId = $_POST['rfid_card_id'];
        $customer_id = getCustomerIdByRfidCard($mysqli, $rfidCardId);

        if ($customer_id !== null) {
            $totalWeight = 0;
            $totalAmount = 0;
            $totalQuantityInCart = count($_SESSION['cart']);
            $transactionSuccess = true;

            foreach ($_SESSION['cart'] as $cartItem) {
                $item = $cartItem['item'];

                // Subtract 1 from the product table for each item in the cart
                $sqlUpdateQuantity = "UPDATE products SET quantity = GREATEST(quantity - 1, 0) WHERE barcode = ?";
                $stmtUpdateQuantity = $mysqli->prepare($sqlUpdateQuantity);
                $stmtUpdateQuantity->bind_param("s", $cartItem['barcode']);
                $stmtUpdateQuantity->execute();
                $stmtUpdateQuantity->close();

                // Calculate total weight and amount
                if (is_numeric($cartItem['weight'])) {
                    $totalWeight += $cartItem['weight'];
                } else {
                    // Handle the case where $cartItem['weight'] is not numeric
                    // For example, set a default value or log an error
                    // $totalWeight += 0; // Set a default value of 0
                    // or
                    // log_error("Non-numeric value encountered for weight: " . $cartItem['weight']);
                }

                if (is_numeric($cartItem['unit_price'])) {
                    $totalAmount += $cartItem['unit_price'];
                } else {
                    // Handle the case where $cartItem['unit_price'] is not numeric
                    // For example, set a default value or log an error
                    // $totalAmount += 0; // Set a default value of 0
                    // or
                    // log_error("Non-numeric value encountered for unit_price: " . $cartItem['unit_price']);
                }

                // Insert transaction record
                $sqlInsertTransaction = "INSERT INTO transaction (item, quantity, customer_id, t_weight, t_amount) VALUES (?, ?, ?, ?, ?)";
                $stmtInsertTransaction = $mysqli->prepare($sqlInsertTransaction);
                $quantityToUpdate = max(1, $cartItem['quantity']); // Ensure the quantity to update is at least 1
                $stmtInsertTransaction->bind_param("sdddi", $item, $quantityToUpdate, $customer_id, $cartItem['weight'], $cartItem['unit_price']);

                if (!$stmtInsertTransaction->execute()) {
                    $event_type = "Transaction Failed";
                    $logDetails = "Transaction failed for customer ID: $customer_id";
                    auditTrail($event_type, $logDetails);

                    $alertMessage = "Transaction failed. Please try again.";
                    $transactionSuccess = false;
                    break;
                }

                $stmtInsertTransaction->close();
            }

            if ($transactionSuccess) {
                $sql = "SELECT balance FROM rfid_cards WHERE customer_id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("i", $customer_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row) {
                    $currentBalance = $row['balance'];
                    $newBalance = is_numeric($currentBalance) ? max(0, $currentBalance - $totalAmount) : 0;

                    // Additional check for non-numeric values
                    if (!is_numeric($currentBalance)) {
                        $event_type = "Non-Numeric Balance";
                        $logDetails = "Non-numeric balance encountered for customer ID: $customer_id. Balance: $currentBalance";
                        auditTrail($event_type, $logDetails);
                    }

                    $sql = "UPDATE rfid_cards SET balance = ? WHERE customer_id = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("di", $newBalance, $customer_id);

                    if ($stmt->execute()) {
                        // Deduct total price from total_cost column in products table
                        deductTotalPriceFromProducts($mysqli, $totalAmount, $_SESSION['cart']);

                        $_SESSION['cart'] = array();
                        $event_type = "Transaction Success";
                        $logDetails = "Transaction successful for customer ID: $customer_id. Amount: $totalAmount";
                        auditTrail($event_type, $logDetails);

                        // echo '<div class="col-lg-12 alert alert-success" role="alert">Transaction submitted successfully!</div>';
                    } else {
                        $event_type = "Balance Update Failed";
                        $logDetails = "Failed to update balance for customer ID: $customer_id";
                        auditTrail($event_type, $logDetails);

                        echo '<div class="col-lg-12 alert alert-danger" role="alert">Failed to update the customer\'s balance.</div>';
                    }
                } else {
                    $event_type = "Customer Not Found";
                    $logDetails = "Customer not found in the database. Customer ID: $customer_id";
                    auditTrail($event_type, $logDetails);

                    echo '<div class="col-lg-12 alert alert-danger" role="alert">Customer not found in the database.</div>';
                }
            } else {
                // Transaction failed
                // Alert message is already set inside the loop
            }
        } else {
            $event_type = "RFID Card Not Found";
            $logDetails = "RFID card not found in the database. RFID Card ID: $rfidCardId";
            auditTrail($event_type, $logDetails);

            echo '<div class="col-lg-12 alert alert-danger" role="alert">RFID card not found in the database.</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barcode'])) {
    $barcode = $_POST['barcode'];
    $productInfo = getProductInfoByBarcode($mysqli, $barcode);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    $productInfo = getProductInfoByBarcode($mysqli, $_POST['barcode']);
    $_SESSION['cart'][] = $productInfo;

    $event_type = "Add to Cart";
    $logDetails = "Item added to cart: {$productInfo['item']}";
    auditTrail($event_type, $logDetails);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_cart'])) {
    $removeBarcode = $_POST['remove_from_cart'];
    foreach ($_SESSION['cart'] as $key => $cartItem) {
        if ($cartItem['barcode'] === $removeBarcode) {
            $event_type = "Remove from Cart";
            $logDetails = "Item removed from cart: {$cartItem['item']}";
            auditTrail($event_type, $logDetails);

            unset($_SESSION['cart'][$key]);
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    $event_type = "Clear Cart";
    $logDetails = "Cart cleared";
    auditTrail($event_type, $logDetails);

    unset($_SESSION['cart']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    // Clear all data and perform logout actions
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: index.php"); // Redirect to the index.php page
    exit();
}

include 'includes/header.php';
include 'includes/footer.php';
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Page Title</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Arial', sans-serif;
        }

        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            padding: 30px;
            margin-top: 50px;
        }

        .card-header {
            font-weight: bold;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #218838;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }

        .input-group-btn {
            display: flex;
            align-items: flex-end;
        }
    </style>

<script>
        function cancelBarcode() {
            document.getElementById('barcode').value = '';
            document.getElementById('productInfo').innerHTML = '';
        }
    </script>
</head>

<body>
    <div class="container">
    <span class="d-none d-md-block ps-2" style="font-weight: bold;"><?php echo $customerName; ?></span>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">Billing Information</h6>
                    </div>
                    <div class="card-body">
                        <form method="post" id="barcodeForm">
                            <div class="form-group">
                                <label for="barcode" class="form-label">Barcode:</label>
                                <div class="input-group">
                                    <input type="text" name="barcode" id="barcode" class="form-control" required value="<?php echo isset($_POST['barcode']) ? $_POST['barcode'] : ''; ?>">
                                    <div class="input-group-btn">
                                        <button type="button" class="btn btn-secondary" onclick="cancelBarcode()">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div id="productInfo" class="mt-3">
                            <?php if (!empty($productInfo)) : ?>
                                <ul class="list-group">
                                    <li class="list-group-item border-0 d-flex p-4 mb-2 bg-gray-100 border-radius-lg">
                                        <div class="d-flex flex-column">
                                            <span class="mb-2 text-xs">Item Description: <span class="text-dark font-weight-bold ms-sm-2"><?php echo $productInfo['item']; ?></span></span>
                                            <span class="mb-2 text-xs">Quantity: <span class="text-dark font-weight-bold ms-sm-2"><?php echo $productInfo['quantity']; ?></span></span>
                                            <span class="mb-2 text-xs">Unit Price: <span class="text-dark ms-sm-2 font-weight-bold"><?php echo $productInfo['unit_price']; ?></span></span>
                                            <span class="text-xs">Weight: <span class="text-dark ms-sm-2 font-weight-bold"><?php echo $productInfo['weight']; ?></span></span>
                                        </div>
                                    </li>
                                </ul>
                                <form method="post">
                                    <input type="hidden" name="barcode" value="<?php echo $productInfo['barcode']; ?>">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header pb-0 px-3">
                        <h6 class="mb-0">General Bill</h6>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <!-- <th>Quantity</th> -->
                                    <th>Weight</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!isset($_SESSION['cart'])) {
                                    $_SESSION['cart'] = array();
                                }
                                $totalWeight = 0;
                                $totalPrice = 0;

                                foreach ($_SESSION['cart'] as $cartItem) {
                                    // Cast $cartItem['weight'] to float
                                    $weight = floatval($cartItem['weight']);

                                    $totalWeight += $weight;
                                    $totalPrice += $cartItem['unit_price'];
                                    ?>
                                    <tr>
                                        <td><?php echo $cartItem['item']; ?></td>
                                        <!-- <td><?php echo $cartItem['quantity']; ?></td> -->
                                        <td><?php echo $weight; ?></td>
                                        <td><?php echo $cartItem['unit_price']; ?></td>
                                        <td>
                                            <form method="post">
                                                <input type="hidden" name="remove_from_cart" value="<?php echo $cartItem['barcode']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">X</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <td colspan="2"></td>
                                    <td>Total Weight: <?php echo $totalWeight; ?></td>
                                    <td>Total Price: <?php echo $totalPrice; ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <form method="post">
                            <button type="submit" name="clear_cart" class="btn btn-danger">Clear Cart</button>
                        </form>
                        <form method="post" class="mt-3">
    <div class="form-group">
        <input type="text" name="rfid_card_id" required placeholder="RFID Card ID" class="form-control">
    </div>
    <button type="submit" name="submit_transaction" class="btn btn-success">Submit Transaction</button>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaction'])) {
        if ($transactionSuccess) {
            // Check if there was no insufficient balance
            if (!isset($alertMessage)) {
                echo '<div class="alert alert-success mt-3" role="alert">Transaction submitted successfully!</div>';
            }
        }
    }
    ?>
</form>
                    </div>
                </div>
            </div>
        </div>
        <form method="post">
            <button type="submit" name="logout" class="btn btn-danger">Logout</button>
        </form>
    </div>

    <script>
        const barcodeInput = document.getElementById('barcode');
        const barcodeForm = document.getElementById('barcodeForm');
        const productInfo = document.getElementById('productInfo');

        barcodeInput.addEventListener('input', function() {
            const barcodeValue = barcodeInput.value.trim();
            if (barcodeValue === '') {
                productInfo.innerHTML = '';
            } else {
                barcodeForm.submit();
            }
        });
    </script>
</body>

</html>