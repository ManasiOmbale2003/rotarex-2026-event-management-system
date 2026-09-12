<?php
// --- SECURITY CHECK START ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
// --- SECURITY CHECK END ---

include 'config.php';
// Include PHPMailer classes for the email notification feature
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// MANUAL LOAD: Points to the PHPMailer folder containing Exception.php, PHPMailer.php, and SMTP.php
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

/* ---------- SOFT DELETE & RESTORE LOGIC ---------- */
// Handle Soft Delete (Hide from dashboard)
if (isset($_POST['delete_team_admin'])) {
    $email = $conn->real_escape_string($_POST['target_email']);
    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE users SET deleted_at = '$now' WHERE email = '$email'";
    if ($conn->query($sql)) {
        echo "<script>alert('Record hidden from dashboard.'); window.location.href='admin_dashboard.php';</script>";
    }
}

// Handle Restore (Unhide)
if (isset($_POST['restore_record'])) {
    $email = $conn->real_escape_string($_POST['target_email']);
    $sql = "UPDATE users SET deleted_at = NULL WHERE email = '$email'";
    if ($conn->query($sql)) {
        echo "<script>alert('Record restored successfully!'); window.location.href='admin_dashboard.php?view=trash';</script>";
    }
}

/* ---------- ADD 5TH MEMBER LOGIC ---------- */
if (isset($_POST['add_extra_member'])) {
    $team_id = $conn->real_escape_string($_POST['team_id']);
    $name = $conn->real_escape_string($_POST['m_name']);
    $email = $conn->real_escape_string($_POST['m_email']);
    $phone = $conn->real_escape_string($_POST['m_phone']);
    $prn = $conn->real_escape_string($_POST['m_prn']);
    $branch = $conn->real_escape_string($_POST['m_branch']);
    
    // Check current count
    $count_sql = "SELECT COUNT(*) as total FROM team_members WHERE team_id = '$team_id'";
    $count_res = $conn->query($count_sql)->fetch_assoc();
    
    if ($count_res['total'] >= 5) {
        echo "<script>alert('Maximum limit of 5 members reached for this team.');</script>";
    } else {
        // Fetch District and Team Info to generate Member ID Code
        $info_sql = "SELECT r.region, t.user_email FROM team_information t 
                     JOIN registrations r ON t.user_email = r.user_email 
                     WHERE t.id = '$team_id'";
        $info_res = $conn->query($info_sql)->fetch_assoc();
        $district_code = strtoupper(substr($info_res['region'] ?? 'XX', 0, 2));
        
        // Generate Unique Member ID Code (e.g., T-PN-RTX2026-05-05)
        $member_code = "T-" . $district_code . "-RTX2026-" . str_pad($team_id, 2, '0', STR_PAD_LEFT) . "-" . str_pad(($count_res['total'] + 1), 2, '0', STR_PAD_LEFT);

        $insert_sql = "INSERT INTO team_members (team_id, student_name, email, mobile_no, enrollment_prn, branch, member_id_code, year) 
                       VALUES ('$team_id', '$name', '$email', '$phone', '$prn', '$branch', '$member_code', 2)";
        if ($conn->query($insert_sql)) {
            echo "<script>alert('Member added successfully! ID: $member_code'); window.location.href='admin_dashboard.php';</script>";
        } else {
            echo "<script>alert('Error adding member: " . $conn->error . "'); window.location.href='admin_dashboard.php';</script>";
        }
    }
}

/* ---------- HANDLE TICKET STATUS UPDATE & EMAIL ---------- */
if (isset($_POST['mark_solved'])) {
    $ticket_id = $conn->real_escape_string($_POST['ticket_id']);
    
    // Fetch user details for email before updating
    $ticket_info_query = "SELECT user_email, subject FROM support_tickets WHERE id = '$ticket_id'";
    $ticket_info_result = $conn->query($ticket_info_query);
    
    if ($ticket_info_result && $ticket_info_result->num_rows > 0) {
        $ticket_info = $ticket_info_result->fetch_assoc();
        $user_email = $ticket_info['user_email'];
        $ticket_subject = $ticket_info['subject'];

        $sql = "UPDATE support_tickets SET status = 'solved', solved_at = NOW() WHERE id = '$ticket_id'";
        if ($conn->query($sql)) {
            // Send Notification Email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; 
                $mail->SMTPAuth   = true;
                $mail->Username = ''; 
                $mail->Password = ''; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('rotraxproject@gmail.com', 'ROTAREX 2026 Support');
                $mail->addAddress($user_email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Support Ticket Resolved: ' . $ticket_subject;
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; color: #333;'>
                        <h2>Ticket Resolved</h2>
                        <p>Dear Participant,</p>
                        <p>We are writing to inform you that your support ticket regarding <b>$ticket_subject</b> has been marked as <b>Solved</b> by our administration team.</p>
                        <p><b>Ticket ID:</b> #$ticket_id</p>
                        <hr>
                        <p>If you still face any issues, please feel free to raise a new ticket from your dashboard.</p>
                        <p>Best Regards,<br><b>Team ROTAREX 2026</b></p>
                    </div>";

                $mail->send();
                echo "<script>alert('Ticket marked as solved and notification email sent to $user_email!'); window.location.href='admin_dashboard.php?section=received_tickets';</script>";
            } catch (Exception $e) {
                echo "<script>alert('Ticket marked solved, but email could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href='admin_dashboard.php?section=received_tickets';</script>";
            }
        }
    } else {
        echo "<script>alert('Ticket not found.'); window.location.href='admin_dashboard.php?section=received_tickets';</script>";
    }
}

// Toggle View (Normal or Trash)
$view = $_GET['view'] ?? 'active';

// Get Section (reports, received_tickets, solved_tickets)
$section = $_GET['section'] ?? 'reports';

