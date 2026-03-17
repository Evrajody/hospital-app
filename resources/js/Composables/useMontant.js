/**
 * Composable pour le formatage des montants
 */
export function useMontant() {
  const formatMontant = (montant) => {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XOF',
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

  return {
    formatMontant,
    formatInputMontant,
    parseInputMontant,
  };
}
