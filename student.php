<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// Получаем оценки студента по всем дисциплинам
$grades_stmt = $pdo->prepare("
    SELECT subjects.subject_name, grades.grade 
    FROM grades 
    JOIN subjects ON grades.subject_id = subjects.id 
    WHERE grades.student_id = ?
");
$grades_stmt->execute([$student_id]);
$grades = $grades_stmt->fetchAll();

// Получаем дисциплины для выбора домашнего задания
$subjects_stmt = $pdo->query("SELECT * FROM subjects");
$subjects = $subjects_stmt->fetchAll();

// Получаем домашние задания по выбранной дисциплине (если выбрана)
$homework = [];
if (isset($_POST['view_homework'])) {
    $homework_subject_id = $_POST['homework_subject_id'];
    $homework_stmt = $pdo->prepare("SELECT * FROM homework WHERE subject_id = ?");
    $homework_stmt->execute([$homework_subject_id]);
    $homework = $homework_stmt->fetchAll();
}

// Получаем id группы студента
$group_id_stmt = $pdo->prepare("SELECT group_id FROM group_members WHERE student_id = ?");
$group_id_stmt->execute([$student_id]);
$group_id = $group_id_stmt->fetchColumn();

$schedule = [];
$bells = [];

// Если группа найдена, получаем расписание и звонки
if ($group_id) {
    // Получаем расписание для группы, вместе с названием предмета
    $schedule_stmt = $pdo->prepare("
        SELECT schedule.day_of_week, schedule.lesson_number, schedule.start_time, schedule.end_time, subjects.subject_name 
        FROM schedule 
        JOIN subjects ON schedule.subject_id = subjects.id 
        WHERE schedule.group_id = ? 
        ORDER BY schedule.day_of_week, schedule.lesson_number
    ");
    $schedule_stmt->execute([$group_id]);
    $schedule = $schedule_stmt->fetchAll();

    // Получаем звонки (пары)
}

// Для отображения дней недели на русском
$daysOfWeek = [
    1 => 'Понедельник',
    2 => 'Вторник',
    3 => 'Среда',
    4 => 'Четверг',
    5 => 'Пятница',
    6 => 'Суббота',
    7 => 'Воскресенье'
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Студент</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table { border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px 12px; border: 1px solid #ccc; }
        h3, h4 { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Панель студента</h2>

        <h3>Мои оценки</h3>
        <table>
            <tr>
                <th>Дисциплина</th>
                <th>Оценка</th>
            </tr>
            <?php foreach ($grades as $grade): ?>
                <tr>
                    <td><?= htmlspecialchars($grade['subject_name']) ?></td>
                    <td><?= htmlspecialchars($grade['grade']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h3>Домашние задания</h3>
        <form method="POST">
            <select name="homework_subject_id" required>
                <option value="">Выберите дисциплину</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="view_homework">Посмотреть домашние задания</button>
        </form>

        <?php if (!empty($homework)): ?>
            <h4>Домашние задания по выбранной дисциплине</h4>
            <table>
                <tr>
                    <th>Описание</th>
                    <th>Срок сдачи</th>
                </tr>
                <?php foreach ($homework as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['description']) ?></td>
                        <td><?= htmlspecialchars($task['due_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php elseif (isset($_POST['view_homework'])): ?>
            <p>Нет домашних заданий для выбранной дисциплины.</p>
        <?php endif; ?>

        <h3>Расписание занятий</h3>

        <?php if (!$group_id): ?>
            <p>Вы не прикреплены ни к одной группе, расписание недоступно.</p>
        <?php else: ?>
            <?php if (empty($schedule)): ?>
                <p>Расписание для вашей группы пока отсутствует.</p>
            <?php else: ?>
                <?php
                // Группируем расписание по дням недели
                $grouped_schedule = [];
                foreach ($schedule as $lesson) {
                    $grouped_schedule[$lesson['day_of_week']][] = $lesson;
                }
                ?>
                <?php foreach ($grouped_schedule as $day => $lessons): ?>
                    <h4><?= $daysOfWeek[$day] ?? "День $day" ?></h4>
                    <table>
                        <tr>
                            <th>Номер пары</th>
                            <th>Дисциплина</th>
                            <th>Начало занятия</th>
                            <th>Конец занятия</th>
                        </tr>
                        <?php foreach ($lessons as $lesson): ?>
                            <?php
                            // Ищем звонок по номеру пары
                            $bellTime = '';
                            foreach ($bells as $bell) {
                                if ($bell['pair_number'] == $lesson['lesson_number']) {
                                    $bellTime = htmlspecialchars(substr($bell['start_time'], 0, 5)) . ' - ' . htmlspecialchars(substr($bell['end_time'], 0, 5));
                                    break;
                                }
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($lesson['lesson_number']) ?></td>
                                <td><?= htmlspecialchars($lesson['subject_name']) ?></td>
                                <td><?= htmlspecialchars(substr($lesson['start_time'], 0, 5)) ?></td>
                                <td><?= htmlspecialchars(substr($lesson['end_time'], 0, 5)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <a href="index.php">Выход</a>
</body>
</html>
