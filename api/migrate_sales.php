<?php
$conn = new mysqli('localhost', 'root', '', 'sheriff_inventory');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql1 = "ALTER TABLE sales_transactions ADD COLUMN IF NOT EXISTS serial_number VARCHAR(100) AFTER customer_phone";
$sql2 = "ALTER TABLE sales_transactions ADD COLUMN IF NOT EXISTS vin VARCHAR(100) AFTER serial_number";

if ($conn->query($sql1) === TRUE && $conn->query($sql2) === TRUE) {
    echo "Columns added successfully";
} else {
    echo "Error adding columns: " . $conn->error;
}
$conn->close();
?>
