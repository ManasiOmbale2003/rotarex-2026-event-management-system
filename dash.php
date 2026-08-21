<?php 
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
$user_email = $_SESSION['user'];

$conn = new mysqli("localhost","root","root","rotrax2026");
if($conn->connect_error) die("DB Error");

$stmt = $conn->prepare("SELECT fullname FROM users WHERE email=?");
$stmt->bind_param("s",$user_email);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$display_name = $result['fullname'] ?? $user_email;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ROTAREX 2026 Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
:root{
  --glass-bg: rgba(255,255,255,0.08);
  --glass-border: rgba(255,255,255,0.18);
  --purple-glow: #c084fc;
  --gold-main: #0b080e;
  --text-light: #0b0a0a;
}

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:'Poppins',sans-serif;
}

body{
  min-height:100vh;
  background: url("assets/images/im.jpeg") no-repeat center center fixed;
  background-size:cover;
  color:var(--text-light);
}

/* ===== WATERMARK ===== */
.watermark{
  position:fixed;
  top:50%;
  left:50%;
  width:700px;
  height:700px;
  background:url('assets/images/logoo.png') no-repeat center;
  background-size:contain;
  opacity:0.05;
  transform:translate(-50%,-50%);
  z-index:-1;
}

/* ---------- Sidebar ---------- */
.sidebar{
  position:fixed;
  top:0;
  left:0;
  width:240px;
  height:100%;
  background:rgba(15,3,40,0.35);
  backdrop-filter:blur(18px) saturate(160%);
  border-right:1px solid var(--glass-border);
  padding-top:25px;
  z-index:100;
}

.sidebar h2{
  text-align:center;
  margin-bottom:30px;
  color:var(--gold-main);
  font-weight:700;
  text-shadow:0 0 20px rgba(192,132,252,0.8);
}

.sidebar ul{
  list-style:none;
}

.sidebar ul li{
  margin:12px 15px;
  border-radius:14px;
}

.sidebar ul li a{
  display:block;
  padding:14px 18px;
  color:#fff;
  text-decoration:none;
  transition:0.3s;
}

.sidebar ul li a:hover{
  background:rgba(192,132,252,0.25);
  color:var(--gold-main);
}

/* ---------- Main Content ---------- */
.main-content{
  margin-left:240px;
  padding:40px;
}

.main-content h1{
  margin-bottom:35px;
  font-size:2.4rem;
  text-shadow:0 0 25px rgba(192,132,252,0.8);
}

/* ---------- Cards ---------- */
.cards{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:25px;
}

.card{
  background:var(--glass-bg);
  backdrop-filter:blur(16px);
  border:1px solid var(--glass-border);
  border-radius:25px;
  padding:35px 22px;
  text-align:center;
  box-shadow:0 15px 50px rgba(0,0,0,0.35);
  cursor:pointer;
  transition:0.35s;
}

.card:hover{
  transform:translateY(-8px);
  background:rgba(255,255,255,0.12);
  box-shadow:0 25px 65px rgba(192,132,252,0.6);
}

.card h3{
  color:var(--purple-glow);
  margin-bottom:12px;
}

.card p{
  font-size:15px;
  line-height:1.6;
}

/* ---------- Responsive ---------- */
@media(max-width:768px){
  .sidebar{width:70px;}
  .sidebar h2{display:none;}
  .sidebar ul li a{
    font-size:0.75rem;
    padding:12px 8px;
    text-align:center;
  }
  .main-content{
    margin-left:70px;
    padding:25px;
  }
}
</style>
</head>

<body>

<div class="watermark"></div>

<div class="sidebar">
  <h2>InnovaHub</h2>
  <ul>
    <li><a href="section.php">Stream & Section</a></li>
    <li><a href="step2.php">Team Information</a></li>
    <li><a href="step3_project_proposal.php">Project Proposal</a></li>
    <li><a href="step4_payment.php">Registration Fees</a></li>
    <li><a href="logout.php">Confirmation & Receipt</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</div>

<div class="main-content">
  <h1>Welcome, <?php echo htmlspecialchars($display_name); ?> 👋</h1>

  <div class="cards">
    <div class="card" onclick="location.href='section.php'">
      <h3>Stream & Section</h3>
      <p>Edit and view your stream details</p>
    </div>

    <div class="card" onclick="location.href='step2.php'">
      <h3>Team Information</h3>
      <p>Add and manage team members</p>
    </div>

    <div class="card" onclick="location.href='step3_project_proposal.php'">
      <h3>Project Proposal</h3>
      <p>Submit and track proposals</p>
    </div>

    <div class="card" onclick="location.href='step4_payment.php'">
      <h3>Registration Fees</h3>
      <p>Upload payment details</p>
    </div>

    <div class="card" onclick="location.href='logout.php'">
      <h3>Confirmation & Receipt</h3>
      <p>Download confirmation</p>
    </div>
  </div>
</div>

</body>
</html>