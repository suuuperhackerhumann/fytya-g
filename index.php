<?php
require_once 'includes/config.php';
require_once 'includes/icons.php';
initUserSession();
applySecurityHeaders();

$msg = '';
$msgIcon = 'alert-triangle';

$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? 'accueil.php';
$redirect = trim((string)$redirect);
if ($redirect === '' || preg_match('/^(?:https?:)?\/\//i', $redirect) || !preg_match('/^[A-Za-z0-9_\-\.\/\\?=&%]+$/', $redirect)) {
    $redirect = 'accueil.php';
}

$explicitLogin = (isset($_GET['login']) && $_GET['login'] === '1') || (isset($_GET['msg']) && $_GET['msg'] === 'login_required');
$goToLoginPage = $explicitLogin || isset($_GET['redirect']);

if (!isset($_SESSION['user']) && !$goToLoginPage && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: accueil.php');
    exit;
}

// Déjà connecté → page de présentation (ou destination demandée)
if (isset($_SESSION['user'])) {
    header('Location: ' . $redirect);
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] === 'login_required') {
    $msg = "Connectez-vous pour accéder à cette page.";
    $msgIcon = 'lock';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $msg = 'Session invalide. Rechargez la page et réessayez.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!canAttemptLogin($email)) {
            $msg = 'Trop de tentatives. Réessayez dans 5 minutes.';
            $msgIcon = 'clock';
        } elseif ($email && $password) {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT * FROM users WHERE email=?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    clearLoginAttempts($email);
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id'     => $user['id'],
                        'nom'    => $user['nom'],
                        'prenom' => $user['prenom'],
                        'email'  => $user['email'],
                    ];
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    registerFailedLogin($email);
                    $msg = 'Email ou mot de passe incorrect.';
                }
            } catch (Exception $e) {
                $msg = 'Erreur lors de la connexion.';
            }
        } else {
            $msg = 'Veuillez remplir tous les champs.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — <?=SITE_NAME?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="premium.css">
<link rel="icon" type="image/png" href="logo.png">
<style>
  body { margin:0; min-height:100vh; min-height:100dvh; display:grid; grid-template-columns:minmax(0,1fr); background:var(--stone); padding-bottom:0; }
  .login-side { display:none; }
  .login-main { display:flex; align-items:center; justify-content:center; padding:clamp(20px,5vw,48px); }
  .login-box { width:100%; max-width:400px; animation:rise .6s var(--ease) both; }
  .login-brand { display:flex; align-items:center; gap:12px; margin-bottom:36px; }
  .login-brand img { height:44px; width:auto; }
  .login-brand span { font-family:var(--fh); font-weight:700; font-size:18px; letter-spacing:-.01em; }
  .login-title { font-family:var(--fh); font-weight:700; font-size:clamp(26px,5vw,32px); letter-spacing:-.03em; line-height:1.1; margin-bottom:8px; }
  .login-sub { color:var(--muted); font-size:15px; margin-bottom:28px; }
  .li-alert { display:flex; align-items:flex-start; gap:10px; background:var(--accent-l); border:1px solid #F8D6BC; color:var(--ink); border-radius:var(--r); padding:12px 14px; font-size:14px; margin-bottom:20px; }
  .li-alert i { color:var(--accent-d); margin-top:3px; }
  .li-field { margin-bottom:18px; }
  .li-field label { display:block; margin-bottom:6px; font-size:13.5px; }
  .li-input { position:relative; }
  .li-input input { width:100%; padding:0 14px; font-size:16px; }
  .li-input input[type=password], .li-input input.has-toggle { padding-right:52px; }
  .li-toggle { position:absolute; right:4px; top:50%; transform:translateY(-50%); width:44px; height:44px; border:0; background:none; color:var(--muted); cursor:pointer; border-radius:var(--r); transition:color var(--tr); }
  .li-toggle:hover { color:var(--ink); }
  .btn-welcome-primary { width:100%; min-height:50px; margin-top:6px; font-size:15px; }
  .btn-welcome-primary[disabled] { opacity:.7; cursor:progress; }
  .li-register { color:var(--muted); font-size:14px; margin-top:24px; }
  .li-register a { color:var(--accent-d); font-weight:700; display:inline-flex; align-items:center; gap:6px; }
  .li-register a:hover { text-decoration:underline; }
  .login-guest { display:inline-flex; align-items:center; gap:8px; margin-top:28px; font-size:13.5px; font-weight:600; color:var(--muted); }
  .login-guest:hover { color:var(--ink); }
  @media (min-width: 960px) {
    body { grid-template-columns:minmax(0,1fr) minmax(0,1fr); }
    .login-side { display:flex; flex-direction:column; justify-content:space-between; background:var(--ink); color:#fff; padding:clamp(40px,5vw,72px); }
    .login-side h2 { font-family:var(--fh); font-weight:700; font-size:clamp(34px,3.6vw,48px); letter-spacing:-.035em; line-height:1.05; max-width:14ch; }
    .login-side h2 span { color:var(--accent); }
    .login-side ul { list-style:none; display:grid; gap:14px; }
    .login-side li { display:flex; align-items:center; gap:12px; color:rgba(255,255,255,.7); font-size:14.5px; padding-bottom:14px; border-bottom:1px solid rgba(255,255,255,.12); }
    .login-side li i { color:var(--accent); width:18px; }
    .login-side .side-logo { height:44px; width:auto; align-self:flex-start; background:#fff; padding:6px 10px; border-radius:var(--r); }
  }
</style>
</head>
<body>
<aside class="login-side">
  <img src="logo.png" alt="FYTYA-G" class="side-logo">
  <h2>Vos commandes, <span>suivies de près</span>.</h2>
  <ul>
    <li><i class="fa-solid fa-truck-fast" aria-hidden="true"></i> Livraison en 24h à Abidjan</li>
    <li><i class="fa-solid fa-lock" aria-hidden="true"></i> Paiement mobile sécurisé</li>
    <li><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Historique de toutes vos commandes</li>
  </ul>
</aside>

<main class="login-main">
  <div class="login-box">
    <a href="accueil.php" class="login-brand"><img src="logo.png" alt=""><span>FYTYA-G</span></a>
    <h1 class="login-title">Connexion</h1>
    <p class="login-sub">Retrouvez vos commandes et payez plus vite.</p>

    <?php if ($msg): ?>
    <div class="li-alert" role="alert"><?=icon($msgIcon, 16)?> <span><?=$msg?></span></div>
    <?php endif; ?>

    <form method="POST" action="index.php" id="loginForm" novalidate>
      <?=csrfField()?>
      <input type="hidden" name="redirect" value="<?=sanitize($redirect)?>">
      <div class="li-field">
        <label for="li-email">Adresse e-mail</label>
        <div class="li-input"><input id="li-email" type="email" name="email" placeholder="vous@exemple.com" autocomplete="email" inputmode="email" required></div>
      </div>
      <div class="li-field">
        <label for="li-pass">Mot de passe</label>
        <div class="li-input">
          <input id="li-pass" type="password" name="password" placeholder="Votre mot de passe" autocomplete="current-password" required>
          <button type="button" class="li-toggle" id="liToggle" aria-label="Afficher le mot de passe"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
        </div>
      </div>
      <button type="submit" class="btn-primary btn-welcome-primary" id="liSubmit">Se connecter</button>
    </form>

    <p class="li-register">Pas encore de compte ? <a href="register.php">Créer un compte <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p>
    <a href="accueil.php" class="login-guest"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Continuer sans compte</a>
  </div>
</main>

<script>
(function(){
  var f=document.getElementById('loginForm'), p=document.getElementById('li-pass'), t=document.getElementById('liToggle'), b=document.getElementById('liSubmit');
  t.addEventListener('click',function(){
    var show=p.type==='password'; p.type=show?'text':'password';
    t.setAttribute('aria-label',show?'Masquer le mot de passe':'Afficher le mot de passe');
    t.firstElementChild.className='fa-solid '+(show?'fa-eye-slash':'fa-eye');
  });
  f.addEventListener('submit',function(e){
    var em=document.getElementById('li-email');
    if(!em.value.trim()||!/^\S+@\S+\.\S+$/.test(em.value)){ e.preventDefault(); em.focus(); em.setCustomValidity('Saisissez une adresse e-mail valide.'); em.reportValidity(); em.setCustomValidity(''); return; }
    if(!p.value){ e.preventDefault(); p.focus(); p.setCustomValidity('Saisissez votre mot de passe.'); p.reportValidity(); p.setCustomValidity(''); return; }
    b.disabled=true; b.innerHTML='<i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i> Connexion…';
  });
})();
</script>
</body>
</html>
