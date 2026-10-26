<?php
require_once 'includes/config.php';
initUserSession();
$page_title = 'Contact';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $msg = 'Session invalide. Rechargez la page et réessayez.';
    } else {
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tel = trim($_POST['tel'] ?? '');
        $sujet = trim($_POST['sujet'] ?? '');
        $message = trim($_POST['message'] ?? '');

        $text = "Bonjour FYTYA-G,%0A%0A";
        $text .= "Prénom : " . rawurlencode($prenom) . "%0A";
        $text .= "Nom : " . rawurlencode($nom) . "%0A";
        $text .= "Email : " . rawurlencode($email) . "%0A";
        $text .= "Téléphone : " . rawurlencode($tel ?: 'Non renseigné') . "%0A";
        $text .= "Sujet : " . rawurlencode($sujet) . "%0A";
        $text .= "Message : %0A" . rawurlencode($message) . "%0A%0A";
        $text .= "Merci pour votre réponse.";

        header('Location: https://wa.me/' . WHATSAPP_NUMBER . '?text=' . $text);
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="page-hero">
  <div class="container">
    <div class="page-hero-stack">
      <span class="section-tag section-tag-soft">Contact</span>
      <h1><span>Contactez-nous</span></h1>
      <p>Notre équipe est disponible du lundi au samedi, de 8h à 19h.</p>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="contact-grid">
      <div class="checkout-box">
        <h2 style="font-family:var(--fh);font-weight:800;font-size:26px;margin-bottom:8px">Envoyez-nous un message</h2>
        <p style="color:var(--g600);margin-bottom:28px">Réponse garantie sous 24h ouvrées.</p>
        <form method="POST">
          <?=csrfField()?>
          <div class="field-grid-2">
            <div class="form-group"><label>Prénom *</label><input type="text" name="prenom" required placeholder="Konan"></div>
            <div class="form-group"><label>Nom *</label><input type="text" name="nom" required placeholder="Diallo"></div>
          </div>
          <div class="form-group"><label>Email *</label><input type="email" name="email" required placeholder="vous@exemple.ci"></div>
          <div class="form-group"><label>Téléphone</label><input type="tel" name="tel" placeholder="+225 07 89 06 06 89"></div>
          <div class="form-group"><label>Sujet *</label>
            <select name="sujet" required>
              <option value="">Choisissez</option>
              <option>Renseignement épice</option>
              <option>Passer une commande</option>
              <option>Suivi de commande</option>
              <option>Retour / SAV</option>
              <option>Autre</option>
            </select>
          </div>
          <div class="form-group"><label>Message *</label><textarea name="message" required placeholder="Votre message..."></textarea></div>
          <button type="submit" class="btn-primary" style="width:100%;padding:14px;font-size:15px">Envoyer</button>
        </form>
      </div>

      <div class="checkout-box">
        <h2 style="font-family:var(--fh);font-weight:800;font-size:26px;margin-bottom:28px">Nos coordonnées</h2>
        <?php foreach([
          ['Adresse', nl2br(COMPANY_ADDRESS)],
          ['Téléphone', '<a href="tel:+2250789060689" style="color:var(--o600);font-weight:600">' . COMPANY_PHONE_1 . '</a>'],
          ['WhatsApp', '<a href="https://wa.me/2250789060689" target="_blank" rel="noopener" style="color:var(--o600);font-weight:600">' . COMPANY_PHONE_1 . '</a>'],
          ['Email', '<a href="mailto:' . COMPANY_EMAIL . '" style="color:var(--o600);font-weight:600">' . COMPANY_EMAIL . '</a>'],
          ['Horaires', COMPANY_HOURS],
        ] as [$label, $val]): ?>
        <div class="contact-info-item">
          <div class="contact-info-bullet">•</div>
          <div>
            <div style="font-family:var(--fh);font-weight:700;font-size:14px;margin-bottom:2px"><?=$label?></div>
            <div style="font-size:14px;color:var(--g600)"><?=$val?></div>
          </div>
        </div>
        <?php endforeach; ?>

        <div style="background:linear-gradient(135deg, #ff8c3a 0%, #ff6600 100%);border-radius:var(--rl);padding:24px;margin-top:28px;color:#fff">
          <div style="font-family:var(--fh);font-weight:800;font-size:18px;margin-bottom:10px">Paiements acceptés</div>
          <div style="display:flex;flex-wrap:wrap;gap:8px">
            <?php foreach(['Wave','Orange Money','MTN Money','Moov Money','Djamo','À la livraison'] as $m): ?>
            <span style="background:rgba(255,255,255,.18);padding:5px 12px;border-radius:100px;font-size:12px;font-weight:600"><?=$m?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
