<?php
session_start();
require_once('tcpdf/tcpdf.php');
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set Timezone for accurate current time
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

/* ---------- DATABASE CONNECTION ---------- */
require_once('config.php'); 

$user_email = $_SESSION['user'];

// --- LOCK LOGIC: Check if already submitted ---
$check_lock = $conn->prepare("SELECT id FROM registration_payments WHERE email = ? AND receipt_uploaded IS NOT NULL");

// LOGIC FOR FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['receipt'])) {
    // 1. Fetch User Info
    $u_stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $u_stmt->bind_param("s", $user_email);
    $u_stmt->execute();
    $user_data = $u_stmt->get_result()->fetch_assoc();
    $phone_number = $user_data['phone']; 

    // Fetch Payment Info for Receipt
    $p_stmt = $conn->prepare("SELECT * FROM registration_payments WHERE email = ? ORDER BY id DESC LIMIT 1");
    $p_stmt->bind_param("s", $user_email);
    $p_stmt->execute();
    $payment_data = $p_stmt->get_result()->fetch_assoc();

    // --- NEW: FILE UPLOAD LOGIC ---
    $target_dir = "uploads/receipts/"; // Ensure this folder exists and is writable
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES["receipt"]["name"], PATHINFO_EXTENSION);
    $p_id_for_file = $payment_data['payment_id'] ?? 'temp_' . time();
    $new_filename = "receipt_" . $p_id_for_file . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["receipt"]["tmp_name"], $target_file)) {
        // --- FIXED: UPDATE DATABASE LOGIC ---
        // We use the ID (primary key) to ensure we update the exact latest record for this user
        $record_id = $payment_data['id'];
        $update_db = $conn->prepare("UPDATE registration_payments SET receipt_uploaded = 1, receipt_file=?,status = 'Success' WHERE id = ?");
        $update_db->bind_param("si", $new_filename, $record_id);
        
        if(!$update_db->execute()){
            $error_msg = "Database Error: " . $conn->error;
        }
    } else {
        $error_msg = "File Upload Failed. Check folder permissions.";
    }

    // 2. Setup PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = ''; 
        $mail->Password = ''; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port = 587;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('rotraxproject@gmail.com', 'InnovaHub');
        $mail->addAddress($user_email, $user_data['fullname']);
        $mail->isHTML(true);
        $mail->Subject = 'InnovaHub - Payment Receipt';
        
        // Updated Email Body with WhatsApp Link
        $mail->Body    = "Dear " . $user_data['fullname'] . ",<br><br>
        Thank you for your payment. Please find your official payment receipt attached to this email.<br><br>
        <b>Join Our Official WhatsApp Group:</b><br>
        To stay updated with the latest announcements and event details, please join our WhatsApp group by clicking the link below:<br>
        <a href='https://chat.whatsapp.com/CgkNdMI0HVR1bzI00IvU73' style='display:inline-block; background-color:#25D366; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold; margin-top:10px;'>Join WhatsApp Group</a><br><br>
        Best Regards,<br>Team InnovaHub";

        // 3. Generate Payment Receipt PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Payment Receipt');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetFooterMargin(10);
        $pdf->SetMargins(15, 10, 15);
        $pdf->SetAutoPageBreak(FALSE, 10); 
        $pdf->AddPage();

        $html = '
        <div style="border: 2px solid #0d1b2a; padding: 15px;">
            <table cellpadding="4" style="width: 100%; text-align: center; border-bottom: 1px solid #0d1b2a;">
                <tr>
                    <td width="20%"><img src="http://localhost/rotrax/assets/images/Yashoda.jpg" width="70"></td>
                    <td width="60%">
                        <div style="text-align: center;">
                            <h3 style="margin: 0; font-size: 10pt;">Yashoda Shikshan Prasarak Mandal\'s</h3>
                            <h1 style="margin: 2px 0; font-size: 14pt; color: #0d1b2a;">YASHODA TECHNICAL CAMPUS, SATARA</h1>
                            <p style="margin: 0; font-size: 8pt;">Approved by AICTE, PCI & Govt. of Maharashtra</p>
                            <h4 style="margin: 2px 0; color: #0d1b2a; font-size: 10pt;">ROTARY CLUB OF SATARA</h4>
                        </div>
                    </td>
                    <td width="20%"><img src="http://localhost/rotrax/assets/images/logooo.jpg" width="70"></td>
                </tr>
            </table>

            <div style="text-align: center; background-color: #f8f9fa; padding: 8px;">
                <h1 style="color: #0d1b2a; margin-bottom: 2px; font-size: 16pt;">ROTAREX 2026</h1>
                <h3 style="color: #a855f7; margin-top: 0; font-size: 11pt;">OFFICIAL PAYMENT RECEIPT</h3>
            </div>
            
            <br><br>
            
            <table cellpadding="6" style="width: 100%; font-size: 10pt;">
                <tr>
                    <td width="30%" style="border-bottom: 1px solid #eee;"><b>Full Name</b></td>
                    <td width="70%" style="border-bottom: 1px solid #eee;">' . htmlspecialchars($user_data['fullname']) . '</td>
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #eee;"><b>Email Address</b></td>
                    <td style="border-bottom: 1px solid #eee;">' . htmlspecialchars($user_email) . '</td>
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #eee;"><b>Contact Number</b></td>
                    <td style="border-bottom: 1px solid #eee;">' . htmlspecialchars($user_data['phone']) . '</td>
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #eee;"><b>Payment ID</b></td>
                    <td style="border-bottom: 1px solid #eee; color: #0056b3;">' . htmlspecialchars($payment_data['payment_id']) . '</td>
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #eee;"><b>Transaction Amount</b></td>
                    <td style="border-bottom: 1px solid #eee;"><b>INR ' . htmlspecialchars($payment_data['amount']) . '.00</b></td>
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #eee;"><b>Payment Status</b></td>
                    <td style="border-bottom: 1px solid #eee; color: green;"><b>SUCCESSFUL</b></td>
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #eee;"><b>Transaction Date</b></td>
                    <td style="border-bottom: 1px solid #eee;">' . date("d-m-Y h:i A") . '</td>
                </tr>
            </table>

            <br><br><br>

            <table style="width: 100%; text-align: center; font-size: 9pt;">
                <tr>
                    <td>_______________________<br><b>Team Leader</b></td>
                    <td>_______________________<br><b>Project Guide</b></td>
                    <td>_______________________<br><b>Principal</b></td>
                </tr>
            </table>
            <br>
        </div>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->SetY(-20);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 10, 'Generated on: ' . date("d-m-Y h:i A"), 0, false, 'C', 0, '', 0, false, 'T', 'M');

        $pdf_content = $pdf->Output('', 'S');
        $mail->addStringAttachment($pdf_content, 'Payment_Receipt_' . ($payment_data['payment_id'] ?? 'Rec') . '.pdf');
        $mail->send();

        // --- UPDATE LOCK STATUS IN DATABASE ---
        $lock_stmt = $conn->prepare("UPDATE users SET is_locked = 1 WHERE email = ?");
        $lock_stmt->bind_param("s", $user_email);
        $lock_stmt->execute();

        // 4. SEND SMS
        if (!empty($phone_number) && strlen($phone_number) >= 10) {
            $api_key = "367E3CACE28824";
            $sender_id = "YSPMSR";
            $campaign_id = "14105";
            $route_id = "3";
            $pe_id = "1001539170000013821";
            $template_id = "1007927937667821652"; 

            $raw_message = "Your registration process for InnovaHub has been completed successfully.Thank you for joining us.– YSPM Satara";
            $sms_text = urlencode($raw_message);
            $url = "https://jskbulkmarketing.in/app/smsapi/index.php?key=$api_key&campaign=$campaign_id&routeid=$route_id&type=text&contacts=$phone_number&senderid=$sender_id&msg=$sms_text&template_id=$template_id&pe_id=$pe_id";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?registration=success");
        exit();

    } catch (Exception $e) {
        $error_msg = "Mail Error: " . $mail->ErrorInfo;
    }
}

