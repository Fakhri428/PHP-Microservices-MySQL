<?php

$host = "localhost";
$dbname = "db_order_service";
$username = "root";
$password = "root";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Koneksi database Order Service gagal",
        "error" => $e->getMessage()
    ]);
    exit;
}