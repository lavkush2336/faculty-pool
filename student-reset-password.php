<?php
// student-reset-password.php
session_start();
require_once 'db.php';

$error_message = '';
$success_message = '';
$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($email && $password && $confirm_password) {
        if ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } else {
            // Password validation: minimum 8 characters, one special character, one capital letter, one number
            if (strlen($password) < 8) {
                $error_message = 'Password must be at least 8 characters long.';
            } else {
                $has_uppercase = preg_match('/[A-Z]/', $password);
                $has_number = preg_match('/[0-9]/', $password);
                $has_special = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password);
                
                if (!$has_uppercase) {
                    $error_message = 'Password must contain at least one capital letter.';
                } elseif (!$has_number) {
                    $error_message = 'Password must contain at least one number.';
                } elseif (!$has_special) {
                    $error_message = 'Password must contain at least one special character (!@#$%^&*(),.?":{}|<>)';
                } else {
                try {
                    // Check if student exists
                    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE email = :email");
                    $stmt->execute([':email' => $email]);
                    $student = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($student) {
                        // Update password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE students SET password = :password WHERE email = :email");
                        $stmt->execute([
                            ':password' => $hashed_password,
                            ':email' => $email
                        ]);
                        
                        $success_message = 'Password reset successfully! Redirecting to login page...';
                        // Redirect to login page after 2 seconds
                        header("refresh:2;url=student-login.php");
                    } else {
                        $error_message = 'Invalid email address.';
                    }
                } catch (PDOException $e) {
                    error_log("Reset Password PDO Error: " . $e->getMessage());
                    $error_message = 'System Error: Please try again later.';
                }
            }
        }
    } else {
        $error_message = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - Student</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

</head>
<body class="font-poppins">
  <div class="login-container">
    <a href="index.php" class="back-to-home">
      <i class="fas fa-arrow-left"></i> Back to Home
    </a>

    <div class="login-card">
      <div class="text-center mb-4">
        <div class="text-4xl mb-3" style="color: #8B0000;">
          <i class="fas fa-key"></i>
        </div>
        <h2 class="text-3xl font-bold mb-2" style="color: #8B0000;">Reset Password</h2>
        <p class="text-gray-600">Enter your new password</p>
      </div>

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

      <form method="POST" action="" onsubmit="return validateForm()">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        
        <div class="form-group">
          <label for="password" class="form-label">
            <i class="fas fa-lock"></i> New Password
          </label>
          <div class="password-input-group">
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-control" 
              placeholder="Enter your new password"
              required
              minlength="8"
              oninput="validatePassword()"
            >
            <span class="password-toggle" onclick="togglePassword('password')">
              <i class="fas fa-eye" id="toggleIconPassword"></i>
            </span>
          </div>
          <small class="text-gray-500 text-sm">
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
              placeholder="Confirm your new password"
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

        <button type="submit" class="login-btn">
          <i class="fas fa-save"></i> Save Password
        </button>
      </form>

      <div class="login-links">
        <p>Remember your password? <a href="student-login.php"><i class="fas fa-sign-in-alt"></i> Login</a></p>
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
