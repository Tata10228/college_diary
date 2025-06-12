<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    http_response_code(403);
    exit;
}

$specialty_id = $_GET['specialty_id'] ?? null;

if (!$specialty_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, group_name FROM groups WHERE specialty_id = ? ORDER BY group_name");
$stmt->execute([$specialty_id]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($groups);
