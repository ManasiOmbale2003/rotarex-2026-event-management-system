<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Us | InnovaHub</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root{
  --glass-bg: rgba(255,255,255,0.08);
  --glass-border: rgba(87, 34, 92, 0.18);
  --purple: #381a57;
  --gold: #4b2354;
  --text: #713d79;
}

*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}

body{
  background: url('assets/images/img.jpeg') no-repeat center center fixed;
  background-size: cover;
  color: var(--text);
  min-height:100vh;
}

/* ================= NAVBAR ================= */
header.navbar{
  position:fixed;
  top:0;
  width:100%;
  z-index:1000;
  background: rgba(15,3,40,0.3);
  backdrop-filter: blur(18px) saturate(160%);
  -webkit-backdrop-filter: blur(18px) saturate(160%);
  border-bottom:1px solid var(--glass-border);
}

.nav-container{
  width:90%;
  max-width:1200px;
  margin:auto;
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:14px 0;
}

.brand{
  display:flex;
  align-items:center;
  gap:12px;
  font-size:22px;
  font-weight:700;
  color:var(--gold);
}

.brand img{
  height:60px; /* Increased from 42px */
  filter: drop-shadow(0 0 10px rgba(90, 35, 97, 0.6));
}

/* Mobile Menu Toggle */
.menu-toggle {
  display: none;
  font-size: 24px;
  color: #fff;
  cursor: pointer;
}

nav{
  display: flex;
  align-items: center;
}

nav a{
  margin-left:22px;
  text-decoration:none;
  color:#fff;
  font-size:14px;
  transition:0.3s;
}

nav a:hover{color:var(--gold);}

.nav-btn{
  background: rgba(107, 24, 202, 0.25);
  border:1px solid var(--purple);
  padding:8px 22px;
  border-radius:50px;
  font-weight:600;
}

/* ================= WATERMARK ================= */
.watermark{
  position:fixed;
  top:50%;
  left:50%;
  width:900px;
  height:900px;
  background:url('assets/images/logoo.png') no-repeat center;
  background-size:contain;
  opacity:0.12;
  pointer-events:none;
  transform:translate(-50%,-50%);
  animation: rotate 25s linear infinite;
  z-index:-1;
}

@keyframes rotate{
  from{transform:translate(-50%,-50%) rotateY(0deg);}
  to{transform:translate(-50%,-50%) rotateY(360deg);}
}

/* ================= CONTACT ================= */
.contact-section{
  padding:170px 20px 100px;
  display:flex;
  justify-content:center;
}

.contact-card{
  max-width:460px;
  width:100%;
  background: var(--glass-bg);
  backdrop-filter: blur(18px);
  border:1px solid var(--glass-border);
  border-radius:25px;
  padding:80px 30px 40px; /* Adjusted top padding for larger logos */
  text-align:center;
  box-shadow:0 20px 60px rgba(0,0,0,0.45);
  animation: fadeIn 1.4s ease;
  position: relative; 
}

/* ===== CONTACT CARD CORNER LOGOS ===== */
.logo-wrapper{
  position:absolute;
  top: 15px;
  left: 15px;
  right: 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 10;
}

.logo-wrapper .logo-img{
  height: 65px; /* Increased from 45px */
  width: auto;
  opacity: 1;
  transform: none;
  animation: none; 
}

.logo-wrapper .yashoda{
  width: 60px; /* Increased from 40px */
}

.logo-wrapper .rotrax-logo{
  width: 75px; /* Increased from 50px */
}

/* COLLEGE HEADER STYLES */
.college-header{
  text-align:center;
  margin-bottom:20px;
  margin-top: 10px;
}

