<?php
// student-dashboard.php - Student Dashboard with Projects/Research and Profile Editing

// -----------------------------------------------------------
// 1. PHP SETUP & DATABASE INTEGRATION
// -----------------------------------------------------------
session_start();

// Set PHP Timezone to IST (India Standard Time)
date_default_timezone_set('Asia/Kolkata');

// Database connection using PDO (as used in student-login.php)
require_once 'db.php';

// Session check
if(!isset($_SESSION['student_id']) || !isset($_SESSION['student_logged_in'])) {
    header('Location: student-login.php');
    exit;
}

$student_id = $_SESSION['student_id'] ?? null;
$student_name = $_SESSION['student_name'] ?? "Student";

// --- FILE UPLOAD SETUP (Existing CV Upload Code) ---
$upload_dir_name = 'student_cv_files';
$upload_dir = __DIR__ . DIRECTORY_SEPARATOR . $upload_dir_name . DIRECTORY_SEPARATOR; 
$web_upload_dir = $upload_dir_name . '/';

// Ensure the upload directory exists
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}
$allowed_cv_types = [
    'application/pdf',
    'application/msword', // .doc
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' // .docx
];

// Initialize variables
$student_data = null;
$projects = [];
$research = [];
$db_error = null;
$success_message = null;
$error_message = null;
$view_all = isset($_GET['view_all']) && $_GET['view_all'] === 'true';

// --- FIXED: Fetch departments list for the modal dropdown (Must run always) ---
$departments = [];
try {
    $stmt = $pdo->prepare("SELECT department_name FROM departments ORDER BY department_name ASC");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching departments: " . $e->getMessage());
    // Display a generic error if this fetch fails, but let the rest of the page load.
    $db_error = $db_error ?? "Could not load department list. Database connection issue.";
}
// --- END DEPARTMENT FETCHING FIX ---


// --- NEW: APPLICATION SUBMISSION HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_project_id'])) {
    $project_id_to_apply = filter_input(INPUT_POST, 'apply_project_id', FILTER_VALIDATE_INT);
    
    // Ensure student has a CV uploaded before allowing application
    $cv_check_stmt = $pdo->prepare("SELECT cv_path FROM student WHERE Student_ID = :student_id");
    $cv_check_stmt->execute([':student_id' => $student_id]);
    $cv_path = $cv_check_stmt->fetchColumn();

    if (empty($cv_path)) {
        $error_message = "Application failed: Please upload your CV/Resume in your profile before applying.";
    } elseif ($project_id_to_apply) {
        try {
            // Check if application already exists
            $check_stmt = $pdo->prepare("SELECT application_id FROM project_applications WHERE student_id = :student_id AND project_id = :project_id");
            $check_stmt->execute([':student_id' => $student_id, ':project_id' => $project_id_to_apply]);
            
            if ($check_stmt->rowCount() > 0) {
                $error_message = "You have already applied for this project.";
            } else {
                // Insert new application
                $insert_stmt = $pdo->prepare("INSERT INTO project_applications (project_id, student_id) VALUES (:project_id, :student_id)");
                $insert_stmt->execute([':project_id' => $project_id_to_apply, ':student_id' => $student_id]);
                
                $success_message = "Application submitted successfully!";
            }
        } catch (PDOException $e) {
            error_log("Application Submission Error: " . $e->getMessage());
            $error_message = "Application failed due to a system error. Please try again.";
        }
        
        // Redirect to clear POST data and show message
        $redirect_params = 'success=' . (isset($success_message) ? '2' : '0');
        if ($view_all) $redirect_params .= '&view_all=true';
        header('Location: student-dashboard.php?' . $redirect_params);
        exit;
    }
}

