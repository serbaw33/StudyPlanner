<?php


session_start();

// Verificăm dacă utilizatorul este autentificat
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Identificăm utilizatorul conectat
$userId = (int)$_SESSION['user_id'];

// Conectăm pagina la baza de date
require_once __DIR__ . '/../config/database.php';


$pageTitle = 'Sarcini';
$search = trim($_GET['search'] ?? '');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$successMessage = '';

if (isset($_GET['added'])) {
    $successMessage = 'Sarcina a fost adăugată cu succes!';
}

if (isset($_GET['deleted'])) {
    $successMessage = 'Sarcina a fost ștearsă cu succes!';
}

$title = '';
$description = '';
$dueDate = '';
$priority = 'medium';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors[] = 'Cerere invalidă. Reîncarcă pagina și încearcă din nou.';
    } else {

        if (isset($_POST['delete_task_id'])) {
            $deleteTaskId = filter_var(
                $_POST['delete_task_id'],
                FILTER_VALIDATE_INT
            );

            if (!$deleteTaskId || $deleteTaskId < 1) {
                $errors[] = 'Sarcina selectată nu este validă.';
            } else {
                $stmt = $pdo->prepare(
                    'DELETE FROM tasks
             WHERE id = :id AND user_id = :user_id'
                );

                $stmt->execute([
                    'id' => $deleteTaskId,
                    'user_id' => $userId
                ]);

                if ($stmt->rowCount() > 0) {
                    header('Location: tasks.php?deleted=1');
                    exit;
                }

                $errors[] = 'Sarcina nu a fost găsită.';
            }
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
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO tasks
                (user_id, title, description, due_date, priority, status, type)
             VALUES
                (:user_id, :title, :description, :due_date, :priority, :status, :type)'
                    );

                    $stmt->execute([
                        'user_id' => $userId,
                        'title' => $title,
                        'description' => $description !== '' ? $description : null,
                        'due_date' => $dueDate !== '' ? $dueDate : null,
                        'priority' => $priority,
                        'status' => 'pending',
                        'type' => 'other'
                    ]);

                    $successMessage = 'Sarcina a fost adăugată cu succes!';

                    $title = '';
                    $description = '';
                    $dueDate = '';
                    $priority = 'medium';
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    $errors[] = 'Sarcina nu a putut fi salvată. Încearcă din nou.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> - StudyPlanner</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>


<div class="container py-5">

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Sarcinile mele</h1>

        <a href="../index.php" class="btn btn-secondary">
            Înapoi la pagina principală
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h2 class="h5 mb-0">Adaugă o sarcină nouă</h2>
        </div>

        <div class="card-body">

            <form method="GET" action="tasks.php" class="mb-4">
                <label for="search" class="form-label">Caută o sarcină după titlu</label>
                <div class="input-group">
                    <input
                        type="search"
                        id="search"
                        name="search"
                        class="form-control"
                        placeholder="Introdu titlul sarcinii..."
                        value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <button type="submit" class="btn btn-primary">Caută</button>
                    <a href="tasks.php" class="btn btn-secondary">Resetează</a>
                </div>
            </form>
            <form method="POST" action="" data-validate novalidate>
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                >
                <div class="mb-3">
                    <label for="title" class="form-label">
                        Titlul sarcinii *
                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="form-control"
                        maxlength="150"
                        placeholder="Ex.: Realizarea proiectului la PHP"
                        value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">
                        Descriere
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        rows="3"
                        placeholder="Detalii despre ce ai de făcut..."
                    ><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="due_date" class="form-label">
                        Data limită
                    </label>

                    <input
                        type="date"
                        id="due_date"
                        name="due_date"
                        class="form-control"
                        value="<?= htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="mb-3">
                    <label for="priority" class="form-label">
                        Prioritate
                    </label>

                    <select
                        id="priority"
                        name="priority"
                        class="form-select"
                    >

                        <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Scăzută</option>
                        <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medie</option>
                        <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>Ridicată</option>

                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Adaugă sarcina
                </button>
            </form>
        </div>
    </div>
</div>


<?php
$stmt = $pdo->prepare(
    'SELECT id, title, description, due_date, priority, status
     FROM tasks
     WHERE user_id = :user_id
     ORDER BY due_date IS NULL, due_date ASC, id DESC'
);


$stmt = $pdo->prepare(
    'SELECT id, title, description, due_date, priority, status
     FROM tasks
     WHERE user_id = :user_id
       AND title LIKE :search
     ORDER BY due_date IS NULL, due_date ASC, id DESC'
);

$stmt->execute([
    'user_id' => $userId,
    'search' => '%' . $search . '%'
]);

$tasks = $stmt->fetchAll();


?>

<div class="container pb-5">
    <h2 class="mb-3">Sarcinile mele salvate</h2>

    <?php if (empty($tasks)): ?>
        <div class="alert alert-info">
            Nu ai nicio sarcină momentan.
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($tasks as $task): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5">
                                <?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?>
                            </h3>

                            <?php if (!empty($task['description'])): ?>
                                <p>
                                    <?= nl2br(htmlspecialchars($task['description'], ENT_QUOTES, 'UTF-8')) ?>
                                </p>
                            <?php endif; ?>

                            <p class="mb-1">
                                <strong>Prioritate:</strong>
                                <?php
                                $priorities = [
                                    'low' => 'Scăzută',
                                    'medium' => 'Medie',
                                    'high' => 'Ridicată'
                                ];
                                echo $priorities[$task['priority']] ?? 'Medie';
                                ?>
                            </p>

                            <p class="mb-1">
                                <strong>Data limită:</strong>
                                <?= $task['due_date']
                                    ? htmlspecialchars($task['due_date'], ENT_QUOTES, 'UTF-8')
                                    : 'Nespecificată' ?>
                            </p>

                            <p class="mb-0">
                                <strong>Status:</strong>
                                <?php
                                $statuses = [
                                    'pending' => 'În așteptare',
                                    'in_progress' => 'În desfășurare',
                                    'completed' => 'Finalizată'
                                ];
                                echo $statuses[$task['status']] ?? $task['status'];
                                ?>
                            </p>

                            <a href="edit_task.php?id=<?= (int) $task['id'] ?>"
                               class="btn btn-warning btn-sm">
                                Editează
                            </a>

                            <form method="POST" action="" class="mt-3"
                                  onsubmit="return confirm('Sigur dorești să ștergi această sarcină?');">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                                <input type="hidden" name="delete_task_id"
                                       value="<?= (int) $task['id'] ?>">

                                <button type="submit" class="btn btn-danger btn-sm">
                                    Șterge sarcina
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
