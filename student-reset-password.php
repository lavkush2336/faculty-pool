<?php
// student-reset-password.php (Step 2: OTP Table Validation & Password Reset)
session_start();
require_once 'db.php';

$error_message = '';
$success_message = '';

// Get parameters from the URL
$email = $_GET['email'] ?? $_POST['email'] ?? '';
$otp_from_url = $_GET['otp'] ?? $_POST['otp'] ?? ''; 
$password_reset_allowed = false;

// --- Initial Database OTP and Expiry Check (Run only once on page load) ---
if ($email && $otp_from_url && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // 1. Check token validity in the dedicated 'student_otp' table
        $stmt = $pdo->prepare("SELECT otp, expiry FROM student_otp WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($token_data) {
            // Check OTP match (cast to string for reliable comparison)
            if ((string)$token_data['otp'] === $otp_from_url) {
                // Check expiry time
                if (time() < $token_data['expiry']) {
                    $password_reset_allowed = true; // Token is valid
                } else {
                    $error_message = 'Reset link has expired.';
                    // Clear the expired token from the OTP table
                    $pdo->prepare("DELETE FROM student_otp WHERE email = :email")->execute([':email' => $email]);
                }
            } else {
                $error_message = 'Invalid reset link/OTP.';
            }
        } else {
            $error_message = 'Invalid or used reset link. Please request a new one.';
        }
    } catch (PDOException $e) {
        error_log("Initial Token Check Error: " . $e->getMessage());
        $error_message = 'System Error during token validation. Please try again.';
    }
}
// --------------------------------------------------------------------------


