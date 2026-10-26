<?php require_once 'admin_header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('commandes.php');

$commande = $db->prepare("SELECT * FROM commandes WHERE id=?");
$commande->execute([$id]);
$c = $commande->fetch();
if (!$c) redirect('commandes.php');

$items = $db->prepare("SELECT * FROM commande_items WHERE commande_id=?");
$items->execute([$id]);
$items = $items->fetchAll();

$success_msg = '';

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_statut') {
        $ns = $_POST['statut'];
        if (array_key_exists($ns, ORDER_STATUSES)) {
            $db->prepare("UPDATE commandes SET statut=? WHERE id=?")->execute([$ns, $id]);
            $c['statut'] = $ns;
            $success_msg = 'Statut mis à jour avec succès.';
        }
    }
    if ($action === 'save_note') {
        $note = trim($_POST['note_admin'] ?? '');
        $db->prepare("UPDATE commandes SET note_admin=? WHERE id=?")->execute([$note, $id]);
        $c['note_admin'] = $note;
        $success_msg = 'Note enregistrée.';
    }
}

$st = ORDER_STATUSES[$c['statut']] ?? ['label'=>$c['statut'],'color'=>'#888','bg'=>'#eee'];
?>

<!-- BREADCRUMB -->
<div class="breadcrumb" style="display:flex;align-items:center;gap:10px;margin-bottom:20px;font-size:13px;color:var(--g400)">
  <a href="commandes.php" style="color:var(--orange);display:inline-flex;align-items:center;gap:4px"><?=icon('arrow-left', 14)?> Commandes</a>
  <span>/</span>
  <span style="font-weight:600;color:var(--g700)"><?=sanitize($c['reference'])?></span>
  <span class="badge" style="background:<?=$st['bg']?>;color:<?=$st['color']?>;margin-left:8px"><?=$st['label']?></span>
</div>

<?php if($success_msg): ?>
<div class="alert alert-success"><?=icon('check-circle', 16)?> <?=sanitize($success_msg)?></div>
<?php endif; ?>

