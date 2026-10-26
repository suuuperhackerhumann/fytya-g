<?php
require_once 'includes/config.php';
initUserSession();

$page_title = 'Mon Panier';

$success = false;
$ref = '';
$current_user = getCurrentUser();

// Mode invité : bloquer la validation de commande → rediriger vers connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data']) && !isset($_SESSION['user'])) {
    header('Location: index.php?redirect=panier.php&msg=login_required');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $stock_error = 'Session invalide. Rechargez la page et réessayez.';
    } else {
    $db      = getDB();

    // Sécurité : si l'utilisateur en session n'existe plus en base
    // (ex: base réinitialisée), on le déconnecte et on redemande la connexion
    // au lieu de planter sur la contrainte de clé étrangère.
    if ($current_user) {
        $chk = $db->prepare("SELECT id FROM users WHERE id=?");
        $chk->execute([$current_user['id']]);
        if (!$chk->fetch()) {
            unset($_SESSION['user']);
            header('Location: index.php?redirect=panier.php&msg=login_required');
            exit;
        }
    }

    $items   = json_decode($_POST['cart_data'], true);
    $nom     = trim($_POST['nom']     ?? '');
    $prenom  = trim($_POST['prenom']  ?? '');
    $email   = trim($_POST['email']   ?? '');
    $tel     = trim($_POST['tel']     ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville   = trim($_POST['ville']   ?? 'Abidjan');
    $paiement= trim($_POST['paiement']?? 'A la livraison');
    $note    = trim($_POST['note']    ?? '');

    $stock_error = '';
    if ($nom && $prenom && $tel && $adresse && !empty($items)) {
        // Vérifier la disponibilité du stock avant toute écriture en base
        $stock_check = $db->prepare("SELECT nom, stock FROM produits WHERE id=? FOR UPDATE");
        $db->beginTransaction();
        foreach ($items as $it) {
            $stock_check->execute([(int)$it['id']]);
            $p = $stock_check->fetch();
            if (!$p || (int)$p['stock'] < (int)$it['qty']) {
                $stock_error = 'Stock insuffisant pour "' . ($p['nom'] ?? $it['name']) . '" (disponible : ' . ($p['stock'] ?? 0) . ').';
                break;
            }
        }

        if ($stock_error) {
            $db->rollBack();
        } else {
            $sous_total = array_reduce($items, fn($s,$i) => $s + ($i['price'] * $i['qty']), 0);
            $frais      = computeDeliveryFee($items);
            $total      = $sous_total + $frais;
            $ref        = genRef();

            $stmt = $db->prepare("INSERT INTO commandes
                (user_id,reference,client_nom,client_prenom,client_email,client_tel,client_adresse,client_ville,paiement,total,frais_livraison,note_client)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $current_user ? $current_user['id'] : null,
                $ref,$nom,$prenom,$email,$tel,$adresse,$ville,$paiement,$total,$frais,$note
            ]);
            $cid = $db->lastInsertId();

            $si = $db->prepare("INSERT INTO commande_items (commande_id,produit_id,nom_produit,emoji,prix_unit,quantite) VALUES (?,?,?,?,?,?)");
            foreach ($items as $it) {
                $si->execute([$cid, (int)$it['id'], $it['name'], $it['emoji'], (int)$it['price'], (int)$it['qty']]);
                // Décrémenter le stock (uniquement si suffisant, protégé par la vérification ci-dessus)
                $db->prepare("UPDATE produits SET stock=stock-? WHERE id=? AND stock>=?")
                   ->execute([(int)$it['qty'], (int)$it['id'], (int)$it['qty']]);
            }
            $db->commit();
            $success = true;
            if (in_array($paiement, PAYDUNYA_METHODS, true)) {
                header('Location: payer.php?ref=' . urlencode($ref));
            } else {
                header('Location: mes-commandes.php?just_ordered=' . urlencode($ref));
            }
            exit;
        }
    }
    }
}

// Message si redirigé depuis panier après tentative commande invité
$login_msg = isset($_GET['msg']) && $_GET['msg'] === 'login_required';

require_once 'includes/header.php';
?>

<div class="page-hero">
  <div class="container">
    <div class="page-hero-stack">
      <span class="section-tag section-tag-soft">Panier</span>
      <h1><span>Mon panier</span></h1>
      <p>Vérifiez votre commande avant de valider</p>
    </div>
  </div>
</div>

<?php if (!isset($_SESSION['user'])): ?>
<div class="cart-guest-banner">
  <div class="container cart-guest-inner">
    <div class="cart-guest-copy">
      <strong>Mode invité</strong> — Pour passer une commande, vous devez être connecté.
    </div>
    <div class="cart-guest-actions">
      <a href="index.php?redirect=panier.php" class="btn-primary btn-sm">Se connecter</a>
      <a href="register.php" class="btn-outline btn-sm">Créer un compte</a>
    </div>
  </div>
</div>
<?php endif; ?>

