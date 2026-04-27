<?php
// ── logout.php ──
// Destroys the current session and redirects the user back to the login page.

session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session on the server
session_destroy();

// Redirect to login page
header("Location: index.html");
exit;
?>