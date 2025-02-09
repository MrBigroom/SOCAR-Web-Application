<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOCAR Web Application</title>
    <link rel="stylesheet" href="/css/header.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo-container">
                <img src="../src/SOCAR title logo.png" style="width: 80px;">
                <h1>Car Sharing Services</h1>
            </div>

            <nav>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="home.html">Home</a>
                    <a href="/auth/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/home.html">Home</a>
                    <a href="/public/login.php">Login</a>
                    <a href="/public/register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
</body>