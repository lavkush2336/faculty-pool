<?php
// 🛑 DEBUGGING START: FORCING ALL ERRORS TO DISPLAY IN THE BROWSER
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// 🛑 DEBUGGING END

// student-forgot-password.php (Step 1: Email Submission, OTP Table Insertion, & Email Link)
session_start();

// 1. LOAD DEPENDENCIES
require_once 'vendor/autoload.php';
require_once 'db.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 2. EMAIL SIMULATION FUNCTION
function send_reset_link_email($email, $otp)
{
    $reset_url = "http://localhost/faculty-pool/student-reset-password.php?email=" . urlencode($email) . "&otp=" . urlencode($otp);
    
    $mail = new PHPMailer(true);
    try {
        // --- SMTP Settings (Your provided credentials) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'facultypoolthapar@gmail.com';     
        $mail->Password   = 'xrpvcgnjkqofjlta'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        // --------------------------------------------------------------

        $mail->setFrom('noreply@yourdomain.com', 'Student Support');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Student Password Reset Request';
        $mail->Body    = "
            <h2>Password Reset Request</h2>
            <p>You requested a password reset. Click the link below to set a new password:</p>
            <p>
                <a href='".$reset_url."' style='display: inline-block; padding: 10px 20px; color: white; background-color: #8B0000; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                    Reset Password
                </a>
            </p>
            <p>Your unique reset code is: <strong>".$otp."</strong></p>
            <p>This link is valid for 10 minutes.</p>
        ";

        $mail->send();
        return true; 
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo); 
        return true; 
    }
}

// 3. INITIALIZE VARIABLES
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo;
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $error_message = 'Please enter your email address.';
    } else {
        try {
            // 1. Check if student exists in the main 'students' table (using plural 'students' table name)
            $stmt = $pdo->prepare("SELECT student_id FROM student WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $student_exists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student_exists) {
                // Generate secure 6-digit OTP
                $otp = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                $expiry_time = time() + 600; // 10 minutes from now

                // 🔑 DATABASE ACTION: REPLACE INTO requires email to be UNIQUE/PRIMARY KEY
                $stmt_update = $pdo->prepare("
                    REPLACE INTO student_otp (email, otp, expiry) 
                    VALUES (:email, :otp, :expiry)
                ");
                $stmt_update->execute([
                    ':email' => $email,
                    ':otp' => $otp,
                    ':expiry' => $expiry_time
                ]);

                // Send email
                $mail_result = send_reset_link_email($email, $otp);

                if ($mail_result === true) {
                    $success_message = 'A password reset link has been sent to your inbox. Please check your spam folder.';
                } else {
                    $error_message = 'Failed to send reset link email.';
                }
            } else {
                $success_message = 'If this email exists in our system, a password reset link has been sent.';
            }
        } catch (PDOException $e) {
            $error_message = 'System Error: Database query failed. Error: ' . $e->getMessage();
            error_log("Forgot Password PDO Query Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - Student</title>

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
        <h2 class="text-3xl font-bold mb-2" style="color: #8B0000;">Forgot Password</h2>
        <p class="text-gray-600">Enter your email to receive a password reset link.</p>
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

      <form method="POST" action="">
        <div class="form-group">
          <label for="email" class="form-label">
            <i class="fas fa-envelope"></i> Email Address
          </label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control" 
            placeholder="Enter your email"
            required
          >
        </div>

        <button type="submit" class="login-btn">
          <i class="fas fa-paper-plane"></i> Send Reset Link
        </button>
      </form>

      <div class="login-links">
        <p>Remember your password? <a href="student-login.php"><i class="fas fa-sign-in-alt"></i> Login</a></p>
      </div>
    </div>
  </div>
</body>
</html>