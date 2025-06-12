<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$message = '';

// Обработка POST выставления оценки и домашнего задания
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['set_grade'])) {
        $subject_id = $_POST['subject_id'] ?? null;
        $student_id = $_POST['student_id'] ?? null;
        $grade = $_POST['grade'] ?? null;

        if ($subject_id && $student_id && $grade !== null) {
            $stmt = $pdo->prepare("INSERT INTO grades (subject_id, student_id, grade) VALUES (?, ?, ?)
                                    ON DUPLICATE KEY UPDATE grade = ?");
            $stmt->execute([$subject_id, $student_id, $grade, $grade]);
            $message = "Оценка успешно выставлена.";
        } else {
            $message = "Ошибка: заполните все поля для выставления оценки.";
        }
    }

    if (isset($_POST['assign_homework'])) {
        $subject_id = $_POST['homework_subject_id'] ?? null;
        $description = $_POST['homework_description'] ?? null;
        $due_date = $_POST['homework_due_date'] ?? null;

        if ($subject_id && $description && $due_date) {
            $stmt = $pdo->prepare("INSERT INTO homework (subject_id, description, due_date) VALUES (?, ?, ?)");
            $stmt->execute([$subject_id, $description, $due_date]);
            $message = "Домашнее задание успешно задано.";
        } else {
            $message = "Ошибка: заполните все поля для задания домашнего задания.";
        }
    }
}

