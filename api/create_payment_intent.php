<?php
require_once '../config/db.php';
require_once '../services/stripe_payment.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if(!isset($data['booking_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing booking id']);
    exit;
}

try {
    session_start();
    if(!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }
    $stmt = $conn->prepare("
        SELECT user_id
        FROM bookings
        WHERE booking_id = ?
    ");
    $stmt->execute([$data['booking_id']]);
    $booking = $stmt->fetch();

    if(!$booking || $booking['user_id'] !== $_SESSION['user_id']) {
        throw new Exception('Invalid booking');
    }

    $stripePayment = new Stripe_payment($conn);
    $paymentData = $stripePayment->createPaymentIntent($data['booking_id']);
    echo json_encode([
        'clientSecret' => $paymentData['client_secret'],
        'payment_intent_id' => $paymentData['payment_intent_id'],
        'amount' => $paymentData['amount']
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>