<?php
// db_connect.php
$host = 'sql213.infinityfree.com';
$db   = 'epiz_33496197_blogs';
$user = 'epiz_33496197';
$pass = 'jJgY7jeQtt'; // Your secret password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
?>