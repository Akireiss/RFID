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

function getBalanceFromDatabase($mysqli, $rfid_card_id)
{
    // Implement your logic to fetch the balance from the database based on $rfid_card_id
    // Replace the following query with your actual database query
    $query = "SELECT balance FROM rfid_cards WHERE rfid_card_id = ?";
    $stmt = $mysqli->prepare($query);

    // Bind the RFID card ID parameter
    $stmt->bind_param("s", $rfid_card_id);

    // Execute the query
    $stmt->execute();

    // Bind the result variable
    $stmt->bind_result($balance);

    // Fetch the result
    $stmt->fetch();

    // Close the statement
    $stmt->close();

    return $balance;
}

// Rest of your existing code...

if (!empty($rfid_card_id)) {
    // Fetch customer information based on the RFID card ID
    $customer_id = getCustomerIdByRfidCard($mysqli, $rfid_card_id);

    if ($customer_id !== null) {
        // Fetch customer name
        $customerInfo = getCustomerInfoById($mysqli, $customer_id);
        $customerName = $customerInfo['first_name'] . ' ' . $customerInfo['last_name'];

        // Fetch the balance from the database based on $rfid_card_id
        $balance = getBalanceFromDatabase($mysqli, $rfid_card_id);

        // Display the customer's name and balance on the shop page
        // echo '<div>Welcome, ' . $customerName . '! Your Balance: $' . number_format($balance, 2) . '</div>';
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
        $quantityToUpdate = $cartItem['quantity'];

        // Calculate the deduction based on quantity and unit_price
        $deduction = $cartItem['unit_price'] * $quantityToUpdate;

        // Deduct the calculated amount from total_cost column in products table
        $sqlDeductTotalCost = "UPDATE products SET total_cost = GREATEST(total_cost - ?, 0) WHERE barcode = ?";
        $stmtDeductTotalCost = $mysqli->prepare($sqlDeductTotalCost);
        $stmtDeductTotalCost->bind_param("ds", $deduction, $barcode);
        $stmtDeductTotalCost->execute();
        $stmtDeductTotalCost->close();
    }
}

