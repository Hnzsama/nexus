/**
 * Nexus Framework - Turbo SPA Router Module
 * Seamless client-side page transitions, View Transitions API,
 * AJAX form handling, prefetching, and progress bar.
 */

import { initLucideIcons } from './icons.js';

export class NexusTurboRouter {
  constructor() {
    this.progressBar = null;
    this.progressTimer = null;
    this.prefetchCache = new Map();
    this.isNavigating = false;

    this.init();
  }

  init() {
    this.progressBar = document.getElementById('nexus-progress-bar');

    // Intercept clicks on internal links
    document.addEventListener('click', (e) => this.handleLinkClick(e));

    // Intercept form submissions (e.g. tambah user, hapus user)
    document.addEventListener('submit', (e) => this.handleFormSubmit(e));

    // Handle browser back/forward buttons
    window.addEventListener('popstate', () => {
      this.navigate(window.location.href, { pushState: false });
    });

    // Prefetch on hover for sub-10ms instant navigations
    document.addEventListener('mouseover', (e) => this.handlePrefetch(e));

    // Initial navbar active state
    this.updateNavActiveState(window.location.href);
  }

  startProgressBar() {
    if (!this.progressBar) {
      this.progressBar = document.getElementById('nexus-progress-bar');
    }
    if (!this.progressBar) return;

    clearTimeout(this.progressTimer);
    this.progressBar.style.opacity = '1';
    this.progressBar.style.width = '30%';

    this.progressTimer = setTimeout(() => {
      if (this.progressBar) {
        this.progressBar.style.width = '75%';
      }
    }, 150);
  }

  finishProgressBar() {
    if (!this.progressBar) return;
    clearTimeout(this.progressTimer);
    this.progressBar.style.width = '100%';

    setTimeout(() => {
      if (this.progressBar) {
        this.progressBar.style.opacity = '0';
        setTimeout(() => {
          if (this.progressBar) {
            this.progressBar.style.width = '0%';
          }
        }, 200);
      }
    }, 150);
  }

  updateNavActiveState(targetUrl) {
    try {
      const path = new URL(targetUrl, window.location.origin).pathname;
      const navLinks = document.querySelectorAll('#main-nav a[data-nav]');
      navLinks.forEach((link) => {
        const navType = link.getAttribute('data-nav');
        const isActive =
          (navType === 'home' && path === '/') ||
          (navType === 'users' && path.startsWith('/users'));

        if (isActive) {
          link.className =
            'bg-primary text-primary-content font-bold shadow-md shadow-primary/25 gap-2 transition-all';
        } else {
          link.className =
            'text-base-content/70 hover:text-base-content hover:bg-base-content/10 font-medium gap-2 transition-all';
        }
      });
    } catch (_) {}
  }

  handleLinkClick(e) {
    const link = e.target.closest('a');
    if (!link) return;

    if (link.dataset.native !== undefined) return;
    if (link.target && link.target !== '_self') return;
    if (link.hasAttribute('download')) return;
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

    const targetUrl = new URL(link.href, window.location.origin);
    if (targetUrl.origin !== window.location.origin) return;

    // Anchor hash on the same page
    if (
      targetUrl.pathname === window.location.pathname &&
      targetUrl.search === window.location.search &&
      targetUrl.hash
    ) {
      return;
    }

    e.preventDefault();

    if (targetUrl.href === window.location.href && !targetUrl.pathname.includes('/seed')) {
      return;
    }

    this.navigate(targetUrl.href);
  }

  handlePrefetch(e) {
    const link = e.target.closest('a');
    if (!link || link.target === '_blank' || link.dataset.native !== undefined) return;

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

    // Don't prefetch mutation endpoints
    if (href.includes('/seed') || href.includes('/delete')) return;

    const targetUrl = new URL(link.href, window.location.origin);
    if (targetUrl.origin !== window.location.origin) return;
    if (this.prefetchCache.has(targetUrl.href)) return;

    fetch(targetUrl.href, { headers: { 'X-Nexus-SPA': '1' } })
      .then((res) => {
        if (res.ok && res.headers.get('content-type')?.includes('text/html')) {
          return res.text();
        }
        return null;
      })
      .then((html) => {
        if (html) {
          this.prefetchCache.set(targetUrl.href, html);
        }
      })
      .catch(() => {});
  }

