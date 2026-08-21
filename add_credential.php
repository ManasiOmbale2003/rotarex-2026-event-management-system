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

$message = "";
$messageType = "";

// Handle Form Submissions for adding structural credentials
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_credential'])) {
    // Check if the admin is trying to add a custom program or use the dropdown selection
    if (!empty($_POST['custom_program'])) {
        $program = trim($_POST['custom_program']);
    } else {
        $program = isset($_POST['program']) ? trim($_POST['program']) : '';
    }
    
    $discipline = trim($_POST['discipline']);
    $theme = trim($_POST['theme']);
    $region = isset($_POST['region']) ? trim($_POST['region']) : 'Satara';
    
    // Check if empty values are provided for mandatory fields
    if (empty($program) || empty($discipline) || empty($theme)) {
        $message = "All core fields (Program, Discipline, and Theme) are strictly required.";
        $messageType = "danger";
    } else {
        // Use an admin system email to tag master configurations/credentials in the registrations table
        $config_email = "admin_config@innovahub.com";
        
        // SECURE FIXED: Check if this explicit mapping sequence already exists using Prepared Statements
        $check_stmt = $conn->prepare("SELECT id FROM registrations WHERE program = ? AND discipline = ? AND theme = ?");
        $check_stmt->bind_param("sss", $program, $discipline, $theme);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();
        
        if ($check_res && $check_res->num_rows > 0) {
            $message = "This combination of Program, Discipline, and Theme already exists in the database.";
            $messageType = "warning";
        } else {
            // Check if there is a unique key violation on user_email. If so, append timestamps to keep configuration values open.
            $email_stmt = $conn->prepare("SELECT id FROM registrations WHERE user_email = ?");
            $email_stmt->bind_param("s", $config_email);
            $email_stmt->execute();
            $email_res = $email_stmt->get_result();
            
            if ($email_res && $email_res->num_rows > 0) {
                $config_email = "admin_config_" . time() . "@innovahub.com";
            }

            // SECURE FIXED: Perform direct CRUD insert operation into the registrations table using Prepared Statements
            $insert_stmt = $conn->prepare("INSERT INTO registrations (user_email, stream, program, discipline, theme, region, completed_steps) VALUES (?, 'NA', ?, ?, ?, ?, 1)");
            $insert_stmt->bind_param("sssss", $config_email, $program, $discipline, $theme, $region);
            
            if ($insert_stmt->execute()) {
                $message = "Credential options added and synced to the database successfully!";
                $messageType = "success";
            } else {
                $message = "Error inserting option combinations: " . $conn->error;
                $messageType = "danger";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// Fetch all distinct program values dynamically from the database table to populate the select menu
$dynamicPrograms = [];
$prog_query = "SELECT DISTINCT program FROM registrations WHERE program != ''";
$prog_res = $conn->query($prog_query);
if ($prog_res && $prog_res->num_rows > 0) {
    while ($row = $prog_res->fetch_assoc()) {
        $dynamicPrograms[] = $row['program'];
    }
}
// Backwards compatibility fallbacks if table metadata remains entirely unpopulated
foreach (['Diploma', 'UG', 'PG'] as $defaultProg) {
    if (!in_array($defaultProg, $dynamicPrograms)) {
        $dynamicPrograms[] = $defaultProg;
    }
}

// Fetch all existing system configuration setups to showcase below the form container layout
$all_credentials = [];
$table_query = "SELECT id, program, discipline, theme, region, user_email FROM registrations WHERE user_email LIKE 'admin_config%' ORDER BY id DESC";
$table_res = $conn->query($table_query);
if ($table_res && $table_res->num_rows > 0) {
    while ($row = $table_res->fetch_assoc()) {
        $all_credentials[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InnovaHub Admin | Add Credentials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .credential-card { 
            background: var(--glass-bg); 
            backdrop-filter: blur(15px) saturate(180%);
            -webkit-backdrop-filter: blur(15px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 35px; 
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.4); 
            margin-top: 30px;
            color: #ffffff;
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
            background: rgba(0, 0, 0, 0.5) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
            border-radius: 10px;
            padding: 12px 15px;
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4) !important;
        }
        .form-select option {
            background-color: var(--bg-dark);
            color: #ffffff;
        }
        
        .btn-submit { 
            background: linear-gradient(135deg, var(--accent-purple), var(--deep-purple)); 
            color: #ffffff !important; 
            border: none;
            border-radius: 10px; 
            padding: 14px 30px;
            font-weight: 600;
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 5px 25px rgba(192, 132, 252, 0.5); }

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
            text-decoration: none;
            display: block;
        }
        .sidebar-menu li a:hover { background: rgba(192, 132, 252, 0.1); color: var(--accent-purple); }
        .sidebar-menu li a.active {
            background: linear-gradient(90deg, var(--accent-purple), var(--deep-purple));
            color: #ffffff !important;
            box-shadow: 5px 5px 15px rgba(0,0,0,0.3);
        }

        .main-content { margin-left: 260px; padding: 30px; transition: 0.3s; }
        .navbar { margin-left: 260px; width: calc(100% - 260px); }

        /* Table Styling adjustments */
        .table-responsive-container {
            margin-top: 40px;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
        }
        .custom-table {
            margin-bottom: 0;
        }
        .custom-table thead th {
            color: var(--accent-purple);
            border-bottom: 2px solid rgba(255, 255, 255, 0.15);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        /* Updated record cell colors to explicit black */
        .custom-table td, 
        .custom-table td strong, 
        .custom-table td small, 
        .custom-table td span {
            color: #000000 !important;
            font-weight: 500;
        }
        
        .custom-table td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 14px 10px;
            vertical-align: middle;
        }
        .custom-table tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content, .navbar { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><i class="fa-solid fa-bolt me-2"></i>InnovaHub</h4>
        <p class="text-muted small">Admin Control Panel</p>
    </div>

    <ul class="sidebar-menu list-unstyled">
        <li>
            <a href="admin_dashboard.php?section=reports" class="nav-link">
                <i class="fa-solid fa-chart-bar me-2"></i>
                Reports
            </a>
        </li>
        <li>
            <a href="admin_dashboard.php?section=received_tickets" class="nav-link">
                <i class="fa-solid fa-ticket me-2"></i>
                Received Tickets
            </a>
        </li>
        <li>
            <a href="admin_dashboard.php?section=solved_tickets" class="nav-link">
                <i class="fa-solid fa-check-circle me-2"></i>
                Solved Tickets
            </a>
        </li>
        <li>
            <a href="add_credential.php" class="nav-link active">
                <i class="fa-solid fa-folder-plus me-2"></i>
                Add Credentials
            </a>
        </li>
    </ul>

    <div class="sidebar-divider" style="height: 1px; background: rgba(255,255,255,0.1); margin: 20px 25px;"></div>

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
                <li class="nav-item"><a class="nav-link text-light" href="admin_dashboard.php">Dashboard</a></li>
                <li class="nav-item ms-3"><a class="nav-link text-danger" href="logout.php"><i class="fa-solid fa-power-off"></i></a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-content">
    <div class="container-fluid" style="max-width: 950px;">
        <div class="d-flex align-items-center mb-2">
            <i class="fa-solid fa-folder-plus fa-2x text-info me-3"></i>
            <h3 class="fw-bold m-0" style="color: #ffffff;">Add System Credentials</h3>
        </div>
        <p class="text-muted mb-4">Populate structural options for program levels, distinct disciplines, and structural registration themes directly into active components.</p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="fa-solid <?php echo ($messageType === 'success') ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="credential-card">
            <form method="POST" action="add_credential.php">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label" for="program">Program Stream Level (Select Existing)</label>
                        <select name="program" id="program" class="form-select">
                            <option value="" selected>-- Use Custom Program Input Below or Select --</option>
                            <?php
                            foreach ($dynamicPrograms as $progOpt) {
                                echo "<option value=\"" . htmlspecialchars($progOpt) . "\">" . htmlspecialchars($progOpt) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="custom_program">Or Add New Program Category</label>
                        <input type="text" name="custom_program" id="custom_program" class="form-control" placeholder="e.g., Ph.D / Integrated Course">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="discipline">Discipline / Branch Name</label>
                        <input type="text" name="discipline" id="discipline" class="form-control" placeholder="e.g., Computer Engineering" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="region">Default Regional Region / District</label>
                        <select name="region" id="region" class="form-select">
                            <?php
                            $districts = ["Satara", "Pune", "Sangli", "Kolhapur", "Nashik", "Jalgaon", "Dhule", "Nandurbar", "Nagpur", "Amravati", "Akola", "Bhandara", "Wardha", "Chandrapur", "Gadchiroli", "Yavatmal", "Aurangabad", "Jalna", "Beed", "Osmanabad", "Parbhani", "Nanded", "Hingoli", "Latur", "Mumbai", "Thane", "Raigad", "Ratnagiri", "Sindhudurg", "Palghar"];
                            foreach($districts as $d) {
                                echo "<option value=\"$d\">$d</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="theme">Project Allocation Theme</label>
                        <input type="text" name="theme" id="theme" class="form-control" placeholder="e.g., Advanced Infrastructure Development Automation" required>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="submit" name="add_credential" class="btn btn-submit w-100">
                            <i class="fa-solid fa-plus-circle me-2"></i> Register Configuration Options
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive-container">
            <div class="d-flex align-items-center mb-3">
                <i class="fa-solid fa-table text-warning me-2 fa-lg"></i>
                <h4 class="m-0 fw-bold" style="color: #ffffff; font-size: 18px;">Active Registered Configurations</h4>
            </div>
            
            <div class="table-responsive">
                <table class="table custom-table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 15%;">Program</th>
                            <th style="width: 25%;">Discipline / Branch</th>
                            <th style="width: 37%;">Project Theme</th>
                            <th style="width: 15%;">Region</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_credentials)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-database me-2"></i>No master credentials configured yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_credentials as $credential): ?>
                                <tr>
                                    <td><span class="badge bg-dark border border-secondary"><?php echo htmlspecialchars($credential['id']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($credential['program']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($credential['discipline']); ?></td>
                                    <td><small><?php echo htmlspecialchars($credential['theme']); ?></small></td>
                                    <td><span><i class="fa-solid fa-location-dot me-1 small"></i><?php echo htmlspecialchars($credential['region']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('form').on('submit', function() {
        var dropdownVal = $('#program').val();
        var customVal = $.trim($('#custom_program').val());
        if (!dropdownVal && !customVal) {
            alert('Please select an existing structural Program level stream or type a new Program entry category.');
            return false;
        }
    });
});
</script>
</body>
</html>