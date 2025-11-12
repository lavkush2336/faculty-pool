<?php
// research.php - Research/Project Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Research/Project - Faculty Pool - Thapar Institute of Engineering & Technology" />
  <meta name="theme-color" content="#8B0000" />
  <title>Research/Project - Faculty Pool</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body class="font-poppins bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">

  <nav class="creative-nav">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between items-center py-3">
        <div class="flex space-x-8">
          <a href="index.php" class="nav-item">HOME</a>
          <a href="department.php" class="nav-item">PROGRAMS</a>
        </div>
        <div class="flex space-x-8">
          <a href="faculty-member.php" class="nav-item">FACULTY</a>
          <a href="student.php" class="nav-item">STUDENT'S DOMAIN</a> 
        </div>
      </div>
    </div>
  </nav>

  <section class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Research/Project</h1>
      <p class="hero-subtitle">Explore research opportunities and project collaborations</p>
      <div class="hero-decoration">
        <div class="decoration-line"></div>
        <div class="decoration-dot"></div>
        <div class="decoration-line"></div>
      </div>
    </div>
  </section>

  <section class="main-content py-8">
    <div class="max-w-7xl mx-auto px-4">
      <div class="research-cards-container">
        <a href="student-login.php" class="research-sub-card-link">
          <div class="research-sub-card cursor-pointer" data-aos="fade-up" data-aos-delay="100">
            <div class="text-4xl text-primary-600 mb-4"><i class="fas fa-user-graduate"></i></div>
            <div class="text-3xl font-semibold text-primary-400 mb-3">I Am a Student</div>
            <p class="text-gray-600 text-center">Access research opportunities and project collaborations as a student</p>
          </div>
        </a>
        <a href="teacher-login.php" class="research-sub-card-link">
          <div class="research-sub-card cursor-pointer" data-aos="fade-up" data-aos-delay="200">
            <div class="text-4xl text-primary-600 mb-4"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="text-3xl font-semibold text-primary-400 mb-3">I am a Teacher</div>
            <p class="text-gray-600 text-center">Manage research projects and collaborate with students</p>
          </div>
        </a>
      </div>

      <div class="mt-8 flex gap-4 items-center justify-center">
        <a href="index.php" class="creative-button" data-aos="fade-up">
          <i class="fas fa-arrow-left"></i><span>Back to Home</span>
        </a>
      </div>
    </div>
  </section>

  <footer class="creative-footer">
    <div class="max-w-7xl mx-auto px-4 py-8 text-center">
      <p>&copy; <?php echo date('Y'); ?> Thapar Institute of Engineering & Technology. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="script.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      AOS && AOS.init && AOS.init({ duration: 700, easing: 'ease-in-out', once: true, offset: 100 });
    });
  </script>
</body>
</html>