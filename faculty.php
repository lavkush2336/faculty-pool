<?php
// faculty.php - Displays faculty members based on course name expertise

require_once 'db.php'; // must define $pdo (PDO connection)

// Safe HTML escape
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$courseName = filter_input(INPUT_GET, 'course_name', FILTER_SANITIZE_STRING);
$courseCode = filter_input(INPUT_GET, 'course_code', FILTER_SANITIZE_STRING);
$departmentId = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);

// Redirect if critical course information is missing
if (!$courseName || !$departmentId) {
    header('Location: department.php');
    exit;
}

// 1. Fetch Department Name for display
$deptName = "Department"; // Default name
$stmt = $pdo->prepare("SELECT department_name FROM departments WHERE id = :id");
$stmt->execute([':id' => $departmentId]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);
if ($department) {
    $deptName = e($department['department_name']);
}

$pageTitle = "Experts for " . e($courseCode);

// Prepare search query for the LIKE clause in SQL
// This aggressively searches for the course name within the expertise field, ignoring case and surrounding characters.
// We use simple replacement to allow for partial matches on complex course names.
$searchQuery = '%' . str_replace(['(', ')', '&', '/', '-'], '%', trim($courseName)) . '%';
$courseCodeSearchQuery = '%' . trim($courseCode) . '%';

// 2. Fetch Faculty by Expertise and Department
$stmt = $pdo->prepare("
    SELECT 
        F.faculty_id, F.first_name, F.last_name, F.email, F.expertise, F.Image, F.department
    FROM faculty F 
    WHERE 
        F.department_id = :department_id 
        AND (F.expertise LIKE :searchQuery OR F.CT LIKE :courseCodeSearch)
    ORDER BY F.first_name ASC
");
$stmt->execute([
    ':department_id' => $departmentId,
    ':searchQuery' => $searchQuery,
    ':courseCodeSearch' => $courseCodeSearchQuery
]);
$facultyList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Faculty Experts for <?php echo e($courseName); ?> - Faculty Pool" />
  <meta name="theme-color" content="#8B0000" />
  <title><?php echo $pageTitle; ?> - Faculty Pool</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Custom CSS (Assuming styles.css contains core nav/hero styles) -->
  <link rel="stylesheet" href="styles.css">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <!-- AOS -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <style>
    /* Styles adapted for the wide card design from faculty-member.php */
    .departments-grid {
        display: flex;
        flex-direction: column; /* Stack the wide faculty cards */
        gap: 2rem;
        margin-top: 2rem;
    }
    
    .faculty-card-link {
        text-decoration: none;
        color: inherit;
    }

    .faculty-card {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 1px solid rgba(139, 0, 0, 0.1);
    }

    .faculty-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(139, 0, 0, 0.15);
    }

    .faculty-image-container {
        flex: 0 0 180px; /* Fixed width for the image container */
        min-height: 100%;
        position: relative;
        background: #fdf2f2;
        display: flex;
        align-items: center;
        justify-content: center;
        border-right: 4px solid #8B0000;
    }

    .faculty-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .faculty-info {
        flex: 1 1 300px;
        padding: 20px 30px;
    }

    .faculty-info h2 {
        font-weight: 700;
        color: #8B0000;
        font-size: 1.6rem;
        margin-bottom: 3px;
    }
    
    /* MODIFIED: Reduced bottom margin for h5 and h6 to tighten spacing */
    .faculty-info h5 {
        font-weight: 500;
        color: #444;
        font-size: 1rem;
        margin-bottom: 5px; /* Reduced from 15px to 5px */
    }

    .faculty-info h6 {
        font-weight: 600;
        color: #8B0000;
        text-transform: uppercase;
        font-size: 0.9rem; /* Adjusted font size for title lines */
        margin-bottom: 3px; /* Reduced from 10px to 3px */
        margin-top: 10px;
    }

    /* Additional spacing for the specific info lines based on screenshot */
    .faculty-info .info-line {
        font-size: 1rem;
        color: #333;
        margin-bottom: 5px; /* Tight spacing between info lines */
        display: flex;
        align-items: center;
    }

    .expertise-container {
        margin-top: 10px;
    }

    .expertise-pill {
        display: inline-block;
        background: #fde4e4;
        color: #8B0000;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-right: 5px;
        margin-bottom: 5px;
        white-space: nowrap;
    }
    /* END MODIFIED */

    @media (max-width: 768px) {
        .faculty-card {
            flex-direction: column;
        }

        .faculty-image-container {
            flex: 0 0 auto;
            height: 200px;
            width: 100%;
            border-right: none;
            border-bottom: 4px solid #8B0000;
        }
        
        .faculty-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .faculty-info {
            padding: 20px;
        }
    }
  </style>
