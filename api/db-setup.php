<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
echo "=== products columns ===\n";
try{$cols=$d->fetchAll("SHOW COLUMNS FROM products");foreach($cols as $c) echo $c['Field']." (".$c['Type'].")\n";}
catch(Throwable $e){echo "ERROR: ".$e->getMessage()."\n";}
echo "\n=== cart columns ===\n";
try{$cols=$d->fetchAll("SHOW COLUMNS FROM cart");foreach($cols as $c) echo $c['Field']." (".$c['Type'].")\n";}
catch(Throwable $e){echo "ERROR: ".$e->getMessage()."\n";}
echo "\n=== cart.php full GET query ===\n";
try{
    $sql = "SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.sale_price, p.image_url, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = 724";
    $items = $d->fetchAll($sql);
    echo count($items)." items OK\n";
}catch(Throwable $e){echo "SQL ERROR: ".$e->getMessage()."\n";}
