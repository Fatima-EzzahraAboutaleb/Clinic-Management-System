<?php
$host = getenv('MYSQLHOST');      // mysql.railway.internal
$user = getenv('MYSQLUSER');      // The actual MySQL user (NOT root)
$password = getenv('MYSQLPASSWORD'); // The actual MySQL password
$database = getenv('MYSQLDATABASE'); // The actual database name
$port = getenv('MYSQLPORT');      // 3306

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully";
?>