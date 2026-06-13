/**
 * Formate une valeur en chaîne 'YYYY-MM-DD' à partir des composantes LOCALES.
 *
 * ⚠️ Ne JAMAIS utiliser `Date.toISOString().split('T')[0]` pour sérialiser une date
 * choisie par l'utilisateur : `toISOString()` convertit en UTC, donc minuit local
 * dans un fuseau à l'est de Greenwich (ex. Bénin, UTC+1) renvoie la veille
 * → la date enregistrée recule d'un jour.
 *
 * @param {Date|string|null} value
 * @returns {string|null} 'YYYY-MM-DD' ou null
 */
export function toYmd(value) {
  if (!value) return null;

  // Chaîne déjà au format date (venant de l'API : 'YYYY-MM-DD' ou ISO) → on garde la partie date.
  if (typeof value === 'string') {
    return value.length >= 10 ? value.slice(0, 10) : value;
  }

  const d = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(d.getTime())) return null;

  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

/** Date du jour au format 'YYYY-MM-DD' (locale). */
export function todayYmd() {
  return toYmd(new Date());
}
