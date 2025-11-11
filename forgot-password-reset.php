<?php
// forgot-password-reset.php (Step 2: OTP and Password Reset)
session_start();

// 1. LOAD DEPENDENCIES (Duplicated to make this file independent)
require_once 'vendor/autoload.php';
require_once 'db.php'; // ⚠️ Make sure this file defines your $pdo variable

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. DEFINE YOUR EMAIL FUNCTION (Duplicated, though not needed for this script's POST, it maintains file independence)
function sendemail_verify($email, $otp)
{
    // Function definition needed if you were to resend OTP from this page, 
    // but we'll include it for consistency, even if it's not strictly used here.
    // ... (Mailer setup) ... 
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'facultypoolthapar@gmail.com';     
        $mail->Password   = 'xrpvcgnjkqofjlta';   
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        $mail->setFrom('facultypoolthapar@gmail.com', 'Faculty Pool');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Faculty Pool Password Reset OTP';
        $mail->Body    = "<h2>Your One-Time Password (OTP) for Faculty Pool is: <b>".$otp."</b></h2>";

        $mail->send();
        return true; 
    } catch (Exception $e) {
        return $mail->ErrorInfo; 
    }
}

// 3. INITIALIZE VARIABLES & SECURITY CHECK
$error_message = '';
$success_message = 'An OTP has been sent to your email. Please check your inbox.';

// SECURITY CHECK: If no email is set in session, redirect user back to Step 1.
if (!isset($_SESSION['otp_email']) || !isset($_SESSION['otp'])) {
    header('Location: forgot-password-email.php');
    exit;
}

$display_email = $_SESSION['otp_email'];

// Optional: Check OTP expiry on page load (10 minutes)
if (time() - ($_SESSION['otp_time'] ?? 0) > 600) { 
     // Clear session data and redirect back to email page with an expiry status
     unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time']);
     header('Location: forgot-password-email.php?status=expired');
     exit;
}


