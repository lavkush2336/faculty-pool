<?php
// faculty-projects-redesign.php
session_start();
require_once 'db.php'; // keep your DB connection

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: teacher-login.php');
    exit;
}

$faculty_id = $_SESSION['faculty_id'];
// Updated: Fetch faculty name from session for display
$faculty_name = $_SESSION['faculty_name'] ?? 'Faculty Member'; 
$message_html = '';
$departments = []; // Array to hold department names
$view_mode = $_GET['view'] ?? 'projects'; // 'projects' or 'applications'

// --- START: DEPARTMENT FETCHING ---
try {
    // Fetch all department names from the departments table
    $stmt = $pdo->prepare("SELECT department_name FROM departments ORDER BY department_name ASC");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching departments: " . $e->getMessage());
    $message_html = alert_html('Warning: Could not load department list from the database.', 'warning');
}
// --- END: DEPARTMENT FETCHING ---

// Allowed project file types (omitted for brevity)
$allowed_project_types = ['application/zip', 'application/x-zip-compressed', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'project_bid' . DIRECTORY_SEPARATOR; 
$web_upload_dir = 'project_bid/'; 

if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}
$submission_success = isset($_GET['success']) && $_GET['success'] == 1;

// --- START: DELETION LOGIC (omitted for brevity) ---
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = filter_input(INPUT_GET, 'delete_id', FILTER_SANITIZE_NUMBER_INT);

    try {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = :id AND faculty_id = :faculty_id");
        $stmt->execute([':id' => $delete_id, ':faculty_id' => $faculty_id]);

        if ($stmt->rowCount()) {
            header('Location: faculty-projects.php?success=2'); 
            exit;
        } else {
            $message_html = alert_html('Error: Project not found or unauthorized to delete.', 'danger');
        }
    } catch (PDOException $e) {
        error_log('Research and Projects Delete Error: ' . $e->getMessage());
        $message_html = alert_html('Database Deletion Error: ' . $e->getMessage(), 'danger');
    }
}
// --- END: DELETION LOGIC ---


// --- START: Database Update/Insertion Logic (omitted for brevity) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_research_and_projects'])) { 
    
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
    $department_name = trim(filter_input(INPUT_POST, 'department_name', FILTER_SANITIZE_STRING)); 
    $expertise = trim(filter_input(INPUT_POST, 'expertise', FILTER_SANITIZE_STRING));
    $type = trim(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING)); 
    
    $file_path = null; 
    $upload_success = true;
    $file_input_name = 'research_and_projects_file';

    // 1. Validation check
    if ($name === '' || $description === '' || $department_name === '' || $expertise === '' || $type === '') {
        $message_html = alert_html('Please fill in all required text fields.', 'danger');
        $upload_success = false;
    }

    // 2. File Upload Handling 
    if ($upload_success && isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES[$file_input_name]['tmp_name'];
            $mime = mime_content_type($tmp) ?: '';
            if (!in_array($mime, $allowed_project_types)) {
                $message_html = alert_html('Invalid file type for attachment.', 'danger');
                $upload_success = false;
            } else {
                $original = basename($_FILES[$file_input_name]['name']);
                $ext = pathinfo($original, PATHINFO_EXTENSION);
                $safe_name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($original, PATHINFO_FILENAME));
                $file_name = time() . '_' . $safe_name . ($ext ? '.' . $ext : '');
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($tmp, $target_file)) {
                    $file_path = $web_upload_dir . $file_name; 
                } else {
                    $error_msg = "Error moving file.";
                    error_log($error_msg);
                    $message_html = alert_html('Error moving uploaded file.', 'danger');
                    $upload_success = false;
                }
            }
        } else {
            $message_html = alert_html('File upload error.', 'danger');
            $upload_success = false;
        }
    }


    // 3. Database Insertion
    if ($upload_success) {
        $dummy_department_id = 1; 

        try {
            $dept_stmt = $pdo->prepare("SELECT id FROM departments WHERE department_name = :name");
            $dept_stmt->execute([':name' => $department_name]);
            $dept_row = $dept_stmt->fetch(PDO::FETCH_ASSOC);
            $final_department_id = $dept_row ? $dept_row['id'] : $dummy_department_id;

            $stmt = $pdo->prepare("INSERT INTO projects (name, description, department_id, faculty_id, expertise, bid, type) VALUES (:name, :description, :department_id, :faculty_id, :expertise, :bid, :type)");
            $stmt->execute([
                ':name' => $name, ':description' => $description, ':department_id' => $final_department_id,
                ':faculty_id' => $faculty_id, ':expertise' => $expertise, ':bid' => $file_path, ':type' => $type
            ]);

            header('Location: faculty-projects.php?success=1'); 
            exit;

        } catch (PDOException $e) {
            error_log('Research and Projects Add Error: ' . $e->getMessage()); 
            $message_html = alert_html('Database Insertion Error: ' . $e->getMessage(), 'danger');
        }
    }
}
// --- END: Database Update/Insertion Logic ---


