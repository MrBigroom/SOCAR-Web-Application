<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT user_id, email, full_name, ic_number, driver_license, created_at
        FROM users
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("
        SELECT b.*, v.model, v.type, v.price_per_hour
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.vehicle_id
        WHERE b.user_id = ?
        ORDER BY b.start_time DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Profile - SOCAR</title>
        <link rel="stylesheet" href="../css/public.css">
        <link rel="stylesheet" href="../css/profile.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    </head>
    <body>
        <?php include '../includes/header.php'; ?>

        <div class="profile-container">
            <?php if(isset($error)): ?>
                <div class="error"><?= $error ?></div>
            <?php else: ?>
                <div class="profile-section">
                    <div class="profile-header">
                        <i class="fas fa-user-circle"></i>
                        <h2><?= htmlspecialchars($user['full_name']) ?>'s Profile</h2>
                    </div>

                    <dl class="profile-info">
                        <dt>Email</dt>
                        <dd><?= htmlspecialchars($user['email']) ?></dd>
                        <dt>IC Number</dt>
                        <dd><?= htmlspecialchars($user['ic_number']) ?></dd>
                        <dt>Driver License</dt>
                        <dd><?= htmlspecialchars($user['driver_license']) ?></dd>
                        <dt>Member Since</dt>
                        <dd><?= date('F j, Y', strtotime($user['created_at'])) ?></dd>
                    </dl>

                    <button class="edit-button" onclick="location.href='edit_profile.php'">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                </div>
            <div class="profile-section">
                <h2>My Bookings</h2>
                <div class="booking-grid">
                    <?php if(empty($bookings)): ?>
                        <p>No bookings found.</p>
                    <?php else: ?>
                        <?php foreach($bookings as $booking): ?>
                            <div class="booking-card <?= $booking['status'] ?>">
                                <h3><?= htmlspecialchars($booking['model']) ?></h3>
                                <p>
                                    <strong>Start Time:</strong>
                                    <?= date('M j, Y g:i A', strtotime($booking['start_time'])) ?>
                                </p>
                                <p>
                                    <strong>End Time:</strong>
                                    <?= date('M j, Y g:i A', strtotime($booking['end_time'])) ?>
                                </p>
                                <p>
                                    <strong>Duration:</strong>
                                    <?= $booking['duration_hours'] ?> hours
                                </p>
                                <p>
                                    <strong>Total Cost:</strong>
                                    RM<?= number_format($booking['total_cost'], 2) ?>
                                </p>
                                <p>
                                    <strong>Booking Status:</strong>
                                    <span class="status-<?= $booking['status'] ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php include '../includes/footer.php'; ?>
    </body>
</html>