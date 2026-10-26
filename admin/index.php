<?php require_once 'admin_header.php';

// Statistiques
$total_cmd    = $db->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
$rev_total    = $db->query("SELECT COALESCE(SUM(total),0) FROM commandes WHERE statut != 'annulee'")->fetchColumn();
$cmd_attente  = $db->query("SELECT COUNT(*) FROM commandes WHERE statut='en_attente'")->fetchColumn();
$cmd_livrees  = $db->query("SELECT COUNT(*) FROM commandes WHERE statut='livree'")->fetchColumn();
$cmd_livraison= $db->query("SELECT COUNT(*) FROM commandes WHERE statut='en_livraison'")->fetchColumn();
$nb_produits  = $db->query("SELECT COUNT(*) FROM produits WHERE actif=1")->fetchColumn();
$stock_faible = $db->query("SELECT COUNT(*) FROM produits WHERE stock < 5 AND actif=1")->fetchColumn();

// Dernières commandes
$dernieres = $db->query("SELECT * FROM commandes ORDER BY created_at DESC LIMIT 8")->fetchAll();

// Commandes par statut (graphique simple)
$par_statut = $db->query("SELECT statut, COUNT(*) as n FROM commandes GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR);

// Revenus du mois
$rev_mois = $db->query("SELECT COALESCE(SUM(total),0) FROM commandes WHERE statut!='annulee' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

// Produits les plus vendus (quantités cumulées sur les commandes non annulées)
$top_produits = $db->query("
    SELECT ci.produit_id AS pid, ci.nom_produit, ci.emoji,
           SUM(ci.quantite) AS qte_vendue,
           SUM(ci.quantite * ci.prix_unit) AS revenu
    FROM commande_items ci
    JOIN commandes c ON c.id = ci.commande_id
    WHERE c.statut != 'annulee'
    GROUP BY ci.produit_id, ci.nom_produit, ci.emoji
    ORDER BY qte_vendue DESC
    LIMIT 5
")->fetchAll();
?>

<!-- STATS -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#FFF0E6;color:#FF6600"><?=icon('package', 22)?></div>
    <div class="stat-info">
      <div class="stat-label">Commandes totales</div>
      <div class="stat-value"><?=$total_cmd?></div>
      <div class="stat-change up"><?=icon('arrow-up', 12)?> <?=$cmd_livrees?> livrées</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#D1FAE5;color:#10b981"><?=icon('dollar-sign', 22)?></div>
    <div class="stat-info">
      <div class="stat-label">Chiffre d'affaires</div>
      <div class="stat-value" style="font-size:20px"><?=number_format((int)$rev_total,0,',',' ')?></div>
      <div class="stat-change" style="color:var(--g400)">FCFA total</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#FEF3C7;color:#B45309"><?=icon('clock', 22)?></div>
    <div class="stat-info">
      <div class="stat-label">En attente</div>
      <div class="stat-value" style="color:<?=$cmd_attente>0?'#B45309':'var(--g800)'?>"><?=$cmd_attente?></div>
      <?php if($cmd_attente>0): ?>
      <div class="stat-change down"><?=icon('alert-triangle', 12)?> Action requise</div>
      <?php else: ?>
      <div class="stat-change up"><?=icon('check', 12)?> Tout traité</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#EDE9FE;color:#6D28D9"><?=icon('shopping-bag', 22)?></div>
    <div class="stat-info">
      <div class="stat-label">Produits actifs</div>
      <div class="stat-value"><?=$nb_produits?></div>
      <?php if($stock_faible>0): ?>
      <div class="stat-change down"><?=icon('alert-triangle', 12)?> <?=$stock_faible?> en stock faible</div>
      <?php else: ?>
      <div class="stat-change up"><?=icon('check', 12)?> Stocks OK</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- GRAPHIQUE STATUTS (visuel simple) -->
<div class="dashboard-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

  <div class="table-card">
    <div class="table-header"><span class="table-title">Répartition des commandes</span></div>
    <div style="padding:20px">
      <?php foreach(ORDER_STATUSES as $key => $st):
        $n = (int)($par_statut[$key] ?? 0);
        $pct = $total_cmd > 0 ? round($n / $total_cmd * 100) : 0;
      ?>
      <div style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px;font-size:13px">
          <span style="font-weight:600;color:var(--g700)"><?=$st['label']?></span>
          <span style="color:var(--g500)"><?=$n?> (<?=$pct?>%)</span>
        </div>
        <div style="height:8px;background:var(--g100);border-radius:100px;overflow:hidden">
          <div style="height:100%;width:<?=$pct?>%;background:<?=$st['color']?>;border-radius:100px;transition:width .6s ease"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="table-card">
    <div class="table-header"><span class="table-title">Actions rapides</span></div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:12px">
      <a href="commandes.php?statut=en_attente" style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--g50);border-radius:10px;border:1.5px solid var(--g200);transition:all .2s" onmouseover="this.style.borderColor='#FF6600'" onmouseout="this.style.borderColor='var(--g200)'">
        <span style="color:#FF6600;flex-shrink:0"><?=icon('clock', 22)?></span>
        <div style="flex:1;min-width:0"><strong style="font-size:13px;display:block">Commandes en attente</strong><span style="font-size:12px;color:var(--g500)"><?=$cmd_attente?> commande(s) à traiter</span></div>
        <span style="margin-left:auto;color:var(--orange)"><?=icon('chevron-right', 16)?></span>
      </a>
      <a href="commandes.php?statut=en_livraison" style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--g50);border-radius:10px;border:1.5px solid var(--g200);transition:all .2s" onmouseover="this.style.borderColor='#6D28D9'" onmouseout="this.style.borderColor='var(--g200)'">
        <span style="color:#6D28D9;flex-shrink:0"><?=icon('truck', 22)?></span>
        <div style="flex:1;min-width:0"><strong style="font-size:13px;display:block">En cours de livraison</strong><span style="font-size:12px;color:var(--g500)"><?=$cmd_livraison?> commande(s)</span></div>
        <span style="margin-left:auto;color:#6D28D9"><?=icon('chevron-right', 16)?></span>
      </a>
      <a href="produits.php" style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--g50);border-radius:10px;border:1.5px solid var(--g200);transition:all .2s" onmouseover="this.style.borderColor='#10b981'" onmouseout="this.style.borderColor='var(--g200)'">
        <span style="color:#10b981;flex-shrink:0"><?=icon('package', 22)?></span>
        <div style="flex:1;min-width:0"><strong style="font-size:13px;display:block">Gérer les produits</strong><span style="font-size:12px;color:var(--g500)"><?=$nb_produits?> produits · <?=$stock_faible?> stock(s) faible(s)</span></div>
        <span style="margin-left:auto;color:#10b981"><?=icon('chevron-right', 16)?></span>
      </a>
    </div>
  </div>

