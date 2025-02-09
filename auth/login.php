<?php
session_start();
require_once '../config/db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("
            SELECT user_id, email, password_hash 
            FROM users 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            
            header("Location: ../index.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password";
            header("Location: ../public/login.html");
            exit();
        }
        
    } catch(PDOException $e) {
        error_log($e->getMessage());
        $_SESSION['error'] = "Login failed";
        header("Location: ../public/login.html");
    }
}