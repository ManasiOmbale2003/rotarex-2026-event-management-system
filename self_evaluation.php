<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
$user_email = $_SESSION['user'];

/* ===== DATABASE CONNECTION ===== */
require_once('config.php'); // Included external database configuration

if($conn->connect_error) die("DB Error");

/* ===== SECURITY CHECK: 15 DAYS VERIFICATION (DEACTIVATED) ===== */
$stmt = $conn->prepare("SELECT created_at FROM users WHERE email=?");
$stmt->bind_param("s",$user_email);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// FIX: Check if user_data exists before accessing 'created_at'
if (!$user_data) {
    die("Error: User record not found in database.");
}

$reg_date = $user_data['created_at'];

/* $days_passed = floor((time() - strtotime($reg_date)) / (60 * 60 * 24));
if ($days_passed < 15) {
    die("Access Denied: This section unlocks 15 days after registration. Remaining: " . (15 - $days_passed) . " days.");
}
*/

/* ===== HANDLE FILE UPLOAD ===== */
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['self_eval'])) {
    $target_dir = "uploads/evaluations/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    $file_ext = strtolower(pathinfo($_FILES["self_eval"]["name"], PATHINFO_EXTENSION));
    
    // UPDATED VALIDATION: Check if file extension is PDF
    if ($file_ext !== "pdf") {
        $message = "<p style='color:#ef4444; font-weight:600; margin-bottom:15px;'>Error: Only PDF files are allowed. Please convert your Word document to PDF before uploading.</p>";
    } else {
        $file_name = "EVAL_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["self_eval"]["tmp_name"], $target_file)) {
            $update = $conn->prepare("UPDATE project_proposals SET self_eval_file=? WHERE user_email=?");
            $update->bind_param("ss", $target_file, $user_email);
            if ($update->execute()) {
                $message = "<p style='color:#22c55e; font-weight:600; margin-bottom:15px;'><i class='fa-solid fa-check-circle'></i> File uploaded successfully!</p>";
            } else {
                $message = "<p style='color:#ef4444; font-weight:600; margin-bottom:15px;'>Database update failed.</p>";
            }
        } else {
            $message = "<p style='color:#ef4444; font-weight:600; margin-bottom:15px;'>File upload failed.</p>";
        }
    }
}

// Fetch existing file if any
$check = $conn->query("SELECT self_eval_file FROM project_proposals WHERE user_email='$user_email'");
$existing_file = ($check && $row = $check->fetch_assoc()) ? $row['self_eval_file'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Self Evaluation - InnovaHub</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<style>
:root{
  --glass-bg: rgba(255,255,255,0.08);
  --glass-border: rgba(255,255,255,0.18);
  --purple-glow: #c084fc;
  --text-light: #ffffff;
}

body{
  min-height:100vh;
  background: #0f172a url("assets/images/img.jpeg") no-repeat center center fixed;
  background-size:cover;
  color:var(--text-light);
  font-family:'Poppins',sans-serif;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.upload-container {
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(20px);
  padding: 40px;
  border-radius: 25px;
  border: 1px solid var(--glass-border);
  max-width: 550px;
  width: 100%;
  text-align: center;
  box-shadow: 0 20px 50px rgba(0,0,0,0.5);
}

h2 { color: var(--purple-glow); margin-bottom: 10px; font-weight: 700; }
.desc { font-size: 14px; margin-bottom: 25px; opacity: 0.8; line-height: 1.6; }

/* Download Section Styling */
.download-section {
  background: rgba(192, 132, 252, 0.1);
  border: 1px dashed var(--purple-glow);
  padding: 20px;
  border-radius: 15px;
  margin-bottom: 30px;
}

.download-btn {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: #fff;
  color: #000;
  text-decoration: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  transition: 0.3s;
  margin-top: 10px;
}

.download-btn:hover {
  background: var(--purple-glow);
  color: #fff;
  transform: translateY(-2px);
}

.file-input-wrapper {
  margin-bottom: 25px;
  text-align: left;
}

.file-input-wrapper label {
    display: block;
    font-size: 13px;
    margin-bottom: 8px;
    color: var(--purple-glow);
}

input[type="file"] {
  background: var(--glass-bg);
  border: 1px solid var(--glass-border);
  padding: 12px;
  width: 100%;
  border-radius: 10px;
  color: #fff;
  cursor: pointer;
}

.btn-submit {
  background: var(--purple-glow);
  color: #000;
  border: none;
  padding: 15px 30px;
  border-radius: 30px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.3s;
  width: 100%;
  letter-spacing: 1px;
}

.btn-submit:hover {
  transform: scale(1.02);
  box-shadow: 0 0 20px var(--purple-glow);
}

.status-box {
    margin-top: 25px;
    padding: 15px;
    background: rgba(34, 197, 94, 0.15);
    border-radius: 12px;
    border: 1px solid rgba(34, 197, 94, 0.4);
}

.back-link {
  display: inline-block;
  margin-top: 25px;
  color: #fff;
  text-decoration: none;
  font-size: 13px;
  opacity: 0.6;
}

.back-link:hover { opacity: 1; color: var(--purple-glow); }
</style>
</head>
<body>

<div class="upload-container">
    <i class="fa-solid fa-clipboard-check" style="font-size: 45px; color: var(--purple-glow); margin-bottom: 15px;"></i>
    <h2>Self Evaluation</h2>
    <p class="desc">Download the template, fill it out in Word, and upload the completed version below.</p>

    <div class="download-section">
        <p style="margin-bottom: 5px; font-weight: 600;">Step 1: Get the Form</p>
        <span style="font-size: 12px; opacity: 0.7;">Download the official .docx template</span><br>
        <a href="assets/Rotarex_2k26_Self Evaluation Report.docx" class="download-btn" download>
            <i class="fa-solid fa-file-word"></i> DOWNLOAD TEMPLATE
        </a>
    </div>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="file-input-wrapper">
            <label>Step 2: Upload Completed File (PDF Only)</label>
            <input type="file" name="self_eval" required accept=".pdf">
        </div>

        <?php echo $message; ?>

        <button type="submit" class="btn-submit">UPLOAD COMPLETED REPORT</button>
    </form>

    <?php if($existing_file): ?>
        <div class="status-box">
            <p style="color: #22c55e; margin-bottom: 5px; font-size: 14px;">
                <i class="fa-solid fa-circle-check"></i> You have already submitted a report.
            </p>
            <a href="<?php echo $existing_file; ?>" target="_blank" style="color: #c084fc; font-size: 12px; text-decoration: underline;">
                View Submitted File
            </a>
        </div>
    <?php endif; ?>

    <a href="dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
</div>

</body>
</html>