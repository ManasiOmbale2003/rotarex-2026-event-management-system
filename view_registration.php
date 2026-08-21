<?php
session_start();
include 'config.php';

/* ================= PHPMailer & TCPDF INTEGRATION ================= */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once('tcpdf/tcpdf.php'); 
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$reg_id_get = $_GET['id'] ?? '';
$email_get = $_GET['email'] ?? '';

if (empty($reg_id_get) && empty($email_get)) {
    echo "No registration specified.";
    exit();
}

// 1. Fetch Registration details - Updated to support both ID and Email lookup
if (!empty($reg_id_get)) {
    $r_stmt = $conn->prepare("SELECT * FROM registrations WHERE id = ?");
    $r_stmt->bind_param("i", $reg_id_get);
} else {
    $r_stmt = $conn->prepare("SELECT * FROM registrations WHERE user_email = ?");
    $r_stmt->bind_param("s", $email_get);
}

$r_stmt->execute();
$reg_data = $r_stmt->get_result()->fetch_assoc();

if (!$reg_data) {
    echo "Registration record not found.";
    exit();
}

$user_email = $reg_data['user_email'];

/* ===== FETCH ALL DATA ===== */
$u_stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$u_stmt->bind_param("s", $user_email);
$u_stmt->execute();
$user_data = $u_stmt->get_result()->fetch_assoc();

$rs_stmt = $conn->prepare("SELECT * FROM registrations WHERE user_email = ?");
$rs_stmt->bind_param("s", $user_email);
$rs_stmt->execute();
$reg_step = $rs_stmt->get_result()->fetch_assoc();

$ti_stmt = $conn->prepare("SELECT * FROM team_information WHERE user_email = ?");
$ti_stmt->bind_param("s", $user_email);
$ti_stmt->execute();
$team_info = $ti_stmt->get_result()->fetch_assoc();
$team_id = $team_info['id'] ?? 0;

$m_stmt = $conn->prepare("SELECT * FROM team_members WHERE team_id = ?");
$m_stmt->bind_param("i", $team_id);
$m_stmt->execute();
$members_res = $m_stmt->get_result();
$members = [];
while($row = $members_res->fetch_assoc()) { $members[] = $row; }

$p_stmt = $conn->prepare("SELECT * FROM project_proposals WHERE user_email = ?");
$p_stmt->bind_param("s", $user_email);
$p_stmt->execute();
$project = $p_stmt->get_result()->fetch_assoc();

// Payment logic fix - Updated to fetch correct receipt_file field
$payment_res = $conn->query("SELECT * FROM registration_payments WHERE email = '$user_email' AND (status = 'Success' OR status = 'COMPLETED') LIMIT 1");
if($payment_res->num_rows == 0) {
    $payment_res = $conn->query("SELECT * FROM payments WHERE email = '$user_email' LIMIT 1");
}
$payment = $payment_res->fetch_assoc();

$unique_reg_id = $user_data['unique_reg_id'] ?? 'Pending';
$current_time = date("d-m-Y H:i:s");

// Re-fetch team members list for the passes tab
$t_stmt = $conn->prepare("SELECT tm.* FROM team_members tm JOIN team_information ti ON tm.team_id = ti.id WHERE ti.user_email = ?");
$t_stmt->bind_param("s", $user_email);
$t_stmt->execute();
$team_members_list = $t_stmt->get_result();

