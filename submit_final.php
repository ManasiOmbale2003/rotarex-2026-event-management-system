<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

/* ===== DATABASE CONNECTION ===== */
require_once('config.php'); // Included external database configuration

$user_email = $_SESSION['user'];
$error = "";
$success = false;

// DEBUG: Uncomment the line below if you want to see what email is being searched
// echo "Searching for: " . $user_email; 

// 1. Fetch the existing payment record
// We use 'email' column and check for 'Success' status
$stmt = $conn->prepare("SELECT * FROM registration_payments WHERE email = ? AND (status = 'Success' OR status = 'captured') LIMIT 1");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$payment_data = $stmt->get_result()->fetch_assoc();

if (!$payment_data) {
    // If we can't find it by email, let's see if the table exists but is empty
    $check_table = $conn->query("SELECT id FROM registration_payments LIMIT 1");
    if (!$check_table) {
        die("Error: Table 'registration_payments' does not exist in the database.");
    }
    die("No successful payment found for <b>" . htmlspecialchars($user_email) . "</b>. Please ensure your payment was successful in Step 4.");
}

// 2. Handle the Final Submission (Receipt Upload)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['receipt'])) {
    $target_dir = "uploads/receipts/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_ext = strtolower(pathinfo($_FILES["receipt"]["name"], PATHINFO_EXTENSION));
    $file_name = "Receipt_" . time() . "_" . $payment_data['payment_id'] . "." . $file_ext;
    $target_file = $target_dir . $file_name;

    // Validate file type
    $allowed = array("pdf", "jpg", "jpeg", "png");
    if (!in_array($file_ext, $allowed)) {
        $error = "Invalid file type. Only PDF, JPG, and PNG are allowed.";
    } else {
        if (move_uploaded_file($_FILES["receipt"]["tmp_name"], $target_file)) {
            $update = $conn->prepare("UPDATE registration_payments SET receipt_file = ?, final_submitted = 1 WHERE email = ?");
            $update->bind_param("ss", $target_file, $user_email);
            if ($update->execute()) {
                $success = true;
            } else {
                $error = "Database update failed: " . $conn->error;
            }
        } else {
            $error = "File upload failed. Check folder permissions.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>InnovaHub | Final Submission</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --purple: #a855f7; }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
        body {
            background: url("assets/images/im.jpeg") center/cover no-repeat fixed;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .box {
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(15px);
            padding: 40px; border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.3);
            max-width: 500px; width: 100%; text-align: center; color: #000;
        }
        h2 { margin-bottom: 20px; border-bottom: 2px solid var(--purple); padding-bottom: 10px; }
        .btn {
            background: var(--purple); color: white; padding: 12px 25px; 
            border: none; border-radius: 12px; cursor: pointer; text-decoration: none; display: inline-block;
            margin-top: 20px; font-weight: bold; width: 100%;
        }
        input[type="file"] { 
            margin: 20px 0; display: block; width: 100%; 
            background: rgba(255,255,255,0.5); padding: 10px; border-radius: 8px;
        }
        .success-msg { color: #15803d; font-weight: bold; }
    </style>
</head>
<body>

<div class="box">
    <?php if ($success): ?>
        <h2 class="success-msg">Registration Complete! 🎉</h2>
        <p>Thank you, <strong><?php echo htmlspecialchars($payment_data['fullname']); ?></strong>.</p>
        <p>Your receipt has been uploaded. Your registration is now being processed.</p>
        <a href="dashboard.php" class="btn">Go to Dashboard</a>
    <?php else: ?>
        <h2>Submit Final Receipt</h2>
        <p>Payment ID: <strong><?php echo htmlspecialchars($payment_data['payment_id']); ?></strong></p>
        
        <?php if ($error): ?> <p style="color: red;"><?php echo $error; ?></p> <?php endif; ?>

        <form action="submit_final.php" method="POST" enctype="multipart/form-data">
            <label style="font-weight: 600;">Upload your Razorpay Receipt (PDF/JPG):</label>
            <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png" required>
            <button type="submit" class="btn">Submit Final Registration</button>
        </form>
        <div style="margin-top: 15px;">
            <a href="dashboard.php" style="font-size: 13px; color: #333; text-decoration: none;">← Back to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>