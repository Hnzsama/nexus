/**
 * Nexus Framework - Icons Module (Lucide Icons)
 * Menangani render otomatis icon berbasis atribut data-lucide
 */

import { createIcons, icons } from 'lucide';

/**
 * Inisialisasi ikon Lucide pada elemen dengan atribut data-lucide.
 *
 * @param {Element|Document} root Elemen root untuk pencarian icon (default: document)
 */
export function initLucideIcons(root = document) {
  try {
    createIcons({
      icons,
      nameAttr: 'data-lucide',
      attrs: {
        'stroke-width': 1.8,
      },
      root: root instanceof Element ? root : document,
    });
  } catch (err) {
    console.warn('[Nexus Icons] Gagal merender ikon Lucide:', err);
  }
}

// Expose ke global window agar mudah diakses oleh inline script atau AJAX kustom
if (typeof window !== 'undefined') {
  window.lucide = { createIcons, icons };
  window.initLucideIcons = initLucideIcons;
}
