<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

/* ---------- DATABASE CONNECTION ---------- */
require_once('config.php'); // Replaced manual connection with your config file

/* ---------- FETCH DATA FOR AUTOFILL ---------- */
$user_email = $_SESSION['user'];
$project_title = "";
$project_theme = "";
$project_discipline = "";
$project_abstract = "";
$innovative_features = "";
$tools_technologies = "";
$expected_outcome = "";
$requirements = ""; // New field initialization
$is_update = false; // Flag to check if record exists
$is_locked = false; // Flag for lock logic

/* ---------- LOCK LOGIC: CHECK IF PAYMENT COMPLETED ---------- */
$check_payment = $conn->prepare("SELECT status FROM registration_payments WHERE email = ? AND status = 'Success' LIMIT 1");
$check_payment->bind_param("s", $user_email);
$check_payment->execute();
$pay_res = $check_payment->get_result();
if ($pay_res->num_rows > 0) {
    $is_locked = true;
}

// 1. First, try to fetch existing proposal data for EDITING
$stmt_edit = $conn->prepare("SELECT * FROM project_proposals WHERE user_email = ?");
$stmt_edit->bind_param("s", $user_email);
$stmt_edit->execute();
$edit_result = $stmt_edit->get_result();

if ($row_edit = $edit_result->fetch_assoc()) {
    $is_update = true; // Record found, we are in update mode
    $project_title = $row_edit['project_title'];
    $project_theme = $row_edit['project_theme'];
    $project_discipline = $row_edit['project_discipline'];
    $project_abstract = $row_edit['project_description'];
    $innovative_features = $row_edit['innovative_features'];
    $tools_technologies = $row_edit['tools_technologies'];
    $expected_outcome = $row_edit['expected_outcome'];
    $requirements = $row_edit['requirements'] ?? ""; // Fetching requirements
    // Lock logic check from project_proposals table as well
    if (isset($row_edit['is_locked']) && $row_edit['is_locked'] == 1) {
        $is_locked = true;
    }
} else {
    // 2. Fallback: Fetch initial data from registrations if proposal doesn't exist yet
    $stmt = $conn->prepare("SELECT theme, discipline FROM registrations WHERE user_email = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $project_theme = $row['theme'] ?? ""; 
        $project_discipline = $row['discipline'] ?? ""; 
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>InnovaHub | Step 3 – Project Proposal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
:root{
  --purple-main:#a855f7;
  --text-color:#000; 
}

/* ===== RESET & BODY ===== */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    min-height:100vh;
    background: url("assets/images/img.jpeg") center/cover no-repeat;
    display:flex;
    justify-content:center;
    align-items:flex-start;
    padding:20px 10px;
    color: var(--text-color);
}

/* ===== GLASS CONTAINER WITH WATERMARK ===== */
.container{
    position: relative;
    background: rgba(255, 255, 255, 0.15); 
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    padding: 30px 20px;
    max-width: 1100px;
    width: 100%;
    overflow: hidden;
}

/* ===== WATERMARK ===== */
.container::before{
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url("assets/images/img.jpeg") center/cover no-repeat;
    opacity: 0.05; 
    pointer-events: none;
}

/* ===== NEW HEADER STYLES ===== */
.header-logos {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    position: relative;
    z-index: 2;
    gap: 10px;
}

.header-logos img {
    max-height: 70px;
    width: auto;
}

.header-logos img.rotrax-logo {
    max-height: 90px; 
    transform: scale(1.1);
}

.college-header {
    text-align: center;
    margin-bottom: 25px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding-bottom: 15px;
    position: relative;
    z-index: 2;
}

.college-header h3 {
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.college-header h1 {
    font-size: 20px;
    font-weight: 700;
    margin: 5px 0;
    color: #1a1a1a;
    line-height: 1.2;
}

.college-header p {
    font-size: 10px;
    margin-bottom: 5px;
}

.college-header h4 {
    font-size: 16px;
    font-weight: 700;
    color: #2e2e2e;
    margin-top: 5px;
}

/* ===== HEADING ===== */
h2{
    text-align:center;
    color:#000; 
    margin-bottom:25px;
    text-shadow: none; 
    position: relative;
    z-index: 1;
    font-size: 20px;
    line-height: 1.3;
}

/* ===== FORM GRID ===== */
form{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px 20px;
    position: relative;
    z-index: 1;
}

label{
    font-size:13px;
    font-weight:600;
    color:#000; 
    margin-bottom:6px;
    display:block;
}

/* ===== INPUTS ===== */
input, textarea, select{
    width:100%;
    padding:12px 14px;
    border-radius:12px;
    border:2px solid #000; 
    background: rgba(255,255,255,0.25);
    color:#000; 
    outline:none;
    font-size:14px;
}

input, select{height:44px;}

textarea{
    min-height:90px;
    resize:vertical;
}

input:read-only, textarea:read-only {
    background: rgba(0,0,0,0.05);
    cursor: not-allowed;
}

input:disabled, textarea:disabled, select:disabled {
    background: rgba(0,0,0,0.1);
    cursor: not-allowed;
    border-color: #666;
}

input:hover, textarea:hover, select:hover{
    background: rgba(255,255,255,0.35);
    border:2px solid #000; 
}

/* Live Error Styling */
.error-text {
    color: #d90429;
    font-size: 11px;
    font-weight: 600;
    display: block;
    margin-top: 4px;
    visibility: hidden;
    min-height: 15px;
}

/* ===== SELECT ARROW ===== */
select{
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000000'%3E%3Cpath d='M1.5 5.5l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 16px center;
    background-size:14px;
}

/* ===== FULL WIDTH ===== */
.full{
    grid-column:1 / -1;
}

/* ===== DECLARATION ===== */
.declaration{
    grid-column:1 / -1;
    display:flex;
    align-items:flex-start;
    gap:10px;
    font-size:13px;
    margin-top:10px;
    color:#000; 
}

.declaration label{
    display:flex;
    align-items:flex-start;
    gap:10px;
    cursor:pointer;
    font-weight: 400;
}

.declaration input[type="checkbox"]{
    margin-top: 3px;
    transform:scale(1.3);
    accent-color:var(--purple-main);
}

/* ===== BUTTONS ===== */
.buttons{
    grid-column:1 / -1;
    display:flex;
    flex-direction: column;
    align-items: center;
    gap:15px;
    margin-top:25px;
}

button, .btn-link{
    padding:14px 20px;
    background: #000;
    color:#fff; 
    font-size:15px;
    font-weight:700;
    border:2px solid #000; 
    border-radius:14px;
    cursor:pointer;
    transition:0.3s ease;
    width:100%;
    max-width:300px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    text-align: center;
    text-decoration: none;
}

button:hover, .btn-link:hover{
    transform:translateY(-2px);
    background: transparent;
    color: #000; 
}

button:disabled {
    background: #ccc;
    border-color: #ccc;
    cursor: not-allowed;
    transform: none !important;
}

.lock-msg {
    grid-column: 1/-1;
    background: #fff3cd;
    color: #856404;
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
    margin-bottom: 15px;
}
</style>

<script>
function checkAlpha(input, errorId) {
    const errorSpan = document.getElementById(errorId);
    if (/\d/.test(input.value)) {
        errorSpan.innerText = "Numbers are not allowed in this field.";
        errorSpan.style.visibility = "visible";
        input.style.borderColor = "#d90429";
    } else {
        errorSpan.style.visibility = "hidden";
        input.style.borderColor = "#000";
    }
}

function validateForm() {
    let isValid = true;
    const requiredFields = [
        'project_title', 
        'project_discipline', 
        'project_abstract', 
        'innovative_features', 
        'tools_technologies', 
        'expected_outcome',
        'requirements'
    ];
    
    requiredFields.forEach(fieldName => {
        const field = document.getElementsByName(fieldName)[0];
        if (!field.value.trim()) {
            field.style.borderColor = "red";
            isValid = false;
        } else if (/\d/.test(field.value) && (fieldName !== 'tools_technologies' && fieldName !== 'requirements')) {
            isValid = false;
        }
    });

    const checkbox = document.getElementsByName('declaration')[0];
    if (!checkbox.checked) {
        alert("Please confirm the declaration.");
        isValid = false;
    }

    if (!isValid) {
        alert("Please fix the errors and fill in all required fields.");
    }
    return isValid;
}

function setAction(action){
    document.getElementById('action_type').value = action;
    return true;
}

// Redirect logic if status is 'saved'
window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'saved') {
        setTimeout(function() {
            window.location.href = "step4_payment.php";
        }, 1500); // 1.5 second delay to show the success message
    }
}
</script>
</head>

