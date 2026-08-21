<?php
session_start();

/* ---------- DB CONNECTION ---------- */
require_once('config.php'); // Included external database configuration

/* ---------- INPUT VALIDATION ---------- */
// We need the session to get the phone number for the SMS
if(!isset($_SESSION['reg'])){
    die("Session expired. Please register again.");
}

// Get Email and OTP (Securely handle inputs)
$email = $_POST['email'] ?? $_SESSION['reg']['email'];
$otp   = $_POST['otp'];
$phone = $_SESSION['reg']['phone']; // Needed for SMS

/* ---------- SECURE OTP CHECK ---------- */
// Using Prepared Statements to prevent SQL Injection
$stmt = $conn->prepare("SELECT * FROM otp_table WHERE email=? AND otp=?");
$stmt->bind_param("ss", $email, $otp);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    
    // Check if OTP is expired
    if(strtotime($row['expires_at']) >= time()){
        
        /* 1. MOVE DATA FROM SESSION TO DATABASE (Finalize Registration) */
        $fullname  = $_SESSION['reg']['fullname'];
        $alt_phone = $_SESSION['reg']['alt_phone'];
        $password  = $_SESSION['reg']['password'];
        $hash      = password_hash($password, PASSWORD_DEFAULT);

        $insert = $conn->prepare("INSERT INTO users (fullname, phone, alternate_phone, email, password, email_verified) VALUES (?,?,?,?,?,1)");
        $insert->bind_param("sssss", $fullname, $phone, $alt_phone, $email, $hash);
        
        if($insert->execute()){
            
            /* 2. SEND SUCCESS SMS */
            // SMS Credentials
            $api_key = "367E3CACE28824";
            $sender_id = "YSPMSR";
            $campaign_id = "14105";
            $route_id = "3";
            $pe_id = "1001539170000013821"; // Entity ID (Common for your organization)
            $template_id = "1007072890883656649"; // New Template ID you provided

            // Message: "Registration completed successfully!Now please complete all remaining steps to finalize your registration for Rotarex 2026.-YSPM Satara"
            // We replace {#numeric#} with 2026
            $message = "Registration completed successfully!Now please complete all remaining steps to finalize your registration for Rotarex 2026.-YSPM Satara";
            $sms_text = urlencode($message);

            $url = "https://jskbulkmarketing.in/app/smsapi/index.php?key=$api_key&campaign=$campaign_id&routeid=$route_id&type=text&contacts=$phone&senderid=$sender_id&msg=$sms_text&template_id=$template_id&pe_id=$pe_id";

            // Fire SMS via cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $output = curl_exec($ch);
            curl_close($ch);

            /* 3. CLEAN UP */
            // Remove OTP
            $del = $conn->prepare("DELETE FROM otp_table WHERE email=?");
            $del->bind_param("s", $email);
            $del->execute();

            // Clear registration session
            unset($_SESSION['reg']);
            unset($_SESSION['show_otp']);
            unset($_SESSION['otp_resent']);

            // Output success for your AJAX or Form handling
            echo "verified"; 
            
            // Optional: If this file is a direct form action, uncomment line below:
            // header("Location: login.php"); exit;

        } else {
            echo "Database Error: " . $conn->error;
        }

    } else { 
        echo "OTP Expired"; 
    }
} else { 
    echo "Invalid OTP"; 
}
?>