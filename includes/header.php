<?php
require_once __DIR__ . '/icons.php';
$current = basename($_SERVER['PHP_SELF'], '.php');
applySecurityHeaders();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? sanitize($page_title).' — ' : '' ?><?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="icon" type="image/png" href="../logo.png">
<link rel="stylesheet" href="style.css?v=<?= @filemtime(__DIR__.'/../style.css') ?: 1 ?>">
<link rel="stylesheet" href="premium.css?v=<?= @filemtime(__DIR__.'/../premium.css') ?: 1 ?>">
    <style>
  .tabbar { display: none !important; }
  @media (max-width: 900px) { .tabbar { display: grid !important; } }
</style>
</head>
<body>

<div class="topbar" role="region" aria-label="Informations de livraison">
  <div class="topbar-marquee">
    <div class="topbar-track">
      <?php for ($g = 0; $g < 2; $g++): ?>
      <div class="topbar-group"<?= $g ? ' aria-hidden="true"' : '' ?>>
        <?php for ($k = 0; $k < 4; $k++): ?>
        <div class="topbar-item"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i> Livraison gratuite pour les œufs et le poulet à Abidjan. Épices : livraison à 500 FCFA.</div>
        <?php endfor; ?>
      </div>
      <?php endfor; ?>
    </div>
  </div>
</div>

<header class="header">
  <div class="container header-inner">

    <a href="accueil.php" class="logo">
      <img src="logo.png" alt="FYTYA-G Logo" class="logo-img">
      <span class="logo-text"><strong>FYTYA-G</strong><small>Depuis 2015, Abidjan</small></span>
    </a>

    <nav class="nav">
      <a href="accueil.php" class="nav-link <?= $current==='accueil' ?'active':'' ?>">Accueil</a>
      <?php foreach (CATEGORIES as $slug => $cat): ?>
      <a href="<?=$cat['page']?>" class="nav-link <?= $current===pathinfo($cat['page'],PATHINFO_FILENAME) ?'active':'' ?>"><?=sanitize($cat['label'])?></a>
      <?php endforeach; ?>
      <a href="contact.php" class="nav-link <?= $current==='contact' ?'active':'' ?>">Contact</a>
    </nav>

    <div class="header-actions">
      <a href="panier.php" class="btn-icon cart-btn" title="Mon panier">
        <?=icon('shopping-cart', 18)?> <span class="cart-count">0</span>
      </a>
      <?php if (isset($_SESSION['user'])): ?>
        <a href="mes-commandes.php" class="btn-outline desktop-only" style="font-size:13px;padding:8px 16px" title="Mes commandes">Mes commandes</a>
        <a href="mon-compte.php" class="btn-primary desktop-only" style="font-size:13px">
          <?=sanitize($_SESSION['user']['prenom'])?>
        </a>
        <a href="logout.php" class="btn-outline desktop-only" style="font-size:13px;padding:8px 16px" title="Se déconnecter">Quitter</a>
      <?php else: ?>
        <span class="guest-badge desktop-only" style="font-size:11px;padding:6px 12px;background:rgba(255,107,0,.1);border:1px solid rgba(255,107,0,.3);border-radius:6px;color:var(--orange);font-weight:600;white-space:nowrap;display:inline-flex;align-items:center;gap:5px"><?=icon('user', 13)?> Invité</span>
        <a href="index.php?login=1" class="btn-primary desktop-only" style="font-size:13px">Connexion</a>
      <?php endif; ?>

      <button class="mobile-menu-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>

  </div>
</header>

<div class="mobile-menu-overlay"></div>
<aside class="mobile-menu-panel" aria-label="Menu mobile">
  <div class="mobile-menu-head">
    <a href="accueil.php" class="logo mobile-logo">
      <img src="logo.png" alt="FYTYA-G Logo" class="logo-img">
      <span class="logo-text"><strong>FYTYA-G</strong><small>Depuis 2015, Abidjan</small></span>
    </a>
    <button class="mobile-menu-close" type="button" aria-label="Fermer le menu"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
  </div>

  <nav class="mobile-menu-nav">
    <a href="accueil.php" class="mobile-link <?= $current==='accueil' ?'active':'' ?>">Accueil</a>
    <?php foreach (CATEGORIES as $slug => $cat): ?>
    <a href="<?=$cat['page']?>" class="mobile-link <?= $current===pathinfo($cat['page'],PATHINFO_FILENAME) ?'active':'' ?>" style="display:flex;align-items:center;gap:10px"><?=icon($cat['icon'], 17)?> <?=sanitize($cat['label'])?></a>
    <?php endforeach; ?>
    <a href="contact.php" class="mobile-link <?= $current==='contact' ?'active':'' ?>">Contact</a>
  </nav>

  <div class="mobile-menu-actions">
    <?php if (isset($_SESSION['user'])): ?>
      <a href="mon-compte.php" class="btn-primary mobile-block">Mon compte</a>
      <a href="mes-commandes.php" class="btn-outline mobile-block">Mes commandes</a>
      <a href="logout.php" class="btn-outline mobile-block">Se déconnecter</a>
    <?php else: ?>
      <a href="index.php?login=1" class="btn-primary mobile-block">Connexion</a>
      <a href="register.php" class="btn-outline mobile-block">Créer un compte</a>
    <?php endif; ?>
  </div>
</aside>

<nav class="tabbar" aria-label="Navigation principale">
  <a href="accueil.php" class="<?= $current==='accueil' ? 'active' : '' ?>"><i class="fa-solid fa-house" aria-hidden="true"></i><span>Accueil</span></a>
  <a href="accueil.php#familles"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i><span>Produits</span></a>
  <a href="panier.php" class="<?= $current==='panier' ? 'active' : '' ?>"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i><span>Panier</span><span class="cart-count">0</span></a>
  <?php if (isset($_SESSION['user'])): ?>
  <a href="mon-compte.php" class="<?= in_array($current, ['mon-compte','mes-commandes'], true) ? 'active' : '' ?>"><i class="fa-solid fa-user" aria-hidden="true"></i><span>Compte</span></a>
  <?php else: ?>
  <a href="index.php?login=1"><i class="fa-solid fa-user" aria-hidden="true"></i><span>Connexion</span></a>
  <?php endif; ?>
</nav>