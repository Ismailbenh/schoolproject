<?php
// ── login.php ──
// Receives email + password from the login form via POST,
// checks against the account table, and starts a session if correct.

session_start();

// Database configuration
$host     = "localhost";
$db       = "cpstore";
$user     = "root";
$password = "";

// Only handle POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Grab and sanitize inputs
    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    // Basic validation
    if (empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Please fill in all fields."]);
        exit;
    }

    // Connect to the database
    $conn = new mysqli($host, $user, $password, $db);

    // Check connection
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "Database connection failed."]);
        exit;
    }

    // Query the account table for a matching email
    $stmt = $conn->prepare("SELECT password FROM account WHERE login = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verify the password (password_verify works with password_hash)
        if (password_verify($password, $row["password"])) {
            // ✅ Credentials are correct — start session
            $_SESSION["user"] = $email;
            echo json_encode(["success" => true, "redirect" => "main.html"]);
        } else {
            // ❌ Wrong password
            echo json_encode(["success" => false, "message" => "Incorrect email or password."]);
        }
    } else {
        // ❌ Email not found
        echo json_encode(["success" => false, "message" => "Incorrect email or password."]);
    }

    $stmt->close();
    $conn->close();

} else {
    // Not a POST request
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>