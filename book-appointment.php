<?php
// book-appointment.php
// Usage: book-appointment.php?id=FACULTY_ID
// Requires: db.php (must create $pdo = new PDO(...))
// If you don't have db.php, uncomment and edit the example below.

/*
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=faculty_pool;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("DB connection failed: " . $e->getMessage());
}
*/

require_once 'db.php'; // <-- make sure this file exists and creates $pdo

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// fetch faculty by id (top-level code)
$faculty = null;
$faculty_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($faculty_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM faculty WHERE id = ? LIMIT 1"); // adjust table name if different
    $stmt->execute([$faculty_id]);
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
}

// if faculty not found, show fallback sample (but still allow booking)
if (!$faculty) {
    $faculty = [
        'id' => 0,
        'name' => 'Dr. Ashima Singh',
        'title' => 'Associate Professor',
        'specialization' => 'DevOps, Data Mining and Machine Intelligence, Software Engineering',
        'email' => 'ashima@thapar.edu',
        'photo' => 'images/ashima-singh.jpg', // adjust path
        // you can add more fields as needed
    ];
}

// create appointments table if not exists
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
    // collect and trim
    $faculty_id_post = isset($_POST['faculty_id']) ? (int)$_POST['faculty_id'] : 0;
    $student_name = trim($_POST['student_name'] ?? '');
    $student_email = trim($_POST['student_email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $subgroup = trim($_POST['subgroup'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    // server-side validation
    if ($student_name === '') {
        $errors[] = "Student name is required.";
    }

    if ($student_email === '' || !filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    } else {
        // must end with thapa.edu (case-insensitive)
        if (!preg_match('/@thapa\.edu$/i', $student_email)) {
            $errors[] = "Email must end with '@thapa.edu'.";
        }
    }

    if ($department === '' || !preg_match('/^[A-Za-z ]+$/', $department)) {
        $errors[] = "Department is required and must contain only letters and spaces.";
    }

    // subgroup should be exactly 4 alphanumeric characters (as requested)
    if ($subgroup === '' || !preg_match('/^[A-Za-z0-9]{4}$/', $subgroup)) {
        $errors[] = "Subgroup is required and must be exactly 4 letters/numbers (e.g. A1B2).";
    }

    $allowed = ['paper related','doubt related','project related','other'];
    if (!in_array($reason, $allowed, true)) {
        $errors[] = "Please choose a valid reason.";
    }

    if (empty($errors)) {
        // insert appointment
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
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Book Appointment</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body{font-family:'Poppins',sans-serif;background:#f8fafc;padding:30px;}
    .container-grid{display:flex;gap:30px;max-width:1100px;margin:0 auto;align-items:flex-start;}
    /* Left: faculty card */
    .faculty-card{
      flex:0 0 360px;
      background:white;border-radius:10px;padding:18px;border:1px solid #dedede;
      box-shadow:0 6px 20px rgba(0,0,0,0.04);
    }
    .faculty-image{width:120px;height:120px;overflow:hidden;border-radius:6px;display:inline-block;vertical-align:top;margin-right:16px;border-right:5px solid #8B0000;}
    .faculty-image img{width:120px;height:120px;object-fit:cover;display:block;border:0;}
    .faculty-meta{display:inline-block;vertical-align:top;max-width:200px;}
    .faculty-name{font-weight:700;color:#222;font-size:1.05rem;margin-bottom:2px;}
    .faculty-title{color:#555;margin-bottom:12px;}
    .label-strong{color:#8B0000;font-weight:700;margin-top:10px;margin-bottom:6px;display:block;}
    /* Right: form area */
    .form-card{flex:1;background:white;padding:22px;border-radius:10px;border:1px solid #dedede;}
    .form-group label{font-weight:600;}
    .error{color:#b00020;background:#ffeef0;padding:10px;border-radius:6px;border:1px solid #f5c6cb;margin-bottom:12px;}
    .success{color:#155724;background:#d4edda;padding:10px;border-radius:6px;border:1px solid #c3e6cb;margin-bottom:12px;}
    .submit-btn{background:#8B0000;color:white;border:0;padding:10px 18px;border-radius:6px;}
    @media(max-width:900px){
      .container-grid{flex-direction:column;padding:10px}
      .faculty-card{width:100%;display:flex;align-items:center}
      .faculty-image{margin-right:12px}
    }
  </style>
</head>
<body>

<div class="container-grid">
  <!-- LEFT: faculty card -->
  <div class="faculty-card" aria-label="Faculty details">
    <div style="display:flex;align-items:flex-start;">
      <div class="faculty-image">
        <img src="<?php echo e($faculty['photo'] ?? 'images/ashima-singh.jpg'); ?>" alt="<?php echo e($faculty['name']); ?>">
      </div>
      <div class="faculty-meta">
        <div class="faculty-name"><?php echo e($faculty['name']); ?></div>
        <div class="faculty-title"><?php echo e($faculty['title'] ?? $faculty['position'] ?? 'Associate Professor'); ?></div>
        <div class="label-strong">Specialization</div>
        <div style="color:#333;"><?php echo nl2br(e($faculty['specialization'] ?? 'DevOps, Data Mining and machine intelligence, Software Engineering')); ?></div>
        <div class="label-strong">Email</div>
        <div style="color:#333;"><?php echo e($faculty['email']); ?></div>
      </div>
    </div>
  </div>

  <!-- RIGHT: booking form -->
  <div class="form-card">
    <h4 style="margin-bottom:12px">Book Appointment</h4>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <ul style="margin:0 0 0 18px;padding:0;">
          <?php foreach($errors as $err): ?>
            <li><?php echo e($err); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success">
        Appointment booked successfully! The faculty will be notified by email (demo).<br>
        <strong>Student:</strong> <?php echo e($_POST['student_name']); ?><br>
        <strong>Email:</strong> <?php echo e($_POST['student_email']); ?><br>
        <strong>Reason:</strong> <?php echo e($_POST['reason']); ?>
      </div>
    <?php endif; ?>

    <form method="post" novalidate id="appointmentForm">
      <!-- include faculty id -->
      <input type="hidden" name="faculty_id" value="<?php echo e($faculty['id'] ?? 0); ?>">

      <div class="mb-3">
        <label class="form-label">Student Name</label>
        <input type="text" name="student_name" required class="form-control" value="<?php echo e($_POST['student_name'] ?? ''); ?>" placeholder="Full name">
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="student_email" required class="form-control" value="<?php echo e($_POST['student_email'] ?? ''); ?>"
               pattern="^[^\s@]+@thapa\.edu$" title="Email must end with @thapa.edu"
               placeholder="you@thapa.edu">
      </div>

      <div class="mb-3">
        <label class="form-label">Department</label>
        <input type="text" name="department" required class="form-control" value="<?php echo e($_POST['department'] ?? ''); ?>"
               pattern="^[A-Za-z ]+$" title="Only letters and spaces allowed" placeholder="e.g. Computer Science">
      </div>

      <div class="mb-3">
        <label class="form-label">Subgroup</label>
        <input type="text" name="subgroup" required class="form-control" value="<?php echo e($_POST['subgroup'] ?? ''); ?>"
               pattern="^[A-Za-z0-9]{4}$" title="Exactly 4 letters/numbers" placeholder="e.g. A1B2" maxlength="4">
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

<script>
  // client-side extra validation feedback
  document.getElementById('appointmentForm').addEventListener('submit', function(e){
    const email = this.student_email.value.trim();
    if (!/^[^\s@]+@thapa\.edu$/i.test(email)) {
      alert("Email must end with @thapa.edu");
      e.preventDefault();
      return;
    }
    const dept = this.department.value.trim();
    if (!/^[A-Za-z ]+$/.test(dept)) {
      alert("Department must contain only letters and spaces.");
      e.preventDefault();
      return;
    }
    const subgroup = this.subgroup.value.trim();
    if (!/^[A-Za-z0-9]{4}$/.test(subgroup)) {
      alert("Subgroup must be exactly 4 letters/numbers.");
      e.preventDefault();
      return;
    }
  });
</script>

</body>
</html>
