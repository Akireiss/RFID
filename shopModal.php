<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop Modal</title>
    <!-- Include Bootstrap 5 CSS and JavaScript -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</head>
<body>
    <!-- Modal for Shop -->
    <div class="modal fade" id="shopModal" tabindex="-1" aria-labelledby="shopModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shopModalLabel">Shop</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Product information displayed here -->
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Barcode</th>
                                <th>Item Description</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Weight</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Sample product data -->
                            <tr>
                                <td>123456</td>
                                <td>Sample Product 1</td>
                                <td>2</td>
                                <td>$10.00</td>
                                <td>0.5 lbs</td>
                                <td>$20.00</td>
                            </tr>
                            <tr>
                                <td>789012</td>
                                <td>Sample Product 2</td>
                                <td>3</td>
                                <td>$15.00</td>
                                <td>1 lb</td>
                                <td>$45.00</td>
                            </tr>
                            <!-- Add more product data here as needed -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
