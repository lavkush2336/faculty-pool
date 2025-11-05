<?php
// book-appointment.php
// Usage: book-appointment.php?id=FACULTY_ID
// Requires: db.php that defines $pdo (PDO connection)

require_once 'db.php'; // ensure this exists and creates $pdo

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// load faculty by id
$faculty = null;
$faculty_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($faculty_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM faculty WHERE id = ? LIMIT 1");
    $stmt->execute([$faculty_id]);
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
}

// fallback sample faculty (if DB missing or id not found)
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
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body { font-family: 'Poppins', sans-serif; background:#f8fafc; padding:28px 16px; color:#222; }
    .layout { max-width:1100px; margin:0 auto; display:flex; gap:28px; align-items:flex-start; }
    /* LEFT: large faculty flex-card (matches faculty-member.php) */
    .left-card {
      flex:0 0 600px;
      border-radius:16px;
      overflow:hidden;
      background:#fff;
      box-shadow:0 18px 40px rgba(0,0,0,0.06);
      transition:transform .2s ease;
    }
    .left-card:hover{ transform:translateY(-4px); }
    .faculty-card-big { display:flex; flex-direction:row; min-height:220px; }
    .faculty-image {
      flex:0 0 250px;
      min-width:220px;
      height: 270px;
      display:block;
      background:#eee;
      position:relative;
      border-right:5px solid #8B0000; /* requested right border */
      overflow:hidden;
    }
    .faculty-image img { width:100%; height:100%; object-fit:cover; display:block; }
    .faculty-body {
      flex:1; padding:22px 20px; display:flex; flex-direction:column; justify-content:center;
    }
    .faculty-name { font-size:1.15rem; font-weight:700; color:#8B0000; margin-bottom:4px; }
    .faculty-title { color:#444; margin-bottom:12px; }
    .label-strong { color:#8B0000; font-weight:700; margin-top:8px; margin-bottom:6px; display:block; font-size:.95rem; }
    .faculty-special { color:#333; line-height:1.5; }
    /* RIGHT: form */
    .form-card { flex:1; background:#fff; padding:22px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.04); }
    .form-card h4 { margin-bottom:14px; color:#222; }
    .form-control:focus { box-shadow:0 0 0 4px rgba(139,0,0,0.06); border-color:#8B0000; }
    .submit-btn { background:#8B0000; color:#fff; border:0; padding:10px 16px; border-radius:8px; }
    .error { background:#fff0f0; border:1px solid #f5c6cb; color:#8B0000; padding:10px; border-radius:8px; margin-bottom:12px; }
    .success { background:#edf7ee; border:1px solid #c3e6cb; color:#155724; padding:10px; border-radius:8px; margin-bottom:12px; }
    @media (max-width: 980px) {
      .layout{ flex-direction:column; padding:12px; }
      .left-card{ width:100%; }
      .faculty-image{ min-width:100%; height:240px; border-right:none; border-bottom:5px solid #8B0000; }
      .faculty-card-big{ flex-direction:column; }
    }
  </style>
</head>
<body>

<div class="layout">
  <!-- LEFT: styled faculty card (like faculty-member.php) -->
  <div class="left-card" aria-label="Faculty">
    <div class="faculty-card-big">
      <div class="faculty-image">
        <img src="<?php echo e($faculty['photo'] ?? 'images/ashima-singh.jpg'); ?>" alt="<?php echo e($faculty['name']); ?>">
      </div>
      <div class="faculty-body">
        <div class="faculty-name"><?php echo e($faculty['name']); ?></div>
        <div class="faculty-title"><?php echo e($faculty['title'] ?? $faculty['position'] ?? 'Associate Professor'); ?></div>

        <div>
          <div class="label-strong">Specialization</div>
          <div class="faculty-special"><?php echo nl2br(e($faculty['specialization'] ?? 'DevOps, Data Mining and machine intelligence, Software Engineering')); ?></div>
        </div>

        <div style="margin-top:10px;">
          <div class="label-strong">Email</div>
          <div><?php echo e($faculty['email']); ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT: booking form -->
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
</script>

</body>
</html>
