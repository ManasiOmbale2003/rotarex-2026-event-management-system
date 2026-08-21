<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

include 'config.php'; 
date_default_timezone_set('Asia/Kolkata');

$user_email = $_SESSION['user'];
$message_status = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_ticket'])) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    // File upload logic
    $target_dir = "uploads/tickets/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name = time() . '_' . basename($_FILES["screenshot"]["name"]);
    $target_file = $target_dir . $file_name;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Allow certain file formats
    $allowTypes = array('jpg', 'png', 'jpeg', 'pdf');
    
    if (in_array($fileType, $allowTypes)) {
        if (move_uploaded_file($_FILES["screenshot"]["tmp_name"], $target_file)) {
            // UPDATED QUERY: Explicitly set status to 'received' so admin dashboard can see it
            $stmt = $conn->prepare("INSERT INTO support_tickets (user_email, subject, message, screenshot_path, status) VALUES (?, ?, ?, ?, 'received')");
            $stmt->bind_param("ssss", $user_email, $subject, $message, $target_file);
            
            if ($stmt->execute()) {
                $message_status = "success";
            } else {
                $message_status = "error";
            }
        } else {
            $message_status = "upload_fail";
        }
    } else {
        $message_status = "invalid_file";
    }
}

// Fetch user data for sidebar consistency
$stmt = $conn->prepare("SELECT fullname FROM users WHERE email=?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$display_name = $res['fullname'] ?? $user_email;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Ticket | InnovaHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.9); /* Increased opacity for black text contrast */
            --glass-border: #000000; /* Black border for container */
            --purple-glow: #c084fc;
            --text-light: #000000; /* Black text color */
            --success-green: #22c55e;
            --error-red: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        body {
            min-height: 100vh;
            background: #0f172a url("assets/images/img.jpeg") no-repeat center center fixed;
            background-size: cover;
            color: var(--text-light);
        }

        .sidebar {
            position: fixed; top: 0; left: 0; width: 260px; height: 100%;
            background: rgba(15, 3, 40, 0.6); backdrop-filter: blur(25px);
            border-right: 1px solid rgba(255, 255, 255, 0.18); padding-top: 25px; z-index: 1000;
        }

        .sidebar h2 { text-align: center; color: #fff; margin-bottom: 30px; font-weight: 700; }
        .sidebar ul { list-style: none; }
        .sidebar ul li { margin: 8px 15px; }
        .sidebar ul li a { display: flex; align-items: center; padding: 14px 18px; color: #fff; text-decoration: none; border-radius: 12px; transition: 0.3s; }
        .sidebar ul li a:hover { background: rgba(192, 132, 252, 0.25); color: var(--purple-glow); }

        .main-content { margin-left: 260px; padding: 40px; }

        .ticket-container {
            max-width: 700px; margin: 0 auto;
            background: var(--glass-bg); backdrop-filter: blur(16px);
            border: 2px solid var(--glass-border); border-radius: 25px;
            padding: 40px; box-shadow: 0 15px 50px rgba(0, 0, 0, 0.35);
            color: #000000;
        }

        h2 { color: #000000; margin-bottom: 20px; font-weight: 700; }
        
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; color: #000000; font-weight: 600; }
        
        input[type="text"], textarea, input[type="file"] {
            width: 100%; padding: 12px; border-radius: 10px;
            border: 2px solid #000000; background: #ffffff;
            color: #000000; font-size: 15px;
        }

        /* Input text and placeholder color */
        input[type="text"]::placeholder, textarea::placeholder {
            color: #555555;
        }

        textarea { height: 120px; resize: none; }

        .submit-btn {
            background: #000000; color: #ffffff;
            border: none; padding: 15px 30px; border-radius: 30px;
            font-weight: 700; cursor: pointer; transition: 0.3s; width: 100%;
        }

        .submit-btn:hover { transform: scale(1.02); background: #333333; }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .alert-success { background: rgba(34, 197, 94, 0.2); border: 1px solid var(--success-green); color: var(--success-green); }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid var(--error-red); color: var(--error-red); }

        @media(max-width: 992px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>InnovaHub</h2>
    <ul>
        <li><a href="dashboard.php"><i class="fa-solid fa-house"></i>&nbsp; Dashboard</a></li>
        <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>&nbsp; Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="ticket-container">
        <h2>Raise a Support Ticket</h2>
        <p style="margin-bottom: 25px; font-size: 14px; opacity: 1; color: #000000;">If you face any issues with payment or registration, fill this form and attach a screenshot of the relevant section.</p>

        <?php if($message_status == "success"): ?>
            <div class="alert alert-success">Ticket raised successfully! Admin will review it shortly.</div>
        <?php elseif($message_status == "error"): ?>
            <div class="alert alert-error">Something went wrong. Please try again.</div>
        <?php elseif($message_status == "invalid_file"): ?>
            <div class="alert alert-error">Invalid file format. Only JPG, PNG, and PDF are allowed.</div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" placeholder="e.g. Payment Verification Issue" required>
            </div>

            <div class="form-group">
                <label>Detailed Message</label>
                <textarea name="message" placeholder="Explain the problem you are facing..." required></textarea>
            </div>

            <div class="form-group">
                <label>Attach Dashboard Screenshot / PDF</label>
                <input type="file" name="screenshot" accept="image/*,.pdf" required>
            </div>

            <button type="submit" name="submit_ticket" class="submit-btn">SUBMIT TICKET</button>
        </form>
    </div>
</div>

</body>
</html>