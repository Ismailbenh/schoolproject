<?php
// ── register.php ──
// Receives email + password from the sign in form via POST,
// inserts a new account into the account table if the email is not already taken.

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
    $confirm  = trim($_POST["confirm"] ?? "");

    // Basic validation
    if (empty($email) || empty($password) || empty($confirm)) {
        echo json_encode(["success" => false, "message" => "Please fill in all fields."]);
        exit;
    }

    // Check passwords match
    if ($password !== $confirm) {
        echo json_encode(["success" => false, "message" => "Passwords do not match."]);
        exit;
    }

    // Password length
    if (strlen($password) < 6) {
        echo json_encode(["success" => false, "message" => "Password must be at least 6 characters."]);
        exit;
    }

    // Connect to database
    $conn = new mysqli($host, $user, $password, $db);

    // Check connection
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "Database connection failed."]);
        exit;
    }

    // Check if email already exists in account table
    $check = $conn->prepare("SELECT login FROM account WHERE login = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // ❌ Email already registered
        echo json_encode(["success" => false, "message" => "This email is already registered."]);
        $check->close();
        $conn->close();
        exit;
    }
    $check->close();

    // Hash the password before storing (never store plain text passwords)
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new account into the account table
    $stmt = $conn->prepare("INSERT INTO account (login, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $hashed);

    if ($stmt->execute()) {
        // ✅ Account created successfully
        echo json_encode(["success" => true, "redirect" => "index.html"]);
    } else {
        // ❌ Insert failed
        echo json_encode(["success" => false, "message" => "Registration failed. Please try again."]);
    }

    $stmt->close();
    $conn->close();

} else {
    // Not a POST request
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>