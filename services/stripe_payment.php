<?php
require_once '../config/db.php';
require_once '../vendor/autoload.php';

class Stripe_payment {
    private $stripe;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        \Stripe\Stripe::setApiKey('');
    }

    public function createPaymentIntent($bookingId) {
        $stmt = $this->conn->prepare("
                SELECT b.total_cost, v.model, u.email
                FROM bookings b
                JOIN vehicles v ON b.vehicle_id = v.vehicle_id
                JOIN users u ON b.user_id = u.user_id
                WHERE b.booking_id = ?
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$booking) {
            throw new Exception('Booking not found.');
        }

        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $booking['total_cost'] * 100,
                'currency' => 'myr',
                'metadata' => [
                    'booking_id' => $bookingId,
                    'vehicle_model' => $booking['model']
                    ]
                ]);

                $this->conn->beginTransaction();
                $stmt = $this->conn->prepare("
                    UPDATE bookings
                    SET payment_intent_id = ?
                    WHERE booking_id = ?
                ");
                $stmt->execute([$paymentIntent->id, $bookingId]);
            
                $stmt = $this->conn->prepare("
                        INSERT INTO payments (booking_id, amount, payment_method, transaction_id, payment_status)
                        VALUES (?, ?, 'stripe', ?, 'pending')
                ");
                $stmt->execute([$bookingId, $booking['total_cost'], $paymentIntent->id]);

                return [
                    'clientSecret' => $paymentIntent->client_secret,
                    'paymentIntentId' => $paymentIntent->id,
                    'amount' => $booking['total_cost']
                ];
        } catch(\Stripe\Exception\ApiErrorException $e) {
            if($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw new Exception('Stripe API error: ' . $e->getMessage());
        } catch(Exception $e) {
            if($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw new Exception('Payment processing error: ' . $e->getMessage());
        }
    }

    public function updatePaymentStatus($paymentIntentId, $status) {
        try{
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("
                UPDATE payments
                SET payment_status = ?
                WHERE transaction_id = ?
            ");
            $stmt->execute([$status, $paymentIntentId]);

            if($status === 'completed') {
            $stmt = $this->conn->prepare("
                    UPDATE bookings b
                    JOIN payments p ON b.booking_id = p.booking_id
                    SET b.payment_status = 'active'
                    WHERE p.transaction_id = ?
            ");
            $stmt->execute([$paymentIntentId]);
        }
        $this->conn->commit();
        } catch(Exception $e) {
            if($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw new Exception('Payment status update error: ' . $e->getMessage());
        }
    }
}
?>