# Faculty Pool

A full-stack web platform that connects students directly with faculty, giving every student fair, open access to academic guidance instead of leaving it to informal networks.

## Features
- Separate student and faculty portals with dedicated login, signup, and dashboards
- OTP-based email verification and forgot-password / reset-password flow (via PHPMailer)
- Faculty discovery and listing, filterable by department
- Project postings: faculty list research projects, students apply/bid with a project description upload
- Appointment booking between students and faculty
- Course and department directory
- CV upload for students

## Tech Stack
PHP · MySQL · HTML · CSS · JavaScript · PHPMailer (SMTP email)

## Database
Core tables: `student`, `faculty`, `departments`, `courses`, `appointments`, `projects`, `project_applications`, `attendance`, `student_otp`.

## My Contribution
Built as a team project (5 contributors). I was the primary contributor, handling:
- Core authentication flows for both students and faculty (login, signup, OTP verification, password reset)
- Database schema design and integration with PHP
- The faculty discovery/matching and appointment-booking logic

## Setup
1. Clone the repo and import `faculty_pool.sql` into MySQL.
2. Configure database credentials in `db.php`.
3. Configure SMTP credentials for PHPMailer (used for OTP and password-reset emails).
4. Serve the folder with a PHP-enabled server (e.g. XAMPP/WAMP or `php -S localhost:8000`).

## Screenshots
_Add screenshots of the student dashboard, faculty listing, and appointment booking here._
