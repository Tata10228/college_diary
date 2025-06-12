<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    http_response_code(403);
    exit;
}

$group_id = $_GET['group_id'] ?? null;

if (!$group_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.username 
    FROM users u
    JOIN group_members gm ON u.id = gm.student_id
    WHERE gm.group_id = ?
    ORDER BY u.username
");
$stmt->execute([$group_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($students);