// Get Filter Values
$program = $_GET['program'] ?? '';
$discipline = $_GET['discipline'] ?? '';
$theme = $_GET['theme'] ?? '';
$district = $_GET['district'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// Base Query - Updated to fetch guide details and member IDs
$query = "SELECT u.fullname, u.email as user_email, u.unique_reg_id, u.created_at as user_created_at, u.deleted_at, u.phone,
          IF(u.deleted_at IS NOT NULL, 1, 0) as delete_status,
          r.program, r.discipline, r.theme, r.region as district, r.created_at as reg_date,
          p.status as payment_status, p.payment_id, p.amount,
          t.team_name, t.college_name, t.guide_name, t.contact_number, t.id as team_id,
          (SELECT GROUP_CONCAT(CONCAT(student_name, ' [', member_id_code, ']') SEPARATOR ', ') FROM team_members WHERE team_id = t.id) as members,
          pp.project_title, pp.project_description, pp.requirements, pp.project_discipline, 
          pp.innovative_features, pp.tools_technologies, pp.expected_outcome, pp.abstract_pdf_path,
          IF(t.user_email IS NOT NULL, 1, 0) as has_step2,
          IF(pp.user_email IS NOT NULL, 1, 0) as has_step3,
          IF(r.user_email IS NOT NULL, 1, 0) as has_step1
          FROM users u
          LEFT JOIN registrations r ON u.email = r.user_email 
          LEFT JOIN team_information t ON u.email = t.user_email
          LEFT JOIN registration_payments p ON u.email = p.email
          LEFT JOIN project_proposals pp ON u.email = pp.user_email
          WHERE u.role != 'admin'";

// Apply Soft Delete Filter
if ($view === 'trash') {
    $query .= " AND u.deleted_at IS NOT NULL";
} else {
    $query .= " AND u.deleted_at IS NULL";
}

if($program && $program !== "") {
    $query .= " AND r.program = '" . $conn->real_escape_string($program) . "'";
}
if($discipline && $discipline !== "") {
    $query .= " AND r.discipline = '" . $conn->real_escape_string($discipline) . "'";
}
if($theme && $theme !== "") {
    $query .= " AND r.theme = '" . $conn->real_escape_string($theme) . "'";
}
if($district && $district !== "") {
    $query .= " AND r.region = '" . $conn->real_escape_string($district) . "'";
}
if($from_date && $to_date) {
    $query .= " AND u.created_at BETWEEN '" . $conn->real_escape_string($from_date) . " 00:00:00' AND '" . $conn->real_escape_string($to_date) . " 23:59:59'";
}

$query .= " ORDER BY u.id DESC";
$result = $conn->query($query);

// Array to store name map dynamically for matching emails to names in tickets
$user_name_mapping = [];

// Clone for modals
$delete_search_list = [];
$result_clone = $conn->query($query);
if ($result_clone) {
    while($r = $result_clone->fetch_assoc()) { 
        $delete_search_list[] = $r; 
        if(!empty($r['user_email']) && !empty($r['fullname'])) {
            $user_name_mapping[$r['user_email']] = $r['fullname'];
        }
    }
}

// Fetch Received Tickets (pending/open tickets)
$received_tickets_query = "SELECT * FROM support_tickets WHERE status != 'solved' ORDER BY created_at DESC";
$received_tickets_result = $conn->query($received_tickets_query);

// Fetch Solved Tickets
$solved_tickets_query = "SELECT * FROM support_tickets WHERE status = 'solved' ORDER BY solved_at DESC";
$solved_tickets_result = $conn->query($solved_tickets_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> InnovaHub Admin | Filter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --accent-purple: #c084fc;
            --deep-purple: #9333ea;
            --bg-dark: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.07);
        }

        body { 
            background: var(--bg-dark) url("assets/images/img.jpeg") no-repeat center center fixed;
            background-size: cover;
            font-family: 'Segoe UI', 'Times New Roman', serif;
            color: #000000;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .filter-section, .data-card, .ticket-card { 
            background: var(--glass-bg); 
            backdrop-filter: blur(15px) saturate(180%);
            -webkit-backdrop-filter: blur(15px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 25px; 
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.4); 
            margin-bottom: 30px; 
            color: #000000;
        }

        .navbar {
            background: rgba(15, 23, 42, 0.9) !important;
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .form-label { color: var(--accent-purple); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control, .form-select {
            background: rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #000000 !important;
            border-radius: 10px;
            padding: 10px 15px;
        }
        .form-select option { background: #1e293b; color: #000000; }

        .btn-filter { 
            background: linear-gradient(135deg, var(--accent-purple), var(--deep-purple)); 
            color: #000000; 
            border: none;
            border-radius: 10px; 
            padding: 12px 30px;
            font-weight: 600;
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-filter:hover { transform: translateY(-3px); box-shadow: 0 5px 25px rgba(192, 132, 252, 0.5); color: #000000; }

        .table { color: #000000 !important; background: transparent !important; border-collapse: separate; border-spacing: 0 8px; }
        .table thead th { 
            background: rgba(30, 41, 59, 0.9) !important; 
            color: var(--accent-purple) !important; 
            border: none !important;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 1.5px;
            padding: 15px !important;
        }
        .table tbody tr { 
            background: rgba(255, 255, 255, 0.05); 
            transition: 0.3s ease; 
            border-radius: 10px;
        }
        .table tbody tr:hover { background: rgba(255, 255, 255, 0.12) !important; transform: scale(1.002); }
        .table td { border: none !important; padding: 15px !important; vertical-align: middle; font-size: 13px; border-top: 1px solid rgba(255,255,255,0.05); color: #000000; }

        /* DataTables Customization */
        .dt-buttons .btn { 
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #000000 !important;
            border-radius: 8px !important;
            margin-right: 5px;
            padding: 7px 18px;
            font-size: 12px;
            transition: 0.3s;
        }
        .dt-buttons .buttons-copy { background: #475569 !important; }
        .dt-buttons .buttons-csv { background: #059669 !important; }
        .dt-buttons .buttons-pdf { background: #be123c !important; }
        .dt-buttons .buttons-print { background: #2563eb !important; }
        .dt-buttons .buttons-delete-trigger { background: #dc2626 !important; border-color: #dc2626 !important; }

        .dataTables_filter input {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid var(--accent-purple) !important;
            color: #000000 !important;
            border-radius: 20px;
            padding: 6px 20px;
            margin-left: 10px;
        }

        .report-controls {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 25px;
            background: rgba(0,0,0,0.25);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .btn-report {
            font-size: 12px;
            padding: 10px 18px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
            color: #000000;
            transition: 0.3s;
        }
        .btn-report:hover { background: rgba(192, 132, 252, 0.2); color: #000000; border-color: var(--accent-purple); }
        .btn-report.active { background: var(--accent-purple) !important; color: #000000 !important; font-weight: 700; box-shadow: 0 4px 15px rgba(192, 132, 252, 0.3); }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0; left: 0; height: 100vh; width: 260px;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.98) 100%);
            backdrop-filter: blur(25px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1050;
            padding-top: 20px;
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-header { padding: 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); margin-bottom: 20px; text-align: center; }
        .sidebar-header h4 { color: var(--accent-purple); font-weight: 800; letter-spacing: 2px; }
        
        .sidebar-menu li a {
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.7);
            border-radius: 0 30px 30px 0;
            margin: 4px 15px 4px 0;
            font-weight: 500;
        }
        .sidebar-menu li a:hover { background: rgba(192, 132, 252, 0.1); color: var(--accent-purple); }
        .sidebar-menu li a.active {
            background: linear-gradient(90deg, var(--accent-purple), var(--deep-purple));
            color: #000000;
            box-shadow: 5px 5px 15px rgba(0,0,0,0.3);
        }

        .main-content { margin-left: 260px; padding: 30px; transition: 0.3s; }
        .navbar { margin-left: 260px; width: calc(100% - 260px); }

        /* Badge Styles */
        .badge { padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 10px; text-transform: uppercase; }
        .badge.bg-info { background: rgba(14, 165, 233, 0.2) !important; color: #38bdf8 !important; border: 1px solid #0ea5e9; }
        .badge.bg-success { background: rgba(34, 197, 94, 0.2) !important; color: #4ade80 !important; border: 1px solid #22c55e; }
        .badge.bg-danger { background: rgba(239, 68, 68, 0.2) !important; color: #f87171 !important; border: 1px solid #ef4444; }

        .pdf-viewer {
            width: 100%;
            height: 400px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 15px;
        }

        .attachment-container {
            margin-top: 20px;
            padding: 15px;
            background: rgba(0,0,0,0.1);
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.2);
        }

        .attachment-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 6px;
            margin-top: 10px;
            display: block;
        }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content, .navbar { margin-left: 0; width: 100%; }
            .sidebar-toggle { display: block; }
        }
    </style>
</head>
<body>

<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><i class="fa-solid fa-bolt me-2"></i>InnovaHub</h4>
        <p class="text-muted small">Admin Control Panel</p>
    </div>

    <ul class="sidebar-menu list-unstyled">
        <li>
            <a href="admin_dashboard.php?section=reports&view=<?php echo $view; ?>" class="nav-link <?php echo ($section === 'reports') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-bar me-2"></i>
                Reports
            </a>
        </li>
        <li>
            <a href="admin_dashboard.php?section=received_tickets" class="nav-link <?php echo ($section === 'received_tickets') ? 'active' : ''; ?>">
                <i class="fa-solid fa-ticket me-2"></i>
                Received Tickets
                <?php 
                $pending_count = $received_tickets_result ? $received_tickets_result->num_rows : 0;
                if($pending_count > 0): 
                ?>
                <span class="badge bg-danger ms-auto"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="admin_dashboard.php?section=solved_tickets" class="nav-link <?php echo ($section === 'solved_tickets') ? 'active' : ''; ?>">
                <i class="fa-solid fa-check-circle me-2"></i>
                Solved Tickets
            </a>
        </li>
        <li>
            <a href="add_credential.php" class="nav-link">
                <i class="fa-solid fa-folder-plus me-2"></i>
                Add Credentials
            </a>
        </li>
    </ul>

    <div class="sidebar-divider" style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 25px;"></div>

    <ul class="sidebar-menu list-unstyled">
        <li>
            <?php if($view === 'trash'): ?>
                <a href="admin_dashboard.php?section=reports&view=active" class="nav-link">
                    <i class="fa-solid fa-eye me-2"></i>
                    Show Active Records
                </a>
            <?php else: ?>
                <a href="admin_dashboard.php?section=reports&view=trash" class="nav-link text-warning">
                    <i class="fa-solid fa-trash-arrow-up me-2"></i>
                    Recycle Bin
                </a>
            <?php endif; ?>
        </li>
    </ul>

    <div class="sidebar-footer" style="position: absolute; bottom: 20px; width: 100%; padding: 0 15px;">
        <a href="logout.php" class="btn btn-outline-danger w-100 border-0 text-start ps-4">
            <i class="fa-solid fa-power-off me-2"></i>
            Logout
        </a>
    </div>
</div>

<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#" style="color: var(--accent-purple);"><i class="fa-solid fa-bolt me-2"></i>INNOVAHUB ADMIN</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link text-dark" href="admin_dashboard.php">Dashboard</a></li>
                <li class="nav-item ms-2">
                    <?php if($view === 'trash'): ?>
                        <a class="btn btn-success btn-sm px-3" href="admin_dashboard.php?view=active">Show Active</a>
                    <?php else: ?>
                        <a class="btn btn-warning btn-sm px-3" href="admin_dashboard.php?view=trash"><i class="fa-solid fa-trash-arrow-up"></i> Recycle Bin</a>
                    <?php endif; ?>
                </li>
                <li class="nav-item ms-3"><a class="nav-link text-danger" href="logout.php"><i class="fa-solid fa-power-off"></i></a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-content">
    <?php if($section === 'reports'): ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold m-0" style="color: #000000;">
                <i class="fa-solid fa-file-invoice me-2 text-info"></i> 
                <?php echo ($view === 'trash') ? 'Recycle Bin' : 'Registration Reports'; ?>
            </h3>
        </div>

        <div class="filter-section">
            <h6 class="fw-bold mb-4" style="color: var(--accent-purple);"><i class="fa-solid fa-sliders me-2"></i> Advanced Filters</h6>
            <form method="GET" class="row g-3" id="filterForm">
                <input type="hidden" name="view" value="<?php echo $view; ?>">
                <input type="hidden" name="section" value="reports">
                <div class="col-md-2">
                    <label class="form-label">Program</label>
                    <select name="program" id="admin_program" class="form-select" onchange="updateAdminDiscipline()">
                        <option value="">All Programs</option>
                        <option value="Diploma" <?php if($program == 'Diploma') echo 'selected'; ?>>Diploma</option>
                        <option value="UG" <?php if($program == 'UG') echo 'selected'; ?>>UG</option>
                        <option value="PG" <?php if($program == 'PG') echo 'selected'; ?>>PG</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Discipline</label>
                    <select name="discipline" id="admin_discipline" class="form-select">
                        <option value="">All Disciplines</option>
                        <?php if($discipline): ?>
                            <option value="<?php echo $discipline; ?>" selected><?php echo $discipline; ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Project Theme</label>
                    <select name="theme" id="admin_theme" class="form-select">
                        <option value="">All Themes</option>
                        <?php
                        $themes = ["Software Development & Application Engineering", "Artificial Intelligence & Machine Learning", "Health Informatics / Healthcare IT", "Cybercrime Detection and Digital Forensics Toolkit", "IoT-Based Health Monitoring System for Rural Areas", "AI-Based Smart Agriculture and Crop Prediction System", "Smart Energy Monitoring and Optimization System", "Smart Waste Segregation and Management System", "Sustainable & Green Mechanical System", "Smart Manufacturing & Industry 4.0", "Agricultural & Rural Development Machines", "Automobile & E-Mobility Innovations", "Material Handling & Logistics System", "Smart & Sustainable Infrastructure Development", "Water Resources Management & Conservation", "Climate-Resilient & Disaster-Resistant Structures", "Waste Management & Circular Economy in Construction", "Affordable, Inclusive & Rural Infrastructure Development", "Electric Vehicles", "Automation & Control Systems", "Power Systems & Protection", "Renewable Energy", "IoT & Embedded Systems", "AI for social good", "Agentic AI and Autonomous intelligent systems", "Sustainable Smart Cities", "Advances in Mechanical Engineering", "Applications of AI in Mechanical Engineering", "Electric Vehicles & Charging Infrastructure", "Industrial Automation", "Smart Power Systems", "Innovative and Sustainable Infrastructure Development", "Application of AI in Construction Industry", "Advanced Construction Materials and Equipments"];
                        foreach($themes as $t) {
                            $sel = ($theme == $t) ? 'selected' : '';
                            echo "<option value=\"$t\" $sel>$t</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">District</label>
                    <select name="district" class="form-select">
                        <option value="">All Districts</option>
                        <?php
                        $districts = ["Pune","Satara","Sangli","Kolhapur","Nashik","Jalgaon","Dhule","Nandurbar","Nagpur","Amravati","Akola","Bhandara","Wardha","Chandrapur","Gadchiroli","Yavatmal","Aurangabad","Jalna","Beed","Osmanabad","Parbhani","Nanded","Hingoli","Latur","Mumbai","Thane","Raigad","Ratnagiri","Sindhudurg","Palghar"];
                        foreach($districts as $d) {
                            $sel = ($district == $d) ? 'selected' : '';
                            echo "<option value=\"$d\" $sel>$d</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <div class="input-group">
                        <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                        <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                    </div>
                </div>
                <div class="col-12 text-end mt-4">
                    <a href="admin_dashboard.php?section=reports&view=<?php echo $view; ?>" class="btn btn-link text-dark text-decoration-none me-3">Clear All</a>
                    <button type="submit" class="btn btn-filter px-5"><i class="fa-solid fa-magnifying-glass me-2"></i> Apply Filters</button>
                </div>
            </form>
        </div>

        <div class="data-card">
            <div class="report-controls">
                <button class="btn-report active" onclick="switchReportView('all', this)">Full Comprehensive</button>
                <button class="btn-report" onclick="switchReportView('step1', this)">Step 1: Student & Stream</button>
                <button class="btn-report" onclick="switchReportView('step2', this)">Step 2: Team & College</button>
                <button class="btn-report" onclick="switchReportView('step3', this)">Step 3: Project Proposal</button>
                <button class="btn-report" onclick="switchReportView('step4', this)">Step 4: Payment Details</button>
                <button class="btn-report" onclick="switchReportView('step1_completed', this)">Step 1 Completed Only</button>
                <button class="btn-report" onclick="switchReportView('step2_completed', this)">Step 2 Completed Only</button>
                <button class="btn-report" onclick="switchReportView('pending_tickets_rep', this)">Pending Tickets Report</button>
                <button class="btn-report" onclick="switchReportView('solved_tickets_rep', this)">Solved Tickets Report</button>
            </div>

            <div class="table-responsive">
                <table id="adminTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Reg ID</th>
                            <th>Status</th>
                            <th>Student Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Program</th>
                            <th>Discipline</th>
                            <th>Theme</th>
                            <th>Project Title</th>
                            <th>Team Name</th>
                            <th>Members [ID]</th>
                            <th>College</th>
                            <th>District</th>
                            <th>Payment ID</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                            <th>Account Created</th>
                            <th>Guide Name</th> 
                            <th>Guide Contact</th> 
                            <th>Requirements</th>
                            <th>Proj Discipline</th>
                            <th>Description</th>
                            <th>Innovative Features</th>
                            <th>Tools/Tech</th>
                            <th>Outcomes</th>
                            <th>Submission Status</th>
                            <th class="action-col">Action</th>
                            <th>Ticket Subject</th>
                            <th>Ticket Message</th>
                            <th>Ticket Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="student-row" data-has-step1="<?php echo (isset($row['has_step1']) && $row['has_step1'] == 1) ? '1' : '0'; ?>" data-has-step2="<?php echo (isset($row['has_step2']) && $row['has_step2'] == 1) ? '1' : '0'; ?>">
                                <td class="fw-bold text-info"><?php echo $row['unique_reg_id'] ?? 'Pending'; ?></td>
                                <td class="fw-bold" style="color: #000000;"><?php echo $row['delete_status']; ?></td>
                                <td style="color: #000000;"><?php echo $row['fullname']; ?></td>
                                <td style="color: #000000;"><?php echo $row['phone']; ?></td>
                                <td style="color: #000000;"><?php echo $row['user_email']; ?></td>
                                <td><span class="badge bg-info"><?php echo $row['program'] ?? 'N/A'; ?></span></td>
                                <td style="color: #000000;"><?php echo $row['discipline'] ?? 'N/A'; ?></td>
                                <td class="text-truncate" style="max-width: 150px; color: #000000;"><?php echo $row['theme'] ?? 'N/A'; ?></td>
                                <td class="text-truncate" style="max-width: 150px; color: #000000;"><?php echo $row['project_title'] ?? 'N/A'; ?></td>
                                <td style="color: #000000;"><?php echo $row['team_name'] ?? 'N/A'; ?></td>
                                <td class="text-truncate" style="max-width: 150px; color: #000000;"><?php echo $row['members'] ?? 'N/A'; ?></td>
                                <td style="color: #000000;"><?php echo $row['college_name'] ?? 'N/A'; ?></td>
                                <td style="color: #000000;"><?php echo $row['district'] ?? 'N/A'; ?></td>
                                <td style="font-size: 11px; color: #000000;"><?php echo $row['payment_id'] ?? 'N/A'; ?></td>
                                <td class="fw-bold" style="color: #000000;">₹<?php echo $row['amount'] ?? '0'; ?></td>
                                <td>
                                    <?php if(isset($row['payment_status']) && $row['payment_status'] == 'Success'): ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-check-circle me-1"></i> Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 11px; color: #000000;"><?php echo date('d-M-Y', strtotime($row['user_created_at'])); ?></td>
                                <td style="color: #000000;"><?php echo $row['guide_name'] ?? 'N/A'; ?></td>
                                <td style="color: #000000;"><?php echo $row['contact_number'] ?? 'N/A'; ?></td>
                                <td style="color: #000000;"><?php echo $row['requirements'] ?? 'N/A'; ?></td>
                                <td style="color: #000000;"><?php echo $row['project_discipline'] ?? 'N/A'; ?></td>
                                <td class="text-truncate" style="max-width: 150px; color: #000000;"><?php echo $row['project_description'] ?? 'N/A'; ?></td>
                                <td style="color: #000000;"><?php echo $row['innovative_features'] ?? 'N/A'; ?></td>
                                <td style="color: #000000;"><?php echo $row['tools_technologies'] ?? 'N/A'; ?></td>
                                <td style="color: #000000;"><?php echo $row['expected_outcome'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php 
                                        $s1 = (isset($row['has_step1']) && $row['has_step1'] == 1);
                                        $s2 = (isset($row['has_step2']) && $row['has_step2'] == 1);
                                        $s3 = (isset($row['has_step3']) && $row['has_step3'] == 1);
                                        if($s1 && $s2 && $s3) { echo '<span class="badge bg-success">Completed</span>'; } 
                                        else {
                                            $pending = [];
                                            if(!$s1) $pending[] = "S1";
                                            if(!$s2) $pending[] = "S2";
                                            if(!$s3) $pending[] = "S3";
                                            echo '<span class="badge bg-warning text-dark">Pending ('.implode(',', $pending).')</span>';
                                        }
                                    ?>
                                </td>
                                <td class="action-col">
                                    <div class="btn-group">
                                        <?php if($view === 'trash'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="target_email" value="<?php echo $row['user_email']; ?>">
                                                <button type="submit" name="restore_record" class="btn btn-sm btn-success">
                                                    <i class="fa-solid fa-rotate-left"></i> Restore
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a href="view_registration.php?email=<?php echo urlencode($row['user_email']); ?>" class="btn btn-sm btn-info text-dark" title="View Details"><i class="fa-solid fa-eye"></i></a>
                                            <?php if($row['abstract_pdf_path']): ?>
                                                <button class="btn btn-sm btn-secondary" onclick="viewUploadedPDF('<?php echo $row['abstract_pdf_path']; ?>')" title="View Abstract PDF">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if($row['team_id']): ?>
                                                <button class="btn btn-sm btn-primary" title="Add Member" onclick="openMemberModal('<?php echo $row['team_id']; ?>', '<?php echo addslashes($row['members']); ?>')">
                                                    <i class="fa-solid fa-user-plus"></i>
                                                </button>
                                                
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>N/A</td>
                                <td>N/A</td>
                                <td>N/A</td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>

                        <?php 
                        if ($received_tickets_result) {
                            $received_tickets_result->data_seek(0);
                            while($t = $received_tickets_result->fetch_assoc()): 
                                $t_email = $t['user_email'] ?? '';
                                $t_name = isset($user_name_mapping[$t_email]) ? $user_name_mapping[$t_email] : 'Unknown';
                        ?>
                        <tr class="ticket-pending-row" style="display:none;">
                            <td class="fw-bold text-warning">#<?php echo $t['id']; ?></td>
                            <td class="fw-bold" style="color: #000000;">Active</td>
                            <td style="color: #000000;"><?php echo $t_name; ?></td>
                            <td></td>
                            <td style="color: #000000;"><?php echo $t_email ?: 'N/A'; ?></td>
                            <td><span class="badge bg-warning">Ticket</span></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><span class="badge bg-danger"><?php echo ucfirst($t['status'] ?? 'Pending'); ?></span></td>
                            <td style="color: #000000;"><?php echo date('d-M-Y', strtotime($t['created_at'])); ?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><span class="badge bg-danger">Pending</span></td>
                            <td class="action-col">
                                <button class="btn btn-sm btn-primary" onclick="viewTicketDetails(<?php echo htmlspecialchars(json_encode($t)); ?>)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>
                            <td style="color: #000000;"><?php echo $t['subject'] ?? 'N/A'; ?></td>
                            <td style="color: #000000;"><?php echo $t['message'] ?? 'N/A'; ?></td>
                            <td style="color: #000000;"><?php echo date('d-M-Y H:i', strtotime($t['created_at'])); ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        } 
                        ?>

                        <?php 
                        if ($solved_tickets_result) {
                            $solved_tickets_result->data_seek(0);
                            while($s = $solved_tickets_result->fetch_assoc()): 
                                $s_email = $s['user_email'] ?? '';
                                $s_name = isset($user_name_mapping[$s_email]) ? $user_name_mapping[$s_email] : 'Unknown';
                        ?>
                        <tr class="ticket-solved-row" style="display:none;">
                            <td class="fw-bold text-success">#<?php echo $s['id']; ?></td>
                            <td class="fw-bold" style="color: #000000;">Archived</td>
                            <td style="color: #000000;"><?php echo $s_name; ?></td>
                            <td></td>
                            <td style="color: #000000;"><?php echo $s_email ?: 'N/A'; ?></td>
                            <td><span class="badge bg-success">Ticket</span></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><span class="badge bg-success">Solved</span></td>
                            <td style="color: #000000;"><?php echo date('d-M-Y', strtotime($s['created_at'])); ?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><span class="badge bg-success">Solved</span></td>
                            <td class="action-col">
                                <button class="btn btn-sm btn-info text-dark" onclick="viewTicketDetails(<?php echo htmlspecialchars(json_encode($s)); ?>)">
                                    <i class="fa-solid fa-eye"></i> View
                                </button>
                            </td>
                            <td style="color: #000000;"><?php echo $s['subject'] ?? 'N/A'; ?></td>
                            <td style="color: #000000;"><?php echo $s['message'] ?? 'N/A'; ?></td>
                            <td style="color: #000000;"><?php echo date('d-M-Y H:i', strtotime($s['solved_at'])); ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        } 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php elseif($section === 'received_tickets'): ?>
    <div class="container-fluid">
        <div class="section-header d-flex align-items-center mb-4">
            <i class="fa-solid fa-ticket fa-2x text-warning me-3"></i>
            <h3 class="m-0 fw-bold" style="color: #000000;">Active Support Tickets</h3>
            <span class="ms-3 badge bg-primary"><?php echo $received_tickets_result ? $received_tickets_result->num_rows : 0; ?> Total</span>
        </div>

        <div class="ticket-card">
            <div class="table-responsive">
                <table id="receivedTicketsTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>User Name</th>
                            <th>User Email</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Message</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if($received_tickets_result) {
                            $received_tickets_result->data_seek(0);
                            while($ticket = $received_tickets_result->fetch_assoc()): 
                                $t_email = $ticket['user_email'] ?? '';
                                $t_name = isset($user_name_mapping[$t_email]) ? $user_name_mapping[$t_email] : 'Unknown';
                        ?>
                        <tr>
                            <td class="fw-bold text-info">#<?php echo $ticket['id']; ?></td>
                            <td style="color: #000000;"><?php echo $t_name; ?></td>
                            <td style="color: #000000;"><?php echo $t_email ?: 'N/A'; ?></td>
                            <td style="color: #000000;"><?php echo $ticket['subject'] ?? 'N/A'; ?></td>
                            <td>
                                <?php 
                                $status = $ticket['status'] ?? 'pending';
                                $statusClass = 'bg-warning';
                                if($status === 'open') $statusClass = 'bg-primary';
                                ?>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                            <td style="font-size: 11px; color: #000000;"><?php echo date('d-M-Y H:i', strtotime($ticket['created_at'])); ?></td>
                            <td class="text-truncate" style="max-width: 200px; color: #000000;"><?php echo $ticket['message'] ?? 'N/A'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary me-1" onclick="viewTicketDetails(<?php echo htmlspecialchars(json_encode($ticket)); ?>)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <button type="submit" name="mark_solved" class="btn btn-sm btn-success" onclick="return confirm('Mark this ticket as solved? An email will be sent to the user.')">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php elseif($section === 'solved_tickets'): ?>
    <div class="container-fluid">
        <div class="section-header d-flex align-items-center mb-4">
            <i class="fa-solid fa-check-circle fa-2x text-success me-3"></i>
            <h3 class="m-0 fw-bold" style="color: #000000;">Archived (Solved) Tickets</h3>
        </div>

        <div class="ticket-card">
            <div class="table-responsive">
                <table id="solvedTicketsTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>User Name</th>
                            <th>User Email</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Solved At</th>
                            <th>Message</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if($solved_tickets_result) {
                            $solved_tickets_result->data_seek(0);
                            while($ticket = $solved_tickets_result->fetch_assoc()): 
                                $s_email = $ticket['user_email'] ?? '';
                                $s_name = isset($user_name_mapping[$s_email]) ? $user_name_mapping[$s_email] : 'Unknown';
                        ?>
                        <tr>
                            <td class="fw-bold text-info">#<?php echo $ticket['id']; ?></td>
                            <td style="color: #000000;"><?php echo $s_name; ?></td>
                            <td style="color: #000000;"><?php echo $s_email ?: 'N/A'; ?></td>
                            <td style="color: #000000;"><?php echo $ticket['subject'] ?? 'N/A'; ?></td>
                            <td>
                                <span class="badge bg-success"><i class="fa-solid fa-check-circle me-1"></i> Solved</span>
                            </td>
                            <td style="font-size: 11px; color: #000000;"><?php echo date('d-M-Y H:i', strtotime($ticket['created_at'])); ?></td>
                            <td style="font-size: 11px; color: #000000;"><?php echo date('d-M-Y H:i', strtotime($ticket['solved_at'])); ?></td>
                            <td class="text-truncate" style="max-width: 200px; color: #000000;"><?php echo $ticket['message'] ?? 'N/A'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-info text-dark" onclick="viewTicketDetails(<?php echo htmlspecialchars(json_encode($ticket)); ?>)">
                                    <i class="fa-solid fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="ticketDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-info">
            <div class="modal-header bg-dark">
                <h5 class="modal-title text-info"><i class="fa-solid fa-ticket me-2"></i>Ticket Breakdown</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f0f0f0; color: #000000; max-height: 70vh; overflow-y: auto;">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase">Ticket ID</label>
                        <p id="modal_ticket_id" class="fw-bold" style="color: #000000;"></p>
                        <label class="text-muted small text-uppercase">User Email</label>
                        <p id="modal_user_email" style="color: #000000;"></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <label class="text-muted small text-uppercase">Priority</label>
                        <p id="modal_priority" style="color: #000000;"></p>
                        <label class="text-muted small text-uppercase">Status</label>
                        <p id="modal_status" style="color: #000000;"></p>
                    </div>
                </div>
                <hr style="opacity: 0.1">
                <label class="text-muted small text-uppercase">Subject</label>
                <h6 id="modal_subject" class="fw-bold mb-3" style="color: #000000;"></h6>
                <label class="text-muted small text-uppercase">Detailed Message</label>
                <div id="modal_message" class="p-3 rounded" style="background: rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.2); white-space: pre-wrap; color: #000000;"></div>
                <div id="attachment_container" class="attachment-container" style="display:none;">
                    <label class="text-muted small text-uppercase d-block mb-2"><i class="fa-solid fa-paperclip me-2"></i>Uploaded Attachment</label>
                    <div id="attachment_preview" style="min-height: 150px; display: flex; align-items: center; justify-content: center;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="memberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" style="color: #000000;">Manage Team Members</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="background-color: #f0f0f0; color: #000000;">
                    <p class="text-info small fw-bold">Current Members & IDs:</p>
                    <p id="currentMembersList" class="p-3 bg-dark rounded small text-muted" style="border: 1px solid rgba(255,255,255,0.1);"></p>
                    <hr class="bg-secondary">
                    <h6 class="text-info small fw-bold mb-3">Add 5th Member Details:</h6>
                    <input type="hidden" name="team_id" id="modalTeamId">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="m_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="m_email" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="m_phone" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Enrollment/PRN</label>
                        <input type="text" name="m_prn" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch/Department</label>
                        <input type="text" name="m_branch" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_extra_member" class="btn btn-primary w-100 py-2">Generate ID & Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger fw-bold"><i class="fa-solid fa-eye-slash me-2"></i>Hide Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background-color: #f0f0f0; color: #000000;">
                <p class="text-muted small">Search by name to hide them from the primary dashboard.</p>
                <input type="text" id="deleteSearchInput" class="form-control mb-3" placeholder="Start typing student name...">
                <div id="deleteSearchResults" class="delete-search-results"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
    const allRecords = <?php echo json_encode($delete_search_list); ?>;
    let table;
    let currentReportView = "all";

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }

    // Function to view uploaded PDF in new tab
    function viewUploadedPDF(path) {
        if(!path || path === "") {
            alert("No PDF file uploaded for this record.");
            return;
        }
        window.open(path, '_blank');
    }

    function openMemberModal(tid, members) {
        document.getElementById('modalTeamId').value = tid;
        document.getElementById('currentMembersList').innerText = members;
        new bootstrap.Modal(document.getElementById('memberModal')).show();
    }

    function viewTicketDetails(ticket) {
        document.getElementById('modal_ticket_id').innerText = '#' + ticket.id;
        document.getElementById('modal_user_email').innerText = ticket.user_email || 'N/A';
        document.getElementById('modal_priority').innerText = (ticket.priority || 'medium').toUpperCase();
        document.getElementById('modal_status').innerText = (ticket.status || 'pending').toUpperCase();
        document.getElementById('modal_subject').innerText = ticket.subject || 'N/A';
        document.getElementById('modal_message').innerText = ticket.message || 'No message provided';
        
        const attachmentContainer = document.getElementById('attachment_container');
        const attachmentPreview = document.getElementById('attachment_preview');
        
        attachmentPreview.innerHTML = '';
        let hasAttachment = false;
        
        // Use the correct field name from the database: screenshot_path
        const attachmentField = ticket.screenshot_path || null;
        
        if(attachmentField && attachmentField !== '' && attachmentField !== 'N/A' && attachmentField !== null) {
            hasAttachment = true;
            const fileExt = attachmentField.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(fileExt);
            const isPDF = fileExt === 'pdf';
            
            if(isImage) {
                const img = document.createElement('img');
                img.src = attachmentField;
                img.className = 'attachment-preview';
                img.alt = 'Uploaded Attachment';
                img.onerror = function() {
                    this.style.display = 'none';
                    attachmentPreview.innerHTML = '<div class="alert alert-warning w-100 mb-0"><i class="fa-solid fa-exclamation-triangle me-2"></i>Image could not be loaded</div>';
                };
                attachmentPreview.appendChild(img);
            } else if(isPDF) {
                const pdfContainer = document.createElement('div');
                pdfContainer.style.width = '100%';
                const iframe = document.createElement('iframe');
                iframe.src = attachmentField;
                iframe.className = 'pdf-viewer';
                iframe.style.width = '100%';
                iframe.style.height = '400px';
                iframe.style.border = '1px solid #ddd';
                iframe.style.borderRadius = '6px';
                pdfContainer.appendChild(iframe);
                attachmentPreview.appendChild(pdfContainer);
            } else {
                const link = document.createElement('a');
                link.href = attachmentField;
                link.target = '_blank';
                link.className = 'btn btn-info btn-sm';
                link.innerHTML = '<i class="fa-solid fa-download me-2"></i>Download File';
                attachmentPreview.appendChild(link);
            }
        }
        
        if(hasAttachment) {
            attachmentContainer.style.display = 'block';
        } else {
            attachmentContainer.style.display = 'none';
        }
        
        new bootstrap.Modal(document.getElementById('ticketDetailsModal')).show();
    }

    $(document).ready(function() {
        if($('#adminTable').length) {
            table = $('#adminTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    { 
                        extend: 'copy', 
                        className: 'buttons-copy',
                        exportOptions: { columns: ':visible:not(.action-col)' }
                    },
                    { 
                        extend: 'csv', 
                        className: 'buttons-csv',
                        exportOptions: { columns: ':visible:not(.action-col)' }
                    },
                    { 
                        extend: 'pdf', 
                        className: 'buttons-pdf', 
                        title: '', 
                        orientation: 'landscape', 
                        pageSize: 'A3',
                        exportOptions: { columns: ':visible:not(.action-col)' },
                        customize: function (doc) {
                            let activeSectionText = $('.btn-report.active').text().toUpperCase();
                            doc.content.splice(0, 0, {
                                stack: [
                                    { text: 'INNOVAHUB', style: 'header', alignment: 'center', fontSize: 20, bold: true },
                                    { text: activeSectionText + ' REPORT', style: 'subheader', alignment: 'center', fontSize: 14, margin: [0, 5, 0, 20] }
                                ]
                            });
                        }
                    },
                    { 
                        extend: 'print', 
                        className: 'buttons-print',
                        exportOptions: { columns: ':visible:not(.action-col)' }
                    },
                    <?php if($view !== 'trash'): ?>
                    { 
                        text: '<i class="fa-solid fa-eye-slash"></i> HIDE RECORD', 
                        className: 'buttons-delete-trigger',
                        action: function () { $('#deleteModal').modal('show'); }
                    }
                    <?php endif; ?>
                ],
                columnDefs: [{ targets: [16, 17, 18, 19, 20, 21, 22, 23, 24, 27, 28, 29], visible: false }]
            });
        }

        $('.table:not(#adminTable)').each(function() {
            $(this).DataTable({ dom: 'frtip' });
        });

        $('#deleteSearchInput').on('input', function() {
            const val = $(this).val().toLowerCase();
            const resultsDiv = $('#deleteSearchResults');
            resultsDiv.empty();
            if(val.length < 2) return;
            const filtered = allRecords.filter(r => (r.fullname || "").toLowerCase().includes(val));
            filtered.forEach(r => {
                 resultsDiv.append(`
                    <div class="search-item d-flex justify-content-between align-items-center mb-2 p-2 rounded bg-dark border border-secondary">
                        <span class="small">${r.fullname}</span>
                        <form method="POST">
                            <input type="hidden" name="target_email" value="${r.user_email}">
                            <button type="submit" name="delete_team_admin" class="btn btn-danger btn-sm">Hide</button>
                        </form>
                    </div>
                `);
            });
        });
    });

    function updateAdminDiscipline() {
        const program = $('#admin_program').val();
        const disc = $('#admin_discipline');
        disc.empty().append('<option value="">All Disciplines</option>');
        const map = {
            'Diploma': ["Computer Engineering", "Civil Engineering", "Mechanical Engineering", "Electrical Engineering", "Electronics & Telecommunication"],
            'UG': ["BCA", "Civil Engineering", "Computer Science & Engineering", "Electrical Engineering", "Electronics & Telecommunication Engineering", "Information Technology", "Mechanical Engineering", "Artificial Intelligence & Data Science", "Automation & Robotics"],
            'PG': ["MCA", "Civil Engineering", "Computer Science & Engineering", "Electrical Engineering", "Electronics & Telecommunication Engineering", "Information Technology", "Mechanical Engineering", "Artificial Intelligence & Data Science", "Automation & Robotics"]
        };
        (map[program] || []).forEach(o => disc.append(`<option value="${o}">${o}</option>`));
    }

    function switchReportView(type, btn) {
        $('.btn-report').removeClass('active');
        $(btn).addClass('active');
        currentReportView = type;
        
        $.fn.dataTable.ext.search = [];
        $('.student-row, .ticket-pending-row, .ticket-solved-row').show();
        table.columns().visible(false);

        if (type === 'pending_tickets_rep') {
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                return $(table.row(dataIndex).node()).hasClass('ticket-pending-row');
            });
            table.columns([0, 1, 2, 4, 5, 15, 16, 25, 26, 27, 28, 29]).visible(true);
        } 
        else if (type === 'solved_tickets_rep') {
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                return $(table.row(dataIndex).node()).hasClass('ticket-solved-row');
            });
            table.columns([0, 1, 2, 4, 5, 15, 16, 25, 26, 27, 28, 29]).visible(true);
        } 
        else {
            if (type === 'step1_completed') {
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    let node = $(table.row(dataIndex).node());
                    return node.hasClass('student-row') && node.attr('data-has-step1') === '1';
                });
                table.columns([0, 2, 3, 4, 5, 6, 7, 12, 16, 25, 26]).visible(true);
            } 
            else if (type === 'step2_completed') {
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    let node = $(table.row(dataIndex).node());
                    return node.hasClass('student-row') && node.attr('data-has-step2') === '1';
                });
                table.columns([0, 2, 9, 10, 11, 12, 17, 18, 25, 26]).visible(true);
            } 
            else {
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    return $(table.row(dataIndex).node()).hasClass('student-row');
                });
                const map = {
                    'all': [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 25, 26],
                    'step1': [0, 2, 3, 4, 5, 6, 7, 12, 16, 26],
                    'step2': [0, 9, 10, 11, 17, 18, 26],
                    'step3': [0, 8, 20, 21, 22, 23, 24, 19, 26],
                    'step4': [0, 2, 4, 13, 14, 15, 26]
                };
                table.columns(map[type] || map['all']).visible(true);
            }
        }
        
        table.draw();
        table.columns.adjust();
    }
</script>

</body>
</html>
