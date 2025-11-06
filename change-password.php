<?php
// change-password.php
session_start();
require_once 'vendor/autoload.php'; // For password_hash/verify
require_once 'db.php'; // For $pdo

$error_message = '';
$success_message = '';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo;

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $oldPassword = $_POST['oldPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmNewPassword = $_POST['confirmNewPassword'] ?? '';

    // --- 1. Basic Validation ---
    if (empty($email) || empty($oldPassword) || empty($newPassword) || empty($confirmNewPassword)) {
        $error_message = 'Please fill in all fields.';
    } elseif ($newPassword !== $confirmNewPassword) {
        $error_message = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $error_message = 'New password must be at least 8 characters long.';
    } elseif ($oldPassword === $newPassword) {
         $error_message = 'New password cannot be the same as the old password.';
    } else {
        // --- 2. Check Database ---
        try {
            $stmt = $pdo->prepare("SELECT faculty_id, password FROM Faculty WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            // Check if user exists AND if the old password is correct
            if ($user && password_verify($oldPassword, $user['password'])) {
                
                // --- 3. Hash the new password ---
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // --- 4. Update in database ---
                $updateStmt = $pdo->prepare("UPDATE Faculty SET password = :password WHERE faculty_id = :id");
                $updateStmt->execute([
                    ':password' => $hashedPassword,
                    ':id' => $user['faculty_id']
                ]);
                
                $success_message = 'Your password has been successfully updated! You can now log in.';

            } else {
                // Generic error for security
                $error_message = 'Incorrect email or old password.';
            }

        } catch (Exception $e) {
            $error_message = 'A database error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Password - Faculty Pool</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* All your styles are preserved */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 50%, #f0f4f8 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .forgot-container { width: 100%; max-width: 450px; padding: 20px; }
    .forgot-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); position: relative; }
    .forgot-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #8B0000, #A52A2A, #8B0000); border-radius: 20px 20px 0 0; }
    .header { text-align: center; margin-bottom: 30px; }
    .header i { font-size: 3rem; color: #8B0000; margin-bottom: 15px; }
    .header h2 { color: #8B0000; font-weight: 700; margin-bottom: 10px; }
    .header p { color: #666; margin-bottom: 20px; }
    .form-group { margin-bottom: 20px; position: relative; }
    .form-label { color: #8B0000; font-weight: 600; margin-bottom: 8px; display: block; }
    .password-input-group { position: relative; }
    .form-control { width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; transition: all 0.3s ease; background: white; pointer-events: auto; }
    .form-control:focus { outline: none; border-color: #8B0000; box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1); }
    .form-control.is-invalid { border-color: #dc3545; }
    .form-control::placeholder { color: #999; }
    .password-toggle { position: absolute; top: 50%; right: 15px; transform: translateY(-50%); background: none; border: none; color: #999; cursor: pointer; padding: 5px; transition: color 0.2s ease; z-index: 10; }
    .password-toggle:hover { color: #8B0000; }
    .submit-btn { width: 100%; background: linear-gradient(135deg, #8B0000 0%, #A52A2A 100%); color: white; border: none; border-radius: 10px; padding: 15px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-bottom: 20px; pointer-events: auto; }
    .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(139, 0, 0, 0.1); }
    .submit-btn:active { transform: translateY(0); }
    .back-link { position: absolute; top: 20px; left: 20px; color: #8B0000; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; pointer-events: auto; cursor: pointer; }
    .back-link:hover { color: #A52A2A; transform: translateX(-3px); }
    .decoration { display: flex; align-items: center; justify-content: center; gap: 10px; margin: 20px 0; }
    .decoration-line { width: 30px; height: 2px; background: linear-gradient(90deg, transparent, #8B0000, transparent); }
    .decoration-dot { width: 6px; height: 6px; background: #8B0000; border-radius: 50%; }
    .login-links { text-align: center; margin-top: 20px; }
    .login-links a { color: #8B0000; text-decoration: none; font-weight: 500; margin: 0 10px; transition: color 0.3s ease; pointer-events: auto; cursor: pointer; }
    .login-links a:hover { color: #A52A2A; }
    .info-box { background: rgba(139, 0, 0, 0.05); border: 1px solid rgba(139, 0, 0, 0.1); border-radius: 10px; padding: 15px; margin-bottom: 20px; }
    .info-box p { color: #666; font-size: 14px; margin: 0; }
    .info-box i { color: #8B0000; margin-right: 8px; }
    .password-error { color: #dc3545; font-size: 12px; margin-top: 5px; display: none; list-style: none; padding-left: 0; }
    .password-error li { margin-top: 3px; }
    input, button, a, select, textarea { pointer-events: auto !important; -webkit-user-select: auto !important; user-select: auto !important; }
  </style>
</head>
<body>
  <a href="faculty-login.php" class="back-link">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Login</span>
  </a>

  <div class="forgot-container">
    <div class="forgot-card">
      
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error_message; ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $success_message; ?>
        </div>
      <?php endif; ?>

      <div id="passwordSection">
        <div class="header">
          <i class="fas fa-key"></i>
          <h2 id="cardHeader">Change Password</h2>
          <p>Faculty Pool - Thapar Institute</p>
          <div class="decoration">
            <div class="decoration-line"></div>
            <div class="decoration-dot"></div>
            <div class="decoration-line"></div>
          </div>
        </div>

        <form id="changePasswordForm" method="post" action="change-password.php">
          
          <div class="form-group">
            <label for="email" class="form-label">
              <i class="fas fa-envelope"></i> Email Address
            </label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email address" required>
          </div>
          
          <div class="form-group">
            <label for="oldPassword" class="form-label">
              <i class="fas fa-lock"></i> Old Password
            </label>
            <div class="password-input-group">
                <input type="password" id="oldPassword" name="oldPassword" class="form-control" placeholder="Enter old password" required>
                <button type="button" class="password-toggle" data-target="oldPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
          </div>

          <div class="form-group">
            <label for="newPassword" class="form-label">
              <i class="fas fa-key"></i> New Password
            </label>
            <div class="password-input-group">
                <input type="password" id="newPassword" name="newPassword" class="form-control" placeholder="Enter new password" required>
                <button type="button" class="password-toggle" data-target="newPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <ul id="passwordValidationList" class="password-error">
                </ul>
          </div>

          <div class="form-group">
            <label for="confirmNewPassword" class="form-label">
              <i class="fas fa-redo-alt"></i> Confirm New Password
            </label>
            <div class="password-input-group">
                <input type="password" id="confirmNewPassword" name="confirmNewPassword" class="form-control" placeholder="Confirm new password" required>
                <button type="button" class="password-toggle" data-target="confirmNewPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
             <div id="matchError" class="password-error" style="display:none; color: #dc3545; font-size: 12px; margin-top: 5px;">
                <i class="fas fa-times-circle"></i> Passwords do not match.
            </div>
          </div>

          <button type="submit" class="submit-btn" id="passwordSubmitBtn">
            <i class="fas fa-save"></i> Save New Password
          </button>
        </form>
      </div>

      <div class="login-links">
        <a href="faculty-login.php">
          <i class="fas fa-sign-in-alt"></i> Back to Login
        </a>
      </div>
    </div>
  </div>

  <script>
    // --- Utility Function: Password Toggle (This JS is still needed) ---
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                targetInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // --- Validation Logic has been removed, as PHP now handles it ---
  </script>
</body>
</html>