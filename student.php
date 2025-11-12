<?php
// student.php - Student Portal Login Page with dynamic department fetching

// --- 1. Database Configuration ---
// IMPORTANT: Update these credentials to match your local MySQL setup (e.g., XAMPP/WAMP)
// define('DB_SERVER', 'sql110.infinityfree.com');
// define('DB_USERNAME', 'if0_40356779'); 
// define('DB_PASSWORD', 'Divyam2005');
// define('DB_NAME', 'if0_40356779_faculty_pool');
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', '');
define('DB_NAME', 'faculty_pool');
// --- 2. Database Connection and Fetching ---
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // In a production environment, you should log the error and display a generic message
    die("Database connection failed: " . $conn->connect_error);
}

// Array for Semester options (Roman numerals I to VIII)
$semesters = array(
    'I' => 'I Semester',
    'II' => 'II Semester',
    'III' => 'III Semester',
    'IV' => 'IV Semester',
    'V' => 'V Semester',
    'VI' => 'VI Semester',
    'VII' => 'VII Semester',
    'VIII' => 'VIII Semester'
);

// Query to fetch department names from the 'departments' table
$sql = "SELECT department_name FROM departments ORDER BY department_name ASC";
$result = $conn->query($sql);

$departments = [];
if ($result && $result->num_rows > 0) {
    // Fetch all department names into an array
    while($row = $result->fetch_assoc()) {
        $departments[] = $row['department_name'];
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Domain - Faculty Pool</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <script>
    // Re-added Tailwind config to ensure colors like 'primary-800' are defined
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
  
  <style>
    /* Global fixes to center content and remove scrollbars */
    html, body {
        height: 100%;       /* Ensure full viewport height */
        margin: 0;          /* Remove default browser margin */
        padding: 0;         /* Remove default browser padding */
        overflow-x: hidden; /* Ensure no horizontal scrollbar */
    }

    /* Use Flexbox on the body to center the content (form) vertically and horizontally */
    body {
        display: flex;
        justify-content: center; /* Center horizontally */
        align-items: center;     /* Center vertically */
        min-height: 100vh;       /* Use min-height to ensure centering on full viewport */
    }

    /* Styling for the form container */
    .student-form-container {
      max-width: 60%;
      width: 90%;
      padding: 63px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,0.4);
      box-shadow: 0 12px 36px rgba(0, 0, 0, 0.12);
      margin: 56px 0 20px; /* add top margin so the fixed back button never overlaps */
      position: relative; 
      transition: box-shadow 0.3s ease, transform 0.3s ease;
      min-height: 80vh; /* make the div taller vertically */
      display: flex;
      flex-direction: column;
      justify-content: center; /* center contents vertically within the taller card */
    }
    .student-form-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #8B0000, #A52A2A, #8B0000);
      border-top-left-radius: 12px;
      border-top-right-radius: 12px;
    }
    .student-form-container:hover {
      transform: translateY(-4px);
      box-shadow: 0 18px 48px rgba(0, 0, 0, 0.16);
    }
    
    /* Style adjustments for the title to match the screenshot */
    .student-form-container h2 {
      color: #B91C1C !important; 
      margin-bottom: 25px; 
    }
    
    /* Match the color and style for the form button */
    .btn.bg-red-700 {
      background-color: #B91C1C !important;
      border-color: #B91C1C !important;
      box-shadow: 0 6px 18px rgba(185, 28, 28, 0.25);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn.bg-red-700:hover {
      background-color: #991b1b !important;
      border-color: #991b1b !important;
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(185, 28, 28, 0.35);
    }
    .btn.bg-red-700:active {
      transform: translateY(0);
    }

    /* --- ADDED: Styles for the Back Button (from faculty-login.php) --- */
    .back-to-home {
      position: fixed;
      top: 0.75rem;
      left: 0.75rem;
      color: #8B0000;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.45rem;
      background: rgba(255,255,255,0.7);
      padding: 0.35rem 0.6rem;
      border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.6);
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      backdrop-filter: blur(6px);
      z-index: 1000;
    }

    .back-to-home:hover {
      color: #A52A2A;
      transform: translateX(-3px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    }
    .form-select {
      background: rgba(255,255,255, 0.95);
      border: 2px solid rgba(139, 0, 0, 0.2);
      border-radius: 12px;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    .form-select:focus {
      outline: none;
      border-color: #8B0000;
      box-shadow: 0 0 0 4px rgba(139, 0, 0, 0.18);
      background: #fff;
    }
    
    /* --- Responsive tweaks for student page (CSS-only) --- */
    @media (max-width: 992px) {
      .student-form-container {
        max-width: 80%;
        padding: 48px;
        min-height: 75vh;
        margin-top: 48px; /* keep safe space for fixed back button */
      }
      .student-form-container h2 { font-size: 1.75rem; }
    }

    @media (max-width: 768px) {
      .student-form-container {
        max-width: 92%;
        padding: 36px;
        border-radius: 12px;
        min-height: 70vh;
        margin-top: 44px; /* safe space on tablet */
      }
      .student-form-container::before { height: 3px; }
      .back-to-home { position: fixed; top: 0.75rem; left: 0.75rem; font-size: 0.9rem; padding: 0.32rem 0.55rem; }
      /* Prevent iOS/Android zoom on focus: keep inputs >= 16px */
      .form-select { padding: 0.65rem 0.9rem; font-size: 16px; }
      select, input, textarea { font-size: 16px; }
      .btn.bg-red-700 { padding: 0.65rem 0.9rem; font-size: 16px; }
    }

    @media (max-width: 480px) {
      .student-form-container {
        max-width: 96%;
        padding: 24px;
        min-height: 68vh;
        margin-top: 40px; /* safe space on mobile */
      }
      .student-form-container h2 { font-size: 1.5rem; }
      .back-to-home { gap: 0.35rem; padding: 0.28rem 0.5rem; font-size: 0.85rem; }
      /* Ensure minimum 16px font-size on small phones as well */
      .form-select, select, input, textarea { font-size: 16px; }
      .btn.bg-red-700 { font-size: 16px; }
    }
  </style>
</head>
<body class="font-poppins bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">

  <div class="student-form-container">
    
    <a href="index.php" class="back-to-home" data-aos="fade-right">
      <i class="fas fa-arrow-left"></i>
      <span>Back to Home</span>
    </a>
    
    <h2 class="text-3xl font-bold text-center">Student portal</h2>
    
    <form action="display_courses.php" method="GET">
      
      <div class="mb-4">
        <label for="semester" class="form-label font-semibold">Select Semester</label>
        <select class="form-select p-2 border border-gray-300 rounded-md w-full" id="semester" name="semester" required>
          <option value="" disabled selected>-- Choose your Semester --</option>
          <?php foreach ($semesters as $value => $label): ?>
            <option value="<?php echo htmlspecialchars($value); ?>"><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-4">
        <label for="department" class="form-label font-semibold">Select Department</label>
        <select class="form-select p-2 border border-gray-300 rounded-md w-full" id="department" name="department" required>
          <option value="" disabled selected>-- Choose your Department --</option>
          <?php if (!empty($departments)): ?>
            <?php foreach ($departments as $dept_name): ?>
              <option value="<?php echo htmlspecialchars($dept_name); ?>"><?php echo htmlspecialchars($dept_name); ?></option>
            <?php endforeach; ?>
          <?php else: ?>
            <option value="" disabled>No departments found in database</option>
          <?php endif; ?>
        </select>
      </div>
      
      <button type="submit" class="btn bg-red-700 hover:bg-red-800 text-white font-bold py-2 px-4 rounded w-full transition duration-300">
        <i class="fas fa-sign-in-alt me-2"></i> Submit
      </button>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    // ADDED: Initialize AOS
    AOS.init({
      duration: 800,
      easing: 'ease-in-out',
      once: true
    });
  </script>
</body>
</html>