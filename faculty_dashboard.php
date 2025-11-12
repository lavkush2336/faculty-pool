<?php
// --- START: DEBUGGING ENABLED (TEMPORARY FIX FOR BLANK PAGE) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 
// --- END: DEBUGGING ENABLED ---

// faculty-dashboard.php - The complete working file with PHPMailer integration

// -----------------------------------------------------------
// 1. PHP SETUP & DATABASE INTEGRATION
// -----------------------------------------------------------
session_start();

// Set PHP Timezone to IST (India Standard Time) for all calculations
date_default_timezone_set('Asia/Kolkata');

// --- PHPMailer Dependencies (REQUIRED for Email Sending) ---
// ⚠️ Ensure 'vendor/autoload.php' is the correct path to your Composer dependencies!
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// --- ACADEMIC CALENDAR NON-TEACHING DATES (Manual Extraction) ---
$non_teaching_dates = [
    '2025-08-15', 
    '2025-09-22', 
    '2025-09-23', 
    '2025-09-24', 
    '2025-10-06', 
    '2025-10-07', 
    '2025-10-08', 
    '2025-11-14', 
    '2025-12-12', 
    '2025-12-13', 
    '2025-12-19', 
    '2025-12-20',
];

// --- Check for Weekend or Academic Non-Teaching Day ---
$current_day_name = date('D');
$current_date_ymd = date('Y-m-d');

$is_academic_non_teaching_day = in_array($current_date_ymd, $non_teaching_dates);
$is_weekend = ($current_day_name === 'Sat' || $current_day_name === 'Sun');
$isAttendanceDisabledBySchedule = $is_weekend || $is_academic_non_teaching_day;

// --- Check if attendance should be disabled (after 10:30 AM IST) ---
$isAttendanceClosedByTime = (time() > strtotime('today 10:30'));

// ---------------------------------------------------------------------------------
// *** DATABASE CONNECTION (using mysqli) ***
// ---------------------------------------------------------------------------------
$server='localhost';
$user='root';
$pw='';
$db='faculty_pool';
$con = mysqli_connect($server, $user, $pw, $db); 

$db_error = null;
if (mysqli_connect_errno()) {
    $db_error = "Failed to connect to MySQL: " . mysqli_connect_error();
}

// Session check
if(!isset($_SESSION['faculty_id'])) {
    header('Location:faculty-login.php');
    exit;
}

$faculty_id = $_SESSION['faculty_id'] ?? 101; 
$faculty_name = $_SESSION['faculty_name'] ?? "Dr. Sharma";

$appointments = []; // Array to hold fetched appointments

// --- CHECK IF ATTENDANCE IS ALREADY MARKED TODAY ---
$hasAttendanceBeenMarked = false;

if (isset($con) && !$db_error && !$isAttendanceDisabledBySchedule) {
    $safe_faculty_id = mysqli_real_escape_string($con, $faculty_id);
    
    $check_sql = "SELECT COUNT(*) AS count FROM attendance WHERE faculty_id = '$safe_faculty_id' AND Date = '$current_date_ymd'";
    $check_result = mysqli_query($con, $check_sql);

    if ($check_result && mysqli_fetch_assoc($check_result)['count'] > 0) {
        $hasAttendanceBeenMarked = true;
    }
}

// --- FINAL ATTENDANCE DISABLE CONDITION (UPDATED) ---
$isAttendanceDisabled = $isAttendanceClosedByTime || $hasAttendanceBeenMarked || $isAttendanceDisabledBySchedule;

// ----------------------------------------------------------------------
// *** NEW: PHPMailer Email Function ***
// ----------------------------------------------------------------------

/**
 * Sends an email notification to the student about their appointment status.
 */
