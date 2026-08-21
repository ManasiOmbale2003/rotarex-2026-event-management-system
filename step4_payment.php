<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

/* ===== DATABASE CONNECTION ===== */
require_once('config.php'); // Replaced manual connection with your config file

$user_email = $_SESSION['user'];

/* ===== NEW: PREVENT DUPLICATE PAYMENT/SUBMISSION ===== */
// If a receipt is already uploaded, redirect to the success view of step 5
$check_status = $conn->prepare("SELECT receipt_uploaded FROM registration_payments WHERE email = ? AND receipt_uploaded IS NOT NULL LIMIT 1");
$check_status->bind_param("s", $user_email);
$check_status->execute();
$status_result = $check_status->get_result();

if ($status_result->num_rows > 0) {
    header("Location: step5_confirmation.php?registration=success");
    exit();
}

/* ===== FETCH REGISTERED USER INFO ===== */
$stmt = $conn->prepare("SELECT fullname, unique_reg_id, phone FROM users WHERE email = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();

$reg_name = $user_info['fullname'] ?? '';
$reg_id = $user_info['unique_reg_id'] ?? 'N/A';
$reg_phone = $user_info['phone'] ?? '';

/* ===== FETCH CATEGORY FROM PREVIOUS SECTION (program column) ===== */
$stmt_cat = $conn->prepare("SELECT program FROM registrations WHERE user_email = ?");
$stmt_cat->bind_param("s", $user_email);
$stmt_cat->execute();
$res_cat = $stmt_cat->get_result();
$reg_data = $res_cat->fetch_assoc();
$fetched_category = $reg_data['program'] ?? '';

$amount = 1; // Fixed amount
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InnovaHub | Step 4 – Registration Fees</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <style>
        :root {
            --purple-main: #a855f7;
            --text-color: #000;
        }

        /* ===== RESET & BODY ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            min-height: 100vh;
            background: url("assets/images/img.jpeg") center/cover no-repeat fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text-color);
        }

        /* ===== GLASS CONTAINER ===== */
        .main-container { max-width: 650px; width: 100%; display: flex; flex-direction: column; gap: 25px; }

        .box {
            position: relative;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            padding: 30px 40px;
            overflow: hidden;
        }

        .box > * { position: relative; z-index: 1; }

        /* ===== NEW HEADER STYLES ===== */
        .header-logos {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .header-logos img {
            max-height: 85px; /* Increased general size */
            width: auto;
            object-fit: contain;
        }

        /* Specific size for ROTRAX logo as requested */
        .header-logos img.rotrax-logo {
            max-height: 110px; 
            transform: scale(1.1); /* Slight boost to stand out */
        }

        .college-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding-bottom: 15px;
        }

        .college-header h3 { font-size: 13px; font-weight: 500; text-transform: uppercase; }
        .college-header h1 { font-size: 22px; font-weight: 700; margin: 2px 0; color: #1a1a1a; }
        .college-header p { font-size: 11px; margin-bottom: 5px; }
        .college-header h4 { font-size: 18px; font-weight: 700; color: #2e2e2e; }

        /* ===== FORM CONTENT ===== */
        .form-box h2 {
            text-align: center;
            color: #000;
            margin-bottom: 25px;
            font-weight: 700;
            font-size: 20px;
        }

        label { font-size: 14px; font-weight: 600; color: #000; margin-bottom: 6px; display: block; }

        /* ===== INPUTS ===== */
        input, select {
            width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.4);
            background: rgba(255,255,255,0.4); color: #000; outline: none; font-size: 14px; margin-bottom: 15px;
        }

        .readonly-field { background: rgba(0,0,0,0.05); color: #333; cursor: not-allowed; border: 1px solid rgba(0,0,0,0.1); }

        /* ===== BUTTONS ===== */
        .submit-btn {
            width: 100%; padding: 15px; background: var(--purple-main); color: #fff; 
            font-size: 16px; font-weight: 700; border: none; border-radius: 16px; 
            cursor: pointer; transition: 0.3s ease; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            margin-top: 10px;
        }

        .submit-btn:hover { transform: translateY(-3px); background: #9333ea; }

        .footer-links { text-align: center; margin-top: 20px; }
        .footer-links a { color: #000; text-decoration: none; font-size: 13px; font-weight: 600; }

        /* ===== MOBILE RESPONSIVE ADAPTATIONS ===== */
        @media (max-width: 600px) {
            body { padding: 10px; }
            .box { padding: 20px 25px; border-radius: 18px; }
            .header-logos img { max-height: 60px; }
            .header-logos img.rotrax-logo { max-height: 80px; }
            .college-header h1 { font-size: 18px; }
            .college-header h4 { font-size: 16px; }
            .form-box h2 { font-size: 18px; }
            input, select, .submit-btn { font-size: 14px; padding: 12px; }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="box">
        <div class="form-box">
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
        </div>

        <div class="form-content">
            <h2>Step 4 – Registration Fees</h2>

            <label>Registration ID</label>
            <input type="text" value="<?php echo $reg_id; ?>" readonly class="readonly-field">

            <label>Full Name</label>
            <input type="text" value="<?php echo $reg_name; ?>" readonly class="readonly-field">

            <label>Category</label>
            <input type="text" id="category" value="<?php echo $fetched_category; ?>" readonly class="readonly-field">

            <label>Amount (INR)</label>
            <input type="text" value="₹ <?php echo $amount; ?>" readonly class="readonly-field">

            <label>Payment Method</label>
            <input type="text" value="Online Payment Gateway" readonly class="readonly-field">

            <button type="button" id="rzp-button1" class="submit-btn">Pay Online via Razorpay</button>

            <div class="footer-links">
                <a href="dashboard.php">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<script>
    var options = {
        "key": "rzp_live_SP5aM8NfbIgdeM", 
        "amount": "<?php echo $amount * 100; ?>", 
        "currency": "INR",
        "name": "InnovaHub",
        "description": "Registration Fee Payment",
        "image": "/rotrax/assets/images/logooo.png",
        "handler": function (response){
            const category = document.getElementById('category').value;
            window.location.href = "verify_payment.php?payment_id=" + response.razorpay_payment_id + 
                                   "&order_id=" + (response.razorpay_order_id || '') + 
                                   "&category=" + encodeURIComponent(category);
        },
        "prefill": {
            "name": "<?php echo $reg_name; ?>",
            "email": "<?php echo $user_email; ?>",
            "contact": "<?php echo $reg_phone; ?>"
        },
        "notes": {
            "full_name": "<?php echo $reg_name; ?>",
            "registration_id": "<?php echo $reg_id; ?>",
            "category": "<?php echo $fetched_category; ?>"
        },
        "theme": { "color": "#a855f7" }
    };

    var rzp1 = new Razorpay(options);

    document.getElementById('rzp-button1').onclick = function(e){
        const cat = document.getElementById('category').value;
        if(cat === "" || cat === "N/A"){
            alert("Category information is missing. Please contact support.");
            return false;
        }
        rzp1.open();
        e.preventDefault();
    }
</script>

</body>
</html>