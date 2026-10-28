
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];
$examId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$examId || $examId < 1) {
    header('Location: exams.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function editExamEscape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$stmt = $pdo->prepare(
    'SELECT id, subject_id, title, exam_date, location, notes
     FROM exams
     WHERE id = :id AND user_id = :user_id'
);
$stmt->execute(['id' => $examId, 'user_id' => $userId]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Location: exams.php');
    exit;
}

$subjectsStmt = $pdo->prepare(
    'SELECT id, name FROM subjects
     WHERE user_id = :user_id ORDER BY name'
);
$subjectsStmt->execute(['user_id' => $userId]);
$subjects = $subjectsStmt->fetchAll();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $subjectCheck = $pdo->prepare(
                'SELECT id FROM subjects
                 WHERE id = :id AND user_id = :user_id'
            );
            $subjectCheck->execute([
                'id' => $subjectId,
                'user_id' => $userId
            ]);

            if (!$subjectCheck->fetch()) {
                $errorMessage = 'Materia selectată nu este validă.';
            } else {
                $updateStmt = $pdo->prepare(
                    'UPDATE exams
                     SET subject_id = :subject_id,
                         title = :title,
                         exam_date = :exam_date,
                         location = :location,
                         notes = :notes
                     WHERE id = :id AND user_id = :user_id'
                );

                $updateStmt->execute([
                    'subject_id' => $subjectId,
                    'title' => $title,
                    'exam_date' => $examDate,
                    'location' => $location !== '' ? $location : null,
                    'notes' => $notes !== '' ? $notes : null,
                    'id' => $examId,
                    'user_id' => $userId
                ]);

                header('Location: exams.php?updated=1');
                exit;
            }
        }
    }

    $exam['title'] = $title;
    $exam['subject_id'] = $subjectId ?: '';
    $exam['exam_date'] = $examDate;
    $exam['location'] = $location;
    $exam['notes'] = $notes;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editează examenul - StudyPlanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4">Editează examenul</h1>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger">
            <?= editExamEscape($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token"
                       value="<?= editExamEscape($_SESSION['csrf_token']) ?>">

                <div class="mb-3">
                    <label for="title" class="form-label">Titlul examenului</label>
                    <input id="title" name="title" class="form-control"
                           maxlength="150" required
                           value="<?= editExamEscape($exam['title']) ?>">
                </div>

                <div class="mb-3">
                    <label for="subject_id" class="form-label">Materia</label>
                    <select id="subject_id" name="subject_id"
                            class="form-select" required>
                        <option value="">Selectează materia</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= (int) $subject['id'] ?>"
                                <?= (string) $subject['id'] === (string) $exam['subject_id'] ? 'selected' : '' ?>>
                                <?= editExamEscape($subject['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="exam_date" class="form-label">Data examenului</label>
                    <input type="date" id="exam_date" name="exam_date"
                           class="form-control" required
                           value="<?= editExamEscape($exam['exam_date']) ?>">
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Locație (opțional)</label>
                    <input id="location" name="location" class="form-control"
                           maxlength="255"
                           value="<?= editExamEscape($exam['location']) ?>">
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notițe (opțional)</label>
                    <textarea id="notes" name="notes" class="form-control"
                              rows="3"><?= editExamEscape($exam['notes']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Salvează modificările</button>
                <a href="exams.php" class="btn btn-outline-secondary">Anulează</a>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/validation.js"></script>
</body>
</html>

