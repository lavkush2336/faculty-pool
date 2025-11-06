<?php
// faculty-dashboard.php - The complete working file for the refined dashboard design

// -----------------------------------------------------------
// 1. PHP SETUP & MOCK DATA & REQUEST HANDLING
// -----------------------------------------------------------
session_start();
// In a real application, you must check for a valid session.
// If not logged in: header('Location: faculty-login.php'); exit;

// *** MOCK DATA & CONNECTION ASSUMPTIONS ***
// Use session data for personalization
$faculty_id = $_SESSION['faculty_id'] ?? 101; 
$faculty_name = $_SESSION['faculty_name'] ?? "Dr. Sharma";

// Mock Appointment Data
$mock_appointments = [
    [
        'id' => 1,
        'student_name' => 'John Doe',
        'student_email' => 'john.add@example.com',
        'contact' => '9876543210',
        'time_slot' => '2023-11-15 14:00', // Example ISO format
        'reason' => 'Discussion about project proposal',
        'status' => 'Pending' 
    ],
    [
        'id' => 2,
        'student_name' => 'Jane Smith',
        'student_email' => 'jane.s@example.com',
        'contact' => '9988776655',
        'time_slot' => '2023-11-15 16:30',
        'reason' => 'Review of Thesis Chapter 1',
        'status' => 'Pending'
    ],
];

// --- Handle AJAX/POST Requests for Actions (Attendance, Accept, Decline) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid action or missing parameters.'];

    $action = $_POST['action'] ?? '';
    $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : null;

    if ($action === 'mark_attendance' && isset($_POST['status'])) {
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING); 
        // Implement Real DB INSERT/UPDATE for Attendance here
        $response = ['success' => true, 'message' => "Attendance marked as **$status** for **$faculty_name**."];

    } elseif ($action === 'accept_appointment' && $appointment_id) {
        // Implement Real DB UPDATE for Appointment Status='Accepted' here
        $response = ['success' => true, 'message' => "Appointment #$appointment_id accepted!"];

    } elseif ($action === 'decline_appointment' && $appointment_id && isset($_POST['reason'])) {
        $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
        if (empty($reason)) {
            $response = ['success' => false, 'message' => 'Decline reason cannot be empty.'];
        } else {
            // Implement Real DB UPDATE for Appointment Status='Declined' and save reason here
            $response = ['success' => true, 'message' => "Appointment #$appointment_id declined. Reason: $reason"];
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
        #attendance-card .present-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        #attendance-card h4 {
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        .attendance-radio-group label {
            font-weight: 600;
            color: var(--primary-color);
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
            background-color: var(--pending-color);
            color: white;
            padding: 2px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
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
        .appointment-stack h6 {
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }
        
        /* Modal Styling to match the decline dialog box image */
        #declineModal .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        #declineModal .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            font-weight: 600;
        }
        #declineModal .btn-danger {
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
    <div class="row">
        
        <div class="col-12 col-md-4 mb-4">
            <div id="attendance-card" class="custom-card">
                <h4>Mark Your Attendance</h4>
                <form id="attendanceForm">
                    <input type="hidden" name="action" value="mark_attendance">
                    
                    <div class="mb-4 attendance-radio-group">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" id="statusPresent" value="Present" required>
                            <label class="form-check-label text-success" for="statusPresent">Present</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" id="statusLeave" value="On Leave">
                            <label class="form-check-label text-warning" for="statusLeave">On Leave</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" id="statusSick" value="Sick">
                            <label class="form-check-label text-danger" for="statusSick">Sick</label>
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
                    <?php if (empty($mock_appointments)): ?>
                        <div class="alert alert-info text-center">No pending appointments found.</div>
                    <?php else: ?>
                        <?php foreach ($mock_appointments as $appointment): ?>
                        <div class="appointment-stack" data-appointment-id="<?= $appointment['id'] ?>">
                            <span class="status-badge">Pending</span>
                            <p><strong>Student Name:</strong> <?= htmlspecialchars($appointment['student_name']) ?></p>
                            <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($appointment['student_email']) ?>"><?= htmlspecialchars($appointment['student_email']) ?></a></p>
                            <p><strong>Contact:</strong> <?= htmlspecialchars($appointment['contact']) ?></p>
                            <p><strong>Reason:</strong> <?= htmlspecialchars($appointment['reason']) ?></p>
                            <p><strong>Time Slot:</strong> <?= date('Y-m-d H:i', strtotime($appointment['time_slot'])) ?></p>

                            <div class="mt-3 d-flex justify-content-end gap-2">
                                <button class="btn btn-success btn-sm appointment-action" data-action="accept" data-id="<?= $appointment['id'] ?>">
                                    Accept
                                </button>
                                <button class="btn btn-danger btn-sm appointment-action" data-action="decline-prep" data-id="<?= $appointment['id'] ?>" data-bs-toggle="modal" data-bs-target="#declineModal">
                                    Decline
                                </button>
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
                <h5 class="modal-title" id="declineModalLabel">Reason for Declining Appointment</h5>
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
                const response = await fetch('faculty-dashboard.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    messageElement.className = 'mt-3 text-center alert alert-success py-2';
                    
                    if (appointmentId !== null) {
                        const stackToRemove = document.querySelector(`.appointment-stack[data-appointment-id="${appointmentId}"]`);
                        if (stackToRemove) {
                            stackToRemove.style.opacity = '0';
                            setTimeout(() => { 
                                stackToRemove.remove();
                                // If no more appointments, show a success message
                                if (appointmentList.children.length === 0) {
                                    appointmentList.innerHTML = '<div class="alert alert-success text-center">All pending appointments have been processed!</div>';
                                }
                            }, 300);
                        }
                    }
                } else {
                    messageElement.className = 'mt-3 text-center alert alert-danger py-2';
                }
                messageElement.textContent = result.message;

            } catch (error) {
                console.error('AJAX Error:', error);
                messageElement.className = 'mt-3 text-center alert alert-danger py-2';
                messageElement.textContent = 'A network error occurred. Check console.';
            }

            // Hide the message after 5 seconds for attendance/general feedback
            if (action === 'mark_attendance') {
                setTimeout(() => {
                    messageElement.style.display = 'none';
                }, 5000);
            }
        }

        // --- 1. Attendance Form Handler ---
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