<?php
// db.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, apikey");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Supabase Direct PostgreSQL Connection Settings
$host     = 'db.pyxuzpvpqkzfdatyejui.supabase.co';
$port     = '5432';
$db       = 'postgres';
$user     = 'postgres';
$pass     = '?e/4e3b.Z6Z5n#B'; // Replace with your actual Supabase database password

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $e->getMessage()]);
    exit();
}