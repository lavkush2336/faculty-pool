<?php
// faculty-login.php
session_start();
require_once 'db.php'; // ⚠️ Ensure this file defines your $pdo database connection

$login_error = '';
$login_success = '';

// Handle redirect status from password reset
if (isset($_GET['status']) && $_GET['status'] === 'pwreset') {
    $login_success = "Password successfully reset! You can now log in with your new password.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo;

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $login_error = "Please enter both email and password.";
    } else {
        try {
            // ⭐️ IMPORTANT: Select the 'password' column for login authentication.
            $stmt = $pdo->prepare("SELECT faculty_id, first_name, password FROM Faculty WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // ⭐️ IMPORTANT: Verify against the 'password' column.
                if (password_verify($password, $user['password'])) {
                    // Success: Set session and redirect to dashboard
                    $_SESSION['faculty_id'] = $user['faculty_id'];
                    $_SESSION['faculty_name'] = $user['first_name'];
                    // You should redirect to the faculty dashboard here
                    header('Location: faculty_dashboard.php'); 
                    exit;
                } else {
                    $login_error = "Invalid email or password.";
                }
            } else {
                $login_error = "Invalid email or password.";
            }
        } catch (Exception $e) {
            $login_error = "A system error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Faculty Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* Reusing your consistent styles for the login page */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 50%, #f0f4f8 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .login-container { width: 100%; max-width: 450px; padding: 20px; }
    .login-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); position: relative; }
    .login-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #8B0000, #A52A2A, #8B0000); border-radius: 20px 20px 0 0; }
    .header { text-align: center; margin-bottom: 30px; }
    .header i { font-size: 3rem; color: #8B0000; margin-bottom: 15px; }
    .header h2 { color: #8B0000; font-weight: 700; margin-bottom: 10px; }
    .header p { color: #666; margin-bottom: 20px; }
    .form-group { margin-bottom: 20px; position: relative; }
    .form-label { color: #8B0000; font-weight: 600; margin-bottom: 8px; display: block; text-align: left; }
    .form-control { width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; transition: all 0.3s ease; background: white; }
    .form-control:focus { outline: none; border-color: #8B0000; box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1); }
    .submit-btn { width: 100%; background: linear-gradient(135deg, #8B0000 0%, #A52A2A 100%); color: white; border: none; border-radius: 10px; padding: 15px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-bottom: 20px; }
    .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(139, 0, 0, 0.1); }
    .submit-btn:active { transform: translateY(0); }
    .forgot-link { text-align: center; margin-top: 15px; }
    .forgot-link a { color: #8B0000; text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
    .forgot-link a:hover { color: #A52A2A; }
    .decoration { display: flex; align-items: center; justify-content: center; gap: 10px; margin: 20px 0; }
    .decoration-line { width: 30px; height: 2px; background: linear-gradient(90deg, transparent, #8B0000, transparent); }
    .decoration-dot { width: 6px; height: 6px; background: #8B0000; border-radius: 50%; }
    
    /* === NEW STYLES FOR BACK TO HOME BUTTON === */
    .back-to-home {
        position: fixed; /* Fixed position */
        top: 20px;
        left: 20px;
        color: #8B0000;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        z-index: 10;
        background: rgba(255, 255, 255, 0.9);
        padding: 8px 15px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .back-to-home:hover {
        color: #A52A2A;
        transform: translateX(-5px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }
    
    /* Responsive adjustment for small screens */
    @media (max-width: 500px) {
        .back-to-home {
            top: 10px;
            left: 10px;
            font-size: 0.9rem;
            padding: 6px 12px;
        }
    }
  </style>
</head>
<body>
    <a href="index.php" class="back-to-home">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    
  <div class="login-container">
    <div class="login-card">
      <div class="header">
        <i class="fas fa-user-tie"></i> <h2>Faculty Login</h2>
        <p>Access your Faculty Pool account.</p>
        <div class="decoration">
          <div class="decoration-line"></div>
          <div class="decoration-dot"></div>
          <div class="decoration-line"></div>
        </div>
      </div>
      
      <?php if (!empty($login_error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $login_error; ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($login_success)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $login_success; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="faculty-login.php">
        <div class="form-group">
          <label for="email" class="form-label">
            <i class="fas fa-envelope"></i> Thapar Email
          </label>
          <input type="email" id="email" name="email" class="form-control" placeholder="user@thapar.edu" required>
        </div>
        
        <div class="form-group">
          <label for="password" class="form-label">
            <i class="fas fa-lock"></i> Password
          </label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="submit-btn">
          <i class="fas fa-sign-in-alt"></i> Log In
        </button>
      </form>
      
      <div class="forgot-link">
        <a href="forgot-password-email.php">
          <i class="fas fa-question-circle"></i> Forgot Password?
        </a>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>