// FETCH PAYMENT INFO FOR DISPLAY
$sql = "SELECT * FROM registration_payments WHERE email = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROTRAX 2026 | Step 5: Confirmation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root{
          --purple-main:#a855f7;
          --text-color:#000;
        }

        *{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

        body{
            min-height:100vh;
            background: url("assets/images/im.jpeg") center/cover no-repeat fixed;
            display:flex;
            justify-content:center;
            align-items:center;
            padding:20px 15px;
            color: var(--text-color);
        }

        .container{
            position: relative;
            background: rgba(255, 255, 255, 0.15); 
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            padding: 35px 25px;
            max-width: 550px;
            width: 100%;
            overflow: hidden;
        }

        .container::before{
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url("assets/images/im.jpeg") center/cover no-repeat;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
        }

        .container > * { position: relative; z-index: 1; }

        h2 { 
            text-align: center; 
            color: #000; 
            margin-bottom: 25px; 
            font-weight: 700;
            border-bottom: 2px solid var(--purple-main);
            padding-bottom: 10px;
            font-size: 22px;
        }

        .info-box { 
            background: rgba(255,255,255,0.3); 
            padding: 20px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            font-size: 14px; 
            border: 1px solid rgba(0,0,0,0.1);
        }
        .info-box p { margin-bottom: 10px; display: flex; justify-content: space-between; color: #000; flex-wrap: wrap; }
        .info-box p b { color: var(--purple-main); word-break: break-all; margin-left: auto; }

        .btn, .btn-upload, .submit-btn, .btn-final {
            display: block; width: 100%; padding: 14px; text-align: center;
            border-radius: 12px; font-weight: 700; text-decoration: none;
            margin-bottom: 15px; cursor: pointer; border: none; transition: 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            font-size: 14px;
        }

        .btn-download { background: var(--purple-main); color: #fff; }
        .btn-upload { background: #22c55e; color: #fff; display: block; }
        .btn-upload:disabled { background: #94a3b8; cursor: not-allowed; opacity: 0.7; transform: none !important; }
        .btn-secondary { background: rgba(0,0,0,0.6); color: #fff; }
        .btn-final { background: #6366f1; color: #fff; }
        
        .btn:hover, .btn-final:hover { transform: translateY(-3px); opacity: 0.9; }

        .upload-form { 
            border: 2px dashed var(--purple-main); 
            padding: 20px 15px; 
            border-radius: 16px; 
            text-align: center; 
            margin-bottom: 20px; 
            background: rgba(255,255,255,0.2);
        }
        
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 12px; color: #000; }
        
        input[type="file"] {
            width: 100%; padding: 10px; background: rgba(255,255,255,0.4); 
            border-radius: 8px; border: 1px solid rgba(0,0,0,0.1); color: #000; margin-bottom: 15px;
            font-size: 12px;
        }

        #preview-container { margin-top: 15px; display: none; text-align: center; }
        #image-preview {
            max-width: 100%; max-height: 200px;
            border-radius: 12px; border: 3px solid var(--purple-main);
            margin-bottom: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        #pdf-preview-text {
            background: var(--purple-main);
            color: #fff;
            padding: 15px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
            word-break: break-all;
        }

        .popup-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); display: none; 
            justify-content: center; align-items: center; z-index: 1000;
        }
        .popup-box {
            background: #fff; padding: 30px 20px; border-radius: 24px;
            text-align: center; max-width: 450px; width: 90%;
            animation: slideUp 0.4s ease; border: 1px solid var(--purple-main);
        }
        .popup-box h3 { color: var(--purple-main); margin-bottom: 15px; font-size: 20px; }
        .popup-box p { font-size: 14px; color: #333; margin-bottom: 10px; line-height: 1.5; }
        .lock-notice { color: #ef4444 !important; font-weight: 700; font-size: 13px; margin-bottom: 20px !important; }
        
        .agreement-container {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
            font-size: 13px;
            color: #444;
            cursor: pointer;
            text-align: left;
        }
        .agreement-container input { width: 18px; height: 18px; cursor: pointer; flex-shrink: 0; margin-top: 2px; }

        .popup-close, .popup-confirm {
            background: var(--purple-main); color: #fff;
            padding: 12px 30px; border-radius: 12px;
            cursor: pointer; font-weight: 700; border: none; transition: 0.3s;
            width: 100%; font-size: 15px;
        }
        .popup-close:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .final-confirmation {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 20px 0;
            text-align: left;
            font-size: 12px;
            color: #000;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.4);
            padding: 10px;
            border-radius: 8px;
            border: 1px solid var(--purple-main);
        }
        .final-confirmation input { margin-top: 3px; cursor: pointer; }
        
        .btn-flex-group { display: flex; gap: 10px; margin-top: 15px; }
        .btn-cancel { background: #64748b; color: white; width: 100%; padding: 12px; border-radius: 12px; font-weight: 700; border: none; cursor: pointer;}

        @media (max-width: 480px) {
            h2 { font-size: 18px; }
            .container { padding: 25px 15px; }
            .info-box { padding: 15px; }
            .btn, .btn-upload, .submit-btn, .btn-final { padding: 12px; font-size: 13px; }
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>

<div class="popup-overlay" id="successPopup">
    <div class="popup-box">
        <h3>Submission Successful! 🎉</h3>
        <p>Your payment receipt has been submitted. Please check your registered email for the official confirmation PDF.</p>
        <p class="lock-notice">Your registration process has been locked now you can't edit your form.</p>
        
        <label class="agreement-container">
            <input type="checkbox" id="agreeCheckbox" onchange="toggleButton()">
            <span>I agree that I have successfully completed the payment.</span>
        </label>

        <button class="popup-close" id="loginBtn" onclick="closePopup()" disabled>Go to Login</button>
    </div>
</div>

<div class="container" id="mainFormContainer">
    <h2>Final Confirmation</h2>

    <?php if (isset($error_msg)): ?>
        <p style="color: #ff4d4d; text-align: center; margin-bottom: 15px; font-weight:600;"><?php echo $error_msg; ?></p>
    <?php endif; ?>

    <?php if ($payment): ?>
        <div class="info-box">
            <p><span>Payment ID:</span> <b><?php echo htmlspecialchars($payment['payment_id']); ?></b></p>
            <p><span>Amount:</span> <b>₹<?php echo htmlspecialchars($payment['amount']); ?></b></p>
            <p><span>Status:</span> <b style="color:#22c55e;">COMPLETED</b></p>
        </div>

        <a href="generate_receipt.php" class="btn btn-download">Download Local PDF Receipt</a>

        <hr style="opacity:0.2; margin: 25px 0; border: 0.5px solid #000;">

        <form action="" method="POST" enctype="multipart/form-data" class="upload-form" id="actualRegistrationForm">
            <input type="hidden" name="payment_id" value="<?php echo $payment['payment_id']; ?>">
            <label>Upload screenshot or PDF of your transaction:</label>
            
            <input type="file" name="receipt" id="receipt-upload" accept="image/*,application/pdf" required>
            
            <div id="preview-container">
                <p style="font-size: 11px; margin-bottom: 8px; font-weight: 600;">File Preview:</p>
                <img id="image-preview" src="#" alt="Receipt Preview">
                <div id="pdf-preview-text" style="display:none;">📄 PDF Document Selected</div>
            </div>

            <a href="final_review.php" class="btn-secondary" style="display:block; text-decoration:none; padding:14px; border-radius:12px; text-align:center; font-weight:700; margin-bottom: 25px; font-size: 14px;">Review Registration</a>
            
            <label class="final-confirmation">
                <input type="checkbox" id="finalConfirm" onchange="toggleSubmitButton()">
                <span>Yes, I confirm that all my project details are correct and I do not wish to make any changes. I understand that once I click on submit, I will not be able to modify any project proposal details. Therefore, I am submitting my final project proposal.</span>
            </label>

            <button type="button" class="btn-upload" id="finalSubmitBtn" onclick="handleSubmit()" disabled>Submit & Send Email Receipt</button>
        </form>

    <?php else: ?>
        <p style="text-align:center; color:#ff4d4d; margin-bottom: 25px; font-weight:600;">No payment record found. Please complete the payment step first.</p>
        <a href="step4_payment.php" class="btn btn-download">Go to Payment</a>
        <a href="dashboard.php" class="btn-secondary" style="display:block; text-decoration:none; padding:14px; border-radius:12px; text-align:center; font-weight:700; font-size: 14px;">Back to Dashboard</a>
    <?php endif; ?>
</div>

<script>
    const fileInput = document.getElementById('receipt-upload');
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('image-preview');
    const pdfPreviewText = document.getElementById('pdf-preview-text');

    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            previewContainer.style.display = "block";
            if (file.type === "application/pdf") {
                previewImage.style.display = "none";
                pdfPreviewText.style.display = "block";
                pdfPreviewText.innerText = "📄 PDF Selected: " + file.name;
            } else {
                pdfPreviewText.style.display = "none";
                previewImage.style.display = "inline-block";
                const reader = new FileReader();
                reader.addEventListener('load', function() {
                    previewImage.setAttribute('src', this.result);
                });
                reader.readAsDataURL(file);
            }
        } else {
            previewContainer.style.display = "none";
            previewImage.setAttribute('src', '');
        }
    });

    function toggleButton() {
        const checkbox = document.getElementById('agreeCheckbox');
        const btn = document.getElementById('loginBtn');
        btn.disabled = !checkbox.checked;
    }

    function toggleSubmitButton() {
        const checkbox = document.getElementById('finalConfirm');
        const submitBtn = document.getElementById('finalSubmitBtn');
        submitBtn.disabled = !checkbox.checked;
    }

    function handleSubmit() {
        if(fileInput.files.length === 0) {
            alert("Please upload your transaction receipt first.");
            return;
        }
        document.getElementById('actualRegistrationForm').submit();
    }

    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('registration') === 'success') {
            document.getElementById('successPopup').style.display = 'flex';
            document.getElementById('mainFormContainer').style.display = 'none';
        }
    }

    function closePopup() {
        document.getElementById('successPopup').style.display = 'none';
        window.location.href = "login.php";
    }
</script>

</body>
</html>