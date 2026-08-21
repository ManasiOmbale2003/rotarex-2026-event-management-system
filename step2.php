<?php 
session_start(); 
if(!isset($_SESSION['user'])){     
    header("Location: login.php");     
    exit(); 
} 

// Include the external database configuration
include 'config.php';

/* --- 1. THE AJAX LISTENER (Must be at the very top) --- */
if (isset($_GET['check_team'])) {
    if (ob_get_length()) ob_clean();
    $t_name = trim($_GET['check_team']);
    $u_email = $_SESSION['user'];
    
    $stmt = $conn->prepare("SELECT id FROM team_information WHERE LOWER(team_name) = LOWER(?) AND user_email != ?");
    $stmt->bind_param("ss", $t_name, $u_email);
    $stmt->execute();
    $stmt->store_result();
    
    echo ($stmt->num_rows > 0) ? "exists" : "ok";
    exit; 
}

$user_email = $_SESSION['user'];  

// --- LOCK LOGIC START ---
$is_locked = false;
$check_col = $conn->query("SHOW COLUMNS FROM `registration_payments` LIKE 'receipt_uploaded'");
if ($check_col && $check_col->num_rows > 0) {
    $lock_stmt = $conn->prepare("SELECT receipt_uploaded FROM registration_payments WHERE email = ? AND receipt_uploaded = 1 LIMIT 1");
    $lock_stmt->bind_param("s", $user_email);
    $lock_stmt->execute();
    $is_locked = $lock_stmt->get_result()->num_rows > 0;
}
// --- LOCK LOGIC END ---


