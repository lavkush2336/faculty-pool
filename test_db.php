<?php
// test_db.php
// This is a standalone file to ONLY test the connection.

// 🛑 STEP 1: DEFINE YOUR CREDENTIALS
// Based on your db.php:
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';     // Try 'root' or 'mysql' if this fails
$DB_NAME = 'faculty_pool';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// 🛑 STEP 2: ATTEMPT CONNECTION
try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    
    // IF SUCCESSFUL:
    echo "<h1>✅ SUCCESS: Database Connected!</h1>";
    echo "This confirms your credentials are correct: $DB_USER / $DB_PASS / $DB_NAME";

} catch (PDOException $e) {
    // IF FAILURE: THIS MESSAGE WILL APPEAR.
    echo "<h1>❌ FAILURE: Database Connection Failed!</h1>";
    echo "<p><strong>Check Credentials & Server Status.</strong></p>";
    echo "<p><strong>Error Detail:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Current DB Name being used: $DB_NAME</p>";
    echo "<p>Current User/Pass being used: $DB_USER / $DB_PASS (is it set to empty?)</p>";
}
?>