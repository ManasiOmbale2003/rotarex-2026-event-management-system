<?php
session_start();

/* ================= PHPMailer & TCPDF INTEGRATION ================= */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once('tcpdf/tcpdf.php'); 
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "root", "rotrax2026");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$user_email = $_SESSION['user'];

/* ===== FETCH ALL DATA ===== */
$user_data = $conn->query("SELECT * FROM users WHERE email = '$user_email'")->fetch_assoc();
$reg_step = $conn->query("SELECT * FROM registrations WHERE user_email = '$user_email'")->fetch_assoc();
$team_info = $conn->query("SELECT * FROM team_information WHERE user_email = '$user_email'")->fetch_assoc();
$team_id = $team_info['id'] ?? 0;

$members_res = $conn->query("SELECT * FROM team_members WHERE team_id = '$team_id'");
$members = [];
while($row = $members_res->fetch_assoc()) { $members[] = $row; }

$project = $conn->query("SELECT * FROM project_proposals WHERE user_email = '$user_email'")->fetch_assoc();

$payment_res = $conn->query("SELECT * FROM registration_payments WHERE email = '$user_email' AND status = 'Success' LIMIT 1");
if($payment_res->num_rows == 0) {
    $payment_res = $conn->query("SELECT * FROM payments WHERE email = '$user_email' LIMIT 1");
}
$payment = $payment_res->fetch_assoc();

$reg_id = $user_data['unique_reg_id'] ?? 'Pending';
$current_time = date("d-m-Y H:i:s");

