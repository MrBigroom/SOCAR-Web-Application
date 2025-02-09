<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if(!isset($_GET['booking_id'])) {
    header('Location: ../home.php');
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT b.*, v.model, v.price_per_hour
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.vehicle_id
        WHERE b.booking_id = ? AND b.user_id = ? AND b.status = 'pending'
    ");
    $stmt->execute([$_GET['booking_id'], $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$booking) {
        header('Location: ../home.php');
        exit();
    }
} catch(PDOException $e) {
    error_log($e->getMessage());
    header('Location: ../home.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment - SOCAR</title>
        <link rel="stylesheet" href="../css/payment.css">
        <link rel="stylesheet" href="../css/public.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <script>
            const bookingId = <?= json_encode($booking['booking_id']) ?>;
            const bookingName = <?= json_encode(htmlspecialchars($booking['name'])) ?>;
            const totalCost = <?= json_encode(number_format($booking['total_cost'], 2)) ?>;
        </script>
        <script src="https://js.stripe.com/v3/"></script>
        <script src="../js/payment.js" defer></script>
    </head>
    <body>
        <?php include '../includes/header.php'; ?>

        <div class="payment-container">
            <h2>Payment Details</h2>

            <div class="booking-summary">
                <h3>Booking Summary</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <strong>Vehicle</strong>
                        <?= htmlspecialchars($booking['model']) ?>
                    </div>
                    <div class="summary-grid">
                        <strong>Start Time</strong>
                        <?= date('M j, Y g:i A', strtotime($booking['start_time'])) ?>
                    </div>
                    <div class="summary-grid">
                        <strong>End Time</strong>
                        <?= date('M j, Y g:i A', strtotime($booking['end_time'])) ?>
                    </div>
                    <div class="summary-grid">
                        <strong>Duration</strong>
                        <?= $booking['duration'] ?> hours
                    </div>
                </div>
                <div class="total-amount">
                    Total Amount: RM<?= number_format($booking['total_cost'], 2) ?>
                </div>
            </div>

            <form id="payment-form">
                <div class="form-group">
                    <label>Card Details</label>
                    <div id="card-element"></div>
                    <div id="card-errors" role="alert"></div>
                </div>
                <button type="submit" class="pay-button" id="submit-button">
                    <i class="fas fa-lock"></i> Pay RM<?= number_format($booking['total_cost'], 2) ?>
                </button>
            </form>
        </div>

        <?php include '../includes/footer.php'; ?>
    </body>
</html>