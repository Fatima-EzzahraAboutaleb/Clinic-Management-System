<?php
$mysql_url = getenv('MYSQL_URL');

try {
    $pdo = new PDO($mysql_url);
    echo "Connected successfully";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>