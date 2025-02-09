<?php
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("
        SELECT vehicle_id, is_available
        FROM vehicles
        WHERE is_available = TRUE
    ");
    $stmt->execute();
    $available_vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'available_vehicles' => $available_vehicles
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch vehicle status'
    ]);
}
?>