<?php
// teacher-password-email.php (Step 1: Email Input)
session_start();

// 1. LOAD DEPENDENCIES
require_once 'vendor/autoload.php';
require_once 'db.php'; // ⚠️ Ensure this file defines your $pdo database connection

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
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        //Recipients
        $mail->setFrom('facultypoolthapar@gmail.com', 'Faculty Pool');
        $mail->addAddress($email);
        
        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Faculty Pool Password Reset OTP';
        $mail->Body    = "<h2>Your One-Time Password (OTP) for Faculty Pool is: <b>".$otp."</b></h2>";

        $mail->send();
        return true; // Success
    } catch (Exception $e) {
        return $mail->ErrorInfo; // Return the error message on failure
    }
}

// 3. INITIALIZE VARIABLES
$error_message = '';
$success_message = $_GET['status'] === 'expired' ? 'Your previous OTP expired. Please enter your email again.' : '';

// 4. PROCESS FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo;

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    // 5. VALIDATION
    if (empty($email) || !preg_match('/@thapar\.edu$/i', $email)) {
        $error_message = 'Please use a valid @thapar.edu email.';
    } else {
        try {
            // --- DATABASE CHECK ---
            $stmt = $pdo->prepare("SELECT first_name FROM Faculty WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new Exception("No account found with that email address.");
            }
            
            // --- Send OTP ---
            $otp = rand(1000, 9999);
            $send_result = sendemail_verify($email, $otp);

            if ($send_result === true) {
                // Store OTP and email in session
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_time'] = time(); // For optional 10-minute expiry
                
                // Redirect to the reset page (Step 2)
                header('Location: teacher-password-reset.php');
                exit;
            } else {
                $error_message = "Failed to send OTP. Mailer Error: " . $send_result;
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - Step 1: Email</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
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
  <a href="teacher-login.php" class="back-link">
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

      <div id="emailSection">
        <div class="header">
          <i class="fas fa-user-lock"></i>
          <h2>Reset Password</h2>
          <p>Faculty Pool - Thapar Institute</p>
          <div class="decoration">
            <div class="decoration-line"></div>
            <div class="decoration-dot"></div>
            <div class="decoration-line"></div>
          </div>
        </div>

        <form id="emailForm" method="post" action="teacher-password-email.php">
          <div class="form-group">
            <label for="email" class="form-label">
              <i class="fas fa-envelope"></i> Thapar Email Address
            </label>
            <input type="email" id="email" name="email" class="form-control" placeholder="e.g., user@thapar.edu" required>
          </div>
          <button type="submit" name="submit_email" class="submit-btn">
            <i class="fas fa-arrow-right"></i> Send OTP
          </button>
        </form>
      </div>

      <div class="login-links">
        <a href="teacher-login.php">
          <i class="fas fa-sign-in-alt"></i> Back to Login
        </a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>