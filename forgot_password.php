<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$conn = new mysqli("localhost","root","root","rotrax2026");
if($conn->connect_error) die("DB Connection Error: ".$conn->connect_error);

// Initialize step
if(!isset($_SESSION['step'])){
    $_SESSION['step'] = 1;
}

/* -------- Step 1: Send OTP -------- */
if(isset($_POST['send_otp'])){
    $email = trim($_POST['email']);

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows==0){
        $error = "Email not registered";
    } else {
        // Generate OTP
        $otp = rand(100000,999999);
        $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        // Remove old OTPs
        $conn->query("DELETE FROM otp_table WHERE email='$email'");

        // Insert new OTP
        $insert = $conn->prepare("INSERT INTO otp_table(email,otp,expires_at) VALUES(?,?,?)");
        $insert->bind_param("sss",$email,$otp,$expires);
        $insert->execute();

        $_SESSION['reset_email'] = $email;
        $_SESSION['step'] = 2;

        // Send OTP via email
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = '';
            $mail->Password = ''; // Your app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('rotraxproject@gmail.com','ROTAREX 2026');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body = "Your OTP for password reset is <b>$otp</b>. It is valid for 10 minutes.";
            $mail->send();
        } catch (Exception $e){
            $error = "OTP could not be sent: ".$mail->ErrorInfo;
        }
    }
}

/* -------- Step 2: Verify OTP -------- */
if(isset($_POST['verify_otp'])){
    $entered_otp = trim($_POST['otp']);
    $email = $_SESSION['reset_email'];

    $stmt = $conn->prepare("SELECT otp,expires_at FROM otp_table WHERE email=? LIMIT 1");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if(!$row){
        $error = "OTP not found, please try again";
    } elseif($row['expires_at'] < date("Y-m-d H:i:s")){
        $error = "OTP expired, request again";
    } elseif($row['otp'] != $entered_otp){
        $error = "Invalid OTP";
    } else {
        $_SESSION['step'] = 3;
    }
}

/* -------- Step 3: Reset Password -------- */
if(isset($_POST['reset_password'])){
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if($password !== $confirm){
        $error = "Passwords do not match";
    } else {
        $hash = password_hash($password,PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];

        $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->bind_param("ss",$hash,$email);
        $stmt->execute();

        $conn->query("DELETE FROM otp_table WHERE email='$email'");
        session_destroy();

        echo "<script>alert('Password Reset Successful'); window.location='login.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password | InnovaHub</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

html, body{
    width:100%;
    min-height:100vh;
    overflow:hidden;
}

body{
    display:flex;
    justify-content:center;
    align-items:center;
    background:url("assets/images/im.jpeg") no-repeat center center;
    background-size:cover;
    position: relative;
}

/* ---------- Circulating Logo Background ---------- */
.bg-logo {
    position: fixed;
    top: 50%;
    left: 50%;
    width: 600px;
    height: 600px;
    background: url("assets/images/logoo.png") no-repeat center center;
    background-size: contain;
    opacity: 0.15;
    z-index: -1;
    transform: translate(-50%, -50%);
    animation: rotateLogo 20s linear infinite;
}

@keyframes rotateLogo {
    from { transform: translate(-50%, -50%) rotate(0deg); }
    to { transform: translate(-50%, -50%) rotate(360deg); }
}

/* ---------- Card ---------- */
.form-box{
    width:400px;
    padding:35px 30px;
    border-radius:22px;
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(16px);
    -webkit-backdrop-filter:blur(16px);
    border:1px solid rgba(255,255,255,0.18);
    box-shadow:0 18px 45px rgba(0,0,0,0.55);
    color:#fff;
    z-index: 10;
}

/* ---------- Title ---------- */
.form-box h2{
    text-align:center;
    margin-bottom:22px;
    font-size:26px;
    font-weight:700;
    background:linear-gradient(90deg,#c77dff,#9d4edd,#5a189a);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    text-shadow:0 0 8px rgba(157,78,221,0.6);
}

/* ---------- Inputs ---------- */
input{
    width:100%;
    padding:12px 14px;
    margin:10px 0;
    border-radius:12px;
    border:1px solid #000;
    outline:none;
    background:rgba(255,255,255,0.9);
    color:#000;
    font-size:0.95rem;
}

input::placeholder{
    color:#555;
}

/* ---------- Button ---------- */
button{
    width:100%;
    padding:13px;
    margin-top:15px;
    border:none;
    border-radius:30px;
    font-weight:600;
    font-size:15px;
    background:linear-gradient(45deg,#6a0dad,#b57edc,#ffd700);
    color:#1a0b2e;
    cursor:pointer;
    transition:0.3s;
    box-shadow:0 6px 20px rgba(181,126,220,0.6);
}

button:hover{
    transform:translateY(-2px) scale(1.02);
}

/* ---------- Links ---------- */
p{
    text-align:center;
    margin-top:14px;
    font-size:0.9rem;
    color: #fff;
}

a{
    color: #ffd700;
    text-decoration:none;
    font-weight:600;
}

a:hover{
    text-decoration:underline;
}

/* ---------- Error ---------- */
.error-msg{
    background:rgba(255,0,0,0.2);
    border-left:4px solid #ff4d4d;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
    color:#fff;
    font-size:0.9rem;
    text-align: center;
}

/* Success Message Override */
.success-header {
    color: #00ff88 !important;
    text-align: center;
}

/* Responsive */
@media (max-width: 480px){
    .form-box{ width:90%; padding:24px 18px; }
    .bg-logo { width: 300px; height: 300px; }
}
</style>
</head>
<body>

<div class="bg-logo"></div>

<div class="form-box">
    <h2>Forgot Password</h2>

    <?php if(isset($error)): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if($_SESSION['step']==1): ?>
    <form method="post">
        <input type="email" name="email" placeholder="Enter Registered Email" required>
        <button type="submit" name="send_otp">Send OTP</button>
    </form>
    <p><a href="login.php">Back to Login</a></p>
    <?php endif; ?>

    <?php if($_SESSION['step']==2): ?>
    <p style="margin-bottom: 10px;">OTP sent to: <b><?php echo $_SESSION['reset_email']; ?></b></p>
    <form method="post">
        <input type="text" name="otp" placeholder="Enter 6-Digit OTP" required maxlength="6">
        <button type="submit" name="verify_otp">Verify OTP</button>
    </form>
    <p><a href="forgot_password.php">Resend OTP</a></p>
    <?php endif; ?>

    <?php if($_SESSION['step']==3): ?>
    <form method="post">
        <input type="password" name="password" placeholder="New Password" required minlength="6">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6">
        <button type="submit" name="reset_password">Reset Password</button>
    </form>
    <?php endif; ?>

</div>

</body>
</html>