<body>
<div class="container">

    <div class="form-box">
        <div class="header-logos">
            <img src="/rotrax/assets/images/Yashoda.png" alt="Yashoda Logo">
            <img src="/rotrax/assets/images/logooo.png" alt="ROTRAX Logo" class="rotrax-logo">
        </div>

        <div class="college-header">
            <h3>Yashoda Shikshan Prasarak Mandal's</h3>
            <h1>YASHODA TECHNICAL CAMPUS, SATARA</h1>
            <p>Approved by AICTE, PCI & Govt. of Maharashtra</p>
            <h4>ROTARY CLUB OF SATARA</h4>
        </div>
    </div>
    <h2>Step 3: InnovaHub – Project Proposal</h2>

    <?php if($is_locked): ?>
        <div class="lock-msg">This proposal has been locked and cannot be edited.</div>
    <?php endif; ?>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'saved'): ?>
        <p style="text-align:center; color:#2d6a4f; margin-bottom:15px; font-weight:bold; font-size:14px;">Progress Saved/Updated Successfully! Redirecting to Payment...</p>
    <?php endif; ?>

    <?php if(isset($_GET['error']) && $_GET['error'] == 'empty_fields'): ?>
        <p style="text-align:center; color:#d90429; margin-bottom:15px; font-weight:bold; font-size:14px;">Error: All required fields must be filled.</p>
    <?php endif; ?>

    <?php if(isset($_GET['error']) && $_GET['error'] == 'locked'): ?>
        <p style="text-align:center; color:#d90429; margin-bottom:15px; font-weight:bold; font-size:14px;">Error: Submission is locked.</p>
    <?php endif; ?>

    <form action="step3_project_process.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
        <input type="hidden" name="action_type" id="action_type" value="continue">

        <div class="full">
            <label>Project Title *</label>
            <input type="text" name="project_title" required value="<?php echo htmlspecialchars($project_title); ?>" oninput="checkAlpha(this, 'err_project_title')" <?php echo $is_locked ? 'disabled' : ''; ?>>
            <span id="err_project_title" class="error-text"></span>
        </div>

        <div class="full">
            <label>Project Theme (Pre-filled from registration)</label>
            <input type="text" name="project_theme" value="<?php echo htmlspecialchars($project_theme); ?>" readonly required <?php echo $is_locked ? 'disabled' : ''; ?>>
            <span class="error-text"></span>
        </div>

        <div class="full">
            <label>Project Discipline</label>
            <textarea name="project_discipline" required oninput="checkAlpha(this, 'err_project_discipline')" <?php echo $is_locked ? 'disabled' : ''; ?>><?php echo htmlspecialchars($project_discipline); ?></textarea>
            <span id="err_project_discipline" class="error-text"></span>
        </div>

        <div class="full">
            <label>Description / Abstract</label>
            <textarea name="project_abstract" required oninput="checkAlpha(this, 'err_project_abstract')" <?php echo $is_locked ? 'disabled' : ''; ?>><?php echo htmlspecialchars($project_abstract); ?></textarea>
            <span id="err_project_abstract" class="error-text"></span>
        </div>

        <div class="full">
            <label>Innovative Features</label>
            <textarea name="innovative_features" required oninput="checkAlpha(this, 'err_innovative_features')" <?php echo $is_locked ? 'disabled' : ''; ?>><?php echo htmlspecialchars($innovative_features); ?></textarea>
            <span id="err_innovative_features" class="error-text"></span>
        </div>

        <div class="full">
            <label>Tools / Technologies</label>
            <textarea name="tools_technologies" required <?php echo $is_locked ? 'disabled' : ''; ?>><?php echo htmlspecialchars($tools_technologies); ?></textarea>
            <span id="err_tools_technologies" class="error-text"></span>
        </div>

        <div class="full">
            <label>Expected Outcome</label>
            <textarea name="expected_outcome" required oninput="checkAlpha(this, 'err_expected_outcome')" <?php echo $is_locked ? 'disabled' : ''; ?>><?php echo htmlspecialchars($expected_outcome); ?></textarea>
            <span id="err_expected_outcome" class="error-text"></span>
        </div>

        <div class="full">
            <label>Requirements(e.g. Electricity, Water, table, cabels, Wi-Fi etc.)</label>
            <textarea name="requirements" required oninput="checkAlpha(this, 'err_requirements')" <?php echo $is_locked ? 'disabled' : ''; ?>><?php echo htmlspecialchars($requirements); ?></textarea>
            <span id="err_requirements" class="error-text"></span>
        </div>

        <div class="declaration">
            <label>
                <input type="checkbox" name="declaration" required checked <?php echo $is_locked ? 'disabled' : ''; ?>>
                <span>I hereby declare that the information provided above is true and original.</span>
            </label>
        </div>

        <div class="buttons">
            <?php if(!$is_locked): ?>
                <?php if($is_update): ?>
                    <button type="submit">Update & Continue to Payment</button>
                <?php else: ?>
                    <button type="submit">Save & Continue to Payment</button>
                <?php endif; ?>
            <?php endif; ?>
            <a href="dashboard.php" class="btn-link">Dashboard</a>
        </div>
    </form>
</div>
</body>
</html>