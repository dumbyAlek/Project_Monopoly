<?php
$conn = new mysqli("localhost", "monopoly_user", "RahiqBaddie", "monopoly_db");

if ($conn->connect_error) {
    die("MySQL NOT working: " . $conn->connect_error);
}

echo "MySQL is running and reachable.";
?>