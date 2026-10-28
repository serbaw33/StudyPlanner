
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_exam_id'])) {
        $token = $_POST['csrf_token'] ?? '';

        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            exit('Cerere invalidă. Reîncarcă pagina.');
        }

        $deleteId = filter_var(
            $_POST['delete_exam_id'],
            FILTER_VALIDATE_INT
        );

        if ($deleteId) {
            $stmt = $pdo->prepare(
                'DELETE FROM exams WHERE id = :id AND user_id = :user_id'
            );
            $stmt->execute([
                'id' => $deleteId,
                'user_id' => $userId
            ]);
        }

        header('Location: exams.php?deleted=1');
        exit;
    }

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Cerere invalidă. Reîncarcă pagina.');
    }

    $title = trim($_POST['title'] ?? '');
    $subjectId = filter_var(
        $_POST['subject_id'] ?? '',
        FILTER_VALIDATE_INT
    );
    $examDate = $_POST['exam_date'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($title === '' || strlen($title) > 150) {
        $errorMessage = 'Introdu un titlu de maximum 150 de caractere.';
    } elseif (!$subjectId) {
        $errorMessage = 'Selectează o materie.';
    } elseif ($examDate === '') {
        $errorMessage = 'Selectează data examenului.';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $examDate);

        if (!$date || $date->format('Y-m-d') !== $examDate) {
            $errorMessage = 'Data examenului nu este validă.';
        } else {
            $subjectStmt = $pdo->prepare(
                'SELECT id FROM subjects
                 WHERE id = :id AND user_id = :user_id'
            );
            $subjectStmt->execute([
                'id' => $subjectId,
                'user_id' => $userId
            ]);

            if (!$subjectStmt->fetch()) {
                $errorMessage = 'Materia selectată nu este validă.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO exams
                     (user_id, subject_id, title, exam_date, location, notes)
                     VALUES
                     (:user_id, :subject_id, :title, :exam_date, :location, :notes)'
                );

                $stmt->execute([
                    'user_id' => $userId,
                    'subject_id' => $subjectId,
                    'title' => $title,
                    'exam_date' => $examDate,
                    'location' => $location !== '' ? $location : null,
                    'notes' => $notes !== '' ? $notes : null
                ]);

                header('Location: exams.php?added=1');
                exit;
            }
        }
    }
}

if (isset($_GET['added'])) {
    $successMessage = 'Examenul a fost adăugat!';
} elseif (isset($_GET['updated'])) {
    $successMessage = 'Examenul a fost modificat!';
} elseif (isset($_GET['deleted'])) {
    $successMessage = 'Examenul a fost șters!';
}

$subjectsStmt = $pdo->prepare(
    'SELECT id, name FROM subjects
     WHERE user_id = :user_id ORDER BY name'
);
$subjectsStmt->execute(['user_id' => $userId]);
$subjects = $subjectsStmt->fetchAll();

$examsStmt = $pdo->prepare(
    'SELECT e.id, e.title, e.exam_date, e.location, e.notes,
            s.name AS subject_name
     FROM exams e
     INNER JOIN subjects s ON s.id = e.subject_id
     WHERE e.user_id = :user_id
     ORDER BY e.exam_date ASC'
);
$examsStmt->execute(['user_id' => $userId]);
$exams = $examsStmt->fetchAll();

function examEscape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Examene - StudyPlanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1>Examene</h1>
        <a href="../index.php" class="btn btn-outline-secondary">Înapoi la panou</a>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="alert alert-success">
            <?= examEscape($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger">
            <?= examEscape($errorMessage) ?>
        </div>
    <?php endif; ?>

    <section class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h4 mb-3">Adaugă un examen</h2>

            <?php if (empty($subjects)): ?>
                <p>Adaugă mai întâi o materie în secțiunea Materii.</p>
                <a href="subjects.php" class="btn btn-primary">Mergi la materii</a>
            <?php else: ?>
                <form method="POST" data-validate>
                    <input type="hidden" name="csrf_token"
                           value="<?= examEscape($_SESSION['csrf_token']) ?>">

                    <div class="mb-3">
                        <label for="title" class="form-label">Titlul examenului</label>
                        <input id="title" name="title" class="form-control"
                               maxlength="150" required
                               value="<?= examEscape($_POST['title'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="subject_id" class="form-label">Materia</label>
                        <select id="subject_id" name="subject_id"
                                class="form-select" required>
                            <option value="">Selectează materia</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= (int) $subject['id'] ?>"
                                    <?= (string) ($subject['id']) === (string) ($_POST['subject_id'] ?? '') ? 'selected' : '' ?>>
                                    <?= examEscape($subject['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="exam_date" class="form-label">Data examenului</label>
                        <input type="date" id="exam_date" name="exam_date"
                               class="form-control" required
                               value="<?= examEscape($_POST['exam_date'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Locație (opțional)</label>
                        <input id="location" name="location" class="form-control"
                               maxlength="255"
                               value="<?= examEscape($_POST['location'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notițe (opțional)</label>
                        <textarea id="notes" name="notes" class="form-control"
                                  rows="3"><?= examEscape($_POST['notes'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Adaugă examenul</button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <section class="card shadow-sm">
        <div class="card-body">
            <h2 class="h4 mb-3">Lista examenelor</h2>

            <?php if (empty($exams)): ?>
                <p>Nu ai examene înregistrate încă.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                        <tr>
                            <th>Examen</th>
                            <th>Materie</th>
                            <th>Data</th>
                            <th>Locație</th>
                            <th>Notițe</th>
                            <th>Acțiuni</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($exams as $exam): ?>
                            <tr>
                                <td><?= examEscape($exam['title']) ?></td>
                                <td><?= examEscape($exam['subject_name']) ?></td>
                                <td><?= examEscape($exam['exam_date']) ?></td>
                                <td><?= examEscape($exam['location']) ?></td>
                                <td><?= nl2br(examEscape($exam['notes'])) ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a class="btn btn-sm btn-primary"
                                           href="edit_exam.php?id=<?= (int) $exam['id'] ?>">
                                            Editează
                                        </a>
                                        <form method="POST"
                                              onsubmit="return confirm('Sigur vrei să ștergi acest examen?');">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= examEscape($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="delete_exam_id"
                                                   value="<?= (int) $exam['id'] ?>">
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
        </div>
    </section>
</div>

<script src="../assets/js/validation.js"></script>
</body>
</html>

