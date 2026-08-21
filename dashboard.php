<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

include 'config.php'; 

// Load PHPMailer classes (Adjust paths if your folder structure is different)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Set Timezone to ensure the 11:45 AM trigger works based on Indian Standard Time
date_default_timezone_set('Asia/Kolkata');

$user_email = $_SESSION['user'];

/* ===== FETCH REGISTERED FULL NAME, REG DATE & LOCK STATUS ===== */
$stmt = $conn->prepare("SELECT fullname, created_at, is_locked, evaluation_email_sent FROM users WHERE email=?");
$stmt->bind_param("s",$user_email);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$display_name = $user_data['fullname'] ?? $user_email;
$reg_date = $user_data['created_at'];
$db_is_locked = $user_data['is_locked'] ?? 0; // Fetch the payment lock status
$email_sent_flag = $user_data['evaluation_email_sent'] ?? 0;

/* ===== EMAIL TRIGGER LOGIC (March 12, 2026, 11:57 AM) ===== */
$target_time = strtotime("2026-03-25 11:00:00");
$current_time = time();

if ($current_time >= $target_time && $email_sent_flag == 0) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username = ''; // Your email
        $mail->Password = ''; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('noreply@rotarex2026.com', 'Team ROTAREX');
        $mail->addAddress($user_email, $display_name);

        // Content
        $mail->isHTML(false);
        $mail->Subject = "ROTAREX 2026: Self Evaluation Deadline Reminder";
        $mail->Body    = "Dear $display_name,\n\nThis is a reminder that you have to submit your Self Evaluation before 25 March 2026.\n\nPlease log in to your dashboard to complete the process.\n\nRegards,\nTeam ROTAREX";

        if ($mail->send()) {
            // Update database so email isn't sent again on next refresh
            $update_stmt = $conn->prepare("UPDATE users SET evaluation_email_sent = 1 WHERE email = ?");
            $update_stmt->bind_param("s", $user_email);
            $update_stmt->execute();
        }
    } catch (Exception $e) {
        // Silently fail or log error: $mail->ErrorInfo
    }
}

/* ===== 15 DAYS LOCK LOGIC (PERMANENTLY UNLOCKED) ===== */
$registration_timestamp = strtotime($reg_date);
$current_timestamp = time();
$days_passed = floor(($current_timestamp - $registration_timestamp) / (60 * 60 * 24));
// Set to false to unlock the section as requested
$is_locked_15_days = false; 
$days_remaining = 15 - $days_passed;

/* ===== AUTOMATIC PROGRESS TRACKING LOGIC ===== */
$check1 = $conn->query("SELECT id FROM registrations WHERE user_email='$user_email' AND stream IS NOT NULL");
$status1 = ($check1 && $check1->num_rows > 0);

$check2 = $conn->query("SELECT id FROM team_information WHERE user_email='$user_email'");
$status2 = ($check2 && $check2->num_rows > 0);

$check3 = $conn->query("SELECT id FROM project_proposals WHERE user_email='$user_email'");
$status3 = ($check3 && $check3->num_rows > 0);

