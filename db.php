<?php
// Database connection
$host = "localhost";
$username = "kasunpre_cncfile";
$password = "Kasun0147?";
$database = "kasunpre_cncfile";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>