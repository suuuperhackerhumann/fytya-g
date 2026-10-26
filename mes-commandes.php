<?php
require_once 'includes/config.php';
initUserSession();

$page_title = 'Mes commandes';

if (!isset($_SESSION['user'])) {
    header('Location: index.php?redirect=mes-commandes.php');
    exit;
}

$user = $_SESSION['user'];
$db = getDB();

// Commandes de l'utilisateur
$stmt = $db->prepare("SELECT * FROM commandes WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$commandes = $stmt->fetchAll();

// Articles de toutes ces commandes, avec la catégorie du produit quand elle est encore connue
$fav_produit = null;
$fav_categorie = null;
$total_montant = 0;

if (!empty($commandes)) {
    $ids = array_column($commandes, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $items_stmt = $db->prepare("
        SELECT ci.nom_produit, ci.emoji, ci.quantite, p.categorie
        FROM commande_items ci
        LEFT JOIN produits p ON p.id = ci.produit_id
        WHERE ci.commande_id IN ($in)
    ");
    $items_stmt->execute($ids);
    $all_items = $items_stmt->fetchAll();

    $qty_par_produit = [];
    $qty_par_categorie = [];
    foreach ($all_items as $it) {
        $key = $it['nom_produit'];
        $qty_par_produit[$key] = ($qty_par_produit[$key] ?? ['qty' => 0, 'emoji' => $it['emoji']]);
        $qty_par_produit[$key]['qty'] += (int)$it['quantite'];

        if ($it['categorie']) {
            $qty_par_categorie[$it['categorie']] = ($qty_par_categorie[$it['categorie']] ?? 0) + (int)$it['quantite'];
        }
    }

    if (!empty($qty_par_produit)) {
        uasort($qty_par_produit, fn($a, $b) => $b['qty'] <=> $a['qty']);
        $top_nom = array_key_first($qty_par_produit);
        $fav_produit = ['nom' => $top_nom, 'emoji' => $qty_par_produit[$top_nom]['emoji'], 'qty' => $qty_par_produit[$top_nom]['qty']];
    }

    if (!empty($qty_par_categorie)) {
        arsort($qty_par_categorie);
        $top_cat = array_key_first($qty_par_categorie);
        $fav_categorie = ['slug' => $top_cat, 'label' => CATEGORIES[$top_cat]['label'] ?? $top_cat, 'icon' => CATEGORIES[$top_cat]['icon'] ?? 'package', 'qty' => $qty_par_categorie[$top_cat]];
    }

    foreach ($commandes as $c) {
        if ($c['statut'] !== 'annulee') $total_montant += (int)$c['total'];
    }
}

// Étapes affichées dans la barre de progression (hors annulation)
$progress_steps = ['en_attente', 'confirmee', 'en_livraison', 'livree'];

$just_ordered = trim($_GET['just_ordered'] ?? '');

require_once 'includes/header.php';
?>

<div class="page-hero">
  <div class="container">
    <h1>Mes <span>commandes</span></h1>
    <p>Suivez l'évolution de vos commandes et vos habitudes d'achat</p>
  </div>
</div>

<?php if ($just_ordered): ?>
<div style="background:#D1FAE5;border-bottom:2px solid #10B981;padding:14px 0;">
  <div class="container" style="display:flex;align-items:center;gap:10px">
    <div style="font-size:14px;color:#065F46">
      Commande <strong><?=sanitize($just_ordered)?></strong> confirmée ! Elle passera automatiquement en statut « Confirmée » d'ici quelques secondes.
    </div>
  </div>
</div>
<?php endif; ?>

<section class="section">
  <div class="container">

    <!-- STATISTIQUES -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:40px">

      <div style="background:#fff;border:1.5px solid var(--g200);border-radius:var(--rl);padding:22px">
        <div style="font-size:12px;color:var(--g600);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Commandes totales</div>
        <div style="font-family:var(--fh);font-weight:800;font-size:30px;color:var(--orange)"><?=count($commandes)?></div>
      </div>

      <div style="background:#fff;border:1.5px solid var(--g200);border-radius:var(--rl);padding:22px">
        <div style="font-size:12px;color:var(--g600);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Montant total dépensé</div>
        <div style="font-family:var(--fh);font-weight:800;font-size:22px"><?=formatPrice($total_montant)?></div>
      </div>

      <div style="background:#fff;border:1.5px solid var(--g200);border-radius:var(--rl);padding:22px">
        <div style="font-size:12px;color:var(--g600);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Produit favori</div>
        <?php if ($fav_produit): ?>
        <div style="font-family:var(--fh);font-weight:800;font-size:17px"><?=sanitize($fav_produit['nom'])?></div>
        <div style="font-size:12px;color:var(--g600);margin-top:4px"><?=$fav_produit['qty']?> unité(s) commandée(s)</div>
        <?php else: ?>
        <div style="color:var(--g400);font-size:14px">—</div>
        <?php endif; ?>
      </div>

      <div style="background:#fff;border:1.5px solid var(--g200);border-radius:var(--rl);padding:22px">
        <div style="font-size:12px;color:var(--g600);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Catégorie favorite</div>
        <?php if ($fav_categorie): ?>
        <div style="font-family:var(--fh);font-weight:800;font-size:17px;display:flex;align-items:center;gap:8px"><?=icon($fav_categorie['icon'], 18)?> <?=sanitize($fav_categorie['label'])?></div>
        <div style="font-size:12px;color:var(--g600);margin-top:4px"><?=$fav_categorie['qty']?> unité(s) commandée(s)</div>
        <?php else: ?>
        <div style="color:var(--g400);font-size:14px">—</div>
        <?php endif; ?>
      </div>

    </div>

    <!-- LISTE DES COMMANDES -->
    <h2 style="font-family:var(--fh);font-weight:800;font-size:20px;margin-bottom:20px">Historique</h2>

    <?php if (empty($commandes)): ?>
    <div style="text-align:center;padding:60px 20px;background:#f9f9f9;border-radius:var(--rl);color:var(--g600)">
      <div style="color:var(--g400);margin-bottom:12px"><?=icon('package', 40)?></div>
      <p>Vous n'avez pas encore de commandes.</p>
      <a href="accueil.php" class="btn-primary" style="display:inline-flex;align-items:center;gap:6px;margin-top:16px;padding:12px 24px;font-size:14px">Voir nos produits <?=icon('arrow-right', 14)?></a>
    </div>
    <?php else: ?>

    <div style="display:grid;gap:18px">
      <?php foreach ($commandes as $cmd):
        $is_annulee = $cmd['statut'] === 'annulee';
        $step_index = array_search($cmd['statut'], $progress_steps, true);
      ?>
      <div data-ref="<?=sanitize($cmd['reference'])?>" class="commande-card"
           style="background:#fff;border:1.5px solid <?=$cmd['reference'] === $just_ordered ? 'var(--orange)' : 'var(--g200)'?>;border-radius:var(--rl);padding:22px">

        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px">
          <div>
            <div style="font-size:12px;color:var(--g600);font-weight:600">RÉFÉRENCE</div>
            <div style="font-family:monospace;font-weight:700;color:var(--orange);font-size:15px"><?=sanitize($cmd['reference'])?></div>
          </div>
          <div>
            <div style="font-size:12px;color:var(--g600);font-weight:600">DATE</div>
            <div style="font-size:14px"><?=date('d/m/Y à H:i', strtotime($cmd['created_at']))?></div>
          </div>
          <div>
            <div style="font-size:12px;color:var(--g600);font-weight:600">MONTANT</div>
            <div style="font-weight:700;font-size:16px"><?=formatPrice((int)$cmd['total'])?></div>
          </div>
          <div class="statut-badge" style="display:inline-block;padding:6px 14px;background:<?=ORDER_STATUSES[$cmd['statut']]['bg']?>;color:<?=ORDER_STATUSES[$cmd['statut']]['color']?>;border-radius:6px;font-size:13px;font-weight:700">
            <?=ORDER_STATUSES[$cmd['statut']]['label']?>
          </div>
          <a href="commande-detail.php?ref=<?=urlencode($cmd['reference'])?>" class="btn-outline" style="padding:8px 16px;font-size:13px">Détails</a>
        </div>

        <?php if (!$is_annulee): ?>
        <div style="display:flex;align-items:center;gap:6px">
          <?php foreach ($progress_steps as $i => $step): ?>
          <div style="flex:1;height:6px;border-radius:100px;background:<?=$i <= $step_index ? 'var(--orange)' : 'var(--g200)'?>;transition:background .3s"></div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:var(--g600)">
          <?php foreach ($progress_steps as $i => $step): ?>
          <span style="<?=$i <= $step_index ? 'color:var(--orange);font-weight:700' : ''?>"><?=ORDER_STATUSES[$step]['label']?></span>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="font-size:13px;color:#991B1B">Commande annulée</div>
        <?php endif; ?>

      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>

  </div>
</section>

<?php if ($just_ordered): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Vide le panier après une commande tout juste validée
  if (typeof Cart !== 'undefined') Cart.clear();

  var ref = <?=json_encode($just_ordered)?>;
  var card = document.querySelector('.commande-card[data-ref="' + CSS.escape(ref) + '"]');
  if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });

  // Après 15 secondes, la commande "en attente" passe automatiquement en "confirmée"
  setTimeout(function() {
    fetch('ajax/order-status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'ref=' + encodeURIComponent(ref)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.success || !card) return;
      var badge = card.querySelector('.statut-badge');
      if (badge) {
        badge.textContent = data.label;
        badge.style.background = data.bg;
        badge.style.color = data.color;
      }
      // Met à jour la barre de progression (en_attente -> confirmee = étape 1)
      var bars = card.querySelectorAll('div[style*="border-radius:100px"]');
      if (bars.length >= 2) bars[1].style.background = 'var(--orange)';
      var labels = card.querySelectorAll('span');
      if (labels.length >= 2) { labels[1].style.color = 'var(--orange)'; labels[1].style.fontWeight = '700'; }
    })
    .catch(function() {});
  }, 15000);
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