$check4 = $conn->query("SELECT id FROM project_proposals WHERE user_email='$user_email' 
    AND bonafide_file IS NOT NULL 
    AND attachment_file IS NOT NULL");
$status4 = ($check4 && $check4->num_rows > 0);

$check5 = $conn->query("SELECT id FROM registration_payments WHERE email='$user_email' AND status='Success'");
$status5 = ($check5 && $check5->num_rows > 0);

// Status for Self Evaluation (Check if self_eval_file exists to turn section green)
$check6 = $conn->query("SELECT id FROM project_proposals WHERE user_email='$user_email' AND self_eval_file IS NOT NULL");
$status6 = ($check6 && $check6->num_rows > 0);

/* FETCH USER'S SOLVED TICKETS - FIXED: Changed status filter */
$solved_tickets_query = "SELECT * FROM support_tickets WHERE user_email='$user_email' AND status='solved' ORDER BY solved_at DESC";
$solved_tickets_result = $conn->query($solved_tickets_query);

/* FETCH USER'S PENDING TICKETS - FIXED: Changed status filter to include 'received', 'pending', and NULL values */
$pending_tickets_query = "SELECT * FROM support_tickets WHERE user_email='$user_email' AND status != 'solved' ORDER BY created_at DESC";
$pending_tickets_result = $conn->query($pending_tickets_query);

/* Helper function for card clicks */
$lock_msg = "Your registration process has been locked. You cannot edit your form after successful submission.";
$step1_msg = "Please complete Step 1: Stream & Section form first.";
$step2_msg = "Please complete Step 2: Team Information form first.";
$step3_msg = "Please complete Step 3: Project Proposal form first.";

// Helper function to handle locking logic
function getCardAction($targetUrl, $dbLock, $msg, $isStep1Complete, $s1Msg, $isCurrentStep1 = false, $needsConfirm = false, $isComplete = false, $isStep2Complete = false, $s2Msg = "", $isStep3Complete = false, $s3Msg = "", $isStep2Page = false, $isStep3Page = false, $isPaymentPage = false) {
    // UPDATED: Uploads, Self Evaluation, and Support will NOT be locked even if is_locked = 1
    $bypassLock = ($targetUrl == 'uploads.php' || $targetUrl == 'self_evaluation.php' || $targetUrl == 'support_ticket.php' || $targetUrl == '#solved_tickets_section' || $targetUrl == '#pending_tickets_section');

    // If global lock is active and this page is NOT a bypass page, lock it.
    if (!$bypassLock && $dbLock == 1) {
        return "alert('$msg')";
    }
    
    // Check if Step 1 is complete (required for all steps except Step 1 itself)
    if (!$isCurrentStep1 && !$isStep1Complete) {
        return "alert('$s1Msg')";
    }
    
    // Check if Step 2 is complete (required for Step 3 and Payment)
    if (($isStep3Page || $isPaymentPage) && !$isStep2Complete) {
        return "alert('$s2Msg')";
    }
    
    // Check if Step 3 is complete (required for Payment)
    if ($isPaymentPage && !$isStep3Complete) {
        return "alert('$s3Msg')";
    }
    
    // Check for confirmation requirement
    if ($needsConfirm) {
        return "if(confirm('Are you sure you want to proceed to the confirmation page?')){ location.href='$targetUrl'; }";
    }

    if(substr($targetUrl, 0, 1) === '#') {
        $sectionId = substr($targetUrl, 1);
        return "showTicketSection('$sectionId')";
    }

    return "location.href='$targetUrl'";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>InnovaHub Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<style>
:root{
  --glass-bg: rgba(255,255,255,0.08);
  --glass-border: rgba(255,255,255,0.18);
  --purple-glow: #c084fc;
  --gold-main: #000000; 
  --section-heading: #c084fc; 
  --text-light: #ffffff;
  --success-green: #22c55e;
  --pending-red: #ef4444; 
  --lock-gray: #64748b;
  --support-blue: #38bdf8;
}

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:'Poppins',sans-serif;
}

body{
  min-height:100vh;
  background: #0f172a; 
  background: url("assets/images/img.jpeg") no-repeat center center fixed;
  background-size:cover;
  color:var(--text-light);
  position: relative;
  overflow-x: hidden;
}

.watermark{
  position:fixed;
  top:50%;
  left:50%;
  width:700px;
  height:700px;
  background:url('assets/images/logoo.png') no-repeat center;
  background-size:contain;
  opacity:0.05;
  transform:translate(-50%,-50%);
  z-index:-1;
}

.mobile-header {
    display: none;
    position: fixed;
    top: 0; width: 100%;
    background: rgba(15,3,40,0.9);
    padding: 15px 20px;
    z-index: 1001;
    justify-content: space-between;
    align-items: center;
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--glass-border);
}
.menu-btn { font-size: 24px; cursor: pointer; color: var(--purple-glow); }