function sendAppointmentEmail($student_email, $student_name, $appointment_status, $slot_date, $slot_time, $faculty_name, $reason = '') {
    $mail = new PHPMailer(true);
    
    // Customize email content based on status
    $status_text = strtoupper($appointment_status);
    $subject = "Your Appointment with $faculty_name has been $status_text";
    $body = "<h2>Appointment Status Update</h2>";
    $body .= "<p>Dear <strong>" . htmlspecialchars($student_name) . "</strong>,</p>";
    $body .= "<p>Your appointment request with <strong>" . htmlspecialchars($faculty_name) . "</strong> for <strong>" . htmlspecialchars($slot_date) . "</strong> at <strong>" . htmlspecialchars($slot_time) . "</strong> has been <strong>$status_text</strong>.</p>";

    if ($appointment_status === 'Declined' && $reason) {
        $body .= "<div style='background:#f8d7da; padding:10px; border-radius:5px; border-left: 5px solid #dc3545;'>";
        $body .= "<p style='margin:0; color:#721c24;'><strong>Faculty Reason for Decline:</strong> " . htmlspecialchars($reason) . "</p>";
        $body .= "</div>";
    } elseif ($appointment_status === 'Approved') {
        $body .= "<p style='color:#155724;'>We look forward to your visit. Please be on time.</p>";
    }
    
    $body .= "<p>Thank you.</p>";
    $body .= "<hr><p style='font-size:12px; color:#6c757d;'>Faculty Pool System</p>";


    try {
        // --- SMTP CONFIGURATION (Use your actual settings) ---
        // ⚠️ You must replace these with your actual SMTP credentials if they change
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'facultypoolthapar@gmail.com';     
        $mail->Password   = 'xrpvcgnjkqofjlta';   
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465;

        $mail->setFrom('facultypoolthapar@gmail.com', 'Faculty Pool');
        $mail->addAddress($student_email, $student_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Plain text fallback

        $mail->send();
        return true; 
    } catch (Exception $e) {
        // Log the detailed error but return a generic failure
        error_log("Mailer Error to " . $student_email . ": " . $mail->ErrorInfo);
        return false; 
    }
}


// ----------------------------------------------------------------------
// *** APPOINTMENT FETCH LOGIC (SIMPLIFIED and DIRECT) ***
// ----------------------------------------------------------------------
// Fetches appointments directly without complex date/time conversion logic.

if (isset($con) && !$db_error) {
    $safe_faculty_id = mysqli_real_escape_string($con, $faculty_id);
    
    // Fetch all Pending and Approved appointments for the faculty member.
    $sql = "
        SELECT 
            id, student_name, student_email, contact_number, reason, slot_date, slot_time, status 
        FROM appointments 
        WHERE 
            faculty_id = '$safe_faculty_id' 
            AND status IN ('pending', 'approved')
        ORDER BY slot_date ASC, slot_time ASC
    ";
    
    $result = mysqli_query($con, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $appointments[] = $row;
        }
    } else {
        error_log("Appointment Fetch Error: " . mysqli_error($con));
        $db_error = "Could not fetch appointments: Database error. Check logs for details."; 
    }
} else {
    $db_error = $db_error ?? "Database connection not found or failed.";
}


