<?php
require_once '../config/db.php';
require_once '../services/stripe_payment.php';
require_once '../vendor/autoload.php';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$endpoint_secret = '';

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch(\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
}

try {
    $stripePayment = new Stripe_payment($conn);

    switch($event->type) {
        case 'payment_intent.succeeded':
            $paymentIntent = $event->data->object;
            $stripePayment->updatePaymentStatus($paymentIntent->id, 'completed');
            break;
        
        case 'payment_intent.payment_failed':
            $paymentIntent = $event->data->object;
            $stripePayment->updatePaymentStatus($paymentIntent->id, 'failed');
            break;
    }
    http_response_code(200);
} catch (\Exception $e) {
    error_log('Webhook error: ' . $e->getMessage());
    http_response_code(500);
}
?>