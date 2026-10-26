<?php require_once 'admin_header.php';

// Filtres
$statut  = $_GET['statut']  ?? 'all';
$search  = trim($_GET['q']  ?? '');
$page    = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

// Mise à jour statut via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_statut') {
        $cid = (int)$_POST['commande_id'];
        $ns  = $_POST['new_statut'];
        if (array_key_exists($ns, ORDER_STATUSES)) {
            $db->prepare("UPDATE commandes SET statut=? WHERE id=?")->execute([$ns, $cid]);
        }
        redirect("commandes.php?statut=$statut&q=".urlencode($search)."&p=$page");
    }
    if ($_POST['action'] === 'delete') {
        $db->prepare("DELETE FROM commandes WHERE id=?")->execute([(int)$_POST['commande_id']]);
        redirect("commandes.php");
    }
}

// Requête
$where = ['1=1'];
$params = [];
if ($statut !== 'all' && array_key_exists($statut, ORDER_STATUSES)) {
    $where[] = 'statut = ?'; $params[] = $statut;
}
if ($search) {
    $where[] = '(reference LIKE ? OR client_nom LIKE ? OR client_prenom LIKE ? OR client_tel LIKE ?)';
    $s = "%$search%";
    array_push($params, $s, $s, $s, $s);
}
$whereSQL = implode(' AND ', $where);
$total_rows = $db->prepare("SELECT COUNT(*) FROM commandes WHERE $whereSQL");
$total_rows->execute($params);
$total_rows = (int)$total_rows->fetchColumn();
$total_pages = max(1, ceil($total_rows / $perPage));

