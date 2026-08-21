<?php
session_start();
require_once('config.php'); // Include external database configuration
require_once('tcpdf/tcpdf.php');
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

// Database connectivity removed and replaced by config.php

$user_email = $_SESSION['user'];
$action = $_POST['action_type'] ?? 'save';

/* ---------- FETCH UNIQUE REG ID ---------- */
$u_stmt = $conn->prepare("SELECT unique_reg_id FROM users WHERE email = ?");
$u_stmt->bind_param("s", $user_email);
$u_stmt->execute();
$u_res = $u_stmt->get_result();
$u_row = $u_res->fetch_assoc();
$unique_reg_id = $u_row['unique_reg_id'] ?? '0000';

$stmt1 = $conn->prepare("select program FROM  registrations WHERE user_email = ?");
$stmt1->bind_param("s", $user_email);
$stmt1->execute();
$result1 = $stmt1->get_result();
$reg_info = $result1->fetch_assoc();

$reg_program = $reg_info['program'] ;

/* ---------- VALIDATION FUNCTIONS ---------- */
function validate_student_name($name){ return preg_match('/^[A-Z][a-zA-Z ]*$/', $name); }
function validate_branch($branch){ return preg_match('/^[a-zA-Z ]+$/', $branch); }
/* PRN Validation updated: Allows alphanumeric, no fixed length */
function validate_prn($prn){ return !empty($prn); }
function validate_email($email){ return filter_var($email, FILTER_VALIDATE_EMAIL); }
function validate_mobile($mobile){ return preg_match('/^\d{10}$/', $mobile); }

/* ---------- GET FORM DATA ---------- */
$team_name      = strtoupper(trim($_POST['team_name'] ?? ''));
$college_name   = trim($_POST['college_name'] ?? '');
$guide_name     = strtoupper(trim($_POST['guide_name'] ?? '')); 
$guide_branch   = $_POST['guide_branch'] ?? ''; 
$designation    = $_POST['designation'] ?? '';
$contact_number = $_POST['contact_number'] ?? '';
$alt_contact    = $_POST['alt_contact_number'] ?? '';
$guide_email    = $_POST['guide_email'] ?? '';

$contact = $contact_number; 

$names    = array_map('strtoupper', $_POST['student_name'] ?? []);
$branches = $_POST['branch'] ?? []; 
$prns     = $_POST['enrollment_prn'] ?? [];
$years    = $_POST['year'] ?? [];
$mobiles   = $_POST['mobile_no'] ?? [];
$genders  = $_POST['gender'] ?? [];
$emails   = $_POST['member_email'] ?? [];

$errors = [];
$old = $_POST;

/* ---------- MEMBER COUNT VALIDATION ---------- */
$member_count = count($names);///working
if($reg_program =="PG")
{
    if($member_count < 1 || $member_count > 4) {
        $errors["member_count"] = "Minimum 1 member and maximum 4 members allowed";
    } 
}
else
{
    if($member_count < 2 || $member_count > 4) {
        $errors["member_count"] = "Minimum 2 members and maximum 4 members allowed";
    }
}

/* ---------- SERVER-SIDE VALIDATION ---------- */
for($i=0; $i < count($names); $i++){
    if(!validate_student_name($names[$i])) $errors["student_name_$i"] = "Invalid Name";
    if(!validate_email($emails[$i])) $errors["email_$i"] = "Invalid Email";
    
    // Check for duplicate PRN within the submitted arrays
    for($j = $i + 1; $j < count($prns); $j++) {
        if(!empty($prns[$i]) && strtolower(trim($prns[$i])) == strtolower(trim($prns[$j]))) {
            $errors["prn_$i"] = "Duplicate PRN number detected";
            $errors["prn_$j"] = "Duplicate PRN number detected";
        }
    }

    // Check for duplicate Emails within the submitted arrays
    for($j = $i + 1; $j < count($emails); $j++) {
        if(!empty($emails[$i]) && strtolower(trim($emails[$i])) == strtolower(trim($emails[$j]))) {
            $errors["email_$i"] = "Duplicate email detected";
            $errors["email_$j"] = "Duplicate email detected";
        }
    }

    // Check for duplicate Mobiles within the submitted arrays
    for($j = $i + 1; $j < count($mobiles); $j++) {
        if(!empty($mobiles[$i]) && trim($mobiles[$i]) == trim($mobiles[$j])) {
            $errors["mobile_$i"] = "Duplicate contact number";
            $errors["mobile_$j"] = "Duplicate contact number";
        }
    }

    // Cross-check member details against Guide details
    if(strtolower(trim($emails[$i])) == strtolower(trim($guide_email))) {
        $errors["email_$i"] = "Email matches Guide's email";
        $errors["guide_email"] = "Email matches a student's email";
    }
    if(trim($mobiles[$i]) == trim($contact_number)) {
        $errors["mobile_$i"] = "Mobile matches Guide's contact";
        $errors["contact_number"] = "Contact matches a student's mobile";
    }
}

