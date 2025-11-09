<?php
$DB_HOST = 'sql110.infinityfree.com';
$DB_USER = 'if0_40356779';
$DB_PASS = 'Divyam2005';
$DB_NAME = 'if0_40356779_faculty_pool';
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
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit;
}
?>