</div>

<!-- PRODUITS LES PLUS VENDUS -->
<div class="table-card" style="margin-bottom:24px">
  <div class="table-header">
    <span class="table-title"><?=icon('star', 16)?> Produits les plus vendus</span>
    <a href="produits.php" class="topbar-btn">Gérer les produits</a>
  </div>
  <?php if (empty($top_produits)): ?>
  <div style="padding:36px 20px;text-align:center;color:var(--g400);font-size:13px">
    Aucune vente enregistrée pour le moment — le classement apparaîtra ici dès la première commande confirmée.
  </div>
  <?php else:
    $best  = $top_produits[0];
    $reste = array_slice($top_produits, 1);
  ?>
  <div style="padding:20px">
    <!-- #1 mis en avant -->
    <div style="display:flex;align-items:center;gap:16px;padding:18px;background:linear-gradient(135deg,#FFF7ED,#FFEDD5);border-radius:12px;border:1.5px solid #FED7AA;<?=$reste?'margin-bottom:16px':''?>">
      <div style="flex:1;min-width:0">
        <div style="font-size:11px;font-weight:700;color:#B45309;letter-spacing:.5px;margin-bottom:2px">MEILLEURE VENTE</div>
        <div style="font-family:var(--fh);font-weight:800;font-size:16px;color:var(--g800)"><?=sanitize($best['nom_produit'])?></div>
        <div style="font-size:12px;color:var(--g500);margin-top:2px"><?=(int)$best['qte_vendue']?> unité(s) vendue(s) · <strong style="color:var(--orange)"><?=formatPrice((int)$best['revenu'])?></strong> générés</div>
      </div>
    </div>

    <?php if ($reste): ?>
    <table class="data-table">
      <thead><tr><th>#</th><th>Produit</th><th>Unités vendues</th><th>Revenu généré</th></tr></thead>
      <tbody>
      <?php foreach ($reste as $i => $tp): ?>
      <tr>
        <td style="color:var(--g400);font-weight:700"><?=$i+2?></td>
        <td><?=sanitize($tp['nom_produit'])?></td>
        <td><?=(int)$tp['qte_vendue']?></td>
        <td><strong style="color:var(--orange)"><?=formatPrice((int)$tp['revenu'])?></strong></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- DERNIÈRES COMMANDES -->
<div class="table-card">
  <div class="table-header">
    <span class="table-title"><?=icon('clock', 16)?> Dernières commandes</span>
    <a href="commandes.php" class="topbar-btn">Voir tout</a>
  </div>
  <table class="data-table">
    <thead><tr>
      <th>Référence</th><th>Client</th><th>Ville</th><th>Paiement</th><th>Total</th><th>Statut</th><th>Date</th><th>Action</th>
    </tr></thead>
    <tbody>
    <?php foreach($dernieres as $c):
      $st = ORDER_STATUSES[$c['statut']] ?? ['label'=>$c['statut'],'color'=>'#888','bg'=>'#eee'];
    ?>
    <tr>
      <td><strong style="color:var(--orange);font-family:var(--fh)"><?=sanitize($c['reference'])?></strong></td>
      <td><?=sanitize($c['client_prenom']).' '.sanitize($c['client_nom'])?><br><small style="color:var(--g400)"><?=sanitize($c['client_tel'])?></small></td>
      <td><?=sanitize($c['client_ville'])?></td>
      <td><span style="font-size:12px;color:var(--g600)"><?=sanitize($c['paiement'])?></span></td>
      <td><strong style="color:var(--orange)"><?=formatPrice((int)$c['total'])?></strong></td>
      <td><span class="badge" style="background:<?=$st['bg']?>;color:<?=$st['color']?>"><?=$st['label']?></span></td>
      <td style="color:var(--g400);font-size:12px"><?=date('d/m/Y H:i', strtotime($c['created_at']))?></td>
      <td><a href="commande.php?id=<?=(int)$c['id']?>" class="btn-action"><?=icon('eye', 14)?> Voir</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once 'admin_footer.php'; ?>