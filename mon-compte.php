<?php
require_once 'includes/config.php';
initUserSession();

$page_title = 'Mon compte';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: login.php?redirect=mon-compte.php');
    exit;
}

$user = $_SESSION['user'];
$db = getDB();

// Récupérer les commandes de l'utilisateur
$commandes = $db->prepare("SELECT * FROM commandes WHERE user_id=? ORDER BY created_at DESC");
$commandes->execute([$user['id']]);
$user_commandes = $commandes->fetchAll();

require_once 'includes/header.php';
?>

<div class="page-hero">
  <div class="container">
    <div class="page-hero-stack">
      <span class="section-tag section-tag-soft">Compte</span>
      <h1><span>Mon compte</span></h1>
      <p>Bienvenue <?=sanitize($user['prenom'])?> ! Voici vos commandes et informations.</p>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="account-grid">
      <div class="checkout-box">
        <h3 style="font-family:var(--fh);font-weight:700;font-size:16px;margin-bottom:16px">Mes informations</h3>
        <div style="font-size:14px;line-height:1.8;color:var(--g700)">
          <p><strong>Nom:</strong> <?=sanitize($user['nom'])?></p>
          <p><strong>Prénom:</strong> <?=sanitize($user['prenom'])?></p>
          <p><strong>Email:</strong> <?=sanitize($user['email'])?></p>
        </div>
      </div>

      <div class="checkout-box">
        <h3 style="font-family:var(--fh);font-weight:700;font-size:16px;margin-bottom:16px">Statistiques</h3>
        <div style="font-size:14px;line-height:1.8;color:var(--g700)">
          <p><strong>Nombre de commandes:</strong> <?=count($user_commandes)?></p>
          <?php
            $total = 0;
            foreach($user_commandes as $c) {
              if ($c['statut'] !== 'annulee') {
                $total += $c['total'];
              }
            }
          ?>
          <p><strong>Montant total:</strong> <?=formatPrice($total)?></p>
        </div>
      </div>
    </div>

    <h2 style="font-family:var(--fh);font-weight:800;font-size:20px;margin-bottom:20px">Mes commandes</h2>
    <p style="margin-top:-14px;margin-bottom:20px"><a href="mes-commandes.php" style="color:var(--orange);font-weight:600;font-size:14px">Voir le suivi détaillé et mes statistiques d'achat</a></p>

    <?php if (empty($user_commandes)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">◌</div>
      <p>Vous n'avez pas encore de commandes.</p>
      <a href="accueil.php" class="btn-primary" style="display:inline-block;margin-top:16px;padding:12px 24px;font-size:14px">Voir nos produits</a>
    </div>
    <?php else: ?>

    <div class="orders-list">
      <?php foreach($user_commandes as $cmd): ?>
      <div class="order-row">
        <div>
          <div style="font-size:12px;color:var(--g600);font-weight:600;margin-bottom:4px">RÉFÉRENCE</div>
          <div style="font-family:monospace;font-weight:700;color:var(--orange)"><?=sanitize($cmd['reference'])?></div>
        </div>

        <div>
          <div style="font-size:12px;color:var(--g600);font-weight:600;margin-bottom:4px">MONTANT</div>
          <div style="font-weight:700;font-size:16px"><?=formatPrice((int)$cmd['total'])?></div>
        </div>

        <div>
          <div style="font-size:12px;color:var(--g600);font-weight:600;margin-bottom:4px">STATUT</div>
          <div style="display:inline-block;padding:6px 12px;background:<?=ORDER_STATUSES[$cmd['statut']]['bg']?>;color:<?=ORDER_STATUSES[$cmd['statut']]['color']?>;border-radius:6px;font-size:13px;font-weight:600">
            <?=ORDER_STATUSES[$cmd['statut']]['label']?>
          </div>
        </div>

        <div style="text-align:right">
          <a href="commande-detail.php?ref=<?=urlencode($cmd['reference'])?>" class="btn-primary" style="display:inline-block;padding:10px 16px;font-size:13px">Détails</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>

    <div style="text-align:center;margin-top:40px;padding-top:20px;border-top:1.5px solid var(--g200)">
      <a href="logout.php" class="btn-outline" style="display:inline-block;padding:12px 24px;font-size:14px">Se déconnecter</a>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
