<?php
// student-login.php
session_start();
require_once 'db.php'; // Assumes this file provides the PDO database connection ($pdo)

$login_error = '';
$studentEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($studentEmail && $password) {
        // Basic length check (the hashing/verification handles the complexity checks)
        if (strlen($password) < 8) {
            $login_error = 'Password must be at least 8 characters long.';
        } else {
            try {
                // 1. Fetch student data by email
                // Note: The table structure based on your image uses 'Name' and 'Student_ID'
                $stmt = $pdo->prepare("SELECT Student_ID, Name, Email, Password FROM student WHERE Email = :email");
                $stmt->execute([':email' => $studentEmail]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);

                // 2. Verify password using hashing
                if ($student && password_verify($password, $student['Password'])) {
                    // Success: Set session variables
                    $_SESSION['student_id'] = $student['Student_ID'];
                    $_SESSION['student_name'] = $student['Name'];
                    $_SESSION['student_logged_in'] = true;
                    header('Location: student-dashboard.php'); // Redirect to the dashboard
                    exit;
                } else {
                    $login_error = 'Invalid email or password. Please try again.';
                }
            } catch (PDOException $e) {
                error_log("Login PDO Error: " . $e->getMessage());
                $login_error = 'System Error: Please try again later.';
            }
        }
    } else {
        $login_error = 'Please enter both your email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Login - Faculty Pool</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    /* Custom styles to match the theme of the other files */
    .font-poppins { font-family: 'Poppins', sans-serif; }
    .login-container { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f8f9fa; }
    .login-card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
    .form-control { border-radius: 8px; border: 1px solid #ddd; padding: 10px 15px; width: 100%; }
    .form-control:focus { border-color: #8B0000; box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1); }
    .login-btn { background: #8B0000; color: #fff; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; width: 100%; transition: background-color 0.3s; margin-top: 15px; }
    .login-btn:hover { background: #A52A2A; }
    .password-input-group { position: relative; }
    .password-toggle { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999; }
    .error-message { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
    .success-message { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
    .login-links { display: flex; justify-content: space-between; margin-top: 20px; font-size: 0.9rem; }
    .login-links a { color: #8B0000; text-decoration: none; }
    .login-links a:hover { text-decoration: underline; }
    .back-to-home { position: absolute; top: 20px; left: 20px; color: #8B0000; text-decoration: none; font-weight: 500; }
    .back-to-home:hover { text-decoration: underline; }
  </style>
</head>
<body class="font-poppins">
  <div class="login-container">
    <a href="index.php" class="back-to-home">
      <i class="fas fa-arrow-left"></i> Back to Home
    </a>

    <div class="login-card">
      <div class="text-center mb-4">
        <div class="text-4xl mb-3" style="color: #8B0000;">
          <i class="fas fa-user-graduate"></i>
        </div>
        <h2 class="text-3xl font-bold mb-2" style="color: #8B0000;">Student Login</h2>
        <p class="text-gray-600">Sign in to access your account</p>
      </div>

      <?php if ($login_error): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($login_error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" onsubmit="return validateLoginForm()">
        <div class="form-group">
          <label for="email" class="form-label">
            <i class="fas fa-envelope"></i> Email
          </label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control" 
            placeholder="Enter your email"
            value="<?php echo htmlspecialchars($studentEmail); ?>"
            required
          >
        </div>

        <div class="form-group">
          <label for="password" class="form-label">
            <i class="fas fa-lock"></i> Password
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
            >
            <span class="password-toggle" onclick="togglePassword()">
              <i class="fas fa-eye" id="toggleIcon"></i>
            </span>
          </div>
        </div>

        <button type="submit" class="login-btn">
          <i class="fas fa-sign-in-alt"></i> Login
        </button>
      </form>

      <div class="login-links">
        <a href="student-signup.php">
          <i class="fas fa-user-plus"></i> Signup
        </a>
        <a href="student-forgot-password.php">
          Forgot Password? <i class="fas fa-key"></i>
        </a>
      </div>
    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.getElementById('toggleIcon');
      
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

    function validateLoginForm() {
      const password = document.getElementById('password').value;
      if (password.length < 8) {
        // NOTE: We should avoid 'alert()' but since the original JS used it, I kept the check.
        // The password length check is also handled on the server side.
        alert('Password must be at least 8 characters long.');
        return false;
      }
      return true;
    }
  </script>
</body>
</html>