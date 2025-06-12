<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Создание сущностей
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['create_specialty'])) {
        $specialty_name = trim($_POST['specialty_name']);
        if ($specialty_name) {
            $stmt = $pdo->prepare("INSERT INTO specialties (specialty_name) VALUES (?)");
            $stmt->execute([$name]);
        }
    }

    if (isset($_POST['create_student'])) {
        $username = $_POST['student_username'];
        $password = password_hash($_POST['student_password'], PASSWORD_DEFAULT);
        $role = 'student';

        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password, $role]);
    }

    if (isset($_POST['create_group'])) {
        $group_name = $_POST['group_name'];
        $specialty_id = $_POST['specialty_id'] ?: null; // может быть NULL

        $stmt = $pdo->prepare("INSERT INTO groups (group_name, specialty_id) VALUES (?, ?)");
        $stmt->execute([$group_name, $specialty_id]);
    }

    if (isset($_POST['add_to_group'])) {
        $group_id = $_POST['group_id'];
        $student_id = $_POST['student_id'];

        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, student_id) VALUES (?, ?)");
        $stmt->execute([$group_id, $student_id]);
    }

    if (isset($_POST['create_teacher'])) {
        $username = $_POST['teacher_username'];
        $password = password_hash($_POST['teacher_password'], PASSWORD_DEFAULT);
        $role = 'teacher';

        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password, $role]);
    }

    if (isset($_POST['create_subject'])) {
        $subject_name = $_POST['subject_name'];
        $teacher_id = $_POST['teacher_id'];
        $specialty_id = $_POST['subject_specialty_id'] ?: null;

        $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, teacher_id, specialty_id) VALUES (?, ?, ?)");
        $stmt->execute([$subject_name, $teacher_id, $specialty_id]);
    }
}

// Получаем данные для форм
$students = $pdo->query("SELECT * FROM users WHERE role = 'student'")->fetchAll();
$teachers = $pdo->query("SELECT * FROM users WHERE role = 'teacher'")->fetchAll();
$groups = $pdo->query("SELECT * FROM groups")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();
$specialties = $pdo->query("SELECT * FROM specialties")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Администратор</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Панель администратора</h2>

    <h3>Создать специальность</h3>
    <form method="POST">
        <input type="text" name="specialty_name" required placeholder="Название специальности">
        <button type="submit" name="create_specialty">Создать специальность</button>
    </form>

    <h3>Создать группу</h3>
    <form method="POST">
        <input type="text" name="group_name" required placeholder="Название группы">
        <select name="specialty_id" required>
            <option value="">Выберите специальность</option>
            <?php foreach ($specialties as $specialty): ?>
                <option value="<?php echo $specialty['id']; ?>"><?php echo htmlspecialchars($specialty['specialty_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="create_group">Создать группу</button>
    </form>

    <h3>Создать учебную дисциплину</h3>
    <form method="POST">
        <input type="text" name="subject_name" required placeholder="Название дисциплины">
        <select name="teacher_id" required>
            <option value="">Выберите учителя</option>
            <?php foreach ($teachers as $teacher): ?>
                <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['username']); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="subject_specialty_id" required>
            <option value="">Выберите специальность</option>
            <?php foreach ($specialties as $specialty): ?>
                <option value="<?php echo $specialty['id']; ?>"><?php echo htmlspecialchars($specialty['specialty_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="create_subject">Создать дисциплину</button>
    </form>

    <h3>Добавить студента в группу</h3>
    <form method="POST">
        <select name="group_id" required>
            <option value="">Выберите группу</option>
            <?php foreach ($groups as $group): ?>
                <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="student_id" required>
            <option value="">Выберите студента</option>
            <?php foreach ($students as $student): ?>
                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['username']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="add_to_group">Добавить студента в группу</button>
    </form>

    <h3>Список специальностей</h3>
    <ul>
        <?php foreach ($specialties as $specialty): ?>
            <li>
                <?php echo htmlspecialchars($specialty['specialty_name']); ?>
                <ul>
                    <li>Группы:
                        <ul>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM groups WHERE specialty_id = ?");
                            $stmt->execute([$specialty['id']]);
                            $specialty_groups = $stmt->fetchAll();
                            ?>
                            <?php foreach ($specialty_groups as $grp): ?>
                                <li><?php echo htmlspecialchars($grp['group_name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li>Дисциплины:
                        <ul>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM subjects WHERE specialty_id = ?");
                            $stmt->execute([$specialty['id']]);
                            $specialty_subjects = $stmt->fetchAll();
                            ?>
                            <?php foreach ($specialty_subjects as $subj): ?>
                                <li><?php echo htmlspecialchars($subj['subject_name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
            </li>
        <?php endforeach; ?>
    </ul>

    <p><a href="schedule.php">Создать расписание пар и звонков</a></p>
    <a href="index.php">Выход</a>
</div>
</body>
</html>
