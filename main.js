// ── PANIER ──────────────────────────────────────
const Cart = {
  key: 'fytya_cart',
  get()  { return JSON.parse(localStorage.getItem(this.key) || '[]'); },
  save(c){ localStorage.setItem(this.key, JSON.stringify(c)); },

  add(id, name, price, emoji, stock, categorie) {
    stock = (stock === undefined || stock === null) ? Infinity : parseInt(stock);
    const cart = this.get();
    const ex = cart.find(i => i.id === id);
    if (ex) {
      if (ex.qty >= stock) {
        showToast('Stock maximum atteint pour ' + name);
        return;
      }
      ex.qty++;
      ex.stock = stock;
    } else {
      if (stock <= 0) {
        showToast(name + ' est en rupture de stock');
        return;
      }
      cart.push({ id, name, price, emoji, qty: 1, stock, categorie });
    }
    this.save(cart);
    this.updateCount();
    showToast(name + ' ajouté au panier !');
  },

  remove(id) {
    this.save(this.get().filter(i => i.id !== id));
    this.updateCount();
    if (typeof renderCart === 'function') renderCart();
  },

  updateQty(id, qty) {
    qty = parseInt(qty);
    if (qty <= 0) return this.remove(id);
    const cart = this.get();
    const item = cart.find(i => i.id === id);
    if (item) {
      const max = item.stock ?? Infinity;
      if (qty > max) {
        qty = max;
        showToast('Seulement ' + max + ' en stock pour ' + item.name);
      }
      item.qty = qty;
      this.save(cart);
    }
    if (typeof renderCart === 'function') renderCart();
  },

  total() { return this.get().reduce((s, i) => s + i.price * i.qty, 0); },
  count() { return this.get().reduce((s, i) => s + i.qty, 0); },

  // Frais de livraison : gratuite pour œufs & poulet, 500 FCFA si des épices sont dans le panier
  deliveryFee() {
    const cart = this.get();
    if (cart.length === 0) return 0;
    const hasEpices = cart.some(i => i.categorie === 'epices');
    return hasEpices ? 500 : 0;
  },
  grandTotal() { return this.total() + this.deliveryFee(); },

  updateCount() {
    document.querySelectorAll('.cart-count').forEach(el => {
      el.textContent = this.count();
    });
  },

  clear() { this.save([]); this.updateCount(); }
};

// ── TOAST ─────────────────────────────────────
function showToast(msg, duration = 3000) {
  let t = document.getElementById('fytya-toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'fytya-toast';
    t.className = 'toast';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), duration);
}

// ── FILTRES ───────────────────────────────────
function filterProducts(cat) {
  document.querySelectorAll('.cat-pill, .sidebar-link[data-cat]').forEach(el => {
    el.classList.toggle('active', el.dataset.cat === cat);
  });
  document.querySelectorAll('.product-card[data-cat]').forEach(card => {
    card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
  });
}

