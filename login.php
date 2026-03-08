<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

$conn = new mysqli(
    "localhost",
    "u991633922_financeuser",
    "WateryMarker19",
    "u991633922_finance"
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();   // ✅ ต้องมีบรรทัดนี้

if ($user && password_verify($password, $user['password'])) {

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];

    header("Location: dashboard.php");
    exit();
}

header("Location: login.html?error=invalid");
exit();
?>
