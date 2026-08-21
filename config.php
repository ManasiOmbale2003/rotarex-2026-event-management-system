<?php
$conn = new mysqli("localhost", "root", "root", "rotrax2026");

if ($conn->connect_error) {
    die("Database Connection Failed");
}

// Fixed: Check if session is already active before starting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>