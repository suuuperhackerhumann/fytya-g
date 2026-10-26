<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/icons.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $message = 'Session invalide. Rechargez la page et réessayez.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));
        $nom = trim((string)($_POST['nom'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));

        if ($username === '' || $password === '' || strlen($password) < 6) {
            $message = 'Renseignez un nom d’utilisateur et un mot de passe d’au moins 6 caractères.';
        } else {
            try {
                $db = getDB();
                $exist = $db->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
                $exist->execute([$username]);

                $hash = password_hash($password, PASSWORD_BCRYPT);

                if ($exist->fetch()) {
                    $stmt = $db->prepare('UPDATE admins SET password = ?, nom = ?, email = ? WHERE username = ?');
                    $stmt->execute([$hash, $nom ?: 'Administrateur', $email ?: $username . '@local', $username]);
                    $success = true;
                    $message = 'Compte admin mis à jour avec succès.';
                } else {
                    $stmt = $db->prepare('INSERT INTO admins (username, password, nom, email) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$username, $hash, $nom ?: 'Administrateur', $email ?: $username . '@local']);
                    $success = true;
                    $message = 'Compte admin créé avec succès.';
                }
            } catch (Exception $e) {
                $message = 'Erreur : ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin - FYTYA-G</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #ff7a18, #ffb347);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .box {
            width: 100%;
            max-width: 450px;
            background: #fff;
            border-radius: 18px;
            padding: 30px 28px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        h1 {
            margin: 0 0 10px;
            font-size: 26px;
            color: #1f2937;
        }
        p {
            margin: 0 0 25px;
            color: #4b5563;
        }
        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .alert.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 16px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
        }
        input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249,115,22,0.15);
        }
        button {
            width: 100%;
            padding: 12px 16px;
            border: none;
            border-radius: 10px;
            background: #f97316;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover {
            background: #ea580c;
        }
        .small {
            margin-top: 16px;
            font-size: 13px;
            color: #6b7280;
        }
        a {
            color: #ea580c;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>Setup Admin</h1>
        <p>Créez ou mettez à jour votre compte administrateur.</p>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $success ? 'success' : 'error' ?>" style="display:flex;align-items:center;gap:8px">
                <?=icon($success ? 'check-circle' : 'x-circle', 16)?> <span><?= sanitize($message) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>

            <label for="username">Nom d’utilisateur</label>
            <input type="text" id="username" name="username" placeholder="Ex: admin" required>

            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" placeholder="Minimum 6 caractères" required>

            <label for="nom">Nom complet</label>
            <input type="text" id="nom" name="nom" placeholder="Ex: Administrateur FYTYA">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Ex: admin@fytya-g.ci">

            <button type="submit">Créer / Modifier l’admin</button>
        </form>

        <div class="small">
            Après création, ouvrez : <a href="admin/login.php">admin/login.php</a>
        </div>
    </div>
</body>
</html>
