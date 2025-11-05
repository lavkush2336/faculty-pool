<?php
// departments.php (Modified for redirection to course.php)

require_once 'db.php'; // must define $pdo (PDO connection)

// Safe HTML escape
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Fetch all top-level departments using the new column name 'department_name'
$stmt = $pdo->prepare("
    SELECT id, department_name 
    FROM departments 
    ORDER BY id ASC
");
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=5.0, user-scalable=yes" />
  <meta name="description" content="Faculty Pool - Thapar Institute of Engineering & Technology" />
  <meta name="theme-color" content="#8B0000" />
  <title>Departments - Faculty Pool</title>

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
          <a href="departments.php" class="nav-item active">DEPARTMENTS</a>
        </div>
      </div>
    </div>
  </nav>

  <section class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Departments</h1>
      <p class="hero-subtitle">Explore all academic departments at Thapar Institute</p>
      <div class="hero-decoration">
        <div class="decoration-line"></div>
        <div class="decoration-dot"></div>
        <div class="decoration-line"></div>
      </div>
    </div>
  </section>

  <section class="main-content py-8">
    <div class="max-w-7xl mx-auto px-4">
      <h2 class="section-title" data-aos="fade-up">
        <i class="fas fa-building-columns"></i> Faculties & Departments
      </h2>

      <div class="departments-grid" id="departmentsGrid">
        <?php if (empty($departments)): ?>
          <div class="text-center text-muted py-8">
            No departments found. Please add them in phpMyAdmin.
          </div>
        <?php else: ?>
          <?php foreach ($departments as $dept): ?>
            
            <a href="course.php?department_id=<?php echo e($dept['id']); ?>" class="dept-link">
              <div class="dept-card" data-aos="zoom-in" data-aos-delay="100">
                <div class="dept-icon">
                  <i class="fas fa-building"></i> 
                </div>
                <div class="dept-main">
                  <span class="dept-name"><?php echo e($dept['department_name']); ?></span>
                </div>
              </div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="mt-8 flex gap-4 items-center">
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