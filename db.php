<?php
// Database connection (shared)
$host = "10.10.6.59"; // Sesuaikan dengan IP di HeidiSQL Anda
$username = "root_host"; // Sesuaikan username database Anda
$password = "password"; // Sesuaikan password database Anda
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Koneksi gagal: " . $e->getMessage();
    die();
}

