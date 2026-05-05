<?php
// Database Configuration
// VULNERABLE: Hardcoded credentials (no environment variables)

$db_host = 'localhost';
$db_user = 'root';
$db_password = ''; // Empty password for local development
$db_name = 'clinic_management';

// Create connection (VULNERABLE: No prepared statements will be used)
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

?>
