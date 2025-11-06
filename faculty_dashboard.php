<?php
// --- START: DEBUGGING ENABLED (TEMPORARY FIX FOR BLANK PAGE) ---
// These three lines force PHP to show all errors on the page.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 
// --- END: DEBUGGING ENABLED ---

// faculty-dashboard.php - The complete working file for the refined dashboard design

// -----------------------------------------------------------
// 1. PHP SETUP & DATABASE INTEGRATION
// -----------------------------------------------------------
session_start();
// In a real application, you must check for a valid session.
// If not logged in: header('Location: faculty-login.php'); exit;

// Set PHP Timezone to IST (India Standard Time) for all calculations
date_default_timezone_set('Asia/Kolkata');

// ---------------------------------------------------------------------------------
// *** DATABASE CONNECTION (MODIFIED to use mysqli approach as requested) ***
// ---------------------------------------------------------------------------------
$server='localhost';
$user='root';
$pw='';
$db='faculty_pool';
// Using $con for mysqli connection
$con = mysqli_connect($server, $user, $pw, $db); 

$db_error = null;
if (mysqli_connect_errno()) {
    $db_error = "Failed to connect to MySQL: " . mysqli_connect_error();
}

// Use session data for personalization
$faculty_id = $_SESSION['faculty_id'] ?? 101; 
$faculty_name = $_SESSION['faculty_name'] ?? "Dr. Sharma";

$appointments = []; // Array to hold fetched and processed appointments


/**
 * Fetches and processes appointments based on status, applying a wider IST cutoff
 * to ensure display in test environments. Filters out appointments older than 30 days.
 */
function fetchAppointments($con, $faculty_id) {
    global $db_error;
    
    // 1. Calculate the IST cutoff timestamp (30 Days Ago Midnight IST).
    $ist_cutoff_timestamp = strtotime('-30 days midnight');
    
    // 2. SQL Query: Fetch all Pending and Approved appointments for the faculty member.
    // WARNING: This query is *NOT* using prepared statements, which is a SECURITY RISK (SQL Injection).
    $sql = "
        SELECT 
            id, student_name, student_email, contact_number, reason, slot_time, status 
        FROM appointments 
        WHERE 
            faculty_id = '$faculty_id' 
            AND status IN ('pending', 'approved')
        ORDER BY slot_time ASC
    ";
    
    $result = mysqli_query($con, $sql);
    
    if (!$result) {
        error_log("Appointment Fetch Error: " . mysqli_error($con));
        $db_error = "Could not fetch appointments: Database error. Check logs for details."; 
        return [];
    }
    
    $raw_appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $raw_appointments[] = $row;
    }
    
    $filtered_appointments = [];
    $ist_timezone = new DateTimeZone('Asia/Kolkata');
    $gmt_timezone = new DateTimeZone('GMT');

    // 3. Process, Convert Time Zones, and Apply Cutoff Filter
    foreach ($raw_appointments as $appt) {
        // Ensure slot_time is not empty or invalid before attempting DateTime conversion
        if (empty($appt['slot_time'])) {
            error_log("Missing slot_time for Appointment #{$appt['id']}");
            continue; 
        }
        
        try {
            // Get the slot time as a DateTime object (it's stored in GMT/UTC in the DB)
            $gmt_time = new DateTime($appt['slot_time'], $gmt_timezone);
            
            // Convert the scheduled time to IST for comparison and display
            $ist_time = clone $gmt_time;
            $ist_time->setTimezone($ist_timezone);
            
            // Apply the **LOOSE** cutoff filter (last 30 days).
            if ($ist_time->getTimestamp() >= $ist_cutoff_timestamp) {
                
                $appt['time_slot_ist'] = $ist_time->format('Y-m-d H:i'); // Format for display
                $filtered_appointments[] = $appt;
            }
            
        } catch (Exception $e) {
            // Log issues with date parsing if any
            error_log("Date Parsing Error for Appointment #{$appt['id']}: " . $e->getMessage());
        }
    }
    
    return $filtered_appointments;
}

// Logic to call the fetching function
if (isset($con) && !$db_error) {
    $appointments = fetchAppointments($con, $faculty_id);
} else {
    // If $con is not set, or there was a connection error
    $db_error = $db_error ?? "Database connection (\$con) not found or failed.";
}


