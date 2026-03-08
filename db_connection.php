<?php
$host = "localhost";
$user = "u991633922_financeuser";
$pass = "WateryMarker19";
$db   = "u991633922_finance";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
