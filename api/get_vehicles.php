<?php
require 'config/db.php';

$stmt = $conn->prepare("
        SELECT v.*, l.name AS location.name, l.latitude, l.longitude
        FROM vehicles v
        JOIN locations l ON v.location_id = l.location_id
        WHERE v.is_available = TRUE
        ");
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($vehicles);
?>