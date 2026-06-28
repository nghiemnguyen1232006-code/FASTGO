<?php

header('Content-Type: application/json');

require "../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// GET /api/drivers.php?id=3 => lấy 1 tài xế
if (isset($_GET['id'])) {

    $id = (int) $_GET['id'];

    $stmt = $conn->prepare("
        SELECT
            id, fullname, email, phone,
            cccd, birthdate, education,
            vehicle_plate, address, hometown,
            ethnicity, gender, nationality,
            religion, license_type, created_at
        FROM users
        WHERE id=?
        AND role='driver'
    ");

    $stmt->execute([$id]);

    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$driver) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy tài xế']);
        exit;
    }

    echo json_encode(['success' => true, 'driver' => $driver]);
    exit;
}

// GET /api/drivers.php => lấy tất cả tài xế
$stmt = $conn->query("
    SELECT
        id, fullname, email, phone,
        vehicle_plate, license_type, created_at
    FROM users
    WHERE role='driver'
    ORDER BY id DESC
");

$drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'total'   => count($drivers),
    'drivers' => $drivers
]);
