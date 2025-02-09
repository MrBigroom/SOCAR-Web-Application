<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT email, full_name, ic_number, driver_license
        FROM users
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $ic_number = filter_input(INPUT_POST, 'ic_number', FILTER_SANITIZE_STRING);
    $driver_license = filter_input(INPUT_POST, 'driver_license', FILTER_SANITIZE_STRING);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];
    if(!preg_match('/^\d{6}-\d{2}-\d{4}$/', $ic_number)) {
        $errors[] = 'Invalid IC number format. Please use XXXXXX-XX-XXXX';
    }
    if(!preg_match('/^[A-Z][0-9]{5}$/', $driver_license)) {
        $errors[] = 'Invalid driver license number. Please use XXXXX';
    }

    $stmt = $conn->prepare("
        SELECT user_id
        FROM users
        WHERE email = ? AND user_id != ?
    ");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if($stmt->rowCount() > 0) {
        $errors[] = 'Email already in use';
    }

    if(!empty($new_password)) {
        $stmt = $conn->prepare("
            SELECT password_hash
            FROM users
            WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $current_password_hash = $stmt->fetchColumn();
        if(!password_verify($current_password, $current_password_hash)) {
            $errors[] = 'Current password incorrect.';
        }

        if(strlen($new_password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
    }

    if(empty($errors)) {
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("
                UPDATE users
                SET full_name = ?, email = ?, ic_number = ?, driver_license = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$full_name, $email, $ic_number, $driver_license, $_SESSION['user_id']]);

            if(!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    UPDATE users
                    SET password_hash = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$password_hash, $_SESSION['user_id']]);
            }

            $conn->commit();
            $_SESSION['success'] = "Profile updated successfully.";
            header('Location: profile.php');
            exit;
        } catch(PDOException $e) {
            $conn->rollBack();
            $errors[] = 'Failed to update profile.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Profile - SOCAR</title>
        <link rel="stylesheet" href="../css/public.css">
        <link rel="stylesheet" href="../css/edit_profile.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    </head>
    <body>
        <?php include '../includes/header.php'; ?>

        <div class="form-container">
            <h2>Edit Profile</h2>
            <?php if(!empty($errors)): ?>
                <div class="error_message">
                    <ul>
                        <?php foreach($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-section">
                    <h3>Personal Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name:</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Number:</label>
                            <input type="tel" name="contact_number" value="<?= htmlspecialchars($user['contact_number']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>IC Number:</label>
                            <input type="text" name="ic_number" value="<?= htmlspecialchars($user['ic_number']) ?>"
                                    pattern="\d{6}-\d{2}-\d{4}" title="Format: XXXXXX-XX-XXXX" required>
                        </div>
                        <div class="form-group">
                            <label>Driver License:</label>
                            <input type="text" name="driver_license" value="<?= htmlspecialchars($user['$driver_license']) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Change Password</h3>
                    <p class="hint">Leave password fields empty if you don't want to change it.</p>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Current Password:</label>
                            <input type="password" name="current_password">
                        </div>
                        <div class="form-group">
                            <label>New Password:</label>
                            <input type="password" name="new_password" minlength="8">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password:</label>
                            <input type="password" name="confirm_password">
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="save-button">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="profile.php" class="cancel-button">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>

        <?php include '../includes/footer.php'; ?>
    </body>
</html>