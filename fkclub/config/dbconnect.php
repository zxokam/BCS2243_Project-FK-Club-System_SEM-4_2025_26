<?php
$host = "10.26.30.17";
$user = "cb24070";
$password = "cb24070";
$database = "cb24070";
$port = 3306;

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    die("Database connection failed<br>
    Host: $host<br>
    User: $user<br>
    Database: $database<br>
    Port: $port<br>
    Error: " . mysqli_connect_error());
}
?>

