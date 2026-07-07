<?php
// Database connection (shared)
$host = "10.10.6.59"; // IP Server di HeidiSQL
$username = "root_host"; // Username database Anda
$password = "password"; // Password database Anda
$database = "magang_itakms";

try {
    // DISURUH: Menambahkan charset=utf8mb4 agar mendukung pembacaan karakter simbol (seperti icon mata, dll) secara aman
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Opsional: Mengatur default fetch mode menjadi array asosiatif untuk mempermudah pemanggilan kolom
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Koneksi gagal: " . $e->getMessage();
    die();
}
