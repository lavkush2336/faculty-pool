<?php
// book-appointment.php
// Full page with site navbar, hero, footer + vertically stacked faculty card + booking form.
// Requires db.php (create $pdo)

require_once 'db.php'; // must provide $pdo (PDO)

// helper escape
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// --- 1. Load Faculty by ID from GET ---
$faculty_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$faculty = null;

if ($faculty_id) {
    // Select the necessary fields from the Faculty table, including SVH times
    $stmt = $pdo->prepare("
        SELECT faculty_id, first_name, last_name, department, expertise, Image, email, svh_start_time, svh_end_time
        FROM Faculty 
        WHERE faculty_id = :id LIMIT 1
    ");
    $stmt->execute([':id' => $faculty_id]);
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fallback if faculty not found or ID missing
if (!$faculty) {
    // Redirect to the faculty list page if the ID is invalid
    header('Location: all-faculty.php');
    exit;
}

// Consolidate name and use fallback for image
$faculty['full_name'] = e($faculty['first_name'] . ' ' . $faculty['last_name']);
$faculty['image_url'] = $faculty['Image'] ?: 'https://placehold.co/420x260/A52A2A/ffffff?text=Image%20Missing';

// NEW: Prepare formatted SVH hours for display (HH:MM - HH:MM)
$faculty['svh_hours_display'] = substr($faculty['svh_start_time'], 0, 5) . ' - ' . substr($faculty['svh_end_time'], 0, 5);

// --- 2. Handle POST for Appointment Booking ---
$errors = [];
$success = false;
$post_data = $_POST; // Store POST data to repopulate form

// Check if the database schema needs updating
// The table structure is expanded for new fields (student_email, contact_number, slot_time)
// AND new fields for IP address tracking and status management (IPAddress, status).
$pdo->exec("
CREATE TABLE IF NOT EXISTS appointments (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  faculty_id INT NOT NULL,
  student_name VARCHAR(255) NOT NULL,
  student_department VARCHAR(255) NOT NULL, 
  subgroup VARCHAR(16) NOT NULL,
  reason ENUM('paper related','doubt related','project related','other') NOT NULL,
  
  /* Existing NEW COLUMNS */
  student_email VARCHAR(255) NOT NULL,
  contact_number VARCHAR(15) NOT NULL, 
  slot_time TIME NOT NULL, 
  
  /* CUSTOM NEW COLUMNS FOR IP TRACKING AND STATUS */
  IPAddress VARCHAR(45) NOT NULL, /* Increased size for IPv6 compatibility */
  status ENUM('pending','approved','cancelled','done') NOT NULL DEFAULT 'pending', 
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faculty_id_post = isset($post_data['faculty_id']) ? (int)$post_data['faculty_id'] : 0;
    $student_name = trim($post_data['student_name'] ?? '');
    $student_department = trim($post_data['student_department'] ?? '');
    $subgroup = trim($post_data['subgroup'] ?? '');
    $reason = trim($post_data['reason'] ?? '');
    
    // NEW INPUTS
    $student_email = trim($post_data['student_email'] ?? '');
    $contact_number = trim($post_data['contact_number'] ?? '');
    $slot_time = trim($post_data['slot_time'] ?? ''); // HH:MM from form
    
    // CAPTURE IP ADDRESS - Use a function to get the real client IP (more robust for production)
    // For simplicity and immediate environment, we'll use REMOTE_ADDR.
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // --- Validation ---
    if ($faculty_id_post !== $faculty_id) $errors[] = "Security error: Faculty ID mismatch.";
    if ($student_name === '') $errors[] = "Student name is required.";
    if ($student_department === '') $errors[] = "Department is required.";
    if ($subgroup === '') $errors[] = "Subgroup is required.";
    
    // Email validation: must end with thapar.edu
    if (!filter_var($student_email, FILTER_VALIDATE_EMAIL) || !str_ends_with(strtolower($student_email), '@thapar.edu')) {
         $errors[] = "Student Email is invalid or must end with @thapar.edu.";
    }
    
    // Contact Number validation: must start with +91 and be 10 digits long (after +91)
    if (!preg_match('/^\+91[0-9]{10}$/', $contact_number)) {
        $errors[] = "Contact Number must be in the format +91XXXXXXXXXX (10 digits).";
    }

    // Slot time validation (unchanged)
    $full_slot_time = '';
    if ($slot_time === '') {
        $errors[] = "Appointment Time is required.";
    } else {
        // Simple HH:MM check
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $slot_time)) {
             $errors[] = "Appointment Time is invalid. Please use HH:MM format.";
        } else {
            $full_slot_time = $slot_time . ':00'; // Append seconds for proper database TIME comparison
            
            $start = $faculty['svh_start_time'];
            $end = $faculty['svh_end_time'];

            // Time comparison works directly with HH:MM:SS strings
            // Check if the selected time is within the range [start_time, end_time]
            if ($full_slot_time < $start || $full_slot_time > $end) {
                 $errors[] = "The selected time (" . e($slot_time) . ") is outside the faculty's available hours: " . e(substr($start, 0, 5)) . " to " . e(substr($end, 0, 5)) . ".";
            }
        }
    }
    
    $allowedReasons = ['paper related','doubt related','project related','other'];
    if (!in_array($reason, $allowedReasons, true)) $errors[] = "Please choose a valid reason.";
    
    // --- NEW IP ADDRESS LIMIT CHECK ---
    if (empty($errors)) {
        $limit = 1;
        // Count 'active' appointments (status is 'pending' or 'approved') for the current faculty from this IP
        $stmt_count = $pdo->prepare("
            SELECT COUNT(*) FROM appointments 
            WHERE IPAddress = :ip 
              AND faculty_id = :faculty_id
              AND status IN ('pending', 'approved') 
        ");
        $stmt_count->execute([':ip' => $ip_address, ':faculty_id' => $faculty_id_post]);
        $current_bookings = $stmt_count->fetchColumn();

        if ($current_bookings >= $limit) {
            $errors[] = "Attempts exceeded for now, try again later. You currently have " . $current_bookings . " active appointments with this faculty.";
        }
    }
    // --- END NEW IP ADDRESS LIMIT CHECK ---

    if (empty($errors)) {
        // --- Database Insertion (Updated to include IPAddress and status) ---
        $ins = $pdo->prepare("
            INSERT INTO appointments (
                faculty_id, student_name, department, subgroup, reason, student_email, contact_number, slot_time, IPAddress, status
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $ins->execute([
            $faculty_id_post, 
            $student_name, 
            $student_department, 
            $subgroup, 
            $reason,
            $student_email, 
            $contact_number, 
            $full_slot_time,
            $ip_address, // NEW FIELD
        ]);
        $success = true;
        // Clear post data on successful insertion to blank the form
        $post_data = []; 
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Book Appointment - <?php echo $faculty['full_name']; ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <style>
    /* minimal page-specific overrides while keeping your site's CSS intact */
    body { background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 50%, #f0f4f8 100%); color: #222; font-family: 'Poppins', sans-serif; }
    .page-inner { max-width: 1200px; margin: 0 auto; padding: 28px 16px; }

    /* layout */
    .layout { display:flex; gap:28px; align-items:flex-start; }
    @media (max-width: 980px) { .layout{ flex-direction:column } }

    /* left card (vertical stacked) — match faculty-member.php look */
    .left-card { flex: 0 0 420px; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 18px 40px rgba(0,0,0,0.06); }
    .faculty-card-vertical { display:flex; flex-direction:column; align-items:stretch; }
    .faculty-image { width:100%; height:260px; overflow:hidden; border-bottom:5px solid #8B0000; }
    .faculty-image img { 
        width:100%; 
        height:100%; 
        object-fit: cover; 
        display:block; 
        transition: transform 0.3s ease;
    }
    .faculty-image img:hover {
        transform: scale(1.05);
    }
    .faculty-body { padding:20px; }
    .faculty-name { font-size:1.6rem; font-weight:700; color:#8B0000; margin-bottom:6px; } /* Increased size */
    .faculty-title { color:#444; margin-bottom:12px; font-size:1rem; }
    .label-strong { color:#8B0000; font-weight:700; margin-top:8px; margin-bottom:6px; display:block; font-size:0.9rem; text-transform:uppercase; }
    .faculty-special { color:#333; line-height:1.5; font-size:0.95rem; }

    /* right form card */
    .form-card { flex:1; background:#fff; padding:30px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.04); }
    .form-card h4 { margin-bottom:20px; color:#8B0000; font-weight:700; font-size:1.5rem; }
    .form-control { border-radius: 8px; border: 1px solid #ddd; padding: 10px 15px; }
    .form-control:focus { box-shadow:0 0 0 4px rgba(139,0,0,0.06); border-color:#8B0000; }
    .submit-btn { background:#8B0000; color:#fff; border:0; padding:10px 20px; border-radius:8px; font-weight:600; transition: background-color 0.3s; }
    .submit-btn:hover { background: #A52A2A; }
    .error { background:#fff0f0; border:1px solid #f5c6cb; color:#8B0000; padding:10px; border-radius:8px; margin-bottom:12px; }
    .success { background:#edf7ee; border:1px solid #c3e6cb; color:#155724; padding:10px; border-radius:8px; margin-bottom:12px; }
    
    /* ---------------------------------------------------------------------- */
    /* NAV BAR STYLES (MATCHED TO SITE WIDE STYLES) */
    /* ---------------------------------------------------------------------- */
    .creative-nav { 
      background: #8B0000; /* Primary Dark Red/Maroon */
      box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
      position: sticky; 
      top: 0; 
      z-index: 1000; 
    }
    .creative-nav .nav-item { 
      color: #fff; /* White text */
      font-weight: 600; 
      text-decoration: none; 
      padding: 8px 12px; 
      transition: color 0.3s ease, background-color 0.3s ease; 
      border-radius: 6px; 
      display: inline-block;
    }
    .creative-nav .nav-item:hover {
      color: #fff; 
      background-color: rgba(0, 0, 0, 0.15); /* Slightly darker background on hover */
    }
    .creative-nav .nav-item.active {
      color: #fff; 
      background-color: #A52A2A; 
      font-weight: 700;
      position: relative;
    }

    /* ---------------------------------------------------------------------- */
    /* HERO SECTION STYLES (MATCHED TO SITE WIDE STYLES) */
    /* ---------------------------------------------------------------------- */
    .hero-section {
      position: relative;
      background: linear-gradient(135deg, #8B0000 0%, #B22222 100%); 
      color: #fff;
      padding: 60px 0 60px 0; 
      text-align: center;
      overflow: hidden;
      margin-bottom: 30px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    .hero-section .hero-content {
      position: relative;
      z-index: 10;
      max-width: 800px;
      margin: 0 auto;
      padding: 0 20px;
    }
    .hero-title {
      font-size: 3rem; 
      font-weight: 800;
      margin-bottom: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    .hero-subtitle {
      font-size: 1.25rem;
      font-weight: 300;
      margin-bottom: 1.5rem;
      opacity: 0.8;
    }
    .hero-decoration {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: 15px;
    }
    .decoration-line {
      width: 40px;
      height: 2px;
      background: #fff;
      opacity: 0.5;
    }
    .decoration-dot {
      width: 8px;
      height: 8px;
      background: #fff;
      border-radius: 50%;
      margin: 0 10px;
    }
    .hero-particles {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 1;
    }
    .particle {
      position: absolute;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      opacity: 0.6;
      animation: particle-float infinite ease-in-out;
    }
    /* Simplified particle animation definitions */
    .particle:nth-child(1) { width: 30px; height: 30px; top: 10%; left: 20%; animation-duration: 15s; animation-delay: 0s; }
    .particle:nth-child(2) { width: 50px; height: 50px; top: 50%; left: 80%; animation-duration: 20s; animation-delay: 5s; }
    .particle:nth-child(3) { width: 20px; height: 20px; top: 80%; left: 40%; animation-duration: 12s; animation-delay: 2s; }
    .particle:nth-child(4) { width: 40px; height: 40px; top: 20%; left: 90%; animation-duration: 18s; animation-delay: 8s; }
    .particle:nth-child(5) { width: 60px; height: 60px; top: 70%; left: 10%; animation-duration: 25s; animation-delay: 12s; }
    
    @keyframes particle-float {
      0% { transform: translate(0, 0) rotate(0deg); opacity: 0.6; }
      25% { transform: translate(20px, -20px) rotate(90deg); opacity: 0.7; }
      50% { transform: translate(0, 40px) rotate(180deg); opacity: 0.5; }
      75% { transform: translate(-20px, -20px) rotate(270deg); opacity: 0.6; }
      100% { transform: translate(0, 0) rotate(360deg); opacity: 0.6; }
    }
  </style>
</head>
<body>

  <nav class="creative-nav">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between items-center py-3">
        <div class="flex space-x-8">
          <a href="index.php" class="nav-item">HOME</a>
          <a href="department.php" class="nav-item">DEPARTMENTS</a>
        </div>

        <div class="flex space-x-8">
          <a href="faculty-member.php" class="nav-item active">FACULTY</a>
        </div>
      </div>
    </div>
  </nav>

  <section class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Book Appointment</h1>
      <p class="hero-subtitle">Request a meeting with <?php echo $faculty['full_name']; ?></p>
      <div class="hero-decoration">
        <div class="decoration-line"></div>
        <div class="decoration-dot"></div>
        <div class="decoration-line"></div>
      </div>
    </div>
    <div class="hero-particles">
      <div class="particle"></div>
      <div class="particle"></div>
      <div class="particle"></div>
      <div class="particle"></div>
      <div class="particle"></div>
    </div>
  </section>

  <div class="page-inner">
    <div class="layout">
      <div class="left-card" data-aos="fade-right" aria-label="Faculty Information">
        <div class="faculty-card-vertical">
          <div class="faculty-image">
            <img src="<?php echo $faculty['image_url']; ?>" alt="<?php echo $faculty['full_name']; ?>" onerror="this.onerror=null;this.src='https://placehold.co/420x260/A52A2A/ffffff?text=Image%20Missing';">
          </div>
          <div class="faculty-body">
            <div class="faculty-name"><?php echo $faculty['full_name']; ?></div>
            <div class="faculty-title"><i class="fas fa-building me-1"></i><?php echo e($faculty['department']); ?></div>

            <div>
              <div class="label-strong">Expertise/Specialization</div>
              <div class="faculty-special" style="color:#333; line-height:1.5;">
                  <?php echo nl2br(e($faculty['expertise'] ?? 'N/A')); ?>
              </div>
            </div>

            <div style="margin-top:10px;">
              <div class="label-strong">Student Visiting Hours (SVH)</div>
              <div class="faculty-special text-success font-bold" style="font-size:1rem;">
                  <?php echo e($faculty['svh_hours_display']); ?>
              </div>
            </div>

            <div style="margin-top:10px;">
              <div class="label-strong">Email</div>
              <div class="faculty-special"><?php echo e($faculty['email'] ?? 'Not provided'); ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-card" data-aos="fade-left">
        <h4>Book Appointment</h4>

        <?php if (!empty($errors)): ?>
          <div class="error">
            <ul style="margin:0 0 0 18px;">
              <?php foreach($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="success">
            <i class="fas fa-check-circle me-1"></i>Appointment booked successfully!
            </div>
        <?php endif; ?>

        <form method="post" id="appointmentForm" novalidate>
          <input type="hidden" name="faculty_id" value="<?php echo $faculty['faculty_id']; ?>">

          <div class="mb-3">
            <label class="form-label">Student Name</label>
            <input type="text" name="student_name" class="form-control" required value="<?php echo e($post_data['student_name'] ?? ''); ?>" placeholder="Your full name">
          </div>

          <div class="mb-3">
            <label class="form-label">Student Email (<span class="text-red-700 font-bold">@thapar.edu required</span>)</label>
            <input type="email" name="student_email" class="form-control" required value="<?php echo e($post_data['student_email'] ?? ''); ?>" placeholder="example@thapar.edu">
          </div>
          
          <div class="mb-3">
            <label class="form-label">Contact Number (<span class="text-red-700 font-bold">+91XXXXXXXXXX required</span>)</label>
            <input type="tel" name="contact_number" class="form-control" required value="<?php echo e($post_data['contact_number'] ?? ''); ?>" 
                   pattern="^\+91[0-9]{10}$" title="Format: +91 followed by 10 digits" placeholder="+91XXXXXXXXXX">
          </div>

          <div class="mb-3">
            <label class="form-label">Appointment Time (HH:MM) - <span class="text-red-700 font-bold">Must be between <?php echo substr($faculty['svh_start_time'], 0, 5) . ' and ' . substr($faculty['svh_end_time'], 0, 5); ?></span></label>
            <input type="time" name="slot_time" class="form-control" required 
                   value="<?php echo e($post_data['slot_time'] ?? ''); ?>" placeholder="HH:MM" 
                   min="<?php echo substr($faculty['svh_start_time'], 0, 5); ?>" 
                   max="<?php echo substr($faculty['svh_end_time'], 0, 5); ?>">
          </div>
          
          <div class="mb-3">
            <label class="form-label">Student Department (e.g., CSE, ME)</label>
            <input type="text" name="student_department" class="form-control" required value="<?php echo e($post_data['student_department'] ?? ''); ?>"
                   title="Department is required" placeholder="e.g. Computer Science">
          </div>

          <div class="mb-3">
            <label class="form-label">Subgroup (e.g., A1B2)</label>
            <input type="text" name="subgroup" class="form-control" required value="<?php echo e($post_data['subgroup'] ?? ''); ?>"
                   pattern="^[A-Za-z0-9]{4}$" title="Exactly 4 letters/numbers" maxlength="4" placeholder="A1B2">
          </div>

          <div class="mb-3">
            <label class="form-label">Reason for Appointment</label>
            <select name="reason" class="form-select" required>
              <option value="">-- Select reason --</option>
              <option value="paper related" <?php if(($post_data['reason'] ?? '')==='paper related') echo 'selected'; ?>>Paper related</option>
              <option value="doubt related" <?php if(($post_data['reason'] ?? '')==='doubt related') echo 'selected'; ?>>Doubt related</option>
              <option value="project related" <?php if(($post_data['reason'] ?? '')==='project related') echo 'selected'; ?>>Project related</option>
              <option value="other" <?php if(($post_data['reason'] ?? '')==='other') echo 'selected'; ?>>Other</option>
            </select>
          </div>

          <div class="d-flex justify-content-end">
            <button type="submit" class="submit-btn">
              <i class="fas fa-calendar-check me-2"></i>Book Appointment
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <footer class="creative-footer mt-5">
    <div class="max-w-7xl mx-auto px-4 py-8 text-center">
      <p>&copy; <?php echo date('Y'); ?> Thapar Institute of Engineering & Technology. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

  <script>
    // client validation for friendly UX
    document.getElementById('appointmentForm').addEventListener('submit', function(e){
      const subgroup = this.subgroup.value.trim();
      const studentEmail = this.student_email.value.trim();
      const contactNumber = this.contact_number.value.trim();
      const slotTime = this.slot_time.value.trim(); // NEW

      // Pass PHP variables to JS for client-side validation
      const startTime = "<?php echo substr($faculty['svh_start_time'], 0, 5); ?>"; // NEW
      const endTime = "<?php echo substr($faculty['svh_end_time'], 0, 5); ?>"; // NEW

      // 1. Subgroup validation
      if (!/^[A-Za-z0-9]{4}$/.test(subgroup)) {
        alert("Subgroup must be exactly 4 letters/numbers (e.g., A1B2).");
        e.preventDefault(); return;
      }
      
      // 2. Email constraint validation
      if (!studentEmail.toLowerCase().endsWith('@thapar.edu')) {
        alert("Student Email must end with @thapar.edu.");
        e.preventDefault(); return;
      }
      
      // 3. Contact Number constraint validation (+91XXXXXXXXXX)
      if (!/^\+91[0-9]{10}$/.test(contactNumber)) {
        alert("Contact Number must be in the format +91XXXXXXXXXX (10 digits).");
        e.preventDefault(); return;
      }

      // 4. Slot Time validation
      if (slotTime === '') {
        alert("Appointment Time is required.");
        e.preventDefault(); return;
      }

      // Simple HH:MM string comparison is sufficient for the same day
      if (slotTime < startTime || slotTime > endTime) {
        alert("Appointment Time must be between " + startTime + " and " + endTime + ".");
        e.preventDefault(); return;
      }
    });

    AOS && AOS.init && AOS.init({ duration: 700, easing: 'ease-in-out', once: true, offset: 100 });
  </script>
</body>
</html>