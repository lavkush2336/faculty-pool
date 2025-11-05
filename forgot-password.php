<?php
// forgot-password.php -> Change Password page combining email and password fields
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Password - Faculty Pool</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 50%, #f0f4f8 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .forgot-container {
      width: 100%;
      max-width: 450px;
      padding: 20px;
    }

    .forgot-card {
      background: white;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      position: relative;
    }

    .forgot-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #8B0000, #A52A2A, #8B0000);
      border-radius: 20px 20px 0 0;
    }

    .header {
      text-align: center;
      margin-bottom: 30px;
    }

    .header i {
      font-size: 3rem;
      color: #8B0000;
      margin-bottom: 15px;
    }

    .header h2 {
      color: #8B0000;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .header p {
      color: #666;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 20px;
      position: relative; /* For error messages and input container */
    }

    .form-label {
      color: #8B0000;
      font-weight: 600;
      margin-bottom: 8px;
      display: block;
    }
    
    /* Container for input and toggle button */
    .password-input-group {
        position: relative;
    }

    .form-control {
      width: 100%;
      padding: 15px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      font-size: 16px;
      transition: all 0.3s ease;
      background: white;
      pointer-events: auto;
    }

    .form-control:focus {
      outline: none;
      border-color: #8B0000;
      box-shadow: 0 0 0 3px rgba(139, 0, 0, 0.1);
    }
    
    .form-control.is-invalid {
      border-color: #dc3545; /* Bootstrap red */
    }

    .form-control::placeholder {
      color: #999;
    }
    
    /* Show/Hide Password Button Styling */
    .password-toggle {
        position: absolute;
        top: 50%;
        right: 15px;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 5px;
        transition: color 0.2s ease;
        z-index: 10; /* Ensure button is clickable */
    }
    
    .password-toggle:hover {
        color: #8B0000;
    }


    .submit-btn {
      width: 100%;
      background: linear-gradient(135deg, #8B0000 0%, #A52A2A 100%);
      color: white;
      border: none;
      border-radius: 10px;
      padding: 15px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-bottom: 20px;
      pointer-events: auto;
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(139, 0, 0, 0.1);
    }

    .submit-btn:active {
      transform: translateY(0);
    }

    .back-link {
      position: absolute;
      top: 20px;
      left: 20px;
      color: #8B0000;
      text-decoration: none;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      pointer-events: auto;
      cursor: pointer;
    }

    .back-link:hover {
      color: #A52A2A;
      transform: translateX(-3px);
    }

    .decoration {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin: 20px 0;
    }

    .decoration-line {
      width: 30px;
      height: 2px;
      background: linear-gradient(90deg, transparent, #8B0000, transparent);
    }

    .decoration-dot {
      width: 6px;
      height: 6px;
      background: #8B0000;
      border-radius: 50%;
    }

    .login-links {
      text-align: center;
      margin-top: 20px;
    }

    .login-links a {
      color: #8B0000;
      text-decoration: none;
      font-weight: 500;
      margin: 0 10px;
      transition: color 0.3s ease;
      pointer-events: auto;
      cursor: pointer;
    }

    .login-links a:hover {
      color: #A52A2A;
    }

    .info-box {
      background: rgba(139, 0, 0, 0.05);
      border: 1px solid rgba(139, 0, 0, 0.1);
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
    }

    .info-box p {
      color: #666;
      font-size: 14px;
      margin: 0;
    }

    .info-box i {
      color: #8B0000;
      margin-right: 8px;
    }
    
    .password-error {
        color: #dc3545;
        font-size: 12px;
        margin-top: 5px;
        display: none;
        list-style: none;
        padding-left: 0;
    }
    
    .password-error li {
        margin-top: 3px;
    }

    /* Ensure all interactive elements work */
    input, button, a, select, textarea {
      pointer-events: auto !important;
      -webkit-user-select: auto !important;
      user-select: auto !important;
    }
  </style>
</head>
<body>
  <a href="faculty-login.php" class="back-link">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Login</span>
  </a>

  <div class="forgot-container">
    <div class="forgot-card">
      
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

        <form id="changePasswordForm" method="post" action="#">
          
          <!-- Email Address -->
          <div class="form-group">
            <label for="email" class="form-label">
              <i class="fas fa-envelope"></i> Email Address
            </label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email address" required>
          </div>
          
          <!-- Old Password -->
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

          <!-- New Password -->
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
                <li><i class="fas fa-times-circle"></i> Must be at least 8 characters long</li>
                <li><i class="fas fa-times-circle"></i> Must contain at least one number (0-9)</li>
                <li><i class="fas fa-times-circle"></i> Must contain at least one special character (!@#$...)</li>
            </ul>
          </div>

          <!-- Confirm New Password -->
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

      <!-- Login Links -->
      <div class="login-links">
        <a href="faculty-login.php">
          <i class="fas fa-sign-in-alt"></i> Back to Login
        </a>
      </div>
    </div>
  </div>

  <script>
    // --- Utility Function: Password Toggle ---
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

    // --- Validation Logic ---

    const form = document.getElementById('changePasswordForm');
    const emailInput = document.getElementById('email');
    const oldPasswordInput = document.getElementById('oldPassword');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmNewPasswordInput = document.getElementById('confirmNewPassword');
    const validationList = document.getElementById('passwordValidationList');
    const matchError = document.getElementById('matchError');
    const validationItems = validationList.querySelectorAll('li');

    // Regex for password constraints
    const regex = {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        length: /.{8,}/,
        numeric: /[0-9]/,
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/,
    };

    function validatePassword(password) {
        return {
            length: regex.length.test(password),
            numeric: regex.numeric.test(password),
            special: regex.special.test(password),
        };
    }

    function updateValidationDisplay() {
        const newPassword = newPasswordInput.value;
        const results = validatePassword(newPassword);
        let isValid = true;
        
        // Show validation list only when typing
        validationList.style.display = newPassword.length > 0 ? 'block' : 'none';

        // Update list items
        Object.keys(results).forEach((key, index) => {
            const item = validationItems[index];
            const icon = item.querySelector('i');
            
            if (results[key]) {
                item.style.color = '#28a745'; // Green (Success)
                icon.className = 'fas fa-check-circle';
            } else {
                item.style.color = '#dc3545'; // Red (Error)
                icon.className = 'fas fa-times-circle';
                isValid = false;
            }
        });
        
        // Check for password match
        const passwordsMatch = newPassword === confirmNewPasswordInput.value;
        
        if (confirmNewPasswordInput.value.length > 0 && !passwordsMatch) {
            matchError.style.display = 'block';
            confirmNewPasswordInput.classList.add('is-invalid');
            isValid = false;
        } else {
            matchError.style.display = 'none';
            confirmNewPasswordInput.classList.remove('is-invalid');
        }

        // Apply invalid class to new password input if constraints fail
        if (newPassword.length > 0 && !isValid) {
            newPasswordInput.classList.add('is-invalid');
        } else {
             newPasswordInput.classList.remove('is-invalid');
        }

        // Return overall validity
        return isValid && passwordsMatch && newPassword.length > 0;
    }

    // Attach event listeners for real-time feedback
    newPasswordInput.addEventListener('input', updateValidationDisplay);
    confirmNewPasswordInput.addEventListener('input', updateValidationDisplay);

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = emailInput.value;
        const oldPassword = oldPasswordInput.value;
        const newPassword = newPasswordInput.value;

        // 1. Validate Email
        if (!regex.email.test(email)) {
            emailInput.classList.add('is-invalid');
            showCustomMessage('Please enter a valid email address.', 'Error');
            return;
        }
        emailInput.classList.remove('is-invalid');

        // 2. Validate New Password constraints and match
        const validationPassed = updateValidationDisplay();

        if (!validationPassed) {
            showCustomMessage('Please correct the errors in the New Password fields before proceeding.', 'Error');
            return;
        }
        
        // 3. Check against old password
        if (oldPassword === newPassword) {
            showCustomMessage('New Password cannot be the same as the Old Password.', 'Error');
            return;
        }
        
        // --- Simulate Form Submission ---
        const submitBtn = document.getElementById('passwordSubmitBtn');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        submitBtn.disabled = true;
        
        setTimeout(() => {
            showCustomMessage('Your password has been successfully updated! Redirecting to login...', 'Success');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            // Simulate redirection
            setTimeout(() => {
                // window.location.href = 'faculty-login.php'; 
                form.reset();
                validationList.style.display = 'none';
            }, 1000);
            
        }, 1500);
    });
    
    // --- Custom Message Box ---
    function showCustomMessage(message, type) {
        const existingMessage = document.getElementById('customMessageBox');
        if (existingMessage) existingMessage.remove();
        
        const messageClass = type === 'Success' ? 'alert-success' : 'alert-danger';
        
        const box = document.createElement('div');
        box.id = 'customMessageBox';
        box.className = `alert ${messageClass} mt-3 fixed top-0 w-full z-50 text-center`;
        box.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; margin: 20px auto; max-width: 400px; z-index: 1000;';
        box.innerHTML = `
            <button type="button" class="btn-close float-end" aria-label="Close" onclick="this.parentNode.remove()"></button>
            <strong>${type}:</strong> ${message}
        `;
        document.body.appendChild(box);

        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (document.getElementById('customMessageBox')) {
                document.getElementById('customMessageBox').remove();
            }
        }, 5000);
    }
  </script>
</body>
</html>