<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: admin.php");
        } elseif ($user['role'] == 'teacher') {
            header("Location: teacher.php");
        } else {
            header("Location: student.php");
        }
        exit;
    } else {
        echo "Неправильное имя пользователя или пароль.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Вход</h2>
        <form method="POST">
            <input type="text" name="username" required placeholder="Имя пользователя">
            <input type="password" name="password" required placeholder="Пароль">
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>
