/**
 * Helper fetch centralisé avec CSRF token automatique.
 * Lit le token depuis la meta tag (comme le comportement par défaut de Laravel).
 */

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * Wrapper autour de fetch() avec gestion CSRF automatique.
 *
 * @param {string} url
 * @param {object} options - mêmes options que fetch(), avec en plus :
 *   - body: si c'est un objet, il sera JSON.stringify automatiquement
 * @returns {Promise<Response>}
 */
export async function fetchApi(url, options = {}) {
  const headers = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': getCsrfToken(),
    ...(options.headers || {}),
  };

  // Auto-set Content-Type pour les requêtes avec body JSON
  let body = options.body;
  if (body && typeof body === 'object' && !(body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(body);
  }

  return fetch(url, {
    ...options,
    headers,
    body,
  });
}

/**
 * Raccourcis pour les méthodes courantes
 */
export const api = {
  get: (url, options = {}) => fetchApi(url, { ...options, method: 'GET' }),
  post: (url, body, options = {}) => fetchApi(url, { ...options, method: 'POST', body }),
  put: (url, body, options = {}) => fetchApi(url, { ...options, method: 'PUT', body }),
  patch: (url, body, options = {}) => fetchApi(url, { ...options, method: 'PATCH', body }),
  delete: (url, options = {}) => fetchApi(url, { ...options, method: 'DELETE' }),
};
