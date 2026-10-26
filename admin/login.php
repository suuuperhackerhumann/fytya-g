<?php
session_start();
require_once '../includes/config.php';
require_once 'icons.php';
applySecurityHeaders();

if (isset($_SESSION['admin_logged'])) {
    header('Location: index.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $error = 'Session invalide. Rechargez la page et réessayez.';
    } else {
        $user = trim($_POST['username'] ?? '');
        $pass = trim($_POST['password'] ?? '');

        if (!canAttemptLogin($user)) {
            $error = 'Trop de tentatives. Réessayez dans 5 minutes.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM admins WHERE username=? LIMIT 1");
            $stmt->execute([$user]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($pass, $admin['password'])) {
                clearLoginAttempts($user);
                session_regenerate_id(true);
                $_SESSION['admin_logged'] = true;
                $_SESSION['admin_nom']    = $admin['nom'];
                $_SESSION['admin_user']   = $admin['username'];
                header('Location: index.php'); exit;
            } else {
                registerFailedLogin($user);
                $error = 'Identifiants incorrects. Réessayez.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connexion Admin — <?=SITE_NAME?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="premium-admin.css?v=<?= @filemtime(__DIR__.'/premium-admin.css') ?: 1 ?>">
<link rel="icon" type="image/png" href="../logo.png">

</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo">
      <img src="../logo.png" alt="FYTYA-G">
      <h2 style="font-family:var(--fh);font-weight:800;font-size:22px;margin-bottom:4px">Panel Admin</h2>
      <p>Connectez-vous pour gérer votre boutique</p>
    </div>

    <?php if($error): ?>
    <div class="alert alert-error"><?=icon('x-circle', 16)?> <?=sanitize($error)?></div>
    <?php endif; ?>

    <form method="POST">
      <?=csrfField()?>
      <div class="fg">
        <label>Nom d'utilisateur</label>
        <input type="text" name="username" placeholder="admin" required autofocus>
      </div>
      <div class="fg">
        <label>Mot de passe</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" style="width:100%;background:var(--orange);color:#fff;font-family:var(--fh);font-weight:700;font-size:15px;padding:13px;border-radius:10px;border:none;cursor:pointer;margin-top:8px;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:8px">
        <?=icon('lock', 16)?> Se connecter
      </button>
    </form>

    <div style="margin-top:20px;text-align:center;font-size:12px;color:var(--g400)">
      Accès réservé aux administrateurs FYTYA-G
    </div>
  </div>
</div>
</body>
</html>