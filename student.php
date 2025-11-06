<?php
// student.php - Student Portal Login Page with dynamic department fetching

// --- 1. Database Configuration ---
// IMPORTANT: Update these credentials to match your local MySQL setup (e.g., XAMPP/WAMP)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Your MySQL username
define('DB_PASSWORD', '');     // Your MySQL password
define('DB_NAME', 'faculty_pool'); // Database name as seen in phpMyAdmin image

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
        /* You can remove the background-gradient class from the body tag if you want this style here instead: */
        /* background: linear-gradient(to bottom right, #f8fafc, #eff6ff, #eef2ff); */
    }

    /* Styling for the form container */
    .student-form-container {
     max-width: 60%;
    width: 90%;
    padding: 63px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin: 20px 0;
    }
    
    /* Style adjustments for the title to match the screenshot */
    .student-form-container h2 {
        color: #B91C1C !important; /* Matches the button and image color */
        margin-bottom: 25px; /* Increase spacing below the title */
    }
    
    /* Match the color and style for the form button */
    .btn.bg-red-700 {
        background-color: #B91C1C !important;
        border-color: #B91C1C !important;
    }
    .btn.bg-red-700:hover {
        background-color: #991b1b !important;
        border-color: #991b1b !important;
    }
  </style>
</head>
<body class="font-poppins bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">

  <div class="student-form-container">
    <h2 class="text-3xl font-bold text-center">Student portal</h2>
    
    <form action="display_courses.php" method="POST">
      
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
</body>
</html>