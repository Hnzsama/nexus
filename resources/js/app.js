/**
 * Nexus Framework - Main JavaScript Gateway (Entry Point)
 * 
 * Arsitektur Modular Vanilla JavaScript (Tanpa framework frontend berat):
 * - app.css: Stylesheet utama (Tailwind CSS v4 + DaisyUI)
 * - modules/icons.js: Lucide Icons auto-renderer
 * - modules/turbo.js: Nexus Turbo SPA Router (View Transitions & AJAX Form)
 * - modules/hmr.js: Vite Hot Module Replacement (HMR) status listener
 */

import '../css/app.css';
import { initLucideIcons } from './modules/icons.js';
import { initTurboRouter } from './modules/turbo.js';
import { initHmrListener } from './modules/hmr.js';
import { initCsrfTest } from './modules/csrf-test.js';
import { initSwaggerUi } from './modules/swagger.js';

console.log(
  '%c🚀 Nexus Framework %cv1.0.0 (Modular Vanilla JS Gateway)',
  'background: #6366f1; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;',
  'background: #111827; color: #38bdf8; padding: 4px 8px; border-radius: 4px;'
);

/**
 * Bootstrapping seluruh modul saat DOM siap.
 */
function bootApplication() {
  // 1. Inisialisasi ikon Lucide
  initLucideIcons();

  // 2. Inisialisasi Turbo SPA Router & Form Interceptor
  initTurboRouter();

  // 3. Inisialisasi Live Test CSRF Form Module
  initCsrfTest();

  // 4. Inisialisasi Swagger UI jika berada pada halaman dokumentasi API
  initSwaggerUi();

  // 5. Inisialisasi listener Vite HMR (Dev Mode)
  initHmrListener(() => {
    initLucideIcons();
  });
}

// Jalankan bootstrapping
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootApplication);
} else {
  bootApplication();
}

// Re-scan ikon dan re-init interaktivitas setiap kali terjadi perpindahan halaman via SPA Turbo
document.addEventListener('nexus:navigated', (e) => {
  initLucideIcons(e.detail?.container || document);
  initCsrfTest();
  initSwaggerUi();
});

// Re-export modul agar dapat diakses atau diimpor oleh script modular lain
export { initLucideIcons } from './modules/icons.js';
export { initTurboRouter, NexusTurboRouter } from './modules/turbo.js';
export { initHmrListener, updateHmrBadge } from './modules/hmr.js';
export { initCsrfTest } from './modules/csrf-test.js';
export { initSwaggerUi } from './modules/swagger.js';