// 4. PROCESS FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_password'])) {
    global $pdo;

    $otp = $_POST['otp'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmNewPassword'] ?? '';

    // --- Define Password Regex for Server-Side Validation ---
    // Requires: >= 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 symbol
    $password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

    // --- Validate All Fields ---
    if (empty($otp) || empty($newPassword) || empty($confirmPassword)) {
        $error_message = 'Please fill in all fields.';
    }
    elseif (!isset($_SESSION['otp']) || $_SESSION['otp'] != $otp) {
        $error_message = 'Invalid or incorrect OTP.';
    }
    elseif ($newPassword !== $confirmPassword) {
        $error_message = 'Passwords do not match.';
    }
    // ⭐️ NEW SERVER-SIDE PASSWORD CONSTRAINT CHECK
    elseif (!preg_match($password_regex, $newPassword)) {
        $error_message = 'Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one symbol.';
    }
    // Final expiry check
    elseif (time() - ($_SESSION['otp_time'] ?? 0) > 600) {
         unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time']);
         header('Location: forgot-password-email.php?status=expired');
         exit;
    }
    else {
        // --- SUCCESS: All checks passed - Update Password ---
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $emailToUpdate = $_SESSION['otp_email'];
            
            $stmt = $pdo->prepare("UPDATE Faculty SET password = :password WHERE email = :email");
            $stmt->execute([
                ':password' => $hashedPassword,
                ':email' => $emailToUpdate
            ]);

            // Clear session and redirect to login
            unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time']);
            
            header('Location: faculty-login.php?status=pwreset');
            exit;

        } catch (Exception $e) {
            $error_message = 'Database error. Could not update password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - Step 2: Reset</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* Add your full CSS styles block here */
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
    .info-box p { color: #333; font-size: 14px; margin: 0; font-weight: 500; }
    .info-box p strong { color: #8B0000; }
    .info-box i { color: #8B0000; margin-right: 8px; }
    
    /* ⭐️ NEW STYLES FOR PASSWORD HINTS */
    .password-hint { 
      font-size: 12px; 
      margin-top: 5px; 
      padding-left: 0; 
      list-style: none; 
      color: #6c757d; 
    }
    .password-hint li { 
      transition: color 0.3s ease; 
      margin-bottom: 3px; 
    }
    .password-hint li i {
      margin-right: 5px;
    }
    .valid-constraint {
        color: #28a745 !important; /* Green for valid */
    }
    .invalid-constraint {
        color: #dc3545 !important; /* Red for invalid */
    }
    /* END NEW STYLES */

    .password-error { color: #dc3545; font-size: 12px; margin-top: 5px; display: none; list-style: none; padding-left: 0; }
    .password-error li { margin-top: 3px; }
    #otp { font-size: 1.2rem; text-align: center; letter-spacing: 0.5em; }
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
      <?php if (!empty($success_message) && empty($error_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $success_message; ?>
        </div>
      <?php endif; ?>

      <div id="passwordSection">
        <div class="header">
          <i class="fas fa-key"></i>
          <h2>Change Password</h2>
           <div class="decoration">
            <div class="decoration-line"></div>
            <div class="decoration-dot"></div>
            <div class="decoration-line"></div>
          </div>
        </div>

        <div class="info-box">
          <p><i class="fas fa-info-circle"></i> Changing password for: <strong><?php echo htmlspecialchars($display_email); ?></strong></p>
        </div>

        <form id="changePasswordForm" method="post" action="forgot-password-reset.php">
          
          <div class="form-group">
            <label for="otp" class="form-label">
              <i class="fas fa-shield-alt"></i> 4-Digit OTP
            </label>
            <input type="password" id="otp" name="otp" class="form-control" placeholder="_ _ _ _" required maxlength="4" pattern="\d{4}">
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
            <ul id="passwordHint" class="password-hint">
                <li id="length"><i class="fas fa-times-circle"></i> At least 8 characters</li>
                <li id="uppercase"><i class="fas fa-times-circle"></i> One uppercase letter (A-Z)</li>
                <li id="lowercase"><i class="fas fa-times-circle"></i> One lowercase letter (a-z)</li>
                <li id="number"><i class="fas fa-times-circle"></i> One number (0-9)</li>
                <li id="symbol"><i class="fas fa-times-circle"></i> One symbol (e.g., !@#$%)</li>
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
          </div>

          <button type="submit" name="submit_password" class="submit-btn">
            <i class="fas fa-save"></i> Save New Password
          </button>
        </form>
      </div>

      <div class="login-links">
        <a href="faculty-login.php">
          <i class="fas fa-sign-in-alt"></i> Back to Login
        </a>
        <a href="forgot-password-email.php">
          <i class="fas fa-envelope"></i> Change/Resend Email
        </a>
      </div>
    </div>
  </div>

  <script>
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
    
    // This script ensures only digits are entered in the OTP field
    const otpInput = document.getElementById('otp');
    if (otpInput) {
        otpInput.addEventListener('input', (e) => {
            let value = e.target.value;
            value = value.replace(/\D/g, ''); // Keep only digits
            e.target.value = value;
        });
    }

    // ⭐️ NEW JAVASCRIPT FOR REAL-TIME PASSWORD VALIDATION
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmNewPassword');
    const form = document.getElementById('changePasswordForm');
    
    const constraints = {
        length: { regex: /.{8,}/, element: document.getElementById('length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('lowercase') },
        number: { regex: /\d/, element: document.getElementById('number') },
        symbol: { regex: /[\W_]/, element: document.getElementById('symbol') } // \W_ matches any non-word char or underscore
    };

    function updatePasswordValidation() {
        const password = newPasswordInput.value;
        let allValid = true;

        for (const key in constraints) {
            const constraint = constraints[key];
            const isValid = constraint.regex.test(password);
            
            const icon = constraint.element.querySelector('i');
            icon.classList.toggle('fa-check-circle', isValid);
            icon.classList.toggle('fa-times-circle', !isValid);
            
            constraint.element.classList.toggle('valid-constraint', isValid);
            constraint.element.classList.toggle('invalid-constraint', !isValid);
            
            if (!isValid) {
                allValid = false;
            }
        }
        
        return allValid;
    }

    // Real-time validation for new password
    newPasswordInput.addEventListener('input', updatePasswordValidation);

    // Final check before form submission (client-side)
    form.addEventListener('submit', function(e) {
        let isValid = updatePasswordValidation();
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (!isValid) {
            alert('Your new password does not meet all the security requirements.');
            e.preventDefault();
            return;
        }

        if (newPassword !== confirmPassword) {
            alert('New Password and Confirm New Password do not match.');
            e.preventDefault();
            return;
        }
    });

    // Run on page load in case of browser autofill
    updatePasswordValidation(); 
    // END NEW JAVASCRIPT
  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>