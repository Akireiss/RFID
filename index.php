<?php

include 'includes/indexheader.php';
?>
    <div class="container">
        <h1>Welcome to DMPC</h1>
        <p class="m-3 fw-bold">Scan your RFID card:</p>
        <input type="text" id="manualRFIDInput" class="rfid-input" name="rfid_card_id" placeholder="Enter RFID card ID">
        <div class="link-group" id="button-container" style="display: none;">
            <button type="button" class="btn btn-md btn-primary" id="checkBalanceButton" data-bs-toggle="modal" data-bs-target="#checkBalanceModal">Check Balance</button>

            <button type="button" class="btn btn-md btn-info" id="viewTransactionsButton" data-bs-toggle="modal" data-bs-target="#viewTransactionsModal">View Transactions</button>

            <a href="#" id="shopButton" class="btn btn-md btn-info">Shop</a>
            <a href="index.php" class="btn btn-md btn-danger" id="cancelButton">Cancel</a>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="rfidNotFoundModal" tabindex="-1" aria-labelledby="rfidNotFoundModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rfidNotFoundModalLabel">RFID Card Not Found</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>The RFID card ID you entered is not found. Please check your card ID and try again.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add your Bootstrap or other JS framework links here -->
<!-- 
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script> jQuery library
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> Bootstrap library -->

    <script>
        var modalVisible = false; // Variable to track if the modal is currently visible

        function checkRFID() {
            var rfidInput = document.getElementById('manualRFIDInput').value;

            $.ajax({
                type: "GET",
                url: "check_rfid_existence.php", // Replace with the actual path to your PHP script
                data: {
                    rfid_card_id: rfidInput
                },
                success: function(response) {
                    if (response === 'true') {
                        // If RFID exists, show the buttons
                        document.getElementById('button-container').style.display = 'block';
                        hideRfidNotFoundModal(); // Hide the modal if it's currently visible
                    } else {
                        // If RFID doesn't exist, show the modal and hide the buttons
                        if (!modalVisible) {
                            $('#rfidNotFoundModal').modal('show');
                            modalVisible = true; // Set the flag to indicate that the modal is now visible
                        }
                        document.getElementById('button-container').style.display = 'none';
                    }
                },
                error: function() {
                    console.log("Error in AJAX request");
                }
            });
        }

        // Function to hide the RFID not found modal
        function hideRfidNotFoundModal() {
            if (modalVisible) {
                $('#rfidNotFoundModal').modal('hide');
                modalVisible = false; // Reset the flag when the modal is hidden
            }
        }

        // Trigger the checkRFID function immediately after the RFID input changes
        document.getElementById('manualRFIDInput').addEventListener('input', checkRFID);

        // Attach the hideRfidNotFoundModal function to the modal's hidden.bs.modal event
        $('#rfidNotFoundModal').on('hidden.bs.modal', function() {
            modalVisible = false; // Reset the flag when the modal is hidden by other means (e.g., close button)
        });
    </script>

    <!-- Modal for Check Balance -->
    <div class="modal fade" id="checkBalanceModal" tabindex="-1" aria-labelledby="checkBalanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkBalanceModalLabel">Check Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="checkBalanceModalBody">
                    <div class="form-group">
                        <label for="customerName">Hello!</label>
                        <input type="text" class="form-control" id="customer_data" disabled value="">
                    </div>
                    <div class="form-group">
                        <label for="balance">Your Balance is </label>
                        <input type="text" class="form-control" id="balance" placeholder="Amount" disabled>
                    </div>
                    <hr>
                    <div class="form-group">
                        <p>What do you want to do next?</p>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="shopButtonModal" class="btn btn-md btn-info">Shop</a>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal for View Transactions -->
    <div class="modal fade" id="viewTransactionsModal" tabindex="-1" aria-labelledby="viewTransactionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewTransactionsModalLabel">View Transactions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="background-color: white;" id="viewTransactionsModalBody">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th scope="col">Date and Time</th>
                                <th scope="col">Transaction Type</th>
                                <th scope="col">Transaction Amount</th>


                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            <!-- Transaction rows will be added dynamically here -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function checkInput() {
                var manualRFIDId = document.getElementById('manualRFIDInput').value;
                var buttonContainer = document.getElementById('button-container');
                var checkBalanceButton = document.getElementById('checkBalanceButton');
                var viewTransactionsButton = document.getElementById('viewTransactionsButton');

                if (manualRFIDId.trim() !== "") {
                    buttonContainer.style.display = 'flex';

                    checkBalanceButton.addEventListener('click', function() {
                        var xhr = new XMLHttpRequest();
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4 && xhr.status === 200) {
                                var response = JSON.parse(xhr.responseText);
                                var customerName = response.name;
                                var balance = response.balance;
                                document.getElementById('customer_data').value = customerName;
                                document.getElementById('balance').value = balance;
                            }
                        };

                        xhr.open("GET", "get_customer_info.php?rfid_card_id=" + manualRFIDId, true);
                        xhr.send();
                    });

                    viewTransactionsButton.addEventListener('click', function() {
                        var xhr = new XMLHttpRequest();
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4 && xhr.status === 200) {
                                var transactions = JSON.parse(xhr.responseText);
                                displayTransactions(transactions);
                            }
                        };

                        xhr.open("GET", "get_transaction.php?rfid_card_id=" + manualRFIDId, true);
                        xhr.send();
                    });
                } else {
                    buttonContainer.style.display = 'none';
                }
            }

            document.getElementById('manualRFIDInput').addEventListener('input', checkInput);

            document.getElementById('shopButton').addEventListener('click', function() {
                var rfid_card_id = document.getElementById('manualRFIDInput').value.trim();
                if (rfid_card_id !== '') {
                    window.location.href = 'shop.php?rfid_card_id=' + rfid_card_id;
                }
            });

            document.getElementById('shopButtonModal').addEventListener('click', function() {
                var rfid_card_id = document.getElementById('manualRFIDInput').value.trim();
                if (rfid_card_id !== '') {
                    // Update the href attribute of the "Shop" button inside the modal
                    document.getElementById('shopButtonModal').href = 'shop.php?rfid_card_id=' + rfid_card_id;
                }
            });


            document.getElementById('cancelButton').addEventListener('click', function() {
                document.getElementById('manualRFIDInput').value = "";
                buttonContainer.style.display = 'none';
            });

            function displayTransactions(transactions) {
                console.log("Received transactions:", transactions);

                var transactionsTableBody = document.getElementById('transactionsTableBody');
                transactionsTableBody.innerHTML = '';

                var groupedTransactions = groupTransactionsByTimestamp(transactions);

                groupedTransactions.forEach(function(group) {
                    console.log("Processing group:", group);

                    var row = document.createElement('tr');
                    row.innerHTML =
                        '<td>' + group.timestamp + '</td>' +
                        '<td>' + group.transaction_type + '</td>' +
                        '<td>' + group.amount.join(', ') + '</td>'; // Concatenate t_amount into a single cell

                    transactionsTableBody.appendChild(row);
                });

                viewTransactionsModal.show(); // Show the modal here

                viewTransactionsModal.addEventListener('hidden.bs.modal', function() {
                    // This event is triggered when the modal is completely hidden
                    // You can add additional logic here if needed
                    console.log('Modal is hidden');
                });
            }

            // Helper function to group transactions by timestamp
            function groupTransactionsByTimestamp(transactions) {
                var groupedTransactions = [];

                transactions.forEach(function(transaction) {
                    var existingGroup = groupedTransactions.find(function(group) {
                        return group.timestamp === transaction.timestamp;
                    });

                    if (existingGroup) {
                        // If a group with the same timestamp exists, append the t_amount to the existing group
                        existingGroup.amount.push(transaction.amount);
                    } else {
                        // If no group with the same timestamp exists, create a new group
                        groupedTransactions.push({
                            timestamp: transaction.timestamp,
                            transaction_type: transaction.transaction_type,
                            amount: [transaction.amount] // Start with an array containing the t_amount
                        });
                    }
                });

                return groupedTransactions;
            }



        });
    </script>
</body>

</html>