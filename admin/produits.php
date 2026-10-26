<?php require_once 'admin_header.php';

$msg  = '';
$type = 'success';

// ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $msg = 'Session invalide. Rechargez la page et réessayez.';
        $type = 'error';
    } else {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $pid   = (int)$_POST['id'];
        $actif = (int)$_POST['actif'];
        $db->prepare("UPDATE produits SET actif=? WHERE id=?")->execute([$actif, $pid]);
        $msg = $actif ? 'Produit activé.' : 'Produit désactivé.';
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM produits WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = 'Produit supprimé.';
    }
    if ($action === 'add' || $action === 'edit') {
        $nom    = trim($_POST['nom']    ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $prix   = (int)($_POST['prix']  ?? 0);
        $ancien = $_POST['ancien_prix'] !== '' ? (int)$_POST['ancien_prix'] : null;
        $cat    = $_POST['categorie']   ?? array_key_first(CATEGORIES);
        $emoji  = trim($_POST['emoji']  ?? '📦');
        $badge  = trim($_POST['badge']  ?? '') ?: null;
        $note   = 0.0;
        $avis   = 0;
        $stock  = (int)($_POST['stock'] ?? 0);

        // Photo produit : on garde l'ancienne image sauf si un nouveau fichier est envoyé
        $image = $_POST['image_actuelle'] ?? null;
        if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (isset($allowed[$ext]) && $_FILES['image_file']['size'] <= 5*1024*1024) {
                $uploadDirAbs = __DIR__ . '/../' . UPLOAD_DIR;
                if (!is_dir($uploadDirAbs)) { mkdir($uploadDirAbs, 0775, true); }
                $filename = 'produit_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDirAbs . $filename)) {
                    $image = UPLOAD_DIR . $filename;
                }
            } else {
                $msg = 'Photo invalide (formats acceptés : jpg, png, webp — 5 Mo max).'; $type = 'error';
            }
        }

        if ($nom && $prix > 0 && array_key_exists($cat, CATEGORIES)) {
            if ($action === 'add') {
                $db->prepare("INSERT INTO produits (nom,description,prix,ancien_prix,categorie,emoji,image,badge,note,nb_avis,stock,actif) VALUES (?,?,?,?,?,?,?,?,?,?,?,1)")
                   ->execute([$nom,$desc,$prix,$ancien,$cat,$emoji,$image,$badge,$note,$avis,$stock]);
                $msg = "Produit «$nom» ajouté avec succès dans la catégorie « ".CATEGORIES[$cat]['label']." ».";
            } else {
                $pid = (int)$_POST['id'];
                $db->prepare("UPDATE produits SET nom=?,description=?,prix=?,ancien_prix=?,categorie=?,emoji=?,image=?,badge=?,note=?,nb_avis=?,stock=? WHERE id=?")
                   ->execute([$nom,$desc,$prix,$ancien,$cat,$emoji,$image,$badge,$note,$avis,$stock,$pid]);
                $msg = "Produit «$nom» mis à jour.";
            }
        } elseif ($type !== 'error') {
            $msg = 'Champs obligatoires manquants (nom, prix, catégorie).'; $type = 'error';
        }
    }
    }
}

$edit_product = null;
if (isset($_GET['edit'])) {
    $ep = $db->prepare("SELECT * FROM produits WHERE id=?");
    $ep->execute([(int)$_GET['edit']]);
    $edit_product = $ep->fetch();
}
$show_form = isset($_GET['add']) || $edit_product;

$produits = $db->query("SELECT * FROM produits ORDER BY actif DESC, id DESC")->fetchAll();
?>

<?php if($msg): ?>
<div class="alert alert-<?=$type==='success'?'success':'error'?>"><?=icon($type==='success'?'check-circle':'x-circle', 16)?> <?=sanitize($msg)?></div>
<?php endif; ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:20px">
  <a href="produits.php?add=1" class="topbar-btn"><?=icon('plus', 14)?> Ajouter un produit</a>
</div>

