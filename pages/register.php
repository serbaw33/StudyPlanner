<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$name = '';
$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Numele este obligatoriu și trebuie să aibă maximum 100 de caractere.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Introdu o adresă de email validă.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Parola trebuie să aibă cel puțin 8 caractere.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Parolele nu coincid.';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'Există deja un cont cu această adresă de email.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare(
                'INSERT INTO users (name, email, password, role)
                 VALUES (?, ?, ?, ?)'
            );

            $insert->execute([
                $name,
                $email,
                $hashedPassword,
                'student'
            ]);

            header('Location: login.php?registered=1');
            exit;
        }
    }
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Înregistrare | StudyPlanner</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h1 class="h3 text-center mb-2">Creează un cont</h1>
                    <p class="text-center text-muted mb-4">
                        Alătură-te StudyPlanner
                    </p>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= escapeHtml($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" data-validate novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nume complet</label>
                            <input
                                type="text"
                                class="form-control"
                                id="name"
                                name="name"
                                maxlength="100"
                                value="<?= escapeHtml($name) ?>"
                                required
                            >
                            <div class="invalid-feedback">
                                Completează numele.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                maxlength="255"
                                value="<?= escapeHtml($email) ?>"
                                required
                            >
                            <div class="invalid-feedback">
                                Introdu o adresă de email validă.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Parolă</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >
                            <div class="invalid-feedback">
                                Parola trebuie să aibă minimum 8 caractere.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                Confirmă parola
                            </label>
                            <input
                                type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >
                            <div class="invalid-feedback">
                                Confirmă parola.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Creează contul
                        </button>
                    </form>

                    <p class="text-center mt-3 mb-0">
                        Ai deja cont?
                        <a href="login.php">Conectează-te</a>
                    </p>

                    <p class="text-center mt-2 mb-0">
                        <a href="../index.php">Înapoi la pagina principală</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/validation.js"></script>
</body>
</html>
