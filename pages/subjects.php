<?php

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

require_once __DIR__ . '/../config/database.php';



if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escapeHtml($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function redirectToSubjects(): void
{
    header('Location: subjects.php');
    exit;
}

$error = '';
$editSubject = null;

$allowedDifficulties = ['easy', 'medium', 'hard'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Cerere invalidă. Reîncarcă pagina și încearcă din nou.');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete') {
            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new RuntimeException('ID invalid.');
            }

            $stmt = $pdo->prepare(
                'DELETE FROM subjects
                 WHERE id = :id AND user_id = :user_id'
            );

            $stmt->execute([
                'id' => $id,
                'user_id' => $userId
            ]);

            redirectToSubjects();
        }

        if ($action === 'save') {
            $id = filter_var(
                $_POST['id'] ?? '',
                FILTER_VALIDATE_INT
            );

            $name = trim($_POST['name'] ?? '');
            $teacher = trim($_POST['teacher'] ?? '');
            $color = trim($_POST['color'] ?? '#6366f1');
            $difficulty = $_POST['difficulty'] ?? 'medium';

            if ($id === false || $id === null || $id < 0) {
                throw new RuntimeException('ID invalid.');
            }

            if ($name === '' || mb_strlen($name) > 100) {
                throw new RuntimeException(
                    'Numele materiei este obligatoriu și trebuie să aibă maximum 100 de caractere.'
                );
            }

            if (mb_strlen($teacher) > 100) {
                throw new RuntimeException(
                    'Numele profesorului poate avea maximum 100 de caractere.'
                );
            }

            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                throw new RuntimeException('Culoarea selectată este invalidă.');
            }

            if (!in_array($difficulty, $allowedDifficulties, true)) {
                throw new RuntimeException('Dificultate invalidă.');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE subjects
                     SET name = :name,
                         teacher = :teacher,
                         color = :color,
                         difficulty = :difficulty
                     WHERE id = :id AND user_id = :user_id'
                );

                $stmt->execute([
                    'name' => $name,
                    'teacher' => $teacher !== '' ? $teacher : null,
                    'color' => $color,
                    'difficulty' => $difficulty,
                    'id' => $id,
                    'user_id' => $userId
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO subjects
                     (user_id, name, teacher, color, difficulty)
                     VALUES
                     (:user_id, :name, :teacher, :color, :difficulty)'
                );

                $stmt->execute([
                    'user_id' => $userId,
                    'name' => $name,
                    'teacher' => $teacher !== '' ? $teacher : null,
                    'color' => $color,
                    'difficulty' => $difficulty
                ]);
            }

            redirectToSubjects();
        }
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'Operația nu a putut fi finalizată. Verifică datele și încearcă din nou.';
    }
}

if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($editId && $editId > 0) {
        $stmt = $pdo->prepare(
            'SELECT id, name, teacher, color, difficulty
             FROM subjects
             WHERE id = :id AND user_id = :user_id'
        );

        $stmt->execute([
            'id' => $editId,
            'user_id' => $userId
        ]);

        $editSubject = $stmt->fetch() ?: null;
    }
}

$stmt = $pdo->prepare(
    'SELECT id, name, teacher, color, difficulty, created_at
     FROM subjects
     WHERE user_id = :user_id
     ORDER BY name ASC'
);

$stmt->execute(['user_id' => $userId]);
$subjects = $stmt->fetchAll();

