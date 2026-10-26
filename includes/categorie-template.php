<?php

require_once __DIR__ . '/config.php';
initUserSession();

if (!isset($cat_slug) || !array_key_exists($cat_slug, CATEGORIES)) {
    header('Location: accueil.php');
    exit;
}

$cat_info   = CATEGORIES[$cat_slug];
$page_title = $cat_info['label'];

$db = getDB();
$stmt = $db->prepare("SELECT * FROM produits WHERE actif=1 AND categorie=? ORDER BY id");
$stmt->execute([$cat_slug]);
$produits = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="page-hero">
  <div class="container">
    <div class="page-hero-stack">
      <span class="section-tag section-tag-soft"><?=sanitize($cat_info['label'])?></span>
      <h1><span><?=sanitize($cat_info['label'])?></span></h1>
      <p><?=sanitize($cat_info['desc'])?> <?=count($produits)?> produit(s) disponible(s).</p>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">

    <div class="products-carousel-wrap">
    <div class="products-grid premium-products-grid" id="productsGrid-<?=$cat_slug?>">
      <?php foreach ($produits as $p): $rupture = (int)$p['stock'] <= 0; ?>
      <div class="product-card product-card-<?=$cat_slug?><?=$rupture ? ' out-of-stock' : ''?>">
        <div class="product-img">
          <?=productVisual($p)?>
          <?php if ($rupture): ?>
          <span class="product-badge badge-outofstock">Rupture</span>
          <?php elseif ($p['badge']): ?>
          <span class="product-badge"><?=sanitize($p['badge'])?></span>
          <?php endif; ?>
          <?php if (!$rupture && $p['stock'] < 5): ?>
          <span class="product-badge product-badge-secondary">Stock limité</span>
          <?php endif; ?>
        </div>
        <div class="product-body">
          <div class="product-category"><?=sanitize($cat_info['label'])?></div>
          <div class="product-name"><?=sanitize($p['nom'])?></div>
          <div class="product-desc"><?=sanitize($p['description'])?></div>
          <?php if ((int)$p['nb_avis'] > 0): ?>
          <div class="product-rating">
            <span class="stars"><?=stars((float)$p['note'])?></span>
            <span><?=number_format((float)$p['note'], 1, ',', ' ')?> (<?=$p['nb_avis']?> avis)</span>
          </div>
          <?php else: ?>
          <div class="product-rating product-rating-empty">
            <span class="stars"><?=starIcons(0)?></span>
            <span>Nouveau</span>
          </div>
          <?php endif; ?>
          <div class="product-footer">
            <div>
              <div class="product-price"><?=formatPrice((int)$p['prix'])?></div>
              <?php if ($p['ancien_prix']): ?>
              <div class="product-old-price"><?=formatPrice((int)$p['ancien_prix'])?></div>
              <?php endif; ?>
            </div>
            <?php if ($rupture): ?>
            <button class="btn-cart" disabled>Indisponible</button>
            <?php else: ?>
            <button class="btn-cart"
              onclick="Cart.add(<?=(int)$p['id']?>,'<?=addslashes($p['nom'])?>',<?=(int)$p['prix']?>,'<?=addslashes($p['emoji'])?>',<?=(int)$p['stock']?>,'<?=addslashes($cat_slug)?>')">
              Ajouter
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (count($produits) > 1): ?>
    <div class="carousel-dots" data-carousel-dots-for="productsGrid-<?=$cat_slug?>">
      <?php foreach ($produits as $i => $p): ?>
      <button type="button" class="carousel-dot<?=$i === 0 ? ' active' : ''?>" aria-label="Produit <?=$i+1?>"></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    </div>

    <?php if (empty($produits)): ?>
    <div class="empty-state">
      <p>Aucun produit dans cette catégorie pour le moment.</p>
      <a href="accueil.php" class="btn-primary" style="margin-top:20px">Retour à l'accueil</a>
    </div>
    <?php endif; ?>

  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>