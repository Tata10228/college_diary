<?php
session_start();
require 'db.php';

// Проверка доступа: только админ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Обработка формы создания расписания и звонков
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_lesson'])) {
        $group_id = $_POST['group_id'];
        $subject_id = $_POST['subject_id'];
        $day_of_week = $_POST['day_of_week']; // 1 - Понедельник ... 7 - Воскресенье
        $lesson_number = $_POST['lesson_number']; // номер пары в день
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        // Проверка заполнения
        if (!$group_id || !$subject_id || !$day_of_week || !$lesson_number || !$start_time || !$end_time) {
            $errors[] = "Заполните все поля.";
        } else {
            // Проверим, нет ли уже пары в это время для этой группы и номера пары
            $stmt = $pdo->prepare("SELECT id FROM schedule WHERE group_id = ? AND day_of_week = ? AND lesson_number = ?");
            $stmt->execute([$group_id, $day_of_week, $lesson_number]);
            if ($stmt->fetch()) {
                $errors[] = "На этот день и номер пары уже назначена пара для выбранной группы.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO schedule (group_id, subject_id, day_of_week, lesson_number, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$group_id, $subject_id, $day_of_week, $lesson_number, $start_time, $end_time]);
                $success = "Пара успешно добавлена в расписание.";
            }
        }
    }
}

// Получаем данные для селектов
$groups = $pdo->query("SELECT * FROM groups")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создание расписания</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Создание расписания пар и звонков</h2>

    <?php if ($errors): ?>
        <div style="color: #d9534f;">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?=htmlspecialchars($error)?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($success): ?>
        <div style="color: green; margin-bottom: 15px;">
            <?=htmlspecialchars($success)?>
        </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <label>Группа:</label><br>
        <select name="group_id" required>
            <option value="">Выберите группу</option>
            <?php foreach ($groups as $group): ?>
                <option value="<?= $group['id'] ?>"><?=htmlspecialchars($group['group_name'])?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Дисциплина:</label><br>
        <select name="subject_id" required>
            <option value="">Выберите дисциплину</option>
            <?php foreach ($subjects as $subject): ?>
                <option value="<?= $subject['id'] ?>"><?=htmlspecialchars($subject['subject_name'])?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>День недели:</label><br>
        <select name="day_of_week" required>
            <option value="">Выберите день</option>
            <option value="1">Понедельник</option>
            <option value="2">Вторник</option>
            <option value="3">Среда</option>
            <option value="4">Четверг</option>
            <option value="5">Пятница</option>
            <option value="6">Суббота</option>
            <option value="7">Воскресенье</option>
        </select><br><br>

        <label>Номер пары:</label><br>
        <input type="number" name="lesson_number" min="1" max="10" required placeholder="Номер пары"><br><br>

        <label>Время начала пары:</label><br>
        <input type="time" name="start_time" required><br><br>

        <label>Время окончания пары:</label><br>
        <input type="time" name="end_time" required><br><br>

        <button type="submit" name="create_lesson">Добавить пару</button>
    </form>

    <hr>

    <h3>Текущее расписание</h3>
    <?php
    // Получаем расписание с JOIN для отображения
    $stmt = $pdo->query("
        SELECT s.*, g.group_name, sub.subject_name
        FROM schedule s
        JOIN groups g ON s.group_id = g.id
        JOIN subjects sub ON s.subject_id = sub.id
        ORDER BY s.group_id, s.day_of_week, s.lesson_number
    ");
    $schedule = $stmt->fetchAll();

    if (!$schedule) {
        echo "<p>Расписание пока пустое.</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Группа</th><th>День недели</th><th>Номер пары</th><th>Дисциплина</th><th>Начало</th><th>Конец</th></tr>";
        $days = [1=>'Понедельник',2=>'Вторник',3=>'Среда',4=>'Четверг',5=>'Пятница',6=>'Суббота',7=>'Воскресенье'];
        foreach ($schedule as $row) {
            echo "<tr>";
            echo "<td>".htmlspecialchars($row['group_name'])."</td>";
            echo "<td>".$days[$row['day_of_week']]."</td>";
            echo "<td>".$row['lesson_number']."</td>";
            echo "<td>".htmlspecialchars($row['subject_name'])."</td>";
            echo "<td>".$row['start_time']."</td>";
            echo "<td>".$row['end_time']."</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>

    <br>
    <a href="admin.php">Назад в панель администратора</a>
</div>
</body>
</html>