</head>
<body class="font-poppins bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">

  <!-- Navigation (Copied from course.php) -->
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

  <!-- Hero Section (Copied from course.php) -->
  <section class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Experts for: <?php echo e($courseCode); ?></h1>
      <p class="hero-subtitle">Faculty members specializing in **<?php echo e($courseName); ?>** from <?php echo $deptName; ?></p>
      <div class="hero-decoration">
        <div class="decoration-line"></div>
        <div class="decoration-dot"></div>
        <div class="decoration-line"></div>
      </div>
    </div>
  </section>

  <!-- Faculty List Section -->
  <section class="main-content py-8">
    <div class="max-w-7xl mx-auto px-4">
      <h2 class="section-title" data-aos="fade-up">
        <i class="fas fa-graduation-cap"></i> Found Experts
      </h2>

      <div class="departments-grid" id="facultyList">
        <?php if (empty($facultyList)): ?>
          <div class="text-center text-muted py-8">
            No faculty members were found in **<?php echo $deptName; ?>** with expertise matching "<?php echo e($courseName); ?>".
          </div>
        <?php else: ?>
          <?php foreach ($facultyList as $faculty): 
            $fullName = e($faculty['first_name']) . ' ' . e($faculty['last_name']);
            // Fallback image using placeholder service
            $imageUrl = $faculty['Image'] ?: 'https://placehold.co/180x250/8B0000/ffffff?text=' . urlencode('No%20Image');
          ?>
            <!-- Faculty Card (Adapted from faculty-member.php style) -->
            <a href="book-appointment.php?id=<?php echo e($faculty['faculty_id']); ?>" class="faculty-card-link" data-aos="fade-up" data-aos-delay="100">
                <div class="faculty-card">
                    <div class="faculty-image-container">
                        <img src="<?php echo $imageUrl; ?>" alt="<?php echo $fullName; ?>" class="faculty-image" onerror="this.onerror=null;this.src='https://placehold.co/180x250/CCCCCC/333333?text=Image%20Missing';">
                    </div>
                    <div class="faculty-info">
                        <h2><?php echo $fullName; ?></h2>
                        
                        <!-- Department -->
                        <div class="info-line">
                            <i class="fas fa-building me-2 text-primary-600"></i><?php echo e($faculty['department']); ?>
                        </div>

                        <!-- Email -->
                        <div class="info-line">
                            <i class="fas fa-envelope me-2 text-primary-600"></i><?php echo e($faculty['email']); ?>
                        </div>
                        
                        <!-- Expertise Title -->
                        <h6 class="mt-3"><i class="fas fa-laptop-code me-2"></i>Expertise</h6>
                        
                        <div class="expertise-container">
                            <!-- Display expertise as pills/tags -->
                            <?php 
                            $expertiseList = explode(',', $faculty['expertise']);
                            // Limit displayed expertise to a reasonable amount
                            $count = 0;
                            foreach($expertiseList as $exp) {
                                $exp = trim($exp);
                                if ($exp && $count < 5) {
                                    echo '<span class="expertise-pill">' . e($exp) . '</span>';
                                    $count++;
                                }
                            }
                            if (count($expertiseList) > 5) {
                                echo '<span class="expertise-pill">... and more</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="mt-8 flex gap-4 items-center">
        <a href="course.php?department_id=<?php echo $departmentId; ?>" class="creative-button" data-aos="fade-up">
          <i class="fas fa-arrow-left"></i><span>Back to Courses (<?php echo $deptName; ?>)</span>
        </a>
      </div>
    </div>
  </section>

  <!-- Footer (Copied from course.php) -->
  <footer class="creative-footer">
    <div class="max-w-7xl mx-auto px-4 py-8 text-center">
      <p>&copy; <?php echo date('Y'); ?> Thapar Institute of Engineering & Technology. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      AOS && AOS.init && AOS.init({ duration: 700, easing: 'ease-in-out', once: true, offset: 100 });
    });
  </script>
</body>
</html>