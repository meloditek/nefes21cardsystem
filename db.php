<?php
session_start();

// db.php
$host = 'db.fr-pari1.bengt.wasmernet.com';
$port = '10272'; // <-- PORT TANIMLI
$db = 'kartdb';
$user = 'cd7ec36a7508800059ff9afe3e10';
$pass = '068dcd7e-c36a-7668-8000-f7aa47937809';
$charset = 'utf8';

// DSN düzeltildi: port=$port EKLENDİ
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset"; 

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    
    // Geçmişteki 2006 (gone away) hatasına karşı önlem
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8", 
    PDO::ATTR_TIMEOUT => 5 
];

try {
    // 22. satır (Düzeltilen DSN ile bağlanacak)
    $pdo = new PDO($dsn, $user, $pass, $options); 
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
?>