// -----------------------------------------------------------
// --- Handle AJAX/POST Requests for Actions (Attendance, Accept, Decline) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Action failed.'];

    $action = $_POST['action'];
    $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : null;

    // Use $con defined above for actions
    if (!isset($con) || $db_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection unavailable for action.']);
        exit;
    }
    
    // Escape variables to mitigate the risk from direct substitution (STILL INSECURE)
    $safe_appointment_id = mysqli_real_escape_string($con, $appointment_id);
    $safe_faculty_id = mysqli_real_escape_string($con, $faculty_id);


    if ($action === 'mark_attendance' && isset($_POST['status'])) {
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING); 
        // NOTE: Attendance logic is mocked for functional testing.
        $response = ['success' => true, 'message' => "Attendance marked as **$status**."];

    } elseif ($action === 'accept_appointment' && $appointment_id) {
        // *** CORE FUNCTIONALITY: SET STATUS TO 'Approved' ***
        $sql = "UPDATE appointments SET status = 'Approved' WHERE id = '$safe_appointment_id' AND faculty_id = '$safe_faculty_id'";
        if (mysqli_query($con, $sql)) {
            $response = ['success' => true, 'message' => "Appointment #$appointment_id Approved!"];
        } else {
            error_log("Accept Error: " . mysqli_error($con));
            $response['message'] = 'Accept failed: SQL Error. Error: ' . mysqli_error($con);
        }

    } elseif ($action === 'decline_appointment' && $appointment_id && isset($_POST['reason'])) {
        $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
        if (empty($reason)) {
            $response = ['success' => false, 'message' => 'Decline reason cannot be empty.'];
        } else {
            // Escape reason for security (STILL INSECURE without proper prepared statements)
            $safe_reason = mysqli_real_escape_string($con, $reason);
            
            // *** CORE FUNCTIONALITY: SET STATUS TO 'Declined' ***
            $sql = "UPDATE appointments SET status = 'Declined', reason = '$safe_reason' WHERE id = '$safe_appointment_id' AND faculty_id = '$safe_faculty_id'";
            if (mysqli_query($con, $sql)) {
                 $response = ['success' => true, 'message' => "Appointment #$appointment_id Declined. Reason saved."];
            } else {
                error_log("Decline Error: " . mysqli_error($con));
                $response['message'] = 'Decline failed: SQL Error. Error: ' . mysqli_error($con);
            }
        }
    }

    echo json_encode($response);
    exit; 
}
// -----------------------------------------------------------
// 2. HTML STRUCTURE (Main Page Content)
// -----------------------------------------------------------
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
            --main-bg: #f8f9fa; 
            --card-bg: #ffffff;
            --pending-color: orange;
            --approved-color: #198754; /* Bootstrap success green */
            --danger-color: #dc3545; /* Bootstrap danger red */
        }
        
        body {
            background-color: var(--main-bg);
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }

        /* ------------------- HEADER BAR ------------------- */
        .dashboard-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px 50px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        .dashboard-header h1 {
            font-weight: 700;
            font-size: 1.8rem;
        }
        .dashboard-header p {
            opacity: 0.8;
            margin-bottom: 0;
        }
        .back-link {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            opacity: 0.9;
        }
        .back-link:hover {
            opacity: 1;
            color: #fff;
        }

        /* ------------------- MAIN CONTENT CARDS ------------------- */
        .content-area {
            padding: 0 50px 50px;
        }

        .custom-card {
            background-color: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
            border: 1px solid #eee;
            transition: box-shadow 0.3s;
        }
        .custom-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        /* Attendance Card Specifics */
        #attendance-card {
            text-align: center;
        }
        #attendance-card h4 {
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
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

        /* Custom Radio Button Colors (Matching text colors) */
        .attendance-radio-group .form-check-input:checked[value="Present"] {
            background-color: var(--approved-color);
            border-color: var(--approved-color);
        }
        .attendance-radio-group .form-check-input:checked[value="On Leave"] {
            background-color: var(--pending-color);
            border-color: var(--pending-color);
        }
        .attendance-radio-group .form-check-input:checked[value="Sick"] {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }


        /* Appointment Card Specifics */
        .appointment-stack {
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fcfcfc;
            position: relative;
            transition: opacity 0.3s ease-in-out; 
        }

        .appointment-stack .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 2px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        .status-badge-pending { background-color: var(--pending-color); } /* Added status-badge-pending class */
        .status-badge-approved { background-color: var(--approved-color); } /* Added status-badge-approved class */
        
        .appointment-stack p {
            margin-bottom: 4px;
            font-size: 0.95rem;
            color: #555;
        }
        .appointment-stack p strong {
            display: inline-block;
            width: 110px;
            color: #333;
            font-weight: 600;
        }
        
        /* Modal Styling */
        #declineModal .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        /* Make Decline button red but the Submit Reason button theme colored */
        #declineModal .modal-footer .btn-danger {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>

<header class="dashboard-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Faculty Dashboard</h1>
            <p>Welcome, **<?= htmlspecialchars($faculty_name) ?>**</p>
        </div>
        <a href="faculty-login.php" class="back-link">
            <i class="fas fa-sign-out-alt me-1"></i> Logout
        </a>
    </div>
</header>

