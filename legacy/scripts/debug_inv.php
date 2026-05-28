<?php
require_once 'app/config/DatabaseConnection.php';
$conn = DatabaseConnection::getConnection();
$res = $conn->query("SELECT * FROM inventory LIMIT 5");
while($row = $res->fetch_assoc()) print_r($row);
?>
