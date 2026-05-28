<?php
$c = new mysqli('localhost', 'root', '', 'shevvy_sheriff_inventory');
if ($c->connect_error) die("Connection failed: " . $c->connect_error);
$r = $c->query('DESCRIBE sales_transactions');
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
$c->close();
