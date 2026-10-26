<?php
require_once 'includes/config.php';
initUserSession();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$ref  = $_GET['ref'] ?? '';
$db   = getDB();

$stmt = $db->prepare("SELECT * FROM commandes WHERE reference=? AND user_id=?");
$stmt->execute([$ref, $user['id']]);
$commande = $stmt->fetch();

$page_title = 'Paiement';
require_once 'includes/header.php';

$paye = $commande && ($commande['payment_status'] ?? '') === 'paye';
?>
<section class="section">
  <div class="container" style="max-width:560px;text-align:center">
    <?php if (!$commande): ?>
      <h1>Commande introuvable</h1>
      <p>Impossible de retrouver cette commande.</p>
    <?php elseif ($paye): ?>
      <h1><?=icon('check-circle', 28)?> Paiement confirmé</h1>
      <p>Merci ! Votre commande <strong><?=sanitize($ref)?></strong> est payée.</p>
    <?php else: ?>
      <h1><?=icon('clock', 28)?> Paiement en cours de vérification</h1>
      <p>Nous confirmons votre paiement PayDunya pour la commande <strong><?=sanitize($ref)?></strong>. Cela peut prendre quelques instants — rechargez cette page si besoin.</p>
    <?php endif; ?>
    <div style="margin-top:24px">
      <a href="commande-detail.php?ref=<?=urlencode($ref)?>" class="btn-primary">Voir ma commande</a>
    </div>
  </div>
</section>
<?php require_once 'includes/footer.php'; ?>
