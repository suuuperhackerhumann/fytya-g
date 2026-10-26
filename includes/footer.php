<footer class="footer">
  <div class="container">

    <div class="ft-top">
      <div class="ft-brand">
        <a href="accueil.php" class="ft-logo" aria-label="FYTYA-G, retour à l'accueil">
          <img src="logo.png" alt="FYTYA-G">
        </a>
        <p class="ft-tag"><?=COMPANY_TAGLINE?> Livrés partout en Côte d’Ivoire.</p>
        <div class="ft-socials">
          <a href="https://wa.me/2250789060689" aria-label="WhatsApp" title="WhatsApp" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
          <a href="https://www.facebook.com/share/1HVMLgpk4W/?mibextid=wwXIfr" aria-label="Facebook" title="Facebook" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
          <a href="https://www.tiktok.com/@etsfytyag?_r=1&_t=ZS-98qc6ecAkmV" aria-label="TikTok" title="TikTok" target="_blank" rel="noopener"><i class="fa-brands fa-tiktok" aria-hidden="true"></i></a>
        </div>
      </div>

      <nav class="ft-col" aria-label="Navigation du pied de page">
        <h4>Navigation</h4>
        <a href="accueil.php">Accueil</a>
        <a href="panier.php">Mon panier</a>
        <a href="mes-commandes.php">Mes commandes</a>
        <a href="contact.php">Contact</a>
      </nav>

      <div class="ft-col">
        <h4>Nos familles</h4>
        <?php foreach (CATEGORIES as $slug => $cat): ?>
        <a href="<?=$cat['page']?>"><?=sanitize($cat['label'])?></a>
        <?php endforeach; ?>
      </div>

      <div class="ft-col ft-contact">
        <h4>Nous joindre</h4>
        <ul>
          <li><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span><?=COMPANY_ADDRESS?></span></li>
          <li><i class="fa-solid fa-phone" aria-hidden="true"></i><a href="tel:+2250789060689"><?=COMPANY_PHONE_1?></a></li>
          <li><i class="fa-solid fa-phone" aria-hidden="true"></i><a href="tel:+2250143953737"><?=COMPANY_PHONE_2?></a></li>
          <li><i class="fa-solid fa-envelope" aria-hidden="true"></i><a href="mailto:<?=COMPANY_EMAIL?>"><?=COMPANY_EMAIL?></a></li>
          <li><i class="fa-regular fa-clock" aria-hidden="true"></i><span><?=COMPANY_HOURS?></span></li>
        </ul>
      </div>
    </div>

    <div class="ft-pay">
      <span class="ft-pay-label"><i class="fa-solid fa-lock" aria-hidden="true"></i> Paiement et livraison</span>
      <div class="ft-badges">
        <?php foreach(['Wave','Orange Money','MTN Money','Moov Money','Djamo','Livraison'] as $m): ?>
        <span class="ft-badge"><?=$m?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="ft-bottom">
    <div class="container ft-bottom-in">
      <span>&copy; <?=date('Y')?> <?=COMPANY?>. Tous droits réservés.</span>
      <span class="ft-promise">Qualité contrôlée, livraison rapide, fraîcheur garantie</span>
      <span class="ft-credit">Made by <a href="https://cd-solutions.freedev.app/?i=2#accueil" target="_blank" rel="noopener">CDSolutions</a></span>
      <a href="#" class="ft-up" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">Haut de page <i class="fa-solid fa-arrow-up" aria-hidden="true"></i></a>
    </div>
  </div>
</footer>

<script src="main.js"></script>
</body>
</html>