<div class="container-fluid content-area">
    <?php if (isset($db_error) && $db_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($db_error) ?></div>
    <?php endif; ?>
    <div class="row">
        
        <div class="col-12 col-md-4 mb-4">
            <div id="attendance-card" class="custom-card">
                <h4>Mark Your Attendance</h4>
                <form id="attendanceForm">
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
                    <div id="attendanceMessage" class="mt-3" style="display:none;"></div>
                </form>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="custom-card">
                <h4 class="text-start">Student Appointments</h4>
                
                <div id="appointment-list">
                    <?php if (empty($appointments)): ?>
                        <div class="alert alert-info text-center">No pending or approved appointments found in the last 30 days.</div>
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
                            <p><strong>Time Slot (IST):</strong> <?= htmlspecialchars($appointment['time_slot_ist']) ?></p> 

                            <div class="mt-3 d-flex justify-content-end gap-2">
                                <?php if (strtolower($appointment['status']) === 'pending'): ?>
                                <button class="btn btn-success btn-sm appointment-action" data-action="accept" data-id="<?= $appointment['id'] ?>">
                                    <i class="fas fa-check me-1"></i> Accept
                                </button>
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
            <form id="declineForm">
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
        const declineModal = document.getElementById('declineModal');
        const declineForm = document.getElementById('declineForm');
        const attendanceForm = document.getElementById('attendanceForm');
        const appointmentList = document.getElementById('appointment-list');
        const declineModalInstance = bootstrap.Modal.getOrCreateInstance(declineModal);

        /**
         * Generic function to handle all AJAX form submissions
         */
        async function submitAction(formData, messageElementId, appointmentId = null) {
            const messageElement = document.getElementById(messageElementId);
            const action = formData.get('action');

            messageElement.style.display = 'block';
            messageElement.className = 'mt-3 text-center alert alert-info py-2';
            messageElement.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';

            try {
                // Submit the form data to the same PHP file
                const response = await fetch('faculty-dashboard.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    messageElement.className = 'mt-3 text-center alert alert-success py-2';
                    
                    if (appointmentId !== null) {
                        const stackToUpdate = document.querySelector(`.appointment-stack[data-appointment-id="${appointmentId}"]`);
                        
                        // Handle Accept action: change status badge and remove buttons
                        if (action === 'accept_appointment' && stackToUpdate) {
                            const badge = stackToUpdate.querySelector('.status-badge');
                            badge.textContent = 'Approved';
                            badge.className = 'status-badge status-badge-approved'; // Use lowercase for class
                            
                            const buttonsContainer = stackToUpdate.querySelector('.justify-content-end');
                            if (buttonsContainer) {
                                buttonsContainer.innerHTML = '<span class="badge bg-success p-2"><i class="fas fa-calendar-check me-1"></i> Approved</span>';
                            }
                        }
                         // Handle Decline action: remove the card visually
                        else if (action === 'decline_appointment' && stackToUpdate) {
                            stackToUpdate.style.opacity = '0';
                            setTimeout(() => { 
                                stackToUpdate.remove();
                                // If no more appointments, show a message
                                if (appointmentList.children.length === 0) {
                                    appointmentList.innerHTML = '<div class="alert alert-info text-center">No pending or approved appointments found in the last 30 days.</div>';
                                }
                            }, 300);
                        }
                    }
                } else {
                    // Display the detailed error message returned from the PHP backend
                    messageElement.className = 'mt-3 text-center alert alert-danger py-2';
                }
                messageElement.textContent = result.message;

            } catch (error) {
                console.error('AJAX Error:', error);
                messageElement.className = 'mt-3 text-center alert alert-danger py-2';
                messageElement.textContent = 'A network error occurred. Check console for details.';
            }

            // Hide the message after 5 seconds for attendance/general feedback
            if (action === 'mark_attendance') {
                setTimeout(() => {
                    messageElement.style.display = 'none';
                }, 5000);
            }
        }

        // --- 1. Attendance Form Handler (Present/Leave/Sick) ---
        attendanceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            submitAction(formData, 'attendanceMessage');
        });

        // --- 2. Appointment Action Handler (Accept/Decline Prep) ---
        appointmentList.addEventListener('click', function(e) {
            const button = e.target.closest('.appointment-action');
            if (!button) return;

            const appointmentId = button.getAttribute('data-id');
            const action = button.getAttribute('data-action');

            if (action === 'accept') {
                const formData = new FormData();
                formData.append('action', 'accept_appointment');
                formData.append('appointment_id', appointmentId);
                
                // Use the attendance message space for general success feedback
                submitAction(formData, 'attendanceMessage', appointmentId); 

            } else if (action === 'decline-prep') {
                // Prepare the modal before it opens
                document.getElementById('declineAppointmentId').value = appointmentId;
                document.getElementById('declineReason').value = ''; 
                document.getElementById('declineMessage').style.display = 'none';
            }
        });

        // --- 3. Decline Form Handler (Inside Modal) ---
        declineForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const appointmentId = formData.get('appointment_id');
            
            if (formData.get('reason').trim() === '') {
                 document.getElementById('declineMessage').className = 'mt-3 text-center alert alert-warning py-2';
                 document.getElementById('declineMessage').textContent = 'Please provide a valid reason.';
                 document.getElementById('declineMessage').style.display = 'block';
                 return;
            }

            submitAction(formData, 'declineMessage', appointmentId).then(() => {
                // Close modal if the submission was successful
                if (document.getElementById('declineMessage').classList.contains('alert-success')) {
                    setTimeout(() => { declineModalInstance.hide(); }, 800); 
                }
            });
        });
    });
</script>
</body>
</html>