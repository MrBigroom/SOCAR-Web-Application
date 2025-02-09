<?php
require_once '../config/db.php';
require_once '../services/open_weather.php';

header('Content-Type: application/json');

if(!isset($_GET['location_id'])) {
    http_response_code(400);
    echo json_encode(array('error' => 'Location ID is required'));
    exit;
}

try {
    $location_id = filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT);
    $openWeather = new Open_weather($conn);
    $currentWeather = $openWeather->getLocationWeather(($locationId));

    if(!$currentWeather || (strtotime('now') - strtotime($currentWeather['last-updated'])) > 1800) {
        $openWeather->updateWeatherData();
        $currentWeather = $openWeather->getLocationWeather($locationId);
    }

    if($currentWeather) {
        echo json_encode([
            'temperature' => $currentWeather['temperature'],
            'weather_condition' => $currentWeather['weather_condition'],
            'last_updated' => $currentWeather['last-updated']
        ]);
    } else {
        throw new Exception('Weather data not available.');
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>