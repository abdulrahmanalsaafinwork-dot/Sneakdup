<?php
$servername = getenv('DB_HOST') ?: (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : "localhost");
$username = getenv('DB_USER') ?: (isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : "root");
$password = getenv('DB_PASS') ?: (isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : "");
$dbname = getenv('DB_NAME') ?: (isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : "sneakdup");
$port = (int)(getenv('DB_PORT') ?: (isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : 3306));

if ($servername !== "localhost" && $servername !== "127.0.0.1") {
    $conn = mysqli_init();
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
    $connected = @$conn->real_connect($servername, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);
    if (!$connected) {
        $conn = new mysqli($servername, $username, $password, $dbname, $port);
    }
} else {
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
}

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}
?>