.sidebar{
  position:fixed;
  top:0;
  left:0;
  width:260px;
  height:100%;
  background:rgba(15,3,40,0.4); 
  backdrop-filter:blur(25px);
  border-right:1px solid var(--glass-border);
  padding-top:25px;
  z-index:1000;
  transition: 0.4s ease;
}

.sidebar h2{
  text-align:center;
  margin-bottom:30px;
  color:var(--gold-main); 
  font-weight:700;
  text-shadow:0 0 20px rgba(255,255,255,0.5);
  padding: 0 10px;
}

.sidebar ul{ list-style:none; }
.sidebar ul li{ margin:8px 15px; border-radius:14px; }
.sidebar ul li a{ display:flex; align-items: center; padding:14px 18px; color:#fff; text-decoration:none; transition:0.3s; border-radius: 12px; }
.sidebar ul li a i { width: 30px; font-size: 18px; }
.sidebar ul li a:hover{ background:rgba(192,132,252,0.25); color:var(--section-heading); }

.main-content{ margin-left:260px; padding:40px; transition: 0.4s; }
.main-content h1{ 
  margin-bottom:35px; 
  font-size:2.4rem; 
  color: #000000; 
  text-shadow:0 0 25px rgba(255,255,255,0.5); 
}

.cards{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
  gap:25px;
}

.card{
  background:var(--glass-bg);
  backdrop-filter:blur(16px);
  border:1px solid var(--glass-border);
  border-radius:25px;
  padding:40px 22px;
  text-align:center;
  position: relative;
  box-shadow:0 15px 50px rgba(0,0,0,0.35);
  cursor:pointer;
  transition:0.35s;
  overflow: hidden;
}

.card:hover:not(.locked){
  transform:translateY(-8px);
  background:rgba(255,255,255,0.12);
  border-color: var(--section-heading);
  box-shadow:0 25px 65px rgba(192,132,252,0.6);
}

.card.locked {
    cursor: not-allowed;
    opacity: 0.7;
    filter: grayscale(0.8);
    border-color: var(--lock-gray) !important;
}

.status-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 10px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 20px;
    text-transform: uppercase;
}

.status-completed { background: var(--success-green); color: white; box-shadow: 0 0 10px rgba(34, 197, 94, 0.5); }
.status-pending { background: var(--pending-red); color: white; box-shadow: 0 0 10px rgba(239, 68, 68, 0.5); }
.status-locked { background: var(--lock-gray); color: white; }
.status-support { background: var(--support-blue); color: white; }

.card.completed { border-color: var(--success-green); }
.card.pending { border-color: var(--pending-red); }
.card.support { border-color: var(--support-blue); }

.card h3{ color:var(--section-heading); margin-bottom:12px; font-size: 1.2rem; } 
.card.completed h3 { color: var(--success-green); }
.card.pending h3 { color: var(--pending-red); }
.card.locked h3 { color: var(--lock-gray); }
.card.support h3 { color: var(--support-blue); }
.card p{ font-size:14px; color: #000000; font-weight: 500; } 

.modal-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.92);
    backdrop-filter:blur(15px);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding: 15px;
}

.process-box{
    background: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.85)), url('assets/images/flyer.jpg') no-repeat center center;
    background-size: cover;
    border-radius:22px;
    padding:50px;
    max-width:1000px;
    width:95%;
    text-align:center;
    border:1px solid rgba(192,132,252,0.4);
    box-shadow:0 0 60px rgba(192,132,252,0.5);
    max-height: 95vh;
    overflow-y: auto;
    position: relative;
}

