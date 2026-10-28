<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Introdu o adresă de email validă.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: ../index.php');
            exit;
        }

        $error = 'Emailul sau parola sunt incorecte.';
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
    <title>Conectare | StudyPlanner</title>
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
                    <h1 class="h3 text-center mb-2">Bine ai revenit!</h1>
                    <p class="text-center text-muted mb-4">
                        Conectează-te la StudyPlanner
                    </p>

                    <?php if (isset($_GET['registered'])): ?>
                        <div class="alert alert-success">
                            Contul a fost creat. Te poți conecta acum.
                        </div>
                    <?php endif; ?>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= escapeHtml($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" data-validate novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                value="<?= escapeHtml($email) ?>"
                                autocomplete="email"
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
                                autocomplete="current-password"
                                required
                            >
                            <div class="invalid-feedback">
                                Introdu parola.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Conectare
                        </button>
                    </form>

                    <p class="text-center mt-3 mb-0">
                        Nu ai cont?
                        <a href="register.php">Creează unul</a>
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
