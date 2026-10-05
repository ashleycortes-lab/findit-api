<?php
// login.php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);

$email    = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = trim($input['password'] ?? '');

if (!$email || empty($password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please provide a valid email and password."]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, password_hash, role, status FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
        exit();
    }

    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Account is suspended."]);
        exit();
    }

    $authToken  = bin2hex(random_bytes(32));
    $ipAddress  = $_SERVER['REMOTE_ADDR'] ?? null;
    $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Mobile App';

    $sessionStmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, auth_token, ip_address, device_info, is_active)
        VALUES (:user_id, :auth_token, :ip_address, :device_info, 1)
    ");
    
    $sessionStmt->execute([
        'user_id'     => $user['user_id'],
        'auth_token'  => $authToken,
        'ip_address'  => $ipAddress,
        'device_info' => $deviceInfo
    ]);

    http_response_code(200);
    echo json_encode([
        "status"  => "success",
        "message" => "Login successful.",
        "data"    => [
            "auth_token" => $authToken,
            "user"       => [
                "user_id"   => $user['user_id'],
                "full_name" => $user['full_name'],
                "email"     => $user['email'],
                "role"      => $user['role']
            ]
        ]
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error during authentication."]);
}