<?php
// Start session at the very top to prevent notices and header errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== DATABASE CONNECTION ===== */
require_once('config.php'); // Included external database configuration

if($conn->connect_error){ die("Connection failed"); }

$message = "";
$status_type = ""; // success or error

// Handle session messages after redirect
if (isset($_SESSION['meal_msg'])) {
    $message = $_SESSION['meal_msg'];
    $status_type = $_SESSION['meal_status'];
    unset($_SESSION['meal_msg']);
    unset($_SESSION['meal_status']);
}

if(isset($_GET['token'])){
    $token = trim($_GET['token']); // Added trim to ensure no whitespace issues

    // 1. Find the team and member associated with this token
    // UPDATED: Using a LEFT JOIN to ensure the member is found even if team info has a slight link delay
    $stmt = $conn->prepare("SELECT m.id, m.student_name, m.breakfast_served, m.lunch_served, t.team_name 
                            FROM team_members m 
                            LEFT JOIN team_information t ON m.team_id = t.id 
                            WHERE m.member_id_code = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();

    if(!$member){
        $message = "Invalid Pass! Record not found.";
        $status_type = "error";
    } else {
        // 2. Handle Meal Marking Logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meal_type'])) {
            $meal = $_POST['meal_type']; // 'breakfast_served' or 'lunch_served'
            
            // Security check: only allow these two columns
            if ($meal === 'breakfast_served' || $meal === 'lunch_served') {
                
                // Check if already served
                if ($member[$meal] == 1) {
                    $message = "Alert: Meal already served for this student!";
                    $status_type = "error";
                } else {
                    $update = $conn->prepare("UPDATE team_members SET $meal = 1 WHERE id = ?");
                    $update->bind_param("i", $member['id']);
                    if ($update->execute()) {
                        // Success - Set session message and redirect to prevent form resubmission on refresh
                        $_SESSION['meal_msg'] = "Success: " . ucfirst(str_replace('_served', '', $meal)) . " marked as served!";
                        $_SESSION['meal_status'] = "success";
                        header("Location: verify_meal.php?token=" . urlencode($token));
                        exit();
                    }
                }
            }
        }
    }
} else {
    die("No token provided.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiter Verification</title>
    <style>
        body { 
            font-family: sans-serif; 
            text-align: center; 
            padding: 20px; 
            background: url('assets/images/img.jpeg') no-repeat center center fixed; 
            background-size: cover;
        }
        .container { background: rgba(255, 255, 255, 0.95); padding: 20px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); max-width: 400px; margin: auto; }
        .student-info { margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .btn { 
            display: block; width: 100%; padding: 20px; margin: 10px 0; 
            font-size: 18px; font-weight: bold; border: none; border-radius: 10px; 
            color: white; cursor: pointer; transition: transform 0.2s;
        }
        .btn:active { transform: scale(0.98); }
        .btn-breakfast { background: #4caf50; }
        .btn-lunch { background: #2196f3; }
        .btn:disabled { background: #ccc; cursor: not-allowed; opacity: 0.7; }
        .status { padding: 15px; border-radius: 8px; margin-bottom: 15px; font-weight: bold; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        h2 { color: #333; margin: 5px 0; }
        p { color: #666; margin: 5px 0; }
    </style>
</head>
<body>

<div class="container">
    <h1>Meal Verification</h1>

    <?php if($message): ?>
        <div class="status <?php echo $status_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if($member): ?>
        <div class="student-info">
            <h2><?php echo htmlspecialchars($member['student_name']); ?></h2>
            <p><strong>Team:</strong> <?php echo htmlspecialchars($member['team_name'] ?? 'N/A'); ?></p>
            <p>Token: <?php echo htmlspecialchars($token); ?></p>
        </div>

        <form method="POST">
            <button type="submit" name="meal_type" value="breakfast_served" class="btn btn-breakfast" 
                <?php echo ($member['breakfast_served'] == 1) ? 'disabled' : ''; ?>>
                <?php echo ($member['breakfast_served'] == 1) ? 'Breakfast Done ✅' : 'Serve Breakfast 🍳'; ?>
            </button>

            <button type="submit" name="meal_type" value="lunch_served" class="btn btn-lunch" 
                <?php echo ($member['lunch_served'] == 1) ? 'disabled' : ''; ?>>
                <?php echo ($member['lunch_served'] == 1) ? 'Lunch Done ✅' : 'Serve Lunch 🍱'; ?>
            </button>
        </form>
    <?php endif; ?>

    <p style="margin-top: 20px; font-size: 12px; color: #aaa;">ROTAREX 2026</p>
</div>

</body>
</html>