// --- START: APPLICATION RETRIEVAL LOGIC ---
$student_applications = [];
if ($view_mode === 'applications') {
    try {
        $stmt = $pdo->prepare("
            SELECT
                pa.application_id, pa.application_date, pa.status AS application_status,
                p.name AS project_name, p.expertise AS project_expertise, p.type AS project_type, p.bid AS project_bid,
                s.Name AS student_name, s.email AS student_email, s.phone AS student_phone,
                s.institute AS student_institute, s.department AS student_department, 
                s.expertise AS student_expertise, s.cv_path
            FROM project_applications pa
            JOIN projects p ON pa.project_id = p.id
            JOIN student s ON pa.student_id = s.Student_ID
            WHERE p.faculty_id = :faculty_id
            ORDER BY pa.application_date DESC
        ");
        $stmt->execute([':faculty_id' => $faculty_id]);
        $raw_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode HTML entities for display
        foreach ($raw_applications as $app) {
            $app['project_name'] = html_entity_decode($app['project_name'], ENT_QUOTES, 'UTF-8');
            $app['project_expertise'] = html_entity_decode($app['project_expertise'], ENT_QUOTES, 'UTF-8');
            $app['student_expertise'] = html_entity_decode($app['student_expertise'], ENT_QUOTES, 'UTF-8');
            $student_applications[] = $app;
        }

    } catch (PDOException $e) {
        error_log('Application Fetch Error: ' . $e->getMessage());
        $message_html = alert_html('Could not fetch student applications. Database Error.', 'danger');
    }
}
// --- END: APPLICATION RETRIEVAL LOGIC ---


// --- START: Database Retrieval/Counting Logic (SELECT from projects) ---
$my_projects = [];
if ($view_mode === 'projects') {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.id, p.name, p.description, p.expertise, p.department_id,
                d.department_name AS department_name_str, p.bid, p.type
            FROM projects p 
            LEFT JOIN departments d ON p.department_id = d.id
            WHERE p.faculty_id = :faculty_id 
            ORDER BY p.id DESC
        ");
        $stmt->execute([':faculty_id' => $faculty_id]);
        $raw_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($raw_projects as $project) {
            $project['name'] = html_entity_decode($project['name'], ENT_QUOTES, 'UTF-8');
            $project['description'] = html_entity_decode($project['description'], ENT_QUOTES, 'UTF-8');
            $project['expertise'] = html_entity_decode($project['expertise'], ENT_QUOTES, 'UTF-8');
            $my_projects[] = $project;
        }
        
    } catch (PDOException $e) {
        error_log('Research and Projects Fetch Error: ' . $e->getMessage()); 
        if ($message_html == '') { 
            $message_html = alert_html('Could not fetch research and projects lists. DEBUG: ' . $e->getMessage(), 'warning');
        }
    }
}
// --- END: Database Retrieval/Counting Logic ---