// -----------------------------------------------------------
// --- MODIFIED: HANDLER FOR ALL GET ACTIONS (With Email) ---
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['action'])) {
    
    $action = $_GET['action'];
    $safe_faculty_id = mysqli_real_escape_string($con, $faculty_id);
    $appointment_id = (int)($_GET['appointment_id'] ?? 0); // Use 0 for attendance action

    // Check for database connection
    if (!isset($con) || $db_error) {
        $db_error = "Database connection unavailable for action.";
    } elseif ($action !== 'mark_attendance' && !$appointment_id) {
         $db_error = 'Error: Missing appointment ID for GET action.';
    }
    
    // --- STEP 1: Fetch Appointment Details BEFORE Update (Needed for Email) ---
    $appointment_data = null;
    $safe_appointment_id = mysqli_real_escape_string($con, $appointment_id);

    if (in_array($action, ['accept_appointment', 'decline_appointment']) && $appointment_id) {
        $fetch_sql = "
            SELECT student_name, student_email, slot_date, slot_time 
            FROM appointments 
            WHERE id = '$safe_appointment_id' AND faculty_id = '$safe_faculty_id'
        ";
        $fetch_result = mysqli_query($con, $fetch_sql);

        if ($fetch_result && mysqli_num_rows($fetch_result) === 1) {
            $appointment_data = mysqli_fetch_assoc($fetch_result);
        } else {
            // Only set a DB error if the action requires an appointment ID and it failed to fetch
            $db_error = $db_error ?? 'Appointment not found or not assigned to this faculty.';
        }
    }

    
    // --- 2. Handle 'accept_appointment' ---
    if ($action === 'accept_appointment' && $appointment_data) {
        
        $sql = "UPDATE appointments SET status = 'Approved' WHERE id = '$safe_appointment_id' AND faculty_id = '$safe_faculty_id'";
        
        if (mysqli_query($con, $sql)) {
            
            // *** EMAIL INTEGRATION FOR ACCEPT ***
            if (sendAppointmentEmail(
                $appointment_data['student_email'], 
                $appointment_data['student_name'], 
                'Approved', 
                $appointment_data['slot_date'], 
                $appointment_data['slot_time'], 
                $faculty_name
            )) {
                $_SESSION['temp_message'] = "Appointment for {$appointment_data['student_name']} Approved. Student notified via email.";
            } else {
                $_SESSION['temp_message'] = "Appointment Approved. **FAILED to send email notification** to student. Check system logs.";
            }
            // **********************************

            header('Location: faculty_dashboard.php'); 
            exit;
        } else {
            error_log("Accept Error (GET): " . mysqli_error($con));
            $db_error = 'Accept failed: SQL Error. Check logs.';
        }
    }
    
    // --- 3. Handle 'decline_appointment' ---
    elseif ($action === 'decline_appointment' && $appointment_data && isset($_GET['reason'])) {
        
        $reason = filter_input(INPUT_GET, 'reason', FILTER_SANITIZE_STRING);

        if (empty($reason)) {
            $db_error = 'Decline reason cannot be empty.';
        } else {
            $safe_reason = mysqli_real_escape_string($con, $reason);
            
            $sql = "UPDATE appointments SET status = 'Declined', reason1 = '$safe_reason' WHERE id = '$safe_appointment_id' AND faculty_id = '$safe_faculty_id'";
            if (mysqli_query($con, $sql)) {

                // *** EMAIL INTEGRATION FOR DECLINE ***
                if (sendAppointmentEmail(
                    $appointment_data['student_email'], 
                    $appointment_data['student_name'], 
                    'Declined', 
                    $appointment_data['slot_date'], 
                    $appointment_data['slot_time'], 
                    $faculty_name,
                    $reason
                )) {
                     $_SESSION['temp_message'] = "Appointment for {$appointment_data['student_name']} Declined. Student notified via email.";
                } else {
                     $_SESSION['temp_message'] = "Appointment Declined. **FAILED to send email notification** to student. Check system logs.";
                }
                // **********************************

                 header('Location: faculty_dashboard.php'); 
                 exit;
            } else {
                error_log("Decline Error (GET): " . mysqli_error($con));
                $db_error = 'Decline failed: SQL Error.';
            }
        }
    }
    
    // --- 4. Handle 'mark_attendance' (Existing logic) ---
    elseif ($action === 'mark_attendance' && isset($_GET['status'])) {
        
        if ($isAttendanceDisabledBySchedule) {
             $db_error = 'Attendance is disabled today (Weekend or Academic Holiday).';
        } elseif ($isAttendanceClosedByTime) { 
            $db_error = 'Attendance can only be marked before 10:30 AM.';
        } elseif ($hasAttendanceBeenMarked) { 
            $db_error = 'Attendance has already been marked for today.';
        } else {
            $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING); 
            
            // SAVE TO DATABASE
            $current_date = date('Y-m-d');
            $safe_status = mysqli_real_escape_string($con, $status); 
            
            $sql="INSERT INTO `attendance`(`faculty_id`, `Attendance`, `Date`) VALUES ('$faculty_id','$safe_status','$current_date')";
            $result=mysqli_query($con,$sql);
            
            if ($result) {
                $_SESSION['temp_message'] = "Attendance marked as $status.";
            } else {
                error_log("Attendance Insert Error: " . mysqli_error($con));
                $db_error = 'Failed to mark attendance: Database error.';
            }

            header('Location: faculty_dashboard.php'); // Reload page
            exit;
        }
    }
}
// --- END: GET HANDLER ---
// -----------------------------------------------------------


// -----------------------------------------------------------
// 2. HTML STRUCTURE (Main Page Content)
// -----------------------------------------------------------