$stmt = $conn->prepare("SELECT fullname, unique_reg_id, phone FROM users WHERE email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();

$stmt1 = $conn->prepare("select program FROM  registrations WHERE user_email = ?");
$stmt1->bind_param("s", $user_email);
$stmt1->execute();
$result1 = $stmt1->get_result();
$reg_info = $result1->fetch_assoc();

$reg_name = $user_info['fullname'] ?? '';
$reg_id = $user_info['unique_reg_id'] ?? 'N/A';
$reg_phone = $user_info['phone'] ?? '';
$reg_program = $reg_info['program'] ;

/* --- 2. FETCH EXISTING TEAM DATA FOR EDITING --- */
$stmt_team = $conn->prepare("SELECT * FROM team_information WHERE user_email = ?");
$stmt_team->bind_param("s", $user_email);
$stmt_team->execute();
$team_res = $stmt_team->get_result();
$existing_team = $team_res->fetch_assoc();

$is_edit_mode = ($team_res->num_rows > 0);
$team_id_val = $existing_team['id'] ?? null;
$team_name_val = $existing_team['team_name'] ?? '';
$college_name_val = $existing_team['college_name'] ?? '';

// Show all errors, warnings, and notices


// Fetch Members if in edit mode
$existing_members = [];
if($is_edit_mode) {
    $m_stmt = $conn->prepare("SELECT * FROM team_members WHERE team_id = ? ORDER BY id ASC");
    $m_stmt->bind_param("i", $team_id_val);
    $m_stmt->execute();
    $m_res = $m_stmt->get_result();
    while($m_row = $m_res->fetch_assoc()){
        $existing_members[] = $m_row;
    }
}

$old = $_SESSION['old'] ?? []; 
$errors = $_SESSION['errors'] ?? [];  
$success = $_SESSION['success_step2'] ?? false; 

$js_old = json_encode($old);
$js_errors = json_encode($errors);
$js_members = json_encode($existing_members);
?> 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
<meta charset="UTF-8"> 
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>InnovaHub | Step 2 – Team Information</title> 
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">  
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<style> 
:root{
  --purple-main:#a855f7;
  --text-color:#000;
  --success-green: #22c55e;
}

/* ===== RESET & BODY ===== */
*{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

body{
    min-height:100vh;
    background: url("assets/images/img.jpeg") center/cover no-repeat fixed;
    display:flex;
    justify-content:center;
    align-items: flex-start; 
    padding: 0px 15px 20px 15px; 
    color: var(--text-color);
}

/* ===== GLASS CONTAINER ===== */
.main-container{ 
    max-width:1200px; 
    width:100%; 
    display:flex; 
    flex-direction:column; 
    gap:25px; 
    margin-top: 0; 
}

.box{
    position: relative;
    background: rgba(255, 255, 255, 0.15); 
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    padding: 20px 30px; 
    overflow: hidden;
    margin-top: 0;
}

/* ===== WATERMARK EFFECT ===== */
.box::before{
    content: "";
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: url("assets/images/im.jpeg") center/cover no-repeat;
    opacity: 0.05;
    pointer-events: none;
    z-index: 0;
}

.box > * { position: relative; z-index: 1; }

/* HEADER STYLING */
.top-logos { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 5px; 
    padding-top: 5px; 
}
.top-logos img { height: 70px; object-fit: contain; }

.top-logos img.rotrax-logo {
    height: 110px; 
}

.college-header { text-align: center; margin-bottom: 15px; }
.college-header h3 { font-size: 14px; font-weight: 400; margin-top: 0; }
.college-header h1 { font-size: 24px; font-weight: 700; color: #000; line-height: 1.2; }
.college-header p { font-size: 13px; margin: 2px 0; }
.college-header h4 { font-size: 16px; font-weight: 600; color: var(--purple-main); }

.instructions{ border-left: 6px solid var(--purple-main); padding-left:15px; margin-bottom:15px; }
.instructions h3{ color: var(--purple-main); margin-bottom: 5px; font-size: 16px; }

.lock-banner {
    background: rgba(239, 68, 68, 0.2);
    color: #b91c1c;
    border: 1px solid #ef4444;
    padding: 12px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 20px;
    font-weight: 600;
}

.form-box h2{
    text-align:center;
    color:#000;
    margin-bottom:25px;
    font-weight: 700;
    border-top: 1px solid rgba(0,0,0,0.1);
    padding-top:15px;
}

label{ font-size:14px; font-weight:600; color:#000; margin-bottom:4px; display:block; }

/* ===== INPUTS ===== */
input, select, textarea{
    width:100%; padding:10px 14px; border-radius:12px; border:2px solid #000000; 
    background: rgba(255,255,255,0.4); color:#000; outline:none; font-size:14px; margin-top: 5px;
}

input:disabled, select:disabled, textarea:disabled {
    background: rgba(0,0,0,0.05);
    cursor: not-allowed;
    border-color: #666;
}

input:focus, select:focus{ background: rgba(255,255,255,0.6); border: 2px solid var(--purple-main); }

/* Green border for file selected */
input[type="file"].file-selected {
    border-color: var(--success-green) !important;
    background: rgba(34, 197, 94, 0.1) !important;
}

.readonly-field { background: rgba(0,0,0,0.05); color: #333; cursor: not-allowed; border: 2px solid #000000; }
.error-msg{ color:#ff4d4d; font-size:11px; margin-top:2px; font-weight: 600; min-height: 15px; display: block; } 
.file-hint{ font-size: 10px; color: #555; display: block; margin-top: 2px; }

/* Table scroll for mobile view */
.table-responsive { 
    width: 100%; 
    overflow-x: auto; 
    margin-top: 15px; 
    -webkit-overflow-scrolling: touch; 
    border-radius: 12px;
}

table{ width:100%; border-collapse:collapse; background: rgba(255,255,255,0.2); border-radius: 12px; min-width: 1100px; } 
th,td{ border:1px solid rgba(0,0,0,0.1); padding:10px; text-align:center; color: #000; font-size: 13px; } 
th{ background: rgba(168, 85, 247, 0.2); font-weight: 600; } 

/* Increase Year Column Width and PRN width in table */
th:nth-child(4), td:nth-child(4) { width: 80px; }
th:nth-child(5), td:nth-child(5) { width: 180px; }

button.submit-btn, .download-btn, .btn-link-custom {
    padding: 12px 30px; background: rgba(255,255,255,0.6); color: #000; font-size: 15px; font-weight: 700;
    border: 2px solid #000000; border-radius: 16px; cursor: pointer; transition: 0.3s ease; display: inline-block;
    text-decoration: none; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

button.submit-btn:disabled { background: #ccc; cursor: not-allowed; opacity: 0.6; transform: none !important; }

button.submit-btn:hover:not(:disabled), .download-btn:hover, .btn-link-custom:hover { transform: translateY(-3px) scale(1.05); background: #fff; }

.horizontal{ display:grid; grid-template-columns: 1fr 1fr; gap:15px; }  
.action-btn{ font-size:22px; cursor:pointer; color:var(--purple-main); font-weight:700; display: inline-block; margin: 0 5px; } 
.hidden{display:none;}  
.project-guide{ margin-top:20px; } 

.success-overlay {
    position: fixed; top:0; left:0; width:100%; height:100%; 
    background: rgba(0, 0, 0, 0.8); display: flex; 
    justify-content: center; align-items: center; z-index: 1000; padding: 20px;
}

@media(max-width:1024px){ 
    .horizontal{grid-template-columns:1fr;} 
    .top-logos img { height: 50px; }
    .top-logos img.rotrax-logo { height: 80px; }
    .college-header h1 { font-size: 18px; }
    .box { padding: 15px; }
}

@media(max-width:600px){
    .btn-container-flex { flex-direction: column; width: 100%; }
    .submit-btn, .download-btn, .btn-link-custom { width: 100%; margin-bottom: 10px; }
}
</style>   

<script> 
const reg_program = "<?php echo $reg_program; ?>"; //alert(reg_program);
let maxMembers = 4; 
let minMembers = 2;
if(reg_program === "PG")
{
let minMembers = 1;
}

 oldData = <?php echo $js_old; ?>;
const phpErrors = <?php echo $js_errors; ?>;
const existingMembers = <?php echo $js_members; ?>;
const isLocked = <?php echo $is_locked ? 'true' : 'false'; ?>;

const loggedInName = "<?php echo $reg_name; ?>";
const loggedInEmail = "<?php echo $user_email; ?>";
const loggedInRegID = "<?php echo $reg_id; ?>";

function showError(input, msg) {
    let msgDiv = input.nextElementSibling;
    if (input.type === "file") {
        msgDiv = input.parentNode.querySelector('.error-msg');
    }
    if (msgDiv && msgDiv.classList.contains('error-msg')) {
        msgDiv.innerText = msg;
    }
}

function initRecentValidations() {
    const teamInput = document.querySelector('input[name="team_name"]');
    if(teamInput && !isLocked) {
        teamInput.addEventListener("input", function() {
            let val = this.value;
            let errorDiv = this.nextElementSibling;
            if (!/^[a-zA-Z\s]*$/.test(val)) {
                errorDiv.innerText = "Letters only";
            } else {
                errorDiv.innerText = "";
                if (val.trim().length > 2) {
                    fetch(`step2.php?check_team=${encodeURIComponent(val.trim())}`)
                    .then(res => res.text())
                    .then(data => {
                        if (data.trim() === "exists") {
                            errorDiv.innerText = "Team name already exists!";
                        } else {
                            errorDiv.innerText = "";
                        }
                    });
                }
            }
        });
    }
}

function validateFileSize(input) {
    const file = input.files[0];
    if (file) {
        const fileSize = file.size / 1024 / 1024; // in MB
        if (fileSize > 1) {
            showError(input, "File size exceeds 1MB");
            input.value = ""; 
            input.classList.remove('file-selected');
            return false;
        } else {
            showError(input, "");
            input.classList.add('file-selected');
            return true;
        }
    } else {
        input.classList.remove('file-selected');
    }
    return true;
}

function checkDuplicateNames(){
    let names = [];
    let duplicateFound = false;
    document.querySelectorAll('input[name="student_name[]"]').forEach(input=>{
        let name = input.value.trim().toLowerCase();
        if(name !== ""){
            if(names.includes(name)){
                showError(input, "Student name already exists");
                duplicateFound = true;
            } else {
                showError(input, "");
                names.push(name);
            }
        }
    });
    return !duplicateFound;
}

function checkDuplicateEmail(input) {
    let currentEmail = input.value.trim().toLowerCase();
    if(currentEmail === "") return true;

    let emails = [];
    let duplicate = false;

    // Collect all member emails
    document.querySelectorAll('input[name="member_email[]"]').forEach(el => {
        if(el !== input && el.value.trim().toLowerCase() === currentEmail) duplicate = true;
    });

    // Check guide email
    let guideEmailEl = document.querySelector('input[name="guide_email"]');
    if(guideEmailEl && guideEmailEl !== input && guideEmailEl.value.trim().toLowerCase() === currentEmail) duplicate = true;

    if(duplicate) {
        showError(input, "Duplicate email detected");
        return false;
    } else {
        // Only clear if it was a duplicate error, not a format error
        if(input.nextElementSibling.innerText === "Duplicate email detected") {
            showError(input, "");
        }
        return validateEmail(input);
    }
}

function checkDuplicateContact(input) {
    let currentContact = input.value.trim();
    if(currentContact === "") return true;

    let duplicate = false;

    // Check against other member mobile numbers
    document.querySelectorAll('input[name="mobile_no[]"]').forEach(el => {
        if(el !== input && el.value.trim() === currentContact) duplicate = true;
    });

    // Check against Guide Contact
    let guideContactEl = document.querySelector('input[name="contact_number"]');
    if(guideContactEl && guideContactEl !== input && guideContactEl.value.trim() === currentContact) duplicate = true;

    // Check against Guide Alt Contact
    let guideAltEl = document.querySelector('input[name="alt_contact_number"]');
    if(guideAltEl && guideAltEl !== input && guideAltEl.value.trim() === currentContact) duplicate = true;

    if(duplicate) {
        showError(input, "Duplicate contact number");
        return false;
    } else {
        if(input.nextElementSibling.innerText === "Duplicate contact number") {
            showError(input, "");
        }
        return validateDigits(input, 10);
    }
}

function validatePRN(input){       
    let val = input.value.trim();       
    if(val === ""){            
        showError(input, "Field is required");            
        return false;       
    } else { 
        // Start Duplicate PRN Check
        let allPRNs = document.querySelectorAll('input[name="enrollment_prn[]"]');
        let count = 0;
        allPRNs.forEach(prnInput => {
            if(prnInput.value.trim().toLowerCase() === val.toLowerCase()){
                count++;
            }
        });

        if(count > 1) {
            alert("Duplicate PRN number: " + val + " has already been added.");
            showError(input, "Duplicate PRN number");
            input.value = ""; // Clear the field
            return false;
        }
        // End Duplicate PRN Check

        showError(input, ""); 
        return true; 
    } 
} 

function validateStudentName(input){       
    let val = input.value.trim();       
    if(!/^[A-Z][a-zA-Z ]*$/.test(val)){            
        showError(input, val === "" ? "" : "Start with Capital letter");            
        return false;       
    } else { showError(input, ""); return true; } 
}

function validateLettersOnly(input){       
    let val = input.value.trim();       
    if(!/^[a-zA-Z ]+$/.test(val)){ showError(input, "Please enter letters only"); return false; } 
    else { showError(input, ""); return true; } 
}

function validateEmail(input){       
    let regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;       
    if(!regex.test(input.value.trim())){ showError(input, "Invalid email"); return false; } 
    else { showError(input, ""); return true; } 
}

function validateDigits(input, count){       
    let val = input.value.trim();
    if(val.length > count) {
        input.value = val.slice(0, count);
        val = input.value;
    }
    if(!new RegExp(`^\\d{${count}}$`).test(val)){ showError(input, `${count} digits only`); return false; } 
    else { showError(input, ""); return true; } 
}

function addRow(data = null, index = null){       
    let tbody = document.getElementById("memberBody");       
    if(tbody.rows.length >= maxMembers && index === null) {
        alert("Minimum 2 members and maximum 4 members allowed");
        return;
    }       
    
    let i = (index !== null) ? index : tbody.rows.length;
    let isFirstRow = (i === 0);
    let memberNum = (i + 1).toString().padStart(2, '0');
    let displayID = `T-${loggedInRegID}-${memberNum}`;

    let row = tbody.insertRow();          
    row.innerHTML = `          
        <td style="font-weight:bold; color:var(--purple-main);">${displayID}</td>
        <td>             
            <input type="text" name="student_name[]" value="${isFirstRow ? loggedInName : (data?.student_name || '')}" required 
            ${(isFirstRow || isLocked) ? 'readonly class="readonly-field"' : 'onblur="validateStudentName(this); checkDuplicateNames()"' } ${isLocked ? 'disabled' : ''}>              
            <div class="error-msg">${phpErrors['student_name_'+i] || ''}</div>          
        </td>          
        <td>                
            <input type="text" name="branch[]" value="${data?.branch || ''}" required onblur="validateLettersOnly(this)" ${isLocked ? 'disabled' : ''}>                 
            <div class="error-msg">${phpErrors['branch_'+i] || ''}</div>          
        </td>          
        <td><input type="number" name="year[]" value="${data?.year || ''}" min="1" max="4" required ${isLocked ? 'disabled' : ''}></td>          
        <td>                
            <input type="text" name="enrollment_prn[]" value="${data?.enrollment_prn || ''}" onblur="validatePRN(this)" required ${isLocked ? 'disabled' : ''}>                 
            <div class="error-msg">${phpErrors['prn_'+i] || ''}</div>          
        </td>          
        <td>                
            <input type="email" name="member_email[]" value="${isFirstRow ? loggedInEmail : (data?.email || '')}" required 
            ${(isFirstRow || isLocked) ? 'readonly class="readonly-field"' : 'onblur="checkDuplicateEmail(this)"'} ${isLocked ? 'disabled' : ''}>                 
            <div class="error-msg">${phpErrors['email_'+i] || ''}</div>          
        </td>          
        <td>                
            <select name="gender[]" required ${isLocked ? 'disabled' : ''}>                    
                <option value="">Select</option>                    
                <option ${data?.gender == 'Male'?'selected':''}>Male</option>                    
                <option ${data?.gender == 'Female'?'selected':''}>Female</option>                    
            </select>          
        </td>          
        <td>                
            <input type="text" name="mobile_no[]" value="${data?.mobile_no || ''}" maxlength="10" required oninput="validateDigits(this, 10)" onblur="checkDuplicateContact(this)" ${isLocked ? 'disabled' : ''}>                 
            <div class="error-msg">${phpErrors['mobile_'+i] || ''}</div>          
        </td>          
        <td> 
            <input type="file" name="photo[]" accept="image/*" style="padding: 5px;" onchange="validateFileSize(this)" ${isLocked ? 'disabled' : ''}>
            <span class="file-hint">Choose file under 1MB</span>
            <div class="error-msg"></div>
        </td>          
        <td>                
            <span class="action-btn hidden" onclick="addRow()">+</span>                
            <span class="action-btn hidden" onclick="removeRow(this)">×</span>          
        </td>      
    `;       
    if(!isLocked) updateButtons(); 
}  

function updateButtons(){       
    if(isLocked) return;
    let rows = document.querySelectorAll("#memberBody tr");       
    rows.forEach((row)=> row.querySelectorAll(".action-btn").forEach(b=>b.classList.add("hidden")));       
    if(rows.length > 0){            
        let lastRow = rows[rows.length-1];
        lastRow.querySelector(".action-btn[onclick='addRow()']").classList.remove("hidden");
        if(rows.length > minMembers - 1) lastRow.querySelector(".action-btn[onclick*='removeRow']").classList.remove("hidden");
    } 
}  

function removeRow(btn){ 
    if(isLocked) return;
    let tbody = document.getElementById("memberBody"); 
    if(tbody.rows.length <= minMembers) {
        alert("Minimum 2 members and maximum 4 members allowed");
        return;
    } 
    btn.closest("tr").remove(); 
    updateButtons(); 
}  

function validateMembers(){ 
    if(isLocked) return false;
    let valid = checkDuplicateNames();
    
    // Final check for PRN duplicates before submission
    let prns = [];
    let prnInputs = document.querySelectorAll('input[name="enrollment_prn[]"]');
    for(let input of prnInputs) {
        let val = input.value.trim().toLowerCase();
        if(val !== "") {
            if(prns.includes(val)) {
                alert("Duplicate PRN numbers found. Please correct them before saving.");
                return false;
            }
            prns.push(val);
        }
    }

    // Check for any remaining error messages on screen
    let activeErrors = Array.from(document.querySelectorAll('.error-msg')).some(el => el.innerText !== "");
    if(activeErrors) {
        alert("Please fix the errors in the form before saving.");
        return false;
    }
    
    return valid; 
}

function setAction(action){ 
    if(isLocked) return false;
    document.getElementById("action_type").value = action; return true; 
} 

window.onload = () => {
const reg_program = "<?php echo $reg_program; ?>"; 
initRecentValidations(); 
    if(existingMembers.length > 0) existingMembers.forEach((m, idx) => addRow(m, idx));
    else {
        if(reg_program === "PG")
        {
          addRow();   
        }
        else
        {
            addRow();
            // Row 1
            addRow(); // Row 2 (to satisfy minimum 2 default)
        }
    }
};
</script> 
</head>  
<body> 

<?php if($success): ?>
<div class="success-overlay">
    <div class="box" style="text-align:center; max-width:500px; background: #fff; color: #000;">
        <h2 style="color:var(--purple-main);">Step 2 Completed! 🎉</h2>
        <p style="margin:20px 0; color: #333;">Your team information has been saved successfully.</p>
        <a href="dashboard.php" class="btn-link-custom">Go to Dashboard</a>
    </div>
</div>
<?php unset($_SESSION['success_step2']); endif; ?>

<div class="main-container">   
    <div class="box">
        <div class="top-logos">
            <img src="assets/images/Yashoda.jpg" alt="Yashoda Logo">
            <img src="assets/images/logooo.png" alt="ROTRAX Logo" class="rotrax-logo">
        </div>

        <div class="college-header">
            <h3>Yashoda Shikshan Prasarak Mandal's</h3>
            <h1>YASHODA TECHNICAL CAMPUS, SATARA</h1>
            <p>Approved by AICTE, PCI & Govt. of Maharashtra</p>
            <h4 style="color:var(--purple-main);">ROTARY CLUB OF SATARA</h4>
        </div>

        <?php if($is_locked): ?>
            <div class="lock-banner">🔒 Registration Locked: Changes are not allowed after receipt upload.</div>
        <?php endif; ?>

        <div class="instructions"> 
            <h3>Important Instructions</h3> 
            <ol style="margin-left: 20px; color: #000; font-size:14px;"> 
                <li>Registration ID and the first team member are auto-filled.</li> 
                <li> For UG / Diploma  Minimum 2  and Maximum 4 members allowed.</li> 
                <li> For PG Minimum 1  and Maximum 4 members allowed.</li> 
                <li>Ensure photos are under 1MB each.</li>
            </ol> 
        </div>  

        <div class="form-box"> 
            <h2>Step 2 – Team Information</h2>  
            <form action="step2_process.php" method="POST" enctype="multipart/form-data" onsubmit="return validateMembers();"> 
                <input type="hidden" name="action_type" id="action_type" value="save">  

                <div class="horizontal"> 
                    <div> 
                        <label>Registered ID</label> 
                        <input type="text" value="<?php echo $reg_id; ?>" readonly class="readonly-field"> 
                    </div> 
                    <div> 
                        <label>Program</label> 
                        <input type="text" value="<?php echo $reg_program; ?>" id="reg_program" readonly class="readonly-field"> 
                    </div>
                    <div> 
                        <label>Team Name *</label> 
                        <input type="text" name="team_name" value="<?php echo $old['team_name'] ?? $team_name_val; ?>" required autocomplete="off" <?php echo $is_locked ? 'disabled' : ''; ?>> 
                        <div class="error-msg"><?php echo $errors['team_name'] ?? ''; ?></div> 
                    </div> 
                     <div> 
                        <label>College Name *</label> 
                        <input type="text" name="college_name" value="<?php echo $old['college_name'] ?? $college_name_val; ?>" required <?php echo $is_locked ? 'disabled' : ''; ?>> 
                        <div class="error-msg"><?php echo $errors['college_name'] ?? ''; ?></div> 
                    </div> 
                </div>
                <h3 style="margin-top:25px; color:var(--purple-main); border-bottom: 2px solid var(--purple-main); padding-bottom: 5px;">Team Members</h3> 
                <div class="table-responsive">
                    <table> 
                        <thead>
                            <tr> 
                                <th>ID</th><th>Student Name</th><th>Branch</th><th>Year</th><th>PRN/Enrollment No</th><th>Email</th><th>Gender</th><th>Mobile</th><th>Photo</th><th>Action</th> 
                            </tr> 
                        </thead>
                        <tbody id="memberBody"></tbody> 
                    </table>
                </div>

                <div class="project-guide"> 
                    <h3 style="color:var(--purple-main); border-bottom: 2px solid var(--purple-main); padding-bottom: 5px; margin-bottom: 15px;">Project Guide Details</h3> 
                    <div class="horizontal"> 
                        <div> 
                            <label> Name *</label> 
                            <input type="text" name="guide_name" value="<?php echo $old['guide_name'] ?? ($existing_team['guide_name'] ?? ''); ?>" required onblur="validateLettersOnly(this)" <?php echo $is_locked ? 'disabled' : ''; ?>> 
                            <div class="error-msg"></div>
                        </div> 
                        <div> 
                            <label>Branch *</label> 
                            <input type="text" name="guide_branch" value="<?php echo $old['guide_branch'] ?? ($existing_team['branch'] ?? ''); ?>" required onblur="validateLettersOnly(this)" <?php echo $is_locked ? 'disabled' : ''; ?>> 
                            <div class="error-msg"></div>
                        </div> 
                        <div> 
                            <label>Designation *</label> 
                            <input type="text" name="designation" value="<?php echo $old['designation'] ?? ($existing_team['designation'] ?? ''); ?>" required <?php echo $is_locked ? 'disabled' : ''; ?>>
                            <div class="error-msg"></div>
                        </div> 
                        <div> 
                            <label>Email *</label> 
                            <input type="email" name="guide_email" value="<?php echo $old['guide_email'] ?? ($existing_team['guide_email'] ?? ''); ?>" required onblur="checkDuplicateEmail(this)" <?php echo $is_locked ? 'disabled' : ''; ?>> 
                            <div class="error-msg"></div>
                        </div> 
                        <div> 
                            <label>Contact Number *</label> 
                            <input type="text" name="contact_number" value="<?php echo $old['contact_number'] ?? ($existing_team['contact_number'] ?? ''); ?>" required maxlength="10" oninput="validateDigits(this, 10)" onblur="checkDuplicateContact(this)" <?php echo $is_locked ? 'disabled' : ''; ?>> 
                            <div class="error-msg"></div>
                        </div> 
                        <div> 
                            <label>Alternate Contact</label> 
                            <input type="text" name="alt_contact_number" value="<?php echo $old['alt_contact_number'] ?? ($existing_team['alt_contact_number'] ?? ''); ?>" maxlength="10" oninput="validateDigits(this, 10)" onblur="checkDuplicateContact(this)" <?php echo $is_locked ? 'disabled' : ''; ?>> 
                            <div class="error-msg"></div>
                        </div> 
                    </div> 
                </div> 

                <div class="btn-container-flex" style="display:flex; gap:15px; justify-content: center; margin-top: 40px; flex-wrap: wrap;">
                    <button type="submit" class="submit-btn" onclick="return setAction('continue')" <?php echo $is_locked ? 'disabled' : ''; ?>>
                        <?php echo $is_edit_mode ? "Update" : "Save"; ?>
                    </button>
                    <?php if($is_edit_mode): ?>
                        <a href="step3_project_proposal.php" class="download-btn">Continue</a>
                    <?php endif; ?>
                    <a href="dashboard.php" class="btn-link-custom">Dashboard</a>
                </div>
            </form> 
        </div> 
    </div> 
</div> 

<?php
unset($_SESSION['errors']); 
unset($_SESSION['old']); 
?>
</body> 
</html>