.process-box h2{ color:#c084fc; margin-bottom:35px; font-weight:700; font-size: 2.2rem; text-shadow: 2px 2px 10px #000; }
.process-steps{ display:flex; justify-content:space-between; gap:20px; margin-bottom:40px; flex-wrap: wrap; }
.step{ flex:1; min-width: 140px; text-align:center; }
.step .circle{
    width:55px; height:55px;
    margin:0 auto 15px;
    border-radius:50%;
    background:#c084fc;
    color:#000;
    font-weight:700;
    font-size: 1.2rem;
    display:flex; align-items:center; justify-content:center;
}
.step p{ font-size:13px; color:#e5e7eb; line-height:1.4; text-shadow: 1px 1px 5px #000; }

.guidelines {
    display: flex;
    justify-content: space-between;
    gap: 30px;
    text-align: left;
    margin-bottom: 40px;
    background: rgba(0,0,0,0.6);
    padding: 30px;
    border-radius: 15px;
    backdrop-filter: blur(5px);
}
.guidelines h4 { font-size: 18px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; }
.dos h4 { color: var(--success-green); }
.donts h4 { color: var(--pending-red); }
.guidelines ul { list-style: none; font-size: 15px; color: #ffffff; }
.guidelines ul li { margin-bottom: 12px; position: relative; padding-left: 25px; }
.dos ul li::before { content: '✓'; position: absolute; left: 0; color: var(--success-green); font-weight: bold; }
.donts ul li::before { content: '✕'; position: absolute; left: 0; color: var(--pending-red); font-weight: bold; }

.agreement-section {
    margin-bottom: 30px;
    color: #ffffff;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    text-shadow: 1px 1px 5px #000;
}
.agreement-section input { width: 24px; height: 24px; cursor: pointer; flex-shrink: 0; }

.proceed-btn{
    background:rgba(0,0,0,0.7);
    border:2px solid #c084fc;
    color:#c084fc;
    padding:18px 50px;
    border-radius:40px;
    font-weight:700;
    font-size: 1.1rem;
    cursor:pointer;
    transition: 0.3s;
    width: 100%;
    max-width: 400px;
}
.proceed-btn:disabled { opacity: 0.4; cursor: not-allowed; border-color: #6b7280; color: #6b7280; }
.proceed-btn:hover:not(:disabled){ background:#c084fc; color:#000; box-shadow: 0 0 20px #c084fc; }

.solved-tickets-container {
    margin-top: 50px;
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 30px;
}
.solved-tickets-container h2 { color: var(--success-green); margin-bottom: 20px; font-size: 1.8rem; }
.pending-tickets-container {
    margin-top: 50px;
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 30px;
}
.pending-tickets-container h2 { color: var(--pending-red); margin-bottom: 20px; font-size: 1.8rem; }

.ticket-table { width: 100%; border-collapse: collapse; margin-top: 10px; color: #000000; border: 3px solid #000000; }
.ticket-table th { background: rgba(0,0,0,0.3); padding: 12px; text-align: left; border-bottom: 2px solid #000000; color: #000000; text-transform: uppercase; font-size: 12px; }
.ticket-table td { padding: 12px; border-bottom: 1px solid #000000; font-size: 14px; color: #000000; }
.ticket-table tr:hover { background: rgba(255,255,255,0.05); }

@media(max-width:992px){
    .sidebar { left: -260px; }
    .sidebar.active { left: 0; box-shadow: 20px 0 50px rgba(0,0,0,0.8); }
    .main-content { margin-left: 0; padding: 100px 20px 40px; }
    .mobile-header { display: flex; }
    .main-content h1 { font-size: 1.8rem; text-align: center; }
}
@media(max-width:600px){
    .process-box { padding: 30px 15px; }
    .process-steps { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .guidelines { flex-direction: column; padding: 15px; gap: 15px; }
    .card { padding: 30px 15px; }
    .ticket-table thead { display: none; }
    .ticket-table td { display: block; text-align: right; border: none; padding: 8px 0; }
    .ticket-table td::before { content: attr(data-label); float: left; font-weight: bold; color: #000000; }
    .ticket-table tr { display: block; border-bottom: 2px solid #000000; margin-bottom: 15px; padding-bottom: 10px; }
}
</style>
</head>

<body>
<div class="watermark"></div>

<div class="mobile-header">
    <div class="menu-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></div>
    <div style="font-weight: 700; color: #fff;">ROTAREX 2026</div>
    <div style="width: 24px;"></div>
</div>

<?php if (!isset($_SESSION['popup_shown_this_session'])): ?>
<div id="instructionModal" class="modal-overlay">
    <div class="process-box">
        <h2>REGISTRATION PROCESS</h2>
        <div class="process-steps">
            <div class="step"><div class="circle">1</div><p><b>Stream</b><br>Choose Stream</p></div>
            <div class="step"><div class="circle">2</div><p><b>Team</b><br>Add Members</p></div>
            <div class="step"><div class="circle">3</div><p><b>Proposal</b><br>Submit Abstract</p></div>
            <div class="step"><div class="circle">4</div><p><b>Upload</b><br>Attach Files</p></div>
            <div class="step"><div class="circle">5</div><p><b>Payment</b><br>Pay & Confirm</p></div>
        </div>
        <div class="guidelines">
            <div class="dos">
                <h4>Do's</h4>
                <ul>
                    <li>Provide valid student IDs for all team members.</li>
                    <li>Ensure the project abstract is original.</li>
                    <li>Upload clear transaction screenshots for payment.</li>
                </ul>
            </div>
            <div class="donts">
                <h4>Don'ts</h4>
                <ul>
                    <li>Don't use fake email addresses.</li>
                    <li>Don't leave required team fields empty.</li>
                    <li>Don't refresh the page during payment processing.</li>
                </ul>
            </div>
        </div>
        <div class="agreement-section">
            <input type="checkbox" id="agreeCheckbox" onchange="toggleProceedBtn()">
            <label for="agreeCheckbox">I agree to the instructions and terms of ROTAREX 2026</label>
        </div>
        <button id="mainProceedBtn" class="proceed-btn" onclick="closeInstructions()" disabled>I UNDERSTAND, PROCEED</button>
    </div>
</div>
<?php endif; ?>

<div class="sidebar" id="sidebar">
    <div style="text-align: right; padding: 10px; display: none;" id="close-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-xmark" style="font-size: 24px; color: #fff; cursor: pointer;"></i>
    </div>
    <h2>InnovaHub</h2>
    <ul>
        <li><a href="javascript:void(0)" onclick="<?php echo getCardAction('section.php', $db_is_locked, $lock_msg, $status1, $step1_msg, true, false, false, $status2, $step2_msg, $status3, $step3_msg, false, false, false); ?>"><i class="fa-solid fa-layer-group"></i>&nbsp; <span>Stream & Section</span></a></li>
        <li><a href="javascript:void(0)" onclick="<?php echo getCardAction('step2.php', $db_is_locked, $lock_msg, $status1, $step1_msg, false, false, false, $status2, $step2_msg, $status3, $step3_msg, true, false, false); ?>"><i class="fa-solid fa-users"></i>&nbsp; <span>Team Information</span></a></li>
        <li><a href="javascript:void(0)" onclick="<?php echo getCardAction('step3_project_proposal.php', $db_is_locked, $lock_msg, $status1, $step1_msg, false, false, false, $status2, $step2_msg, $status3, $step3_msg, false, true, false); ?>"><i class="fa-solid fa-file-lines"></i>&nbsp; <span>Project Proposal</span></a></li>
        <li><a href="javascript:void(0)" onclick="<?php echo getCardAction('step4_payment.php', $db_is_locked, $lock_msg, $status1, $step1_msg, false, false, false, $status2, $step2_msg, $status3, $step3_msg, false, false, true); ?>"><i class="fa-solid fa-credit-card"></i>&nbsp; <span>Registration Fees</span></a></li>
        <li><a href="javascript:void(0)" onclick="<?php echo getCardAction('step5_confirmation.php', $db_is_locked, $lock_msg, $status1, $step1_msg, false, true, $status5, $status2, $step2_msg, $status3, $step3_msg, false, false, false); ?>"><i class="fa-solid fa-circle-check"></i>&nbsp; <span>Confirmation</span></a></li>
        <li><a href="javascript:void(0)" onclick="<?php echo getCardAction('support_ticket.php', $db_is_locked, $lock_msg, $status1, $step1_msg, false, false, false, $status2, $step2_msg, $status3, $step3_msg, false, false, false); ?>"><i class="fa-solid fa-headset"></i>&nbsp; <span>Support Ticket</span></a></li>
        <li><a href="javascript:void(0)" onclick="<?php echo getCardAction('#pending_tickets_section', $db_is_locked, $lock_msg, $status1, $step1_msg, false, false, false, $status2, $step2_msg, $status3, $step3_msg, false, false, false); ?>"><i class="fa-solid fa-clock"></i>&nbsp; <span>Pending Tickets</span></a></li>
        <li><a href="javascript:void(0)" onclick="<?php echo getCardAction('#solved_tickets_section', $db_is_locked, $lock_msg, $status1, $step1_msg, false, false, false, $status2, $step2_msg, $status3, $step3_msg, false, false, false); ?>"><i class="fa-solid fa-check-circle"></i>&nbsp; <span>Solved Tickets</span></a></li>
        <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>&nbsp; <span>Logout</span></a></li>
    </ul>
</div>

<div class="main-content">
    <h1>Welcome, <?php echo htmlspecialchars($display_name); ?> 👋</h1>

    <?php if ($db_is_locked == 1): ?>
    <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; padding: 15px; border-radius: 12px; margin-bottom: 25px; text-align: center; color: #ff8080;">
        <i class="fa-solid fa-circle-exclamation"></i> <?php echo $lock_msg; ?>
    </div>
    <?php endif; ?>

    <div class="cards">
        <div class="card <?php echo ($db_is_locked == 1) ? 'locked' : ($status1 ? 'completed' : 'pending'); ?>" onclick="<?php echo getCardAction('section.php', $db_is_locked, $lock_msg, $status1, $step1_msg, true, false, false, $status2, $step2_msg, $status3, $step3_msg, false, false, false); ?>">
            <span class="status-badge <?php echo ($db_is_locked == 1) ? 'status-locked' : ($status1 ? 'status-completed' : 'status-pending'); ?>">
                <?php echo ($db_is_locked == 1) ? '<i class="fa-solid fa-lock"></i> Locked' : ($status1 ? '<i class="fa-solid fa-check"></i> Completed' : 'Pending'); ?>
            </span>
            <h3>Stream & Section</h3>
            <p>Select your program</p>
        </div>

        <div class="card <?php echo ($db_is_locked == 1 || !$status1) ? 'locked' : ($status2 ? 'completed' : 'pending'); ?>" onclick="<?php echo getCardAction('step2.php', $db_is_locked, $lock_msg, $status1, $step1_msg, false, false, false, $status2, $step2_msg, $status3, $step3_msg, true, false, false); ?>">
            <span class="status-badge <?php echo ($db_is_locked == 1 || !$status1) ? 'status-locked' : ($status2 ? 'status-completed' : 'status-pending'); ?>">
                <?php echo ($db_is_locked == 1 || !$status1) ? '<i class="fa-solid fa-lock"></i> Locked' : ($status2 ? '<i class="fa-solid fa-check"></i> Completed' : 'Pending'); ?>
            </span>
            <h3>Team Information</h3>
            <p>Manage members</p>
        </div>

        <div class="card <?php echo ($db_is_locked == 1 || !$status1 || !$status2) ? 'locked' : ($status3 ? 'completed' : 'pending'); ?>" onclick="<?php echo getCardAction('step3_project_proposal.php', $db_is_locked, $lock_msg, $status1, $step1_msg, false, false, false, $status2, $step2_msg, $status3, $step3_msg, false, true, false); ?>">
            <span class="status-badge <?php echo ($db_is_locked == 1 || !$status1 || !$status2) ? 'status-locked' : ($status3 ? 'status-completed' : 'status-pending'); ?>">
                <?php echo ($db_is_locked == 1 || !$status1 || !$status2) ? '<i class="fa-solid fa-lock"></i> Locked' : ($status3 ? '<i class="fa-solid fa-check"></i> Completed' : 'Pending'); ?>
            </span>
            <h3>Project Proposal</h3>
            <p>Submit abstract</p>
        </div>

        <div class="card <?php echo ($db_is_locked == 1 || !$status1 || !$status2 || !$status3) ? 'locked' : ($status5 ? 'completed' : 'pending'); ?>" onclick="<?php echo getCardAction('step4_payment.php', $db_is_locked, $lock_msg, $status1, $step1_msg, false, false, false, $status2, $step2_msg, $status3, $step3_msg, false, false, true); ?>">
            <span class="status-badge <?php echo ($db_is_locked == 1 || !$status1 || !$status2 || !$status3) ? 'status-locked' : ($status5 ? 'status-completed' : 'status-pending'); ?>">
                <?php echo ($db_is_locked == 1 || !$status1 || !$status2 || !$status3) ? '<i class="fa-solid fa-lock"></i> Locked' : ($status5 ? '<i class="fa-solid fa-check"></i> Completed' : 'Pending'); ?>
            </span>
            <h3>Registration Fees</h3>
            <p>Payment Status</p>
        </div>

        <div class="card <?php echo ($db_is_locked == 1) ? 'locked' : ($status5 ? 'completed' : 'pending'); ?>" onclick="<?php echo getCardAction('step5_confirmation.php', $db_is_locked, $lock_msg, $status1, $step1_msg, false, true, $status5, $status2, $step2_msg, $status3, $step3_msg, false, false, false); ?>">
            <span class="status-badge <?php echo ($db_is_locked == 1) ? 'status-locked' : ($status5 ? 'status-completed' : 'status-pending'); ?>">
                <?php echo ($db_is_locked == 1) ? '<i class="fa-solid fa-lock"></i> Locked' : ($status5 ? '<i class="fa-solid fa-check"></i> Completed' : 'Pending'); ?>
            </span>
            <h3>Confirmation</h3>
            <p>Download form</p>
        </div>

        <div class="card <?php echo $status4 ? 'completed' : 'pending'; ?>" onclick="<?php echo getCardAction('uploads.php', $db_is_locked, $lock_msg, $status1, $step1_msg, false, false, false, $status2, $step2_msg, $status3, $step3_msg, false, false, false); ?>">
            <span class="status-badge <?php echo $status4 ? 'status-completed' : 'status-pending'; ?>">
                <?php echo $status4 ? '<i class="fa-solid fa-check"></i> Completed' : 'Pending'; ?>
            </span>
            <h3>Uploads</h3>
            <p>Files & media</p>
        </div>

        <div class="card <?php echo ($status6 ? 'completed' : 'pending'); ?>" 
             onclick="<?php 
                if (!$status1) {
                    echo "alert('$step1_msg')";
                } else {
                    echo "location.href='self_evaluation.php'";
                }
             ?>">
            <span class="status-badge <?php echo ($status6 ? 'status-completed' : 'status-pending'); ?>">
                <?php 
                    echo $status6 ? '<i class="fa-solid fa-check"></i> Completed' : 'Pending';
                ?>
            </span>
            <h3>Self Evaluation</h3>
            <p>Progress report upload</p>
        </div>

        <div class="card support" onclick="<?php echo getCardAction('support_ticket.php', $db_is_locked, $lock_msg, $status1, $step1_msg, false, false, false, $status2, $step2_msg, $status3, $step3_msg, false, false, false); ?>">
            <span class="status-badge status-support">
                <i class="fa-solid fa-headset"></i> Help
            </span>
            <h3>Support Ticket</h3>
            <p>Report Issue / Ask Query</p>
        </div>
    </div>

    <div class="pending-tickets-container" id="pending_tickets_section" style="display: none;">
        <h2><i class="fa-solid fa-clock me-2"></i> Your Pending Tickets</h2>
        <div class="table-responsive">
            <table class="ticket-table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Created On</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_tickets_result && $pending_tickets_result->num_rows > 0): ?>
                        <?php while($ticket = $pending_tickets_result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Ticket ID">#<?php echo $ticket['id']; ?></td>
                            <td data-label="Subject"><?php echo htmlspecialchars($ticket['subject']); ?></td>
                            <td data-label="Category"><?php echo htmlspecialchars($ticket['category']); ?></td>
                            <td data-label="Created On"><?php echo date('d-M-Y', strtotime($ticket['created_at'])); ?></td>
                            <td data-label="Message"><?php echo htmlspecialchars(substr($ticket['message'], 0, 50)) . '...'; ?></td>
                            <td data-label="Status"><span style="color: var(--pending-red); font-weight: bold;"><i class="fa-solid fa-spinner fa-spin"></i> Pending</span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--lock-gray); padding: 30px;">No pending tickets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="solved-tickets-container" id="solved_tickets_section" style="display: none;">
        <h2><i class="fa-solid fa-check-double me-2"></i> Your Solved Tickets</h2>
        <div class="table-responsive">
            <table class="ticket-table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Created On</th>
                        <th>Solved On</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($solved_tickets_result && $solved_tickets_result->num_rows > 0): ?>
                        <?php while($ticket = $solved_tickets_result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Ticket ID">#<?php echo $ticket['id']; ?></td>
                            <td data-label="Subject"><?php echo htmlspecialchars($ticket['subject']); ?></td>
                            <td data-label="Category"><?php echo htmlspecialchars($ticket['category']); ?></td>
                            <td data-label="Created On"><?php echo date('d-M-Y', strtotime($ticket['created_at'])); ?></td>
                            <td data-label="Solved On"><?php echo date('d-M-Y', strtotime($ticket['solved_at'])); ?></td>
                            <td data-label="Message"><?php echo htmlspecialchars(substr($ticket['message'], 0, 50)) . '...'; ?></td>
                            <td data-label="Status"><span style="color: var(--success-green); font-weight: bold;"><i class="fa-solid fa-circle-check"></i> Solved</span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--lock-gray); padding: 30px;">No solved tickets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const closeBtn = document.getElementById('close-btn');
    sidebar.classList.toggle('active');
    if(window.innerWidth <= 992) {
        closeBtn.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
    }
}

function showTicketSection(sectionId) {
    const target = document.getElementById(sectionId);
    if(target) {
        target.style.display = 'block';
        setTimeout(() => {
            target.scrollIntoView({behavior: 'smooth', block: 'start'});
        }, 50);
    }
    
    if (window.innerWidth <= 992) {
        const sidebar = document.getElementById('sidebar');
        if(sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    }
}

function toggleProceedBtn() {
    const checkbox = document.getElementById('agreeCheckbox');
    const btn = document.getElementById('mainProceedBtn');
    btn.disabled = !checkbox.checked;
}

function closeInstructions() {
    const modal = document.getElementById('instructionModal');
    if(modal) modal.style.display = 'none';
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "set_popup_session.php", true);
    xhr.send();
}

document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuBtn = document.querySelector('.menu-btn');
    if (window.innerWidth <= 992 && sidebar.classList.contains('active')) {
        if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
            toggleSidebar();
        }
    }
});
</script>
</body>
</html>