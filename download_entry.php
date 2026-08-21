<?php
session_start();
if(!isset($_SESSION['user'])){
    die("Please login first!");
}

$folder = __DIR__ . "/generated_forms";
$pdf_file = $folder . "/Team_Information_Form.pdf";

if(!file_exists($pdf_file)){
    die("No PDF found. Please submit your team information first!");
}

// Force download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Team_Information_Form.pdf"');
header('Content-Length: ' . filesize($pdf_file));
readfile($pdf_file);
exit();
