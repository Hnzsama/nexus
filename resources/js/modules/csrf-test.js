/**
 * Nexus Framework - CSRF & Form Pipeline Live Test Module
 * Menangani form live test AJAX di halaman utama tanpa reload/refresh.
 */

export function initCsrfTest() {
  const form = document.getElementById('ajax-form');
  if (!form || form.dataset.nexusBound === 'true') return;
  form.dataset.nexusBound = 'true';

  const submitBtn = document.getElementById('submit-btn');
  const feedback = document.getElementById('form-feedback');
  const resultContainer = document.getElementById('result-container');
  const resStatus = document.getElementById('res-status');
  const jsonOutput = document.getElementById('json-output');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    e.stopPropagation();

    if (submitBtn) submitBtn.disabled = true;
    if (feedback) {
      feedback.textContent = 'Memproses request via POST...';
      feedback.className = 'text-xs text-info font-medium';
    }

    const formData = new FormData(form);
    const body = {};
    formData.forEach((val, key) => {
      body[key] = val;
    });

    try {
      const response = await fetch(form.getAttribute('action') || '/submit', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': body._token || '',
        },
        body: JSON.stringify(body),
      });

      const data = await response.json();

      if (resultContainer) {
        resultContainer.classList.remove('hidden');
      }

      if (resStatus) {
        resStatus.textContent = `${response.status} ${response.statusText || 'OK'}`;
        resStatus.className = response.ok
          ? 'badge badge-success badge-sm font-bold'
          : 'badge badge-error badge-sm font-bold';
      }

      if (jsonOutput) {
        jsonOutput.textContent = JSON.stringify(data, null, 2);
      }

      if (feedback) {
        feedback.textContent = response.ok
          ? '✅ Pipeline Berhasil! Response JSON diterima.'
          : '❌ Request Gagal.';
        feedback.className = response.ok
          ? 'text-xs text-success font-medium'
          : 'text-xs text-error font-medium';
      }
    } catch (err) {
      if (feedback) {
        feedback.textContent = '❌ Terjadi kesalahan jaringan: ' + err.message;
        feedback.className = 'text-xs text-error font-medium';
      }
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
      }
    }
  });
}