  async handleFormSubmit(e) {
    const form = e.target.closest('form');
    if (!form || form.dataset.native !== undefined || form.dataset.turbo === 'false') return;

    const actionUrl = new URL(form.action, window.location.origin);
    if (actionUrl.origin !== window.location.origin) return;

    e.preventDefault();

    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
    const originalBtnContent = submitBtn ? submitBtn.innerHTML : null;

    if (submitBtn) {
      submitBtn.disabled = true;
      const initialWidth = submitBtn.offsetWidth;
      submitBtn.style.minWidth = `${initialWidth}px`;
      submitBtn.innerHTML = `
        <svg class="animate-spin w-4 h-4 inline-block text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <span class="ml-1.5 text-xs">Memproses...</span>
      `;
    }

    try {
      const method = (form.method || 'POST').toUpperCase();
      const formData = new FormData(form);

      if (method === 'GET') {
        const searchParams = new URLSearchParams(formData);
        const target =
          actionUrl.pathname + (searchParams.toString() ? '?' + searchParams.toString() : '');
        await this.navigate(target);
      } else {
        await this.navigate(actionUrl.href, {
          method,
          body: formData,
        });
      }
    } catch (err) {
      console.error('[Nexus Turbo] Form submission error:', err);
      form.submit();
    } finally {
      if (submitBtn && originalBtnContent) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnContent;
      }
    }
  }

  async navigate(url, options = {}) {
    const { pushState = true, method = 'GET', body = null } = options;

    if (this.isNavigating && method === 'GET') return;
    this.isNavigating = true;

    this.startProgressBar();

    try {
      let html = null;

      if (method === 'GET' && this.prefetchCache.has(url)) {
        html = this.prefetchCache.get(url);
        this.prefetchCache.delete(url);
      } else {
        const response = await fetch(url, {
          method,
          body,
          headers: {
            'X-Nexus-SPA': '1',
            'Accept': 'text/html, application/xhtml+xml',
          },
        });

        // Invalidate prefetch cache on mutations
        if (method !== 'GET') {
          this.prefetchCache.clear();
        }

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('text/html')) {
          console.warn('[Nexus Turbo] Non-HTML response received:', contentType);
          if (method === 'GET') {
            window.location.href = url;
          }
          return;
        }

        url = response.url || url;
        html = await response.text();
      }

      const parser = new DOMParser();
      const newDoc = parser.parseFromString(html, 'text/html');

      // Update Page Title
      if (newDoc.title) {
        document.title = newDoc.title;
      }

      // Update CSRF token across all inputs and meta
      const newCsrf = newDoc.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (newCsrf) {
        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', newCsrf);
        document.querySelectorAll('input[name="_token"]').forEach((input) => {
          input.value = newCsrf;
        });
      }

      const currentMain = document.getElementById('app-main') || document.querySelector('main');
      const incomingMain = newDoc.getElementById('app-main') || newDoc.querySelector('main');

      if (currentMain && incomingMain) {
        const performSwap = () => {
          currentMain.innerHTML = incomingMain.innerHTML;
          currentMain.className = incomingMain.className;
          window.scrollTo({ top: 0, behavior: 'instant' });
          this.updateNavActiveState(url);
          this.executeInlineScripts(currentMain);
          initLucideIcons(currentMain);
        };

        if (document.startViewTransition) {
          await document.startViewTransition(performSwap).finished;
        } else {
          currentMain.style.opacity = '0';
          await new Promise((r) => setTimeout(r, 80));
          performSwap();
          currentMain.style.opacity = '1';
        }

        if (pushState && window.location.href !== url) {
          window.history.pushState({ url }, '', url);
        } else {
          window.history.replaceState({ url }, '', url);
        }

        // Trigger custom navigation event
        document.dispatchEvent(
          new CustomEvent('nexus:navigated', {
            detail: { url, container: currentMain },
          })
        );
      } else {
        console.warn('[Nexus Turbo] No #app-main found in incoming document.');
        if (method === 'GET') {
          window.location.href = url;
        }
      }
    } catch (err) {
      console.error('[Nexus Turbo] Navigation failed, doing fallback:', err);
      if (method === 'GET') {
        window.location.href = url;
      }
    } finally {
      this.finishProgressBar();
      this.isNavigating = false;
    }
  }

  executeInlineScripts(container) {
    const scripts = container.querySelectorAll('script');
    scripts.forEach((oldScript) => {
      const newScript = document.createElement('script');
      Array.from(oldScript.attributes).forEach((attr) => {
        newScript.setAttribute(attr.name, attr.value);
      });
      newScript.textContent = oldScript.textContent;
      oldScript.parentNode.replaceChild(newScript, oldScript);
    });
  }
}

/**
 * Inisialisasi instance router singleton.
 */
let routerInstance = null;

export function initTurboRouter() {
  if (!routerInstance) {
    routerInstance = new NexusTurboRouter();
  }
  return routerInstance;
}