<!-- FORMULAIRE AJOUT / ÉDITION -->
<?php if($show_form): ?>
<div class="form-section">
  <h3><?=$edit_product ? icon('edit-2',15).' Modifier : '.sanitize($edit_product['nom']) : icon('plus-circle',15).' Nouveau produit'?></h3>
  <form method="POST" action="produits.php" enctype="multipart/form-data">
    <?=csrfField()?>
    <input type="hidden" name="action" value="<?=$edit_product?'edit':'add'?>">
    <?php if($edit_product): ?>
    <input type="hidden" name="id" value="<?=(int)$edit_product['id']?>">
    <input type="hidden" name="image_actuelle" value="<?=sanitize($edit_product['image'] ?? '')?>">
    <?php endif; ?>

    <div class="form-grid-2">
      <div class="fg"><label>Nom du produit *</label>
        <input type="text" name="nom" required value="<?=sanitize($edit_product['nom'] ?? '')?>" placeholder="Ex: Poivre de Selim">
      </div>
      <div class="fg"><label>Emoji (repli si pas de photo)</label>
        <input type="text" name="emoji" value="<?=sanitize($edit_product['emoji'] ?? '📦')?>" maxlength="5">
      </div>
    </div>
    <div class="fg">
      <label>Photo du produit</label>
      <?php if(!empty($edit_product['image'])): ?>
      <div style="margin-bottom:8px">
        <img src="../<?=sanitize($edit_product['image'])?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1.5px solid var(--g200)">
      </div>
      <?php endif; ?>
      <input type="file" name="image_file" accept="image/png,image/jpeg,image/webp">
      <small style="color:var(--g500)">JPG, PNG ou WEBP — 5 Mo max. Laisser vide pour conserver la photo actuelle.</small>
    </div>
    <div class="fg"><label>Description</label>
      <textarea name="description" placeholder="Description du produit..."><?=sanitize($edit_product['description'] ?? '')?></textarea>
    </div>
    <div class="form-grid-3">
      <div class="fg"><label>Prix (FCFA) *</label>
        <input type="number" name="prix" required min="0" value="<?=(int)($edit_product['prix'] ?? 0)?>">
      </div>
      <div class="fg"><label>Ancien prix (optionnel)</label>
        <input type="number" name="ancien_prix" min="0" value="<?=$edit_product['ancien_prix'] ?? ''?>" placeholder="Laisser vide si pas de promo">
      </div>
      <div class="fg"><label>Stock</label>
        <input type="number" name="stock" min="0" value="<?=(int)($edit_product['stock'] ?? 0)?>">
      </div>
    </div>
    <div class="form-grid-2">
      <div class="fg"><label>Catégorie (famille de produits) *</label>
        <select name="categorie" required>
          <?php foreach(CATEGORIES as $slug => $cat): ?>
          <option value="<?=$slug?>" <?=($edit_product['categorie'] ?? '') === $slug ? 'selected' : ''?>><?=$cat['icon']?> <?=$cat['label']?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg"><label>Badge</label>
        <input type="text" name="badge" value="<?=sanitize($edit_product['badge'] ?? '')?>" placeholder="Ex: Promo, Nouveau...">
      </div>
    </div>
    <div style="display:flex;gap:12px;margin-top:8px">
      <button type="submit" class="topbar-btn"><?=$edit_product?icon('save',14).' Enregistrer':icon('plus',14).' Ajouter'?></button>
      <a href="produits.php" class="topbar-btn outline">Annuler</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- LISTE PRODUITS -->
<div class="table-card">
  <div class="table-header">
    <span class="table-title"><?=icon('shopping-bag', 16)?> Tous les produits (<?=count($produits)?>)</span>
  </div>
  <table class="data-table">
    <thead><tr>
      <th></th><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Ancien prix</th><th>Stock</th><th>Note</th><th>Statut</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach($produits as $p): ?>
    <tr style="<?=!$p['actif']?'opacity:.5':''?>">
      <td>
        <?php if(!empty($p['image'])): ?>
        <img src="../<?=sanitize($p['image'])?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px">
        <?php else: ?>
        <span style="font-size:28px"><?=sanitize($p['emoji'])?></span>
        <?php endif; ?>
      </td>
      <td>
        <strong><?=sanitize($p['nom'])?></strong>
        <?php if($p['badge']): ?><br><span class="badge" style="background:var(--orange-light);color:var(--orange);font-size:10px"><?=sanitize($p['badge'])?></span><?php endif; ?>
      </td>
      <td><?=CATEGORIES[$p['categorie']]['icon'] ?? ''?> <?=sanitize(CATEGORIES[$p['categorie']]['label'] ?? $p['categorie'])?></td>
      <td><strong style="color:var(--orange)"><?=formatPrice((int)$p['prix'])?></strong></td>
      <td><?=$p['ancien_prix']?'<s style="color:var(--g400)">'.formatPrice((int)$p['ancien_prix']).'</s>':'—'?></td>
      <td>
        <span style="font-weight:700;color:<?=$p['stock']<5?'#ef4444':($p['stock']<20?'#F59E0B':'#10b981')?>"><?=(int)$p['stock']?></span>
        <?php if($p['stock']<5): ?><span style="color:#ef4444;display:inline-flex;vertical-align:-2px;margin-left:2px"><?=icon('alert-triangle', 12)?></span><?php endif; ?>
      </td>
      <td><?=$p['note']?> <span style="color:#F59E0B;display:inline-flex;vertical-align:-2px"><?=icon('star', 13)?></span></td>
      <td>
        <span class="badge" style="background:<?=$p['actif']?'#D1FAE5':'#F3F4F6'?>;color:<?=$p['actif']?'#065F46':'var(--g500)'?>">
          <?=$p['actif']?'Actif':'Inactif'?>
        </span>
      </td>
      <td>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <a href="produits.php?edit=<?=(int)$p['id']?>" class="btn-action"><?=icon('edit-2', 13)?> Éditer</a>
          <form method="POST" style="display:inline">
            <?=csrfField()?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?=(int)$p['id']?>">
            <input type="hidden" name="actif" value="<?=$p['actif']?0:1?>">
            <button type="submit" class="btn-action" style="color:<?=$p['actif']?'#ef4444':'#10b981'?>"><?=$p['actif']?icon('toggle-off',14).' Désactiver':icon('toggle-on',14).' Activer'?></button>
          </form>
          <form method="POST" style="display:inline">
            <?=csrfField()?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?=(int)$p['id']?>">
            <button type="submit" class="btn-action danger"
              data-confirm="Supprimer le produit «<?=sanitize($p['nom'])?>» ? Irréversible."><?=icon('trash-2', 14)?></button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once 'admin_footer.php'; ?>