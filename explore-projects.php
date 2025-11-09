<?php
// explore-projects.php - Page for exploring projects by Department and Expertise
session_start();
require_once 'db.php';

$error_message = '';
$success_message = '';

// Initialize form data with empty values for search/filter
$form_data = [
    'department' => '',
    'expertise' => ''
];

// Fetch departments for the dropdown
$departments = [];
try {
    $stmt = $pdo->prepare("SELECT department_name FROM departments ORDER BY department_name ASC");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching departments: " . $e->getMessage());
    $error_message = 'Could not load departments. Please try again later.';
}

// Handle form submission (for filtering projects, not updating profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['department'] = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING) ?? '';
    $form_data['expertise'] = filter_input(INPUT_POST, 'expertise', FILTER_SANITIZE_STRING) ?? '';

    // In a real application, you would use $form_data['department'] and $form_data['expertise']
    // to filter and display projects here. For now, we'll just acknowledge the selection.
    $success_message = 'Filters applied (Department: ' . htmlspecialchars($form_data['department']) . ', Expertise: ' . htmlspecialchars($form_data['expertise']) . '). Displaying projects...';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=5.0, user-scalable=yes">
  <meta name="description" content="Faculty Pool - Thapar Institute of Engineering & Technology. Join our distinguished academic community and explore teaching opportunities.">
  <meta name="keywords" content="faculty, recruitment, teaching, research, Thapar University, TIET, academic positions">
  <meta name="author" content="Thapar Institute of Engineering & Technology">
  <meta name="robots" content="index, follow">
  <meta name="theme-color" content="#8B0000">
  <title>Explore Projects - Faculty Pool</title>

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
          <a href="department.php" class="nav-item">PROGRAMS</a>
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
      <h1 class="hero-title">Explore Projects</h1>
      <p class="hero-subtitle"></p>
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

  <section class="main-content py-8">
    <div class="max-w-7xl mx-auto px-4">
      <?php if ($error_message): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>

      <?php if ($success_message): ?>
        <div class="success-message">
          <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
      <?php endif; ?>

      <div class="explore-card">
        <div class="text-center mb-4">
          
          
        </div>
        <form method="POST" action="">
          <div class="flex flex-col md:flex-row gap-4 mb-4">
            <div class="form-group w-full md:w-1/2">
              <label for="department" class="form-label">
                <i class="fas fa-building"></i> Department
              </label>
              <select id="department" name="department" class="form-control" required>
                <option value="">Select a department</option>
                <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo htmlspecialchars($dept); ?>"
                    <?php echo ($form_data['department'] === $dept) ? 'selected' : ''; ?>
                  >
                    <?php echo htmlspecialchars($dept); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group w-full md:w-1/2">
              <label for="expertise" class="form-label">
                <i class="fas fa-lightbulb"></i> Expertise
              </label>
              <input 
                type="text" 
                id="expertise" 
                name="expertise" 
                class="form-control" 
                placeholder="e.g., AI, Web Development, Data Science"
                value="<?php echo htmlspecialchars($form_data['expertise']); ?>"
              >
            </div>
          </div>

          <button type="submit" class="filter-btn">
            <i class="fas fa-search"></i> Search Projects
          </button>
        </form>
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
      AOS && AOS.init && AOS.init({ duration: 800, easing: 'ease-in-out', once: true, offset: 100 });
    });
  </script>
</body>
</html>
