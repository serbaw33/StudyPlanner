
<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: pages/login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$userId = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM subjects WHERE user_id = :user_id'
);
$stmt->execute(['user_id' => $userId]);
$subjectsCount = (int) $stmt->fetchColumn();
$stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM tasks
     WHERE user_id = :user_id
     AND status != 'completed'"
);
$stmt->execute(['user_id' => $userId]);
$activeTasksCount = (int) $stmt->fetchColumn();
$stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM study_sessions WHERE user_id = :user_id'
);
$stmt->execute(['user_id' => $userId]);
$sessionsCount = (int) $stmt->fetchColumn();
$stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM exams
     WHERE user_id = :user_id
     AND exam_date >= CURDATE()'
);
$stmt->execute(['user_id' => $userId]);
$examsCount = (int) $stmt->fetchColumn();
$pageTitle = 'StudyPlanner';
?>

<?php

if (empty($_SESSION['user_id'])) {
    header('Location: pages/login.php');
    exit;
}

$pageTitle = 'StudyPlanner';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            StudyPlanner
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainMenu"
            aria-controls="mainMenu"
            aria-expanded="false"
            aria-label="Deschide meniul"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                   <span class="nav-link">
        Salut, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Student', ENT_QUOTES, 'UTF-8') ?>!
    </span>
                    <a class="nav-link active" href="index.php">Acasă</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pages/subjects.php">Materii</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pages/tasks.php">Sarcini</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pages/sessions.php">Planificator</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pages/exams.php">Examene</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-warning" href="pages/logout.php">
                        Deconectare
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-5">
    <section class="hero p-4 p-md-5 rounded-4 mb-4">
        <span class="badge text-bg-primary mb-3">Organizează. Învață. Reușește.</span>

        <h1 class="display-5 fw-bold">Învață mai organizat cu StudyPlanner.</h1>

        <p class="lead mt-3">
            Planifică sesiunile de studiu, urmărește-ți sarcinile
            și pregătește-te pentru examene fără stres inutil.
        </p>

        <a href="pages/subjects.php" class="btn btn-primary btn-lg mt-3">
            Organizează-ți materiile
        </a>
    </section>

    <h2 class="h3 mb-3">Spațiul tău de studiu</h2>

    <div class="row g-4">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-secondary">Materii</div>
                    <div class="display-6 fw-bold"><?= $subjectsCount ?></div>
                    <p class="mb-0 small">Materiile tale vor apărea aici.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-secondary">Sarcini active</div>
                    <div class="display-6 fw-bold"><?= $activeTasksCount ?></div>
                    <p class="mb-0 small">Urmărește ce ai de făcut.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-secondary">Sesiuni de studiu</div>
                    <div class="display-6 fw-bold"><?= $sessionsCount ?></div>
                    <p class="mb-0 small">Planifică timpul de învățare.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-secondary">Examene viitoare</div>
                    <div class="display-6 fw-bold"><?= $examsCount ?></div>
                    <p class="mb-0 small">Pregătește-te din timp.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="container py-4 text-center text-secondary">
    <small>&copy; <?= date('Y') ?> StudyPlanner — Planifică-ți succesul.</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
