<?php
session_start();
require_once '../config/db.php';

header('Content-Type: appliction/json');

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if(!isset($data['booking_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Booking ID is required']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT b.status, b.start_time, b.end_time, TIMESTAMPDIFF(MINUTE, b.created_at, CURRENT_TIMESTAMP) as minutes_elapsed
        FROM bookings b
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->execute([$data['booking_id'], $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$booking) {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
        exit;
    }

    if($booking['minutes_elapsed'] > 15 && $booking['status'] === 'pending') {
        $stmt = $conn->prepare("
            UPDATE bookings
            SET status = 'expired'
            WHERE booking_id = ? AND user_id = ?
        ");
        $stmt->execute([$data['booking_id'], $_SESSION['user_id']]);

        $stmt = $conn->prepare("
            UPDATE vehicles v
            JOIN bookings b ON v.vehicle_id = b.vehicle_id
            SET v.is_available = TRUE
            WHERE b.booking_id = ? AND b.user_id = ?
        ");
        $stmt->execute([$data['booking_id'], $_SESSION['user_id']]);

        http_response_code(400);
        echo json_encode([
            'error' => 'Booking has expired',
            'status' => 'expired'
        ]);
        exit;
    }

    if(strtotime($booking['start_time']) < time()) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Booking has started',
            'status' => 'invalid'
        ]);
        exit;
    }
    
    echo json_encode([
        'status' => $booking['status'],
        'start_time' => $booking['start_time'],
        'end_time' => $booking['end_time']
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
?>