// ── RENDU PANIER ──────────────────────────────
function renderCart() {
  const tbody   = document.getElementById('cart-tbody');
  const emptyEl = document.getElementById('cart-empty');
  const boxEl   = document.getElementById('cart-box');
  if (!tbody) return;

  const cart = Cart.get();

  if (cart.length === 0) {
    if (emptyEl) emptyEl.style.display = '';
    if (boxEl)   boxEl.style.display   = 'none';
    return;
  }
  if (emptyEl) emptyEl.style.display = 'none';
  if (boxEl)   boxEl.style.display   = '';

  tbody.innerHTML = cart.map(item => {
    const label = (item.name || 'P').trim().charAt(0).toUpperCase() || 'P';
    return `
    <tr>
      <td data-label="Produit"><span class="mini-thumb">${label}</span></td>
      <td data-label="Article"><strong>${item.name}</strong><br><small style="color:#aaa">${fmt(item.price)} / unité</small></td>
      <td data-label="Quantité">
        <input type="number" min="1" ${Number.isFinite(item.stock) ? `max="${item.stock}"` : ''} value="${item.qty}"
          style="width:64px;padding:5px 8px;border:1.5px solid #eee;border-radius:8px;font-size:14px;text-align:center"
          onchange="Cart.updateQty(${item.id}, this.value)">
      </td>
      <td data-label="Total" style="color:#FF6600;font-weight:700;font-family:'Syne',sans-serif">${fmt(item.price * item.qty)}</td>
      <td data-label="Action">
        <button onclick="Cart.remove(${item.id})"
          style="background:none;border:1.5px solid #eee;border-radius:7px;width:32px;height:32px;cursor:pointer;font-size:15px;color:#aaa;transition:all .2s"
          onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'"
          onmouseout="this.style.borderColor='#eee';this.style.color='#aaa'">×</button>
      </td>
    </tr>
  `;
  }).join('');

  // Mise à jour totaux
  const total = Cart.total();
  const fee   = Cart.deliveryFee();
  document.querySelectorAll('.cart-total-display').forEach(el => el.textContent = fmt(total));
  document.querySelectorAll('.cart-delivery-display').forEach(el => {
    el.textContent = fee > 0 ? fmt(fee) : 'Gratuite';
    el.style.color = fee > 0 ? '#FF6600' : 'green';
  });
  document.querySelectorAll('.cart-grandtotal-display').forEach(el => el.textContent = fmt(total + fee));
}

// ── CHECKOUT : injecter le panier dans le form ──
function prepareCheckout() {
  const hidden = document.getElementById('cart-data');
  if (hidden) hidden.value = JSON.stringify(Cart.get());
}

function fmt(n) {
  return new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
}

// ── INIT ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.mobile-menu-toggle');
  const panel = document.querySelector('.mobile-menu-panel');
  const overlay = document.querySelector('.mobile-menu-overlay');
  const closeBtn = document.querySelector('.mobile-menu-close');

  function closeMobileMenu() {
    panel?.classList.remove('open');
    overlay?.classList.remove('show');
    toggle?.classList.remove('active');
    toggle?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('menu-open');
  }

  function openMobileMenu() {
    panel?.classList.add('open');
    overlay?.classList.add('show');
    toggle?.classList.add('active');
    toggle?.setAttribute('aria-expanded', 'true');
    document.body.classList.add('menu-open');
  }

  toggle?.addEventListener('click', () => {
    const isOpen = panel?.classList.contains('open');
    if (isOpen) closeMobileMenu(); else openMobileMenu();
  });

  closeBtn?.addEventListener('click', closeMobileMenu);
  overlay?.addEventListener('click', closeMobileMenu);
  document.querySelectorAll('.mobile-link, .mobile-menu-actions a').forEach(item => {
    item.addEventListener('click', closeMobileMenu);
  });

  Cart.updateCount();
  renderCart();
  initProductCarousels();
});

// Carrousel produits (mobile) : synchronise les points avec le scroll
// et permet de cliquer sur un point pour aller au produit correspondant.
function initProductCarousels() {
  document.querySelectorAll('.carousel-dots[data-carousel-dots-for]').forEach(dotsEl => {
    const grid = document.getElementById(dotsEl.dataset.carouselDotsFor);
    if (!grid) return;
    const cards = Array.from(grid.querySelectorAll('.product-card'));
    const dots = Array.from(dotsEl.querySelectorAll('.carousel-dot'));
    if (!cards.length || !dots.length) return;

    dots.forEach((dot, i) => {
      dot.addEventListener('click', () => {
        cards[i].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
      });
    });

    let ticking = false;
    grid.addEventListener('scroll', () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        const gridRect = grid.getBoundingClientRect();
        const gridCenter = gridRect.left + gridRect.width / 2;
        let closest = 0;
        let closestDist = Infinity;
        cards.forEach((card, i) => {
          const cardRect = card.getBoundingClientRect();
          const cardCenter = cardRect.left + cardRect.width / 2;
          const dist = Math.abs(cardCenter - gridCenter);
          if (dist < closestDist) { closestDist = dist; closest = i; }
        });
        dots.forEach((dot, i) => dot.classList.toggle('active', i === closest));
        ticking = false;
      });
    }, { passive: true });
  });
}