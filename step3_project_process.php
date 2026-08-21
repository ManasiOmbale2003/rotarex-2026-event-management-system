<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

/* ---------- DATABASE CONNECTION ---------- */
require_once('config.php'); // Include external database configuration

$user_email = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- LOCK CHECK LOGIC (From Proposals Table) ---
    $lock_check = $conn->prepare("SELECT is_locked FROM project_proposals WHERE user_email = ?");
    $lock_check->bind_param("s", $user_email);
    $lock_check->execute();
    $lock_res = $lock_check->get_result();
    if ($l_row = $lock_res->fetch_assoc()) {
        if ($l_row['is_locked'] == 1) {
            header("Location: step3_project_proposal.php?error=locked");
            exit();
        }
    }

    // --- LOCK CHECK LOGIC (From Payments Table) ---
    $pay_check = $conn->prepare("SELECT status FROM registration_payments WHERE email = ? AND status = 'Success' LIMIT 1");
    $pay_check->bind_param("s", $user_email);
    $pay_check->execute();
    $pay_res = $pay_check->get_result();
    if ($pay_res->num_rows > 0) {
        header("Location: step3_project_proposal.php?error=locked");
        exit();
    }

    // action_type will be 'continue' from the updated frontend button
    $action_type = $_POST['action_type'] ?? 'continue';

    // Form data mapping & Sanitization
    $project_title       = trim($_POST['project_title']);
    $project_theme       = trim($_POST['project_theme']);
    $project_discipline  = trim($_POST['project_discipline']);
    $project_description = trim($_POST['project_abstract']);
    $innovative_features = trim($_POST['innovative_features']);
    $tools_technologies  = trim($_POST['tools_technologies']);
    $expected_outcome    = trim($_POST['expected_outcome']);
    $requirements        = trim($_POST['requirements']); // Added requirements mapping
    $declaration         = isset($_POST['declaration']) ? 1 : 0;

    /* ---------- SERVER SIDE VALIDATION ---------- */
    if (empty($project_title) || empty($project_theme) || empty($project_discipline) || 
        empty($project_description) || empty($innovative_features) || 
        empty($tools_technologies) || empty($expected_outcome) || empty($requirements) || $declaration == 0) {
        
        header("Location: step3_project_proposal.php?error=empty_fields");
        exit();
    }

    // Check for existing proposal to decide between INSERT or UPDATE
    $check = $conn->prepare("SELECT id, attachment_file FROM project_proposals WHERE user_email = ?");
    $check->bind_param("s", $user_email);
    $check->execute();
    $res = $check->get_result();
    $existing_data = $res->fetch_assoc();
    $exists = $res->num_rows > 0;

    // File Upload Logic
    $file_name = $existing_data['attachment_file'] ?? ""; 
    if (!empty($_FILES['attachment']['name'])) {
        $upload_dir = "uploads/project_attachments/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_name = time() . "_" . $_FILES['attachment']['name'];
        move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $file_name);
    }

    if ($exists) {
        // UPDATE existing record
        $stmt = $conn->prepare("
            UPDATE project_proposals SET 
            project_title=?, project_theme=?, 
            project_discipline=?, project_description=?, innovative_features=?, 
            tools_technologies=?, expected_outcome=?, requirements=?, attachment_file=?, declaration=? 
            WHERE user_email=?
        ");
        $stmt->bind_param(
            "sssssssssis", 
            $project_title, $project_theme, 
            $project_discipline, $project_description, $innovative_features, 
            $tools_technologies, $expected_outcome, $requirements, $file_name, $declaration, 
            $user_email
        );
    } else {
        // INSERT new record
        $stmt = $conn->prepare("
            INSERT INTO project_proposals 
            (user_email, project_title, project_theme, 
             project_discipline, project_description, innovative_features, 
             tools_technologies, expected_outcome, requirements, attachment_file, declaration) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssssssssi", 
            $user_email, $project_title, $project_theme, 
            $project_discipline, $project_description, $innovative_features, 
            $tools_technologies, $expected_outcome, $requirements, $file_name, $declaration
        );
    }

    $stmt->execute();

    // Logic for redirection
    if ($action_type === 'continue') {
        $_SESSION['completed_steps'] = 3;
        header("Location: step4_payment.php");
    } else {
        header("Location: step3_project_proposal.php?status=saved");
    }
    exit();
}
?>