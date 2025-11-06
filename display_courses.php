<?php
// display_courses.php - Displays courses based on student's selected semester and department

// --- 1. Configuration (MUST match student.php) ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'faculty_pool');

// --- 2. Database Connection ---
// Use mysqli for consistency with student.php
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function for safe HTML output
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$selected_semester = null;
$selected_department_name = null;
$department_id = null;
$courses = [];
$error_message = null;

// --- 3. Process Form Submission ---
// --- 3. Process Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "GET") { // CHANGED from POST to GET
    // Get submitted values
    // CHANGED from $_POST to $_GET
    $selected_department_name = trim($_GET['department'] ?? '');
    $selected_semester = trim($_GET['semester'] ?? '');
    
    if (empty($selected_department_name) || empty($selected_semester)) {
        $error_message = "Please select both Department and Semester.";
    } else {
        // --- 4. Fetch department_id from department_name using Prepared Statement ---
      $stmt_dept = $conn->prepare("SELECT id FROM departments WHERE department_name LIKE ?");
        
        if ($stmt_dept === false) {
             $error_message = "Database error: Could not prepare department query.";
        } else {
            $search_name = "%" . $selected_department_name . "%";
            $stmt_dept->bind_param("s", $search_name);
            $stmt_dept->execute();
            $result_dept = $stmt_dept->get_result();

            if ($row_dept = $result_dept->fetch_assoc()) {
                $department_id = $row_dept['id'];
                
                // --- 5. Fetch Courses based on department_id and semester using Prepared Statement ---
                $stmt_courses = $conn->prepare("
                    SELECT 
                        course_code, 
                        course_name
                    FROM courses 
                    WHERE department_id = ? AND semester = ?
                    ORDER BY course_code ASC
                ");

                if ($stmt_courses === false) {
                    $error_message = "Database error: Could not prepare courses query.";
                } else {
                    // Bind parameters (i for integer ID, s for string semester)
                    $stmt_courses->bind_param("is", $department_id, $selected_semester);
                    $stmt_courses->execute();
                    $result_courses = $stmt_courses->get_result();
                    
                    if ($result_courses->num_rows > 0) {
                        while ($row = $result_courses->fetch_assoc()) {
                            $courses[] = $row;
                        }
                    }
                    $stmt_courses->close();
                }

            } else {
                $error_message = "Error: Department not found in the database.";
            }
            $stmt_dept->close();
        }
    }
} else {
    // Redirect if accessed directly without GET data
    header('Location: student.php');
    exit;
}

$conn->close();

// Prepare title for the page
$page_title = e($selected_department_name) . " Courses for Semester " . e($selected_semester);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $page_title; ?> - Courses</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css"> 
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <style>
    /* Custom styling adapted from course.php for course cards */
    .course-semester-title {
        color: #8B0000;
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
        color: #374151;
        display: block; 
    }
    .dept-card .course-name {
        font-size: 0.9rem;
        color: #6b7280;
        margin-top: 0.25rem;
        display: block; 
    }
    .dept-card {
        cursor: pointer; 
        padding: 20px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        height: 100%; /* Important for grid consistency */
    }
    .dept-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .departments-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .course-link {
        text-decoration: none; 
        height: 100%;
    }
  </style>
</head>
<body class="font-poppins bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">

  <nav class="creative-nav">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between items-center py-3">
        <div class="flex space-x-8">
          <a href="index.php" class="nav-item">HOME</a>
          <a href="student.php" class="nav-item active">STUDENT PORTAL</a>
        </div>
      </div>
    </div>
  </nav>

  <section class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Course Catalog</h1>
      <p class="hero-subtitle"><?php echo $page_title; ?></p>
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
        <i class="fas fa-book-open-reader"></i> Available Courses
      </h2>

      <?php if (isset($error_message)): ?>
        <div class="alert alert-danger text-center" role="alert" data-aos="fade-in">
            <?php echo $error_message; ?>
        </div>
      <?php elseif (empty($courses)): ?>
        <div class="text-center text-muted py-8" data-aos="fade-in">
          No courses found for **<?php echo e($selected_department_name); ?>** in **Semester <?php echo e($selected_semester); ?>**.
        </div>
      <?php else: ?>
        <h3 class="course-semester-title" data-aos="fade-up">
            Semester <?php echo e($selected_semester); ?>
        </h3>
        
        <div class="departments-grid" id="coursesGrid">
            <?php foreach ($courses as $course): ?>
                <?php
                    // Construct a link to a hypothetical faculty page for this course
                    $urlCourseName = urlencode($course['course_name']);
                    $urlCourseCode = urlencode($course['course_code']);
                    
                    $facultyLink = "faculty.php?course_name={$urlCourseName}&course_code={$urlCourseCode}&department_id={$department_id}&semester={$selected_semester}";
                ?>
                <a href="<?php echo $facultyLink; ?>" class="course-link">
                    <div class="dept-card" data-aos="zoom-in" data-aos-delay="100">
                        <div class="dept-icon">
                            <i class="fas fa-book"></i> 
                        </div>
                        <div class="dept-main">
                            <span class="course-code"><?php echo e($course['course_code']); ?></span>
                            <span class="course-name"><?php echo e($course['course_name']); ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="mt-8 flex gap-4 items-center">
        <a href="student.php" class="creative-button" data-aos="fade-up">
          <i class="fas fa-arrow-left"></i><span>Back to Student Login</span>
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
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      AOS && AOS.init && AOS.init({ duration: 700, easing: 'ease-in-out', once: true, offset: 100 });
    });
  </script>
</body>
</html>