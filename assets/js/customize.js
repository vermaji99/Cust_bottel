(function () {
  const cfg = window.BOTTEL_CUSTOMIZER || {};
  const base = (cfg.base || '').replace(/\/+$/, '');
  const endpoints = {
    save: `${base}/api/save_design.php`,
    design: `${base}/api/design.php`,
  };

  const saveBtn = document.getElementById('saveBtn');
  const reeditBtn = document.getElementById('reeditBtn');
  const statusEl = document.getElementById('designStatus');
  const formatEl = document.getElementById('exportFormat');
  const canvas = document.getElementById('mainCanvas');
  const state = {
    lastDesignKey: localStorage.getItem('bottleLastDesign') || (cfg.prefill ? cfg.prefill.design_key : null),
  };

  function toast(message, type = 'success') {
    if (window.Bottle && typeof window.Bottle.toast === 'function') {
      window.Bottle.toast(message, type);
    } else {
      alert(message);
    }
  }

  function setStatus(message, type = 'info') {
    if (statusEl) {
      statusEl.style.color = type === 'error' ? '#ff6b6b' : '#9aa';
      statusEl.textContent = message;
    }
  }

  function updateReeditState() {
    if (reeditBtn) {
      reeditBtn.disabled = !state.lastDesignKey;
      reeditBtn.title = state.lastDesignKey ? 'Load your last saved design' : 'Save a design first';
    }
  }

  async function saveDesign() {
    if (!window.BottleCustomizer || !canvas) return;
    try {
      saveBtn.disabled = true;
      saveBtn.innerText = 'Saving...';
      window.BottleCustomizer.render?.();
      const format = (formatEl?.value || 'png').toLowerCase();
      const mime = format === 'jpg' ? 'image/jpeg' : 'image/png';
      const imageData = canvas.toDataURL(mime, 0.92);
      const meta = window.BottleCustomizer.exportMeta();
      const res = await fetch(endpoints.save, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ image: imageData, format, meta }),
      });
      const json = await res.json();
      if (!json.success) {
        throw new Error(json.message || json.error || 'Save failed');
      }
      const design = json.design || {};
      state.lastDesignKey = design.design_key || state.lastDesignKey;
      if (state.lastDesignKey) {
        localStorage.setItem('bottleLastDesign', state.lastDesignKey);
      }
      updateReeditState();
      toast('Design saved successfully!');
      setStatus(`Saved design #${design.design_key || design.design_id || ''}`);
    } catch (error) {
      setStatus(error.message, 'error');
      toast(error.message, 'error');
    } finally {
      if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.innerText = 'Save';
      }
    }
  }

  async function loadDesignByKey(key) {
    if (!key) {
      toast('No design to load yet.', 'error');
      return;
    }
    try {
      setStatus('Loading saved design...');
      const res = await fetch(`${endpoints.design}?id=${encodeURIComponent(key)}`, { credentials: 'same-origin' });
      const json = await res.json();
      if (!json.success || !json.design) {
        throw new Error(json.message || 'Design not found');
      }
      const meta = json.design.meta_json;
      window.BottleCustomizer.importMeta(meta);
      toast('Design loaded. Happy editing!');
      setStatus(`Loaded design #${json.design.design_key || json.design.id}`);
      state.lastDesignKey = json.design.design_key;
      updateReeditState();
    } catch (error) {
      setStatus(error.message, 'error');
      toast(error.message, 'error');
    }
  }

  function initPrefill() {
    if (cfg.prefill && cfg.prefill.meta) {
      window.BottleCustomizer.importMeta(cfg.prefill.meta);
      setStatus(`Loaded draft #${cfg.prefill.design_key}`);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    initPrefill();
    updateReeditState();
    if (saveBtn) saveBtn.addEventListener('click', saveDesign);
    if (reeditBtn) reeditBtn.addEventListener('click', () => loadDesignByKey(state.lastDesignKey));
    setStatus('Live preview ready. Save to keep your layers.');
  });
})();





