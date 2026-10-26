<?php
require_once 'includes/config.php';
initUserSession();

$page_title = 'BTP — Vente de terrains';

function waLink(string $message): string {
    return 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . rawurlencode($message);
}

require_once 'includes/header.php';
?>

<div class="page-hero">
  <div class="container">
    <div class="page-hero-stack">
      <span class="section-tag section-tag-soft"><i class="fa-solid fa-helmet-safety"></i> BTP · Foncier</span>
      <h1><span>Vente de Terrains</span></h1>
      <p>Des terrains sécurisés et bien situés à Abidjan &amp; environs. Contactez-nous sur WhatsApp pour connaître nos disponibilités.</p>
    </div>
  </div>
</div>

<section class="section" style="padding-bottom:0">
  <div class="container">
    <div style="background:linear-gradient(135deg,#25D366,#128C7E);border-radius:var(--rl);padding:32px 28px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;color:#fff;margin-bottom:8px">
      <div>
        <div style="font-family:var(--fh);font-weight:800;font-size:22px;margin-bottom:6px">Un projet d'acquisition de terrain ?</div>
        <div style="font-size:14px;opacity:.95">Discutez directement avec notre équipe BTP sur WhatsApp pour connaître nos terrains disponibles, les prix et les modalités.</div>
      </div>
      <a href="<?=waLink('Bonjour FYTYA-G, je souhaite des informations sur vos terrains à vendre.')?>" target="_blank" rel="noopener"
         style="background:#fff;color:#128C7E;font-family:var(--fh);font-weight:800;padding:14px 28px;border-radius:10px;text-decoration:none;white-space:nowrap;box-shadow:0 8px 20px rgba(0,0,0,.15)">
        Discuter sur WhatsApp
      </a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head" style="text-align:center;margin-bottom:36px">
      <span class="section-tag section-tag-soft">Nos garanties</span>
      <h2 style="font-family:var(--fh);font-weight:800;font-size:30px;margin-top:10px">Pourquoi acheter votre terrain avec nous</h2>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-file"></i></div>
        <div class="feature-title">Documents en règle</div>
        <div class="feature-desc">ACD, titre foncier ou attestation villageoise selon le lot, vérifiés avant toute proposition.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-location-dot"></i></div>
        <div class="feature-title">Emplacements stratégiques</div>
        <div class="feature-desc">Terrains situés à Abidjan et dans les zones périphériques en pleine expansion.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="feature-title">Sécurité juridique</div>
        <div class="feature-desc">Accompagnement dans les démarches d'acquisition pour une transaction en toute sérénité.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-headset"></i></div>
        <div class="feature-title">Suivi personnalisé</div>
        <div class="feature-desc">Un interlocuteur dédié du premier contact jusqu'à la remise des documents.</div>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:var(--o50)">
  <div class="container">
    <div class="section-head" style="text-align:center;margin-bottom:36px">
      <span class="section-tag section-tag-soft">Étapes</span>
      <h2 style="font-family:var(--fh);font-weight:800;font-size:30px;margin-top:10px">Comment se déroule l'acquisition</h2>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">01</div>
        <div class="feature-title">Prise de contact</div>
        <div class="feature-desc">Vous nous contactez sur WhatsApp pour exprimer votre besoin (zone, superficie, budget).</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">02</div>
        <div class="feature-title">Proposition de lots</div>
        <div class="feature-desc">Nous vous présentons les terrains disponibles correspondant à votre demande.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">03</div>
        <div class="feature-title">Visite du terrain</div>
        <div class="feature-desc">Une visite est organisée sur place pour confirmer l'emplacement et les limites du lot.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">04</div>
        <div class="feature-title">Signature &amp; remise des documents</div>
        <div class="feature-desc">Finalisation de la transaction et remise des documents justificatifs du terrain.</div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div style="background:var(--grad-night);border-radius:var(--rxl);padding:48px 32px;text-align:center;color:#fff">
      <div style="font-family:var(--fh);font-weight:800;font-size:26px;margin-bottom:12px">Intéressé par un terrain ?</div>
      <p style="color:rgba(255,255,255,.75);max-width:560px;margin:0 auto 24px;font-size:14px">
        Contactez notre équipe BTP dès maintenant sur WhatsApp pour recevoir la liste des terrains disponibles et leurs prix.
      </p>
      <a href="<?=waLink('Bonjour FYTYA-G, je souhaite des informations sur vos terrains à vendre.')?>" target="_blank" rel="noopener"
         style="background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;font-family:var(--fh);font-weight:800;padding:14px 32px;border-radius:10px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;box-shadow:0 8px 24px rgba(37,211,102,.35)">
        Discuter sur WhatsApp
      </a>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>