$rfidCardId = isset($_GET['rfid_card_id']) ? $_GET['rfid_card_id'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaction'])) {
    if (isset($_SESSION['cart'])) {
        $rfidCardId = $_GET['rfid_card_id'];
        $customer_id = getCustomerIdByRfidCard($mysqli, $rfidCardId);

        if ($customer_id !== null) {
            $totalWeight = 0;
            $totalAmount = 0;
            $totalQuantityInCart = count($_SESSION['cart']);
            $transactionSuccess = true;

            foreach ($_SESSION['cart'] as $cartItem) {
                $item = $cartItem['item'];
                $quantityToUpdate = $cartItem['quantity']; // Use the exact quantity they inputted

                // Subtract quantity from the product table for each item in the cart
                $sqlUpdateQuantity = "UPDATE products SET quantity = GREATEST(quantity - ?, 0) WHERE barcode = ?";
                $stmtUpdateQuantity = $mysqli->prepare($sqlUpdateQuantity);
                $stmtUpdateQuantity->bind_param("ds", $quantityToUpdate, $cartItem['barcode']);
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
                // ... (existing code)

                $sql = "SELECT balance, rfid_card_id FROM rfid_cards WHERE customer_id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("i", $customer_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row) {
                    $currentBalance = $row['balance'];
                    $rfidCardId = $row['rfid_card_id'];

                    // Calculate total price dynamically from the cart items
                    $totalPrice = 0;
                    foreach ($_SESSION['cart'] as $cartItem) {
                        $inputQuantity = $cartItem['quantity'];
                        $itemTotalPrice = $cartItem['unit_price'] * $inputQuantity;
                        $totalPrice += $itemTotalPrice;
                    }

                    // Deduct total price from the balance
                    $newBalance = is_numeric($currentBalance) ? max(0, $currentBalance - $totalPrice) : 0;

                    // Additional check for non-numeric values
                    if (!is_numeric($currentBalance)) {
                        $event_type = "Non-Numeric Balance";
                        $logDetails = "Non-numeric balance encountered for customer ID: $customer_id. Balance: $currentBalance";
                        auditTrail($event_type, $logDetails);
                    }

                    // Update the balance in the rfid_cards table
                    $sql = "UPDATE rfid_cards SET balance = ? WHERE customer_id = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("di", $newBalance, $customer_id);

                    if ($stmt->execute()) {
                        // Deduct total price from total_cost column in products table
                        deductTotalPriceFromProducts($mysqli, $totalPrice, $_SESSION['cart']);

                        $_SESSION['cart'] = array();
                        $event_type = "Transaction Success";
                        $logDetails = "Transaction successful for customer ID: $customer_id. Amount: $totalPrice";
                        auditTrail($event_type, $logDetails);

                        // echo '<div class="col-lg-12 alert alert-success" role="alert">Transaction submitted successfully!</div>';
                    } else {
                        $event_type = "Balance Update Failed";
                        $logDetails = "Failed to update balance for customer ID: $customer_id";
                        auditTrail($event_type, $logDetails);

                        echo '<div class="col-lg-12 alert alert-danger" role="alert">Failed to update the customer\'s balance.</div>';
                    }
                } else {
                    // Handle the case where customer not found
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

    // Check if quantity is set in the post data, otherwise default to 1
    $inputQuantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    // Add the item to the cart along with the inputted quantity
    $_SESSION['cart'][] = [
        'barcode' => $productInfo['barcode'],
        'item' => $productInfo['item'],
        'quantity' => $inputQuantity,
        'unit_price' => $productInfo['unit_price'],
        'weight' => $productInfo['weight'],
        // ... (other item details)
    ];

    $event_type = "Add to Cart";
    $logDetails = "Item added to cart: {$productInfo['item']} (Quantity: $inputQuantity)";
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

include 'includes/shopheader.php';
include 'includes/header.php';
include 'includes/footer.php';
?>

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
                            <?php if (!empty($productInfo) && $productInfo['quantity'] > 0) : ?>
                                <ul class="list-group">
                                    <li class="list-group-item border-0 d-flex p-4 mb-2 bg-gray-100 border-radius-lg" data-barcode="<?php echo $productInfo['barcode']; ?>">
                                        <div class="d-flex flex-column">
                                            <span class="mb-2 text-xs">Item Description: <span class="text-dark font-weight-bold ms-sm-2"><?php echo $productInfo['item']; ?></span></span>
                                            <span class="mb-2 text-xs">Available Quantity: <span id="availableQuantity" class="text-dark font-weight-bold ms-sm-2"><?php echo $productInfo['quantity']; ?></span></span>
                                            <span class="mb-2 text-xs">Unit Price: <span class="text-dark ms-sm-2 font-weight-bold"><?php echo $productInfo['unit_price']; ?></span></span>
                                            <span class="text-xs">Weight: <span class="text-dark ms-sm-2 font-weight-bold"><?php echo $productInfo['weight']; ?></span></span>
                                        </div>
                                    </li>
                                </ul>
                                <div class="mb-2 text-xs">
                                    <label for="quantity">How Many Quantity:</label>
                                    <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" max="<?php echo $productInfo['quantity']; ?>" onchange="updateDisplayedQuantity()">
                                </div>
                                <form method="post" onsubmit="return validateAndSubmit()">
                                    <input type="hidden" name="barcode" value="<?php echo $productInfo['barcode']; ?>">
                                    <input type="hidden" name="quantity" id="inputQuantity" value="1">

                                    <button type="submit" name="add_to_cart" id="addToCartButton" class="btn btn-primary">Add to Cart</button>
                                </form>
                            <?php elseif (!empty($productInfo) && $productInfo['quantity'] <= 0) : ?>
                                <p class="text-danger">Sorry, this product is out of stock.</p>
                            <?php endif; ?>
                        </div>
                        <script>
                            // Update the displayed quantity and set the input quantity for form submission
                            function updateDisplayedQuantity() {
                                var selectedQuantity = parseInt(document.getElementById('quantity').value);
                                var availableQuantity = parseInt(document.getElementById('availableQuantity').innerText);
                                var addToCartButton = document.getElementById('addToCartButton');

                                if (selectedQuantity > availableQuantity) {
                                    alert('Selected quantity exceeds available quantity. Quantity set to available quantity.');
                                    document.getElementById('quantity').value = availableQuantity; // Set quantity to available quantity
                                }

                                // Set the input quantity for form submission
                                document.getElementById('inputQuantity').value = document.getElementById('quantity').value;

                                // Update the displayed quantity in the table
                                document.getElementById('displayedQuantity').innerText = document.getElementById('quantity').value;
                            }

                            // Validate the quantity before form submission
                            function validateAndSubmit() {
                                var selectedQuantity = parseInt(document.getElementById('quantity').value);
                                var availableQuantity = parseInt(document.getElementById('availableQuantity').innerText);

                                if (selectedQuantity > availableQuantity) {
                                    alert('Entered quantity exceeds available quantity. Please enter a valid quantity.');
                                    return false; // Prevent form submission
                                }

                                // Additional validation logic can be added here if needed

                                return true; // Allow form submission
                            }
                        </script>

                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <span class="d-none d-md-block ps-2" style="font-weight: bold;"><?php echo "Your Balance is: ₱" . $balance . ".00"; ?>
                </span>
                <div class="card">
                    <div class="card-header pb-0 px-3">
                        <h6 class="mb-0">General Bill</h6>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
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
                                $productCount = 0; // Counter for the number of products in the cart

                                foreach ($_SESSION['cart'] as $cartItem) {
                                    $weight = floatval($cartItem['weight']);
                                    $totalWeight += $weight;
                                    $productCount++;

                                    $inputQuantity = $cartItem['quantity'];
                                    $itemTotalPrice = $cartItem['unit_price'] * $inputQuantity;

                                    if ($inputQuantity !== '') {
                                ?>
                                        <tr>
                                            <td><?php echo $cartItem['item']; ?></td>
                                            <td>
                                                <input type="number" name="quantity_edit[]" value="<?php echo $inputQuantity; ?>" min="1" class="form-control" oninput="validateAndUpdateQuantity(this, <?php echo $productInfo['quantity']; ?>, '<?php echo $cartItem['barcode']; ?>', '<?php echo $cartItem['weight']; ?>', '<?php echo $cartItem['unit_price']; ?>')">
                                            </td>

                                            <script>
                                                function validateAndUpdateQuantity(inputElement, availableQuantity, barcode, weight, unitPrice) {
                                                    var enteredQuantity = parseInt(inputElement.value);

                                                    if (enteredQuantity > availableQuantity) {
                                                        alert('Entered quantity exceeds available quantity. Please enter a valid quantity.');
                                                        inputElement.value = availableQuantity; // Reset the value to the available quantity
                                                    }

                                                    // Add your logic to update item details (if needed)
                                                    updateItemDetails(inputElement, barcode, weight, unitPrice);
                                                }
                                            </script>
                                            <td id="itemWeight_<?php echo $cartItem['barcode']; ?>"><?php echo $weight; ?></td>
                                            <td id="itemTotalPrice_<?php echo $cartItem['barcode']; ?>"><?php echo number_format($itemTotalPrice, 2); ?></td>
                                            <td>
                                                <form method="post">
                                                    <input type="hidden" name="remove_from_cart" value="<?php echo $cartItem['barcode']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">X</button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php
                                        $totalPrice += $itemTotalPrice;
                                    }
                                }
                                ?>

                                <tr>
                                    <td colspan="2"></td>
                                    <td>Total Weight: <span id="grandTotalWeight"><?php echo number_format($totalWeight, 2); ?></span></td>
                                    <td>Total Price: <span id="grandTotalPrice"><?php echo number_format($totalPrice, 2); ?></span></td>
                                </tr>

                            </tbody>
                        </table>
                        <div id="removeProductsMessage" class="alert alert-danger mt-3" style="display: none;">
                            Remove some Products so that you can continue with the transaction.
                        </div>

                        <!-- Display the number of products added to the cart -->
                        <p>Total Items in Cart: <strong><?php echo $productCount; ?></strong></p>

                        <form method="post">
                            <button type="submit" name="clear_cart" class="btn btn-danger">Clear Cart</button>
                        </form>

                        <form method="post" class="mt-3" onsubmit="return checkBalance()">
                            <div class="form-group">
                                <input type="text" name="rfid_card_id" required class="form-control" value="<?php echo $rfidCardId; ?>" disabled>
                            </div>
                            <?php
                            if ($productCount > 0) {
                            ?>
                                <button type="submit" name="submit_transaction" class="btn btn-success">Submit Transaction</button>
                            <?php
                            } else {
                            ?>
                                <button type="button" class="btn btn-success" disabled>Submit Transaction</button>
                                <p class="text-danger mt-2">Cannot submit transaction. Cart is empty.</p>
                            <?php
                            }
                            ?>
                            <?php
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaction'])) {
                                if ($transactionSuccess) {
                                    // Check if there was no insufficient balance
                                    if (!isset($alertMessage)) {
                                        echo '<div class="alert alert-success mt-3" role="alert">Transaction submitted successfully!</div>';
                                        echo '<meta http-equiv="refresh" content="">';
                                    }
                                }
                            }
                            ?>
                        </form>

                    </div>
                    <script>
                        function updateItemDetails(input, barcode, initialWeight, unitPrice) {
                            var quantity = parseInt(input.value);
                            var updatedWeight = quantity * parseFloat(initialWeight);
                            var itemTotalPrice = quantity * parseFloat(unitPrice);

                            document.getElementById('itemWeight_' + barcode).innerText = updatedWeight.toFixed(2);
                            document.getElementById('itemTotalPrice_' + barcode).innerText = itemTotalPrice.toFixed(2);

                            updateGrandTotalWeight();
                            updateGrandTotalPrice();
                        }

                        function updateGrandTotalWeight() {
                            var grandTotalWeight = 0;
                            var itemWeights = document.querySelectorAll('[id^="itemWeight_"]');

                            itemWeights.forEach(function(itemWeight) {
                                grandTotalWeight += parseFloat(itemWeight.innerText);
                            });

                            document.getElementById('grandTotalWeight').innerText = grandTotalWeight.toFixed(2);
                        }

                        function updateGrandTotalPrice() {
                            var grandTotalPrice = 0;
                            var itemTotalPrices = document.querySelectorAll('[id^="itemTotalPrice_"]');

                            itemTotalPrices.forEach(function(itemTotalPrice) {
                                grandTotalPrice += parseFloat(itemTotalPrice.innerText);
                            });

                            document.getElementById('grandTotalPrice').innerText = grandTotalPrice.toFixed(2);
                        }
                    </script>

                    <script>
                        function setQuantity() {
                            var inputQuantity = document.getElementById('quantity').value;
                            document.getElementById('inputQuantity').value = inputQuantity;
                        }
                    </script>
                </div>
            </div>
            <div class="modal fade" id="insufficientBalanceModal" tabindex="-1" role="dialog" aria-labelledby="insufficientBalanceModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="insufficientBalanceModalLabel">Insufficient Balance</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Your balance is insufficient for this transaction. Do you want to continue?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal()">No, close</button>
                            <button type="button" class="btn btn-primary" onclick="removeProducts()">Yes, remove products</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <form method="post">
            <button type="submit" name="logout" class="btn btn-danger">Logout</button>
        </form>
    </div>

    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->

    <script>
        function checkBalance() {
            var balance = <?php echo $balance; ?>;
            var totalPrice = <?php echo $totalPrice; ?>;

            if (totalPrice > balance) {
                $('#insufficientBalanceModal').modal('show');
                return false;
            }

            return true;
        }

        function removeProducts() {
            // Add logic here to remove products from the list or perform any other action
            // For demonstration purposes, let's assume you set a flag when products are removed
            var productsRemoved = true;

            // Display the "Remove Products" message
            $('#removeProductsMessage').show();

            // Hide the modal
            $('#insufficientBalanceModal').modal('hide');

            // Check if balance is now sufficient
            var balance = <?php echo $balance; ?>;
            var totalPrice = <?php echo $totalPrice; ?>;

            if (totalPrice <= balance && productsRemoved) {
                // If the balance is now sufficient and products were removed, hide the message
                $('#removeProductsMessage').hide();
            }
        }

        // Close the modal if the user clicks "No" or the close button
        $('#insufficientBalanceModal').on('hidden.bs.modal', function() {
            // Optionally, you can perform additional actions here when the modal is closed
        });

        function closeModal() {
            $('#insufficientBalanceModal').modal('hide');
        }
    </script>


    <!-- Add the following script to your HTML file -->
    <script>
        const barcodeInput = document.getElementById('barcode');
        const barcodeForm = document.getElementById('barcodeForm');
        const productInfo = document.getElementById('productInfo');
        const addToCartContainer = document.getElementById('addToCartContainer');

        barcodeInput.addEventListener('input', function() {
            const barcodeValue = barcodeInput.value.trim();
            if (barcodeValue === '') {
                productInfo.innerHTML = '';
                addToCartContainer.innerHTML = '';
            } else {
                fetchProductInfo(barcodeValue);
                fetchAddToCartButton(barcodeValue);
            }
        });

        function fetchProductInfo(barcode) {
            // Perform AJAX request to fetch product info
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax_handler.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        productInfo.innerHTML = xhr.responseText;
                    } else {
                        console.error('Error fetching product info. Status:', xhr.status);
                    }
                }
            };
            xhr.send('barcode=' + encodeURIComponent(barcode));
        }

        function fetchAddToCartButton(barcode) {
            // Perform AJAX request to fetch Add to Cart button
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax_handler.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        addToCartContainer.innerHTML = xhr.responseText;
                    } else {
                        console.error('Error fetching Add to Cart button. Status:', xhr.status);
                    }
                }
            };
            xhr.send('add_to_cart=' + encodeURIComponent(barcode));
        }
    </script>






</body>

</html>