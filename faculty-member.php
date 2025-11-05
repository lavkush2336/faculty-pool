<?php
// faculty-member.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Faculty Member - Faculty Pool</title>

  <!-- Bootstrap & Fonts -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 50%, #f0f4f8 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 10px;
    }

    .faculty-card {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      max-width: 900px;
      transition: all 0.3s ease;
    }

    .faculty-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 25px 60px rgba(139, 0, 0, 0.15);
    }

    .faculty-image {
      flex: 1 1 300px;
      min-width: 280px;
      height: 100%;
      position: relative;
      /* background: #8B0000; */
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .faculty-image img {
      width: 90%;
      height: 100%;
      object-fit: cover;
      border-right: 5px solid #8B0000;
    }

    .faculty-info {
      flex: 2 1 400px;
      padding: 40px;
    }

    .faculty-info h2 {
      font-weight: 700;
      color: #8B0000;
      font-size: 1.8rem;
      margin-bottom: 5px;
    }

    .faculty-info h5 {
      font-weight: 500;
      color: #444;
      margin-bottom: 20px;
    }

    .faculty-info h6 {
      font-weight: 600;
      margin-bottom: 10px;
      color: #8B0000;
      text-transform: uppercase;
    }

    .faculty-info p {
      color: #333;
      margin-bottom: 10px;
      line-height: 1.6;
    }

    .faculty-info a {
      display: inline-block;
      color: #8B0000;
      font-weight: 600;
      text-decoration: none;
      margin-top: 10px;
      transition: color 0.3s ease;
    }

    .faculty-info a:hover {
      color: #A52A2A;
    }

    @media (max-width: 768px) {
      .faculty-card {
        flex-direction: column;
      }

      .faculty-image img {
        border-right: none;
        border-bottom: 5px solid #8B0000;
      }

      .faculty-info {
        padding: 25px;
      }
    }
  </style>
</head>
<body>
    <a href="book-appointment.php">
  <div class="faculty-card">
    <div class="faculty-image">
      <img src="images/ashima-singh.jpg" alt="Dr. Ashima Singh">
    </div>
    <div class="faculty-info">
      <h2>Dr. Ashima Singh</h2>
      <h5>Associate Professor</h5>

      <h6>Specialization</h6>
      <p>DevOps, Data Mining and Machine Intelligence, Software Engineering, Software Development</p>

      <h6>Email</h6>
      <p><i class="fas fa-envelope me-2"></i>ashima@thapar.edu</p>
    </div>
  </div>
  </a>
</body>
</html>
