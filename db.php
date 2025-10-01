<?php
session_start(); // Oturumu başlat

// db.php
$host = 'localhost';
$db = 'kartdb';
$user = 'cd7ec36a7508800059ff9afe3e10';
$pass = '068dcd7e-c36a-7668-8000-f7aa47937809';
$charset = 'utf8';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Hataları göster
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch default
    PDO::ATTR_EMULATE_PREPARES => false,                  // gerçek prepared statement
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
?>

