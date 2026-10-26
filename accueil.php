<?php
require_once 'includes/config.php';
initUserSession();
applySecurityHeaders();

$current_user = getCurrentUser();

$page_title = 'Accueil';
$db = getDB();

// Compteur de produits actifs par catégorie
$counts = [];
foreach ($db->query("SELECT categorie, COUNT(*) AS n FROM produits WHERE actif=1 GROUP BY categorie") as $row) {
    $counts[$row['categorie']] = (int)$row['n'];
}

require_once 'includes/header.php';
?>
<script>document.documentElement.classList.add('js');</script>

<!-- SPLASH : une seule fois par session, court -->
<div id="splash-screen" aria-hidden="true">
  <img src="logo.png" alt="" class="splash-logo">
  <div class="splash-progress-track"><div class="splash-progress-bar" id="splash-progress-bar"></div></div>
</div>
<style>
#splash-screen{position:fixed;inset:0;background:#fff;z-index:99999;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:22px;transition:opacity .45s var(--ease,ease),visibility .45s}
#splash-screen.hide{opacity:0;visibility:hidden;pointer-events:none}
.splash-logo{width:112px;height:auto;border-radius:16px;object-fit:contain}
.splash-progress-track{width:140px;height:2px;background:#E5E2DC;border-radius:2px;overflow:hidden}
.splash-progress-bar{height:100%;width:0;background:#E85D04;transition:width 1s cubic-bezier(.16,1,.3,1)}
</style>
<script>
(function(){
  var splash=document.getElementById('splash-screen'), bar=document.getElementById('splash-progress-bar');
  var seen=false; try{ seen=sessionStorage.getItem('fytya_splash')==='1'; }catch(e){}
  if(seen || window.matchMedia('(prefers-reduced-motion: reduce)').matches){ splash.remove(); return; }
  try{ sessionStorage.setItem('fytya_splash','1'); }catch(e){}
  requestAnimationFrame(function(){ bar.style.width='100%'; });
  setTimeout(function(){ splash.classList.add('hide'); setTimeout(function(){ splash.remove(); },500); }, 1100);
})();
</script>

<!-- HERO -->
<section class="hero">
  <!-- Particules orange flottantes (décor, positions fixes pour un rendu stable) -->
  <div class="hero-particles" aria-hidden="true">
    <?php
    $seed = [[4,5,16,0],[11,3,22,-7],[18,7,18,-3],[25,4,26,-12],[32,6,20,-9],[39,3,24,-1],[46,8,17,-14],[53,4,23,-5],[60,6,19,-11],[67,3,27,-2],[74,7,21,-8],[81,4,25,-15],[88,6,18,-4],[94,5,22,-10],[8,4,28,-17],[22,6,15,-6],[36,5,26,-13],[57,7,16,-16],[71,4,29,-18],[85,3,20,-19]];
    foreach ($seed as $k => $pt): ?>
    <span style="--x:<?= $pt[0] ?>%;--s:<?= $pt[1] ?>px;--d:<?= $pt[2] ?>s;--dl:<?= $pt[3] ?>s;--dx:<?= ($k % 2 ? 1 : -1) * (18 + ($k * 7) % 46) ?>px"></span>
    <?php endforeach; ?>
  </div>
  <div class="container hero-inner">
    <div class="hero-copy">
      <div class="hero-badge">FYTYA-G, depuis 2015 à Treichville</div>
      <?php if (!isset($_SESSION['user'])): ?>
      <p class="hero-welcome">Vous naviguez en <strong>invité</strong> : ajoutez vos produits au panier, la connexion n'est demandée qu'au moment du paiement.</p>
      <?php endif; ?>
      <h1 class="hero-title">
        Approvisionnez-vous en
        <span class="rotor" aria-label="épices, œufs frais, poulet frais et matériaux BTP">
          <span class="rotor-track" aria-hidden="true">
            <span>épices</span><span>œufs frais</span><span>poulet frais</span><span>matériaux BTP</span><span>épices</span>
          </span>
        </span>
      </h1>
      <p class="hero-desc">Entreprise familiale d'Abidjan, FYTYA-G distribue des épices, des œufs et de la volaille, et fournit aussi le BTP. Commandez en ligne, payez par mobile money, on livre.</p>
      <div class="hero-cta">
        <a href="#familles" class="btn-primary"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i> Voir les produits</a>
        <a href="https://wa.me/2250789060689" class="btn-outline" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> Commander sur WhatsApp</a>
      </div>
      <p class="hero-pay"><i class="fa-solid fa-lock" aria-hidden="true"></i> Paiement sécurisé par Wave, Orange Money, MTN Money et Moov Money</p>
    </div>

    <div class="hero-visual">
      <div class="hero-facts">
        <div class="hero-fact"><div class="hero-fact-num">4</div><div class="hero-fact-label">familles de produits</div></div>
        <div class="hero-fact"><div class="hero-fact-num">1000+</div><div class="hero-fact-label">clients servis</div></div>
        <div class="hero-fact"><div class="hero-fact-num">24h</div><div class="hero-fact-label">livraison à Abidjan</div></div>
      </div>
    </div>
  </div>
</section>

<!-- FAMILLES -->
<section class="section" id="familles">
  <div class="container">
    <div class="section-header reveal">
      <div class="section-tag">Nos activités</div>
      <h2 class="section-title">Quatre familles, <span>une même exigence</span></h2>
      <p class="section-sub">Choisissez une famille pour voir les produits disponibles et leurs prix.</p>
    </div>
    <div class="fam-grid">
      <?php $fi = 0; foreach (CATEGORIES as $slug => $cat): ?>
      <a href="<?= sanitize($cat['page']) ?>" class="fam-card reveal" style="--i:<?= $fi++ ?>">
        <span class="fam-icon"><i class="fa-solid fa-<?= sanitize($cat['icon']) ?>" aria-hidden="true"></i></span>
        <span class="fam-go" aria-hidden="true"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
        <span class="fam-body">
          <span class="fam-count"><?= $counts[$slug] ?? 0 ?> produit(s)</span>
          <strong class="fam-title"><?= sanitize($cat['label']) ?></strong>
          <span class="fam-desc"><?= sanitize($cat['desc']) ?></span>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- COMMENT ÇA MARCHE -->
<section class="section steps-section">
  <div class="container">
    <div class="section-header reveal">
      <div class="section-tag">Simple et rapide</div>
      <h2 class="section-title">Commander en <span>3 étapes</span></h2>
    </div>
    <div class="steps">
      <div class="step reveal" style="--i:0">
        <div class="step-num">01</div>
        <div class="step-icon"><i class="fa-solid fa-basket-shopping" aria-hidden="true"></i></div>
        <h3>Choisissez vos produits</h3>
        <p>Parcourez les familles, ajoutez au panier. Pas besoin de compte pour commencer.</p>
      </div>
      <div class="step reveal" style="--i:1">
        <div class="step-num">02</div>
        <div class="step-icon"><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i></div>
        <h3>Payez par mobile money</h3>
        <p>Wave, Orange Money, MTN Money ou Moov Money, en toute sécurité.</p>
      </div>
      <div class="step reveal" style="--i:2">
        <div class="step-num">03</div>
        <div class="step-icon"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i></div>
        <h3>Recevez sous 24h</h3>
        <p>Livraison à Abidjan en 24h, 48 à 72h en région. Gratuite pour les œufs et le poulet à Abidjan.</p>
      </div>
    </div>
  </div>
</section>

<!-- APPEL À L'ACTION -->
<section class="cta-section">
  <div class="container">
    <div class="cta-band reveal">
      <div>
        <h2>Une grosse commande ou un chantier&nbsp;?</h2>
        <p>Parlez-nous de vos quantités : nous vous répondons rapidement avec un prix adapté.</p>
      </div>
      <div class="cta-actions">
        <a href="https://wa.me/2250789060689" class="btn-primary" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> Écrire sur WhatsApp</a>
        <a href="tel:+2250789060689" class="btn-outline"><i class="fa-solid fa-phone" aria-hidden="true"></i> Appeler</a>
      </div>
    </div>
  </div>
</section>

<script>
(function(){
  // Apparition au défilement
  var els = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){ if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: .12, rootMargin: '0px 0px -40px 0px' });
    els.forEach(function(el){ io.observe(el); });
  } else { els.forEach(function(el){ el.classList.add('in'); }); }
})();
</script>

<?php require_once 'includes/footer.php'; ?>