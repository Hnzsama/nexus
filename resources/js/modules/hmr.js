/**
 * Nexus Framework - HMR & Dev Status Module
 * Menangani pendeteksian Vite Hot Module Replacement (HMR) dan status runtime
 */

export function initHmrListener(onReloadCallback = null) {
  // Update indikator status di navbar
  updateHmrBadge();

  if (import.meta.hot) {
    import.meta.hot.accept(() => {
      console.log('[Nexus Vite HMR] Assets updated dynamically.');
      if (typeof onReloadCallback === 'function') {
        onReloadCallback();
      }
    });
  }
}

export function updateHmrBadge() {
  const hmrIndicator = document.getElementById('hmr-status');
  if (hmrIndicator) {
    hmrIndicator.textContent = 'Active (Turbo + HMR)';
    hmrIndicator.classList.add('badge-active');
  }
}