// Получаем специальности, связанные с учителем
$specialties_stmt = $pdo->prepare("
    SELECT DISTINCT sp.id, sp.name 
    FROM specialties sp
    JOIN subjects sub ON sub.specialty_id = sp.id
    WHERE sub.teacher_id = ?
    ORDER BY sp.name
");
$specialties_stmt->execute([$teacher_id]);
$specialties = $specialties_stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем группы (для просмотра расписания и оценок)
$groups_stmt = $pdo->query("SELECT * FROM groups ORDER BY group_name");
$groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель учителя</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h2>Панель учителя</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h3>Выставить оценку</h3>
    <form method="POST" id="gradeForm">
        <label for="specialty_select">Выберите специальность</label>
        <select id="specialty_select" required>
            <option value="">-- выберите специальность --</option>
            <?php foreach ($specialties as $sp): ?>
                <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="subject_select">Выберите дисциплину</label>
        <select name="subject_id" id="subject_select" required disabled>
            <option value="">-- сначала выберите специальность --</option>
        </select>

        <label for="group_select">Выберите группу</label>
        <select id="group_select" required disabled>
            <option value="">-- сначала выберите специальность --</option>
        </select>

        <label for="student_select">Выберите студента</label>
        <select name="student_id" id="student_select" required disabled>
            <option value="">-- сначала выберите группу --</option>
        </select>

        <label for="grade_input">Оценка</label>
        <input type="number" name="grade" id="grade_input" required placeholder="Оценка" step="0.01" min="0" max="100">

        <button type="submit" name="set_grade">Выставить оценку</button>
    </form>

    <hr>

    <h3>Задать домашнее задание</h3>
    <form method="POST" id="homeworkForm">
        <label for="homework_specialty_select">Выберите специальность</label>
        <select id="homework_specialty_select" required>
            <option value="">-- выберите специальность --</option>
            <?php foreach ($specialties as $sp): ?>
                <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="homework_subject_select">Выберите дисциплину</label>
        <select name="homework_subject_id" id="homework_subject_select" required disabled>
            <option value="">-- сначала выберите специальность --</option>
        </select>

        <label for="homework_description">Описание задания</label>
        <textarea name="homework_description" id="homework_description" required placeholder="Описание задания"></textarea>

        <label for="homework_due_date">Срок сдачи</label>
        <input type="date" name="homework_due_date" id="homework_due_date" required>

        <button type="submit" name="assign_homework">Задать домашнее задание</button>
    </form>

    <hr>

    <h3>Просмотр оценок</h3>
    <form method="GET" id="viewGradesForm">
        <label for="view_grades_specialty">Выберите специальность</label>
        <select id="view_grades_specialty" required>
            <option value="">-- выберите специальность --</option>
            <?php foreach ($specialties as $sp): ?>
                <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="view_grades_subject">Выберите дисциплину</label>
        <select name="subject_id" id="view_grades_subject" required disabled>
            <option value="">-- сначала выберите специальность --</option>
        </select>

        <label for="view_grades_group">Выберите группу</label>
        <select name="group_id" id="view_grades_group" required disabled>
            <option value="">-- сначала выберите специальность --</option>
        </select>

        <button type="submit" name="view_grades">Посмотреть оценки</button>
    </form>

<?php
// Отображение оценок после GET-запроса
if (isset($_GET['view_grades'])) {
    $subject_id = $_GET['subject_id'] ?? null;
    $group_id = $_GET['group_id'] ?? null;

    if ($subject_id && $group_id) {
        // Получаем студентов группы
        $stmt = $pdo->prepare("SELECT u.id, u.username 
                               FROM users u
                               JOIN group_members gm ON u.id = gm.student_id
                               WHERE gm.group_id = ?");
        $stmt->execute([$group_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($students) {
            // Получаем оценки по предмету для этих студентов
            $student_ids = array_column($students, 'id');
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));

            $grades_stmt = $pdo->prepare(
                "SELECT student_id, grade FROM grades WHERE subject_id = ? AND student_id IN ($placeholders)"
            );
            $grades_stmt->execute(array_merge([$subject_id], $student_ids));
            $grades = $grades_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            echo '<h4>Оценки по дисциплине</h4>';
            echo '<table><tr><th>Студент</th><th>Оценка</th></tr>';
            foreach ($students as $student) {
                $grade = $grades[$student['id']] ?? 'Не выставлена';
                echo '<tr><td>' . htmlspecialchars($student['username']) . '</td><td>' . htmlspecialchars($grade) . '</td></tr>';
            }
            echo '</table>';
        } else {
            echo '<p>В выбранной группе нет студентов.</p>';
        }
    } else {
        echo '<p>Выберите дисциплину и группу для просмотра оценок.</p>';
    }
}
?>

    <hr>

    <h3>Просмотр домашних заданий</h3>
    <form method="GET" id="viewHomeworkForm">
        <label for="homework_specialty">Выберите специальность</label>
        <select id="homework_specialty" required>
            <option value="">-- выберите специальность --</option>
            <?php foreach ($specialties as $sp): ?>
                <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="homework_subject">Выберите дисциплину</label>
        <select name="homework_subject_id" id="homework_subject" required disabled>
            <option value="">-- сначала выберите специальность --</option>
        </select>

        <button type="submit" name="view_homework">Посмотреть задания</button>
    </form>

<?php
// Отображение домашних заданий
if (isset($_GET['view_homework'])) {
    $homework_subject_id = $_GET['homework_subject_id'] ?? null;

    if ($homework_subject_id) {
        $stmt = $pdo->prepare("SELECT * FROM homework WHERE subject_id = ? ORDER BY due_date DESC");
        $stmt->execute([$homework_subject_id]);
        $homeworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($homeworks) {
            echo '<h4>Домашние задания</h4>';
            echo '<table><tr><th>Описание</th><th>Срок сдачи</th></tr>';
            foreach ($homeworks as $hw) {
                echo '<tr><td>' . htmlspecialchars($hw['description']) . '</td><td>' . htmlspecialchars($hw['due_date']) . '</td></tr>';
            }
            echo '</table>';
        } else {
            echo '<p>Домашние задания отсутствуют.</p>';
        }
    } else {
        echo '<p>Выберите дисциплину для просмотра домашних заданий.</p>';
    }
}
?>

    <hr>

    <h3>Просмотр расписания пар и звонков</h3>
    <form method="GET" id="viewScheduleForm">
        <label for="schedule_group_id">Выберите группу</label>
        <select name="schedule_group_id" id="schedule_group_id" required>
            <option value="">-- выберите группу --</option>
            <?php foreach ($groups as $group): ?>
                <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['group_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="schedule_day">Выберите день недели</label>
        <select name="schedule_day" id="schedule_day" required>
            <option value="">-- выберите день --</option>
            <option value="1">Понедельник</option>
            <option value="2">Вторник</option>
            <option value="3">Среда</option>
            <option value="4">Четверг</option>
            <option value="5">Пятница</option>
            <option value="6">Суббота</option>
            <option value="7">Воскресенье</option>
        </select>

        <button type="submit" name="view_schedule">Посмотреть расписание</button>
    </form>

<?php
if (isset($_GET['view_schedule'])) {
    $schedule_group_id = $_GET['schedule_group_id'] ?? null;
    $schedule_day = $_GET['schedule_day'] ?? null;

    if ($schedule_group_id && $schedule_day) {
        $stmt = $pdo->prepare("
            SELECT s.lesson_number, s.start_time, s.end_time, sub.subject_name
            FROM schedule s
            JOIN subjects sub ON s.subject_id = sub.id
            WHERE s.group_id = ? AND s.day_of_week = ?
            ORDER BY s.lesson_number
        ");
        $stmt->execute([$schedule_group_id, $schedule_day]);
        $lessons = $stmt->fetchAll();

        $days = [1=>'Понедельник',2=>'Вторник',3=>'Среда',4=>'Четверг',5=>'Пятница',6=>'Суббота',7=>'Воскресенье'];

        // Получаем название группы
        $group_stmt = $pdo->prepare("SELECT group_name FROM groups WHERE id = ?");
        $group_stmt->execute([$schedule_group_id]);
        $group_name = $group_stmt->fetchColumn();

        echo '<h4>Расписание на ' . $days[$schedule_day] . ' для группы ' . htmlspecialchars($group_name) . '</h4>';

        if ($lessons) {
            echo '<table><tr><th>Номер пары</th><th>Дисциплина</th><th>Время начала</th><th>Время окончания</th></tr>';
            foreach ($lessons as $lesson) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($lesson['lesson_number']) . '</td>';
                echo '<td>' . htmlspecialchars($lesson['subject_name']) . '</td>';
                echo '<td>' . htmlspecialchars($lesson['start_time']) . '</td>';
                echo '<td>' . htmlspecialchars($lesson['end_time']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>Расписание на этот день отсутствует.</p>';
        }
    } else {
        echo '<p>Выберите группу и день недели для просмотра расписания.</p>';
    }
}
?>

    <br>
    <a href="index.php">Выход</a>
</div>

<script>
// Функции для AJAX-запросов и динамического заполнения селектов

function ajaxGet(url, callback) {
    fetch(url)
        .then(response => response.json())
        .then(data => callback(null, data))
        .catch(err => callback(err));
}

function fillSelect(selectElem, items, placeholder) {
    selectElem.innerHTML = '';
    const option = document.createElement('option');
    option.value = '';
    option.textContent = placeholder;
    selectElem.appendChild(option);

    items.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.name || item.subject_name || item.username || item.group_name;
        selectElem.appendChild(opt);
    });
    selectElem.disabled = false;
}

// Для выставления оценки

const specialtySelect = document.getElementById('specialty_select');
const subjectSelect = document.getElementById('subject_select');
const groupSelect = document.getElementById('group_select');
const studentSelect = document.getElementById('student_select');

specialtySelect.addEventListener('change', () => {
    const specialtyId = specialtySelect.value;

    if (!specialtyId) {
        subjectSelect.innerHTML = '<option value="">-- сначала выберите специальность --</option>';
        subjectSelect.disabled = true;
        groupSelect.innerHTML = '<option value="">-- сначала выберите специальность --</option>';
        groupSelect.disabled = true;
        studentSelect.innerHTML = '<option value="">-- сначала выберите группу --</option>';
        studentSelect.disabled = true;
        return;
    }

    // Загружаем дисциплины по специальности
    ajaxGet('get_subjects.php?specialty_id=' + specialtyId, (err, data) => {
        if (err) {
            alert('Ошибка загрузки дисциплин');
            return;
        }
        fillSelect(subjectSelect, data, '-- выберите дисциплину --');
    });

    // Загружаем группы по специальности
    ajaxGet('get_groups.php?specialty_id=' + specialtyId, (err, data) => {
        if (err) {
            alert('Ошибка загрузки групп');
            return;
        }
        fillSelect(groupSelect, data, '-- выберите группу --');
        studentSelect.innerHTML = '<option value="">-- сначала выберите группу --</option>';
        studentSelect.disabled = true;
    });
});

groupSelect.addEventListener('change', () => {
    const groupId = groupSelect.value;
    if (!groupId) {
        studentSelect.innerHTML = '<option value="">-- сначала выберите группу --</option>';
        studentSelect.disabled = true;
        return;
    }

    // Загружаем студентов группы
    ajaxGet('get_students.php?group_id=' + groupId, (err, data) => {
        if (err) {
            alert('Ошибка загрузки студентов');
            return;
        }
        fillSelect(studentSelect, data, '-- выберите студента --');
    });
});

// Аналогично для домашнего задания

const homeworkSpecialtySelect = document.getElementById('homework_specialty_select');
const homeworkSubjectSelect = document.getElementById('homework_subject_select');

homeworkSpecialtySelect.addEventListener('change', () => {
    const specialtyId = homeworkSpecialtySelect.value;
    if (!specialtyId) {
        homeworkSubjectSelect.innerHTML = '<option value="">-- сначала выберите специальность --</option>';
        homeworkSubjectSelect.disabled = true;
        return;
    }
    ajaxGet('get_subjects.php?specialty_id=' + specialtyId, (err, data) => {
        if (err) {
            alert('Ошибка загрузки дисциплин');
            return;
        }
        fillSelect(homeworkSubjectSelect, data, '-- выберите дисциплину --');
    });
});

// Для просмотра оценок

const viewGradesSpecialty = document.getElementById('view_grades_specialty');
const viewGradesSubject = document.getElementById('view_grades_subject');
const viewGradesGroup = document.getElementById('view_grades_group');

viewGradesSpecialty.addEventListener('change', () => {
    const specialtyId = viewGradesSpecialty.value;
    if (!specialtyId) {
        viewGradesSubject.innerHTML = '<option value="">-- сначала выберите специальность --</option>';
        viewGradesSubject.disabled = true;
        viewGradesGroup.innerHTML = '<option value="">-- сначала выберите специальность --</option>';
        viewGradesGroup.disabled = true;
        return;
    }
    ajaxGet('get_subjects.php?specialty_id=' + specialtyId, (err, data) => {
        if (err) {
            alert('Ошибка загрузки дисциплин');
            return;
        }
        fillSelect(viewGradesSubject, data, '-- выберите дисциплину --');
    });
    ajaxGet('get_groups.php?specialty_id=' + specialtyId, (err, data) => {
        if (err) {
            alert('Ошибка загрузки групп');
            return;
        }
        fillSelect(viewGradesGroup, data, '-- выберите группу --');
    });
});

// Для просмотра домашних заданий

const homeworkSpecialtyView = document.getElementById('homework_specialty');
const homeworkSubjectView = document.getElementById('homework_subject');

homeworkSpecialtyView.addEventListener('change', () => {
    const specialtyId = homeworkSpecialtyView.value;
    if (!specialtyId) {
        homeworkSubjectView.innerHTML = '<option value="">-- сначала выберите специальность --</option>';
        homeworkSubjectView.disabled = true;
        return;
    }
    ajaxGet('get_subjects.php?specialty_id=' + specialtyId, (err, data) => {
        if (err) {
            alert('Ошибка загрузки дисциплин');
            return;
        }
        fillSelect(homeworkSubjectView, data, '-- выберите дисциплину --');
    });
});
</script>

</body>
</html>