// Handle profile update (Existing code, unchanged)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? '');
    $new_phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING) ?? '');
    $new_department = trim(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING) ?? '');
    $new_expertise = trim(filter_input(INPUT_POST, 'expertise', FILTER_SANITIZE_STRING) ?? '');
    $new_institute = trim(filter_input(INPUT_POST, 'institute', FILTER_SANITIZE_STRING) ?? '');
    $current_cv_path = $_POST['current_cv_path'] ?? null;
    $cv_path_to_save = $current_cv_path; 
    $cv_upload_success = true;
    
    // 1. CV File Upload Handling
    if (isset($_FILES['student_cv']) && $_FILES['student_cv']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file_input_name = 'student_cv';
        if ($_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES[$file_input_name]['tmp_name'];
            $mime = mime_content_type($tmp) ?: '';
            if (!in_array($mime, $allowed_cv_types)) {
                $error_message = 'Invalid CV file type. Only PDF and DOC/DOCX files are allowed.';
                $cv_upload_success = false;
            } else {
                $original = basename($_FILES[$file_input_name]['name']);
                $ext = pathinfo($original, PATHINFO_EXTENSION);
                $file_name = $student_id . '_' . time() . '.' . $ext;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($tmp, $target_file)) {
                    $cv_path_to_save = $web_upload_dir . $file_name; 
                    if ($current_cv_path && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $current_cv_path)) {
                        @unlink(__DIR__ . DIRECTORY_SEPARATOR . $current_cv_path);
                    }
                } else {
                    $error_message = 'Error moving uploaded CV file. Check folder permissions.';
                    $cv_upload_success = false;
                }
            }
        } else {
            $error_message = 'File upload error occurred. Please try again.';
            $cv_upload_success = false;
        }
    }
    
    // 2. Profile Data Validation and Update
    if ($cv_upload_success) {
        if ($new_name && $new_department) {
            try {
                $new_phone = preg_replace('/^(\+91|91)/', '', $new_phone);
                $new_phone = '+91' . $new_phone;
                
                $pdo->exec("ALTER TABLE student ADD COLUMN IF NOT EXISTS cv_path VARCHAR(255) NULL AFTER expertise");

                $stmt = $pdo->prepare("
                    UPDATE student 
                    SET Name = :name, phone = :phone, department = :department, expertise = :expertise, institute = :institute, cv_path = :cv_path
                    WHERE Student_ID = :student_id
                ");
                $stmt->execute([
                    ':name' => $new_name, ':phone' => $new_phone, ':department' => $new_department, ':expertise' => $new_expertise, 
                    ':institute' => $new_institute, ':cv_path' => $cv_path_to_save, ':student_id' => $student_id
                ]);
                
                $_SESSION['student_name'] = $new_name;
                header('Location: student-dashboard.php?success=1');
                exit;
            } catch (PDOException $e) {
                error_log("Profile Update PDO Error: " . $e->getMessage());
                $error_message = "Failed to update profile. Database Error.";
            }
        } else {
            $error_message = "Name and Department are required fields.";
        }
    }
}

