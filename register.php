<?php
// register.php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);

$fullName = trim($input['full_name'] ?? '');
$email    = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone    = trim($input['phone_number'] ?? '');
$password = trim($input['password'] ?? '');
$role     = trim($input['role'] ?? 'user');

// Validate allowed role choices
if (!in_array($role, ['user', 'staff', 'admin'])) {
    $role = 'user';
}

if (empty($fullName) || !$email || empty($password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Full name, valid email, and password are required."]);
    exit();
}

try {
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
    $checkStmt->execute(['email' => $email]);
    
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Email is already registered."]);
        exit();
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, phone_number, password_hash, role, status)
        VALUES (:full_name, :email, :phone_number, :password_hash, :role::user_role_type, 'active'::user_status_type)
    ");
    
    $stmt->execute([
        'full_name'     => $fullName,
        'email'         => $email,
        'phone_number'  => $phone,
        'password_hash' => $passwordHash,
        'role'          => $role
    ]);

    http_response_code(201);
    echo json_encode([
        "status" => "success", 
        "message" => "Account registered successfully as " . strtoupper($role) . "."
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}