// Re-fetch proposal docs for the documents tab
$d_stmt = $conn->prepare("SELECT * FROM project_proposals WHERE user_email = ?");
$d_stmt->bind_param("s", $user_email);
$d_stmt->execute();
$proposal_docs = $d_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | View Registration - InnovaHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root{ --purple-main:#a855f7; --text-color:#333; --bg-gray: #f8fafc; }
        body{ font-family:'Poppins',sans-serif; background: var(--bg-gray); color: var(--text-color); padding:40px 15px; }
        .container{ background: white; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); padding: 35px; max-width: 1000px; width: 100%; border: 1px solid #e2e8f0; margin: auto; }
        
        .tabs-header { display: flex; gap: 10px; border-bottom: 2px solid #f1f5f9; margin-bottom: 25px; }
        .tab-btn { padding: 12px 24px; border: none; background: none; font-family: inherit; font-weight: 600; color: #94a3b8; cursor: pointer; transition: 0.3s; }
        .tab-btn.active { color: var(--purple-main); border-bottom: 3px solid var(--purple-main); }

        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .official-form-preview { background: #fff; border: 1px solid #cbd5e1; padding: 40px; margin: 0 auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .form-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .form-section { background: #f1f5f9; font-weight: bold; padding: 10px; border: 1px solid #e2e8f0; margin: 15px 0 5px; font-size: 13px; color: #1e293b; }
        .form-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .form-table th, .form-table td { border: 1px solid #e2e8f0; padding: 10px; font-size: 12px; }
        .form-table th { background: #f8fafc; text-align: left; width: 30%; }

        .btn-download-trigger { background: #22c55e; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: 0.3s; }
        .btn-download-trigger:hover { background: #16a34a; transform: translateY(-1px); }
        
        .preview-frame { width: 100%; height: 500px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; }
        .member-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .member-card { background: #fff; border: 1px solid #e2e8f0; padding: 20px; border-radius: 16px; text-align: center; transition: 0.3s; position: relative; }
        .member-card img { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid #f1f5f9; }

        .doc-label { font-weight: 600; color: var(--purple-main); margin-bottom: 10px; display: flex; align-items: center; gap: 8px; margin-top: 20px; }
        
        .pass-download-btn { margin-top: 15px; background: #6366f1; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-size: 11px; cursor: pointer; transition: 0.2s; width: 100%; }
        .pass-download-btn:hover { background: #4f46e5; }

        /* QR Code styling */
        .qr-wrapper { margin: 10px auto; display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .qr-code-container img { margin: 0 auto; }
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <a href="admin_dashboard.php" style="text-decoration:none; color:#64748b; font-weight: 500;">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Reports
        </a>
        <span class="badge" style="background: #f1f5f9; color: #475569; padding: 8px 12px; border-radius: 20px;">
            Registration ID: <b><?php echo $unique_reg_id; ?></b>
        </span>
    </div>

    <div class="tabs-header">
        <button class="tab-btn active" onclick="openTab(event, 'registration')">Student Details</button>
        <button class="tab-btn" onclick="openTab(event, 'payment')">Payment Receipt</button>
        <button class="tab-btn" onclick="openTab(event, 'documents')">Documents</button>
        <button class="tab-btn" onclick="openTab(event, 'passes')">Entry Passes</button>
    </div>

    <div id="registration" class="tab-content active">
        <div style="text-align: right; margin-bottom: 15px;">
            <button class="btn-download-trigger" onclick="generatePDF()">
                <i class="fa-solid fa-file-pdf me-1"></i> Download Form (PDF)
            </button>
        </div>

        <div class="official-form-preview" id="form-to-print">
            <div class="form-header">
                <img src="assets/images/Yashoda.jpg" width="65" alt="Logo">
                <div style="text-align: center;">
                    <p style="font-size:10px; margin:0; text-transform:uppercase; letter-spacing:1px;">Yashoda Shikshan Prasarak Mandal's</p>
                    <h3 style="margin:2px 0; font-size:20px; color:#000;">YASHODA TECHNICAL CAMPUS, SATARA</h3>
                    <p style="font-size:11px; font-weight:bold; margin:0; color:#444;">ROTRAX 2026 | OFFICIAL REGISTRATION FORM</p>
                </div>
                <img src="assets/images/logooo.jpg" width="65" alt="Logo">
            </div>

            <div class="form-section">CANDIDATE & TEAM DETAILS</div>
            <table class="form-table">
                <tr><th>Full Name</th><td><?php echo htmlspecialchars($user_data['fullname'] ?? 'N/A'); ?></td></tr>
                <tr><th>Team Name</th><td><?php echo htmlspecialchars($team_info['team_name'] ?? 'N/A'); ?></td></tr>
                <tr><th>College Name</th><td><?php echo htmlspecialchars($team_info['college_name'] ?? 'N/A'); ?></td></tr>
                <tr><th>Theme</th><td><?php echo htmlspecialchars($reg_step['theme'] ?? 'N/A'); ?></td></tr>
            </table>

            <div class="form-section">TEAM COMPOSITION</div>
            <table class="form-table">
                <tr style="background:#f8fafc;"><th>Member Name</th><th>PRN / Enrollment</th><th>Branch</th></tr>
                <?php foreach($members as $m): ?>
                <tr><td><?php echo htmlspecialchars($m['student_name']); ?></td><td><?php echo htmlspecialchars($m['enrollment_prn']); ?></td><td><?php echo htmlspecialchars($m['branch']); ?></td></tr>
                <?php endforeach; ?>
            </table>

            <div class="form-section">PROJECT PROPOSAL</div>
            <table class="form-table">
                <tr><th>Project Title</th><td><?php echo htmlspecialchars($project['project_title'] ?? 'N/A'); ?></td></tr>
                <tr><th>Technologies</th><td><?php echo htmlspecialchars($project['tools_technologies'] ?? 'N/A'); ?></td></tr>
                <tr><th>Guide Name</th><td><?php echo htmlspecialchars($team_info['guide_name'] ?? 'N/A'); ?></td></tr>
            </table>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 60px; text-align: center; font-size: 11px;">
                <div style="border-top: 1px solid #000; padding-top: 5px;">Project Guide</div>
                <div style="border-top: 1px solid #000; padding-top: 5px;">Team Leader</div>
                <div style="border-top: 1px solid #000; padding-top: 5px;">Principal / HOD</div>
            </div>
            
            <div style="margin-top:30px; font-size:9px; color:#94a3b8; border-top: 1px solid #f1f5f9; padding-top: 10px;">
                Generated On: <?php echo $current_time; ?> | This is an electronically generated document.
            </div>
        </div>
    </div>

    <div id="payment" class="tab-content">
        <div style="background:#fff; padding:25px; border:1px solid #e2e8f0; border-radius:16px;">
            <?php if ($payment && !empty($payment['receipt_file'])): 
                $receipt_filename = $payment['receipt_file'];
                $receipt_path = "uploads/receipts/" . $receipt_filename;
            ?>
                <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;">
                    <span class="badge" style="background:#22c55e; color:white; padding:5px 10px; border-radius:5px;">Success</span>
                    <p style="margin-top: 10px;"><b>Txn ID:</b> <?php echo htmlspecialchars($payment['payment_id']); ?> | <b>Amount:</b> ₹<?php echo htmlspecialchars($payment['amount']); ?></p>
                </div>
                <?php if(file_exists($receipt_path)): ?>
                    <iframe class="preview-frame" src="<?php echo $receipt_path; ?>"></iframe>
                    <div style="margin-top: 10px;"><a href="<?php echo $receipt_path; ?>" target="_blank" class="btn-download-trigger" style="background:var(--purple-main); font-size:12px;">Open Receipt in New Tab</a></div>
                <?php else: ?>
                    <div style="padding: 20px; border: 1px dashed #ef4444; color: #ef4444; border-radius: 10px; text-align: center;">
                        <i class="fa-solid fa-circle-exclamation fa-2x"></i><br>
                        Receipt file not found on server: <?php echo $receipt_path; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?> 
                <p>No payment record or receipt file found for this user.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="documents" class="tab-content">
        <div style="background:#fff; padding:25px; border:1px solid #e2e8f0; border-radius:16px;">
            
            <div class="doc-label"><i class="fa-solid fa-file-shield"></i> Bonafide Certificate</div>
            <div style="margin-bottom: 30px;">
                <?php if(!empty($proposal_docs['bonafide_file'])): 
                    $bonafide_path = "uploads/bonafide/" . $proposal_docs['bonafide_file']; 
                ?>
                    <iframe class="preview-frame" src="<?php echo $bonafide_path; ?>"></iframe>
                    <div style="margin-top: 10px; display: flex; gap: 10px;">
                        <a href="<?php echo $bonafide_path; ?>" target="_blank" class="btn-download-trigger" style="background:var(--purple-main); font-size:12px;">View Bonafide</a>
                        <a href="<?php echo $bonafide_path; ?>" download class="btn-download-trigger" style="background:#0ea5e9; font-size:12px;"><i class="fa-solid fa-download me-1"></i> Download Bonafide</a>
                    </div>
                <?php else: ?> 
                    <p style="color:#64748b; font-size: small;">Not uploaded.</p> 
                <?php endif; ?>
            </div>

            <div class="doc-label"><i class="fa-solid fa-file-contract"></i> Principal Recommendation Letter</div>
            <div style="margin-bottom: 30px;">
                <?php 
                if(!empty($proposal_docs['attachment_file'])): 
                    $principal_path = "uploads/principal_letter/" . $proposal_docs['attachment_file']; 
                ?>
                    <iframe class="preview-frame" src="<?php echo $principal_path; ?>"></iframe>
                    <div style="margin-top: 10px; display: flex; gap: 10px;">
                        <a href="<?php echo $principal_path; ?>" target="_blank" class="btn-download-trigger" style="background:var(--purple-main); font-size:12px;">View Letter</a>
                        <a href="<?php echo $principal_path; ?>" download class="btn-download-trigger" style="background:#0ea5e9; font-size:12px;"><i class="fa-solid fa-download me-1"></i> Download Letter</a>
                    </div>
                <?php else: ?> 
                    <p style="color:#64748b; font-size: small;">Not uploaded.</p> 
                <?php endif; ?>
            </div>

            <div class="doc-label"><i class="fa-solid fa-file-signature"></i> Self Evaluation Letter</div>
<div class="mt-3">
    <?php 
    if(!empty($proposal_docs['self_eval_file'])): 
        $pure_filename = basename($proposal_docs['self_eval_file']);
        $self_eval_path = "uploads/evaluations/" . $pure_filename; 
    ?>
        <?php if(file_exists($self_eval_path)): ?>
            <iframe src="<?php echo $self_eval_path; ?>" width="100%" height="500px" style="border:1px solid #ddd; border-radius:8px;"></iframe>
            
            <div class="mt-2 d-flex gap-2">
                <a href="<?php echo $self_eval_path; ?>" target="_blank" class="btn btn-sm" style="background-color: #a855f7; color:white; text-decoration:none; padding: 5px 10px; border-radius: 4px;">View Evaluation</a>
                <a href="<?php echo $self_eval_path; ?>" download class="btn btn-sm btn-info text-white" style="text-decoration:none; padding: 5px 10px; border-radius: 4px;">
                    <i class="fa-solid fa-download me-1"></i> Download Evaluation
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">File not found at: <?php echo $self_eval_path; ?></div>
        <?php endif; ?>
    <?php else: ?> 
        <p class="text-muted small">No evaluation file uploaded.</p> 
    <?php endif; ?>
</div>

        </div>
    </div>

    <div id="passes" class="tab-content">
        <div class="member-grid">
            <?php while($m_pass = $team_members_list->fetch_assoc()): 
                $card_id = "pass_card_" . $m_pass['id'];
                $qr_data = "https://rotarex.in/verify_meal.php?token=" . trim($m_pass['member_id_code']);
            ?>
                <div class="member-card" id="<?php echo $card_id; ?>">
                    <div class="pass-content-wrapper">
                        <?php if(!empty($m_pass['photo'])): ?>
                            <img src="<?php echo $m_pass['photo']; ?>" id="photo_<?php echo $m_pass['id']; ?>" alt="Photo">
                        <?php else: ?>
                            <div style="width:90px; height:90px; background:#f1f5f9; border-radius:50%; margin: 0 auto 15px; display:flex; align-items:center; justify-content:center;">
                                <i class="fa-solid fa-user-tie fa-2xl" style="color:#cbd5e1;"></i>
                            </div>
                        <?php endif; ?>
                        <div style="font-weight:600; color:#1e293b;"><?php echo htmlspecialchars($m_pass['student_name']); ?></div>
                        <div style="font-size:12px; color:#9333ea; font-weight:600;"><?php echo htmlspecialchars($m_pass['member_id_code']); ?></div>
                        <div style="font-size:11px; color:#64748b; margin-top:5px;"><?php echo htmlspecialchars($m_pass['branch']); ?></div>
                        
                        <div class="qr-wrapper">
                            <div id="qr_<?php echo $m_pass['id']; ?>" class="qr-code-container"></div>
                            <div style="font-size:9px; color:#16a34a; font-weight:bold; text-transform:uppercase;">Lunch & Breakfast Pass</div>
                        </div>
                        <script>
                            new QRCode(document.getElementById("qr_<?php echo $m_pass['id']; ?>"), {
                                text: "<?php echo $qr_data; ?>",
                                width: 70,
                                height: 70,
                                colorDark : "#000000",
                                colorLight : "#ffffff",
                                correctLevel : QRCode.CorrectLevel.H
                            });
                        </script>
                        <div style="font-size:10px; color:#000; margin-top:5px; font-weight: bold; border-top: 1px dashed #cbd5e1; padding-top: 5px;">ROTRAX 2026</div>
                    </div>
                    <button class="pass-download-btn" onclick="downloadPass({
                        memberCode: '<?php echo $m_pass['member_id_code']; ?>',
                        name: '<?php echo addslashes($m_pass['student_name']); ?>',
                        regId: '<?php echo $unique_reg_id; ?>',
                        team: '<?php echo addslashes($team_info['team_name']); ?>',
                        branch: '<?php echo addslashes($m_pass['branch']); ?>',
                        college: '<?php echo addslashes($team_info['college_name']); ?>',
                        guide: '<?php echo addslashes($team_info['guide_name']); ?>',
                        contact: '<?php echo $team_info['contact_number']; ?>',
                        qrId: 'qr_<?php echo $m_pass['id']; ?>',
                        photoId: '<?php echo !empty($m_pass['photo']) ? "photo_".$m_pass['id'] : ""; ?>'
                    })">
                        <i class="fa-solid fa-download me-1"></i> Download Pass
                    </button>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) { tabcontent[i].style.display = "none"; }
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) { tablinks[i].className = tablinks[i].className.replace(" active", ""); }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

async function generatePDF() {
    const { jsPDF } = window.jspdf;
    const element = document.getElementById('form-to-print');
    try {
        const canvas = await html2canvas(element, { scale: 2 });
        const imgData = canvas.toDataURL('image/jpeg', 1.0);
        const pdf = new jsPDF('p', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const imgProps = pdf.getImageProperties(imgData);
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
        pdf.save("ROTRAX_Form_<?php echo $unique_reg_id; ?>.pdf");
    } catch (error) { console.error("PDF Error:", error); }
}

async function downloadPass(data) {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('l', 'mm', [85, 54]);
    pdf.setFillColor(255, 255, 255);
    pdf.rect(0, 0, 85, 54, 'F');

    const yashodaLogo = "assets/images/Yashoda.jpg";
    const rotraxLogo = "assets/images/logooo.jpg";
    
    try {
        pdf.addImage(yashodaLogo, 'JPEG', 2, 2, 10, 10);
        pdf.addImage(rotraxLogo, 'JPEG', 73, 2, 10, 10);
    } catch(e) { console.log("Logo missing"); }

    pdf.setTextColor(0, 0, 0);
    pdf.setFontSize(5);
    pdf.setFont("helvetica", "bold");
    pdf.text("Yashoda Shikshan Prasarak Mandal's", 42.5, 4, { align: "center" });
    
    pdf.setFontSize(7);
    pdf.text("YASHODA TECHNICAL CAMPUS, SATARA", 42.5, 8, { align: "center" });
    
    pdf.setFontSize(4);
    pdf.setFont("helvetica", "normal");
    pdf.text("Approved by AICTE, PCI & Govt. of Maharashtra", 42.5, 10, { align: "center" });

    pdf.setFontSize(5);
    pdf.setFont("helvetica", "bold");
    pdf.setTextColor(168, 85, 247); 
    pdf.text("ROTARY CLUB OF SATARA", 42.5, 13, { align: "center" });

    pdf.setDrawColor(168, 85, 247);
    pdf.line(2, 14, 83, 14);

    if(data.photoId) {
        const photoImg = document.getElementById(data.photoId);
        if(photoImg) {
            const canvas = document.createElement("canvas");
            canvas.width = photoImg.naturalWidth;
            canvas.height = photoImg.naturalHeight;
            const ctx = canvas.getContext("2d");
            ctx.drawImage(photoImg, 0, 0);
            const photoBase64 = canvas.toDataURL("image/jpeg");
            pdf.addImage(photoBase64, 'JPEG', 63, 16, 18, 22);
        }
    } else {
        pdf.setDrawColor(200, 200, 200);
        pdf.rect(63, 16, 18, 22);
    }

    const qrCanvas = document.querySelector(`#${data.qrId} canvas`);
    if(qrCanvas) {
        const qrData = qrCanvas.toDataURL("image/png");
        pdf.addImage(qrData, 'PNG', 44, 21, 19, 19);
        pdf.setFontSize(4);
        pdf.setTextColor(0,0,0);
        pdf.text("SCAN FOR MEAL", 53.5, 41.5, { align: "center" });
    }

    pdf.setTextColor(0, 0, 0);
    pdf.setFontSize(6.5);
    let y = 18;
    const xLabel = 5;
    const xValue = 18;

    const fields = [
        ["NAME:", data.name],
        ["TEAM ID:", data.memberCode, true],
        ["REG ID:", data.regId],
        ["TEAM:", data.team],
        ["BRANCH:", data.branch],
        ["CONTACT:", data.contact],
        ["COLLEGE:", data.college],
        ["GUIDE:", data.guide]
    ];

    fields.forEach(field => {
        pdf.setFont("helvetica", "bold");
        pdf.text(field[0], xLabel, y);
        
        if(field[2]) { 
             pdf.setTextColor(168, 85, 247);
             pdf.setFont("helvetica", "bold");
        } else {
            pdf.setTextColor(0, 0, 0);
            pdf.setFont("helvetica", "normal");
        }
        
        let val = field[1] || "N/A";
        if(val.length > 28) val = val.substring(0, 25) + "...";
        
        pdf.text(val, xValue, y);
        pdf.setTextColor(0, 0, 0);
        y += 3.8;
    });

    pdf.line(63, 41, 81, 41);
    pdf.setFontSize(4.5);
    pdf.setFont("helvetica", "bold");
    pdf.text("Authorized Sign.", 72, 43.5, { align: "center" });

    pdf.setFillColor(168, 85, 247);
    pdf.rect(0, 48, 85, 6, 'F');
    pdf.setTextColor(255, 255, 255);
    pdf.setFontSize(6);
    pdf.text("ROTAREX 2026 - OFFICIAL ENTRY PASS", 42.5, 52, { align: "center" });

    pdf.save(`Pass_${data.memberCode}.pdf`);
}
</script>

</body>
</html>