// Fetch student data (runs after possible POST redirect)
try {
    $stmt = $pdo->prepare("SELECT Student_ID, Name, email, phone, institute, department, expertise, cv_path FROM student WHERE Student_ID = :student_id");
    $stmt->execute([':student_id' => $student_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student_data) {
        // Fetch existing applications for this student
        $applications_stmt = $pdo->prepare("SELECT project_id FROM project_applications WHERE student_id = :student_id");
        $applications_stmt->execute([':student_id' => $student_id]);
        $applied_projects = $applications_stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Get list of applied project IDs
        
        $student_department = $student_data['department'] ?? '';
        $student_expertise = $student_data['expertise'] ?? '';
        
        try { $pdo->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS type ENUM('project', 'research') DEFAULT 'project'"); } catch (PDOException $e) {}
        
        $expertise_keywords = [];
        if ($student_expertise) {
            $expertise_keywords = array_map('trim', explode(',', strtolower($student_expertise)));
            $expertise_keywords = array_filter($expertise_keywords);
        }
        
        // Function to build query for projects/research
        $buildQuery = function($type) use ($student_department, $expertise_keywords, $pdo, $view_all) {
            
            $sql = "
                SELECT 
                    p.id, p.name, p.description, p.expertise, p.department_id, p.bid, p.type,
                    d.department_name,
                    f.first_name, f.last_name, f.email AS faculty_email, f.faculty_id
                FROM projects p
                LEFT JOIN departments d ON p.department_id = d.id
                LEFT JOIN faculty f ON p.faculty_id = f.faculty_id
                WHERE p.type = :type_val
            ";
            
            $params = [':type_val' => $type];
            $where_clauses = [];
            
            if (!$view_all) {
                
                if ($student_department) {
                    $where_clauses[] = "d.department_name = :dept_exact";
                    $params[':dept_exact'] = $student_department;
                }
                
                if (!empty($expertise_keywords)) {
                    $expertise_conditions = [];
                    foreach ($expertise_keywords as $idx => $keyword) {
                        if (!empty($keyword)) {
                            $param_key = ':expertise_' . $idx;
                            $expertise_conditions[] = "LOWER(p.expertise) LIKE " . $param_key;
                            $params[$param_key] = '%' . $keyword . '%';
                        }
                    }
                    if (!empty($expertise_conditions)) {
                        $where_clauses[] = "(" . implode(' OR ', $expertise_conditions) . ")";
                    }
                }
                
                if (!empty($where_clauses)) {
                    if ($student_department && !empty($expertise_keywords)) {
                        $sql .= " AND d.department_name = :dept_exact AND (" . implode(' OR ', $expertise_conditions) . ")";
                    } elseif ($student_department) {
                        $sql .= " AND d.department_name = :dept_exact";
                    } elseif (!empty($expertise_conditions)) {
                        $sql .= " AND (" . implode(' OR ', $expertise_conditions) . ")";
                    } else {
                        $sql .= " AND 1=0";
                    }
                } else {
                    $sql .= " AND 1=0";
                }
            }
            
            $sql .= " ORDER BY p.id DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $decoded_results = [];
            foreach ($results as $item) {
                $item['name'] = html_entity_decode($item['name'], ENT_QUOTES, 'UTF-8');
                $item['description'] = html_entity_decode($item['description'], ENT_QUOTES, 'UTF-8');
                $item['expertise'] = html_entity_decode($item['expertise'], ENT_QUOTES, 'UTF-8');
                $decoded_results[] = $item;
            }
            return $decoded_results;
        };
        
        if ($view_all || $student_department || $student_expertise) {
            $projects = $buildQuery('project');
            $research = $buildQuery('research');
        }

    } else {
        $db_error = "Student data not found.";
    }
} catch (PDOException $e) {
    error_log("Dashboard PDO Error: " . $e->getMessage());
    $db_error = "Database error occurred. Please try again later.";
}

// Check for success message from redirect (updated for application success)
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Profile updated successfully!";
} elseif (isset($_GET['success']) && $_GET['success'] == '2') {
    $success_message = "Application submitted successfully!";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #8B0000; /* Dark Red/Maroon */
            --primary-light: #A52A2A;
            --main-bg: #f8f9fa; 
            --card-bg: #ffffff;
            --text-primary: #333;
            --text-secondary: #666;
            --border-color: #e0e0e0;
        }
        
        * {
            transition: all 0.3s ease;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background particles */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(139, 0, 0, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 0, 0, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(139, 0, 0, 0.02) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        /* ------------------- HEADER BAR ------------------- */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 25px 50px;
            box-shadow: 0 6px 20px rgba(139, 0, 0, 0.3);
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
            animation: slideDown 0.5s ease-out;
            display: flex; /* Ensure flex layout */
            justify-content: space-between; /* Space out content */
            align-items: center;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .dashboard-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        }
        
        .dashboard-header h1 {
            font-weight: 700;
            font-size: 1.8rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            animation: fadeInLeft 0.6s ease-out;
            margin-bottom: 0;
        }
        
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .dashboard-header p {
            opacity: 0.9;
            margin-bottom: 0;
            animation: fadeInLeft 0.8s ease-out;
        }
        
        .back-link {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            opacity: 0.9;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-size: 0.9rem;
            border: 1px solid rgba(255,255,255,0.5);
        }
        
        .back-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: left 0.3s ease;
        }
        
        .back-link:hover::before {
            left: 0;
        }
        
        .back-link:hover {
            opacity: 1;
            color: #fff;
            transform: translateX(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* ------------------- MAIN CONTENT CARDS ------------------- */
        .content-area {
            padding: 0 50px 50px;
            position: relative;
            z-index: 1;
        }

        .custom-card {
            background: linear-gradient(145deg, #ffffff 0%, #fafafa 100%);
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            padding: 30px;
            border: 1px solid rgba(139, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .custom-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }
        
        .custom-card:hover::before {
            transform: scaleX(1);
        }
        
        .custom-card:hover {
            box-shadow: 0 15px 35px rgba(139, 0, 0, 0.15);
            transform: translateY(-5px);
            border-color: rgba(139, 0, 0, 0.2);
        }

        /* Welcome Card Specifics */
        #welcome-card {
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 300px;
            background: linear-gradient(135deg, rgba(139, 0, 0, 0.02) 0%, rgba(165, 42, 42, 0.02) 100%);
        }
        
        #welcome-card h4 {
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-primary);
            font-size: 1.5rem;
        }
        
        #welcome-card .welcome-message {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-top: 15px;
            line-height: 1.8;
        }
        
        #welcome-card .welcome-message strong {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        #welcome-card .welcome-icon {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 25px;
            animation: bounceIn 1s ease-out, pulse 2s ease-in-out infinite 1s;
            filter: drop-shadow(0 4px 8px rgba(139, 0, 0, 0.2));
        }
        
        @keyframes bounceIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        .edit-profile-btn {
            margin-top: 25px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border: none;
            color: #fff;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .edit-profile-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .edit-profile-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .edit-profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 0, 0, 0.4);
        }
        
        .edit-profile-btn:active {
            transform: translateY(0);
        }

        /* Project Card Specifics */
        .project-card {
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            background: linear-gradient(145deg, #ffffff 0%, #fafafa 100%);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out backwards;
            animation-delay: calc(var(--index, 0) * 0.1s);
        }
        
        .project-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary-color), var(--primary-light));
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.4s ease;
        }
        
        .project-card:hover::before {
            transform: scaleY(1);
        }
        
        .project-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 10px 30px rgba(139, 0, 0, 0.15);
            border-color: rgba(139, 0, 0, 0.3);
        }
        
        .project-card h5 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .project-card h5::before {
            content: '▸';
            color: var(--primary-light);
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .project-card:hover h5::before {
            transform: translateX(5px);
        }
        
        .project-card .project-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid rgba(139, 0, 0, 0.1);
        }
        
        .project-card .project-meta span {
            font-size: 0.9rem;
            color: var(--text-secondary);
            padding: 8px 12px;
            background: rgba(139, 0, 0, 0.05);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .project-card .project-meta span:hover {
            background: rgba(139, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .project-card .project-meta i {
            color: var(--primary-color);
            margin-right: 8px;
            transition: transform 0.3s ease;
        }
        
        .project-card .project-meta span:hover i {
            transform: scale(1.2) rotate(5deg);
        }
        
        .project-description {
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        
        .project-card .project-meta a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .project-card .project-meta a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }
        
        /* Modal Styling */
        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        .btn-theme {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }
        .btn-theme:hover {
            background-color: #A52A2A;
            border-color: #A52A2A;
            color: #fff;
        }
        
        /* Button Group Styling */
        .btn-group-projects {
            box-shadow: 0 4px 15px rgba(139, 0, 0, 0.1);
            border-radius: 25px;
            overflow: hidden;
            padding: 4px;
            background: rgba(139, 0, 0, 0.05);
        }
        
        .btn-group-projects .btn {
            border: none;
            border-color: transparent;
            color: var(--primary-color);
            background: transparent;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }
        
        .btn-group-projects .btn.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);
            transform: scale(1.05);
        }
        
        .btn-group-projects .btn:hover:not(.active) {
            background: rgba(139, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .content-section {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }
        
        /* Alert Styling */
        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: slideInDown 0.5s ease-out;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
        }
        
        /* Empty State Styling */
        .alert-info {
            padding: 30px;
            text-align: center;
        }
        
        .alert-info i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--primary-color);
            opacity: 0.5;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        /* Modal Enhancements */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            border-radius: 15px 15px 0 0;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(139, 0, 0, 0.25);
        }
        
        .input-group-text {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            font-weight: 600;
            border-color: var(--primary-color);
        }
        
        .input-group:focus-within .input-group-text {
            box-shadow: 0 0 0 0.2rem rgba(139, 0, 0, 0.25);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 20px 25px;
            }
            
            .content-area {
                padding: 0 25px 30px;
            }
            
            .custom-card {
                padding: 20px;
            }
        }
        
        /* File input style */
        .file-input-group {
            display: flex;
            align-items: center;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            overflow: hidden;
        }
        .file-input-group input[type="file"] {
            opacity: 0;
            position: absolute;
            pointer-events: none;
            width: 1px;
            height: 1px;
        }
        .file-label {
            padding: 0.375rem 0.75rem;
            cursor: pointer;
            background-color: #e9ecef;
            border-right: 1px solid #ced4da;
            font-weight: 500;
            white-space: nowrap;
        }
        .file-name-display {
            flex-grow: 1;
            padding: 0.375rem 0.75rem;
            color: #6c757d;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .file-upload-container {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            background: #fdfdfd;
            margin-top: 10px;
        }
        .file-upload-container:hover {
            border-color: var(--primary-light);
        }
        .cv-link {
            color: var(--primary-color);
            text-decoration: underline;
        }
        .cv-link:hover {
            color: var(--primary-light);
        }

        /* Apply Button Styles */
        .btn-apply {
            background-color: #198754; /* Success Green */
            border-color: #198754;
            color: #fff;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-apply:hover {
            background-color: #157347;
            border-color: #157347;
            box-shadow: 0 4px 10px rgba(25, 135, 84, 0.4);
        }
        .btn-applied {
            background-color: #6c757d; /* Grey */
            border-color: #6c757d;
            cursor: not-allowed;
            color: #fff;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 25px;
        }
        .btn-home {
            /* NEW: Styles for the Home button in student-dashboard */
            background-color: transparent;
            border: 1px solid #fff;
            color: #fff;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        @media (max-width: 992px) {
            .back-link { padding: 6px 9px; font-size: 0.85rem; }
            .btn-home { padding: 6px 9px; font-size: 0.85rem; }
        }
        @media (max-width: 768px) {
            .back-link { padding: 5px 8px; font-size: 0.82rem; }
            .btn-home { padding: 5px 8px; font-size: 0.82rem; }
        }
        @media (max-width: 576px) {
            .back-link { padding: 5px 8px; font-size: 0.8rem; }
            .btn-home { padding: 5px 8px; font-size: 0.8rem; }
        }
        .btn-home {
            margin-right: 20px; /* Space between home and greeting */
        }
        .btn-home:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
    </style>