// --- Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_post = $_POST['email'] ?? '';
    $otp_post = $_POST['otp'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Check token validity one more time before processing password
    try {
        $stmt = $pdo->prepare("SELECT otp, expiry FROM student_otp WHERE email = :email");
        $stmt->execute([':email' => $email_post]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$token_data || (string)$token_data['otp'] !== $otp_post || time() > $token_data['expiry']) {
            $error_message = 'Reset token expired or invalid. Please request a new link.';
            // Clear token
            $pdo->prepare("DELETE FROM student_otp WHERE email = :email")->execute([':email' => $email_post]);
        }
        // Token is valid, proceed with password validation
        elseif (empty($password) || empty($confirm_password)) {
            $error_message = 'Please fill in all fields.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } else {
            // --- Complex Password Validation ---
            $password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

            if (!preg_match($password_regex, $password)) {
                $error_message = 'Password must be at least 8 characters long and include one uppercase letter, one lowercase letter, one number, and one symbol.';
            } else {
                // --- ALL VALIDATIONS PASSED: Proceed to Database Update ---
                
                // 1. Update password in the main 'students' table
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE student SET password = :password WHERE email = :email");
                $stmt->execute([
                    ':password' => $hashed_password,
                    ':email' => $email_post
                ]);
                
                // 2. Clear token from the dedicated 'student_otp' table
                $pdo->prepare("DELETE FROM student_otp WHERE email = :email")->execute([':email' => $email_post]);
                
                $success_message = 'Password reset successfully! Redirecting to login page...';
                
                // Redirect to login page after 2 seconds
                header("refresh:2;url=student-login.php?status=pwreset");
                exit;
            }
        }
    } catch (PDOException $e) {
        error_log("Reset Password PDO Error: " . $e->getMessage());
        $error_message = 'System Error: Could not finalize password update. Please try again.';
    }

    // If POST failed, allow the form to remain visible if the token was valid for post
    if ($error_message) {
        $password_reset_allowed = true;
    }
}
// Use the email for display
$display_email = $email;
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
    <style>
    /* ... (CSS styles omitted for brevity) ... */
    .login-container { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; background-color: #f7f7f7; padding: 20px; }
    .login-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; }
    .back-to-home { position: absolute; top: 20px; left: 20px; color: #8B0000; text-decoration: none; font-weight: 500; }
    .form-group { margin-bottom: 20px; }
    .form-label { color: #8B0000; font-weight: 600; margin-bottom: 8px; display: block; }
    .form-control { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; }
    .form-control:focus { outline: none; border-color: #8B0000; box-shadow: 0 0 0 1px rgba(139, 0, 0, 0.5); }
    .login-btn { width: 100%; background-color: #8B0000; color: white; border: none; border-radius: 8px; padding: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background-color 0.3s; }
    .login-btn:hover { background-color: #A52A2A; }
    .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 10px; margin-bottom: 20px; }
    .success-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 10px; margin-bottom: 20px; }
    .login-links a { color: #8B0000; text-decoration: none; font-weight: 600; }
    .login-links a:hover { text-decoration: underline; }
    .password-input-group { position: relative; }
    .password-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666; }
    .text-xs { font-size: 0.75rem; }
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
          <i class="fas fa-key"></i>
        </div>
        <h2 class="text-3xl font-bold mb-2" style="color: #8B0000;">Reset Password</h2>
        <p class="text-gray-600">Enter your new password for: **<?php echo htmlspecialchars($display_email); ?>**</p>
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

      <?php if ($password_reset_allowed || $_SERVER['REQUEST_METHOD'] === 'POST' && $error_message): ?>
      <form method="POST" action="" onsubmit="return validateForm()">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <input type="hidden" name="otp" value="<?php echo htmlspecialchars($otp_from_url); ?>">
        
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
              oninput="validatePassword(); checkPasswordMatch();"
            >
            <span class="password-toggle" onclick="togglePassword('password')">
              <i class="fas fa-eye" id="toggleIconPassword"></i>
            </span>
          </div>
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
      <?php else: ?>
        <p class="text-gray-600 text-center">Please request a new password reset link.</p>
        <div class="login-links mt-4">
             <a href="student-forgot-password.php">
                <i class="fas fa-redo-alt"></i> Request New Link
            </a>
        </div>
      <?php endif; ?>


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

    // Client-side validation function to provide real-time feedback
    function validatePassword() {
      const password = document.getElementById('password').value;
      const requirementsDiv = document.getElementById('password-requirements');
      
      const hasMinLength = password.length >= 8;
      const hasUppercase = /[A-Z]/.test(password);
      const hasLowercase = /[a-z]/.test(password);
      const hasNumber = /[0-9]/.test(password);
      const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
      
      let html = '<div class="mt-2 text-xs">';
      html += hasMinLength 
        ? '<div class="text-green-600"><i class="fas fa-check"></i> Min. 8 characters</div>'
        : '<div class="text-red-600"><i class="fas fa-times"></i> Min. 8 characters</div>';
      html += hasUppercase 
        ? '<div class="text-green-600"><i class="fas fa-check"></i> 1 Capital letter</div>'
        : '<div class="text-red-600"><i class="fas fa-times"></i> 1 Capital letter</div>';
      html += hasLowercase 
        ? '<div class="text-green-600"><i class="fas fa-check"></i> 1 Small letter</div>'
        : '<div class="text-red-600"><i class="fas fa-times"></i> 1 Small letter</div>';
      html += hasNumber 
        ? '<div class="text-green-600"><i class="fas fa-check"></i> 1 Number</div>'
        : '<div class="text-red-600"><i class="fas fa-times"></i> 1 Number</div>';
      html += hasSpecial 
        ? '<div class="text-green-600"><i class="fas fa-check"></i> 1 Symbol</div>'
        : '<div class="text-red-600"><i class="fas fa-times"></i> 1 Symbol</div>';
      html += '</div>';
      
      requirementsDiv.innerHTML = html;
    }

    function checkPasswordMatch() {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const matchDiv = document.getElementById('password-match');
      
      if (confirmPassword.length > 0) {
        if (password === confirmPassword) {
          matchDiv.innerHTML = '<div class="text-green-600 mt-1"><i class="fas fa-check"></i> Passwords match</div>';
        } else {
          matchDiv.innerHTML = '<div class="text-red-600 mt-1"><i class="fas fa-times"></i> Passwords do not match</div>';
        }
      } else {
        matchDiv.innerHTML = '';
      }
    }

    // Final client-side validation check before submission
    function validateForm() {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      
      if (password !== confirmPassword) {
        alert('Passwords do not match.');
        return false;
      }
      
      if (password.length < 8) {
        alert('Password must be at least 8 characters long.');
        return false;
      }
      
      const hasUppercase = /[A-Z]/.test(password);
      const hasLowercase = /[a-z]/.test(password);
      const hasNumber = /[0-9]/.test(password);
      const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
      
      if (!hasUppercase || !hasLowercase || !hasNumber || !hasSpecial) {
        alert('Password must contain at least one capital letter, one small letter, one number, and one symbol.');
        return false;
      }
      
      return true;
    }
    
    // Initial validation check on load if fields are pre-filled (useful for error returns)
    document.addEventListener('DOMContentLoaded', () => {
        validatePassword();
        checkPasswordMatch();
    });
  </script>
</body>
</html>