<?php
// index.php - converted from index.html
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Faculty Pool - Thapar Institute of Engineering & Technology. Join our distinguished academic community and explore teaching opportunities.">
  <meta name="keywords" content="faculty, recruitment, teaching, research, Thapar University, TIET, academic positions">
  <meta name="author" content="Thapar Institute of Engineering & Technology">
  <meta name="robots" content="index, follow">
  <meta name="theme-color" content="#8B0000">
  <title>Faculty Pool - Thapar Institute of Engineering & Technology</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              600: '#dc2626',
              700: '#b91c1c',
              800: '#991b1b',
              900: '#7f1d1d',
            }
          },
          fontFamily: {
            'inter': ['Inter', 'sans-serif'],
            'poppins': ['Poppins', 'sans-serif'],
          }
        }
      }
    }
  </script>
</head>
<body class="font-poppins bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
  
  <div class="animated-bg">
    <div class="floating-circle circle-1"></div>
    <div class="floating-circle circle-2"></div>
    <div class="floating-circle circle-3"></div>
    <div class="floating-square square-1"></div>
    <div class="floating-square square-2"></div>
  </div>

  <nav class="creative-nav">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between items-center py-3">
        <div class="flex space-x-8">
          <a href="index.php" class="nav-item">HOME</a>
          <a href="departments.php" class="nav-item">PROGRAMS</a>
        </div>

        <div class="flex space-x-8">
          <a href="faculty-member.php" class="nav-item">FACULTY</a>
          <a href="student.php" class="nav-item active">STUDENT'S DOMAIN</a> 
        </div>
      </div>
    </div>
  </nav>

  <section class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Faculty Pool</h1>
      <p class="hero-subtitle">Join our distinguished academic community</p>
      <div class="hero-decoration">
        <div class="decoration-line"></div>
        <div class="decoration-dot"></div>
        <div class="decoration-line"></div>
      </div>
    </div>
    <div class="hero-particles">
      <div class="particle"></div>
      <div class="particle"></div>
      <div class="particle"></div>
      <div class="particle"></div>
      <div class="particle"></div>
    </div>
  </section>

  <section class="main-content">
    <div class="max-w-7xl mx-auto">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12" id="cardsView">
        <a href="departments.php">
          <div class="flex-card cursor-pointer" onclick="showPrograms(event)">
            <div class="text-4xl text-primary-600 mb-6"><i class="fas fa-graduation-cap"></i></div>
            <div class="text-3xl font-semibold text-primary-400 mb-4">Programs</div>
            <div class="text-1xl font-semibold mb-4">"Diverse programs driving innovation and excellence"</div>
          </div>
        </a>
        <a href="faculty-login.php">
        <div class="flex-card cursor-pointer" onclick="showPrograms(event)">
          <div class="text-4xl text-primary-600 mb-6"><i class="fas fa-user-tie"></i></div>
          <div class="text-3xl font-semibold text-primary-400 mb-4">Teacher's Domain</div>
          <div class="text-1xl font-semibold mb-4">"Sign in to manage classes, research, and appointments"</div>
        </div>
        </a>
        
        <a href="student.php">
          <div class="flex-card cursor-pointer">
            <div class="text-4xl text-primary-600 mb-6"><i class="fas fa-user"></i></div>
            <div class="text-3xl font-semibold text-primary-400 mb-4">Student's Domain</div>
            <div class="text-1xl font-semibold mb-4">"Explore faculty, apply to projects, and track progress"</div>
          </div>
        </a>
        
        <a href="research.php">
          <div class="flex-card cursor-pointer">
            <div class="text-4xl text-primary-600 mb-6"><i class="fas fa-flask"></i></div>
            <div class="text-3xl font-semibold text-primary-400 mb-4">Research/Project</div>
            <div class="text-1xl font-semibold mb-4">"Discover research opportunities and live projects"</div>
          </div>
        </a>
      </div>

      <div class="programs-list hidden" id="programsView" data-aos="fade-up">
        <h2 class="section-title">
          <i class="fas fa-building-columns"></i> Faculties & Departments
        </h2>
        <div class="departments-grid" id="departmentsContainer"></div>
        <div class="back-button-container" data-aos="fade-up" data-aos-delay="1000">
          <button class="creative-button" onclick="backToCards()">
            <i class="fas fa-arrow-left"></i> <span>Back to Main Menu</span>
          </button>
        </div>
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
    async function loadDepartments() {
      const container = document.getElementById('departmentsContainer');
      container.innerHTML = '<p class="text-center">Loading departments...</p>';
      try {
        // This function is still present but will not be triggered by the main cards area anymore,
        // as the "Programs" link redirects directly to 'department.php'.
        // It remains here to ensure existing functionality that might call this function is preserved.
        const res = await fetch('departments.php');
        const data = await res.json();
        container.innerHTML = '';

        if (!data.success || !data.departments.length) {
          container.innerHTML = '<p class="text-center text-muted">No departments found.</p>';
          return;
        }

        data.departments.forEach(dept => {
          const deptDiv = document.createElement('div');
          deptDiv.className = 'dept-card';
          deptDiv.innerHTML = `
            <div class="dept-icon"><i class="${dept.icon_class || 'fas fa-building'}"></i></div>
            <div class="dept-main">
              <span class="dept-name">${dept.name}</span>
              <p class="dept-desc text-sm text-slate-500 mt-2">${dept.description || ''}</p>
            </div>`;
          container.appendChild(deptDiv);
        });

      } catch (err) {
        console.error(err);
        container.innerHTML = '<p class="text-center text-danger">Error loading departments.</p>';
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      AOS && AOS.init && AOS.init({ duration: 800, easing: 'ease-in-out', once: true, offset: 100 });
      // loadDepartments(); // Removed direct call to align with navigation
    });
  </script>
</body>
</html>