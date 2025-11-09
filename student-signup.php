<?php
// student-signup.php
session_start();
require_once 'db.php';

$signup_error = '';
$signup_success = '';
$form_data = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'institute' => '',
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
    $signup_error = 'Could not load departments. Please try again later.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['name'] = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? '';
    $form_data['email'] = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
    $form_data['phone'] = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING) ?? '';
    $form_data['institute'] = filter_input(INPUT_POST, 'institute', FILTER_SANITIZE_STRING) ?? '';
    $form_data['department'] = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING) ?? '';
    $form_data['expertise'] = filter_input(INPUT_POST, 'expertise', FILTER_SANITIZE_STRING) ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($form_data['name'] && $form_data['email'] && $password && $form_data['phone'] && $form_data['institute']) {
        if ($password !== $confirm_password) {
            $signup_error = 'Passwords do not match.';
        } else {
            // Password validation: minimum 8 characters, one special character, one capital letter, one number
            if (strlen($password) < 8) {
                $signup_error = 'Password must be at least 8 characters long.';
            } else {
                $has_uppercase = preg_match('/[A-Z]/', $password);
                $has_number = preg_match('/[0-9]/', $password);
                $has_special = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password);
                
                if (!$has_uppercase) {
                    $signup_error = 'Password must contain at least one capital letter.';
                } elseif (!$has_number) {
                    $signup_error = 'Password must contain at least one number.';
                } elseif (!$has_special) {
                    $signup_error = 'Password must contain at least one special character (!@#$%^&*(),.?":{}|<>)';
                } else {
                    try {
                        // Check if email already exists
                        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE email = :email");
                        $stmt->execute([':email' => $form_data['email']]);
                        if ($stmt->fetch()) {
                            $signup_error = 'Email already registered. Please login instead.';
                        } else {
                            // Create students table if it doesn't exist
                            $pdo->exec("CREATE TABLE IF NOT EXISTS students (
                                student_id INT AUTO_INCREMENT PRIMARY KEY,
                                name VARCHAR(255) NOT NULL,
                                email VARCHAR(255) UNIQUE NOT NULL,
                                password VARCHAR(255) NOT NULL,
                                phone VARCHAR(20),
                                institute_name VARCHAR(255),
                                department_name VARCHAR(255),
                                expertise VARCHAR(255),
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                            )");

                            // Insert new student
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("INSERT INTO students (name, email, password, phone, institute_name, department_name, expertise) 
                                                  VALUES (:name, :email, :password, :phone, :institute, :department, :expertise)");
                            $stmt->execute([
                                ':name' => $form_data['name'],
                                ':email' => $form_data['email'],
                                ':password' => $hashed_password,
                                ':phone' => $form_data['phone'],
                                ':institute' => $form_data['institute'],
                                ':department' => $form_data['department'],
                                ':expertise' => $form_data['expertise']
                            ]);

                            $signup_success = 'Account created successfully! You can now login.';
                            // Clear form data on success
                            $form_data = ['name' => '', 'email' => '', 'phone' => '', 'institute' => '', 'department' => '', 'expertise' => ''];
                        }
                    } catch (PDOException $e) {
                        error_log("Signup PDO Error: " . $e->getMessage());
                        $signup_error = 'System Error: Please try again later.';
                    }
                }
            }
        }
    } else {
        $signup_error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Signup - Faculty Pool</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

</head>
<body class="font-poppins">
  <div class="signup-container">
    <a href="index.php" class="back-to-home">
      <i class="fas fa-arrow-left"></i> Back to Home
    </a>

    <div class="signup-card">
      <div class="text-center mb-4">
        <div class="text-4xl mb-3" style="color: #8B0000;">
          <i class="fas fa-user-plus"></i>
        </div>
        <h2 class="text-3xl font-bold mb-2" style="color: #8B0000;">Student Signup</h2>
        <p class="text-gray-600">Create your account to get started</p>
      </div>

      <?php if ($signup_error): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($signup_error); ?>
        </div>
      <?php endif; ?>

      <?php if ($signup_success): ?>
        <div class="success-message">
          <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($signup_success); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" onsubmit="return validateForm()">
        <div class="form-group">
          <label for="name" class="form-label">
            <i class="fas fa-user"></i> Name
          </label>
          <input 
            type="text" 
            id="name" 
            name="name" 
            class="form-control" 
            placeholder="Enter your full name"
            value="<?php echo htmlspecialchars($form_data['name']); ?>"
            required
          >
        </div>

        <div class="form-group">
          <label for="email" class="form-label">
            <i class="fas fa-envelope"></i> Email ID
          </label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control" 
            placeholder="Enter your email"
            value="<?php echo htmlspecialchars($form_data['email']); ?>"
            required
          >
        </div>

        <div class="form-group">
          <label for="password" class="form-label">
            <i class="fas fa-lock"></i> Create Password
          </label>
          <div class="password-input-group">
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-control" 
              placeholder="Enter your password"
              required
              minlength="8"
              oninput="validatePassword()"
            >
            <span class="password-toggle" onclick="togglePassword('password')">
              <i class="fas fa-eye" id="toggleIconPassword"></i>
            </span>
          </div>
          <small class="text-gray-500 text-sm" id="password-hint">
            Password must be at least 8 characters and contain: 1 capital letter, 1 number, 1 special character
          </small>
          <div id="password-requirements" class="text-sm mt-1"></div>
        </div>

        <div class="form-group">
          <label for="confirm_password" class="form-label">
            <i class="fas fa-lock"></i> Confirm Password
          </label>
          <div class="password-input-group">
            <input 
              type="password" 
              id="confirm_password" 
              name="confirm_password" 
              class="form-control" 
              placeholder="Confirm your password"
              required
              minlength="8"
              oninput="checkPasswordMatch()"
            >
            <span class="password-toggle" onclick="togglePassword('confirm_password')">
              <i class="fas fa-eye" id="toggleIconConfirm"></i>
            </span>
          </div>
          <div id="password-match" class="text-sm mt-1"></div>
        </div>

        <div class="form-group">
          <label for="phone" class="form-label">
            <i class="fas fa-phone"></i> Phone Number
          </label>
          <input 
            type="tel" 
            id="phone" 
            name="phone" 
            class="form-control" 
            placeholder="Enter your phone number"
            value="<?php echo htmlspecialchars($form_data['phone']); ?>"
            required
          >
        </div>

        <div class="form-group">
          <label for="institute" class="form-label">
            <i class="fas fa-university"></i> Institute Name
          </label>
          <input 
            type="text" 
            id="institute" 
            name="institute" 
            class="form-control" 
            placeholder="Enter your institute name"
            value="<?php echo htmlspecialchars($form_data['institute']); ?>"
            required
          >
        </div>

        <div class="form-group">
          <label for="department" class="form-label">
            <i class="fas fa-building"></i> Desired Department
          </label>
          <select id="department" name="department" class="form-control" required>
            <option value="">Select your department</option>
            <?php foreach ($departments as $dept): ?>
              <option value="<?php echo htmlspecialchars($dept); ?>"
                <?php echo ($form_data['department'] === $dept) ? 'selected' : ''; ?>
              >
                <?php echo htmlspecialchars($dept); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="expertise" class="form-label">
            <i class="fas fa-lightbulb"></i> Your Expertise
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

        <button type="submit" class="signup-btn">
          <i class="fas fa-user-plus"></i> Sign Up
        </button>
      </form>

      <div class="login-link">
        <p>Already have an account? <a href="student-login.php"><i class="fas fa-sign-in-alt"></i> Login</a></p>
      </div>
    </div>
  </div>

  <script>
    function togglePassword(fieldId) {
      const passwordInput = document.getElementById(fieldId);
      const toggleIcon = document.getElementById('toggleIcon' + (fieldId === 'password' ? 'Password' : 'Confirm'));
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }

    function validatePassword() {
      const password = document.getElementById('password').value;
      const requirementsDiv = document.getElementById('password-requirements');
      
      const hasMinLength = password.length >= 8;
      const hasUppercase = /[A-Z]/.test(password);
      const hasNumber = /[0-9]/.test(password);
      const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
      
      let html = '<div class="mt-2">';
      html += hasMinLength 
        ? '<div class="text-green-600"><i class="fas fa-check"></i> Minimum 8 characters</div>'
        : '<div class="text-red-600"><i class="fas fa-times"></i> Minimum 8 characters</div>';
      html += hasUppercase 
        ? '<div class="text-green-600"><i class="fas fa-check"></i> Capital letter</div>'
        : '<div class="text-red-600"><i class="fas fa-times"></i> Capital letter</div>';
      html += hasNumber 
        ? '<div class="text-green-600"><i class="fas fa-check"></i> Number</div>'
        : '<div class="text-red-600"><i class="fas fa-times"></i> Number</div>';
      html += hasSpecial 
        ? '<div class="text-green-600"><i class="fas fa-check"></i> Special character</div>'
        : '<div class="text-red-600"><i class="fas fa-times"></i> Special character</div>';
      html += '</div>';
      
      requirementsDiv.innerHTML = html;
    }

    function checkPasswordMatch() {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const matchDiv = document.getElementById('password-match');
      
      if (confirmPassword.length > 0) {
        if (password === confirmPassword) {
          matchDiv.innerHTML = '<div class="text-green-600"><i class="fas fa-check"></i> Passwords match</div>';
        } else {
          matchDiv.innerHTML = '<div class="text-red-600"><i class="fas fa-times"></i> Passwords do not match</div>';
        }
      } else {
        matchDiv.innerHTML = '';
      }
    }

    function validateForm() {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const department = document.getElementById('department').value;
      const expertise = document.getElementById('expertise').value;

      // Basic validation for department (required)
      if (department === '') {
        alert('Please select your department.');
        return false;
      }
      // Expertise is optional, so no direct validation for emptiness here
      
      if (password.length < 8) {
        alert('Password must be at least 8 characters long.');
        return false;
      }
      
      const hasUppercase = /[A-Z]/.test(password);
      const hasNumber = /[0-9]/.test(password);
      const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
      
      if (!hasUppercase) {
        alert('Password must contain at least one capital letter.');
        return false;
      }
      
      if (!hasNumber) {
        alert('Password must contain at least one number.');
        return false;
      }
      
      if (!hasSpecial) {
        alert('Password must contain at least one special character.');
        return false;
      }
      
      if (password !== confirmPassword) {
        alert('Passwords do not match.');
        return false;
      }
      
      return true;
    }
  </script>
</body>
</html>

