<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>État de Règlement - {{ $facture->reference }}</title>
    <style>
        @page { size: A4; margin: 20mm 30mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #000; line-height: 1.5; padding: 14mm 18mm 18mm; }
        .header { text-align: center; margin-bottom: 15px; }
        .hospital-name { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .hospital-info { font-size: 11px; color: #444; line-height: 1.6; margin-top: 3px; }
        .document-title { text-align: center; margin: 25px 0 20px; }
        .document-title h1 { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; border: 2px solid #000; display: inline-block; padding: 8px 30px; }
        .client-line { margin: 15px 0 10px; font-size: 13px; }
        .info-table { width: 100%; border-collapse: collapse; font-size: 13px; margin: 5px 0; }
        .info-table td { padding: 2px 0; vertical-align: top; }
        .reglements-table { width: 100%; border-collapse: collapse; font-size: 12px; margin: 15px 0; }
        .reglements-table th { border: 1px solid #000; padding: 6px 8px; font-weight: bold; text-align: center; background-color: #fff; font-size: 11px; }
        .reglements-table td { border: 1px solid #000; padding: 5px 8px; }
        .reglements-table td.montant { text-align: right; }
        .totaux-table { margin-left: auto; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
        .totaux-table td { padding: 4px 10px; }
        .totaux-label { font-weight: bold; text-align: right; padding-right: 15px; }
        .totaux-value { text-align: right; min-width: 100px; }
        .footer { position: fixed; bottom: 10mm; left: 0; right: 0; font-size: 10px; color: #666; font-style: italic; padding: 0 10mm; }
    </style>
</head>
<body>
    @include('pdf._entete-officiel')

    <div class="document-title">
        <h1>État de Règlement Facture</h1>
    </div>

    <div class="client-line">
        <strong>Client :</strong> [{{ $client?->compteComptable?->numero_compte }}] {{ $clientNom ?? $client?->nom }}
    </div>

    <table class="info-table" style="border: 1px solid #000; padding: 8px;">
        <tr>
            <td><strong>Référence :</strong> {{ $facture->reference }}</td>
            <td><strong>Date :</strong> {{ $facture->date_facture?->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table class="info-table" style="margin-top: 10px;">
        <tr>
            <td><strong>Montant :</strong> {{ number_format($montant, 0, ',', ' ') }}</td>
            <td><strong>Ristourne :</strong> {{ number_format($ristourne, 0, ',', ' ') }}</td>
            <td><strong>Net à payer :</strong> {{ number_format($montantDu, 0, ',', ' ') }}</td>
        </tr>
    </table>

    <table class="info-table" style="margin-top: 6px;">
        <tr>
            <td><strong>Statut :</strong> {{ $facture->estSoldeeManuellement() ? 'Soldée manuellement' : ($facture->statut === \App\Models\FactureClient::STATUT_PAYEE ? 'Réglée intégralement' : 'Non soldée') }}</td>
            <td><strong>Date de solde :</strong> {{ $facture->date_solde?->format('d/m/Y') ?? '-' }}</td>
            @if($facture->estSoldeeManuellement())
                <td><strong>Déficit constaté :</strong> {{ number_format($facture->deficitSolde(), 0, ',', ' ') }}</td>
            @endif
        </tr>
    </table>

    <table class="reglements-table">
        <thead>
            <tr>
                <th>N° Règlement</th>
                <th>Date règlement</th>
                <th>Type</th>
                <th>Institution</th>
                <th>Réf. Chèque</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reglements as $reglement)
            <tr>
                <td style="text-align: center;">{{ $facture->reference }}{{ $reglement->numero_ligne }}</td>
                <td style="text-align: center;">{{ $reglement->date_reglement?->format('d/m/Y') }}</td>
                <td>{{ $reglement->type_reglement_libelle ?: 'Règlement' }}</td>
                <td>{{ $reglement->institution ?: '-' }}</td>
                <td>{{ $reglement->reference_cheque ?: '-' }}</td>
                <td class="montant">{{ number_format((float) $reglement->montant, 0, ',', ' ') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; font-style: italic;">Aucun règlement</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totaux-table">
        <tr>
            <td class="totaux-label">Montant Dû (Net à payer) :</td>
            <td class="totaux-value">{{ number_format($montantDu, 0, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="totaux-label">Total règlement (rejet inclus) :</td>
            <td class="totaux-value">{{ number_format($totalReglements, 0, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="totaux-label">Dont total rejet :</td>
            <td class="totaux-value">{{ number_format($totalRejet ?? 0, 0, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="totaux-label">Total pertes :</td>
            <td class="totaux-value">{{ number_format($totalPerte ?? 0, 0, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="totaux-label">Solde (reste à payer) :</td>
            <td class="totaux-value">{{ number_format($solde, 0, ',', ' ') }}</td>
        </tr>
    </table>
{{-- 
    <p style="font-size: 10px; font-style: italic; margin-top: 6px;">
        Règle de calcul : Solde = Montant dû − (Total règlement encaissé + Total rejet + Total pertes).
        Le montant du rejet fait partie du montant du règlement.
    </p> --}}

    <div class="footer">
        Edité par {{ $user?->name ?? 'Système' }}
    </div>
</body>
</html>
