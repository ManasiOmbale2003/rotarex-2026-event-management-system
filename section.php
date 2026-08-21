<?php
// Fix for the session notice: Only start if one doesn't exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the external database configuration
include 'config.php';

// Load PHPMailer classes
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user'];

// --- LOCK LOGIC START ---
$is_locked = false;
$check_col = $conn->query("SHOW COLUMNS FROM `registration_payments` LIKE 'receipt_uploaded'");
if ($check_col->num_rows > 0) {
    $lock_stmt = $conn->prepare("SELECT receipt_uploaded FROM registration_payments WHERE email = ? AND receipt_uploaded = 1 LIMIT 1");
    $lock_stmt->bind_param("s", $user_email);
    $lock_stmt->execute();
    $is_locked = $lock_stmt->get_result()->num_rows > 0;
}
// Removed the exit() block to keep the form visible as requested.
// --- LOCK LOGIC END ---

// Mode logic: Check if user clicked 'Edit'
$is_viewing = (isset($_GET['mode']) && $_GET['mode'] === 'view');

function getRegionCode($district) {
    $codes = [
        "Pune"=>"PU", "Satara"=>"ST", "Sangli"=>"SN", "Kolhapur"=>"KL", "Nashik"=>"NS",
        "Jalgaon"=>"JL", "Dhule"=>"DH", "Nandurbar"=>"ND", "Nagpur"=>"NG", "Amravati"=>"AM",
        "Akola"=>"AK", "Bhandara"=>"BH", "Wardha"=>"WR", "Chandrapur"=>"CH", "Gadchiroli"=>"GD",
        "Yavatmal"=>"YV", "Aurangabad"=>"AU", "Jalna"=>"JN", "Beed"=>"BD", "Osmanabad"=>"OS",
        "Parbhani"=>"PR", "Nanded"=>"NN", "Hingoli"=>"HN", "Latur"=>"LT", "Mumbai"=>"MU",
        "Thane"=>"TH", "Raigad"=>"RG", "Ratnagiri"=>"RT", "Sindhudurg"=>"SD", "Palghar"=>"PL"
    ];
    return $codes[$district] ?? "XX";
}

