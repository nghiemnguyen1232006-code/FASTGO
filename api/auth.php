<?php

header('Content-Type: application/json');

require "../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email và mật khẩu không được để trống']);
    exit;
}

$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE email=?
");

$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Email không tồn tại']);
    exit;
}

if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sai mật khẩu']);
    exit;
}

// Không trả về password
unset($user['password']);

echo json_encode([
    'success' => true,
    'user'    => $user
]);
