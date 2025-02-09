<?php
$host = "localhost";
$dbname = "socar_db";
$username = "root";
$password = "";

define('OPENWEATHER_API_KEY', value: '2d2413eb7d7c7fbf3f453edb70789c53');
define('STRIPE_SECRET_KEY', 'sk_test_51QqbMxRvmM0pDHQRmN020VSXACCuV2l2lxYQXQ5tiHYUVcbLMxFgnTxK04RhMZssFUdGytSdsVbVzU1updQwSkDY00bw8oKgnN');
define('STRIPE_WEBHOOK_SECRET', '');

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>