<section class="section">
  <div class="container">

    <?php if (!empty($stock_error)): ?>
    <div style="background:#FEE2E2;border:1.5px solid #FCA5A5;color:#991B1B;padding:14px 18px;border-radius:10px;margin-bottom:24px;font-size:14px;font-weight:600;display:flex;align-items:flex-start;gap:10px">
      <span style="flex-shrink:0;margin-top:1px"><?=icon('alert-triangle', 18)?></span>
      <span><?=sanitize($stock_error)?><br>
      <span style="font-weight:400">Merci d'ajuster la quantité dans votre panier avant de valider à nouveau.</span></span>
    </div>
    <?php endif; ?>

    <!-- PANIER VIDE -->
    <div id="cart-empty" class="empty-state cart-empty-state">
      <div class="empty-state-icon">◌</div>
      <h2 style="font-family:var(--fh);font-weight:700;margin-bottom:12px">Votre panier est vide</h2>
      <p style="color:var(--g600);margin-bottom:28px">Ajoutez des produits depuis notre boutique.</p>
      <a href="accueil.php" class="btn-primary" style="font-size:15px;padding:14px 36px">Voir nos produits</a>
    </div>

    <!-- CONTENU PANIER + CHECKOUT -->
    <div id="cart-box" style="display:none">
      <div class="checkout-grid">

        <!-- GAUCHE : panier + livraison -->
        <div>
          <div class="checkout-box" style="margin-bottom:28px">
            <h3>Articles</h3>
            <table class="cart-table">
              <thead><tr>
                <th></th><th>Produit</th><th>Qté</th><th>Total</th><th></th>
              </tr></thead>
              <tbody id="cart-tbody"></tbody>
            </table>
            <div style="margin-top:16px;text-align:right;font-family:var(--fh);font-weight:800;font-size:18px;color:var(--orange)">
              Sous-total : <span class="cart-total-display">0 FCFA</span>
            </div>
          </div>

          <div class="checkout-box">
            <h3>Informations de livraison</h3>
            <?php if (!$current_user): ?>
            <p style="background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF;padding:12px;border-radius:8px;margin-bottom:16px;font-size:13px">
              <a href="index.php" style="color:#1E40AF;font-weight:600">Connectez-vous</a> pour utiliser vos informations sauvegardées
            </p>
            <?php endif; ?>
            <form method="POST" action="panier.php" onsubmit="prepareCheckout()">
              <input type="hidden" name="cart_data" id="cart-data">
              <?=csrfField()?>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group"><label>Prénom *</label><input type="text" name="prenom" placeholder="Konan" value="<?=$current_user ? sanitize($current_user['prenom']) : ''?>" required></div>
                <div class="form-group"><label>Nom *</label><input type="text" name="nom" placeholder="Diallo" value="<?=$current_user ? sanitize($current_user['nom']) : ''?>" required></div>
              </div>
              <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="vous@mail.com" value="<?=$current_user ? sanitize($current_user['email']) : ''?>"></div>
              <div class="form-group"><label>Téléphone (WhatsApp) *</label><input type="tel" name="tel" placeholder="+225 07 89 06 06 89" required></div>
              <div class="form-group"><label>Adresse de livraison *</label><input type="text" name="adresse" placeholder="Quartier, rue, description..." required></div>
              <div class="form-group"><label>Ville *</label>
                <select name="ville">
                  <?php foreach(['Abidjan','Bingerville','Bouaké','Daloa','San-Pédro','Yamoussoukro','Korhogo','Man','Autre'] as $v): ?>
                  <option><?=$v?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="form-group"><label>Mode de paiement *</label>
                <select name="paiement">
                  <option>A la livraison</option>
                  <option>Wave</option>
                  <option>Orange Money</option>
                  <option>MTN Money</option>
                  <option>Moov Money</option>
                  <option>Djamo</option>
                </select>
              </div>
              <div class="form-group"><label>Note (optionnel)</label><textarea name="note" placeholder="Instructions spéciales pour la livraison..."></textarea></div>
              <button type="submit" class="btn-primary" style="width:100%;font-size:15px;padding:14px">
                Valider la commande
              </button>
            </form>
          </div>
        </div>

        <!-- DROITE : récap -->
        <div>
          <div class="checkout-box" style="position:sticky;top:90px">
            <h3>Récapitulatif</h3>
            <div id="recap-items" style="margin-bottom:16px"></div>
            <div class="order-summary-row"><span style="color:var(--g600)">Sous-total</span><span class="cart-total-display" style="font-weight:700">0 FCFA</span></div>
            <div class="order-summary-row"><span style="color:var(--g600)">Livraison</span><span class="cart-delivery-display" style="color:green;font-weight:600">Gratuite</span></div>
            <div style="font-size:12px;color:var(--g600);margin:-4px 0 8px">Œufs &amp; poulet : livraison gratuite · Épices : 500 FCFA</div>
            <div class="order-total-row"><span>Total</span><span class="cart-grandtotal-display" style="color:var(--orange)">0 FCFA</span></div>
            <div style="background:var(--orange-light);border-radius:10px;padding:14px;font-size:13px;color:var(--g600);margin-top:8px">
              Paiement sécurisé · Garantie 1 an · Retour 7 jours
            </div>
            <div style="margin-top:16px">
              <a href="accueil.php" style="color:var(--orange);font-size:13px;font-weight:600">Continuer les achats</a>
            </div>
          </div>
        </div>

      </div>
    </div><!-- /cart-box -->

  </div>
</section>

<script>
// Enrichir renderCart pour le récap
const _baseRender = renderCart;
renderCart = function() {
  _baseRender();
  const recap = document.getElementById('recap-items');
  if (!recap) return;
  const cart = Cart.get();
  recap.innerHTML = cart.map(i => `
    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--g200);font-size:13px">
      <span>${i.emoji} ${i.name} × ${i.qty}</span>
      <span style="font-weight:700;color:var(--orange)">${new Intl.NumberFormat('fr-FR').format(i.price*i.qty)} FCFA</span>
    </div>
  `).join('');
};
</script>

<?php require_once 'includes/footer.php'; ?>