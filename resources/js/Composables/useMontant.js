/**
 * Composable pour le formatage des montants
 */
export function useMontant() {
  const formatMontant = (montant) => {
    return new Intl.NumberFormat('fr-FR', {
      maximumFractionDigits: 0,
      minimumFractionDigits: 0
    }).format(montant || 0);
  };

  const formatInputMontant = (val) => {
    if (!val && val !== 0) return '';
    return new Intl.NumberFormat('fr-FR').format(val);
  };

  const parseInputMontant = (val) => {
    if (!val) return 0;
    const cleaned = String(val).replace(/[^\d]/g, '');
    return parseInt(cleaned, 10) || 0;
  };

  /**
   * Convertit les champs numériques d'un objet réactif (form) en float.
   * Résout le problème des casts decimal Laravel qui retournent des strings ("1250000.00")
   * alors que les règles de validation Element Plus attendent des numbers.
   *
   * @param {Object} form - L'objet réactif du formulaire
   * @param {string[]} fields - Liste des champs à convertir
   */
  const castNumericFields = (form, fields) => {
    fields.forEach(field => {
      if (form[field] !== null && form[field] !== undefined && form[field] !== '') {
        form[field] = parseFloat(form[field]);
        if (isNaN(form[field])) {
          form[field] = 0;
        }
      }
    });
  };

  return {
    formatMontant,
    formatInputMontant,
    parseInputMontant,
    castNumericFields,
  };
}
