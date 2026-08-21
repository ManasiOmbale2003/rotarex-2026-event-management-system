<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

/* ===== DATABASE CONNECTION ===== */
require_once('config.php'); // Included external database configuration

if($conn->connect_error){ die("Connection failed"); }

$user_email = $_SESSION['user'];

// Fetch Team and Member details
$query = "SELECT t.team_name, t.college_name, t.pass_token, m.student_name, m.photo_path, m.branch, m.year 
          FROM team_information t 
          JOIN team_members m ON t.id = m.team_id 
          WHERE t.user_email = ? LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    echo "Please complete Step 2 first.";
    exit();
}

$data = $result->fetch_assoc();

// QR Code URL (Points to your verification script for the waiter)
$qr_data = "https://yourdomain.com/verify_meal.php?token=" . $data['pass_token'];
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ROTAREX 2026 | Entry Pass</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f4f9; display: flex; flex-direction: column; align-items: center; padding: 50px; }
        
        .pass-card {
            width: 350px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 2px solid #000;
            text-align: center;
            position: relative;
        }

        .header { background: #000; color: #fff; padding: 15px; }
        .header h2 { margin: 0; font-size: 20px; letter-spacing: 2px; }
        .header p { margin: 0; font-size: 10px; text-transform: uppercase; }

        .photo-section { margin-top: 20px; }
        .photo-section img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid #f0f0f0;
        }

        .details { padding: 20px; }
        .details h3 { margin: 5px 0; color: #333; font-size: 22px; }
        .details p { margin: 2px 0; font-size: 14px; color: #666; }
        .team-tag { font-weight: bold; color: #a855f7; margin-top: 5px; display: block; }

        .qr-section {
            background: #f9f9f9;
            padding: 20px;
            border-top: 1px dashed #ccc;
        }
        .qr-section img { width: 130px; }
        .qr-label { font-size: 11px; font-weight: bold; margin-top: 10px; color: #444; }

        .btn-print {
            margin-top: 30px;
            padding: 12px 30px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        @media print {
            .btn-print { display: none; }
            body { background: none; padding: 0; }
            .pass-card { box-shadow: none; border: 1px solid #000; }
        }
    </style>
</head>
<body>

    <div class="pass-card">
        <div class="header">
            <h2>ROTAREX 2026</h2>
            <p>Official Entry Pass</p>
        </div>

        <div class="photo-section">
            <img src="<?php echo htmlspecialchars($data['photo_path']); ?>" alt="Student Photo">
        </div>

        <div class="details">
            <h3><?php echo htmlspecialchars($data['student_name']); ?></h3>
            <p><?php echo htmlspecialchars($data['branch']); ?> - <?php echo htmlspecialchars($data['year']); ?> Year</p>
            <p><?php echo htmlspecialchars($data['college_name']); ?></p>
            <span class="team-tag">Team: <?php echo htmlspecialchars($data['team_name']); ?></span>
        </div>

        <div class="qr-section">
            <img src="<?php echo $qr_url; ?>" alt="Meal QR Code">
            <div class="qr-label">WAITER: SCAN FOR MEALS</div>
        </div>
    </div>

    <button class="btn-print" onclick="window.print()">Print Entry Pass</button>
    <a href="dashboard.php" style="margin-top: 15px; color: #666; text-decoration: none; font-size: 14px;">Back to Dashboard</a>

</body>
</html>