// Fetch existing registration
$registration = ['program' => '', 'discipline' => '', 'theme' => '', 'region' => '', 'completed_steps' => 0];
$stmt = $conn->prepare("SELECT program, discipline, theme, region, completed_steps FROM registrations WHERE user_email=?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$res = $stmt->get_result();

$data_exists = false;
$show_success_popup = false;
$popup_message = "";

if ($res->num_rows > 0) {
    $registration = $res->fetch_assoc();
    $data_exists = true;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $program    = $_POST['program'];
    $discipline = $_POST['discipline'];
    $theme      = $_POST['theme'];
    $region     = $_POST['region'];
    $steps      = ($registration['completed_steps'] > 0) ? $registration['completed_steps'] : 1;

    if ($data_exists) {
        $q = $conn->prepare("UPDATE registrations SET program=?, discipline=?, theme=?, region=?, completed_steps=? WHERE user_email=?");
        $q->bind_param("ssssis", $program, $discipline, $theme, $region, $steps, $user_email);
        $q->execute();
        $show_success_popup = true;
        $popup_message = "Your information successfully updated!";
    } else {
        $q = $conn->prepare("INSERT INTO registrations (user_email, program, discipline, theme, region, completed_steps) VALUES (?, ?, ?, ?, ?, ?)");
        $q->bind_param("sssssi", $user_email, $program, $discipline, $theme, $region, $steps);
        $q->execute();

        // Unique ID Generation
        $u_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $u_stmt->bind_param("s", $user_email);
        $u_stmt->execute();
        $db_id = $u_stmt->get_result()->fetch_assoc()['id'];
        $formatted_id = getRegionCode($region) . "-RTX2026-" . str_pad($db_id, 2, "0", STR_PAD_LEFT); 

        $upd = $conn->prepare("UPDATE users SET unique_reg_id = ? WHERE id = ?");
        $upd->bind_param("si", $formatted_id, $db_id);
        $upd->execute();

        // Email logic
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = '';
            $mail->Password = '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('rotraxproject@gmail.com', 'ROTAREX 2026');
            $mail->addAddress($user_email);
            $mail->isHTML(true);
            $mail->Subject = 'ROTAREX 2026 Registration ID';
            $mail->Body = "Your Step 1 is complete. Registration ID: <b>$formatted_id</b>.";
            $mail->send();
        } catch (Exception $e) { }
        
        $show_success_popup = true;
        $popup_message = "Your information saved successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>InnovaHub | Step 1</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
          --purple-main:#a855f7;
          --text-color:#000;
        }

        /* ===== RESET & BODY ===== */
        * {
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Poppins',sans-serif;
        }

        body {
            min-height:100vh;
            background: url("assets/images/img.jpeg") center/cover no-repeat fixed;
            display:flex;
            justify-content:center;
            align-items:center;
            padding:15px;
            color: var(--text-color);
        }

        /* ===== GLASS CONTAINER ===== */
        .container {
            position: relative;
            background: rgba(255, 255, 255, 0.18); 
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            padding: 0 30px 30px 30px; 
            max-width: 900px;
            width: 100%;
            overflow: hidden;
        }

        /* ===== WATERMARK ===== */
        .container::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url("assets/images/im.jpeg") center/cover no-repeat;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
        }

        /* ===== HEADER SECTION ===== */
        .header-section {
            position: relative;
            z-index: 2;
            text-align: center;
            padding-top: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .header-logos {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .header-logos .college-logo {
            max-height: 75px;
            width: auto;
        }

        .header-logos .rotrax-logo {
            max-height: 100px; 
            width: auto;
            transform: scale(1.1); 
        }

        .college-header h3 {
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .college-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 2px 0;
            color: #1a1a1a;
        }

        .college-header p {
            font-size: 11px;
            margin-bottom: 5px;
        }

        .college-header h4 {
            font-size: 18px;
            font-weight: 700;
            color: #2e2e2e;
            margin-bottom: 10px;
        }

        h2.step-title {
            text-align: center;
            color: #000;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
            font-size: 20px;
            font-weight: 600;
        }

        /* ===== FORM FIELDS ===== */
        form {
            position: relative;
            z-index: 1;
        }

        .form-row {
            display:grid;
            grid-template-columns: 180px 1fr;
            gap: 15px;
            margin-bottom: 15px;
            align-items: center;
        }

        label {
            font-size: 14px;
            font-weight: 600;
        }

        select, .readonly-box {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1.5px solid #000; 
            background: rgba(255,255,255,0.4);
            font-size: 14px;
            color: #000;
            outline: none;
            transition: 0.3s;
        }

        /* Style for validation error */
        .error-field {
            border-color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.1) !important;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }

        /* ===== BUTTONS ===== */
        .btn-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        button, .edit-link {
            padding: 12px 30px;
            background: #000;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            border: 2px solid #000;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            min-width: 150px;
            transition: 0.3s;
        }

        button:hover, .edit-link:hover {
            background: transparent;
            color: #000;
        }

        .edit-banner {
            background: rgba(255, 193, 7, 0.3); 
            color: #856404; 
            padding: 10px; 
            border-radius: 10px; 
            text-align: center; 
            margin-bottom: 15px; 
            font-size: 13px;
            font-weight: 500;
        }

        .lock-banner {
            background: rgba(239, 68, 68, 0.2);
            color: #b91c1c;
            border: 1px solid #ef4444;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        button:disabled {
            background: #666;
            cursor: not-allowed;
            border-color: #666;
        }

        @media(max-width:768px){
            .container { padding: 0 15px 25px 15px; border-radius: 15px; }
            .header-logos { flex-direction: row; justify-content: space-between; }
            .header-logos .rotrax-logo { max-height: 60px; transform: scale(1); }
            .header-logos .college-logo { max-height: 50px; }
            .college-header h1 { font-size: 16px; }
            .college-header h4 { font-size: 15px; }
            .form-row { grid-template-columns: 1fr; gap: 5px; }
            h2.step-title { font-size: 18px; }
            button, .edit-link { width: 100%; min-width: unset; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <div class="header-logos">
            <img src="/rotrax/assets/images/Yashoda.png" alt="Yashoda Logo" class="college-logo">
            <img src="/rotrax/assets/images/logooo.png" alt="ROTRAX Logo" class="rotrax-logo">
        </div>

        <div class="college-header">
            <h3>Yashoda Shikshan Prasarak Mandal's</h3>
            <h1>YASHODA TECHNICAL CAMPUS, SATARA</h1>
            <p>Approved by AICTE, PCI & Govt. of Maharashtra</p>
            <h4>ROTARY CLUB OF SATARA</h4>
        </div>
    </div>

    <h2 class="step-title">Step 1: Program & Theme Selection</h2>

    <?php if($is_locked): ?>
        <div class="lock-banner">🔒 Registration Locked: This form cannot be edited because your receipt has been uploaded.</div>
    <?php elseif($data_exists && !$is_viewing): ?>
        <div class="edit-banner">Updating your existing registration information.</div>
    <?php endif; ?>

    <form method="POST" id="regForm" onsubmit="return validateSelection()">
        <div class="form-row">
            <label>Program *</label>
            <?php if($is_viewing): ?>
                <div class="readonly-box"><?php echo $registration['program']; ?></div>
            <?php else: ?>
                <select name="program" id="program" onchange="updateDiscipline(); removeError(this)" required <?php echo $is_locked ? 'disabled' : ''; ?>>
                    <option value="">-- Select Program --</option>
                    <option <?php echo ($registration['program'] == 'Diploma') ? 'selected' : ''; ?>>Diploma</option>
                    <option <?php echo ($registration['program'] == 'UG') ? 'selected' : ''; ?>>UG</option>
                    <option <?php echo ($registration['program'] == 'PG') ? 'selected' : ''; ?>>PG</option>
                </select>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label>Project Discipline *</label>
            <?php if($is_viewing): ?>
                <div class="readonly-box"><?php echo $registration['discipline']; ?></div>
            <?php else: ?>
                <select name="discipline" id="discipline" onchange="updateThemes(); removeError(this)" required <?php echo $is_locked ? 'disabled' : ''; ?>>
                    <?php if($registration['discipline']) echo "<option value='".$registration['discipline']."' selected>".$registration['discipline']."</option>"; ?>
                    <option value="">-- Select Discipline --</option>
                </select>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label>Project Theme *</label>
            <?php if($is_viewing): ?>
                <div class="readonly-box"><?php echo $registration['theme']; ?></div>
            <?php else: ?>
                <select name="theme" id="theme" onchange="removeError(this)" required <?php echo $is_locked ? 'disabled' : ''; ?>>
                    <?php if($registration['theme']) echo "<option value='".addslashes($registration['theme'])."' selected>".$registration['theme']."</option>"; ?>
                    <option value="">-- Select Theme --</option>
                </select>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label>District *</label>
            <?php if($is_viewing): ?>
                <div class="readonly-box"><?php echo $registration['region']; ?></div>
            <?php else: ?>
                <select name="region" id="region" onchange="removeError(this)" required <?php echo $is_locked ? 'disabled' : ''; ?>>
                    <option value="">-- Select District --</option>
                    <?php
                    $districts = ["Pune","Satara","Sangli","Kolhapur","Nashik","Jalgaon","Dhule","Nandurbar","Nagpur","Amravati","Akola","Bhandara","Wardha","Chandrapur","Gadchiroli","Yavatmal","Aurangabad","Jalna","Beed","Osmanabad","Parbhani","Nanded","Hingoli","Latur","Mumbai","Thane","Raigad","Ratnagiri","Sindhudurg","Palghar"];
                    foreach($districts as $d) {
                        $sel = ($registration['region'] == $d) ? 'selected' : '';
                        echo "<option value='$d' $sel>$d</option>";
                    }
                    ?>
                </select>
            <?php endif; ?>
        </div>

        <div class="btn-container">
            <?php if($is_viewing): ?>
                <a href="step1.php" class="edit-link">Edit Details</a>
                <a href="step2.php" class="edit-link" style="background: rgba(255,255,255,0.45); color: #000;">Continue</a>
            <?php else: ?>
                <button type="submit" <?php echo $is_locked ? 'disabled' : ''; ?>><?php echo $data_exists ? "Update & Continue" : "Save & Continue"; ?></button>
                <?php if($data_exists): ?>
                    <a href="step2.php" class="edit-link" style="background: rgba(255,255,255,0.45); color: #000;">Continue</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
const programData = {
    "Diploma": ["Computer Engineering & Allied", "Artificial Intelligence & Data Science", "Mechanical & Industry 4.0", "Electrical & Energy Engineering", "Civil & Environmental Engineering","Electronics and Embedded Systems"],
    "UG": ["Computer Engineering & Allied", "Artificial Intelligence & Data Science", "Mechanical & Industry 4.0", "Electrical & Energy Engineering", "Civil & Environmental Engineering", "Electronics and Embedded Systems","BCA"],
    "PG": ["Computer Engineering & Allied","Artificial Intelligence & Data Science", "Mechanical & Industry 4.0", "Electrical & Energy Engineering","Civil & Environmental Engineering", "Robotics & Mechatronics", "Electronics & Allied", "Electronics and Embedded Systems","MCA"]
};

const disciplineThemes = {
    "Artificial Intelligence & Data Science": [
        "AI-Based Smart Agriculture and Crop Prediction System", "Smart & Sustainable Cities Using AI, IoT and Data Analytics",
        "Multimodal AI: Beyond Text", "Interactive Intelligent Robot for a Better Tomorrow",
        "Intelligent Systems for Waste Reduction and Sustainable Production", "Smart Agriculture & Food Security",
        "Artificial Intelligence & Machine Learning", "Health Informatics / Healthcare IT",
        "AI for social good", "EcoVerse AI: Intelligent Solutions for Sustainability"
    ],
    "Computer Engineering & Allied": [
        "Software Development & Application Engineering", "Cybercrime Detection and Digital Forensics Toolkit",
        "Digital Solutions for Quality Education", "Digital Tools for Skill Development",
        "Technology to Support Industry and Startups", "Women Safety & Social Security Systems",
        "Smart Healthcare Systems", "Agentic AI and the Future of Automation",
        "Automation & Control Systems"
    ],
    "MCA": [
        "Software Development & Application Engineering", "Cybercrime Detection and Digital Forensics Toolkit",
        "Digital Solutions for Quality Education", "Digital Tools for Skill Development",
        "Technology to Support Industry and Startups", "Women Safety & Social Security Systems",
        "Smart Healthcare Systems", "Agentic AI and the Future of Automation",
        "Automation & Control Systems", "IoT & Embedded Systems", "E-Commerce Web Application"
    ],
    "Mechanical & Industry 4.0": [
        "Sustainable & Green Mechanical System", "Material Handling & Logistics System",
        "Machines for Agricultural & Rural Development ", "Automobile & E-Mobility Innovations",
        "Smart Manufacturing & Industry 4.0", "Intelligent Systems for Waste Reduction and Sustainable Production",
        "Advances in Mechanical Engineering", "Applications of AI in Mechanical Engineering",
        "Industrial Automation"
    ],
    "Civil & Environmental Engineering": [
        "Smart & Sustainable Infrastructure Development", "Water Resource Management & Conservation",
        "Climate-Resilient & Disaster-Resistant Structures", "Environmental Monitoring & Climate Action",
        "Smart Waste Segregation and Management System", "Waste Management & Circular Economy in Construction",
        "Affordable, Inclusive & Rural Infrastructure Development", "Application of AI in Construction Industry"
    ],
    "Electrical & Energy Engineering": [
        "Smart Energy Monitoring and Optimization System", "Automation for Sustainable and Clean Energy Solutions",
        "Electric Vehicles", "Automation for Sustainable and Clean Energy Solutions","Electric Vehicles & Charging Infrastructure",
        "Power Systems & Protection", "Smart Power Systems", "Renewable Energy"
    ],
    "BCA": [
        "Software Development & Application Engineering", "Digital Solutions for Quality Education",
        "Digital Tools for Skill Development", "Technology to Support Industry and Startups"
    ],
    "Robotics & Mechatronics": [
        "Interactive Intelligent Robot for a Better Tomorrow", "Automation & Control Systems", "Industrial Automation"
    ],
    "Electronics & Allied": [
        "IoT & Embedded Systems", "Automation & Control Systems", "Smart Power Systems"
    ],
    "Electronics and Embedded Systems": [
        "IoT-Based Health Monitoring System for Rural Areas", "IoT & Embedded Systems", "Technology to Support Industry and Start-ups", "Robotics and Its Applications","IoT & Robotics"
    ]
};

function updateDiscipline(){
    const p = document.getElementById('program').value;
    const dSelect = document.getElementById('discipline');
    if(!dSelect) return;
    dSelect.innerHTML = '<option value="">-- Select Discipline --</option>';
    if(!programData[p]) return;
    programData[p].forEach(d=>{
        const isSelected = (d === "<?php echo $registration['discipline']; ?>") ? 'selected' : '';
        dSelect.innerHTML += `<option value="${d}" ${isSelected}>${d}</option>`;
    });
    updateThemes();
}

function updateThemes(){
    const d = document.getElementById('discipline').value;
    const tSelect = document.getElementById('theme');
    if(!tSelect) return;
    tSelect.innerHTML = '<option value="">-- Select Theme --</option>';
    
    const themes = disciplineThemes[d] || [];
    const currentTheme = "<?php echo addslashes($registration['theme']); ?>";
    
    themes.forEach(t => {
        const isSelected = (t === currentTheme) ? 'selected' : '';
        tSelect.innerHTML += `<option value="${t}" ${isSelected}>${t}</option>`;
    });
}

function validateSelection() {
    let isValid = true;
    const selects = document.querySelectorAll('select[required]');
    
    selects.forEach(select => {
        if (select.value === "" || select.value.includes("-- Select")) {
            select.classList.add('error-field');
            isValid = false;
        } else {
            select.classList.remove('error-field');
        }
    });
    
    return isValid;
}

function removeError(el) {
    if (el.value !== "" && !el.value.includes("-- Select")) {
        el.classList.remove('error-field');
    }
}

window.onload = () => { 
    if(document.getElementById('program')) {
        updateDiscipline(); 
    }

    <?php if ($show_success_popup): ?>
    alert("<?php echo $popup_message; ?>");
    window.location.href = "step2.php";
    <?php endif; ?>
};
</script>
</body>
</html>