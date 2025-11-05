<?php
// book-appointment.php
// Full page with site navbar, hero, footer + vertically stacked faculty card + booking form.
// Requires db.php (create $pdo)

require_once 'db.php'; // must provide $pdo (PDO)

// helper escape
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// load faculty by id from GET
$faculty = null;
$faculty_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($faculty_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM faculty WHERE id = ? LIMIT 1");
    $stmt->execute([$faculty_id]);
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
}

// fallback faculty
if (!$faculty) {
    $faculty = [
        'id' => 0,
        'name' => 'Dr. Ashima Singh',
        'title' => 'Associate Professor',
        'specialization' => 'DevOps, Data Mining and Machine Intelligence, Software Engineering',
        'email' => 'ashima@thapar.edu',
        'photo' => 'images/ashima-singh.jpg',
    ];
}

// ensure appointments table exists
$pdo->exec("
CREATE TABLE IF NOT EXISTS appointments (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  faculty_id INT NOT NULL,
  student_name VARCHAR(255) NOT NULL,
  student_email VARCHAR(255) NOT NULL,
  department VARCHAR(255) NOT NULL,
  subgroup VARCHAR(16) NOT NULL,
  reason ENUM('paper related','doubt related','project related','other') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// handle POST
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faculty_id_post = isset($_POST['faculty_id']) ? (int)$_POST['faculty_id'] : 0;
    $student_name = trim($_POST['student_name'] ?? '');
    $student_email = trim($_POST['student_email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $subgroup = trim($_POST['subgroup'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if ($student_name === '') $errors[] = "Student name is required.";

    if ($student_email === '' || !filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    } else {
        if (!preg_match('/@thapa\.edu$/i', $student_email)) {
            $errors[] = "Email must end with '@thapa.edu'.";
        }
    }

    if ($department === '' || !preg_match('/^[A-Za-z ]+$/', $department)) {
        $errors[] = "Department is required and must contain only letters and spaces.";
    }

    if ($subgroup === '' || !preg_match('/^[A-Za-z0-9]{4}$/', $subgroup)) {
        $errors[] = "Subgroup is required and must be exactly 4 letters/numbers (e.g. A1B2).";
    }

    $allowed = ['paper related','doubt related','project related','other'];
    if (!in_array($reason, $allowed, true)) $errors[] = "Please choose a valid reason.";

    if (empty($errors)) {
        $ins = $pdo->prepare("INSERT INTO appointments (faculty_id, student_name, student_email, department, subgroup, reason) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([$faculty_id_post, $student_name, $student_email, $department, $subgroup, $reason]);
        $success = true;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Book Appointment - <?php echo e($faculty['name']); ?></title>

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
    .faculty-image img { width:100%; height:100%; object-fit:cover; display:block; }
    .faculty-body { padding:20px; }
    .faculty-name { font-size:1.15rem; font-weight:700; color:#8B0000; margin-bottom:6px; }
    .faculty-title { color:#444; margin-bottom:12px; }
    .label-strong { color:#8B0000; font-weight:700; margin-top:8px; margin-bottom:6px; display:block; }

    /* right form card */
    .form-card { flex:1; background:#fff; padding:22px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.04); }
    .form-card h4 { margin-bottom:14px; color:#222; }
    .form-control:focus { box-shadow:0 0 0 4px rgba(139,0,0,0.06); border-color:#8B0000; }
    .submit-btn { background:#8B0000; color:#fff; border:0; padding:10px 16px; border-radius:8px; }
    .error { background:#fff0f0; border:1px solid #f5c6cb; color:#8B0000; padding:10px; border-radius:8px; margin-bottom:12px; }
    .success { background:#edf7ee; border:1px solid #c3e6cb; color:#155724; padding:10px; border-radius:8px; margin-bottom:12px; }

    /* ---------------------------------------------------------------------- */
    /* NAV BAR STYLES (EXACT MATCH TO INDEX.PHP) */
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
    /* FIX: Active button should look like a highlighted button on a dark background */
    .creative-nav .nav-item.active {
      color: #fff; /* White text */
      background-color: #A52A2A; /* Slightly lighter/different maroon for contrast */
      font-weight: 700;
      position: relative;
    }
    /* FIX: Remove the non-matching indicator from the bottom */
    .creative-nav .nav-item.active::after {
      content: none;
    }

    /* ---------------------------------------------------------------------- */
    /* HERO SECTION STYLES (EXACT MATCH TO INDEX.PHP) */
    /* ---------------------------------------------------------------------- */
    .hero-section {
      position: relative;
      /* Using the exact gradient implied by index.php's visual style */
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
      font-size: 3rem; /* Adjusted for visual match */
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
    
    /* Hero Particles Styling & Animation */
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
    .particle:nth-child(1) {
      width: 30px; height: 30px; top: 10%; left: 20%;
      animation-duration: 15s; animation-delay: 0s;
    }
    .particle:nth-child(2) {
      width: 50px; height: 50px; top: 50%; left: 80%;
      animation-duration: 20s; animation-delay: 5s;
    }
    .particle:nth-child(3) {
      width: 20px; height: 20px; top: 80%; left: 40%;
      animation-duration: 12s; animation-delay: 2s;
    }
    .particle:nth-child(4) {
      width: 40px; height: 40px; top: 20%; left: 90%;
      animation-duration: 18s; animation-delay: 8s;
    }
    .particle:nth-child(5) {
      width: 60px; height: 60px; top: 70%; left: 10%;
      animation-duration: 25s; animation-delay: 12s;
    }
    
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
          <a href="department.php" class="nav-item">PROGRAMS</a>
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
      <p class="hero-subtitle">Request a meeting with <?php echo e($faculty['name']); ?></p>
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
      <div class="left-card" aria-label="Faculty">
        <div class="faculty-card-vertical">
          <div class="faculty-image">
            <img src="<?php echo e($faculty['photo'] ?? 'images/ashima-singh.jpg'); ?>" alt="<?php echo e($faculty['name']); ?>">
          </div>
          <div class="faculty-body">
            <div class="faculty-name"><?php echo e($faculty['name']); ?></div>
            <div class="faculty-title"><?php echo e($faculty['title'] ?? $faculty['position'] ?? 'Associate Professor'); ?></div>

            <div>
              <div class="label-strong">Specialization</div>
              <div class="faculty-special" style="color:#333; line-height:1.5;"><?php echo nl2br(e($faculty['specialization'] ?? 'DevOps, Data Mining and machine intelligence, Software Engineering')); ?></div>
            </div>

            <div style="margin-top:10px;">
              <div class="label-strong">Email</div>
              <div><?php echo e($faculty['email']); ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-card">
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
            Appointment booked successfully!<br>
            <strong>Student:</strong> <?php echo e($_POST['student_name']); ?><br>
            <strong>Email:</strong> <?php echo e($_POST['student_email']); ?><br>
            <strong>Reason:</strong> <?php echo e($_POST['reason']); ?>
          </div>
        <?php endif; ?>

        <form method="post" id="appointmentForm" novalidate>
          <input type="hidden" name="faculty_id" value="<?php echo e($faculty['id'] ?? 0); ?>">

          <div class="mb-3">
            <label class="form-label">Student Name</label>
            <input type="text" name="student_name" class="form-control" required value="<?php echo e($_POST['student_name'] ?? ''); ?>" placeholder="Full name">
          </div>

          <div class="mb-3">
            <label class="form-label">Email (must end with <code>@thapa.edu</code>)</label>
            <input type="email" name="student_email" class="form-control" required value="<?php echo e($_POST['student_email'] ?? ''); ?>"
                   pattern="^[^\s@]+@thapa\.edu$" title="Email must end with @thapa.edu" placeholder="you@thapa.edu">
          </div>

          <div class="mb-3">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control" required value="<?php echo e($_POST['department'] ?? ''); ?>"
                   pattern="^[A-Za-z ]+$" title="Only letters and spaces allowed" placeholder="e.g. Computer Science">
          </div>

          <div class="mb-3">
            <label class="form-label">Subgroup (exactly 4 letters/numbers)</label>
            <input type="text" name="subgroup" class="form-control" required value="<?php echo e($_POST['subgroup'] ?? ''); ?>"
                   pattern="^[A-Za-z0-9]{4}$" title="Exactly 4 letters/numbers" maxlength="4" placeholder="A1B2">
          </div>

          <div class="mb-3">
            <label class="form-label">Reason</label>
            <select name="reason" class="form-select" required>
              <option value="">-- Select reason --</option>
              <option value="paper related" <?php if(($_POST['reason'] ?? '')==='paper related') echo 'selected'; ?>>Paper related</option>
              <option value="doubt related" <?php if(($_POST['reason'] ?? '')==='doubt related') echo 'selected'; ?>>Doubt related</option>
              <option value="project related" <?php if(($_POST['reason'] ?? '')==='project related') echo 'selected'; ?>>Project related</option>
              <option value="other" <?php if(($_POST['reason'] ?? '')==='other') echo 'selected'; ?>>Other</option>
            </select>
          </div>

          <div class="d-flex justify-content-end">
            <button type="submit" class="submit-btn">Book Appointment</button>
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
      const email = this.student_email.value.trim();
      if (!/^[^\s@]+@thapa\.edu$/i.test(email)) {
        alert("Email must end with @thapa.edu");
        e.preventDefault(); return;
      }
      const dept = this.department.value.trim();
      if (!/^[A-Za-z ]+$/.test(dept)) {
        alert("Department must contain only letters and spaces.");
        e.preventDefault(); return;
      }
      const subgroup = this.subgroup.value.trim();
      if (!/^[A-Za-z0-9]{4}$/.test(subgroup)) {
        alert("Subgroup must be exactly 4 letters/numbers.");
        e.preventDefault(); return;
      }
    });

    AOS && AOS.init && AOS.init({ duration: 700, easing: 'ease-in-out', once: true, offset: 100 });
  </script>
</body>
</html>