.college-header h3{ font-size:10px; letter-spacing:0.5px; color:#333; }
.college-header h1{ font-size:16px; font-weight:800; color:#0b5ed7; margin: 2px 0; }
.college-header p{ font-size:10px; color:#333; }
.college-header h4{ font-size:11px; color:#7b2cbf; font-weight:700; }

.contact-card h2{
  color:var(--gold);
  margin-bottom:18px;
  font-size:2rem;
  margin-top: 10px;
}

.contact-item{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:12px;
  margin:14px 0;
  font-size:1.05rem;
  color:var(--purple);
  text-decoration:none;
  transition:0.3s;
}

.contact-item:hover{
  color:var(--gold);
}

.contact-item i{font-size:18px;}

/* ================= FOOTER ================= */
footer{
  background: rgba(13,4,21,0.7);
  backdrop-filter: blur(16px);
  border-top:1px solid var(--glass-border);
  padding:50px 30px 20px;
}

.footer-content{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
  gap:25px;
}

.footer-section h3{
  color:var(--gold);
  margin-bottom:15px;
}

.footer-section p,
.footer-section a{
  color:#ccc;
  font-size:0.95rem;
  text-decoration:none;
}

.footer-section a:hover{color:var(--purple);}

.footer-bottom{
  text-align:center;
  margin-top:25px;
  font-size:13px;
  color:#c084fc;
  border-top:1px solid var(--glass-border);
  padding-top:15px;
}

@keyframes fadeIn{
  from{opacity:0; transform:translateY(25px);}
  to{opacity:1; transform:translateY(0);}
}

/* ================= RESPONSIVE DESIGN ================= */
@media(max-width:768px){
  .menu-toggle {
    display: block;
  }

  nav {
    display: none;
    flex-direction: column;
    position: absolute;
    top: 70px;
    left: 0;
    width: 100%;
    background: rgba(15,3,40,0.95);
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid var(--glass-border);
  }

  nav.active {
    display: flex;
  }

  nav a {
    margin: 15px 0;
    margin-left: 0;
    font-size: 16px;
  }

  .contact-section {
    padding: 120px 15px 60px;
  }

  .contact-card {
    padding: 80px 20px 40px; /* Adjusted padding */
  }

  .college-header h1 {
    font-size: 14px;
  }

  .contact-card h2 {
    font-size: 1.5rem;
  }

  .watermark {
    width: 300px;
    height: 300px;
  }
}
</style>
</head>

<body>

<div class="watermark"></div>

<header class="navbar">
  <div class="nav-container">
    <div class="brand">
      <img src="assets/images/logooo.png">
      ROTAREX 
    </div>
    
    <div class="menu-toggle" id="mobile-menu">
      <i class="fas fa-bars"></i>
    </div>

    <nav id="nav-links">
      <a href="index.php">Home</a>
      <a href="about.php">About</a>
      <a href="feedback.php">Feedback</a>
      <a href="login.php" class="nav-btn">Sign In</a>
    </nav>
  </div>
</header>

<section class="contact-section">
  <div class="contact-card">

    <div class="logo-wrapper">
      <img src="assets/images/Yashoda.png" class="logo-img yashoda">
      <img src="assets/images/logooo.png" class="logo-img rotrax-logo">
    </div>

    <div class="college-header">
      <h3>Yashoda Shikshan Prasarak Mandal's</h3>
      <h1>YASHODA TECHNICAL CAMPUS, SATARA</h1>
      <p>Approved by AICTE, PCI & Govt. of Maharashtra</p>
      <h4>ROTARY CLUB OF SATARA</h4>
    </div>

    <h2>Contact Us</h2>
    <p style="color:#000;margin-bottom:20px;">Yashoda Technical Campus, Satara</p>

    <a href="tel:+919011232333" class="contact-item">
      <i class="fas fa-phone"></i> +91 90112 32333
    </a>

    <a href="mailto:rotraxproject@gmail.com" class="contact-item">
      <i class="fas fa-envelope"></i> rotraxproject@gmail.com
    </a>

    <a href="https://www.yes.edu.in" target="_blank" class="contact-item">
      <i class="fas fa-globe"></i> www.yes.edu.in
    </a>

    <div class="contact-item">
      <i class="fas fa-location-dot"></i> Maharashtra, India
    </div>

  </div>
</section>

<footer>
  <div class="footer-content">
    <div class="footer-section">
      <h3>InnovaHub</h3>
      <p>College-level project exhibition platform showcasing innovation and technical excellence.</p>
    </div>
    <div class="footer-section">
      <h3>Quick Links</h3>
      <a href="index.php">Home</a>
      <a href="about.php">About</a>
      <a href="contact.php">Contact</a>
      <a href="feedback.php">Feedback</a>
      <a href="login.php" class="nav-btn">Sign In</a>
    </div>
    <div class="footer-section">
      <h3>Contact</h3>
      <p>Yashoda Technical Campus, Satara</p>
      <p>rotraxproject@gmail.com</p>
      <p>+91 90112 32333</p>
    </div>
  </div>
  <div class="footer-bottom">© 2026 InnovaHub – Where Talent Meets Technology</div>
</footer>

<script>
  // Mobile Menu Toggle Script
  const menuToggle = document.getElementById('mobile-menu');
  const navLinks = document.getElementById('nav-links');

  menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
  });
</script>

</body>
</html>