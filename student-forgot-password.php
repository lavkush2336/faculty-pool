<?php
// student-forgot-password.php
session_start();
require_once 'db.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if ($email) {
        try {
            $stmt = $pdo->prepare("SELECT student_id, name FROM students WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                // Redirect to reset password page with email
                header('Location: student-reset-password.php?email=' . urlencode($email));
                exit;
            } else {
                // Don't reveal if email exists for security
                $success_message = 'If this email exists in our system, you will be redirected to reset your password.';
            }
        } catch (PDOException $e) {
            $error_message = 'System Error: Please try again later.';
        }
    } else {
        $error_message = 'Please enter your email address.';
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
        <p class="text-gray-600">Enter your email to reset your password</p>
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