<div class="detail-grid">

  <!-- GAUCHE -->
  <div>
    <!-- Articles commandés -->
    <div class="detail-card">
      <h4><?=icon('shopping-cart', 16)?> Articles commandés</h4>
      <table class="data-table" style="margin:0">
        <thead><tr><th></th><th>Produit</th><th>Prix unit.</th><th>Qté</th><th>Sous-total</th></tr></thead>
        <tbody>
        <?php foreach($items as $item): ?>
        <tr>
          <td style="font-size:28px"><?=sanitize($item['emoji'])?></td>
          <td><strong><?=sanitize($item['nom_produit'])?></strong></td>
          <td><?=formatPrice((int)$item['prix_unit'])?></td>
          <td><span style="background:var(--g100);padding:3px 10px;border-radius:100px;font-weight:700"><?=(int)$item['quantite']?></span></td>
          <td><strong style="color:var(--orange)"><?=formatPrice((int)$item['prix_unit'] * (int)$item['quantite'])?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div style="text-align:right;padding:8px 22px 0;font-size:13px;color:var(--g600);border-top:2px solid var(--orange-light)">
        Livraison : <?=((int)$c['frais_livraison'] > 0) ? formatPrice((int)$c['frais_livraison']) : 'Gratuite'?>
      </div>
      <div style="text-align:right;padding:8px 22px 16px;font-family:var(--fh);font-weight:800;font-size:20px;color:var(--orange)">
        Total : <?=formatPrice((int)$c['total'])?>
      </div>
    </div>

    <!-- Informations client -->
    <div class="detail-card">
      <h4><?=icon('user', 16)?> Informations client</h4>
      <div class="detail-row"><span>Nom complet</span><span><?=sanitize($c['client_prenom']).' '.sanitize($c['client_nom'])?></span></div>
      <div class="detail-row"><span>Téléphone</span><span><?=sanitize($c['client_tel'])?></span></div>
      <?php if($c['client_email']): ?>
      <div class="detail-row"><span>Email</span><span><?=sanitize($c['client_email'])?></span></div>
      <?php endif; ?>
      <div class="detail-row"><span>Adresse</span><span><?=sanitize($c['client_adresse'])?></span></div>
      <div class="detail-row"><span>Ville</span><span><?=sanitize($c['client_ville'])?></span></div>
      <div class="detail-row"><span>Mode de paiement</span><span><?=sanitize($c['paiement'])?></span></div>
      <?php if($c['note_client']): ?>
      <div class="detail-row"><span>Note client</span><span style="font-style:italic;color:var(--g500)"><?=sanitize($c['note_client'])?></span></div>
      <?php endif; ?>
      <div style="margin-top:16px;display:flex;gap:10px">
        <a href="tel:<?=sanitize($c['client_tel'])?>" class="topbar-btn btn-sm"><?=icon('phone', 13)?> Appeler</a>
        <a href="https://wa.me/<?=preg_replace('/[^0-9]/','',$c['client_tel'])?>" target="_blank" class="topbar-btn btn-sm" style="background:#25D366"><?=icon('message-circle', 13)?> WhatsApp</a>
        <?php if($c['client_email']): ?>
        <a href="mailto:<?=sanitize($c['client_email'])?>" class="topbar-btn outline btn-sm"><?=icon('mail', 13)?> Email</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Note admin -->
    <div class="detail-card">
      <h4><?=icon('note', 16)?> Note interne (admin)</h4>
      <form method="POST">
        <input type="hidden" name="action" value="save_note">
        <div class="fg">
          <textarea name="note_admin" placeholder="Remarques internes sur cette commande (non visible par le client)..."><?=sanitize($c['note_admin'] ?? '')?></textarea>
        </div>
        <button type="submit" class="topbar-btn"><?=icon('save', 14)?> Enregistrer la note</button>
      </form>
    </div>
  </div>

  <!-- DROITE : gestion statut -->
  <div>
    <div class="detail-card" style="position:sticky;top:90px">
      <h4><?=icon('settings', 16)?> Gérer le statut</h4>

      <!-- Timeline -->
      <?php
      $steps = ['en_attente','confirmee','en_livraison','livree'];
      $cur_idx = array_search($c['statut'], $steps);
      ?>
      <div style="margin-bottom:24px">
        <?php foreach($steps as $i => $step):
          $s = ORDER_STATUSES[$step];
          $done    = $cur_idx !== false && $i <= $cur_idx && $c['statut'] !== 'annulee';
          $current = $c['statut'] === $step;
        ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:<?=$i < count($steps)-1?'0':'0'?>">
          <div style="display:flex;flex-direction:column;align-items:center">
            <div style="width:32px;height:32px;border-radius:50%;background:<?=$done?$s['color']:($current?$s['color']:'var(--g200)')?>;color:<?=$done||$current?'#fff':'var(--g400)'?>;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;font-family:var(--fh)">
              <?=$done&&!$current?icon('check',14):($i+1)?>
            </div>
            <?php if($i < count($steps)-1): ?>
            <div style="width:2px;height:24px;background:<?=$cur_idx!==false&&$i<$cur_idx?$s['color']:'var(--g200)'?>"></div>
            <?php endif; ?>
          </div>
          <div style="padding:8px 0">
            <div style="font-weight:700;font-size:13px;color:<?=$current?$s['color']:'var(--g700)'?>"><?=$s['label']?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if($c['statut'] === 'annulee'): ?>
        <div style="margin-top:8px;padding:10px 14px;background:#fee2e2;border-radius:9px;color:#991b1b;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px">
          <?=icon('x-circle', 15)?> Cette commande a été annulée
        </div>
        <?php endif; ?>
      </div>

      <form method="POST">
        <input type="hidden" name="action" value="update_statut">
        <div class="fg">
          <label>Nouveau statut</label>
          <select name="statut" style="font-weight:700">
            <?php foreach(ORDER_STATUSES as $key => $s): ?>
            <option value="<?=$key?>" <?=$c['statut']===$key?'selected':''?>><?=$s['label']?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="topbar-btn" style="width:100%;justify-content:center"><?=icon('check', 15)?> Mettre à jour</button>
      </form>

      <hr style="border:none;border-top:1px solid var(--g200);margin:20px 0">

      <div class="detail-row"><span>Référence</span><span style="font-family:var(--fh);font-weight:700;color:var(--orange)"><?=sanitize($c['reference'])?></span></div>
      <div class="detail-row"><span>Date commande</span><span><?=date('d/m/Y à H:i', strtotime($c['created_at']))?></span></div>
      <div class="detail-row"><span>Dernière màj</span><span><?=date('d/m/Y à H:i', strtotime($c['updated_at'] ?? $c['created_at']))?></span></div>
      <div class="detail-row"><span>Nb articles</span><span><?=array_sum(array_column($items,'quantite'))?></span></div>
      <div class="detail-row" style="border-top:2px solid var(--orange-light);margin-top:8px;padding-top:12px"><span style="font-weight:700">Total</span><span style="font-family:var(--fh);font-weight:800;font-size:18px;color:var(--orange)"><?=formatPrice((int)$c['total'])?></span></div>

      <div style="margin-top:20px">
        <a href="commandes.php" class="topbar-btn outline btn-sm"><?=icon('arrow-left', 13)?> Retour aux commandes</a>
      </div>
    </div>
  </div>

</div>

<?php require_once 'admin_footer.php'; ?>