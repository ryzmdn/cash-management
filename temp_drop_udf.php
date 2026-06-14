<?php
require_once 'config/koneksi.php';
try {
    $pdo->exec("DROP FUNCTION IF EXISTS HitungSaldoTotal");
    echo "SUCCESS\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
unlink(__FILE__);
