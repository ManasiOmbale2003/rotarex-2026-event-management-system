<?php
// Include your DB connection
$conn = new mysqli("localhost","root","root","rotrax2026");
if($conn->connect_error){
    die("Database Connection Failed");
}

$email = $_GET['email'] ?? '';
$email = trim($email);

if(filter_var($email, FILTER_VALIDATE_EMAIL)){
    $email = $conn->real_escape_string($email);
    $res = $conn->query("SELECT fullname FROM users WHERE email='$email' LIMIT 1");
    if($res && $res->num_rows > 0){
        $row = $res->fetch_assoc();
        echo json_encode(['fullname' => $row['fullname']]);
        exit;
    }
}

echo json_encode(['fullname' => '']);
exit;
?>