</head>
<body>

<header class="dashboard-header">
    <div class="d-flex align-items-center">
        <!-- ADDED: Home Button -->
        <a href="index.php" class="back-link me-3">
            <i class="fas fa-home me-1"></i> Home
        </a>
        <div>
            <h1>Student Dashboard</h1>
            <p>Welcome, <strong><?= htmlspecialchars($student_name) ?></strong></p>
        </div>
    </div>
    <a href="student-logout.php" class="back-link">
        <i class="fas fa-sign-out-alt me-1"></i> Logout
    </a>
</header>

<div class="container-fluid content-area">

    <?php if (isset($db_error) && $db_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($db_error) ?></div>
    <?php endif; ?>
    
    <?php if (isset($success_message) && $success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message) && $error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        
        <div class="col-12 col-md-4 mb-4">
            <div id="welcome-card" class="custom-card">
                <div class="welcome-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h4>Welcome</h4>
                <div class="welcome-message">
                    <p>Hello, <strong><?= htmlspecialchars($student_name) ?></strong>!</p>
                    <p class="mt-3">Explore projects and research opportunities matching your expertise.</p>
                </div>
                <button type="button" class="btn edit-profile-btn" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-edit me-2"></i> Edit Profile
                </button>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="custom-card">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <h4 class="text-start mb-0">
                        <i class="fas fa-project-diagram me-2"></i>Projects & Research
                    </h4>
                    
                    <div class="d-flex align-items-center gap-3">
                        <!-- NEW: View All Checkbox -->
                        <div class="form-check form-switch p-0">
                            <input class="form-check-input ms-0" type="checkbox" role="switch" id="viewAllSwitch" 
                                onclick="toggleViewAll(this)" <?= $view_all ? 'checked' : '' ?>>
                            <label class="form-check-label text-sm text-secondary ms-2" for="viewAllSwitch">
                                View All (Unfiltered)
                            </label>
                        </div>
                        
                        <!-- Tabs -->
                        <div class="btn-group-projects" role="group">
                            <button type="button" class="btn active" id="btn-projects" onclick="showContent('projects')">
                                <i class="fas fa-project-diagram me-2"></i>Projects (<?= count($projects) ?>)
                            </button>
                            <button type="button" class="btn" id="btn-research" onclick="showContent('research')">
                                <i class="fas fa-flask me-2"></i>Research (<?= count($research) ?>)
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Status Message -->
                <?php if (!$view_all): ?>
                    <div class="alert alert-info text-sm py-2 px-3 mb-4">
                        <i class="fas fa-filter me-2"></i>
                        Showing results filtered by Department: <strong><?= htmlspecialchars($student_data['department'] ?? 'N/A') ?></strong> and Expertise: <strong><?= htmlspecialchars($student_data['expertise'] ?? 'N/A') ?></strong>.
                        <?php if (empty($student_data['department']) || empty($student_data['expertise'])): ?>
                            <br><small class="text-danger">**Warning:** Your profile details are incomplete, which may restrict your results. Click 'Edit Profile' to update.</small>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-sm py-2 px-3 mb-4">
                        <i class="fas fa-globe me-2"></i>
                        **Showing ALL Projects and Research (Unfiltered).** Results may not be directly relevant to your profile.
                    </div>
                <?php endif; ?>


                
                <!-- Projects Section -->
                <div id="projects-list" class="content-section">
                    <?php if (empty($projects) && !$view_all && (empty($student_data['department']) || empty($student_data['expertise']))): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            Please update your profile with your department and expertise to see **relevant** projects. Or, check 'View All' to see everything.
                        </div>
                    <?php elseif (empty($projects)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            No projects found matching the current criteria.
                        </div>
                    <?php else: ?>
                        <?php foreach ($projects as $project): 
                            $is_applied = in_array($project['id'], $applied_projects);
                        ?>
                        <div class="project-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5><?= htmlspecialchars($project['name']) ?></h5>
                                <!-- Apply Button -->
                                <form method="POST" action="student-dashboard.php" onsubmit="return confirm('Confirm application for <?= htmlspecialchars($project['name']) ?>?');">
                                    <input type="hidden" name="apply_project_id" value="<?= $project['id'] ?>">
                                    <?php if ($is_applied): ?>
                                        <button type="button" class="btn btn-applied btn-sm" disabled>
                                            <i class="fas fa-check me-1"></i> Applied
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-apply btn-sm">
                                            <i class="fas fa-paper-plane me-1"></i> Apply Now
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                            
                            <div class="project-description">
                                <?= nl2br(htmlspecialchars($project['description'])) ?>
                            </div>
                            <div class="project-meta">
                                <?php if (!empty($project['department_name'])): ?>
                                    <span><i class="fas fa-building"></i> <strong>Department:</strong> <?= htmlspecialchars($project['department_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($project['expertise'])): ?>
                                    <span><i class="fas fa-lightbulb"></i> <strong>Expertise:</strong> <?= htmlspecialchars($project['expertise']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($project['first_name']) || !empty($project['last_name'])): ?>
                                    <span><i class="fas fa-user-tie"></i> <strong>Faculty:</strong> 
                                        <?= htmlspecialchars(trim(($project['first_name'] ?? '') . ' ' . ($project['last_name'] ?? ''))) ?>
                                        <?php if (!empty($project['faculty_email'])): ?>
                                            (<a href="mailto:<?= htmlspecialchars($project['faculty_email']) ?>"><?= htmlspecialchars($project['faculty_email']) ?></a>)
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($project['bid'])): ?>
                                    <span><i class="fas fa-file-pdf"></i> 
                                        <a href="<?= htmlspecialchars($project['bid']) ?>" target="_blank" class="text-primary">View BID Document</a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Research Section -->
                <div id="research-list" class="content-section" style="display: none;">
                    <?php if (empty($research) && !$view_all && (empty($student_data['department']) || empty($student_data['expertise']))): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            Please update your profile with your department and expertise to see **relevant** research. Or, check 'View All' to see everything.
                        </div>
                    <?php elseif (empty($research)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            No research papers found matching the current criteria.
                        </div>
                    <?php else: ?>
                        <?php foreach ($research as $item): 
                            $is_applied = in_array($item['id'], $applied_projects);
                        ?>
                        <div class="project-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5><?= htmlspecialchars($item['name']) ?></h5>
                                <!-- Apply Button -->
                                <form method="POST" action="student-dashboard.php" onsubmit="return confirm('Confirm application for <?= htmlspecialchars($item['name']) ?>?');">
                                    <input type="hidden" name="apply_project_id" value="<?= $item['id'] ?>">
                                    <?php if ($is_applied): ?>
                                        <button type="button" class="btn btn-applied btn-sm" disabled>
                                            <i class="fas fa-check me-1"></i> Applied
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-apply btn-sm">
                                            <i class="fas fa-paper-plane me-1"></i> Apply Now
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="project-description">
                                <?= nl2br(htmlspecialchars($item['description'])) ?>
                            </div>
                            <div class="project-meta">
                                <?php if (!empty($item['department_name'])): ?>
                                    <span><i class="fas fa-building"></i> <strong>Department:</strong> <?= htmlspecialchars($item['department_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['expertise'])): ?>
                                    <span><i class="fas fa-lightbulb"></i> <strong>Expertise:</strong> <?= htmlspecialchars($item['expertise']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['first_name']) || !empty($item['last_name'])): ?>
                                    <span><i class="fas fa-user-tie"></i> <strong>Faculty:</strong> 
                                        <?= htmlspecialchars(trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''))) ?>
                                        <?php if (!empty($item['faculty_email'])): ?>
                                            (<a href="mailto:<?= htmlspecialchars($item['faculty_email']) ?>"><?= htmlspecialchars($item['faculty_email']) ?></a>)
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($item['bid'])): ?>
                                    <span><i class="fas fa-file-pdf"></i> 
                                        <a href="<?= htmlspecialchars($item['bid']) ?>" target="_blank" class="text-primary">View BID Document</a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">
                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                <input type="hidden" name="current_cv_path" value="<?= htmlspecialchars($student_data['cv_path'] ?? '') ?>">
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <label class="form-label">Update CV / Resume (PDF/DOCX)</label>
                        
                        <div class="file-upload-container">
                            <label for="student_cv_file" class="d-block w-100 cursor-pointer">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fas fa-file-upload text-2xl text-primary-light"></i>
                                    <div class="flex-grow-1">
                                        <div class="text-sm font-weight-bold text-dark">Click to upload or replace CV</div>
                                        <div class="text-xs text-muted" id="fileNameDisplay">
                                            <?php 
                                            if (!empty($student_data['cv_path'])): 
                                                $filename = basename($student_data['cv_path']);
                                                echo "Currently uploaded: <a href='" . htmlspecialchars($student_data['cv_path']) . "' target='_blank' class='cv-link'>" . htmlspecialchars($filename) . "</a>";
                                            else:
                                                echo "No CV uploaded yet.";
                                            endif;
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            <input type="file" name="student_cv" id="student_cv_file" 
                                   accept=".pdf,.doc,.docx" class="d-none" onchange="updateFileName(this)">
                        </div>
                        <small class="form-text text-muted">Max file size limit applies.</small>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">+91</span>
                            <input type="tel" class="form-control" id="edit_phone" name="phone" 
                                   value="<?= htmlspecialchars(preg_replace('/^\+91/', '', $student_data['phone'] ?? '+91')) ?>" 
                                   placeholder="Enter phone number"
                                   pattern="[0-9]{10}"
                                   maxlength="10">
                        </div>
                        <small class="form-text text-muted">Enter 10-digit phone number </small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" 
                               value="<?= htmlspecialchars($student_data['Name'] ?? '') ?>" 
                               required placeholder="Enter your full name">
                    </div>
                    <div class="mb-3">
                        <label for="edit_department" class="form-label">Department <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_department" name="department" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" 
                                    <?= (isset($student_data['department']) && $student_data['department'] === $dept) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_expertise" class="form-label">Expertise</label>
                        <input type="text" class="form-control" id="edit_expertise" name="expertise" 
                               value="<?= htmlspecialchars($student_data['expertise'] ?? '') ?>" 
                               placeholder="e.g., Web Development, AI, Data Science (comma-separated)">
                        <small class="form-text text-muted">Enter your areas of expertise separated by commas</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_institute" class="form-label">Institute Name</label>
                        <input type="text" class="form-control" id="edit_institute" name="institute" 
                               value="<?= htmlspecialchars($student_data['institute'] ?? '') ?>" 
                               placeholder="Enter institute name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function updateFileName(input) {
        const display = document.getElementById('fileNameDisplay');
        if (input.files.length > 0) {
            display.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i> New file selected: <strong>${input.files[0].name}</strong>`;
        } else {
            // Restore previous text if cancelled
            const currentPath = document.querySelector('input[name="current_cv_path"]').value;
            if (currentPath) {
                const filename = currentPath.substring(currentPath.lastIndexOf('/') + 1);
                display.innerHTML = `Currently uploaded: <a href="${currentPath}" target='_blank' class='cv-link'>${filename}</a>`;
            } else {
                display.innerHTML = "No CV uploaded yet.";
            }
        }
    }

    function showContent(type) {
        const projectsList = document.getElementById('projects-list');
        const researchList = document.getElementById('research-list');
        const btnProjects = document.getElementById('btn-projects');
        const btnResearch = document.getElementById('btn-research');
        
        // Add fade out animation
        if (projectsList.style.display !== 'none') {
            projectsList.style.opacity = '0';
            projectsList.style.transform = 'translateY(-10px)';
        }
        if (researchList.style.display !== 'none') {
            researchList.style.opacity = '0';
            researchList.style.transform = 'translateY(-10px)';
        }
        
        setTimeout(() => {
            // Hide all content sections
            projectsList.style.display = 'none';
            researchList.style.display = 'none';
            
            // Remove active class from all buttons
            btnProjects.classList.remove('active');
            btnResearch.classList.remove('active');
            
            // Show selected content and activate button
            if (type === 'projects') {
                projectsList.style.display = 'block';
                btnProjects.classList.add('active');
                setTimeout(() => {
                    projectsList.style.opacity = '1';
                    projectsList.style.transform = 'translateY(0)';
                }, 50);
            } else if (type === 'research') {
                researchList.style.display = 'block';
                btnResearch.classList.add('active');
                setTimeout(() => {
                    researchList.style.opacity = '1';
                    researchList.style.transform = 'translateY(0)';
                }, 50);
            }
        }, 200);
        
        // Store active tab on button click (run outside of timeout)
        sessionStorage.setItem('activeTab', type);
    }
    
    function toggleViewAll(checkbox) {
        // Construct the new URL based on the checkbox state
        const url = new URL(window.location.href);
        if (checkbox.checked) {
            url.searchParams.set('view_all', 'true');
        } else {
            url.searchParams.delete('view_all');
        }
        // Reload the page with the new URL
        window.location.href = url.toString();
    }
    
    // Initialize: Show projects by default
    document.addEventListener('DOMContentLoaded', function() {
        // Check which tab was active before the reload (for persistence)
        const activeTab = sessionStorage.getItem('activeTab') || 'projects';
        showContent(activeTab);
        
        // Add stagger animation to project cards
        const projectCards = document.querySelectorAll('.project-card');
        projectCards.forEach((card, index) => {
            card.style.setProperty('--index', index);
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });
</script>

</body>
</html>