$stmt = $db->prepare("SELECT * FROM commandes WHERE $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$commandes = $stmt->fetchAll();
?>

<!-- EXPORT PDF -->
<div class="table-card" style="margin-bottom:20px">
  <div class="table-header">
    <span class="table-title"><?=icon('receipt', 16)?> Exporter en PDF</span>
  </div>
  <form method="GET" action="export-commandes.php" target="_blank" style="padding:18px 20px;display:flex;flex-wrap:wrap;gap:14px;align-items:end">
    <div>
      <label style="display:block;font-size:12px;font-weight:700;color:var(--g600);margin-bottom:5px">Statut</label>
      <select name="statut" style="padding:8px 10px;border:1.5px solid var(--g200);border-radius:8px;font-size:13px">
        <option value="all">Tous les statuts</option>
        <?php foreach(ORDER_STATUSES as $key => $st): ?>
        <option value="<?=$key?>"><?=$st['label']?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label style="display:block;font-size:12px;font-weight:700;color:var(--g600);margin-bottom:5px">Catégorie</label>
      <select name="categorie" style="padding:8px 10px;border:1.5px solid var(--g200);border-radius:8px;font-size:13px">
        <option value="all">Toutes les catégories</option>
        <?php foreach(CATEGORIES as $slug => $cat): ?>
        <option value="<?=$slug?>"><?=$cat['icon']?> <?=sanitize($cat['label'])?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label style="display:block;font-size:12px;font-weight:700;color:var(--g600);margin-bottom:5px">Du</label>
      <input type="date" name="date_debut" style="padding:8px 10px;border:1.5px solid var(--g200);border-radius:8px;font-size:13px">
    </div>
    <div>
      <label style="display:block;font-size:12px;font-weight:700;color:var(--g600);margin-bottom:5px">Au</label>
      <input type="date" name="date_fin" style="padding:8px 10px;border:1.5px solid var(--g200);border-radius:8px;font-size:13px">
    </div>
    <div>
      <label style="display:block;font-size:12px;font-weight:700;color:var(--g600);margin-bottom:5px">Trier par</label>
      <select name="tri" style="padding:8px 10px;border:1.5px solid var(--g200);border-radius:8px;font-size:13px">
        <option value="date">Date (plus récent)</option>
        <option value="statut">Statut</option>
        <option value="montant">Montant</option>
      </select>
    </div>
    <button type="submit" class="topbar-btn btn-sm"><?=icon('download', 14)?> Exporter la sélection</button>
    <a href="export-commandes.php" target="_blank" class="topbar-btn outline btn-sm"><?=icon('file-text', 14)?> Tout exporter</a>
  </form>
</div>

<!-- FILTRES PAR STATUT -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
  <a href="commandes.php" class="badge" style="padding:8px 16px;cursor:pointer;font-size:13px;background:<?=$statut==='all'?'var(--orange)':'var(--g100)'?>;color:<?=$statut==='all'?'#fff':'var(--g600)'?>">Toutes (<?=$total_rows?>)</a>
  <?php foreach(ORDER_STATUSES as $key => $st):
    $stmt_n = $db->prepare("SELECT COUNT(*) FROM commandes WHERE statut=?");
    $stmt_n->execute([$key]);
    $n = $stmt_n->fetchColumn();
  ?>
  <a href="commandes.php?statut=<?=$key?>" class="badge" style="padding:8px 16px;cursor:pointer;font-size:13px;background:<?=$statut===$key?$st['color']:'var(--g100)'?>;color:<?=$statut===$key?'#fff':$st['color']?>">
    <?=$st['label']?> (<?=$n?>)
  </a>
  <?php endforeach; ?>
</div>

<div class="table-card">
  <div class="table-header">
    <span class="table-title"><?=icon('package', 16)?> Commandes</span>
    <form method="GET" style="display:flex;align-items:center;gap:8px">
      <?php if($statut!=='all'): ?><input type="hidden" name="statut" value="<?=sanitize($statut)?>"><?php endif; ?>
      <div class="table-search">
        <span style="display:flex;color:var(--g400)"><?=icon('search', 15)?></span>
        <input type="text" name="q" placeholder="Ref, nom, téléphone..." value="<?=sanitize($search)?>">
      </div>
      <button type="submit" class="topbar-btn btn-sm">Chercher</button>
      <?php if($search): ?><a href="commandes.php" class="topbar-btn outline btn-sm"><?=icon('x', 13)?> Effacer</a><?php endif; ?>
    </form>
  </div>

  <?php if(empty($commandes)): ?>
  <div style="text-align:center;padding:60px;color:var(--g400)">
    <div style="margin-bottom:16px;display:flex;justify-content:center"><?=icon('inbox', 44)?></div>
    <p>Aucune commande trouvée.</p>
  </div>
  <?php else: ?>

  <table class="data-table">
    <thead><tr>
      <th>Référence</th><th>Client</th><th>Ville</th><th>Paiement</th>
      <th>Total</th><th>Statut</th><th>Date</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach($commandes as $c):
      $st = ORDER_STATUSES[$c['statut']] ?? ['label'=>$c['statut'],'color'=>'#888','bg'=>'#eee'];
    ?>
    <tr>
      <td><strong style="color:var(--orange);font-family:var(--fh)"><?=sanitize($c['reference'])?></strong></td>
      <td>
        <strong><?=sanitize($c['client_prenom']).' '.sanitize($c['client_nom'])?></strong>
        <br><small style="color:var(--g400)"><?=sanitize($c['client_tel'])?></small>
        <?php if($c['client_email']): ?>
        <br><small style="color:var(--g400)"><?=sanitize($c['client_email'])?></small>
        <?php endif; ?>
      </td>
      <td><?=sanitize($c['client_ville'])?></td>
      <td><small style="color:var(--g600)"><?=sanitize($c['paiement'])?></small></td>
      <td><strong style="color:var(--orange)"><?=formatPrice((int)$c['total'])?></strong></td>
      <td>
        <!-- Changement rapide de statut -->
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="update_statut">
          <input type="hidden" name="commande_id" value="<?=(int)$c['id']?>">
          <select name="new_statut" onchange="this.form.submit()"
            style="border:none;border-radius:100px;padding:4px 10px;font-size:11px;font-weight:700;font-family:var(--fh);cursor:pointer;background:<?=$st['bg']?>;color:<?=$st['color']?>">
            <?php foreach(ORDER_STATUSES as $key => $s): ?>
            <option value="<?=$key?>" <?=$c['statut']===$key?'selected':''?> style="background:#fff;color:var(--g800)"><?=$s['label']?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </td>
      <td style="color:var(--g400);font-size:12px;white-space:nowrap">
        <?=date('d/m/Y', strtotime($c['created_at']))?>
        <br><?=date('H:i', strtotime($c['created_at']))?>
      </td>
      <td>
        <div style="display:flex;gap:6px;align-items:center">
          <a href="commande.php?id=<?=(int)$c['id']?>" class="btn-action"><?=icon('eye', 14)?> Voir</a>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="commande_id" value="<?=(int)$c['id']?>">
            <button type="submit" class="btn-action danger"
              data-confirm="Supprimer la commande <?=sanitize($c['reference'])?> ? Cette action est irréversible.">
              <?=icon('trash-2', 14)?>
            </button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <!-- PAGINATION -->
  <?php if($total_pages > 1): ?>
  <div class="pagination">
    <?php for($i=1; $i<=$total_pages; $i++): ?>
    <a href="?statut=<?=$statut?>&q=<?=urlencode($search)?>&p=<?=$i?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once 'admin_footer.php'; ?>