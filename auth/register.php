<?php
session_start();
require_once 'config/db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $contact_number = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_NUMBER_INT);
    $ic_number = filter_input(INPUT_POST, 'ic_number', FILTER_SANITIZE_STRING);
    $driver_license = filter_input(INPUT_POST, 'driver_license', FILTER_SANITIZE_STRING);

    if(!preg_match('/^\d{6}-\d{2}-\d{4}$/', $ic_number)) {
        $_SESSION['error'] = "Invalid IC Number format.";
        header("Location: ../public/register.html");
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Email is already registered";
            header("Location: ../public/register.html");
            exit();
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (email, password_hash, full_name, contact_number, ic_number, driver_license)
                                VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $email,
            $password_hash,
            $full_name,
            $contact_number,
            $ic_number,
            $driver_license
        ]);

        $_SESSION['success'] = "Registration successful. You can proceed to login.";
        header("Location: public/login.php");
    } catch(PDOException $e) {
        error_log($e->getMessage());
        $_SESSION['error'] = "Registration failed. Please try again.";
        header("Location: ../public/register.html");
    }
}
?>