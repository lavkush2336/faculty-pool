<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    if ($q !== '') {
        $stmt = $pdo->prepare("SELECT id, sno, name, email FROM faculty_members WHERE name LIKE :q OR email LIKE :q ORDER BY id ASC");
        $stmt->execute([':q' => "%$q%"]);
    } else {
        $stmt = $pdo->query("SELECT id, sno, name, email FROM faculty_members ORDER BY id ASC");
    }

    $rows = $stmt->fetchAll();
    echo json_encode(['success' => true, 'count' => count($rows), 'data' => $rows], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>