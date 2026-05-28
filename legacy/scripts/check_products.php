<?php
$c = new mysqli('localhost', 'root', '', 'shevvy_sheriff_inventory');
if ($c->connect_error) die("Connection failed: " . $c->connect_error);
$r = $c->query('SELECT product_id, product_name, category FROM products LIMIT 10');
while($row = $r->fetch_assoc()) {
    print_r($row);
}
$c->close();
