</div><!-- /admin-content -->
</div><!-- /main -->
</div><!-- /admin-layout -->
<script>
// Menu mobile/tablette : ouverture / fermeture de la sidebar.
// Délégation d'événements sur document (plutôt que des listeners
// attachés uniquement aux boutons) : plus robuste si une icône SVG
// interne reçoit le clic, si le DOM est modifié dynamiquement, ou si
// ce script est chargé/relancé plusieurs fois.
(function() {
  const MOBILE_BREAKPOINT = 1024; // doit correspondre au breakpoint CSS de admin.css

  function els() {
    return {
      sidebar:  document.getElementById('adminSidebar'),
      overlay:  document.getElementById('sidebarOverlay'),
      openBtn:  document.getElementById('sidebarToggle'),
      closeBtn: document.getElementById('sidebarClose')
    };
  }

  function openSidebar() {
    const { sidebar, overlay, openBtn } = els();
    if (!sidebar || !overlay) return;
    sidebar.classList.add('open');
    overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
    if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
  }

  function closeSidebar() {
    const { sidebar, overlay, openBtn } = els();
    if (!sidebar || !overlay) return;
    sidebar.classList.remove('open');
    overlay.classList.remove('visible');
    document.body.style.overflow = '';
    if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
  }

  // Un seul listener sur le document, actif même si les boutons
  // n'existaient pas encore au moment de l'exécution du script.
  document.addEventListener('click', function(e) {
    if (e.target.closest('#sidebarToggle')) { openSidebar(); return; }
    if (e.target.closest('#sidebarClose'))  { closeSidebar(); return; }
    if (e.target.closest('#sidebarOverlay')) { closeSidebar(); return; }
    // Ferme automatiquement le menu après avoir cliqué sur un lien (mobile)
    const navLink = e.target.closest('#adminSidebar .nav-item');
    if (navLink) closeSidebar();
  });

  // Fermeture au clavier (accessibilité)
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
  });

  // Si on repasse en desktop, on s'assure que l'état mobile est réinitialisé
  let resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
      if (window.innerWidth > MOBILE_BREAKPOINT) closeSidebar();
    }, 100);
  });
})();
</script>
<script>
// Confirmation suppression
document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', e => {
        if (!confirm(btn.dataset.confirm)) e.preventDefault();
    });
});
</script>


<style>
@keyframes fyt-toast-in { from { transform: translateY(20px); opacity:0 } to { transform: translateY(0); opacity:1 } }
</style>
<script>
(function() {
  const POLL_INTERVAL = 15000; // 15 secondes
  const originalTitle = document.title;
  let titleFlashTimer = null;
  let lastKnownId = window.__ADMIN_LAST_ORDER_ID || 0;

  // Petit bip généré à la volée (aucun fichier audio externe requis)
  function beep() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      [880, 1108].forEach((freq, i) => {
        setTimeout(() => {
          const o = ctx.createOscillator();
          const g = ctx.createGain();
          o.connect(g); g.connect(ctx.destination);
          o.type = 'sine';
          o.frequency.setValueAtTime(freq, ctx.currentTime);
          g.gain.setValueAtTime(0.15, ctx.currentTime);
          g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
          o.start();
          o.stop(ctx.currentTime + 0.4);
        }, i * 180);
      });
    } catch (e) { /* audio indisponible (autoplay bloqué...) : on ignore */ }
  }

  function flashTitle(text) {
    if (titleFlashTimer) return;
    let on = false;
    titleFlashTimer = setInterval(() => {
      document.title = on ? originalTitle : text;
      on = !on;
    }, 1200);
  }
  function stopFlashTitle() {
    if (titleFlashTimer) { clearInterval(titleFlashTimer); titleFlashTimer = null; }
    document.title = originalTitle;
  }
  document.addEventListener('visibilitychange', () => { if (!document.hidden) stopFlashTitle(); });
  window.addEventListener('focus', stopFlashTitle);

  function updateBadges(nbAttente) {
    const sidebarBadge = document.getElementById('sidebar-order-badge');
    const topbarAlert  = document.getElementById('topbar-order-alert');
    const topbarCount  = document.getElementById('topbar-order-count');
    if (sidebarBadge) {
      sidebarBadge.textContent = nbAttente;
      sidebarBadge.style.display = nbAttente > 0 ? '' : 'none';
    }
    if (topbarAlert && topbarCount) {
      topbarCount.textContent = nbAttente;
      topbarAlert.style.display = nbAttente > 0 ? 'flex' : 'none';
    }
  }

  function showToast(ref) {
    const existing = document.getElementById('new-order-toast');
    if (existing) existing.remove();

    // Sur le dashboard et la liste des commandes, on force l'actualisation
    // (avec possibilité d'annuler) puisque c'est l'écran que l'admin surveille.
    const isListPage = ['index', 'commandes'].includes(window.__ADMIN_CURRENT_PAGE);
    let countdown = 5;

    const toast = document.createElement('div');
    toast.id = 'new-order-toast';
    toast.style.cssText = 'position:fixed;bottom:24px;right:24px;left:auto;z-index:9999;background:#1a1a1a;color:#fff;padding:16px 20px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.3);font-family:var(--fh, sans-serif);width:min(320px, calc(100vw - 32px));max-width:320px;display:flex;flex-direction:column;gap:10px;animation:fyt-toast-in .3s ease';
    const bellSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>';
    const chevronSvg = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><polyline points="9 18 15 12 9 6"></polyline></svg>';
    toast.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px">
        <span style="color:#FF9950;flex-shrink:0">${bellSvg}</span>
        <div style="flex:1">
          <strong style="font-size:13px">Nouvelle commande reçue</strong><br>
          <span style="font-size:12px;color:#FF9950;font-family:monospace">${ref || ''}</span>
        </div>
      </div>
      ${isListPage
        ? `<div style="font-size:12px;color:#bbb">Actualisation dans <span id="toast-countdown">${countdown}</span>s… <a href="#" id="toast-cancel" style="color:#FF9950;font-weight:700;text-decoration:underline">Annuler</a></div>`
        : `<a href="commandes.php?statut=en_attente" style="font-size:12px;color:#FF9950;font-weight:700;text-decoration:underline">Voir la commande ${chevronSvg}</a>`}
    `;
    document.body.appendChild(toast);

    if (isListPage) {
      let cancelled = false;
      document.getElementById('toast-cancel').addEventListener('click', (e) => {
        e.preventDefault();
        cancelled = true;
        toast.remove();
      });
      const countdownSpan = document.getElementById('toast-countdown');
      const timer = setInterval(() => {
        if (cancelled) { clearInterval(timer); return; }
        countdown--;
        if (countdownSpan) countdownSpan.textContent = countdown;
        if (countdown <= 0) {
          clearInterval(timer);
          if (!cancelled) window.location.reload();
        }
      }, 1000);
    } else {
      setTimeout(() => { if (toast.parentNode) toast.remove(); }, 8000);
    }
  }

  function poll() {
    fetch('ajax-check-orders.php', { credentials: 'same-origin' })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(data => {
        updateBadges(data.nb_attente);
        if (data.last_id > lastKnownId) {
          lastKnownId = data.last_id;
          window.__ADMIN_LAST_ORDER_ID = lastKnownId;
          beep();
          flashTitle('(!) Nouvelle commande');
          showToast(data.last_ref);
        }
      })
      .catch(() => { /* silencieux : on retentera au prochain intervalle */ });
  }

  setInterval(poll, POLL_INTERVAL);
})();
</script>
</body>
</html>