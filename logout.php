<?php
// logout.php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

$headers   = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$authToken  = null;

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $authToken = $matches[1];
} else {
    $input = json_decode(file_get_contents("php://input"), true);
    $authToken = $input['auth_token'] ?? null;
}

if (!$authToken) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Authorization token is required to logout."]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE user_sessions 
        SET is_active = 0, logout_time = CURRENT_TIMESTAMP 
        WHERE auth_token = :auth_token AND is_active = 1
    ");
    $stmt->execute(['auth_token' => $authToken]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Successfully logged out."]);
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid token or user is already logged out."]);
    }

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error during logout."]);
}