<?php
// db.php - Database Connection Configuration

// ⚠️ CHANGE THESE TO MATCH YOUR LOCAL DATABASE SETUP ⚠️
$DB_HOST = 'localhost';
$DB_USER = 'root'; 
$DB_PASS = ''; // ⬅️ COMMON FIX: Try 'root' or use the actual password you set
$DB_NAME = 'faculty_pool'; 
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // This forced error message is your only window into the MySQL failure.
    die("<h1>❌ DATABASE CONNECTION FAILED!</h1>
         <p>Please check your MySQL server status and the credentials in <b>db.php</b>.</p>
         <p><b>Error Detail:</b> " . $e->getMessage() . "</p>
         <p><b>Configuration Used:</b> User={$DB_USER}, DB={$DB_NAME}</p>");
}
?>