<?php
// Database configuration
$host = 'localhost';
$db_name = 'POSTMAN';
$username = 'root'; 
$password = ''; 

// Create connection
$conn = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the city name from POST request
$city_name = $_POST['city_name'] ?? '';

// Fetch new data from the API
$api_url = "https://weatherapi-com.p.rapidapi.com/current.json?q={$city_name}";
$api_key = 'bed4324cd8msh754dd6be9b8dfcap1c95e3jsnb925ade23d82'; // Your new RapidAPI key

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "", // Empty for no specific encoding
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "x-rapidapi-host: weatherapi-com.p.rapidapi.com",
        "x-rapidapi-key: $api_key"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    $weather_data = json_decode($response, true);

    // Convert temperature to Celsius
    $tempCelsius = $weather_data['current']['temp_c'];

    // Prepare data for insertion
    $condition = $weather_data['current']['condition']['text'];
    $rain_chance = $weather_data['current']['cloud'];
    $wind_speed = $weather_data['current']['wind_kph'];
    $humidity = $weather_data['current']['humidity'];
    $visibility = $weather_data['current']['vis_km'];
    $pressure = $weather_data['current']['pressure_mb'];

    // Check if the data already exists in the database with all parameters
    $stmt = $conn->prepare("SELECT * FROM weather_searches WHERE city_name = ? AND temperature = ? AND conditions = ? AND rain_chance = ? AND wind_speed = ? AND humidity = ? AND visibility = ? AND pressure = ?");
    $stmt->bind_param("sdsdidsd", 
        $city_name, 
        $tempCelsius, 
        $condition, 
        $rain_chance, 
        $wind_speed, 
        $humidity, 
        $visibility, 
        $pressure
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        // If the exact data already exists, return it as JSON
        echo json_encode($data);
    } else {
        // If data doesn't exist, insert new data into the database
        $stmt = $conn->prepare("INSERT INTO weather_searches (city_name, temperature, CONDITIONs, rain_chance, wind_speed, humidity, visibility, pressure) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdsdidsd", 
            $city_name, 
            $tempCelsius, 
            $condition, 
            $rain_chance, 
            $wind_speed, 
            $humidity, 
            $visibility, 
            $pressure
        );
        $stmt->execute();

        // Return the newly inserted data
        $data = [
            'city_name' => $city_name,
            'temperature' => $tempCelsius,
            'condition' => $condition,
            'rain_chance' => $rain_chance,
            'wind_speed' => $wind_speed,
            'humidity' => $humidity,
            'visibility' => $visibility,
            'pressure' => $pressure
        ];

        echo json_encode($data);
    }
}

$stmt->close();
$conn->close();
?>
