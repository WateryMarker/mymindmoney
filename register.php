<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);


$conn = new mysqli(
    "localhost",
    "u991633922_financeuser",
    "WateryMarker19",
    "u991633922_finance"
);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);


$check = $conn->prepare("SELECT * FROM users WHERE email = ?");
if (!$check) {
    die("❌ Prepare failed: " . $conn->error); // 🔍 แสดง error แบบชัดเจน
}

$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "<script>alert('Email นี้ถูกใช้แล้ว'); window.history.back();</script>";
} else {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    if (!$stmt) {
        die("❌ Prepare failed (insert): " . $conn->error); // 🔍 ถ้ามี error ตรงนี้ จะบอกว่า insert ผิด
    }

    $stmt->bind_param("sss", $username, $email, $password);
    $stmt->execute();

    echo "<script>alert('สมัครสมาชิกสำเร็จ!'); window.location.href='login.html';</script>";
}
$conn->close();
?>
