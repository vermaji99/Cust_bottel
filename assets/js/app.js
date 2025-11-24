(function () {
  const baseMeta = document.querySelector('meta[name="app-base"]');
  const appBase = baseMeta ? baseMeta.getAttribute('content').replace(/\/$/, '') : '';
  const apiPath = (path) => `${appBase}${path}`;
  const API = {
    wishlist: apiPath('/api/wishlist.php'),
    stats: apiPath('/api/user-stats.php'),
  };

  const Bottel = {
    init() {
      this.ensureStyles();
      this.ensureToastContainer();
      this.cacheBadgeTargets();
      this.bindWishlistButtons();
      this.bindWishlistGrid();
      this.fetchCounts();
    },

    ensureStyles() {
      if (document.getElementById('bottel-ui-styles')) return;
      const style = document.createElement('style');
      style.id = 'bottel-ui-styles';
      style.textContent = `
        .b-count-badge{display:inline-flex;align-items:center;justify-content:center;background:#00bcd4;color:#000;font-size:0.65rem;font-weight:700;border-radius:999px;padding:2px 6px;margin-left:6px;min-width:18px;}
        .b-toast-wrap{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
        .b-toast{background:#111;color:#fff;padding:12px 18px;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,0.4);display:flex;align-items:center;gap:10px;animation:slideIn .3s ease;}
        .b-toast.success{border-left:4px solid #4ade80;}
        .b-toast.error{border-left:4px solid #f87171;}
        @keyframes slideIn{from{opacity:0;transform:translateY(-5px);}to{opacity:1;transform:translateY(0);}}
        .b-btn-loading{opacity:0.6;pointer-events:none;}
      `;
      document.head.appendChild(style);
    },

    ensureToastContainer() {
      if (!document.querySelector('.b-toast-wrap')) {
        const wrap = document.createElement('div');
        wrap.className = 'b-toast-wrap';
        document.body.appendChild(wrap);
      }
    },

    toast(message, type = 'success') {
      const wrap = document.querySelector('.b-toast-wrap');
      if (!wrap) return;
      const toast = document.createElement('div');
      toast.className = `b-toast ${type}`;
      toast.textContent = message;
      wrap.appendChild(toast);
      setTimeout(() => toast.remove(), 4000);
    },

    cacheBadgeTargets() {
      const anchors = document.querySelectorAll('a[href*="wishlist.php"], a[href*="cart.php"]');
      anchors.forEach(anchor => {
        const href = anchor.getAttribute('href') || '';
        let type = null;
        if (href.includes('wishlist')) type = 'wishlist';
        if (href.includes('cart')) type = 'cart';
        if (!type) return;
        anchor.dataset.badge = type;
        if (!anchor.querySelector('.b-count-badge')) {
          const badge = document.createElement('span');
          badge.className = 'b-count-badge';
          badge.textContent = '0';
          anchor.appendChild(badge);
        }
      });
    },

    updateBadges(counts = {}) {
      document.querySelectorAll('[data-badge="wishlist"] .b-count-badge')
        .forEach(el => el.textContent = counts.wishlist ?? '0');
      document.querySelectorAll('[data-badge="cart"] .b-count-badge')
        .forEach(el => el.textContent = counts.cart ?? '0');
    },

    setLoading(el, isLoading) {
      if (!el) return;
      if (isLoading) {
        el.classList.add('b-btn-loading');
        el.dataset.originalText = el.textContent;
        el.textContent = 'Please wait...';
      } else {
        el.classList.remove('b-btn-loading');
        if (el.dataset.originalText) {
          el.textContent = el.dataset.originalText;
        }
      }
    },

    async fetchCounts() {
      try {
        const res = await fetch(API.stats, { credentials: 'same-origin' });
        const json = await res.json();
        if (json.success) {
          this.updateBadges(json.counts);
        }
      } catch (err) {
        console.warn('Failed to load counts', err);
      }
    },

    bindWishlistButtons() {
      document.querySelectorAll('[data-wishlist-add]').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const productId = parseInt(btn.dataset.wishlistAdd, 10);
          if (!productId) return;
          this.handleWishlistAction('add', { product_id: productId }, btn);
        });
      });
    },

    bindWishlistGrid() {
      document.querySelectorAll('[data-wishlist-remove]').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const productId = parseInt(btn.dataset.wishlistRemove, 10);
          if (!productId) return;
          this.handleWishlistAction('remove', { product_id: productId }, btn, () => {
            const card = btn.closest('.product-card');
            if (card) card.remove();
          });
        });

        const moveBtn = btn.parentElement?.querySelector('[data-move-to-cart]');
        if (moveBtn) {
          moveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = parseInt(moveBtn.dataset.moveToCart, 10);
            this.handleWishlistAction('move_to_cart', { product_id: productId }, moveBtn, () => {
              const card = moveBtn.closest('.product-card');
              if (card) card.remove();
            });
          });
        }
      });
    },

    async handleWishlistAction(action, payload, btn, onSuccess) {
      this.setLoading(btn, true);
      try {
        const res = await fetch(API.wishlist, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ action, ...payload }),
        });
        const json = await res.json();
        if (!json.success) {
          this.toast(json.message || json.error || 'Action failed', 'error');
        } else {
          this.toast('Updated wishlist', 'success');
          if (json.counts) this.updateBadges(json.counts);
          if (typeof onSuccess === 'function') onSuccess(json);
        }
      } catch (err) {
        this.toast('Network error', 'error');
      } finally {
        this.setLoading(btn, false);
      }
    },
  };

  window.Bottel = Bottel;
  document.addEventListener('DOMContentLoaded', () => Bottel.init());
})();