/* ================= SERVER-SIDE EMAIL LOGIC ================= */
$process_complete = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_confirm'])) {
    
    // 1. Generate PDF for Email attachment using TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('ROTRAX');
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    $pdf_html = '
    <table cellpadding="5" style="width:100%;">
        <tr>
            <td width="15%"><img src="assets/images/Yashoda.jpg" width="50"></td>
            <td width="70%" align="center">
                <span style="font-size:10px;">Yashoda Shikshan Prasarak Mandal\'s</span><br>
                <b style="font-size:16px;">YASHODA TECHNICAL CAMPUS, SATARA</b><br>
                <i style="font-size:8px;">Approved by AICTE, PCI & Govt. of Maharashtra</i><br>
                <b style="font-size:12px;">ROTARY CLUB OF SATARA</b><br>
                <b style="font-size:10px;">ROTAREX 2026 | OFFICIAL REGISTRATION FORM</b>
            </td>
            <td width="15%" align="right"><img src="assets/images/logooo.jpg" width="50"></td>
        </tr>
    </table>
    <div style="text-align:right; font-weight:bold; border-bottom:1.5px solid #000; font-size:10px;">Registration ID: '.$reg_id.'</div>
    <br><br>
    <div style="background-color:#f2f2f2; font-weight:bold; border:1px solid #ccc; font-size:10px;"> CANDIDATE & TEAM DETAILS</div>
    <table border="1" cellpadding="5" style="font-size:9px;">
        <tr><td width="30%"><b>Full Name</b></td><td width="70%">'.$user_data['fullname'].'</td></tr>
        <tr><td><b>Team Name</b></td><td>'.($team_info['team_name'] ?? 'N/A').'</td></tr>
        <tr><td><b>College Name</b></td><td>'.($team_info['college_name'] ?? 'N/A').'</td></tr>
        <tr><td><b>Theme</b></td><td>'.($reg_step['theme'] ?? 'N/A').'</td></tr>
    </table>
    <br>
    <div style="background-color:#f2f2f2; font-weight:bold; border:1px solid #ccc; font-size:10px;"> TEAM COMPOSITION</div>
    <table border="1" cellpadding="5" style="font-size:9px;">
        <tr style="background-color:#fafafa;"><td width="40%"><b>Member Name</b></td><td width="40%"><b>PRN / Enrollment</b></td><td width="20%"><b>Branch</b></td></tr>';
        foreach($members as $m) {
            $pdf_html .= '<tr><td>'.$m['student_name'].'</td><td>'.$m['enrollment_prn'].'</td><td>'.$m['branch'].'</td></tr>';
        }
    $pdf_html .= '</table>
    <br>
    <div style="background-color:#f2f2f2; font-weight:bold; border:1px solid #ccc; font-size:10px;"> PROJECT PROPOSAL</div>
    <table border="1" cellpadding="5" style="font-size:9px;">
        <tr><td width="30%"><b>Title</b></td><td width="70%">'.($project['project_title'] ?? 'N/A').'</td></tr>
        <tr><td><b>Technologies</b></td><td>'.($project['tools_technologies'] ?? 'N/A').'</td></tr>
        <tr><td><b>Guide Name</b></td><td>'.($team_info['guide_name'] ?? 'N/A').'</td></tr>
    </table>
    <br>
    <div style="background-color:#f2f2f2; font-weight:bold; border:1px solid #ccc; font-size:10px;"> PAYMENT INFORMATION</div>
    <table border="1" cellpadding="5" style="font-size:9px;">
        <tr><td width="30%"><b>Transaction ID</b></td><td width="70%">'.($payment['payment_id'] ?? 'N/A').'</td></tr>
        <tr><td><b>Amount Paid</b></td><td>₹'.($payment['amount'] ?? '500').'</td></tr>
        <tr><td><b>Status</b></td><td>VERIFIED (Success)</td></tr>
    </table>
    <br><br><br>
    <table cellpadding="5" style="width:100%; font-size:9px; text-align:center;">
        <tr>
            <td><br><br>__________________________<br><b>Project Guide</b></td>
            <td><br><br>__________________________<br><b>Team Leader</b></td>
            <td><br><br>__________________________<br><b>Principal</b></td>
        </tr>
    </table>
    <br><br>
    <div style="font-size:8px; color:#555;">
        <b>Downloaded On:</b> '.$current_time.'<br>
        &copy; InnovaHub 2026 Official Registration Document
    </div>';
    
    $pdf->writeHTML($pdf_html, true, false, true, false, '');
    $pdf_data = $pdf->Output('registration.pdf', 'S'); 

    // 2. Send Email
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = ''; 
        $mail->Password = ''; 
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('rotraxproject@gmail.com', 'ROTAREX 2026');
        $mail->addAddress($user_email);
        $mail->addStringAttachment($pdf_data, "ROTAREX_Form_{$reg_id}.pdf");

        $mail->isHTML(true);
        $mail->Subject = 'Official Registration Form - ROTAREX 2026';
        $mail->Body    = "Hello {$user_data['fullname']},<br><br>Your registration for ROTAREX 2026 is confirmed. Please find your attached registration form with current timestamp and signature fields.";

        $mail->send();
        $process_complete = true;
    } catch (Exception $e) { 
        // Log error if needed: $mail->ErrorInfo
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROTAREX 2026 | Final Preview</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        :root {
          --purple-main: #a855f7;
          --text-color: #000;
        }

        /* ===== RESET & BODY ===== */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

        body {
            min-height: 100vh;
            background: url("assets/images/img.jpeg") center/cover no-repeat fixed;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px 10px;
            color: var(--text-color);
        }

        /* ===== GLASS CONTAINER ===== */
        .main-container { max-width: 1000px; width: 100%; display: flex; flex-direction: column; gap: 20px; }

        .box {
            position: relative;
            background: rgba(255, 255, 255, 0.25); 
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            padding: 25px 15px;
            overflow: hidden;
        }

        .box::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url("assets/images/img.jpeg") center/cover no-repeat;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
        }

        .box > * { position: relative; z-index: 1; }

        h2 { text-align: center; color: #000; margin-bottom: 25px; font-size: 20px; font-weight: 700; border-bottom: 2px solid var(--purple-main); padding-bottom: 10px; }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .data-item { background: rgba(255,255,255,0.5); padding: 12px; border-radius: 12px; border-left: 5px solid var(--purple-main); box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .label { font-size: 10px; text-transform: uppercase; font-weight: 600; color: #555; margin-bottom: 2px; display: block; }
        .val { font-weight: 600; font-size: 14px; color: #000; word-break: break-word; }

        .table-responsive { width: 100%; overflow-x: auto; margin-top: 15px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; background: rgba(255, 255, 255, 0.3); min-width: 500px; }
        th, td { border: 1px solid rgba(0,0,0,0.1); padding: 10px; text-align: left; color: #000; font-size: 13px; }
        th { background: rgba(168, 85, 247, 0.2); font-weight: 600; color: var(--purple-main); }

        .btn-submit { 
            background: var(--purple-main); color: #fff; border: none; padding: 14px 20px; 
            border-radius: 12px; font-weight: 700; cursor: pointer; display: block; 
            margin: 30px auto 0; width: 100%; max-width: 350px; transition: 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2); text-transform: uppercase;
            font-size: 14px;
        }
        .btn-submit:hover { background: #9333ea; transform: translateY(-2px); }

        /* ===== HIDDEN PDF CONTENT STYLING ===== */
        #registration-form-pdf {
            background: #ffffff;
            color: #333;
            padding: 40px 50px;
            width: 850px; 
            position: absolute;
            left: -9999px;
            top: 0;
            z-index: -1;
        }

        #registration-form-pdf::after {
            content: "";
            background: url("assets/images/logooo.jpg") no-repeat center;
            background-size: 400px;
            opacity: 0.05;
            top: 0; left: 0; bottom: 0; right: 0;
            position: absolute;
            z-index: -1;
        }

        .pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .pdf-header img { height: 60px; width: auto; }

        .pdf-college-info {
            text-align: center;
            flex: 1;
        }

        .pdf-college-info h4 { font-size: 12px; font-weight: 500; margin: 0; color: #333; }
        .pdf-college-info h1 { font-size: 20px; font-weight: 800; margin: 2px 0; color: #000; }
        .pdf-college-info p.sub-text { font-size: 9px; margin: 0; color: #666; font-weight: 500; }
        .pdf-college-info h3 { font-size: 14px; font-weight: 700; margin-top: 4px; color: #000; }
        .pdf-college-info p.event-text { font-size: 11px; font-weight: 600; color: #333; margin-top: 2px; text-transform: uppercase; }

        .pdf-reg-id {
            text-align: right;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 10px;
            border-bottom: 1.5px solid #000;
            padding-bottom: 5px;
        }

        .pdf-section-title {
            background: #f2f2f2;
            padding: 8px 12px;
            font-weight: 800;
            margin-top: 15px;
            border: 1px solid #ccc;
            color: #000;
            font-size: 12px;
            text-transform: uppercase;
        }

        .pdf-table { width: 100%; border-collapse: collapse; background: transparent; }
        .pdf-table th, .pdf-table td { border: 1px solid #ddd; padding: 10px; font-size: 11px; text-align: left; color: #000; }
        .pdf-table th { background: #fafafa; width: 25%; font-weight: 700; }

        .pdf-footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
            text-align: center;
        }

        .sig-box {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 11px;
            font-weight: bold;
        }

        @media(max-width:768px){ 
            .grid { grid-template-columns: 1fr; } 
            h2 { font-size: 18px; }
            .box { padding: 20px 10px; }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="box">
        <h2>Final Registration Preview</h2>

        <div class="grid">
            <div class="data-item"><span class="label">Registration ID</span><div class="val"><?php echo htmlspecialchars($user_data['unique_reg_id'] ?? 'Pending'); ?></div></div>
            <div class="data-item"><span class="label">Full Name</span><div class="val"><?php echo htmlspecialchars($user_data['fullname']); ?></div></div>
            <div class="data-item"><span class="label">Team Name</span><div class="val"><?php echo htmlspecialchars($team_info['team_name'] ?? 'N/A'); ?></div></div>
            <div class="data-item"><span class="label">Project Guide</span><div class="val"><?php echo htmlspecialchars($team_info['guide_name'] ?? 'N/A'); ?></div></div>
        </div>

        <h3 style="color:var(--purple-main); font-size:16px; margin: 25px 0 10px; border-bottom: 2px solid rgba(168, 85, 247, 0.2); padding-bottom: 5px;">Team Members</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>PRN / Enrollment</th>
                        <th>Branch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($members)): ?>
                        <tr><td colspan="3" style="text-align:center;">No members found.</td></tr>
                    <?php else: ?>
                        <?php foreach($members as $m): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($m['enrollment_prn']); ?></td>
                            <td><?php echo htmlspecialchars($m['branch']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h3 style="color:var(--purple-main); font-size:16px; margin: 30px 0 10px; border-bottom: 2px solid rgba(168, 85, 247, 0.2); padding-bottom: 5px;">Project Proposal</h3>
        <div class="data-item" style="margin-top:10px;">
            <span class="label">Project Title</span>
            <div class="val"><?php echo htmlspecialchars($project['project_title'] ?? 'N/A'); ?></div>
            <hr style="margin:12px 0; opacity:0.1; border:0; border-top:1px solid #000;">
            <span class="label">Technologies Used</span>
            <div class="val"><?php echo htmlspecialchars($project['tools_technologies'] ?? 'N/A'); ?></div>
        </div>

        <h3 style="color:var(--purple-main); font-size:16px; margin: 30px 0 10px; border-bottom: 2px solid rgba(168, 85, 247, 0.2); padding-bottom: 5px;">Payment Details</h3>
        <div class="grid">
            <div class="data-item"><span class="label">Payment ID</span><div class="val"><?php echo htmlspecialchars($payment['payment_id'] ?? 'N/A'); ?></div></div>
            <div class="data-item"><span class="label">Status</span><div class="val" style="color: #16a34a;">Success / Verified</div></div>
        </div>

        <form method="POST" id="confirmForm">
            <input type="hidden" name="action_confirm" value="1">
            <button type="submit" class="btn-submit">Confirm & Download PDF</button>
        </form>
    </div>
</div>

<div id="registration-form-pdf">
    <div class="pdf-header">
        <img src="assets/images/Yashoda.jpg" alt="Yashoda Logo">
        <div class="pdf-college-info">
            <h4>Yashoda Shikshan Prasarak Mandal's</h4>
            <h1>YASHODA TECHNICAL CAMPUS, SATARA</h1>
            <p class="sub-text">Approved by AICTE, PCI & Govt. of Maharashtra</p>
            <h3>ROTARY CLUB OF SATARA</h3>
            <p class="event-text">ROTAREX 2026 | OFFICIAL REGISTRATION FORM</p>
        </div>
        <img src="assets/images/logooo.jpg" alt="ROTRAX Logo">
    </div>

    <div class="pdf-reg-id">
        Registration ID: <?php echo htmlspecialchars($user_data['unique_reg_id'] ?? 'N/A'); ?>
    </div>

    <div class="pdf-section-title">CANDIDATE & TEAM DETAILS</div>
    <table class="pdf-table">
        <tr><th>Full Name</th><td><?php echo htmlspecialchars($user_data['fullname']); ?></td></tr>
        <tr><th>Team Name</th><td><?php echo htmlspecialchars($team_info['team_name'] ?? 'N/A'); ?></td></tr>
        <tr><th>College Name</th><td><?php echo htmlspecialchars($team_info['college_name'] ?? 'N/A'); ?></td></tr>
        <tr><th>Theme</th><td><?php echo htmlspecialchars($reg_step['theme'] ?? 'N/A'); ?></td></tr>
    </table>

    <div class="pdf-section-title">TEAM COMPOSITION</div>
    <table class="pdf-table">
        <thead>
            <tr>
                <th style="width: 40%;">Member Name</th>
                <th style="width: 40%;">PRN / Enrollment</th>
                <th style="width: 20%;">Branch</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($members as $m): ?>
            <tr>
                <td><?php echo htmlspecialchars($m['student_name']); ?></td>
                <td><?php echo htmlspecialchars($m['enrollment_prn']); ?></td>
                <td><?php echo htmlspecialchars($m['branch']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pdf-section-title">PROJECT PROPOSAL</div>
    <table class="pdf-table">
        <tr><th>Title</th><td><?php echo htmlspecialchars($project['project_title'] ?? 'N/A'); ?></td></tr>
        <tr><th>Technologies</th><td><?php echo htmlspecialchars($project['tools_technologies'] ?? 'N/A'); ?></td></tr>
        <tr><th>Guide Name</th><td><?php echo htmlspecialchars($team_info['guide_name'] ?? 'N/A'); ?></td></tr>
    </table>

    <div class="pdf-section-title">PAYMENT INFORMATION</div>
    <table class="pdf-table">
        <tr><th>Transaction ID</th><td><?php echo htmlspecialchars($payment['payment_id'] ?? 'N/A'); ?></td></tr>
        <tr><th>Amount Paid</th><td>₹<?php echo htmlspecialchars($payment['amount'] ?? '500'); ?></td></tr>
        <tr><th>Status</th><td>VERIFIED (Success)</td></tr>
    </table>

    <div class="signature-grid">
        <div class="sig-box">Project Guide</div>
        <div class="sig-box">Team Leader</div>
        <div class="sig-box">Principal</div>
    </div>

    <div class="pdf-footer">
        <div style="text-align: left;">
            <p style="font-size: 10px; color: #555;"><strong>Downloaded On:</strong> <?php echo $current_time; ?></p>
            <p style="font-size: 9px; color: #888;">&copy; ROTAREX 2026 Official Registration Document</p>
        </div>
    </div>
</div>

<script>
async function generatePDF() {
    const { jsPDF } = window.jspdf;
    const element = document.getElementById('registration-form-pdf');
    
    // Make visible briefly for capture
    element.style.left = '0px';
    element.style.position = 'fixed'; 

    try {
        const canvas = await html2canvas(element, { 
            scale: 2, 
            useCORS: true, 
            allowTaint: true,
            windowWidth: 850 
        });
        
        const imgData = canvas.toDataURL('image/jpeg');
        const pdf = new jsPDF('p', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        
        pdf.addImage(imgData, 'jpeg', 0, 0, pdfWidth, pdfHeight);
        pdf.save("ROTAREX_2026_Form_<?php echo $user_data['unique_reg_id'] ?? 'User'; ?>.pdf");
        
        // Hide it again
        element.style.left = '-9999px';
        element.style.position = 'absolute';
        
        // Updated Alert with Redirect
        alert("Success! Your Registration Form has been downloaded and sent to your email.");
        window.location.href = "step5_confirmation.php";
        
    } catch (error) {
        console.error("PDF generation error:", error);
        element.style.left = '-9999px';
        element.style.position = 'absolute';
    }
}

// Trigger browser download if the PHP email process was successful
<?php if($process_complete): ?>
    window.onload = function() {
        generatePDF();
    };
<?php endif; ?>
</script>

</body>
</html>