$formName = $editSubject['name'] ?? '';
$formTeacher = $editSubject['teacher'] ?? '';
$formColor = $editSubject['color'] ?? '#6366f1';
$formDifficulty = $editSubject['difficulty'] ?? 'medium';
$formId = $editSubject['id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materii | StudyPlanner</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">StudyPlanner</a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainMenu"
                aria-controls="mainMenu" aria-expanded="false"
                aria-label="Deschide meniul">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">Acasă</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="subjects.php">Materii</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="tasks.php">Sarcini</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sessions.php">Planificator</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="exams.php">Examene</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-5">
    <div class="mb-4">
        <h1 class="fw-bold">Materiile mele</h1>
        <p class="text-secondary">
            Organizează materiile și urmărește nivelul de dificultate.
        </p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <?= escapeHtml($error) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h2 class="h4 mb-3">
                        <?= $editSubject ? 'Editează materia' : 'Adaugă o materie' ?>
                    </h2>

                    <form method="post" action="subjects.php">
                        <input type="hidden" name="csrf_token"
                               value="<?= escapeHtml($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id"
                               value="<?= (int) $formId ?>">

                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Numele materiei*
                         </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   class="form-control"
                                   maxlength="100"
                                   required
                                   value="<?= escapeHtml($formName) ?>"
                            placeholder="Ex.: Programare web">
                            <div class="form-text">Numele materiei este obligatoriu.</div>
                        </div>

                        <div class="mb-3">
                            <label for="teacher" class="form-label">
                                Profesor
                            </label>
                            <input type="text" id="teacher" name="teacher"
                                   class="form-control" maxlength="100"
                                   value="<?= escapeHtml($formTeacher) ?>"
                                   placeholder="Numele profesorului">
                        </div>

                        <div class="mb-3">
                            <label for="difficulty" class="form-label">
                                Dificultate
                            </label>
                            <select id="difficulty" name="difficulty"
                                    class="form-select">
                                <option value="easy"
                                    <?= $formDifficulty === 'easy' ? 'selected' : '' ?>>
                                    Ușoară
                                </option>
                                <option value="medium"
                                    <?= $formDifficulty === 'medium' ? 'selected' : '' ?>>
                                    Medie
                                </option>
                                <option value="hard"
                                    <?= $formDifficulty === 'hard' ? 'selected' : '' ?>>
                                    Dificilă
                                </option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="color" class="form-label">
                                Culoarea materiei
                            </label>
                            <input type="color" id="color" name="color"
                                   class="form-control form-control-color"
                                   value="<?= escapeHtml($formColor) ?>">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <?= $editSubject ? 'Salvează modificările' : 'Adaugă materia' ?>
                        </button>

                        <?php if ($editSubject): ?>
                            <a href="subjects.php"
                               class="btn btn-outline-secondary w-100 mt-2">
                                Anulează editarea
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between
                                align-items-center mb-3">
                        <h2 class="h4 mb-0">Lista materiilor</h2>
                        <span class="badge text-bg-primary">
                            <?= count($subjects) ?> materii
                        </span>
                    </div>

                    <?php if (!$subjects): ?>
                        <div class="text-center py-5">
                            <h3 class="h5">Nu ai adăugat materii încă.</h3>
                            <p class="text-secondary mb-0">
                                Folosește formularul pentru a adăuga prima materie.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                <tr>
                                    <th>Materie</th>
                                    <th>Dificultate</th>
                                    <th class="text-end">Acțiuni</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td>
                                            <span
                                                class="d-inline-block rounded-circle me-2"
                                                style="width: 12px; height: 12px;
                                                background-color: <?= escapeHtml($subject['color']) ?>;">
                                            </span>
                                            <strong>
                                                <?= escapeHtml($subject['name']) ?>
                                            </strong>
                                            <?php if ($subject['teacher']): ?>
                                                <div class="small text-secondary">
                                                    <?= escapeHtml($subject['teacher']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $difficultyLabels = [
                                                'easy' => 'Ușoară',
                                                'medium' => 'Medie',
                                                'hard' => 'Dificilă'
                                            ];
                                            ?>
                                            <?= escapeHtml(
                                                $difficultyLabels[$subject['difficulty']]
                                                ?? 'Medie'
                                            ) ?>
                                        </td>
                                        <td class="text-end">
                                            <a
                                                href="subjects.php?edit=<?= (int) $subject['id'] ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                Editează
                                            </a>

                                            <form method="post" action="subjects.php" class="d-inline"
                                                  onsubmit="return confirm('Sigur dorești să ștergi această materie?');">
                                                <input type="hidden"
                                                       name="csrf_token"
                                                       value="<?= escapeHtml($_SESSION['csrf_token']) ?>">
                                                <input type="hidden"
                                                       name="action"
                                                       value="delete">
                                                <input type="hidden"
                                                       name="id"
                                                       value="<?= (int) $subject['id'] ?>">

                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger">
                                                    Șterge
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/validation.js"></script>
</body>
</html>
