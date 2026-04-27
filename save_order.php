<?php
// ── save_order.php ──
// Receives cart data from script.js via POST (as JSON),
// and saves each item as a row in the orders table.

session_start();

// Database configuration
$host     = "localhost";
$db       = "cpstore";
$user     = "root";
$password = "";

// Make sure the user is logged in
if (!isset($_SESSION["user"])) {
    echo json_encode(["success" => false, "message" => "Not logged in."]);
    exit;
}

// Only handle POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Read raw JSON body sent by script.js
    $body = file_get_contents("php://input");
    $data = json_decode($body, true);

    // Validate received data
    if (!$data || !isset($data["items"]) || count($data["items"]) === 0) {
        echo json_encode(["success" => false, "message" => "Cart is empty."]);
        exit;
    }

    // Connect to database
    $conn = new mysqli($host, $user, $password, $db);

    // Check connection
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "Database connection failed."]);
        exit;
    }

    // Get the customer_id from the customer table using the session email
    $email     = $_SESSION["user"];
    $custQuery = $conn->prepare("SELECT customer_id FROM customer WHERE email = ?");
    $custQuery->bind_param("s", $email);
    $custQuery->execute();
    $custResult = $custQuery->get_result();

    if ($custResult->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Customer not found."]);
        $custQuery->close();
        $conn->close();
        exit;
    }

    $customer    = $custResult->fetch_assoc();
    $customer_id = $customer["customer_id"];
    $custQuery->close();

    // Today's date for order_date
    $order_date = date("Y-m-d");

    // Prepare insert statement for the orders table
    $stmt = $conn->prepare(
        "INSERT INTO orders (customer_id, product_id, quantity, order_date, total_price)
         VALUES (?, ?, ?, ?, ?)"
    );

    $errors = 0;

    // Loop through each item in the cart and insert a row
    foreach ($data["items"] as $item) {
        $product_id  = intval($item["product_id"]);
        $quantity    = intval($item["quantity"]);
        $total_price = floatval($item["price"]) * $quantity;

        $stmt->bind_param("iiisd", $customer_id, $product_id, $quantity, $order_date, $total_price);

        if (!$stmt->execute()) {
            $errors++;
        }
    }

    $stmt->close();
    $conn->close();

    if ($errors === 0) {
        // ✅ All orders saved successfully
        echo json_encode(["success" => true, "message" => "Order saved successfully."]);
    } else {
        // ❌ Some inserts failed
        echo json_encode(["success" => false, "message" => "Some items could not be saved."]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>