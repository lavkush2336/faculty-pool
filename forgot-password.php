<?php
// forgot-password.php
session_start();

// 1. LOAD DEPENDENCIES
require_once 'vendor/autoload.php';
require_once 'db.php'; // ⚠️ Make sure this file defines your $pdo variable

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. DEFINE YOUR EMAIL FUNCTION
function sendemail_verify($email, $otp)
{
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'facultypoolthapar@gmail.com';     // Your email
        $mail->Password   = 'xrpvcgnjkqofjlta';   // ⚠️ MUST BE YOUR 16-DIGIT APP PASSWORD
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('facultypoolthapar@gmail.com', 'Faculty Pool');
        $mail->addAddress($email);
        
        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Faculty Pool Password Reset OTP'; // Updated Subject
        $mail->Body    = "<h2>Your One-Time Password (OTP) for Faculty Pool is: <b>".$otp."</b></h2>"; // Updated Body

        $mail->send();
        return true; // Success
    } catch (Exception $e) {
        return $mail->ErrorInfo; // Return the error message on failure
    }
}

// 3. INITIALIZE VARIABLES
$step = 1; // 1 = Show Email, 2 = Show OTP/Password
$error_message = '';
$success_message = '';
$display_email = ''; // To show the email on step 2

// 4. PROCESS FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo; // Get the database connection

    // --- STEP 1 SUBMISSION: User submitted an email ---
    if (isset($_POST['submit_email'])) {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        // Validate @thapar.edu
        if (empty($email) || !preg_match('/@thapar\.edu$/i', $email)) {
            $error_message = 'Please use a valid @thapar.edu email.';
            $step = 1;
        } else {
            try {
                // --- ⚠️ DATABASE CHECK ---
                // I am keeping this commented out as you requested, using a placeholder name.
                // To enable it, uncomment the block and make sure your query is correct.
                /*
                $stmt = $pdo->prepare("SELECT first_name FROM Faculty WHERE email = :email");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();
                if (!$user) {
                    throw new Exception("No account found with that email address.");
                }
                $name = $user['first_name'];
                */
                $name = "Faculty Member"; // Placeholder name
                
                // --- Send OTP ---
                $otp = rand(1000, 9999);
                $send_result = sendemail_verify($email, $otp);

                if ($send_result === true) {
                    // Store OTP and email in session
                    $_SESSION['otp'] = $otp;
                    $_SESSION['otp_email'] = $email;
                    $_SESSION['otp_time'] = time(); // For optional 10-minute expiry
                    
                    // Move to Step 2
                    $step = 2;
                    $success_message = 'An OTP has been sent to your email.';
                    $display_email = $email;
                } else {
                    $error_message = "Failed to send OTP. Mailer Error: " . $send_result;
                    $step = 1;
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                $step = 1;
            }
        }
    }
    
    // --- STEP 2 SUBMISSION: User submitted OTP and new password ---
    elseif (isset($_POST['submit_password'])) {
        $step = 2; // Keep user on step 2 if errors occur
        $display_email = $_SESSION['otp_email'] ?? ''; // Get email from session

        $otp = $_POST['otp'] ?? '';
        $newPassword = $_POST['newPassword'] ?? '';
        $confirmPassword = $_POST['confirmNewPassword'] ?? '';

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
        elseif (strlen($newPassword) < 8) {
            $error_message = 'Password must be at least 8 characters long.';
        }
        // Optional: Check OTP expiry
        elseif (time() - ($_SESSION['otp_time'] ?? 0) > 600) { // 10 minute expiry
             $error_message = 'OTP has expired. Please request a new one.';
             $step = 1; // Send user back to step 1
        }
        else {
            // --- SUCCESS: All checks passed ---
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $emailToUpdate = $_SESSION['otp_email'];
                
                $stmt = $pdo->prepare("UPDATE Faculty SET password = :password WHERE email = :email");
                $stmt->execute([
                    ':password' => $hashedPassword,
                    ':email' => $emailToUpdate
                ]);

                // Clear session and redirect
                unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time']);
                
                // Redirect to login page with a success message
                header('Location: faculty-login.php?status=pwreset');
                exit;

            } catch (Exception $e) {
                $error_message = 'Database error. Could not update password.';
            }
        }
    }
}
// This logic handles showing Step 2 if the user reloads the page after an OTP was sent
elseif (isset($_SESSION['otp_email']) && $step == 1) {
    $step = 2;
    $display_email = $_SESSION['otp_email'];
    $success_message = 'Please enter the OTP sent to your email.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - Faculty Pool</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* All your styles are unchanged */
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
      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $success_message; ?>
        </div>
      <?php endif; ?>


      <?php if ($step == 1): ?>
      <div id="emailSection">
        <div class="header">
          <i class="fas fa-user-lock"></i>
          <h2 id="cardHeader">Reset Password</h2>
          <p>Faculty Pool - Thapar Institute</p>
          <div class="decoration">
            <div class="decoration-line"></div>
            <div class="decoration-dot"></div>
            <div class="decoration-line"></div>
          </div>
        </div>

        <form id="emailForm" method="post" action="forgot-password.php">
          <div class="form-group">
            <label for="email" class="form-label">
              <i class="fas fa-envelope"></i> Thapar Email Address
            </label>
            <input type="email" id="email" name="email" class="form-control" placeholder="e.g., user@thapar.edu" required>
          </div>
          <button type="submit" name="submit_email" class="submit-btn" id="nextBtn">
            <i class="fas fa-arrow-right"></i> Next
          </button>
        </form>
      </div>
      <?php endif; // End Step 1 ?>


      <?php if ($step == 2): ?>
      <div id="passwordSection">
        <div class="header">
          <i class="fas fa-key"></i>
          <h2 id="cardHeader">Change Password</h2>
           <div class="decoration">
            <div class="decoration-line"></div>
            <div class="decoration-dot"></div>
            <div class="decoration-line"></div>
          </div>
        </div>

        <div class="info-box">
          <p><i class="fas fa-info-circle"></i> Changing password for: <strong><?php echo htmlspecialchars($display_email); ?></strong></p>
        </div>

        <form id="changePasswordForm" method="post" action="forgot-password.php">
          
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

          <button type="submit" name="submit_password" class="submit-btn" id="passwordSubmitBtn">
            <i class="fas fa-save"></i> Save New Password
          </button>
        </form>
      </div>
      <?php endif; // End Step 2 ?>


      <div class="login-links">
        <a href="faculty-login.php" id="loginLink">
          <i class="fas fa-sign-in-alt"></i> Back to Login
        </a>
        <?php if ($step == 2): ?>
        <a href="forgot-password.php" id="backToEmailLink">
          <i class="fas fa-envelope"></i> Change Email
        </a>
        <?php endif; ?>
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
  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>