// Check if Guide Alternate Contact matches Guide Primary Contact
if(!empty($alt_contact) && trim($alt_contact) == trim($contact_number)) {
    $errors["alt_contact_number"] = "Alternate cannot be same as Primary contact";
}

if(!validate_branch($guide_name)) $errors["guide_name"] = "Letters only";
if(!validate_branch($guide_branch)) $errors["guide_branch"] = "Letters only";
if(!validate_email($guide_email)) $errors["guide_email"] = "Invalid Email";
if(!validate_mobile($contact_number)) $errors["contact_number"] = "10 digits required";

if(!empty($errors)){
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $old;
    header("Location: step2.php");
    exit();
}

/* ---------- SAVE / UPDATE TEAM DATA ---------- */
$chk = $conn->prepare("SELECT id FROM team_information WHERE user_email=?");
$chk->bind_param("s", $user_email);
$chk->execute();
$res = $chk->get_result();

if($res->num_rows > 0){
    $row = $res->fetch_assoc();
    $team_id = $row['id'];
    $up = $conn->prepare("UPDATE team_information SET team_name=?, college_name=?, guide_name=?, branch=?, designation=?, contact_number=?, alt_contact_number=?, guide_email=? WHERE id=?");
    $up->bind_param("ssssssssi", $team_name, $college_name, $guide_name, $guide_branch, $designation, $contact_number, $alt_contact, $guide_email, $team_id);
    $up->execute();
    
    $p_stmt = $conn->prepare("SELECT member_id_code, photo FROM team_members WHERE team_id = ?");
    $p_stmt->bind_param("i", $team_id);
    $p_stmt->execute();
    $p_res = $p_stmt->get_result();
    $existing_photos = [];
    while($p_row = $p_res->fetch_assoc()){
        $existing_photos[$p_row['member_id_code']] = $p_row['photo'];
    }
    $conn->query("DELETE FROM team_members WHERE team_id=$team_id");
} else {
    $in = $conn->prepare("INSERT INTO team_information (user_email, team_name, college_name, guide_name, branch, designation, contact_number, alt_contact_number, guide_email) VALUES (?,?,?,?,?,?,?,?,?)");
    $in->bind_param("sssssssss", $user_email, $team_name, $college_name, $guide_name, $guide_branch, $designation, $contact_number, $alt_contact, $guide_email);
    $in->execute();
    $team_id = $in->insert_id;
    $existing_photos = [];
}

