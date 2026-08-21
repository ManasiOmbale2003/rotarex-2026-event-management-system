<?php
session_start();

/* ===============================
   SHOW MYSQL ERRORS (DEV MODE)
   =============================== */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* ---------- DB CONNECTION ---------- */
require_once('config.php'); // Included external database configuration

/* ===============================
   GET & CLEAN INPUT
   =============================== */
$fullname = trim($_POST['fullname'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

/* ===============================
   BASIC VALIDATION
   =============================== */
if($fullname === '' || $email === '' || $password === ''){
    echo "<script>
            alert('All fields are required!');
            window.history.back();
          </script>";
    exit();
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    echo "<script>
            alert('Invalid email format!');
            window.history.back();
          </script>";
    exit();
}

/* ===============================
   DUPLICATE EMAIL CHECK
   =============================== */
$chk = $conn->prepare("SELECT id FROM users WHERE email=?");
$chk->bind_param("s",$email);
$chk->execute();
$res = $chk->get_result();

if($res->num_rows > 0){
    echo "<script>
            alert('This email is already registered!');
            window.history.back();
          </script>";
    exit();
}

/* ===============================
   HASH PASSWORD
   =============================== */
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

/* ===============================
   INSERT USER
   =============================== */
$stmt = $conn->prepare("
    INSERT INTO users (fullname,email,password,email_verified)
    VALUES (?,?,?,1)
");
$stmt->bind_param("sss",$fullname,$email,$hashed_password);
$stmt->execute();

/* ===============================
   SUCCESS MESSAGE
   =============================== */
echo "<script>
        alert('User registered successfully!');
        window.location.href='login.php';
      </script>";
exit();
?>