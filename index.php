<?php
// Include your database connection here
include 'db_conn.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rfid_card_id']) && isset($_POST['first_name']) && isset($_POST['last_name'])) {
    $rfid_card_id = $_POST['rfid_card_id'];
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    // Prepare the SQL query
    $query = "SELECT c.first_name, c.last_name
              FROM customer c
              INNER JOIN rfid_cards r ON c.id = r.customer_id
              WHERE r.rfid_card_id = ?";

    // Use a prepared statement to prevent SQL injection
    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $rfid_card_id);
   
    // Execute the query
    if ($stmt->execute()) {
        // Bind the result to variables
        $stmt->bind_result($firstName, $lastName);

        // Fetch the data
        if ($stmt->fetch()) {
            // Data found, assign it to variables
            $stmt->close();
        } else {
            // No customer found
            $firstName = 'No';
            $lastName = 'Customer Found';
        }
    } else {
        // Error executing the query
        $firstName = 'Error';
        $lastName = 'Fetching Data';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RFID Card Reader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
    <style>
        /* Reset some default styles */
        body {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Style the container */
        .container {
            text-align: center;
            margin: 30px auto;
            max-width: 900px;
            background-color: #666050;
            padding: 100px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
        }

        /* Style the header */
        h1 {
            font-size: 78px;
            margin-bottom: 10px;
        }

        /* Style the RFID input field */
        .rfid-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        /* Style the scan button */
        .scan-button {
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }

        /* Style the result div */
        .result {
            margin-top: 10px;
            font-size: 16px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to DMPC</h1>
        <p class="m-3 fw-bold">Scan your RFID card:</p>
        <input type="text" id="manualRFIDInput" class="rfid-input" placeholder="Enter RFID card ID">
        <div id="btn" class="mt-3" style="display: none; align-items: center; justify-content; grid-gap: 5px;">
            <button class="btn btn-md btn-warning" id="checkBalanceButton">Check Balance</button>
            <button class="btn btn-md btn-info" id="shopButton">Shop</button>
            <button class="btn btn-md btn-danger" id="cancelButton">Cancel</button>
        </div>
    </div>

    <div class="modal fade" id="checkBalanceModal" tabindex="-1" aria-labelledby="checkBalanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkBalanceModalLabel">Check Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="checkBalanceModalBody">
                    <div class="form-group">
                        <label for="customerName">Hi</label>
                        <input type="text" class="form-control" id="customer_data" name="customer_data" value="<?php echo (!empty($firstName) && !empty($lastName)) ? $firstName . ' ' . $lastName : 'No customer found'; ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="balanceAmount">Enter Amount</label>
                        <input type="text" class="form-control" id="balanceAmount" placeholder="Amount">
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveBalanceButton">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Shop -->
    <div class="modal fade" id="shopModal" tabindex="-1" aria-labelledby="shopModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shopModalLabel">Shop</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Add content for the Shop modal here -->
                    This is the Shop modal.
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function scanRFID() {
                var manualRFIDId = document.getElementById('manualRFIDInput').value;
                var btn = document.getElementById('btn');
                if (manualRFIDId.trim() !== "") {
                    // Show the buttons
                    btn.style.display = 'block';
                } else {
                    // Hide the buttons
                    btn.style.display = 'none';
                }
            }

            // Attach the scanRFID function to an input event
            document.getElementById('manualRFIDInput').addEventListener('input', scanRFID);

            // Clear the RFID input and hide the buttons when the "Cancel" button is clicked
            document.getElementById('cancelButton').addEventListener('click', function() {
                document.getElementById('manualRFIDInput').value = '';
                var btn = document.getElementById('btn');
                btn.style.display = 'none';
            });

            // Show the Check Balance modal when the "Check Balance" button is clicked
            document.getElementById('checkBalanceButton').addEventListener('click', function() {
                // Get the customer data input field in the modal
                var customerDataInput = document.getElementById('customer_data');

                // Update the value of the input field with the fetched first name and last name
                customerDataInput.value = "<?php echo (!empty($firstName) && !empty($lastName)) ? $firstName . ' ' . $lastName : 'No customer found'; ?>";

                // Show the modal
                var checkBalanceModal = new bootstrap.Modal(document.getElementById('checkBalanceModal'));
                checkBalanceModal.show();
            });

            // Handle saving balance
            document.getElementById('saveBalanceButton').addEventListener('click', function() {
                // Get the entered balance amount
                var balanceAmount = document.getElementById('balanceAmount').value;

                // TODO: Implement the logic to save the balance and perform any necessary actions here
                // You can make an AJAX request to your server to save the balance.
                // Example:
                // $.ajax({
                //     type: 'POST',
                //     url: 'save_balance.php',
                //     data: { balance: balanceAmount },
                //     success: function(response) {
                //         // Handle the response here
                //         console.log(response);
                //     },
                //     error: function(error) {
                //         // Handle the error here
                //         console.error(error);
                //     }
                // });
            });

            // Show the Shop modal when the "Shop" button is clicked
            document.getElementById('shopButton').addEventListener('click', function() {
                var shopModal = new bootstrap.Modal(document.getElementById('shopModal'));
                shopModal.show();
            });
        });
    </script>
</body>
</html>
