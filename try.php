    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Customer Information</title>

        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }

            h1 {
                text-align: center;
                background-color: #007BFF;
                color: white;
                padding: 20px;
                margin: 0;
            }

            form {
                text-align: center;
                margin: 20px auto;
                max-width: 300px;
                padding: 20px;
                background-color: white;
                border-radius: 5px;
                box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            }

            label {
                display: block;
                margin-bottom: 10px;
            }

            input[type="text"] {
                width: 90%;
                padding: 10px;
                margin-bottom: 20px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }

            input[type="submit"] {
                background-color: #007BFF;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }

            div {
                text-align: center;
                margin: 20px auto;
                max-width: 300px;
                background-color: white;
                border-radius: 5px;
                box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
                padding: 20px;
            }
            .hidden {
                display: none;
            }
        </style>
    </head>
    <body>
        <h1>Customer Information</h1>
        <form action="" method="GET" id="customerForm">
            <label for="rfid_card_id">Enter RFID Card ID:</label>
            <input type="text" id="rfid_card_id" name="rfid_card_id" oninput="toggleButtons()">
            <input type="submit" value="Search" class="hidden" id="searchButton">
            <button type="button" class="hidden" id="shopButton" onclick="shop()">Shop</button>
            <button type="button" class="hidden" id="backButton" onclick="cancelForm()">Cancel</button>
        </form>
        <div>
            <?php
            include 'db_conn.php'; // Include your database connection script

            $full_name = 'No customer found'; // Default value
            $balance = 0; // Default balance value
            $rfid_card_id = isset($_GET['rfid_card_id']) ? $_GET['rfid_card_id'] : '';

            if (!empty($rfid_card_id)) {
                // Query the database to fetch the customer's full name and balance
                $sql = "SELECT customer.first_name, customer.last_name, rfid_cards.balance
                        FROM rfid_cards
                        LEFT JOIN customer ON rfid_cards.customer_id = customer.id
                        WHERE rfid_cards.rfid_card_id = ?";

                $stmt = $con->prepare($sql);
                $stmt->bind_param("s", $rfid_card_id);
                if ($stmt->execute()) {
                    $stmt->bind_result($first_name, $last_name, $balance);
                    if ($stmt->fetch()) {
                        $full_name = isset($first_name) ? $first_name . ' ' . $last_name : 'No customer found';
                    }
                }
                $stmt->close();
            }

            echo "Customer Name: $full_name<br>";
            echo "Balance: $balance";
            ?>
        </div>
        
        <script>
            function toggleButtons() {
                const rfidCardId = document.getElementById('rfid_card_id').value;
                const searchButton = document.getElementById('searchButton');
                const shopButton = document.getElementById('shopButton');
                const backButton = document.getElementById('backButton');

                if (rfidCardId) {
                    searchButton.classList.remove('hidden');
                    shopButton.classList.remove('hidden');
                    backButton.classList.remove('hidden');
                } else {
                    searchButton.classList.add('hidden');
                    shopButton.classList.add('hidden');
                    backButton.classList.add('hidden');
                }
            }

            function shop() {
                // Add your shopping logic here
                alert('Shopping functionality goes here');
            }

            function cancelForm() {
                // Reset the form
                document.getElementById('customerForm').reset();
                toggleButtons(); // Hide buttons again after reset
            }

            // Initial button state
            toggleButtons();
        </script>
    </body>
    </html>
