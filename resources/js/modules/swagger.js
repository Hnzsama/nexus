/**
 * Nexus Framework - Swagger UI & Scalar API Explorer Module
 * 
 * Modul Vanilla JS modular untuk merender dokumentasi API interaktif
 * dengan dukungan dual-engine (Swagger UI & Scalar Modern Studio).
 */

import { initLucideIcons } from './icons.js';

let swaggerUiInstance = null;
let scalarLoaded = false;

/**
 * Muat script secara dinamis jika belum ada di DOM
 */
function loadScript(url) {
  return new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[src="${url}"]`);
    if (existing) {
      if (existing.getAttribute('data-loaded') === 'true' || window.SwaggerUIBundle || window.Scalar) {
        return resolve();
      }
      existing.addEventListener('load', () => resolve());
      return;
    }
    const script = document.createElement('script');
    script.src = url;
    script.crossOrigin = 'anonymous';
    script.onload = () => {
      script.setAttribute('data-loaded', 'true');
      resolve();
    };
    script.onerror = reject;
    document.head.appendChild(script);
  });
}

/**
 * Inisialisasi Swagger UI pada kontainer #swagger-ui
 */
export async function initSwaggerUi() {
  const container = document.getElementById('swagger-ui');
  if (!container) {
    swaggerUiInstance = null;
    return;
  }

  const specUrl = container.getAttribute('data-spec-url') || '/swagger/openapi.json';

  try {
    // Muat bundle JS Swagger UI dari CDN
    await loadScript('https://unpkg.com/swagger-ui-dist@5.18.2/swagger-ui-bundle.js');

    if (typeof window.SwaggerUIBundle === 'function') {
      container.innerHTML = '';

      swaggerUiInstance = window.SwaggerUIBundle({
        url: specUrl,
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          window.SwaggerUIBundle.presets.apis,
          window.SwaggerUIBundle.SwaggerUIStandalonePreset || null,
        ].filter(Boolean),
        layout: 'BaseLayout',
        defaultModelsExpandDepth: 1,
        defaultModelExpandDepth: 1,
        docExpansion: 'list',
        persistAuthorization: true,
      });
    }
  } catch (error) {
    console.error('[Swagger] Gagal menginisialisasi Swagger UI:', error);
    container.innerHTML = `
      <div class="alert alert-error my-4">
        <span>Gagal memuat asset Swagger UI dari CDN. Periksa koneksi internet Anda.</span>
      </div>
    `;
  }

  // Inisialisasi tombol toggle view (Swagger UI vs Scalar)
  initExplorerTabs(specUrl);

  // Fungsi untuk membersihkan teks dummy bawaan Swagger UI (seperti 'string', 'user@example.com')
  // Menggunakan native prototype setter agar sinkron dengan React controlled input internal Swagger UI
  function clearDummyValue(input) {
    if (!input || input.type === 'checkbox' || input.type === 'radio' || input.type === 'submit') return;
    const dummyValues = ['string', 'user@example.com', 'string($email)', 'string($password)'];
    if (dummyValues.includes(input.value)) {
      try {
        const nativeSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value')?.set;
        if (nativeSetter) {
          nativeSetter.call(input, '');
        } else {
          input.value = '';
        }
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      } catch (err) {
        input.value = '';
      }
    }
  }

  function clearAllDummiesInContainer(root = container) {
    if (!root) return;
    root.querySelectorAll('input').forEach(clearDummyValue);
  }

  // Observer untuk mendeteksi setiap kali form "Try it out" dibuka atau elemen input baru dirender
  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      if (mutation.type === 'childList') {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === Node.ELEMENT_NODE) {
            if (node.tagName === 'INPUT') {
              clearDummyValue(node);
            } else if (node.querySelectorAll) {
              node.querySelectorAll('input').forEach(clearDummyValue);
            }
          }
        });
      }
    }
  });

  observer.observe(container, { childList: true, subtree: true });

  // Saat tombol Try it out diklik, bersihkan berulang kali selama rendering React
  document.addEventListener('click', (e) => {
    const btn = e.target && e.target.closest('.try-out__btn');
    if (btn) {
      [50, 150, 300, 500].forEach((delay) => {
        setTimeout(() => clearAllDummiesInContainer(btn.closest('.opblock')), delay);
      });
    }
    if (e.target && e.target.tagName === 'INPUT') {
      clearDummyValue(e.target);
    }
  }, true);

  // Saat input difokuskan oleh pengguna, pastikan teks dummy langsung hilang jika masih tersisa
  document.addEventListener('focusin', (e) => {
    if (e.target && e.target.tagName === 'INPUT') {
      clearDummyValue(e.target);
    }
  }, true);
}

/**
 * Setup tab switcher antara Swagger UI dan Scalar Studio
 */
function initExplorerTabs(specUrl) {
  const swaggerTabBtn = document.getElementById('tab-swagger-btn');
  const scalarTabBtn = document.getElementById('tab-scalar-btn');
  const swaggerView = document.getElementById('view-swagger');
  const scalarView = document.getElementById('view-scalar');
  const scalarContainer = document.getElementById('scalar-container');

  if (!swaggerTabBtn || !scalarTabBtn || !swaggerView || !scalarView) return;

  function switchTab(target) {
    if (target === 'swagger') {
      swaggerTabBtn.classList.add('tab-active', 'font-bold');
      scalarTabBtn.classList.remove('tab-active', 'font-bold');
      swaggerView.classList.remove('hidden');
      scalarView.classList.add('hidden');
    } else {
      scalarTabBtn.classList.add('tab-active', 'font-bold');
      swaggerTabBtn.classList.remove('tab-active', 'font-bold');
      scalarView.classList.remove('hidden');
      swaggerView.classList.add('hidden');

      if (!scalarLoaded && scalarContainer) {
        renderScalarStudio(scalarContainer, specUrl);
      }
    }
  }

  swaggerTabBtn.onclick = () => switchTab('swagger');
  scalarTabBtn.onclick = () => switchTab('scalar');
}

/**
 * Render Scalar Modern API Reference
 */
async function renderScalarStudio(container, specUrl) {
  scalarLoaded = true;
  container.innerHTML = `
    <div class="flex flex-col items-center justify-center py-20 gap-3 text-center">
      <span class="loading loading-spinner loading-md text-primary"></span>
      <p class="text-xs text-base-content/70">Memuat antarmuka Scalar Modern Studio...</p>
    </div>
  `;

  try {
    // Siapkan tag konfigurasi Scalar Standalone
    const existingRef = document.getElementById('api-reference');
    if (!existingRef) {
      const refScript = document.createElement('script');
      refScript.id = 'api-reference';
      refScript.setAttribute('data-url', specUrl);
      refScript.setAttribute('data-configuration', JSON.stringify({
        theme: 'purple',
        darkMode: true,
        hideDownloadButton: false,
        metaData: {
          title: 'Nexus API Documentation &bull; Scalar',
        },
      }));
      container.appendChild(refScript);
    }

    await loadScript('https://cdn.jsdelivr.net/npm/@scalar/api-reference@latest/dist/browser/standalone.js');
  } catch (err) {
    console.error('[Scalar] Error loading Scalar Studio:', err);
    container.innerHTML = `
      <div class="alert alert-warning text-xs">
        <span>Gagal memuat Scalar Studio dari CDN. Anda tetap dapat menggunakan tab Swagger UI di atas.</span>
      </div>
    `;
  }
}