/* ---------- INSERT MEMBERS & SEND EMAILS ---------- */
for($i=0; $i<count($names); $i++){
    $memberNum = str_pad($i + 1, 2, "0", STR_PAD_LEFT);
    $generated_id = "T-" . $unique_reg_id . "-" . $memberNum;

    $photo_path = $existing_photos[$generated_id] ?? "";
    if(isset($_FILES['photo']['name'][$i]) && $_FILES['photo']['error'][$i] == 0){
        $ext = pathinfo($_FILES['photo']['name'][$i], PATHINFO_EXTENSION);
        $photo_name = "photo_" . $generated_id . "_" . time() . "." . $ext;
        $upload_dir = "uploads/photos/";
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        move_uploaded_file($_FILES['photo']['tmp_name'][$i], $upload_dir . $photo_name);
        $photo_path = $upload_dir . $photo_name;
    }

    $m = $conn->prepare("INSERT INTO team_members (team_id, student_name, branch, enrollment_prn, year, mobile_no, gender, email, member_id_code, photo) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $m->bind_param("isssisssss", $team_id, $names[$i], $branches[$i], $prns[$i], $years[$i], $mobiles[$i], $genders[$i], $emails[$i], $generated_id, $photo_path);
    $m->execute();

    /* --- PDF ENTRY PASS --- */
    $pdf = new TCPDF('L', 'mm', array(85, 54), true, 'UTF-8', false);
    $pdf->setPrintHeader(false); 
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false, 0); 
    $pdf->SetMargins(2, 2, 2);
    $pdf->AddPage();
    $pdf->Rect(0, 0, 85, 54, 'F', array(), array(255, 255, 255));
    
    $yashoda_logo = 'assets/images/Yashoda.jpg';
    $rotrax_logo = 'assets/images/logooo.jpg';
    if(file_exists($yashoda_logo)) $pdf->Image($yashoda_logo, 2, 2, 10, 10);
    if(file_exists($rotrax_logo)) $pdf->Image($rotrax_logo, 73, 2, 10, 10);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 5);
    $pdf->SetXY(12, 2);
    $pdf->Cell(61, 3, "Yashoda Shikshan Prasarak Mandal's", 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetXY(12, 4.5);
    $pdf->Cell(61, 4, "YASHODA TECHNICAL CAMPUS, SATARA", 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 4);
    $pdf->SetXY(12, 8);
    $pdf->Cell(61, 2, "Approved by AICTE, PCI & Govt. of Maharashtra", 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 5);
    $pdf->SetTextColor(168, 85, 247); 
    $pdf->SetXY(12, 10);
    $pdf->Cell(61, 3, "ROTARY CLUB OF SATARA", 0, 1, 'C');

    $pdf->SetDrawColor(168, 85, 247);
    $pdf->Line(2, 14, 83, 14);

    if($photo_path != "" && file_exists($photo_path)){
        $pdf->Image($photo_path, 63, 16, 18, 22);
    } else {
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Rect(63, 16, 18, 22);
    }

    /* ---------- FINAL FIX FOR QR REDIRECT (AUTOMATED) ---------- */
    // Using your official domain for the QR verification link
    $verification_url = "https://rotarex.in/verify_meal.php?token=" . trim($generated_id);

    $qr_style = array(
        'border' => 0,
        'vpadding' => 2, 
        'hpadding' => 2,
        'fgcolor' => array(0,0,0),
        'bgcolor' => array(255,255,255),
        'module_width' => 1,
        'module_height' => 1
    );

    $pdf->write2DBarcode($verification_url, 'QRCODE,H', 44, 21, 19, 19, $qr_style, 'N');
    
    /* --- ADDED LINE UNDER QR CODE --- */
    $pdf->SetFont('helvetica', 'B', 4);
    $pdf->SetXY(44, 39.5);
    $pdf->Cell(19, 2, 'SCAN FOR MEAL', 0, 0, 'C');

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(2, 15);
    
    $html = '
    <table border="0" cellpadding="1" style="font-family:helvetica; font-size:6.5pt;">
        <tr><td width="42px"><b>NAME:</b></td><td width="138px">'.$names[$i].'</td></tr>
        <tr><td><b>TEAM ID:</b></td><td style="color:#a855f7;"><b>'.$generated_id.'</b></td></tr>
        <tr><td><b>REG ID:</b></td><td>'.$unique_reg_id.'</td></tr>
        <tr><td><b>TEAM:</b></td><td>'.$team_name.'</td></tr>
        <tr><td><b>BRANCH:</b></td><td>'.$branches[$i].'</td></tr>
        <tr><td><b>CONTACT:</b></td><td>'.$contact.'</td></tr>
        <tr><td><b>COLLEGE:</b></td><td>'.$college_name.'</td></tr>
        <tr><td><b>GUIDE:</b></td><td>'.$guide_name.'</td></tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->SetFillColor(168, 85, 247);
    $pdf->Rect(0, 48, 85, 6, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 6);
    $pdf->SetXY(0, 49.5);
    $pdf->Cell(85, 3, 'InnovHub - OFFICIAL ENTRY PASS', 0, 0, 'C');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(63, 40);
    $pdf->SetFont('helvetica', 'B', 4.5);
    $pdf->Cell(18, 3, 'Authorized Sign.', 'T', 0, 'C');

    $pdfData = $pdf->Output('', 'S');

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = ''; 
        $mail->Password = ''; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('rotraxproject@gmail.com', 'InnovaHub');
        $mail->addAddress($emails[$i]);
        $mail->isHTML(true);
        $mail->Subject = "Entry Pass - $generated_id";
        $mail->Body = "Hello <b>{$names[$i]}</b>, please find your pass attached.<br><br>For more details, visit: <a href='https://rotarex.in/rotrax/'>https://rotarex.in/rotrax/</a>";
        $mail->addStringAttachment($pdfData, "$generated_id.pdf");
        $mail->send();
    } catch (Exception $e) {}
}

$_SESSION['success_step2'] = true;
header("Location: step2.php");
exit();
?>