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
// The table structure was shown in the uploaded image.
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
  
  <style>
    /* Tailwind Configuration from index.php */
    .student-form-container {
      max-width: 600px;
      margin: 50px auto;
      padding: 30px;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>
<body class="font-poppins bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">

  <div class="student-form-container">
    <h2 class="text-3xl font-bold text-center mb-6 text-red-800">Student Portal Login</h2>
    
    <form action="student_login_process.php" method="POST">
      
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

      <div class="mb-4">
        <label for="student_id" class="form-label font-semibold">Roll Number / Student ID</label>
        <input type="text" class="form-control p-2 border border-gray-300 rounded-md w-full" id="student_id" name="student_id" placeholder="Enter your Roll Number" required>
      </div>

      <button type="submit" class="btn bg-red-700 hover:bg-red-800 text-white font-bold py-2 px-4 rounded w-full transition duration-300">
        <i class="fas fa-sign-in-alt me-2"></i> Submit
      </button>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>