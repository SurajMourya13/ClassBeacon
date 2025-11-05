<?php
// --- Database Connection Configuration ---
$dbHost = 'localhost';      // Your database host, usually 'localhost'
$dbUser = 'root';           // Your database username, default for XAMPP is 'root'
$dbPass = '';               // Your database password, default for XAMPP is empty
$dbName = 'classbeacon_db';    // The name of the database you created

// --- Create Connection ---
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// --- Check Connection ---
if ($conn->connect_error) {
    // If connection fails, stop the script and display an error
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to utf8mb4 for proper encoding
$conn->set_charset("utf8mb4");
?>