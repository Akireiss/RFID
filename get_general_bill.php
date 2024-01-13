<?php
session_start();

// Include your database connection or any necessary functions
include 'db_conn.php';

// Function to get product info by barcode
function getProductInfoByBarcode($con, $barcode)
{
    $sql = "SELECT * FROM products WHERE barcode = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to calculate total weight and price of the cart
function calculateCartTotal($cart)
{
    $totalWeight = 0;
    $totalPrice = 0;

    foreach ($cart as $cartItem) {
        $weight = floatval($cartItem['weight']);
        $totalWeight += $weight;

        $inputQuantity = $cartItem['quantity'];
        $itemTotalPrice = $cartItem['unit_price'] * $inputQuantity;
        $totalPrice += $itemTotalPrice;
    }

    return [
        'totalWeight' => number_format($totalWeight, 2),
        'totalPrice' => number_format($totalPrice, 2),
    ];
}

// Check if the cart is set in the session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Get the cart content from the session
$cart = $_SESSION['cart'];

// Generate the HTML content for the "General Bill" section
$html = '<span class="d-none d-md-block ps-2" style="font-weight: bold;">';
$html .= "Your Balance is: ₱" . $balance . ".00";
$html .= '</span>';
$html .= '<div class="card">';
$html .= '<div class="card-header pb-0 px-3">';
$html .= '<h6 class="mb-0">General Bill</h6>';
$html .= '</div>';
$html .= '<div class="card-body">';
$html .= '<table class="table">';
$html .= '<thead>';
$html .= '<tr>';
$html .= '<th>Item</th>';
$html .= '<th>Quantity</th>';
$html .= '<th>Weight</th>';
$html .= '<th>Price</th>';
$html .= '<th>Action</th>';
$html .= '</tr>';
$html .= '</thead>';
$html .= '<tbody>';

$totalWeight = 0;
$totalPrice = 0;
$productCount = 0;

foreach ($cart as $cartItem) {
    $weight = floatval($cartItem['weight']);
    $totalWeight += $weight;
    $productCount++;

    $inputQuantity = $cartItem['quantity'];
    $itemTotalPrice = $cartItem['unit_price'] * $inputQuantity;

    if ($inputQuantity !== '') {
        $html .= '<tr>';
        $html .= '<td>' . $cartItem['item'] . '</td>';
        $html .= '<td>' . $inputQuantity . '</td>';
        $html .= '<td id="itemWeight_' . $cartItem['barcode'] . '">' . $weight . '</td>';
        $html .= '<td id="itemTotalPrice_' . $cartItem['barcode'] . '">' . number_format($itemTotalPrice, 2) . '</td>';
        $html .= '<td>';
        $html .= '<form method="post">';
        $html .= '<input type="hidden" name="remove_from_cart" value="' . $cartItem['barcode'] . '">';
        $html .= '<button type="submit" class="btn btn-danger btn-sm">X</button>';
        $html .= '</form>';
        $html .= '</td>';
        $html .= '</tr>';

        $totalPrice += $itemTotalPrice;
    }
}

$html .= '<tr>';
$html .= '<td colspan="2"></td>';
$html .= '<td>Total Weight: <span id="grandTotalWeight">' . number_format($totalWeight, 2) . '</span></td>';
$html .= '<td>Total Price: <span id="grandTotalPrice">' . number_format($totalPrice, 2) . '</span></td>';
$html .= '</tr>';

$html .= '</tbody>';
$html .= '</table>';
$html .= '<div id="removeProductsMessage" class="alert alert-danger mt-3" style="display: none;">';
$html .= 'Remove some Products so that you can continue with the transaction.';
$html .= '</div>';

$html .= '<p>Total Items in Cart: <strong>' . $productCount . '</strong></p>';

$html .= '<form method="post">';
$html .= '<button type="submit" name="clear_cart" class="btn btn-danger">Clear Cart</button>';
$html .= '</form>';
$html .= '<form method="post" class="mt-3" onsubmit="return checkBalance()">';
$html .= '<div class="form-group">';
$html .= '<input type="text" name="rfid_card_id" required placeholder="RFID Card ID" class="form-control">';
$html .= '</div>';
$html .= '<button type="submit" name="submit_transaction" class="btn btn-success">Submit Transaction</button>';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaction'])) {
    if ($transactionSuccess) {
        if (!isset($alertMessage)) {
            $html .= '<div class="alert alert-success mt-3" role="alert">Transaction submitted successfully!</div>';
        }
    }
}

$html .= '</form>';
$html .= '</div>';
$html .= '</div>';

// Send the HTML response
echo $html;
?>
