<?php
include("config.php"); // Database connection included from config.php

if (isset($_POST['submit'])) {
    $name = $_POST['student_name'];
    $email = $_POST['student_email'];
    $course = $_POST['course'];
    $rating = $_POST['rating'];
    $feedback = $_POST['feedback'];

    $query = "INSERT INTO student_feedback
    (student_name, student_email, course, rating, feedback)
    VALUES ('$name','$email','$course','$rating','$feedback')";

    mysqli_query($conn, $query);
    echo "<script>alert('Feedback Submitted Successfully');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Feedback | InnovaHub</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}

body{
  color:#fff;
  background: url('assets/images/img.jpeg') no-repeat center center fixed;
  background-size: cover;
  overflow-x:hidden;
}

/* ===== WATERMARK ===== */
.watermark{
  position:fixed;
  top:50%;
  left:50%;
  width:900px;
  height:900px;
  background:url('assets/images/logoo.png') no-repeat center;
  background-size:contain;
  opacity:0.25;
  pointer-events:none;
  transform:translate(-50%,-50%);
  animation:rotate 20s linear infinite;
  z-index:-1;
}

@keyframes rotate{
  from{transform:translate(-50%,-50%) rotateY(0deg);}
  to{transform:translate(-50%,-50%) rotateY(360deg);}
}

/* ===== NAVBAR ===== */
header.navbar{
  position:fixed;
  top:0;
  width:100%;
  z-index:1000;
  background: rgba(15,3,40,0.3);
  backdrop-filter: blur(18px) saturate(160%);
  -webkit-backdrop-filter: blur(18px) saturate(160%);
  border-bottom:1px solid rgba(87, 34, 92, 0.18);
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
  color:#4b2354;
}

.brand img{
  height:42px;
  filter: drop-shadow(0 0 10px rgba(90, 35, 97, 0.6));
}

.menu-toggle {
  display: none;
  font-size: 24px;
  cursor: pointer;
}

nav a{
  margin-left:22px;
  text-decoration:none;
  color:#fff;
  font-size:14px;
  transition:0.3s;
}

nav a:hover{color:#4b2354;}

.nav-btn{
  background: rgba(107, 24, 202, 0.25);
  border:1px solid #381a57;
  padding:8px 22px;
  border-radius:50px;
  font-weight:600;
}

/* ===== FEEDBACK FORM ===== */
.feedback-box{
  width: 90%;
  max-width:480px;
  margin:160px auto 80px;
  padding:35px;
  background:rgba(255,255,255,0.08);
  backdrop-filter:blur(16px);
  border-radius:20px;
  box-shadow:0 15px 40px rgba(0,0,0,0.6);
  animation:fadeUp 1.2s ease;
}

@keyframes fadeUp{
  from{opacity:0;transform:translateY(40px);}
  to{opacity:1;transform:translateY(0);}
}

input,select,textarea{
  width:100%;
  margin-top:12px;
  padding:12px;
  border-radius:8px;
  border:none;
  background:rgba(255,255,255,0.85);
  color:#000;
}

input::placeholder,textarea::placeholder{color:#333;}

/* ===== STAR RATING ===== */
.star-rating{
  display:flex;
  justify-content:center;
  flex-direction:row-reverse;
  margin:18px 0;
}

.star-rating input{display:none;}

.star-rating label{
  font-size:38px;
  color:#475569;
  cursor:pointer;
  transition:0.3s;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label{ color:gold; transform:scale(1.2); }

/* ===== BUTTON ===== */
button{
    width:100%;
    padding:13px;
    margin-top:15px;
    border:none;
    border-radius:30px;
    font-weight:600;
    font-size:15px;
    background:linear-gradient(45deg,#6a0dad,#b57edc,#ffd700);
    color:#1a0b2e;
    cursor:pointer;
    transition:0.3s;
    box-shadow:0 6px 20px rgba(181,126,220,0.6);
}

/* ===== HEADER STYLING ===== */
.top-logos{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
}
.top-logos img{
    height:55px;
    object-fit:contain;
}
.college-header{
    text-align:center;
    margin-bottom:15px;
}
.college-header h3{ font-size:11px; letter-spacing:1px; color:#555; }
.college-header h1{ font-size:18px; font-weight:800; color:#0b5ed7; }
.college-header p{ font-size:11px; color:#333; }
.college-header h4{ font-size:12px; color:#7b2cbf; font-weight:700; }

/* ===== FAINT THEMED FOOTER ===== */
footer {
  background: rgba(15, 3, 40, 0.4); /* Reduced opacity for faint effect */
  backdrop-filter: blur(5px);
  color: rgba(0, 0, 0, 0.6); /* Faint black text */
  padding: 60px 40px 20px; 
  text-align: left; 
  margin-top: 80px;
  border-top: 1px solid rgba(75, 35, 84, 0.2); /* Fainter border */
}
.footer-content {
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
  gap: 40px;
  max-width: 1200px;
  margin: auto;
}
.footer-section h3 { 
  font-weight: 700; 
  margin-bottom: 20px; 
  color: rgba(75, 35, 84, 0.7); /* Faint purple branding */
  text-transform: uppercase;
  font-size: 1rem;
}
.footer-section p { color: rgba(0, 0, 0, 0.6); margin-bottom: 12px; font-size: 0.9rem; }
.footer-section a { color: rgba(0, 0, 0, 0.6); text-decoration: none; display: block; margin-bottom: 10px; font-size: 0.9rem; transition: 0.3s; }
.footer-section a:hover { color: #4b2354; }
.footer-bottom { 
  text-align: center; 
  padding-top: 25px; 
  font-size: 0.8rem; 
  color: rgba(0, 0, 0, 0.4); 
  border-top: 1px solid rgba(0,0,0,0.05); 
  margin-top: 40px; 
}

/* ===== MOBILE RESPONSIVE ===== */
@media(max-width:768px){
  .menu-toggle { display: block; color: white; }
  nav {
    display: none;
    position: absolute;
    top: 70px;
    left: 0;
    width: 100%;
    background: rgba(15,3,40,0.95);
    flex-direction: column;
    padding: 20px;
  }
  nav.active { display: flex; }
  nav a { margin: 10px 0; margin-left: 0; font-size: 16px; text-align: center; }
  .feedback-box { margin-top: 100px; padding: 25px; }
  .college-header h1 { font-size: 15px; }
  .top-logos img { height: 45px; }
}
</style>
</head>

<body>

<div class="watermark"></div>

<header class="navbar">
  <div class="nav-container">
    <div class="brand">
      <img src="assets/images/logooo.png">
      InnovaHub 
    </div>
    <div class="menu-toggle" id="mobile-menu">
      <i class="fas fa-bars"></i>
    </div>
    <nav id="nav-list">
      <a href="index.php">Home</a>
      <a href="about.php">About</a>
      <a href="#terms">Terms</a>
      <a href="feedback.php">Feedback</a>
      <a href="login.php" class="nav-btn">Sign In</a>
    </nav>
  </div>
</header>

<div class="feedback-box">
    <div class="top-logos">
      <img src="assets/images/Yashoda.png" alt="Yashoda Logo">
      <img src="assets/images/logooo.png" alt="ROTRAX Logo">
    </div>

    <div class="college-header">
      <h3>Yashoda Shikshan Prasarak Mandal's</h3>
      <h1>YASHODA TECHNICAL CAMPUS, SATARA</h1>
      <p>Approved by AICTE, PCI & Govt. of Maharashtra</p>
      <h4>ROTARY CLUB OF SATARA</h4>
    </div>

    <form method="post">
      <input type="text" name="student_name" placeholder="Student Name" required>
      <input type="email" name="student_email" placeholder="Student Email" required>

      <select name="course" required>
        <option value="">Select Course</option>
        <option>BCA</option>
        <option>BSc</option>
        <option>BTech</option>
        <option>MCA</option>
        <option>MSc</option>
      </select>

      <div class="star-rating">
        <input type="radio" id="5" name="rating" value="5"><label for="5">★</label>
        <input type="radio" id="4" name="rating" value="4"><label for="4">★</label>
        <input type="radio" id="3" name="rating" value="3"><label for="3">★</label>
        <input type="radio" id="2" name="rating" value="2"><label for="2">★</label>
        <input type="radio" id="1" name="rating" value="1" required><label for="1">★</label>
      </div>

      <textarea name="feedback" rows="4" placeholder="Write your feedback..." required></textarea>
      <button type="submit" name="submit">Submit Feedback</button>
    </form>
</div>

<footer>
  <div class="footer-content">
    <div class="footer-section">
      <h3>InnovaHub</h3>
      <p>The premier platform for showcasing engineering and technology projects. We bridge the gap between academic innovation and industry standards.</p>
    </div>
    <div class="footer-section">
      <h3>Quick Links</h3>
      <a href="index.php">Home</a>
      <a href="about.php">About Us</a>
      <a href="contact.php">Contact</a>
      <a href="feedback.php">User Feedback</a>
    </div>
    <div class="footer-section">
      <h3>Get In Touch</h3>
      <p><i class="fas fa-map-marker-alt"></i> YTC, Satara, MH</p>
      <p><i class="fas fa-envelope"></i> rotraxproject@gmail.com</p>
      <p><i class="fas fa-phone"></i> +91 90112 32333</p>
    </div>
  </div>
  <div class="footer-bottom">
    &copy; InnovaHub | Where Talent Meets Technology. All Rights Reserved.
  </div>
</footer>

<script>
    const mobileMenu = document.getElementById('mobile-menu');
    const navList = document.getElementById('nav-list');
    mobileMenu.addEventListener('click', () => {
        navList.classList.toggle('active');
    });
</script>

</body>
</html>