<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>InnovaHub | Downloads</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

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
        background: url("assets/images/im.jpeg") center/cover no-repeat fixed;
        display:flex;
        justify-content:center;
        align-items:center;
        padding:40px 15px;
        color: var(--text-color);
    }

    /* ===== GLASS CONTAINER WITH WATERMARK ===== */
    .container{
        position: relative;
        background: rgba(32, 2, 30, 0.15); 
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 32px rgba(0,0,0,0.25);
        padding: 40px 35px;
        max-width: 800px;
        width: 100%;
        overflow: hidden;
    }

    /* ===== WATERMARK ===== */
    .container::before{
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url("assets/images/im.jpeg") center/cover no-repeat;
        opacity: 0.05;
        pointer-events: none;
    }

    /* ===== HEADING ===== */
    h2{
        text-align:center;
        color:#000;
        margin-bottom:35px;
        position: relative;
        z-index: 1;
        font-weight: 700;
    }

    /* ===== DOWNLOAD CARDS ===== */
    .download-card{
        position: relative;
        z-index: 1;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 18px;
        padding: 25px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 2px solid #000000;
        transition: 0.3s ease;
    }

    .download-card:hover{
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.35);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .download-card h3{
        color: #000;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .download-card p{
        color: #333;
        font-size: 13px;
        font-style: italic;
    }

    /* ===== BUTTONS ===== */
    .download-btn{
        padding: 12px 25px;
        background: #000;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        border-radius: 12px;
        text-decoration: none;
        transition: 0.3s ease;
        display: inline-block;
        white-space: nowrap;
        margin-left: 15px;
    }

    .download-btn:hover{
        background: #333;
        transform: scale(1.05);
    }

    .back-btn{
        position: relative;
        z-index: 1;
        display: block;
        text-align: center;
        margin-top: 30px;
        color: #000;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        transition: 0.3s;
    }

    .back-btn:hover{
        text-decoration: underline;
        transform: translateX(-5px);
    }

    /* ===== RESPONSIVE ===== */
    @media(max-width:768px){
        .download-card{
            flex-direction: column;
            text-align: center;
        }
        .download-btn{
            margin-left: 0;
            margin-top: 15px;
            width: 100%;
        }
        h2{font-size:22px;}
    }
</style>
</head>

<body>

<div class="container">

<h2>Downloads</h2>

<div class="download-card">
    <div>
        <h3>Self Evaluation Form</h3>
        <p>Students must fill and submit this form during project evaluation.</p>
    </div>
    <a href="assets/Rotarex_2k26_Self Evaluation Report.docx" class="download-btn" download>
        <i class="fa fa-download"></i> Download
    </a>
</div>

<div class="download-card">
    <div>
        <h3>Principal Recommendation Letter</h3>
        <p>Official recommendation letter required from your institute.</p>
    </div>
    <a href="assets/Rotarex_2k26_Principal Recomendation form.docx" class="download-btn" download>
        <i class="fa fa-download"></i> Download
    </a>
</div>

<a href="uploads.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Uploads</a>

</div>

</body>
</html>