// Check for and display temporary messages (e.g., from attendance or appointment actions)
$temp_message = null;
if (isset($_SESSION['temp_message'])) {
    $temp_message = $_SESSION['temp_message'];
    unset($_SESSION['temp_message']); // Clear it after reading
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #8B0000; /* Dark Red/Maroon */
            --primary-light: #A52A2A;
            --main-bg: #f8f9fa; 
            --card-bg: #ffffff;
            --pending-color: orange;
            --approved-color: #198754; /* Bootstrap success green */
            --danger-color: #dc3545; /* Bootstrap danger red */
            --border-color: #e0e0e0;
            --text-primary: #333;
            --text-secondary: #666;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Poppins', Arial, sans-serif;
        }

        /* ------------------- HEADER BAR ------------------- */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 24px 50px;
            box-shadow: 0 6px 20px rgba(139, 0, 0, 0.3);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .dashboard-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35), transparent);
        }
        .dashboard-header h1 {
            font-weight: 800;
            font-size: 1.9rem;
            text-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        .dashboard-header p { opacity: 0.9; margin-bottom: 0; }
        .back-link { color: #fff; text-decoration: none; font-weight: 600; opacity: 0.95; padding: 6px 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.5); font-size: 0.9rem; }
        .back-link:hover { opacity: 1; background: rgba(255,255,255,0.12); color: #fff; }

        /* ------------------- MAIN CONTENT CARDS ------------------- */
        .content-area { padding: 0 50px 50px; }

        .custom-card {
            background: linear-gradient(145deg, #ffffff 0%, #fafafa 100%);
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
            border: 1px solid var(--border-color);
            position: relative; overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.5s ease both;
        }
        .custom-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg,var(--primary-color),var(--primary-light)); transform: scaleX(0); transform-origin: left; transition: transform 0.3s ease; }
        .custom-card:hover::before { transform: scaleX(1); }
        .custom-card:hover { box-shadow: 0 14px 32px rgba(139, 0, 0, 0.15); transform: translateY(-4px); }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px);} to { opacity: 1; transform: translateY(0);} }

        /* Bounce hover animation for all cards */
        @keyframes cardBounce { 0%{transform: translateY(0) scale(1);} 40%{transform: translateY(-6px) scale(1.01);} 70%{transform: translateY(-2px) scale(1.005);} 100%{transform: translateY(0) scale(1);} }
        .custom-card:hover{ animation: cardBounce .6s ease; }
        .appointment-stack:hover{ animation: cardBounce .6s ease; }

        /* Attendance Card Specifics */
        #attendance-card { text-align: center; }
        #attendance-card h4 { font-weight: 700; margin-bottom: 20px; color: var(--text-primary); }
        
        .btn-theme { background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%); border-color: var(--primary-color); color: #fff; box-shadow: 0 6px 16px rgba(139,0,0,0.25); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .btn-theme:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(139,0,0,0.3); color: #fff; }
        .btn-theme:active { transform: translateY(0); }

        /* Custom Radio Button Colors and Pills */
        .attendance-radio-group .form-check { margin-right: 10px; }
        .attendance-radio-group .form-check-label { padding: 6px 10px; border-radius: 999px; background: rgba(0,0,0,0.03); border: 1px solid #e6e6e6; font-weight: 600; }
        .attendance-radio-group input[type="radio"] { cursor: pointer; }
        .attendance-radio-group input[type="radio"]:checked + label { color: #fff; border-color: transparent; }
        .attendance-radio-group .form-check-input:checked[value="Present"] + label { background: var(--approved-color); }
        .attendance-radio-group .form-check-input:checked[value="On Leave"] + label { background: var(--pending-color); }
        .attendance-radio-group .form-check-input:checked[value="Sick"] + label { background: var(--danger-color); }

        /* Appointment Card Specifics */
        .appointment-stack { border: 1px solid #ddd; border-radius: 12px; padding: 15px; margin-bottom: 15px; background-color: #fcfcfc; position: relative; transition: opacity 0.3s ease-in-out, transform 0.25s ease, box-shadow 0.25s ease; }
        .appointment-stack:hover { transform: translateY(-3px); box-shadow: 0 10px 22px rgba(0,0,0,0.08); }
        .appointment-stack::before { content: ''; position: absolute; left: 0; top: 0; width: 4px; height: 100%; background: linear-gradient(180deg,var(--primary-color),var(--primary-light)); opacity: .35; border-radius: 12px 0 0 12px; }

        .appointment-stack .status-badge { position: absolute; top: 15px; right: 15px; padding: 2px 8px; border-radius: 5px; font-size: 0.8rem; font-weight: 700; color: white; }
        .status-badge-pending { background-color: var(--pending-color); }
        .status-badge-approved { background-color: var(--approved-color); }
        
        .appointment-stack p { margin-bottom: 4px; font-size: 0.95rem; color: var(--text-secondary); }
        .appointment-stack p strong { display: inline-block; width: 110px; color: var(--text-primary); font-weight: 700; }
        
        /* Modal Styling */
        #declineModal .modal-header { background: var(--primary-color); color: white; }
        #declineModal .modal-footer .btn-danger { background-color: var(--primary-color); border-color: var(--primary-color); }

        /* Responsive improvements */
        @media (max-width: 992px) {
            .dashboard-header { padding: 18px 24px; }
            .content-area { padding: 0 24px 36px; }
            .back-link { padding: 6px 9px; font-size: 0.85rem; }
        }
        @media (max-width: 768px) {
            .dashboard-header { padding: 14px 16px; }
            .dashboard-header h1 { font-size: 1.5rem; }
            .content-area { padding: 0 16px 28px; }
            .custom-card { padding: 20px; border-radius: 16px; }
            .appointment-stack p strong { width: 96px; }
            .back-link { padding: 5px 8px; font-size: 0.82rem; }
        }
        @media (max-width: 576px) {
            .dashboard-header { padding: 12px 12px; }
            .content-area { padding: 0 12px 20px; }
            .custom-card { padding: 16px; border-radius: 14px; }
            .appointment-stack { padding: 12px; }
            .appointment-stack .status-badge { top: 10px; right: 10px; font-size: 0.7rem; }
            .back-link { padding: 5px 8px; font-size: 0.8rem; }
        }
    </style>
</head>
<body>

<header class="dashboard-header">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="back-link me-3">
                <i class="fas fa-home me-1"></i> Home
            </a>
            <div>
                <h1>Faculty Dashboard</h1>
                <p>Welcome, <strong><?= htmlspecialchars($faculty_name) ?></strong></p>
            </div>
        </div>
        <a href="faculty-logout.php" class="back-link">
            <i class="fas fa-sign-out-alt me-1"></i> Logout
        </a>
    </div>
    </header>

<div class="container-fluid content-area">

    <?php if (isset($db_error) && $db_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($db_error) ?></div>
    <?php endif; ?>
    
    <?php if (isset($temp_message) && $temp_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($temp_message) ?></div>
    <?php endif; ?>

    <div class="row">
        
        <div class="col-12 col-md-4 mb-4">
            <div class="custom-card text-center mb-4">
                <div class="mb-2"><i class="fas fa-user-tie fa-3x text-danger"></i></div>
                <h4 class="mb-1">Welcome</h4>
                <div class="text-secondary">Hello, <strong><?= htmlspecialchars($faculty_name) ?></strong></div>
            </div>
            <div id="attendance-card" class="custom-card">
                <h4>Mark Your Attendance</h4>
                
                <form id="attendanceForm" method="GET">
                    
                    <fieldset <?php if ($isAttendanceDisabled) echo 'disabled'; ?>>
                        <input type="hidden" name="action" value="mark_attendance">
                        
                        <div class="mb-4 attendance-radio-group">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="statusPresent" value="Present" required>
                                <label class="form-check-label text-success" for="statusPresent">
                                    <i class="fas fa-user-check me-1"></i> Present
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="statusLeave" value="On Leave">
                                <label class="form-check-label text-warning" for="statusLeave">
                                    <i class="fas fa-house-user me-1"></i> On Leave
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="statusSick" value="Sick">
                                <label class="form-check-label text-danger" for="statusSick">
                                    <i class="fas fa-bed me-1"></i> Sick
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-theme w-100">
                            <i class="fas fa-check-circle me-2"></i> Mark Attendance
                        </button>
                    </fieldset>

                    <?php if ($isAttendanceDisabled): ?>
                        <div id="attendanceMessage" class="mt-3 alert alert-warning py-2">
                            <?php if ($hasAttendanceBeenMarked): ?>
                                **Attendance already marked for today.**
                            <?php elseif ($isAttendanceDisabledBySchedule): ?>
                                **Attendance is disabled.** Today is a Non-Teaching Day (<?php echo $current_day_name; ?> or Academic Holiday).
                            <?php elseif ($isAttendanceClosedByTime): ?>
                                Attendance marking is closed for today (10:30 AM cutoff).
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div id="attendanceMessage" class="mt-3" style="display:none;"></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="custom-card">
                <h4 class="text-start">Student Appointments</h4>
                
                <div id="appointment-list">
                    <?php if (empty($appointments)): ?>
                        <div class="alert alert-info text-center">No pending or approved appointments found.</div>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
                        <div class="appointment-stack" data-appointment-id="<?= $appointment['id'] ?>">
                            <span class="status-badge status-badge-<?= strtolower(htmlspecialchars($appointment['status'])) ?>">
                                <?= htmlspecialchars($appointment['status']) ?>
                            </span>
                            
                            <p><strong>Student Name:</strong> <?= htmlspecialchars($appointment['student_name']) ?></p>
                            <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($appointment['student_email']) ?>"><?= htmlspecialchars($appointment['student_email']) ?></a></p>
                            <p><strong>Contact:</strong> <?= htmlspecialchars($appointment['contact_number']) ?></p> 
                            <p><strong>Reason:</strong> <?= htmlspecialchars($appointment['reason']) ?></p>
                            <p><strong>Date & Time:</strong> <?= htmlspecialchars($appointment['slot_date']) ?> at <?= htmlspecialchars($appointment['slot_time']) ?></p> 

                            <div class="mt-3 d-flex justify-content-end gap-2">
                                <?php if (strtolower($appointment['status']) === 'pending'): ?>
                                
                                <a href="faculty_dashboard.php?action=accept_appointment&appointment_id=<?= $appointment['id'] ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-check me-1"></i> Accept
                                </a>
                                
                                <button class="btn btn-danger btn-sm appointment-action" data-action="decline-prep" data-id="<?= $appointment['id'] ?>" data-bs-toggle="modal" data-bs-target="#declineModal">
                                    <i class="fas fa-times me-1"></i> Decline
                                </button>
                                <?php else: ?>
                                <span class="badge bg-success p-2"><i class="fas fa-calendar-check me-1"></i> Approved</span>
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

<div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="declineModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Reason for Declining Appointment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="declineForm" method="GET">
                <input type="hidden" name="action" value="decline_appointment">
                <input type="hidden" name="appointment_id" id="declineAppointmentId">
                <div class="modal-body">
                    <div class="mb-3">
                        <textarea class="form-control" id="declineReason" name="reason" rows="3" required placeholder="Please provide your reason..."></textarea>
                    </div>
                    <div id="declineMessage" class="mt-2 text-center" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane me-1"></i> Submit Reason</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const appointmentList = document.getElementById('appointment-list');
        const attendanceForm = document.getElementById('attendanceForm');
        const attendanceMsg = document.getElementById('attendanceMessage');

        // --- Appointment Action Handler (Decline Prep) ---
        appointmentList.addEventListener('click', function(e) {
            const button = e.target.closest('.appointment-action');
            if (!button) return;

            const action = button.getAttribute('data-action');

            if (action === 'decline-prep') {
                const appointmentId = button.getAttribute('data-id');
                // Prepare the modal before it opens
                document.getElementById('declineAppointmentId').value = appointmentId;
                document.getElementById('declineReason').value = '';
                document.getElementById('declineMessage').style.display = 'none';
            }
        });

        // --- Attendance selection feedback (no structure/text change) ---
        attendanceForm?.addEventListener('change', function(e){
            const selected = attendanceForm.querySelector('input[name="status"]:checked');
            if (!selected || !attendanceMsg) return;
            const mapIcon = { 'Present': 'fa-user-check text-success', 'On Leave': 'fa-house-user text-warning', 'Sick': 'fa-bed text-danger' };
            attendanceMsg.className = 'mt-3 alert alert-info py-2';
            attendanceMsg.style.display = '';
            attendanceMsg.innerHTML = `<i class="fas ${mapIcon[selected.value] || 'fa-info-circle'} me-2"></i> Selected: <strong>${selected.value}</strong>. You can now submit to mark attendance.`;
        });

        // --- Stagger animation for appointments ---
        const stacks = document.querySelectorAll('.appointment-stack');
        stacks.forEach((el, idx)=>{
            el.style.animation = `fadeInUp 0.45s ease ${idx * 60}ms both`;
        });
    });
</script>
</body>
</html>