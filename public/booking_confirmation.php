<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if(!isset($_GET['booking_id'])) {
    header('Location: ../home.php');
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT b.*, v.model, v.type, p.transaction_id, p.payment_status
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.vehicle_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->execute([$_GET['booking_id'], $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$booking) {
        header('Location: ../home.php');
        exit;
    }
} catch(PDOException $e) {
    error_log($e->getMessage());
    header('Location: ../home.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booking Confirmation - SOCAR</title>
        <link rel="stylesheet" href="../booking_confirmation.css">
        <link rel="stylesheet" href="../css/public.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    </head>
    <body>
        <?php include '../includes/header.php' ?>

        <div class="confirmation-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <h2>Booking Confirmed!</h2>
            <p>Your booking and payment are successfully processed.</p>

            <div class="booking-details">
                <h3>Booking Details</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <strong>Vehicle</strong>
                        <?= htmlspecialchars($booking['model']) ?>
                    </div>
                    <div class="detail-item">
                        <strong>Type</strong>
                        <?= htmlspecialchars($booking['type']) ?>
                    </div>
                    <div class="detail-item">
                        <strong>Start Time</strong>
                        <?= date('M j, Y g:i A', strtotime($booking['start_time'])) ?>;
                    </div>
                    <div class="detail-item">
                        <strong>End Time</strong>
                        <?= date('M j, Y g:i A', strtotime($booking['end_time'])) ?>
                    </div>
                    <div class="detail-item">
                        <strong>Duration</strong>
                        <?= $booking['duration_hours'] ?> hours
                    </div>
                    <div class="detail-item">
                        <strong>Total Cost</strong>
                        RM<?= number_format($booking['total_cost'], 2) ?>
                    </div>
                </div>

                <div class="payment-info">
                    <strong>Payment Reference:</strong>
                    <?= substr($booking['transaction_id'], 0, 8) ?>
                    <br>
                    <strong>Payment Status:</strong>
                    <span style="color: #2ecc71;">
                        <?= ucfirst($booking['payment_status']) ?>
                    </span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="../home.php" class="home-button">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="profile.php" class="view-bookings-button">
                    <i class="fas fa-clipboard-list"></i> View My Bookings
                </a>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
    </body>
</html>