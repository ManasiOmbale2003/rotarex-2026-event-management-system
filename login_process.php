<?php
session_start();

/* ---------- DB CONNECTION ---------- */
require_once('config.php'); // Included external database configuration

$email = $_POST['email'];
$password = $_POST['password'];

$res = $conn->query("SELECT * FROM users WHERE email='$email'");
if($res->num_rows>0){
    $row = $res->fetch_assoc();
    if(password_verify($password,$row['password'])){
        $_SESSION['user'] = $row['id'];
        echo "success";
    } else { echo "Invalid Password"; }
} else { echo "Email not registered"; }
?>