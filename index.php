<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>InnovaHub</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

  <style>
    *{margin:0;padding:0;box-sizing:border-box;}

    body{
      font-family:'Poppins',sans-serif;
      background:#1a0b2e; 
      color:#fff;
      overflow-x:hidden;
    }

    /* --- NAVBAR FULLY TRANSPARENT --- */
    header{
      padding: 20px 40px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      position:fixed;
      top:0;
      width:100%;
      z-index:1000;
      transition: all 0.4s ease;
      /* Changed to full transparency */
      background: transparent; 
      backdrop-filter: none;
      -webkit-backdrop-filter: none;
    }

    /* Class added via JS on scroll - keeps text readable */
    header.scrolled {
      background: rgba(26, 11, 46, 0.95);
      padding: 10px 40px;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      box-shadow: 0 4px 30px rgba(0,0,0,0.5);
    }

    .brand-container{
      display:flex;
      align-items:center;
      gap:15px;
      text-decoration:none;
      flex-wrap:nowrap;
    }

    .brand-container img{
      height: 90px; 
      width: auto;
      filter:drop-shadow(0 0 12px rgba(212,175,55,0.5));
      transition: all 0.4s ease;
    }

    header.scrolled .brand-container img {
      height: 70px;
    }

    .brand-container:hover img {
      transform: scale(1.08);
    }

    .logo-text{
      font-size:1.8rem;
      font-weight:700;
      color:#ffd700;
      letter-spacing:1.5px;
      white-space:nowrap;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
    }

    nav a{
      margin-left:12px;
      padding:10px 18px;
      text-decoration:none;
      color:#fff;
      font-weight:600;
      font-size:.9rem;
      border-radius:8px;
      background: rgba(0, 0, 0, 0.4); 
      border: 1px solid rgba(255, 215, 0, 0.3);
      transition: all 0.3s ease;
      display: inline-block;
    }

    nav a:hover{
      color:#000;
      background: #ffd700;
      border-color: #ffd700;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
    }

    .menu-toggle{
      display:none;
      font-size:2rem;
      color:#ffd700;
      cursor:pointer;
      background: rgba(0,0,0,0.5);
      padding: 10px;
      border-radius: 5px;
    }

    .mobile-nav{
      position:fixed;
      top:0;
      right:-100%;
      width:280px;
      height:100vh;
      background:rgba(26, 11, 46, 0.98);
      backdrop-filter: blur(15px);
      padding:80px 30px;
      display:flex;
      flex-direction:column;
      gap:20px;
      transition:.4s cubic-bezier(0.4, 0, 0.2, 1);
      z-index:2000;
      box-shadow: -10px 0 30px rgba(0,0,0,0.8);
    }

    .mobile-nav .close-btn {
      position: absolute;
      top: 25px;
      right: 25px;
      font-size: 2rem;
      color: #ffd700;
      cursor: pointer;
    }

    .mobile-nav a{
      color:#fff;
      text-decoration:none;
      font-size:1.2rem;
      font-weight: 500;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      padding: 10px;
      background: rgba(0,0,0,0.3);
      border-radius: 5px;
    }

    .mobile-nav a:hover{
        background: #ffd700;
        color: #000;
        padding-left: 15px;
    }

    /* --- REGISTRATION NOTICE --- */
    .reg-notice {
      position: fixed;
      top: 130px; /* Static position */
      left: 0;
      width: 100%;
      background: transparent; /* Made transparent */
      color: #fff; /* Changed to white for visibility on transparent background */
      padding: 10px 0;
      text-align: center;
      z-index: 999;
      font-weight: 700;
      font-size: 1.1rem;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.9); /* Added shadow to make bold text pop */
    }

    .swiper-container{width:100%;height:100vh;}
    .swiper-slide{
      position:relative;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    .slide-image-layer,
    .slide-image-layer img{
      width:100%;
      height:100%;
      position:absolute;
      top:0;
      left:0;
      object-fit:cover;
      filter: brightness(0.8); 
    }

    .top-align-img img {
      object-fit: fill !important; 
      height: 100vh !important;
      width: 100vw !important;
    }

    .slide-overlay{
      position:absolute;
      width:100%;
      height:100%;
      background: linear-gradient(to bottom, rgba(0,0,0,0.15), rgba(0,0,0,0.5)); 
      z-index:2;
    }

    .hero-text{
      z-index:10;
      text-align:center;
      max-width:900px;
      padding:0 25px;
    }

    .hero-text h1{
      font-size:4.5rem;
      line-height: 1.1;
      margin-bottom:20px;
      background:linear-gradient(to right,#fff, #ffd700, #fff);
      background-size: 200% auto;
      -webkit-background-clip:text;
      -webkit-text-fill-color:transparent;
      animation: shine 3s linear infinite;
      text-shadow: 0 10px 20px rgba(0,0,0,0.5);
    }

    @keyframes shine {
      to { background-position: 200% center; }
    }

    .hero-text p{
      font-size:1.3rem;
      margin-bottom:40px;
      color: #ffffff;
      background: rgba(0,0,0,0.3);
      display: inline-block;
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: 500;
    }

    .cta-button{
      background:linear-gradient(45deg,#8b2ee3,#c89bed); 
      color:#fff;
      padding:18px 50px;
      border-radius:50px;
      border:none;
      cursor:pointer;
      font-weight:700;
      font-size: 1.1rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      transition: 0.3s;
      box-shadow: 0 5px 20px rgba(139, 46, 227, 0.4);
    }

    .cta-button:hover {
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 8px 25px rgba(139, 46, 227, 0.6);
    }

    footer{
      position:fixed;
      bottom:0;
      width:100%;
      text-align:center;
      padding:15px;
      background:rgba(26, 11, 46, 0.9);
      font-size:.85rem;
      z-index: 10;
      letter-spacing: 1px;
      border-top: 1px solid rgba(255,215,0,0.2);
    }

    @media(max-width:768px){
      header{ padding: 10px 15px; }
      header.scrolled { padding: 8px 15px; }
      nav{display:none;}
      .menu-toggle{display:block;}
      .brand-container img { height: 65px; } 
      header.scrolled .brand-container img { height: 55px; }
      .logo-text { font-size: 1.2rem; }
      .hero-text h1{font-size:2.5rem;}
      .hero-text p{font-size:1rem; margin-bottom: 30px;}
      .cta-button { padding: 15px 35px; font-size: 0.9rem; }
      .reg-notice { top: 85px; font-size: 0.9rem; }
    }
  </style>
</head>

<body>

<header id="mainHeader">
  <a href="#" class="brand-container">
    <img src="assets/images/logooo.png" alt="ROTRAX Logo">
    <div class="logo-text">InnovaHub</div>
  </a>

  <nav>
    <a href="index.php">Home</a>
    <a href="about.php">About Us</a>
    <a href="gallery.php">Gallery</a>
    <a href="feedback.php">Feedback</a>
    <a href="contact.php">Contact</a>
    <a href="login.php">Sign In</a>
  </nav>

  <i class="fas fa-bars menu-toggle" onclick="toggleMenu(true)"></i>
</header>

<div class="reg-notice">
  <marquee scrollamount="8"><strong>Note: Rotarex 2026 Registration date is extended up to 31st March 2026</strong></marquee>
</div>

<div class="mobile-nav" id="mobileNav">
  <i class="fas fa-times close-btn" onclick="toggleMenu(false)"></i>
  <a href="index.php" onclick="toggleMenu(false)">Home</a>
  <a href="about.php" onclick="toggleMenu(false)">About Us</a>
  <a href="gallery.php" onclick="toggleMenu(false)">Gallery</a>
  <a href="feedback.php" onclick="toggleMenu(false)">Feedback</a>
  <a href="contact.php" onclick="toggleMenu(false)">Contact</a>
  <a href="login.php" onclick="toggleMenu(false)">Sign In</a>
</div>

<div class="swiper-container">
  <div class="swiper-wrapper">
    <div class="swiper-slide">
      <div class="slide-image-layer top-align-img">
        <img src="assets/images/flyer.jpg" alt="Event Flyer">
      </div>
      <div class="slide-overlay"></div>
    </div>
    <div class="swiper-slide">
      <div class="slide-image-layer">
        <img src="assets/images/img.jpeg" alt="Background Image">
      </div>
      <div class="slide-overlay"></div>
      <div class="hero-text">
        <h1>Welcome to InnovaHub</h1>
        <p>InnovaHub empowers students to transform concepts into impactful projects across technology, sustainability, and innovation.</p>
        <br>
        <button class="cta-button" onclick="window.location.href='login.php'">Explore Innovation</button>
      </div>
    </div>
  </div>
</div>

<footer>
  © 2026 InnovaHub – Where Talent Meets Technology.
</footer>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
  const swiper = new Swiper('.swiper-container',{
    loop:true,
    effect:'fade',
    fadeEffect: { crossFade: true },
    autoplay:{
      delay:5000,
      disableOnInteraction: false,
    },
    speed:1500
  });

  window.addEventListener('scroll', function() {
    const header = document.getElementById('mainHeader');
    if (window.scrollY > 50) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });

  function toggleMenu(open) {
    const nav = document.getElementById("mobileNav");
    nav.style.right = open ? "0" : "-100%";
  }

  document.addEventListener("click", e => {
    const nav = document.getElementById("mobileNav");
    const toggle = document.querySelector(".menu-toggle");
    if (nav.style.right === "0px") {
        if (!nav.contains(e.target) && !toggle.contains(e.target)) {
            toggleMenu(false);
        }
    }
  });
</script>
</body>
</html>