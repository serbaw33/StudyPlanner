
<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: sessions.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->prepare(
    'SELECT id, subject_id, title, session_date, duration_minutes, notes
     FROM study_sessions WHERE id = :id AND user_id = :user_id'
);
$stmt->execute(['id' => $id, 'user_id' => $userId]);
$session = $stmt->fetch();

if (!$session) {
    http_response_code(404);
    exit('Sesiunea nu a fost găsită.');
}

$errors = [];

$title = $session['title'];
$sessionDate = $session['session_date'];
$duration = (string) $session['duration_minutes'];
$notes = $session['notes'] ?? '';
$subjectId = $session['subject_id'] === null ? '' : (string) $session['subject_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $sessionDate = trim($_POST['session_date'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $subjectId = trim($_POST['subject_id'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Cerere invalidă. Reîncarcă pagina.';
    }

    if ($title === '' || mb_strlen($title) > 150) {
        $errors[] = 'Introdu un titlu de maximum 150 de caractere.';
    }

    $dateObject = DateTime::createFromFormat('Y-m-d', $sessionDate);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $sessionDate) {
        $errors[] = 'Selectează o dată validă.';
    }

    if (filter_var($duration, FILTER_VALIDATE_INT) === false
        || (int) $duration < 1 || (int) $duration > 1440) {
        $errors[] = 'Durata trebuie să fie între 1 și 1440 de minute.';
    }

    if ($subjectId !== '') {
        $stmt = $pdo->prepare(
            'SELECT id FROM subjects WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute(['id' => $subjectId, 'user_id' => $userId]);

        if (!$stmt->fetch()) {
            $errors[] = 'Materia selectată nu este validă.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE study_sessions
             SET subject_id = :subject_id, title = :title,
                 session_date = :session_date,
                 duration_minutes = :duration, notes = :notes
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'subject_id' => $subjectId === '' ? null : (int) $subjectId,
            'title' => $title,
            'session_date' => $sessionDate,
            'duration' => (int) $duration,
            'notes' => $notes,
            'id' => $id,
            'user_id' => $userId
        ]);

        header('Location: sessions.php?updated=1');
        exit;
    }
}

$stmt = $pdo->prepare(
    'SELECT id, name FROM subjects WHERE user_id = :user_id ORDER BY name'
);
$stmt->execute(['user_id' => $userId]);
$subjects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editează sesiunea - StudyPlanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="container py-5">
    <h1 class="mb-4">Editează sesiunea de studiu</h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="card card-body" data-validate novalidate>
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="mb-3">
            <label for="title" class="form-label">Titlul sesiunii *</label>
            <input id="title" name="title" class="form-control" maxlength="150" required
                   value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="mb-3">
            <label for="subject_id" class="form-label">Materia</label>
            <select id="subject_id" name="subject_id" class="form-select">
                <option value="">Fără materie asociată</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?= (int) $subject['id'] ?>"
                        <?= $subjectId === (string) $subject['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="session_date" class="form-label">Data *</label>
            <input type="date" id="session_date" name="session_date"
                   class="form-control" required
                   value="<?= htmlspecialchars($sessionDate, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="mb-3">
            <label for="duration" class="form-label">Durata (minute) *</label>
            <input type="number" id="duration" name="duration" min="1" max="1440"
                   class="form-control" required
                   value="<?= htmlspecialchars($duration, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="mb-3">
            <label for="notes" class="form-label">Notițe</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"><?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Salvează modificările</button>
            <a href="sessions.php" class="btn btn-secondary">Înapoi</a>
        </div>
    </form>
</main>
<script src="../assets/js/validation.js"></script>
</body>
</html>

