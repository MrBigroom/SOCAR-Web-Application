<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if(!isset($data['booking_id']) || !isset($data['payment_intent_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Booking ID and Payment Intent ID are required']);
    exit;
}

try {
    $conn->beginTransaction();
    $stmt = $conn->prepare("
        SELECT status, vehicle_id
        FROM bookings
        WHERE booking_id = ? AND user_id = ?
    ");
    $stmt->execute([$data['booking_id'], $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$booking) {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
    }
    if($booking['status'] !== 'pending') {
        http_response_code(400);
        throw new Exception('Invalid booking status.');
    }

    $stmt = $conn->prepare("
        UPDATE bookings
        SET status = 'active', payment_intent_id = ?
        WHERE booking_id = ?
    ");
    $stmt->execute([$data['payment_intent_id'], $data['booking_id']]);

    $stmt = $conn->prepare("
        UPDATE vehicles
        SET is_available = FALSE
        WHERE vehicle_id = ?
    ");
    $stmt->execute([$booking['vehicle_id']]);

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Booking status updated successfully',
        'booking_id' => $data['booking_id']
    ]);
} catch(Exception $e) {
    if($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Booking update error: '. $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to update booking status',
        'message' => $e->getMessage()
    ]);
}
?>