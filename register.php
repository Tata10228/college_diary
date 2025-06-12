<?php
session_start();
require 'db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($username) < 3) {
        $errors[] = "Имя пользователя должно быть не менее 3 символов.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать не менее 6 символов.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Пароли не совпадают.";
    }

    // Роль всегда 'student'
    $role = 'student';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        $errors[] = "Пользователь с таким именем уже существует.";
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $passwordHash, $role]);

        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['role'] = $role;

        header("Location: student.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация студента</title>
    <link rel="stylesheet" href="../style.css">
    
</head>
<body>
    <div class="container">
        <h2>Регистрация студента</h2>

        <?php if (!empty($errors)): ?>
            <div style="color: #d9534f; margin-bottom: 15px;">
                <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?=htmlspecialchars($error)?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="text" name="username" required placeholder="Имя пользователя" value="<?=isset($username) ? htmlspecialchars($username) : ''?>">
            <input type="password" name="password" required placeholder="Пароль">
            <input type="password" name="confirm_password" required placeholder="Подтвердите пароль">

            <button type="submit">Зарегистрироваться</button>
        </form>
    </div>
</body>
</html>
