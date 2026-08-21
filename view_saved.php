<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- DB CONNECTION ---------- */
require_once('config.php'); // Included external database configuration

if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user'];

// Fetch registration details and Unique ID from users table
$query = "SELECT r.*, u.unique_reg_id 
          FROM registrations r 
          JOIN users u ON r.user_email = u.email 
          WHERE r.user_email = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('No registration data found!'); window.location.href='step1.php';</script>";
    exit();
}

$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Registration | InnovaHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
        body{
            min-height:100vh;
            background: linear-gradient(-45deg, #0d1b2a, #1b263b, #415a77, #0d1b2a);
            display:flex;
            justify-content:center;
            align-items:center;
            padding: 20px;
        }
        .view-card{
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }
        h2{text-align:center; color:#00ffff; margin-bottom:10px; text-transform: uppercase; letter-spacing: 2px;}
        p.subtitle{text-align: center; color: #ccc; margin-bottom: 30px; font-size: 0.9rem;}
        
        .info-group {
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(0, 255, 255, 0.1);
            padding-bottom: 10px;
        }
        .label {
            display: block;
            color: #00ffff;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .value {
            display: block;
            color: #fff;
            font-size: 1.1rem;
            margin-top: 5px;
        }
        .reg-id-badge {
            background: rgba(0, 255, 255, 0.2);
            color: #00ffff;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 30px;
            border: 1px dashed #00ffff;
        }
        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-edit { background: #00ffff; color: #0d1b2a; }
        .btn-dash { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid #fff; }
        .btn:hover { opacity: 0.8; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="view-card">
    <h2>Registration Details</h2>
    <p class="subtitle">Confirmation of your Step 1 Submission</p>

    <div class="reg-id-badge">
        ID: <?php echo htmlspecialchars($data['unique_reg_id']); ?>
    </div>

    <div class="info-group">
        <span class="label">Program</span>
        <span class="value"><?php echo htmlspecialchars($data['program']); ?></span>
    </div>

    <div class="info-group">
        <span class="label">Discipline</span>
        <span class="value"><?php echo htmlspecialchars($data['discipline']); ?></span>
    </div>

    <div class="info-group">
        <span class="label">Theme</span>
        <span class="value"><?php echo htmlspecialchars($data['theme']); ?></span>
    </div>

    <div class="info-group">
        <span class="label">District / Region</span>
        <span class="value"><?php echo htmlspecialchars($data['region']); ?></span>
    </div>

    <div class="info-group">
        <span class="label">Email Address</span>
        <span class="value"><?php echo htmlspecialchars($data['user_email']); ?></span>
    </div>

    <div class="btn-container">
      
        <a href="dashboard.php" class="btn btn-dash">Dashboard</a>
    </div>
</div>

</body>
</html>