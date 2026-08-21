<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
$user_email = $_SESSION['user'];

/* ===== DATABASE CONNECTION ===== */
require_once('config.php'); // Included external database configuration

// Define upload directories
$upload_dirs = [
    "bonafide" => "uploads/bonafide/",
    "principal_letter" => "uploads/principal_letter/"
];

// Create directories if not exists
foreach ($upload_dirs as $dir) {
    if(!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Handle file uploads
$success_msg = "";
$error_msg = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    foreach($upload_dirs as $key => $dir){
        if(isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK){
            $file_tmp = $_FILES[$key]['tmp_name'];
            $file_name = basename($_FILES[$key]['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['pdf','doc','docx','jpg','jpeg','png'];

            if(in_array($file_ext, $allowed)){
                $new_name = $user_email . "_" . $key . "." . $file_ext; // unique filename
                if(move_uploaded_file($file_tmp, $dir.$new_name)){
                    
                    /* ===== UPDATE DATABASE STATUS ===== */
                    $db_column = "";
                    if($key == "bonafide") $db_column = "bonafide_file"; 
                    if($key == "principal_letter") $db_column = "attachment_file"; 

                    if($db_column != ""){
                        // FIRST: Check if the user record exists
                        $check_stmt = $conn->prepare("SELECT user_email FROM project_proposals WHERE user_email = ?");
                        $check_stmt->bind_param("s", $user_email);
                        $check_stmt->execute();
                        $result = $check_stmt->get_result();

                        if($result->num_rows > 0){
                            // Row exists, use UPDATE
                            $stmt = $conn->prepare("UPDATE project_proposals SET $db_column = ? WHERE user_email = ?");
                            $stmt->bind_param("ss", $new_name, $user_email);
                        } else {
                            // Row does not exist, use INSERT
                            $stmt = $conn->prepare("INSERT INTO project_proposals (user_email, $db_column) VALUES (?, ?)");
                            $stmt->bind_param("ss", $user_email, $new_name);
                        }
                        
                        if($stmt->execute()){
                            $success_msg .= ucfirst(str_replace("_"," ",$key)) . " uploaded and saved to database successfully.<br>";
                        } else {
                            $error_msg .= "Database error for ".ucfirst(str_replace("_"," ",$key)).": " . $conn->error . "<br>";
                        }
                        $stmt->close();
                        $check_stmt->close();
                    }

                } else {
                    $error_msg .= "Failed to upload ".ucfirst(str_replace("_"," ",$key)).".<br>";
                }
            } else {
                $error_msg .= "Invalid file type for ".ucfirst(str_replace("_"," ",$key)).". Only PDF, DOC, DOCX, JPG, PNG allowed.<br>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>InnovaHub | Upload Documents</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
    :root{
      --purple-main:#a855f7;
      --text-color:#000;
    }

    /* ===== RESET & BODY ===== */
    *{
        margin:0;
        padding:0;
        box-sizing:border-box;
        font-family:'Poppins',sans-serif;
    }

    body{
        min-height:100vh;
        background: url("assets/images/img.jpeg") center/cover no-repeat fixed;
        display:flex;
        justify-content:center;
        align-items:center;
        padding:20px 15px;
        color: var(--text-color);
    }

    /* ===== GLASS CONTAINER ===== */
    .container{
        position: relative;
        background: rgba(255, 255, 255, 0.2); 
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 32px rgba(0,0,0,0.25);
        padding: 15px 35px 25px 35px; /* Minimal top padding */
        max-width: 800px;
        width: 100%;
        overflow: hidden;
    }

    /* ===== COMPACT HEADER ===== */
    .header-logos {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2px; /* Extremely tight spacing */
    }

    .header-logos img {
        max-height: 60px; /* Yashoda Logo size */
        width: auto;
    }

    /* Increased ROTRAX Logo Size */
    .header-logos img.rotrax-logo {
        max-height: 100px; 
        transform: translateY(5px); /* Fine-tune vertical alignment */
    }

    .college-header {
        text-align: center;
        margin-bottom: 12px;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        padding-bottom: 5px;
    }

    .college-header h3 { font-size: 11px; font-weight: 500; text-transform: uppercase; margin: 0; }
    .college-header h1 { font-size: 19px; font-weight: 700; margin: 0; color: #1a1a1a; line-height: 1.1; }
    .college-header p { font-size: 10px; margin: 1px 0; }
    .college-header h4 { font-size: 15px; font-weight: 700; color: #2e2e2e; margin: 0; }

    /* ===== STEP HEADING ===== */
    h2{
        text-align:center;
        color:#000;
        margin-bottom:15px;
        font-size: 19px;
    }

    /* ===== FORM ===== */
    form{
        display: grid;
        gap: 12px;
    }

    .label-header{
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2px;
    }

    label{
        font-size:13px;
        font-weight:600;
        color:#000;
    }

    .inline-download{
        font-size: 11px;
        color: #5b21b6;
        text-decoration: underline;
        font-weight: 700;
        padding: 1px 6px;
        background: rgba(255,255,255,0.4);
        border-radius: 5px;
    }

    .instructions{
        font-size: 11px;
        color: #444;
        margin-bottom: 3px;
        font-style: italic;
    }

    /* ===== INPUTS ===== */
    input[type="file"]{
        width:100%;
        padding:7px 12px;
        border-radius:10px;
        border:1px solid #000; 
        background: rgba(255,255,255,0.3);
        color:#000;
        font-size:13px;
    }

    /* ===== BUTTONS ===== */
    .btn-container{
        display:flex;
        justify-content:center;
        gap:15px;
        margin-top:5px;
        flex-wrap:wrap;
    }

    button, .download-link{
        padding: 10px 25px;
        background: #000;
        color:#fff;
        font-size:14px;
        font-weight:700;
        border:none; 
        border-radius:10px;
        cursor:pointer;
        transition:0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        min-width: 160px;
    }

    button:hover, .download-link:hover{
        background: #333;
        transform:translateY(-2px);
    }

    .success{color:#2d6a4f; font-size:12px; text-align: center; margin-bottom:8px;}
    .error{color:#a4161a; font-size:12px; text-align: center; margin-bottom:8px;}

    /* Mobile Responsive Optimizations */
    @media(max-width:768px){
        body { padding: 10px; }
        .container { padding: 15px 18px; border-radius: 16px; }
        .header-logos img { max-height: 45px; }
        .header-logos img.rotrax-logo { max-height: 75px; }
        .college-header h1 { font-size: 16px; }
        .college-header h4 { font-size: 13px; }
        h2 { font-size: 17px; }
        button, .download-link { width: 100%; min-width: auto; padding: 12px; }
        .btn-container { gap: 10px; }
        .label-header { flex-direction: row; align-items: center; }
        input[type="file"] { padding: 10px; }
    }
</style>
</head>
<body>

<div class="container">
    <div class="header-logos">
        <img src="/rotrax/assets/images/Yashoda.png" alt="Yashoda Logo">
        <img src="/rotrax/assets/images/logooo.png" alt="ROTRAX Logo" class="rotrax-logo">
    </div>

    <div class="college-header">
        <h3>Yashoda Shikshan Prasarak Mandal's</h3>
        <h1>YASHODA TECHNICAL CAMPUS, SATARA</h1>
        <p>Approved by AICTE, PCI & Govt. of Maharashtra</p>
        <h4>ROTARY CLUB OF SATARA</h4>
    </div>

    <h2>Step 3: Upload Documents</h2>

    <?php if($success_msg != ""): ?>
        <div class="success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if($error_msg != ""): ?>
        <div class="error"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div>
            <label>Bonafide Certificate</label>
            <div class="instructions">Upload merged file for each student (max 1 MB).</div>
            <input type="file" name="bonafide">
        </div>

        <div>
            <div class="label-header">
                <label>Principal Recommendation Letter *</label>
                <a href="assets/Rotarex_2k26_Principal Recomendation form.docx" class="inline-download" download>Template</a>
            </div>
            <div class="instructions">Upload signed recommendation letter (max 1 MB).</div>
            <input type="file" name="principal_letter" required>
        </div>

        <div class="btn-container">
            <button type="submit">Upload Documents</button>
        </div>
    </form>
</div>

</body>
</html>