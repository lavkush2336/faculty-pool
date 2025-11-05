<?php
// course.php - Displays courses for a specific department ID

require_once 'db.php'; // must define $pdo (PDO connection)

// Safe HTML escape
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$departmentId = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);

// Redirect if department_id is missing or invalid
if (!$departmentId) {
    header('Location: departments.php');
    exit;
}

// 1. Fetch the Department Name
$deptName = "Courses"; // Default title
$stmt = $pdo->prepare("SELECT department_name FROM departments WHERE id = :id");
$stmt->execute([':id' => $departmentId]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if ($department) {
    $deptName = e($department['department_name']) . " Courses";
} else {
    // If department not found, redirect back
    header('Location: departments.php');
    exit;
}

// 2. Fetch all Courses for this Department ID
// The query maintains sorting by semester and then course_code (ascending)
$stmt = $pdo->prepare("
    SELECT 
        course_code, 
        course_name, 
        semester
    FROM courses 
    WHERE department_id = :department_id
    ORDER BY semester ASC, course_code ASC
");
$stmt->execute([':department_id' => $departmentId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// To group the courses by semester for better display
$courses_by_semester = [];
foreach ($courses as $course) {
    $courses_by_semester[$course['semester']][] = $course;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=5.0, user-scalable=yes" />
  <meta name="description" content="<?php echo e($deptName); ?> - Faculty Pool" />
  <meta name="theme-color" content="#8B0000" />
  <title><?php echo $deptName; ?> - Faculty Pool</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <style>
    /* Custom styling for the course cards to ensure consistency */
    .course-semester-title {
        color: #8B0000; /* Deep Red for the semester headers */
        font-size: 1.5rem;
        font-weight: 700;
        margin-top: 2rem;
        margin-bottom: 1rem;
        border-bottom: 3px solid rgba(139, 0, 0, 0.2);
        padding-bottom: 0.5rem;
    }
    .dept-card .course-code {
        font-size: 1.1rem;
        font-weight: 600;
        color: #374151; /* Darker text for the code */
    }
    .dept-card .course-name {
        font-size: 0.9rem;
        color: #6b7280; /* Muted text for the name */
        margin-top: 0.25rem;
    }
    /* Ensure the existing .dept-card flexbox layout is used for consistency */
    .dept-card {
        cursor: default; /* Change cursor since these are not links */
    }
  </style>
</head>
<body class="font-poppins bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">

  <nav class="creative-nav">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between items-center py-3">
        <div class="flex space-x-8">
          <a href="index.php" class="nav-item">HOME</a>
          <a href="department.php" class="nav-item active">DEPARTMENTS</a>
        </div>
      </div>
    </div>
  </nav>

  <section class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title"><?php echo $deptName; ?></h1>
      <p class="hero-subtitle">All academic courses offered by the department</p>
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
        <i class="fas fa-book-open-reader"></i> Course Catalog
      </h2>

      <?php if (empty($courses)): ?>
        <div class="text-center text-muted py-8">
          No courses found for this department (ID: <?php echo $departmentId; ?>).
        </div>
      <?php else: ?>
        <?php 
        // Iterate through semesters (keys) and courses (values)
        foreach ($courses_by_semester as $semester => $semester_courses): 
        ?>
            <h3 class="course-semester-title" data-aos="fade-up">
                Semester <?php echo e($semester); ?>
            </h3>

            <div class="departments-grid" id="coursesGrid">
                <?php foreach ($semester_courses as $course): ?>
                    <div class="dept-card" data-aos="zoom-in" data-aos-delay="100">
                        <div class="dept-icon">
                            <i class="fas fa-book"></i> 
                        </div>
                        <div class="dept-main">
                            <span class="course-code"><?php echo e($course['course_code']); ?></span>
                            <span class="course-name"><?php echo e($course['course_name']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="mt-8 flex gap-4 items-center">
        <a href="department.php" class="creative-button" data-aos="fade-up">
          <i class="fas fa-arrow-left"></i><span>Back to Departments</span>
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