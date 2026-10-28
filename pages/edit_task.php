
<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

require_once __DIR__ . '/../config/database.php';

$taskId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$taskId || $taskId < 1) {
    header('Location: tasks.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, title, description, due_date, priority
     FROM tasks
     WHERE id = :id AND user_id = :user_id'
);

$stmt->execute([
    'id' => $taskId,
    'user_id' => $userId
]);

$task = $stmt->fetch();

if (!$task) {
    header('Location: tasks.php');
    exit;
}

$title = $task['title'];
$description = $task['description'] ?? '';
$dueDate = $task['due_date'] ?? '';
$priority = $task['priority'];


$errors = [];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = 'Cerere invalidă. Reîncarcă pagina și încearcă din nou.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $dueDate = trim($_POST['due_date'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';

        if ($title === '' || mb_strlen($title) > 150) {
            $errors[] = 'Titlul este obligatoriu și poate avea maximum 150 de caractere.';
        }

        if (!in_array($priority, ['low', 'medium', 'high'], true)) {
            $errors[] = 'Prioritatea selectată nu este validă.';
        }

        if ($dueDate !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);

            if (!$date || $date->format('Y-m-d') !== $dueDate) {
                $errors[] = 'Data limită nu este validă.';
            }
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'UPDATE tasks
                 SET title = :title,
                     description = :description,
                     due_date = :due_date,
                     priority = :priority
                 WHERE id = :id AND user_id = :user_id'
            );

            $stmt->execute([
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'due_date' => $dueDate !== '' ? $dueDate : null,
                'priority' => $priority,
                'id' => $taskId,
                'user_id' => $userId
            ]);

            header('Location: edit_task.php?id=' . $taskId . '&updated=1');
            exit;
        }
    }
}


?>


<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editează sarcina - StudyPlanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container py-5">
    <h1 class="mb-4">Editează sarcina</h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success" role="alert">
            Modificările au fost salvate cu succes!
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="mb-3">
            <label for="title" class="form-label">Titlul sarcinii</label>
            <input
                type="text"
                class="form-control"
                id="title"
                name="title"
                maxlength="150"
                required
                value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Descriere</label>
            <textarea
                class="form-control"
                id="description"
                name="description"
                rows="4"
            ><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="mb-3">
            <label for="due_date" class="form-label">Data limită</label>
            <input
                type="date"
                class="form-control"
                id="due_date"
                name="due_date"
                value="<?= htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="mb-3">
            <label for="priority" class="form-label">Prioritate</label>
            <select class="form-select" id="priority" name="priority">
                <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Scăzută</option>
                <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medie</option>
                <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>Ridicată</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Salvează modificările</button>
        <a href="tasks.php" class="btn btn-secondary">Anulează</a>
    </form>
</div>
</body>
</html>
