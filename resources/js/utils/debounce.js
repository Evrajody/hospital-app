/**
 * Petit debounce sans dépendance : retarde l'appel de `fn` tant que de nouveaux
 * appels arrivent dans la fenêtre `delay` (ms). Idéal pour les champs de recherche
 * afin d'éviter une requête serveur à chaque frappe.
 *
 * @param {Function} fn
 * @param {number} delay
 * @returns {Function}
 */
export function debounce(fn, delay = 300) {
  let timer = null;
  return function (...args) {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}
