<?php
require_once 'includes/config.php';
initUserSession();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$db = getDB();
$ref = $_GET['ref'] ?? '';

// Récupérer la commande
$cmd_stmt = $db->prepare("SELECT * FROM commandes WHERE reference=? AND user_id=?");
$cmd_stmt->execute([$ref, $user['id']]);
$commande = $cmd_stmt->fetch();

if (!$commande) {
    header('Location: mon-compte.php');
    exit;
}

// Récupérer les articles de la commande
$items_stmt = $db->prepare("SELECT * FROM commande_items WHERE commande_id=? ORDER BY id");
$items_stmt->execute([$commande['id']]);
$items = $items_stmt->fetchAll();

$page_title = 'Détail commande';
require_once 'includes/header.php';
?>

<div class="page-hero">
  <div class="container">
    <h1 style="display:flex;align-items:center;gap:12px"><?=icon('package', 28)?> Détail de la <span>commande</span></h1>
    <p>Référence: <strong style="color:var(--orange)"><?=sanitize($commande['reference'])?></strong></p>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:700px">
    
    <!-- STATUS -->
    <div style="background:#f9f9f9;border-radius:var(--rl);padding:20px;margin-bottom:24px">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div>
          <div style="font-size:12px;color:var(--g600);font-weight:600;margin-bottom:4px">STATUT ACTUEL</div>
          <div style="display:inline-block;padding:8px 14px;background:<?=ORDER_STATUSES[$commande['statut']]['bg']?>;color:<?=ORDER_STATUSES[$commande['statut']]['color']?>;border-radius:8px;font-weight:700;font-size:14px">
            <?=ORDER_STATUSES[$commande['statut']]['label']?>
          </div>
        </div>
        <div style="text-align:right">
          <div style="font-size:12px;color:var(--g600);font-weight:600;margin-bottom:4px">DATE</div>
          <div style="font-weight:600"><?=date('d/m/Y H:i', strtotime($commande['created_at']))?></div>
        </div>
      </div>
    </div>
    
    <!-- INFORMATIONS -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
      <div style="background:#fff;border:1.5px solid var(--g200);border-radius:var(--rl);padding:16px">
        <h4 style="font-weight:700;margin-bottom:12px;font-size:14px;display:flex;align-items:center;gap:8px"><?=icon('map-pin', 16)?> Adresse de livraison</h4>
        <p style="font-size:13px;line-height:1.6;color:var(--g700)">
          <?=sanitize($commande['client_prenom'])?> <?=sanitize($commande['client_nom'])?><br>
          <?=sanitize($commande['client_adresse'])?><br>
          <?=sanitize($commande['client_ville'])?>
        </p>
      </div>
      
      <div style="background:#fff;border:1.5px solid var(--g200);border-radius:var(--rl);padding:16px">
        <h4 style="font-weight:700;margin-bottom:12px;font-size:14px;display:flex;align-items:center;gap:8px"><?=icon('phone', 16)?> Contact</h4>
        <p style="font-size:13px;line-height:1.6;color:var(--g700)">
          <span style="display:inline-flex;align-items:center;gap:6px"><?=icon('mail', 13)?> <?=sanitize($commande['client_email'])?></span><br>
          <span style="display:inline-flex;align-items:center;gap:6px"><?=icon('smartphone', 13)?> <?=sanitize($commande['client_tel'])?></span><br>
          <span style="display:inline-flex;align-items:center;gap:6px"><?=icon('credit-card', 13)?> Paiement: <?=sanitize($commande['paiement'])?></span>
          <?php if (in_array($commande['paiement'], PAYDUNYA_METHODS, true)): ?>
            <br><span style="display:inline-flex;align-items:center;gap:6px">
              <?php if (($commande['payment_status'] ?? 'non_paye') === 'paye'): ?>
                <?=icon('check-circle', 13)?> Payé
              <?php else: ?>
                <?=icon('alert-triangle', 13)?> Non payé
              <?php endif; ?>
            </span>
          <?php endif; ?>
        </p>
      </div>
    </div>

    <?php if (in_array($commande['paiement'], PAYDUNYA_METHODS, true) && ($commande['payment_status'] ?? 'non_paye') !== 'paye'): ?>
    <div style="text-align:center;margin-bottom:24px">
      <a href="payer.php?ref=<?=urlencode($commande['reference'])?>" class="btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px">
        <?=icon('credit-card', 16)?> Payer maintenant avec <?=sanitize($commande['paiement'])?>
      </a>
    </div>
    <?php endif; ?>
    <?php if (($_GET['paiement'] ?? '') === 'erreur'): ?>
    <div style="background:#FEE2E2;border:1.5px solid #FCA5A5;color:#991B1B;padding:14px 18px;border-radius:10px;margin-bottom:24px;font-size:14px;font-weight:600;text-align:center">
      Le paiement en ligne a échoué ou a été annulé. Vous pouvez réessayer.
    </div>
    <?php endif; ?>
    
    <!-- ARTICLES -->
    <div style="background:#fff;border:1.5px solid var(--g200);border-radius:var(--rl);padding:16px;margin-bottom:24px">
      <h4 style="font-weight:700;margin-bottom:14px;font-size:14px;display:flex;align-items:center;gap:8px"><?=icon('shopping-cart', 16)?> Articles</h4>
      
      <div style="border-top:1px solid var(--g100);padding-top:12px">
        <?php foreach($items as $item): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--g100);font-size:13px">
          <div style="display:flex;align-items:center;gap:8px;flex:1">
            <span style="font-size:20px"><?=sanitize($item['emoji'])?></span>
            <div>
              <div style="font-weight:600"><?=sanitize($item['nom_produit'])?></div>
              <div style="color:var(--g600)">Qté: <?=(int)$item['quantite']?></div>
            </div>
          </div>
          <div style="text-align:right;font-weight:600">
            <?=formatPrice((int)$item['prix_unit'] * (int)$item['quantite'])?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <div style="padding-top:12px;display:flex;justify-content:space-between;align-items:center;font-size:13px;color:var(--g600)">
        <span>Livraison</span>
        <span><?=((int)$commande['frais_livraison'] > 0) ? formatPrice((int)$commande['frais_livraison']) : 'Gratuite'?></span>
      </div>
      <div style="border-top:2px solid var(--orange);margin-top:8px;padding-top:12px;display:flex;justify-content:space-between;align-items:center;font-weight:700">
        <span>TOTAL</span>
        <span style="font-size:18px;color:var(--orange)"><?=formatPrice((int)$commande['total'])?></span>
      </div>
    </div>
    
    <!-- NOTE -->
    <?php if ($commande['note_client']): ?>
    <div style="background:#FEF3C7;border-radius:var(--rl);padding:14px;margin-bottom:24px;font-size:13px;color:#B45309;display:flex;align-items:flex-start;gap:8px">
      <?=icon('file-text', 15)?> <strong>Votre note:</strong> <?=sanitize($commande['note_client'])?>
    </div>
    <?php endif; ?>
    
    <!-- ACTIONS -->
    <div style="text-align:center">
      <a href="mon-compte.php" class="btn-outline" style="display:inline-flex;align-items:center;gap:6px;padding:12px 24px;font-size:14px"><?=icon('arrow-left', 14)?> Retour au compte</a>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>