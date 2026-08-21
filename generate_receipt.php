<?php
session_start();
require_once('tcpdf/tcpdf.php'); // Ensure this path is correct

/* ---------- DATABASE CONNECTION ---------- */
require_once('config.php'); // Replaced manual connection with config.php

// Set Timezone to ensure correct current time
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user'])) { exit("Access Denied"); }

$email = $_SESSION['user'];

// UPDATED QUERY: Uses registration_payments and JOINS users to get fullname and phone
$sql = "SELECT p.*, u.fullname, u.phone 
        FROM registration_payments p 
        JOIN users u ON p.email = u.email 
        WHERE p.email = ? 
        ORDER BY p.id DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) { exit("No payment record found."); }

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ROTAREX 2026');
$pdf->SetTitle('Payment Receipt - ' . $payment['payment_id']);

// Remove default header
$pdf->setPrintHeader(false);

// Enable Footer and set current date/time
$pdf->setPrintFooter(true);
$pdf->setFooterData(array(0,64,0), array(0,64,128));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetFooterMargin(10); 

// Set margins - adjusted to prevent overflow
$pdf->SetMargins(15, 10, 15); 
$pdf->SetAutoPageBreak(FALSE, 10); // Disabled AutoPageBreak to force single page
$pdf->AddPage();

// Design the Receipt Content
$html = '
<div style="border: 2px solid #0d1b2a; padding: 15px;">
    
    <table cellpadding="4" style="width: 100%; text-align: center; border-bottom: 1px solid #0d1b2a;">
        <tr>
            <td width="20%"><img src="/rotrax/assets/images/Yashoda.jpg" width="70"></td>
            <td width="60%">
                <div style="text-align: center;">
                    <h3 style="margin: 0; font-size: 10pt;">Yashoda Shikshan Prasarak Mandal\'s</h3>
                    <h1 style="margin: 2px 0; font-size: 14pt; color: #0d1b2a;">YASHODA TECHNICAL CAMPUS, SATARA</h1>
                    <p style="margin: 0; font-size: 8pt;">Approved by AICTE, PCI & Govt. of Maharashtra</p>
                    <h4 style="margin: 2px 0; color: #0d1b2a; font-size: 10pt;">ROTARY CLUB OF SATARA</h4>
                </div>
            </td>
            <td width="20%"><img src="/rotrax/assets/images/logooo.jpg" width="70"></td>
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
            <td width="70%" style="border-bottom: 1px solid #eee;">' . htmlspecialchars($payment['fullname']) . '</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #eee;"><b>Email Address</b></td>
            <td style="border-bottom: 1px solid #eee;">' . htmlspecialchars($payment['email']) . '</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #eee;"><b>Contact Number</b></td>
            <td style="border-bottom: 1px solid #eee;">' . htmlspecialchars($payment['phone']) . '</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #eee;"><b>Payment ID</b></td>
            <td style="border-bottom: 1px solid #eee; color: #0056b3;">' . htmlspecialchars($payment['payment_id']) . '</td>
        </tr>
       
        <tr>
            <td style="border-bottom: 1px solid #eee;"><b>Transaction Amount</b></td>
            <td style="border-bottom: 1px solid #eee;"><b>INR ' . htmlspecialchars($payment['amount']) . '.00</b></td>
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
            <td>
                _______________________<br>
                <b>Team Leader</b>
            </td>
            <td>
                _______________________<br>
                <b>Project Guide</b>
            </td>
            <td>
                _______________________<br>
                <b>Principal</b>
            </td>
        </tr>
    </table>

    <br>
    
</div>';

$pdf->writeHTML($html, true, false, true, false, '');

// Set footer position slightly higher (vrti) and text manually on the same page
$pdf->SetY(-20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Generated on: ' . date("d-m-Y h:i A"), 0, false, 'C', 0, '', 0, false, 'T', 'M');

// Close and output PDF
$pdf->Output('Receipt_' . $payment['payment_id'] . '.pdf', 'D'); // 'D' forces download
?>