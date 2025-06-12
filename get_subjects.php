<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    http_response_code(403);
    exit;
}

$teacher_id = $_SESSION['user_id'];
$specialty_id = $_GET['specialty_id'] ?? null;

if (!$specialty_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE specialty_id = ? AND teacher_id = ? ORDER BY subject_name");
$stmt->execute([$specialty_id, $teacher_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($subjects);