function alert_html($msg, $type = 'info') {
    $color = ['success' => 'emerald', 'danger' => 'rose', 'warning' => 'amber', 'info' => 'sky'][$type] ?? 'sky';
    return "<div class=\"mb-6 p-4 rounded-xl bg-{$color}-50 border border-{$color}-200 shadow-sm\" role=\"alert\"><div class=\"font-semibold text-{$color}-700 flex items-center gap-2\"><i class=\"fas fa-exclamation-circle\"></i>" . htmlspecialchars($msg) . "</div></div>";
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Faculty Research and Projects — Dashboard</title> <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{--maroon:#7f1d1d;--maroon-deep:#5b0f0f}
    body{font-family:'Poppins',sans-serif;background-color:#f8f9fa;}
    .card{background:white;box-shadow:0 10px 25px rgba(0,0,0,0.05);border-radius:12px;border:1px solid #e2e8f0}
    .line-clamp-4{display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden}
    .backdrop{background:rgba(2,6,23,0.7)}
    .project-list-item:hover{background-color:#f8fafc;box-shadow:0 4px 12px rgba(0,0,0,0.05);}
    .btn-maroon{background-color:var(--maroon);transition:background-color 0.2s}
    .btn-maroon:hover{background-color:var(--maroon-deep)}
    .modal-panel-container {display: flex;align-items: center;justify-content: center;min-height: 100vh;padding: 1rem;}
    .app-card { background: #fff; border-left: 5px solid #d97706; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s ease; }
    .app-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .app-meta span { display: block; font-size: 0.85rem; color: #555; margin-bottom: 5px; }
    .app-meta strong { font-weight: 600; color: #333; margin-right: 5px;}
    .app-cv-link { font-weight: 600; }
    .header-link { padding: 8px 16px; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; }
  </style>
</head>
<body class="min-h-screen">
  <header class="w-full sticky top-0 z-50 bg-[#7f1d1d] shadow-lg">
    <div class="max-w-7xl mx-auto px-4 py-6 flex items-center justify-between text-white">
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-4">
            <i class="fas fa-microscope text-2xl"></i>
            <h1 class="text-xl font-bold">
                Research & Projects
            </h1>
            <!-- NEW: Welcome and Faculty Name -->
            <span class="text-sm opacity-80 border-l border-white/50 pl-4">
                Welcome, **<?php echo htmlspecialchars($faculty_name); ?>**
            </span>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <!-- NEW: Home Button -->
        <a href="index.php" class="header-link text-white border border-white hover:bg-white hover:text-[#7f1d1d] transition">
            <i class="fas fa-home mr-1"></i> Home
        </a>
        <a href="faculty-projects.php?view=projects" class="header-link text-sm text-white border border-white rounded-lg hover:bg-white hover:text-[#7f1d1d] transition <?php echo $view_mode === 'projects' ? 'bg-[#5b0f0f]' : ''; ?>">
            <i class="fas fa-list mr-1"></i>My Projects
        </a>
        <a href="faculty-projects.php?view=applications" class="header-link text-sm text-white border border-white rounded-lg hover:bg-white hover:text-[#7f1d1d] transition <?php echo $view_mode === 'applications' ? 'bg-[#5b0f0f]' : ''; ?>">
            <i class="fas fa-users mr-1"></i>View Student Applications
        </a>
        <a href="logout.php" class="header-link text-sm text-white border border-white rounded-lg hover:bg-white hover:text-[#7f1d1d] transition">
            <i class="fas fa-sign-out-alt mr-1"></i>Logout
        </a>
      </div>
    </div>
  </header>
  <main class="max-w-7xl mx-auto px-4 py-8">
    <?php echo $message_html; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="card p-6 bg-[#7f1d1d] text-white shadow-xl">
                <p class="text-sm opacity-80 font-medium"><?php echo $view_mode === 'projects' ? 'Total Listed Research and Projects' : 'Total Pending Applications'; ?></p>
                <p class="text-5xl font-extrabold mt-1">
                    <?php echo $view_mode === 'projects' ? count($my_projects) : count($student_applications); ?>
                </p>
                <p class="text-sm opacity-80 mt-2"><?php echo $view_mode === 'projects' ? 'Manage your academic contributions efficiently.' : 'Review applicants for your listings.'; ?></p>
            </div>
            
            <div class="card p-6">
                <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2"><i class="fas fa-bolt text-amber-500"></i> Quick Actions</h3>
                <button id="openModalBtn" class="w-full text-left flex items-center justify-between p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition border mb-2">
                    <span class="font-medium">Add New Research and Projects</span>
                    <i class="fas fa-arrow-right text-sm text-red-700"></i>
                </button>
                <a href="faculty-projects.php?view=applications" class="w-full text-left flex items-center justify-between p-3 rounded-lg text-gray-700 hover:bg-gray-100 transition border">
                    <span class="font-medium">Review Applications (<?php echo count($student_applications); ?>)</span>
                    <i class="fas fa-arrow-right text-sm text-red-700"></i>
                </a>
            </div>
        </div>
        
        <div class="lg:col-span-2">
            <h2 class="text-2xl font-bold text-gray-800 mb-5">
                <i class="fas <?php echo $view_mode === 'projects' ? 'fa-list-alt' : 'fa-clipboard-list'; ?> mr-2 text-[#7f1d1d]"></i>
                <?php echo $view_mode === 'projects' ? 'My Research and Projects' : 'Student Applications'; ?>
            </h2>
            
            <div class="space-y-4">
                <?php if ($view_mode === 'projects'): ?>
                    <!-- PROJECT LIST DISPLAY -->
                    <?php if (!empty($my_projects)): ?>
                        <?php foreach ($my_projects as $project): ?>
                            <div class="card p-5 project-list-item transition duration-200">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-xl font-bold text-[#7f1d1d]"><?php echo htmlspecialchars($project['name']); ?></h3>
                                    <div class="text-xs font-medium text-white bg-gray-500 px-3 py-1 rounded-full shadow-sm">
                                        Department: <?php echo htmlspecialchars($project['department_name_str'] ?: ('ID: ' . $project['department_id'])); ?>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mt-2 line-clamp-4 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                                
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <?php 
                                        $type_display = ucfirst($project['type'] ?? 'Unknown');
                                        $type_color = $project['type'] == 'research' ? 'purple' : 'teal';
                                    ?>
                                    <span class="text-xs font-semibold text-<?php echo $type_color; ?>-700 bg-<?php echo $type_color; ?>-100 px-3 py-1 rounded-full">
                                        <i class="fas fa-certificate mr-1 text-<?php echo $type_color; ?>-400"></i>Type: <?php echo htmlspecialchars($type_display); ?>
                                    </span>
                                    <span class="text-xs font-semibold text-gray-700 bg-gray-100 px-3 py-1 rounded-full"><i class="fas fa-tag mr-1 text-gray-400"></i>Expertise: <?php echo htmlspecialchars($project['expertise']); ?></span>
                                    
                                    <?php if (isset($project['bid']) && $project['bid']): ?>
                                        <a href="<?php echo htmlspecialchars($project['bid']); ?>" target="_blank" class="text-xs font-semibold text-blue-700 bg-blue-100 px-3 py-1 rounded-full hover:bg-blue-200 transition">
                                            <i class="fas fa-file-download mr-1"></i>Download Bid File
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="deleteProjectConfirmation(<?php echo $project['id']; ?>)" class="text-xs font-semibold text-rose-700 bg-rose-100 px-3 py-1 rounded-full hover:bg-rose-200 transition">
                                        <i class="fas fa-trash-alt mr-1"></i>Delete
                                    </button>
                                    
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card p-10 text-center col-span-2 bg-gray-50">
                            <i class="fas fa-folder-open text-6xl text-gray-300"></i>
                            <h3 class="text-2xl font-bold text-gray-700 mt-4">No Research and Projects Listed Yet</h3>
                            <p class="text-gray-500 mt-2">Get started by listing your first research and projects idea to attract students.</p>
                            <button id="openModalBtnEmpty" class="mt-4 px-5 py-2 text-sm text-white rounded-lg font-semibold btn-maroon shadow-md hover:shadow-lg">
                                <i class="fas fa-plus mr-1"></i>Add Research and Projects Now
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- APPLICATION LIST DISPLAY -->
                    <?php if (!empty($student_applications)): ?>
                        <?php foreach ($student_applications as $app): ?>
                            <div class="app-card grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-1 border-r pr-4">
                                    <h4 class="text-lg font-bold text-gray-800 mb-2">Student Details</h4>
                                    <div class="app-meta">
                                        <span><i class="fas fa-user text-[#7f1d1d]"></i> <strong>Name:</strong> <?php echo htmlspecialchars($app['student_name']); ?></span>
                                        <span><i class="fas fa-envelope text-[#7f1d1d]"></i> <strong>Email:</strong> <?php echo htmlspecialchars($app['student_email']); ?></span>
                                        <span><i class="fas fa-phone text-[#7f1d1d]"></i> <strong>Phone:</strong> <?php echo htmlspecialchars($app['student_phone']); ?></span>
                                        <span><i class="fas fa-university text-[#7f1d1d]"></i> <strong>Institute:</strong> <?php echo htmlspecialchars($app['student_institute']); ?></span>
                                        <span><i class="fas fa-building text-[#7f1d1d]"></i> <strong>Dept:</strong> <?php echo htmlspecialchars($app['student_department']); ?></span>
                                        <span><i class="fas fa-tag text-[#7f1d1d]"></i> <strong>Exp:</strong> <?php echo htmlspecialchars($app['student_expertise']); ?></span>
                                        <?php if (!empty($app['cv_path'])): ?>
                                            <span><i class="fas fa-file-pdf text-green-600"></i> 
                                                <a href="<?php echo htmlspecialchars($app['cv_path']); ?>" target="_blank" class="text-green-600 hover:underline app-cv-link">Download CV</a>
                                            </span>
                                        <?php else: ?>
                                            <span><i class="fas fa-exclamation-triangle text-amber-500"></i> CV Missing</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="md:col-span-2 pl-4">
                                    <h4 class="text-lg font-bold text-gray-800 mb-2">Project Applied For</h4>
                                    <p class="text-xl font-bold text-[#7f1d1d] mb-1"><?php echo htmlspecialchars($app['project_name']); ?></p>
                                    <div class="flex items-center gap-3 text-sm mb-3">
                                        <span class="text-xs font-semibold text-gray-700 bg-gray-100 px-3 py-1 rounded-full">
                                            Type: <?php echo ucfirst(htmlspecialchars($app['project_type'])); ?>
                                        </span>
                                        <span class="text-xs font-semibold text-gray-700 bg-gray-100 px-3 py-1 rounded-full">
                                            Expertise: <?php echo htmlspecialchars($app['project_expertise']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">Applied on: <?php echo date('M d, Y H:i', strtotime($app['application_date'])); ?></p>
                                    <div class="mt-4 flex gap-2">
                                        <button class="px-3 py-1 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition" onclick="alert('Accept logic here for application ID: <?php echo $app['application_id']; ?>')">
                                            <i class="fas fa-check"></i> Accept
                                        </button>
                                        <button class="px-3 py-1 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition" onclick="alert('Reject logic here for application ID: <?php echo $app['application_id']; ?>')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card p-10 text-center col-span-2 bg-gray-50">
                            <i class="fas fa-users-slash text-6xl text-gray-300"></i>
                            <h3 class="text-2xl font-bold text-gray-700 mt-4">No Pending Student Applications</h3>
                            <p class="text-gray-500 mt-2">Students will appear here once they apply to your listed projects or research papers.</p>
                            <a href="faculty-projects.php?view=projects" class="mt-4 px-5 py-2 text-sm text-white rounded-lg font-semibold btn-maroon shadow-md hover:shadow-lg inline-block">
                                <i class="fas fa-list mr-1"></i>Back to Project List
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
  </main>

  <!-- Project Modal (omitted for brevity) -->
  <div id="projectModal" class="fixed inset-0 hidden z-50" aria-hidden="true">
    <div class="absolute inset-0 backdrop"></div>
    <div class="relative modal-panel-container"> 
      <div class="w-full max-w-3xl mx-4 transform transition-all duration-300 scale-95 opacity-0 modal-panel bg-white rounded-xl shadow-2xl overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        
        <div class="flex items-center justify-between px-6 py-4 bg-[#7f1d1d]">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-white/20">
              <i class="fas fa-lightbulb text-white"></i>
            </div>
            <div>
              <h3 id="modalTitle" class="text-white text-xl font-bold">List a New Research and Projects</h3>
              <p class="text-sm text-white/80">Share a clear brief.</p>
            </div>
          </div>
          <button id="closeModal" aria-label="Close modal" class="text-white p-2 rounded-full hover:bg-white/10 transition">
              <i class="fas fa-times"></i>
          </button>
        </div>

        <form method="post" enctype="multipart/form-data" class="p-6 bg-white">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Research and Projects Title</label>
              <input name="name" type="text" maxlength="300" required class="p-3 w-full border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[#7f1d1d]" placeholder="E.g., Autonomous Drone for Crop Monitoring">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Research/Project Type</label>
              <select name="type" required class="p-3 w-full border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[#7f1d1d]">
                <option value="">-- Select Type --</option>
                <option value="project">Project</option>
                <option value="research">Research Paper</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Department</label>
              <select name="department_name" required class="p-3 w-full border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[#7f1d1d]">
                <option value="">-- Select Department --</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>">
                        <?php echo htmlspecialchars($dept); ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1">Expertise / Keywords (Comma-separated)</label>
              <input name="expertise" type="text" maxlength="500" required class="p-3 w-full border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[#7f1d1d]" placeholder="e.g., AI, Robotics, Computer Vision, Big Data">
            </div>

            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1">Detailed Research and Projects Description</label>
              <textarea name="description" rows="5" maxlength="2000" required class="p-3 w-full border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[#7f1d1d]" placeholder="Provide core goals, deliverables, prerequisites, timeline..."></textarea>
            </div>
            
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-2">Attach Project Bid File (optional)</label>
              <label for="project_file_input" class="flex items-center gap-3 p-4 bg-gray-50 border border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-100 transition">
                <svg xmlns="http://www.w3d.org/2000/svg" class="h-6 w-6 text-[#7f1d1d]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 0 003 3h10a3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <div class="flex-grow text-sm text-slate-700 font-medium">Click to attach or drag and drop</div>
                <input id="project_file_input" type="file" name="research_and_projects_file" accept=".zip,.ppt,.pptx,.doc,.docx" class="sr-only">
              </label>
              <div id="attachedFile" class="mt-2 text-sm text-slate-500 font-mono pl-1">No file chosen</div>
              <div class="text-xs text-slate-400 mt-1 pl-1">Allowed formats: .zip, .ppt, .pptx, .doc, .docx.</div>
            </div>
            </div>

          <input type="hidden" name="add_research_and_projects" value="1">

          <div class="mt-6 flex items-center justify-end gap-3 border-t pt-4">
            <button type="button" id="cancelBtn" class="px-4 py-2 border border-gray-300 rounded-md text-slate-700 hover:bg-gray-100 transition">Cancel</button>
            <button type="submit" class="px-5 py-2 text-white rounded-md shadow btn-maroon hover:shadow-lg font-semibold">
                <i class="fas fa-upload mr-1"></i>Add Research and Projects
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script>
    const modal = document.getElementById('projectModal');
    const panel = modal.querySelector('.modal-panel');
    const openBtn = document.getElementById('openModalBtn');
    const openBtnEmpty = document.getElementById('openModalBtnEmpty'); 
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    
    function showModal(){
      modal.classList.remove('hidden');
      modal.setAttribute('aria-hidden','false');
      requestAnimationFrame(()=>{
        panel.style.transform = 'translateY(0) scale(1)';
        panel.style.opacity = '1';
      });
      document.body.style.overflow = 'hidden';
    }
    function hideModal(){
      panel.style.transform = 'translateY(-8px) scale(0.98)';
      panel.style.opacity = '0';
      setTimeout(()=>{
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden','true');
      }, 180);
      document.body.style.overflow = '';
    }

    openBtn?.addEventListener('click', showModal);
    openBtnEmpty?.addEventListener('click', showModal);
    closeBtn?.addEventListener('click', hideModal);
    cancelBtn?.addEventListener('click', hideModal);
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') hideModal(); });
    
    // File Input Handling (Simplified for brevity)
    const fileInput = document.getElementById('project_file_input');
    const attachedFile = document.getElementById('attachedFile');
    fileInput?.addEventListener('change', (e)=>{
      const f = e.target.files[0];
      if(!f){ attachedFile.textContent = 'No file chosen'; return; }
      attachedFile.textContent = `${f.name} (${(f.size/1024).toFixed(1)} KB)`;
    });

    // Function to handle direct Deletion Confirmation
    function deleteProjectConfirmation(projectId) {
        if (confirm("Are you sure you want to permanently delete Project ID " + projectId + "? This action cannot be undone.")) {
            window.location.href = 'faculty-projects.php?delete_id=' + projectId;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('success')) {
            const successCode = urlParams.get('success');
            
            if (successCode === '1') {
                alert("Success! Your Research and Projects entry has been added to the database.");
            } else if (successCode === '2') {
                alert("Success! The project has been deleted from the database.");
            }

            if (history.replaceState) {
                history.replaceState(null, null, window.location.pathname);
            }
        }
        
        // Open modal if we are viewing applications initially
        if ('<?php echo $view_mode; ?>' === 'applications') {
            // No action needed since we display applications in the main content area now
        }
    });
  </script>

</body>
</html>