<?php
require_once '../config/db.php';

class Open_weather {
    private $apikey = '2d2413eb7d7c7fbf3f453edb70789c53';
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function updateWeatherData() {
        $stmt = $this->conn->prepare("SELECT location_id, name, latitude, longitude FROM locations");
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($locations as $location) {
            $weatherData = $this->fetchWeatherData($location['latitude'], $location['longitude']);

            if($weatherData) {
                $this->saveWeatherData($location['location_id'], $weatherData);
            }
        }
    }

    private function fetchWeatherData($lat, $lng) {
        $url = "http://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={&lng}&appid={$this->apikey}&units=metric";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if($response) {
            $data = json_decode($response, true);
            return [
                'temperature' => $data['main']['temp'],
                'weather_condition' => $data['weather'][0]['main']
            ];
        }
        return null;
    }

    private function saveWeatherData($locationId, $weatherData) {
        $stmt = $this->conn->prepare("
                INSERT INTO weather_data (location_id, temperature, weather_condition)
                VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE
                temperature = VALUES(temperature),
                weather_condition = VALUES(weather_condition),
                last_updated = NOW()
                ");

        $stmt->execute([$locationId, $weatherData['temperature'], $weatherData['weather_condition']]);
    }

    public function getLocationWeather($locationId) {
        $stmt = $this->conn->prepare("
                SELECT temperature, weather_condition, last_updated
                FROM weather_data
                WHERE location_id = ?
                ORDER BY last_updated DESC
                LIMIT 1
                ");
                
        $stmt->execute([$locationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>