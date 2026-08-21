<?php
include 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Fetch all users who haven't received the mail yet
$sql = "SELECT email, fullname FROM users WHERE evaluation_email_sent = 0";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $user_email = $row['email'];
        $display_name = $row['fullname'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username = ''; 
            $mail->Password = ''; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('rotraxproject@gmail.com', 'Team ROTAREX');
            $mail->addAddress($user_email);

            $mail->isHTML(false);
            $mail->Subject = "ROTAREX 2026: Self Evaluation Deadline Reminder";
            $mail->Body    = "Dear $display_name,\n\nThis is a reminder to submit your Self Evaluation before 25 March 2026.\n\nRegards,\nTeam ROTAREX";

            if($mail->send()) {
                $conn->query("UPDATE users SET evaluation_email_sent = 1 WHERE email = '$user_email'");
            }
        } catch (Exception $e) {
            continue; // Jar ekala mail gela nahi tr pudhchyala pathva
        }
    }
}
?>