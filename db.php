<?php
$host = 'localhost';
$db = 'college_diary';
$user = 'root'; // замените на ваше имя пользователя
$pass = 'root'; // замените на ваш пароль

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
