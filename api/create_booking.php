<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$required_fields = ['vehicleId', 'startTime', 'endTime'];
foreach($required_fields as $field) {
    if(!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing requried field: $field"]);
        exit;
    }
}

try {
    $startTime = new DateTime($data['startTime']);
    $endTime = new DateTime($data['endTime']);
    $now = new DateTime();

    if($startTime < $now) {
        throw new Exception('Start time cannot be in the past.');
    }
    if($endTime <= $startTime) {
        throw new Exception('End time must be after start time.');
    }

    $duration = $startTime->diff($endTime);
    $hours = $duration->h + ($duration->days * 24);

    $stmt = $conn->prepare("
        SELECT price_per_hour, is_available
        FROM vehicles
        WHERE vehicle_id = ?
    ");
    $stmt->execute([$data['vehicleId']]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$vehicle) {
        throw new Exception('Vehicle not found');
    }
    if(!$vehicle['is_available']) {
        throw new Exception('Vehicle is not available');
    }

    $totalCost = $hours * $vehicle['price_per_hour'];
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO bookings (user_id, vehicle_id, start_time, end_time, duration_hours, total_cost, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
            $_SESSION['user_id'],
            $data['vehicleId'],
            $startTime->format('Y-m-d H:i:s'),
            $endTime->format('Y-m-d H:i:s'),
            $hours,
            $totalCost
        ]);

    $bookingId = $conn->lastInsertId();

    $stmt = $conn->prepare("
        UPDATE vehicles
        SET is_available = FALSE
        WHERE vehicle_id = ?
    ");
    $stmt->execute([$data['vehicleId']]);
    $conn->commit();

    echo json_encode([
        'bookingId' => $bookingId,
        'totalCost' => $totalCost,
        'duration_hours' => $hours,
        'status' => 'pending'
    ]);
} catch(Exception $e) {
    if($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>