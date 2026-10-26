<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: login.php'); exit;
}
require_once '../includes/config.php';
require_once 'icons.php';
applySecurityHeaders();
$db = getDB();

// Comptes pour les badges sidebar
$nb_attente = $db->query("SELECT COUNT(*) FROM commandes WHERE statut='en_attente'")->fetchColumn();
$nb_total   = $db->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
$last_order_id = (int)$db->query("SELECT COALESCE(MAX(id),0) FROM commandes")->fetchColumn();

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_titles  = [
    'index'     => 'Tableau de bord',
    'commandes' => 'Gestion des commandes',
    'commande'  => 'Détail commande',
    'produits'  => 'Gestion des produits',
    'produit'   => 'Modifier produit',
];
$page_title = $page_titles[$current_page] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=$page_title?> — Admin <?=SITE_NAME?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="../logo.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="admin.css">
<link rel="stylesheet" href="premium-admin.css?v=<?= @filemtime(__DIR__.'/premium-admin.css') ?: 1 ?>">
<script>
  // État initial pour le polling "nouvelles commandes" (voir admin_footer.php)
  window.__ADMIN_LAST_ORDER_ID = <?=(int)$last_order_id?>;
  window.__ADMIN_CURRENT_PAGE  = <?=json_encode($current_page)?>;
</script>
</head>
<body>
<div class="admin-layout">

<!-- OVERLAY (mobile, ferme la sidebar au clic en dehors) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="adminSidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <img src="../logo.png" alt="FYTYA-G">
      <div>
        <div class="sidebar-logo-text">FYTYA-G</div>
        <div class="sidebar-logo-sub">Administration</div>
      </div>
    </div>
    <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Fermer le menu"><?=icon('x', 16)?></button>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Principal</div>
    <a href="index.php" class="nav-item <?=$current_page==='index'?'active':''?>">
      <?=icon('dashboard', 17, 'icon')?> Tableau de bord
    </a>

    <div class="nav-section">Gestion</div>
    <a href="commandes.php" class="nav-item <?=$current_page==='commandes'||$current_page==='commande'?'active':''?>">
      <?=icon('shopping-cart', 17, 'icon')?> Commandes
      <span id="sidebar-order-badge" class="nav-badge" style="<?=$nb_attente > 0 ? '' : 'display:none'?>"><?=$nb_attente?></span>
    </a>
    <a href="produits.php" class="nav-item <?=$current_page==='produits'||$current_page==='produit'?'active':''?>">
      <?=icon('package', 17, 'icon')?> Produits
    </a>

    <div class="nav-section">Liens</div>
    <a href="../index.php" target="_blank" class="nav-item">
      <?=icon('external-link', 17, 'icon')?> Voir le site
    </a>
    <a href="logout.php" class="nav-item" style="color:rgba(255,80,80,.8)" onmouseover="this.style.background='rgba(255,80,80,.1)'" onmouseout="this.style.background=''">
      <?=icon('log-out', 17, 'icon')?> Déconnexion
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?=strtoupper(substr($_SESSION['admin_nom'],0,1))?></div>
      <div class="sidebar-user-info">
        <strong><?=sanitize($_SESSION['admin_nom'])?></strong>
        <span>Administrateur</span>
      </div>
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <div class="admin-topbar">
    <div style="display:flex;align-items:center;gap:12px;min-width:0">
      <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="adminSidebar"><?=icon('menu', 20)?></button>
      <div class="topbar-title"><?=$page_title?></div>
    </div>
    <div class="topbar-actions">
      <a id="topbar-order-alert" href="commandes.php?statut=en_attente" style="display:<?=$nb_attente > 0 ? 'flex' : 'none'?>;align-items:center;gap:6px;background:#FEF3C7;color:#B45309;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;font-family:var(--fh)">
        <?=icon('bell', 15)?> <span id="topbar-order-count"><?=$nb_attente?></span> commande(s) en attente
      </a>
      <a href="../index.php" target="_blank" class="topbar-btn outline"><?=icon('external-link', 14)?> Voir site</a>
    </div>
  </div>
  <div class="admin-content">