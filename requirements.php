<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

include 'config.php'; 

$user_email = $_SESSION['user'];

/* ===== FETCH LOCK STATUS & PROGRESS ===== */
$stmt = $conn->prepare("SELECT is_locked FROM users WHERE email=?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$db_is_locked = $user_data['is_locked'] ?? 0;

$check1 = $conn->query("SELECT id FROM registrations WHERE user_email='$user_email' AND stream IS NOT NULL");
$status1 = ($check1 && $check1->num_rows > 0);

$lock_msg = "Your registration process has been locked. You cannot edit requirements after final submission.";
$step1_msg = "Please complete Step 1: Stream & Section form first.";

/* Handle Form Submission */
$success_msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $db_is_locked == 0) {
    $req_text = $_POST['project_requirements'] ?? '';
    
    // Check if entry exists
    $chk = $conn->prepare("SELECT id FROM project_proposals WHERE user_email=?");
    $chk->bind_param("s", $user_email);
    $chk->execute();
    $res = $chk->get_result();

    if ($res->num_rows > 0) {
        $up = $conn->prepare("UPDATE project_proposals SET requirements=? WHERE user_email=?");
        $up->bind_param("ss", $req_text, $user_email);
        $up->execute();
    } else {
        $in = $conn->prepare("INSERT INTO project_proposals (user_email, requirements) VALUES (?, ?)");
        $in->bind_param("ss", $user_email, $req_text);
        $in->execute();
    }
    $success_msg = "Requirements updated successfully!";
}

/* Fetch existing requirements */
$stmt_req = $conn->prepare("SELECT requirements FROM project_proposals WHERE user_email=?");
$stmt_req->bind_param("s", $user_email);
$stmt_req->execute();
$existing_req = $stmt_req->get_result()->fetch_assoc()['requirements'] ?? '';

function getLinkAction($targetUrl, $dbLock, $msg, $isStep1Complete, $s1Msg, $isCurrentStep1 = false) {
    if ($dbLock == 1) return "alert('$msg')";
    if (!$isCurrentStep1 && !$isStep1Complete) return "alert('$s1Msg')";
    return "location.href='$targetUrl'";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Project Requirements - InnovaHub</title>
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

*{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

body{
  min-height:100vh;
  background: url("assets/images/img.jpeg") no-repeat center center fixed;
  background-size:cover;
  color:var(--text-light);
}

.sidebar{
  position:fixed;
  top:0; left:0; width:260px; height:100%;
  background:rgba(15,3,40,0.6); 
  backdrop-filter:blur(25px);
  border-right:1px solid var(--glass-border);
  padding-top:25px; z-index:1000;
}

.sidebar h2{ text-align:center; margin-bottom:30px; color:#000; font-weight:700; text-shadow:0 0 20px rgba(255,255,255,0.5); }
.sidebar ul{ list-style:none; }
.sidebar ul li{ margin:8px 15px; }
.sidebar ul li a{ display:flex; align-items: center; padding:14px 18px; color:#fff; text-decoration:none; transition:0.3s; border-radius: 12px; }
.sidebar ul li a:hover{ background:rgba(192,132,252,0.25); color:#c084fc; }

.main-content{ margin-left:260px; padding:40px; }

.form-container {
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    border: 1px solid var(--glass-border);
    border-radius: 25px;
    padding: 40px;
    max-width: 800px;
    margin: 0 auto;
    box-shadow: 0 15px 50px rgba(0,0,0,0.3);
}

h1 { color: #000; text-shadow: 0 0 20px rgba(255,255,255,0.5); margin-bottom: 20px; }

textarea {
    width: 100%;
    height: 200px;
    background: rgba(255,255,255,0.1);
    border: 1px solid var(--glass-border);
    border-radius: 15px;
    padding: 20px;
    color: #000000;
    font-size: 16px;
    margin-bottom: 20px;
    resize: none;
}

textarea:focus { outline: none; border-color: var(--purple-glow); }

.submit-btn {
    background: var(--purple-glow);
    color: #000;
    border: none;
    padding: 15px 40px;
    border-radius: 30px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s;
}

.submit-btn:hover { transform: scale(1.05); box-shadow: 0 0 20px var(--purple-glow); }
.submit-btn:disabled { background: #64748b; cursor: not-allowed; }

.alert {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
}
.alert-success { background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #fff; }
.alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ff8080; }

@media(max-width:992px){
    .sidebar { display: none; }
    .main-content { margin-left: 0; padding: 20px; }
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>InnovaHub</h2>
    <ul>
        <li><a href="dashboard.php"><i class="fa-solid fa-house"></i>&nbsp; <span>Dashboard</span></a></li>
        <li><a href="javascript:void(0)" onclick="<?php echo getLinkAction('section.php', $db_is_locked, $lock_msg, $status1, $step1_msg, true); ?>"><i class="fa-solid fa-layer-group"></i>&nbsp; <span>Stream & Section</span></a></li>
        <li><a href="javascript:void(0)" onclick="<?php echo getLinkAction('requirements.php', $db_is_locked, $lock_msg, $status1, $step1_msg); ?>"><i class="fa-solid fa-list-check"></i>&nbsp; <span>Requirements</span></a></li>
        <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>&nbsp; <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content">
    <h1>Project Requirements</h1>
    
    <div class="form-container">
        <?php if($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if($db_is_locked == 1): ?>
            <div class="alert alert-error"><i class="fa-solid fa-lock"></i> <?php echo $lock_msg; ?></div>
        <?php endif; ?>

        <p style="margin-bottom: 20px; color: #000; font-weight: 500;">
            List any specific hardware, software, space, or electrical requirements for your project.
        </p>

        <form method="POST">
            <textarea name="project_requirements" placeholder="e.g. 1. Two power sockets&#10;2. 4x4 ft Table space&#10;3. Stable Wi-Fi..." <?php echo ($db_is_locked == 1) ? 'disabled' : ''; ?>><?php echo htmlspecialchars($existing_req); ?></textarea>
            
            <div style="text-align: right;">
                <button type="submit" class="submit-btn" <?php echo ($db_is_locked == 1) ? 'disabled' : ''; ?>>
                    SAVE REQUIREMENTS
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>