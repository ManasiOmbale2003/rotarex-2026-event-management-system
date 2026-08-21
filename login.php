<?php
// Check if session is already active to prevent notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php'; 

// Initialize $error to prevent "Undefined variable" notice on line 252
$error = "";

/* ---------- DEFAULT ADMIN CREDENTIALS ---------- */
$admin_email = "rotraxproject@gmail.com";
$admin_password = "rotrax@123"; // you can change

if(isset($_POST['login'])){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    /* ---------- ADMIN LOGIN ---------- */
    if($email === $admin_email && $password === $admin_password){
        $_SESSION['admin'] = $admin_email;
        // Adding a role based session for extra security
        $_SESSION['role'] = 'admin'; 
        header("Location: admin_dashboard.php");
        exit();
    }

    /* ---------- USER LOGIN ---------- */
    $stmt = $conn->prepare("SELECT password, email_verified FROM users WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){

        if(!$row['email_verified']){
            $error = "Email not verified. Please verify your email first.";
        }
        elseif(password_verify($password, $row['password'])){
            $_SESSION['user'] = $email;
            $_SESSION['role'] = 'user';
            header("Location: dashboard.php");
            exit();
        }
        else{
            $error = "Invalid password.";
        }

    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>InnovaHub | Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

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
}

body{
    display:flex;
    justify-content:center;
    align-items:center;
    background:url("/rotrax/assets/images/img.jpeg") no-repeat center center fixed;
    background-size:cover;
    padding: 20px;
}

/* ---------- Card ---------- */
.form-box{
    width:100%;
    max-width:420px;
    padding:115px 25px 35px; /* Adjust top padding for logo space */
    border-radius:22px;
    background:rgba(255,255,255,0.12);
    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,0.2);
    box-shadow:0 18px 45px rgba(0,0,0,0.55);
    color:#fff;
    position: relative;
    margin: auto;
}

/* ---------- Corner Logos ---------- */
.yashoda-logo {
    position: absolute;
    top: 15px;
    left: 15px;
    width: 50px; 
    height: auto;
}

.rotrax-logo {
    position: absolute;
    top: 5px;
    right: 10px;
    width: 130px; 
    height: auto;
}

/* ---------- Title ---------- */
.form-box h2{
    text-align:center;
    margin-bottom:20px;
    font-size:26px;
    font-weight:700;
    background:linear-gradient(90deg,#c77dff,#9d4edd,#5a189a);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    text-shadow:0 0 8px rgba(157,78,221,0.6);
}

/* HEADER TEXT */
.college-header{
    text-align:center;
    margin-bottom:15px;
}

.college-header h3{
    font-size:10px;
    letter-spacing:0.5px;
    color:#222; 
}

.college-header h1{
    font-size:17px;
    font-weight:800;
    color:#0b5ed7;
    line-height: 1.2;
    margin: 3px 0;
}

.college-header p{
    font-size:10px;
    color:#222;
}

.college-header h4{
    font-size:11px;
    color:#7b2cbf;
    font-weight:700;
    margin-top: 2px;
}

/* ---------- Inputs ---------- */
input{
    width:100%;
    padding:12px 14px;
    margin:10px 0;
    border-radius:12px;
    border:1px solid #000;
    outline:none;
    background:rgba(255,255,255,0.85); /* Slightly more solid for mobile readability */
    color:#000;
    font-size:16px; /* Prevents auto-zoom on iOS */
}

input::placeholder{
    color:#666;
}

/* ---------- Button ---------- */
button{
    width:100%;
    padding:14px;
    margin-top:15px;
    border:none;
    border-radius:30px;
    font-weight:600;
    font-size:15px;
    background:linear-gradient(45deg,#6a0dad,#b57edc,#ffd700);
    color:#1a0b2e;
    cursor:pointer;
    transition:0.3s;
    box-shadow:0 6px 20px rgba(181,126,220,0.4);
}

button:active{
    transform: scale(0.98);
}

/* ---------- Links ---------- */
p{
    text-align:center;
    margin-top:14px;
    font-size:0.9rem;
    color: #000;
}

a{
    color: #000;
    text-decoration:none;
    font-weight:600;
}

.form-box p:last-of-type{
    color: #7b2cbf;
    font-weight: 600;
}

/* ---------- Error ---------- */
.error{
    background:rgba(255,0,0,0.2);
    border-left:4px solid #ff4d4d;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
    color:#700;
    font-size:0.85rem;
    text-align: center;
}

/* ================= RESPONSIVE ================= */
@media (max-width: 480px){
    body{
        padding: 10px;
    }
    .form-box{
        padding: 95px 20px 30px;
    }
    .college-header h1{ font-size: 15px; }
    .rotrax-logo { width: 100px; top: 10px; }
    .yashoda-logo { width: 40px; }
    .form-box h2 { font-size: 22px; }
}
</style>
</head>

<body>

<div class="form-box">

    <img src="/rotrax/assets/images/Yashoda.png" class="yashoda-logo" alt="Yashoda Logo">
    <img src="/rotrax/assets/images/logooo.png" class="rotrax-logo" alt="ROTRAX Logo">

    <div class="college-header">
        <h3>Yashoda Shikshan Prasarak Mandal's</h3>
        <h1>YASHODA TECHNICAL CAMPUS, SATARA</h1>
        <p>Approved by AICTE, PCI & Govt. of Maharashtra</p>
        <h4>ROTARY CLUB OF SATARA</h4>
    </div>

    <?php if($error!=""): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post">
        <h2>Login</h2>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <p><a href="forgot_password.php">Forgot Password?</a></p>
    <p><a href="registration.php">New User? Register</a></p>
</div>

</body>
</html>