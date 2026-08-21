<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gallery - InnovaHub</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    *{margin:0;padding:0;box-sizing:border-box;}

    body {
      font-family: 'Poppins', sans-serif;
      background: #1a0b2e;
      color: #fff;
      overflow-x: hidden;
      min-height: 100vh;
    }

    /* --- NAVBAR (Consistent with Index) --- */
    header {
      padding: 10px 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1000;
      background: rgba(26, 11, 46, 0.95);
      backdrop-filter: blur(10px);
      box-shadow: 0 4px 30px rgba(0,0,0,0.5);
    }

    .brand-container {
      display: flex;
      align-items: center;
      gap: 15px;
      text-decoration: none;
    }

    .brand-container img {
      height: 70px;
      filter: drop-shadow(0 0 12px rgba(212,175,55,0.5));
    }

    .logo-text {
      font-size: 1.5rem;
      font-weight: 700;
      color: #ffd700;
      letter-spacing: 1.5px;
    }

    nav a {
      margin-left: 12px;
      padding: 10px 18px;
      text-decoration: none;
      color: #fff;
      font-weight: 600;
      font-size: .9rem;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 215, 0, 0.3);
      transition: 0.3s;
    }

    nav a:hover, nav a.active {
      background: #ffd700;
      color: #000;
    }

    /* --- GALLERY SECTION --- */
    .gallery-container {
      padding: 120px 5% 100px;
      max-width: 1400px;
      margin: 0 auto;
      text-align: center;
    }

    .gallery-container h1 {
      font-size: 3rem;
      margin-bottom: 10px;
      background: linear-gradient(to right, #fff, #ffd700, #fff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .gallery-container p {
      color: #ccc;
      margin-bottom: 40px;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
    }

    .grid-item {
      position: relative;
      overflow: hidden;
      border-radius: 15px;
      height: 250px;
      cursor: pointer;
      border: 2px solid rgba(255, 215, 0, 0.1);
      transition: 0.4s ease;
    }

    .grid-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: 0.5s ease;
      filter: brightness(0.9);
    }

    .grid-item:hover {
      border-color: #ffd700;
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.5);
    }

    .grid-item:hover img {
      transform: scale(1.1);
      filter: brightness(1.1);
    }

    /* --- LIGHTBOX LOGIC --- */
    #lightbox {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.9);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 3000;
      padding: 20px;
    }

    #lightbox img {
      max-width: 90%;
      max-height: 80vh;
      border: 3px solid #ffd700;
      border-radius: 10px;
    }

    #lightbox .close-lightbox {
      position: absolute;
      top: 30px;
      right: 40px;
      font-size: 3rem;
      color: #ffd700;
      cursor: pointer;
    }

    footer {
      text-align: center;
      padding: 20px;
      background: rgba(0,0,0,0.5);
      border-top: 1px solid rgba(255,215,0,0.2);
    }

    @media(max-width: 768px) {
      nav { display: none; }
      .gallery-container h1 { font-size: 2rem; }
    }
  </style>
</head>

<body>

<header>
  <a href="index.php" class="brand-container">
    <img src="assets/images/logooo.png" alt="ROTRAX Logo">
    <div class="logo-text">InnovaHub</div>
  </a>
  <nav>
    <a href="index.php">Home</a>
    <a href="about.php">About Us</a>
    <a href="gallery.php" class="active">Gallery</a>
    <a href="feedback.php">Feedback</a>
    <a href="contact.php">Contact</a>
    <a href="login.php">Register/Login</a>
  </nav>
</header>

<div class="gallery-container">
  <h1>Event Gallery</h1>
  <p>Capturing the innovation and energy of ROTAREX 2026</p>

  <div class="grid">
    <div class="grid-item" onclick="openLightbox(this)">
      <img src="assets/images/img.jpeg" alt="Innovation Hub">
    </div>
    <div class="grid-item" onclick="openLightbox(this)">
      <img src="assets/images/flyer.jpeg" alt="Event Flyer">
    </div>
    <div class="grid-item" onclick="openLightbox(this)">
      <img src="assets/images/img.jpeg" alt="Technology">
    </div>
  </div>
</div>

<div id="lightbox">
  <span class="close-lightbox" onclick="closeLightbox()">&times;</span>
  <img id="lightbox-img" src="" alt="Full View">
</div>

<footer>
  © 2026 InnovaHub – Where Talent Meets Technology.
</footer>

<script>
  function openLightbox(element) {
    const src = element.querySelector('img').src;
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').style.display = 'flex';
  }

  function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
  }

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === "Escape") closeLightbox();
  });
</script>

</body>
</html>