(function () {
  const baseMeta = document.querySelector('meta[name="app-base"]');
  const appBase = baseMeta ? baseMeta.getAttribute('content').replace(/\/$/, '') : '';
  const apiPath = (path) => `${appBase}${path}`;
  const API = {
    wishlist: apiPath('/api/wishlist.php'),
    stats: apiPath('/api/user-stats.php'),
    cart: apiPath('/api/add_to_cart.php'),
  };

  const Bottle = {
    init() {
      this.ensureStyles();
      this.ensureToastContainer();
      this.cacheBadgeTargets();
      this.bindWishlistButtons();
      this.bindWishlistGrid();
      this.bindCartButtons();
      this.fetchCounts();
    },

    ensureStyles() {
      if (document.getElementById('bottle-ui-styles')) return;
      const style = document.createElement('style');
      style.id = 'bottle-ui-styles';
      style.textContent = `
        .b-count-badge{display:inline-flex;align-items:center;justify-content:center;background:#00bcd4;color:#000;font-size:0.65rem;font-weight:700;border-radius:999px;padding:2px 6px;margin-left:6px;min-width:18px;}
        .b-toast-wrap{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
        .b-toast{background:#111;color:#fff;padding:12px 18px;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,0.4);display:flex;align-items:center;gap:10px;animation:slideIn .3s ease;}
        .b-toast.success{border-left:4px solid #4ade80;}
        .b-toast.error{border-left:4px solid #f87171;}
        @keyframes slideIn{from{opacity:0;transform:translateY(-5px);}to{opacity:1;transform:translateY(0);}}
        .b-btn-loading{opacity:0.6;pointer-events:none;}
        .b-celebration{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:10000;background:linear-gradient(135deg,#00bcd4,#0097a7);color:#fff;padding:16px 32px;border-radius:50px;box-shadow:0 8px 30px rgba(0,188,212,0.4);display:flex;align-items:center;gap:12px;font-weight:600;font-size:1rem;animation:celebrationSlideIn 0.5s ease, celebrationPulse 2s ease infinite;white-space:nowrap;}
        .b-celebration::before{content:'✓';background:#4ade80;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:bold;flex-shrink:0;}
        @keyframes celebrationSlideIn{from{opacity:0;transform:translateX(-50%) translateY(-30px);}to{opacity:1;transform:translateX(-50%) translateY(0);}}
        @keyframes celebrationPulse{0%,100%{box-shadow:0 8px 30px rgba(83, 155, 101, 0.4);}50%{box-shadow:0 8px 40px rgba(0,188,212,0.6);}}
        @keyframes celebrationSlideOut{from{opacity:1;transform:translateX(-50%) translateY(0);}to{opacity:0;transform:translateX(-50%) translateY(-30px);}}
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

    showCelebration(message = 'Item added to cart successfully!') {
      // Remove any existing celebration
      const existing = document.querySelector('.b-celebration');
      if (existing) existing.remove();
      
      const celebration = document.createElement('div');
      celebration.className = 'b-celebration';
      celebration.textContent = message;
      document.body.appendChild(celebration);
      
      // Remove after 3 seconds with fade out
      setTimeout(() => {
        celebration.style.animation = 'celebrationSlideOut 0.3s ease forwards';
        setTimeout(() => celebration.remove(), 300);
      }, 3000);
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
      // Check if button contains icons (don't modify text for icon buttons)
      const hasIcon = el.querySelector('span.material-symbols-outlined, span.material-icons, i.fas, i.fa, .material-icons');
      
      if (isLoading) {
        el.classList.add('b-btn-loading');
        // Only change text for text-only buttons (no icons)
        if (!hasIcon && el.textContent.trim()) {
          el.dataset.originalText = el.textContent.trim();
          el.textContent = 'Please wait...';
        }
      } else {
        el.classList.remove('b-btn-loading');
        // Only restore text if we saved it and button has no icons
        if (el.dataset.originalText && !hasIcon) {
          el.textContent = el.dataset.originalText;
          delete el.dataset.originalText;
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

    bindCartButtons() {
      // Handle buttons with data-cart-add attribute
      document.querySelectorAll('[data-cart-add]').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const productId = parseInt(btn.dataset.cartAdd, 10);
          const quantity = parseInt(btn.dataset.quantity || '1', 10);
          if (!productId) return;
          this.handleCartAdd(productId, quantity, btn);
        });
      });

      // Handle form submissions for Add to Cart
      document.querySelectorAll('form[action*="cart_action.php"]').forEach(form => {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (!submitBtn) return;
        
        form.addEventListener('submit', (e) => {
          e.preventDefault();
          e.stopPropagation();
          
          const productIdInput = form.querySelector('input[name="product_id"]');
          const quantityInput = form.querySelector('input[name="quantity"]');
          
          if (!productIdInput) return;
          
          const productId = parseInt(productIdInput.value, 10);
          const quantity = quantityInput ? parseInt(quantityInput.value || '1', 10) : 1;
          
          if (!productId) return;
          
          this.handleCartAdd(productId, quantity, submitBtn);
        });
      });
    },

    async handleCartAdd(productId, quantity, btn) {
      this.setLoading(btn, true);
      try {
        const res = await fetch(API.cart, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ product_id: productId, quantity: quantity }),
        });
        const json = await res.json();
        if (!json.success) {
          // Check if user is not authenticated
          if (res.status === 401 || json.error === 'UNAUTHENTICATED' || json.message === 'Please log in.') {
            // Show login popup instead of redirecting
            if (typeof showLoginPopup === 'function') {
              showLoginPopup();
            } else if (window.showLoginPopup) {
              window.showLoginPopup();
            } else {
              // Fallback to redirect if popup function not available
              window.location.href = 'login.php';
            }
            return;
          }
          this.toast(json.error || json.message || 'Failed to add to cart', 'error');
        } else {
          // Show celebration message at top-center
          this.showCelebration('Item added to cart successfully!');
          if (json.counts) this.updateBadges(json.counts);
        }
      } catch (err) {
        this.toast('Network error', 'error');
      } finally {
        this.setLoading(btn, false);
      }
    },
  };

  window.Bottle = Bottle;
  document.addEventListener('DOMContentLoaded', () => Bottle.init());
})();

