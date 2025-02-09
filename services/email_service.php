<?php
class Email_service {
    private $conn;
    private $templatePath;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->templatePath = __DIR__ . '/email_templates/';
    }

    private function loadTemplate($templateName, $variables) {
        $template = file_get_contents($this->templatePath . $templateName);
        foreach($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', htmlspecialchars($value), $template);
        }
        return $template;
    }

    private function sendBookingConfirmation($bookingId) {
        try{
            $stmt = $this->conn->prepare("
                SELECT b.*, v.model, u.email, u.full_name
                FROM bookings b
                JOIN vehicles v ON b.vehicle_id = v.vehicle_id
                JOIN users u ON b.user_id = u.user_id
                WHERE b.booking_id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$booking) {
                throw new Exception('Booking not found');
            }

            $variables = [
                'full_name' => $booking['full_name'],
                'model' => $booking['model'],
                'start_time' => date('M j, Y g:i A', strtotime($booking['start_time'])),
                'end_time' => date('M j, Y g:i A', strtotime($booking['end_time'])),
                'duration' => $booking['duration_hours'],
                'total_cost' => number_format($booking['total_cost'], 2),
                'booking_id' => $booking['booking_id']
            ];
            $message = $this->loadTemplate('booking_confirmation.html', $variables);
            $to = $booking['email'];
            $subject = 'SOCAR Booking Confirmation - ' . $booking['model'];
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: SOCAR <noreply@socar.com>' . "\r\n";

            if(!mail($to, $subject, $message, $headers)) {
                throw new Exception('Failed to send email');
            }
            return true;
        } catch(Exception $e) {
            error_log('Email failed to send: ' . $e->getMessage());
            return false;
        }
    }
}
?>