
<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];
$pageTitle = 'Planificator';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$successMessage = '';

$title = '';
$sessionDate = '';
$duration = '30';
$notes = '';
$subjectId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $sessionDate = trim($_POST['session_date'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $subjectId = trim($_POST['subject_id'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = 'Cererea nu este validă. Reîncarcă pagina.';
    }

    if ($title === '' || mb_strlen($title) > 150) {
        $errors[] = 'Introdu un titlu de maximum 150 de caractere.';
    }

    $dateObject = DateTime::createFromFormat('Y-m-d', $sessionDate);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $sessionDate) {
        $errors[] = 'Selectează o dată validă.';
    }

    if (filter_var($duration, FILTER_VALIDATE_INT) === false
        || (int) $duration < 1
        || (int) $duration > 1440) {
        $errors[] = 'Durata trebuie să fie între 1 și 1440 de minute.';
    }

    if ($subjectId !== '') {
        $stmt = $pdo->prepare(
            'SELECT id FROM subjects WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'id' => $subjectId,
            'user_id' => $userId
        ]);

        if (!$stmt->fetch()) {
            $errors[] = 'Materia selectată nu este validă.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO study_sessions
                (user_id, subject_id, title, session_date, duration_minutes, notes)
             VALUES
                (:user_id, :subject_id, :title, :session_date, :duration, :notes)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'subject_id' => $subjectId === '' ? null : (int) $subjectId,
            'title' => $title,
            'session_date' => $sessionDate,
            'duration' => (int) $duration,
            'notes' => $notes
        ]);

        header('Location: sessions.php?added=1');
        exit;
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_session_id'])
) {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Cerere invalidă. Reîncarcă pagina.');
    }

    $deleteId = filter_var(
        $_POST['delete_session_id'],
        FILTER_VALIDATE_INT
    );

    if ($deleteId) {
        $stmt = $pdo->prepare(
            'DELETE FROM study_sessions
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'id' => $deleteId,
            'user_id' => $userId
        ]);
    }

    header('Location: sessions.php?deleted=1');
    exit;
}

if (isset($_GET['added'])) {
    $successMessage = 'Sesiunea de studiu a fost adăugată!';
} elseif (isset($_GET['updated'])) {
    $successMessage = 'Sesiunea a fost modificată!';
} elseif (isset($_GET['deleted'])) {
    $successMessage = 'Sesiunea a fost ștearsă!';
}

$stmt = $pdo->prepare(
    'SELECT id, name FROM subjects WHERE user_id = :user_id ORDER BY name'
);
$stmt->execute(['user_id' => $userId]);
$subjects = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT ss.id, ss.title, ss.session_date, ss.duration_minutes,
            ss.notes, s.name AS subject_name
     FROM study_sessions ss
     LEFT JOIN subjects s ON ss.subject_id = s.id
     WHERE ss.user_id = :user_id
     ORDER BY ss.session_date DESC, ss.id DESC'
);
$stmt->execute(['user_id' => $userId]);
$sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">StudyPlanner</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="../index.php">Acasă</a>
            <a class="nav-link" href="subjects.php">Materii</a>
            <a class="nav-link" href="tasks.php">Sarcini</a>
            <a class="nav-link active" href="sessions.php">Planificator</a>
            <a class="nav-link" href="exams.php">Examene</a>
            <a class="nav-link text-warning" href="logout.php">Deconectare</a>
        </div>
    </div>
</nav>

<main class="container py-5">
    <h1 class="mb-4">Planificator de studiu</h1>

    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="card mb-5">
        <div class="card-body">
            <h2 class="h4 mb-3">Adaugă o sesiune de studiu</h2>

            <form method="POST" data-validate novalidate>
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="mb-3">
                    <label for="title" class="form-label">Titlul sesiunii *</label>
                    <input type="text" class="form-control" id="title" name="title"
                           maxlength="150" required
                           value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label for="subject_id" class="form-label">Materia</label>
                    <select class="form-select" id="subject_id" name="subject_id">
                        <option value="">Fără materie asociată</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= (int) $subject['id'] ?>"
                                <?= (string) $subjectId === (string) $subject['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="session_date" class="form-label">Data *</label>
                        <input type="date" class="form-control" id="session_date"
                               name="session_date" required
                               value="<?= htmlspecialchars($sessionDate, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="duration" class="form-label">Durata (minute) *</label>
                        <input type="number" class="form-control" id="duration"
                               name="duration" min="1" max="1440" required
                               value="<?= htmlspecialchars($duration, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notițe</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Adaugă sesiunea</button>
            </form>
        </div>
    </section>

    <h2 class="h4 mb-3">Sesiunile mele</h2>

    <?php if (!$sessions): ?>
        <div class="alert alert-info">Nu ai încă sesiuni de studiu.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-light">
                <tr>
                    <th>Titlu</th>
                    <th>Materie</th>
                    <th>Data</th>
                    <th>Durata</th>
                    <th>Notițe</th>
                    <th>Acțiuni</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td><?= htmlspecialchars($session['title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($session['subject_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($session['session_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $session['duration_minutes'] ?> min</td>
                        <td><?= nl2br(htmlspecialchars($session['notes'] ?? '', ENT_QUOTES, 'UTF-8')) ?></td>

                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-primary"
                                   href="edit_session.php?id=<?= (int) $session['id'] ?>">
                                    Editează
                                </a>

                                <form method="POST"
                                      onsubmit="return confirm('Sigur vrei să ștergi această sesiune?');">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="delete_session_id"
                                           value="<?= (int) $session['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Șterge
                                    </button>
                                </form>
                            </div>
                        </td>

                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<footer class="container py-4 text-center text-secondary">
    <small>&copy; <?= date('Y') ?> StudyPlanner</small>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/validation.js"></script>
</body>
</html>

