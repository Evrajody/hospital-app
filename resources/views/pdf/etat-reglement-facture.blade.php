<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>État de Règlement - {{ $facture->numero_piece }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 30mm;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Times New Roman', serif;
            font-size: 13px;
            color: #000;
            line-height: 1.5;
            padding: 0 15mm;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .hospital-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .hospital-info {
            font-size: 11px;
            color: #444;
            line-height: 1.6;
            margin-top: 3px;
        }

        .document-title {
            text-align: center;
            margin: 25px 0 20px;
        }

        .document-title h1 {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            border: 2px solid #000;
            display: inline-block;
            padding: 8px 30px;
        }

        .fournisseur-line {
            margin: 15px 0 10px;
            font-size: 13px;
        }

        .info-line {
            margin: 5px 0;
            font-size: 13px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin: 5px 0;
        }

        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .info-label {
            font-weight: bold;
            white-space: nowrap;
            padding-right: 10px;
        }

        .objet-line {
            margin: 10px 0;
            font-size: 13px;
        }

        .montants-line {
            margin: 5px 0 15px;
            font-size: 13px;
        }

        .reglements-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin: 15px 0;
        }

        .reglements-table th {
            border: 1px solid #000;
            padding: 6px 8px;
            font-weight: bold;
            text-align: center;
            background-color: #fff;
            font-size: 11px;
        }

        .reglements-table td {
            border: 1px solid #000;
            padding: 5px 8px;
        }

        .reglements-table td.montant {
            text-align: right;
        }

        .totaux-table {
            margin-left: auto;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 10px;
        }

        .totaux-table td {
            padding: 4px 10px;
        }

        .totaux-label {
            font-weight: bold;
            text-align: right;
            padding-right: 15px;
        }

        .totaux-value {
            text-align: right;
            min-width: 100px;
        }

        .footer {
            position: fixed;
            bottom: 10mm;
            left: 0;
            right: 0;
            font-size: 10px;
            color: #666;
            font-style: italic;
            padding: 0 10mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="hospital-name">{{ $etablissement['nom'] }}</div>
        <div class="hospital-info">
            {{ $etablissement['adresse'] }}<br>
            {{ $etablissement['telephone'] ? 'Tél.: ' . $etablissement['telephone'] : '' }}
            @if(!empty($etablissement['email']))
                - E-mail: {{ $etablissement['email'] }}
            @endif
        </div>
    </div>

    <div class="document-title">
        <h1>État de Règlement Facture</h1>
    </div>

    <div class="fournisseur-line">
        <strong>Fournisseur :</strong> [{{ $fournisseur?->compteComptable?->numero_compte }}] {{ $fournisseurNom ?? $fournisseur?->nom }}
    </div>

    <table class="info-table" style="border: 1px solid #000; padding: 8px;">
        <tr>
            <td><strong>N° PC :</strong> {{ $facture->numero_piece }}</td>
            <td><strong>Date PC :</strong> {{ $facture->date?->format('d/m/Y') }}</td>
            <td><strong>Réf. Facture :</strong> {{ $facture->reference_facture }}</td>
        </tr>
    </table>

    <div class="objet-line">
        <strong>Objet :</strong> {{ strtoupper($facture->libelle) }}
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Montant HT :</strong> {{ number_format((float) $facture->montant_facture, 0, ',', ' ') }}</td>
            <td><strong>TVA{{ $facture->assujetti_tva && (float) ($facture->montant_tva ?? 0) > 0 ? ' (' . $facture->taux_tva . '%)' : '' }} :</strong> {{ number_format((float) ($facture->montant_tva ?? 0), 0, ',', ' ') }}</td>
            <td><strong>Montant TTC :</strong> {{ number_format((float) $facture->montant_ttc, 0, ',', ' ') }}</td>
        </tr>
        <tr>
            <td><strong>Montant M.O. :</strong> {{ number_format((float) $facture->montant_mo, 0, ',', ' ') }}</td>
            <td><strong>AIB{{ $facture->taux && (float) $facture->taux > 0 ? ' (' . $facture->taux . '%)' : '' }} :</strong> {{ number_format((float) ($facture->montant_aib ?? 0), 0, ',', ' ') }}</td>
            <td><strong>Avoir :</strong> {{ number_format((float) $facture->avoir, 0, ',', ' ') }}</td>
        </tr>
    </table>

    <table class="reglements-table">
        <thead>
            <tr>
                <th>N° Ordre de règlement</th>
                <th>Date règlement</th>
                <th>Mode règlement</th>
                <th>Bénéficiaire</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reglements as $index => $reglement)
            <tr>
                <td>{{ $reglement->date_reglement?->format('dmY') }}-{{ $reglement->created_at?->format('Hi') }}</td>
                <td>{{ $reglement->date_reglement?->format('d/m/Y') }}</td>
                <td>
                    @php
                        $modeLabel = match($reglement->mode_paiement) {
                            'cheque' => 'Chèque',
                            'virement' => 'Virement Bancaire',
                            'especes' => 'Espèces',
                            default => ucfirst($reglement->mode_paiement ?? ''),
                        };
                    @endphp
                    {{ $modeLabel }}
                </td>
                <td>{{ $reglement->beneficiaire ?: ($fournisseurNom ?? $fournisseur?->nom) }}</td>
                <td class="montant">{{ number_format((float) $reglement->montant, 0, ',', ' ') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; font-style: italic;">Aucun règlement</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totaux-table">
        <tr>
            <td class="totaux-label">Total règlement :</td>
            <td class="totaux-value">{{ number_format((float) $totalReglements, 0, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="totaux-label">Montant Dû (Net à payer) :</td>
            <td class="totaux-value">{{ number_format($montantDu, 0, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="totaux-label">Solde :</td>
            <td class="totaux-value">{{ number_format($solde, 0, ',', ' ') }}</td>
        </tr>
    </table>

    <div class="footer">
        Edité par {{ $user?->name ?? 'Système' }}
    </div>
</body>
</html>
