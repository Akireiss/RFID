<?php
include 'db_conn.php';

// Assuming you have a function like this to get product info by barcode
function getProductInfoByBarcode($con, $barcode)
{
    $sql = "SELECT * FROM products WHERE barcode = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Main handling of AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['barcode'])) {
        // Handle barcode input and fetch product info
        $barcode = $_POST['barcode'];
        $productInfo = getProductInfoByBarcode($con, $barcode);

        // Return the HTML response (you might need to adjust the format based on your existing response structure)
        if (!empty($productInfo) && $productInfo['quantity'] > 0) {
            echo '<ul class="list-group">';
            echo '<li class="list-group-item border-0 d-flex p-4 mb-2 bg-gray-100 border-radius-lg" data-barcode="' . $productInfo['barcode'] . '">';
            echo '<div class="d-flex flex-column">';
            echo '<span class="mb-2 text-xs">Item Description: <span class="text-dark font-weight-bold ms-sm-2">' . $productInfo['item'] . '</span></span>';
            echo '<span class="mb-2 text-xs">Available Quantity: <span id="availableQuantity" class="text-dark font-weight-bold ms-sm-2">' . $productInfo['quantity'] . '</span></span>';
            echo '<span class="mb-2 text-xs">Unit Price: <span class="text-dark ms-sm-2 font-weight-bold">' . $productInfo['unit_price'] . '</span></span>';
            echo '<span class="text-xs">Weight: <span class="text-dark ms-sm-2 font-weight-bold">' . $productInfo['weight'] . '</span></span>';
            echo '</div>';
            echo '</li>';
            echo '</ul>';

            // Include the "How Many Quantity" input and Add to Cart form
            echo '<div class="mb-2 text-xs">';
            echo '<label for="quantity">How Many Quantity:</label>';
            echo '<input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" max="' . $productInfo['quantity'] . '" onchange="updateDisplayedQuantity()">';
            echo '</div>';
            echo '<form method="post">';
            echo '<input type="hidden" name="barcode" value="' . $productInfo['barcode'] . '">';
            echo '<input type="hidden" name="quantity" id="inputQuantity" value="1">';
            echo '<button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>';
            echo '</form>';
        } else {
            echo '<p class="text-danger">Sorry, this product is out of stock.</p>';
        }
        exit();
    }  elseif (isset($_POST['add_to_cart'])) {
        // Retrieve existing cart data from local storage
        $cart = json_decode($_COOKIE['cart'], true);

        if (!$cart) {
            $cart = array();
        }

        $productInfo = getProductInfoByBarcode($con, $_POST['barcode']);

        // Check if the product is already in the cart before adding
        $productInCart = array_filter($cart, function ($item) use ($productInfo) {
            return $item['barcode'] == $productInfo['barcode'];
        });

        if (empty($productInCart)) {
            $cart[] = $productInfo;

            // Save updated cart data back to local storage
            setcookie('cart', json_encode($cart), time() + (86400 * 30), "/"); // Cookie lasts for 30 days

            $response = [
                'status' => 'success',
                'message' => 'Product added to cart successfully.',
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Product is already in the cart.',
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

echo 'Invalid Request';
?>