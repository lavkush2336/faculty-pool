<?php
// faculty-login.php
// Implements secure login logic
session_start(); // Start the session at the very top
require_once 'db.php'; // Database connection must be defined here as $pdo

$login_error = '';
$facultyEmail = ''; // Variable to retain email in the form on failure

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize input
    $facultyEmail = filter_input(INPUT_POST, 'facultyId', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? ''; // Get password as is

    if ($facultyEmail && $password) {
        try {
            // 2. Prepare and execute the query to fetch the faculty record by email and the stored password hash
            // We fetch 'password' (the hash) from the new column.
            $stmt = $pdo->prepare("SELECT faculty_id, first_name, password FROM Faculty WHERE email = :email");
            $stmt->execute([':email' => $facultyEmail]);
            $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. Verify existence and password
            if ($faculty) {
                // Securely verify the plain-text password against the stored hash
                if (password_verify($password, $faculty['password'])) {
                    
                    // Login successful!
                    $_SESSION['faculty_id'] = $faculty['faculty_id'];
                    $_SESSION['faculty_name'] = $faculty['first_name'];
                    $_SESSION['logged_in'] = true;

                    // Redirect to the faculty dashboard
                    header('Location: faculty_dashboard.php'); 
                    exit;
                }
            }
            
            // If execution reaches here, login failed (invalid email or password)
            $login_error = 'Invalid email or password. Please try again.';

        } catch (PDOException $e) {
            // Log the error for debugging and show a generic message to the user
            error_log("Login PDO Error: " . $e->getMessage());
            $login_error = 'A system error occurred. Please try again later.';
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
  <meta name="description" content="Faculty Login - Faculty Pool - Thapar Institute of Engineering & Technology">
  <meta name="keywords" content="faculty login, teacher, Thapar University, TIET">
  <meta name="author" content="Thapar Institute of Engineering & Technology">
  <title>Faculty Login - Faculty Pool</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <script src="https://cdn.tailwindcss.com"></script>

  <link rel="stylesheet" href="styles.css">

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              600: '#dc2626',
              700: '#b91c1c',
              800: '#991b1b',
              900: '#7f1d1d',
            }
          },
          fontFamily: {
            'inter': ['Inter', 'sans-serif'],
            'poppins': ['Poppins', 'sans-serif'],
          }
        }
      }
    }
  </script>

  <style>
    /* --- preserved CSS --- */
    .login-container {
      min-height: 100vh;
      background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 50%, #f0f4f8 100%);
      position: relative;
      overflow: hidden;
    }

    .login-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background:
        radial-gradient(circle at 20% 80%, rgba(139, 0, 0, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(139, 0, 0, 0.05) 0%, transparent 50%);
      pointer-events: none;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      position: relative;
      overflow: hidden;
    }

    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #8B0000, #A52A2A, #8B0000);
    }

    .form-group {
      position: relative;
      margin-bottom: 1.5rem;
    }

    .form-control {
      background: rgba(255, 255, 255, 0.8);
      border: 2px solid rgba(139, 0, 0, 0.1);
      border-radius: 12px;
      padding: 1rem 1.2rem;
      font-size: 1rem;
      transition: all 0.3s ease;
      width: 100%;
    }

    .form-control:focus {
      outline: none;
      border-color: #8B0000;
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
    }

    .form-label {
      color: #8B0000;
      font-weight: 600;
      margin-bottom: 0.5rem;
      display: block;
    }
    
    /* New styles for password toggle input group */
    .password-input-group {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #8B0000;
        z-index: 10;
        padding: 5px; /* Increase click area */
    }

    .login-btn {
      background: linear-gradient(135deg, #8B0000 0%, #A52A2A 100%);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 1rem 2rem;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      width: 100%;
      box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);
    }

    .login-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s ease;
    }

    .login-btn:hover::before {
      left: 100%;
    }

    .login-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(139, 0, 0, 0.4);
    }

    .login-btn:active {
      transform: translateY(0);
    }

    .login-links {
      text-align: center;
      margin-top: 1.5rem;
    }

    .login-links a {
      color: #8B0000;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      display: inline-block;
      margin: 0 0.5rem;
      font-size: 0.9rem; /* Set font size to help fit on one line */
    }

    .login-links a:hover {
      color: #A52A2A;
      transform: translateY(-1px);
    }

    .back-to-home {
      position: absolute;
      top: 2rem;
      left: 2rem;
      color: #8B0000;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .back-to-home:hover {
      color: #A52A2A;
      transform: translateX(-3px);
    }

    .floating-elements {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      overflow: hidden;
    }

    .floating-element {
      position: absolute;
      background: rgba(139, 0, 0, 0.05);
      border-radius: 50%;
      animation: float 20s infinite linear;
    }

    .floating-element:nth-child(1) {
      width: 80px;
      height: 80px;
      top: 20%;
      left: 10%;
      animation-delay: 0s;
    }

    .floating-element:nth-child(2) {
      width: 60px;
      height: 60px;
      top: 60%;
      right: 15%;
      animation-delay: 5s;
    }

    .floating-element:nth-child(3) {
      width: 100px;
      height: 100px;
      bottom: 20%;
      left: 20%;
      animation-delay: 10s;
    }

    @keyframes float {
      0% {
        transform: translateY(0px) rotate(0deg);
        opacity: 0.3;
      }
      50% {
        transform: translateY(-20px) rotate(180deg);
        opacity: 0.6;
      }
      100% {
        transform: translateY(0px) rotate(360deg);
        opacity: 0.3;
      }
    }

    .faculty-badge {
      background: linear-gradient(135deg, #8B0000, #A52A2A);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      display: inline-block;
      margin-bottom: 1rem;
      box-shadow: 0 2px 8px rgba(139, 0, 0, 0.3);
    }

    .faculty-features {
      background: rgba(139, 0, 0, 0.05);
      border-radius: 12px;
      padding: 1rem;
      margin-top: 1.5rem;
      border-left: 4px solid #8B0000;
    }

    .faculty-features h6 {
      color: #8B0000;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .faculty-features ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .faculty-features li {
      color: #2d3748;
      font-size: 0.9rem;
      margin-bottom: 0.3rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .faculty-features li i {
      color: #8B0000;
      font-size: 0.8rem;
    }
  </style>
</head>
<body class="font-poppins">
  <div class="login-container">
    <div class="floating-elements">
      <div class="floating-element"></div>
      <div class="floating-element"></div>
      <div class="floating-element"></div>
    </div>

    <a href="index.php" class="back-to-home" data-aos="fade-right">
      <i class="fas fa-arrow-left"></i>
      <span>Back to Home</span>
    </a>

    <div class="container-fluid d-flex align-items-center justify-content-center min-vh-100">
      <div class="row w-100">
        <div class="col-12 col-md-6 col-lg-4 mx-auto">
          <div class="login-card p-5" data-aos="zoom-in" data-aos-duration="800">
            <div class="text-center mb-4">
              <div class="text-4xl text-primary-600 mb-3">
                <i class="fas fa-user-tie"></i>
              </div>
              <h2 class="h3 fw-bold text-primary-600 mb-2">Faculty Login</h2>
              <p class="text-muted">Faculty Pool - Thapar Institute</p>
              <div class="d-flex align-items-center justify-content-center gap-2 mt-3">
                <div class="decoration-line" style="width: 30px; height: 2px; background: linear-gradient(90deg, transparent, #8B0000, transparent);"></div>
                <div class="decoration-dot" style="width: 6px; height: 6px; background: #8B0000; border-radius: 50%;"></div>
                <div class="decoration-line" style="width: 30px; height: 2px; background: linear-gradient(90deg, transparent, #8B0000, transparent);"></div>
              </div>
            </div>

            <?php if ($login_error): ?>
                <div class="alert alert-danger text-center mb-4" role="alert">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <form id="facultyLoginForm" method="post">
              <div class="form-group">
                <label for="facultyId" class="form-label">
                  <i class="fas fa-id-card me-2"></i>Faculty email
                </label>
                <input type="email" id="facultyId" name="facultyId" class="form-control" placeholder="Enter your email" required value="<?php echo htmlspecialchars($facultyEmail); ?>">
              </div>

              <div class="form-group">
                <label for="password" class="form-label">
                  <i class="fas fa-lock me-2"></i>Password
                </label>
                <div class="password-input-group">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                    <span class="password-toggle" onclick="togglePasswordVisibility()">
                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                    </span>
                </div>
                </div>

              <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt me-2"></i>
                Faculty Login
              </button>
            </form>

            <div class="faculty-features">
              <h6><i class="fas fa-star me-2"></i>Faculty Benefits</h6>
              <ul>
                <li><i class="fas fa-check"></i> Access to faculty dashboard</li>
                <li><i class="fas fa-check"></i> Manage academic profile</li>
                <li><i class="fas fa-check"></i> View research opportunities</li>
                <li><i class="fas fa-check"></i> Connect with students</li>
              </ul>
            </div>

            <div class="login-links">
              <a href="change-password.php" class="forgot-password">
                <i class="fas fa-key me-1"></i>
                Change Password?
              </a>
              <span class="text-muted">|</span>
              <a href="forgot-password.php" class="faculty-signup">
                <i class="fas fa-question-circle me-1"></i> Forgot Password
              </a>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  
  <script>
    // Initialize AOS
    AOS.init({
      duration: 800,
      easing: 'ease-in-out',
      once: true
    });

    /**
     * Toggles the visibility of the password field.
     */
    function togglePasswordVisibility() {
        const passwordField = document.getElementById('password');
        const toggleIcon = document.getElementById('password-toggle-icon');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
  </script>
</body>
</html>