<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau de Règlement - {{ $reglement->facture->numero_piece }}</title>
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
            padding: 20mm 18mm 18mm;
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
            text-align: left;
            margin: 20px 0 8px;
        }

        .document-title .title-main {
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
        }

        .document-title .title-numero {
            font-size: 14px;
        }

        .exercice {
            margin: 5px 0 18px;
            font-size: 14px;
            font-weight: bold;
        }

        .intro-text {
            font-size: 12px;
            font-style: italic;
            line-height: 1.7;
            margin-bottom: 25px;
            
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .details-table td {
            padding: 5px 0;
            vertical-align: top;
        }

        .details-label {
            width: 250px;
            font-weight: bold;
            white-space: nowrap;
            padding-right: 15px;
        }

        .montant-lettres {
            display: inline-block;
            font-size: 10px;
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .spacer td {
            height: 12px;
        }

        /* Mode de paiement : 2 colonnes alignées (mode / N° à gauche, banque / date à droite) */
        .mode-paiement-table {
            border-collapse: collapse;
        }

        .mode-paiement-table td {
            padding: 0 0 2px 0;
            vertical-align: top;
        }

        .mode-paiement-table .mp-col1 {
            white-space: nowrap;
            padding-right: 30px;
        }

        .montants-section {
            margin: 15px 0;
        }

        .montants-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .montants-section td {
            padding: 5px 0;
            vertical-align: top;
        }

        .montants-section .details-label {
            width: 250px;
        }

        .fait-a {
            text-align: center;
            margin: 40px 0 25px;
            font-style: italic;
            font-weight: bold;
            font-size: 14px;
        }

        .signatures-table {
            width: 100%;
        }

        .signatures-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 15px;
        }

        .signature-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .signature-name {
            margin-top: 55px;
            font-size: 13px;
            font-weight: bold;
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
    <!-- Entête officielle (Ministère / Direction / Zone / Hôpital) -->
    @include('pdf._entete-officiel')

    <!-- Titre : à gauche, gras + souligné, N° sur la même ligne -->
    <div class="document-title">
        <span class="title-main">Bordereau de règlement :</span>
        <em class="title-numero">N° {{ $reglement->facture->numero_piece }}/DAF/H.M.</em>
    </div>

    <div class="exercice">EXERCICE {{ \Carbon\Carbon::parse($reglement->date_reglement)->format('Y') }}</div>

    <div class="intro-text">
        En vertu des crédits ouverts au titre du compte désigné ci-contre, le Directeur de l'hôpital
        de Mènontin mandate sur la caisse du Centre, la créance détaillée ci-après :
    </div>

    <!-- Infos facture -->
    <table class="details-table">
        <tr>
            <td class="details-label">OBJET :</td>
            <td>{{ $facture->libelle }}</td>
        </tr>
        <tr class="spacer"><td colspan="2"></td></tr>
        <tr>
            <td class="details-label">PRESTATAIRE :</td>
            <td>{{ $reglement->fournisseur_nom ?: $reglement->fournisseur?->nom }}</td>
        </tr>
    </table>

    <!-- Montants -->
    <div class="montants-section">
        <table>
            <tr>
                <td class="details-label">MONTANT FACTURE :</td>
                <td>{{ number_format((float) $facture->montant_facture, 0, ',', ' ') }} FCFA</td>
            </tr>
            @if($facture->assujetti_tva && (float) ($facture->montant_tva ?? 0) > 0)
            <tr>
                <td class="details-label">MONTANT TVA ({{ $facture->taux_tva }}%) :</td>
                <td>{{ number_format((float) $facture->montant_tva, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif
            <tr>
                <td class="details-label">MONTANT AVOIR / ESCOMPT :</td>
                <td>{{ number_format((float) ($facture->avoir ?? 0), 0, ',', ' ') }} FCFA</td>
            </tr>
            @if($facture->taux && (float) $facture->taux > 0)
            <tr>
                <td class="details-label">IMPÔT / AIB : {{ $facture->taux }}%</td>
                <td>{{ number_format((float) ($reglement->montant_aib_deduit ?? 0), 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif
            <tr>
                <td class="details-label">MONTANT PAYE (FCFA):</td>
                <td>
                    {{ number_format((float) $facture->montant_paye, 0, ',', ' ') }} FCFA
                    <span class="montant-lettres">{{ strtoupper($montantEnLettres) }}</span>
                </td>
            </tr>
            <tr>
                @php $resteAPayer = (float) $facture->reste_a_payer; @endphp
                <td class="details-label">RESTE A PAYER (FCFA):</td>
                <td>
                    {{ number_format($resteAPayer, 0, ',', ' ') }} FCFA
                    <span class="montant-lettres">{{ $resteAPayerLettres }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Pièces et mode paiement -->
    <table class="details-table">
        @if($facture->reference_facture)
        <tr>
            <td class="details-label">PIECES JUSTIFICATIVES :</td>
            <td>{{ $facture->reference_facture }}</td>
        </tr>
        <tr class="spacer"><td colspan="2"></td></tr>
        @endif
        <tr>
            <td class="details-label">MODE PAIEMENT :</td>
            <td>
                <table class="mode-paiement-table">
                    <tr>
                        <td class="mp-col1">{{ $modeLabel }}</td>
                        <td class="mp-col2">{{ $reglement->banque }}</td>
                    </tr>
                    @if($reglement->reference)
                    <tr>
                        <td class="mp-col1">N°{{ $reglement->reference }}</td>
                        <td class="mp-col2">@if($reglement->date_reglement)du {{ \Carbon\Carbon::parse($reglement->date_reglement)->format('d/m/Y') }}@endif</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Signatures -->
    <div class="fait-a">
        <em>Fait à Cotonou, le {{ \Carbon\Carbon::parse($reglement->date_reglement)->format('d/m/Y') }}</em>
    </div>

    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-title">Le Bénéficiaire,</div>
                <div class="signature-name">{{ strtoupper($reglement->beneficiaire ?: ($reglement->fournisseur_nom ?: $reglement->fournisseur?->nom ?? '')) }}</div>
            </td>
            <td style="text-align: right;">
                <div class="signature-title">L'Administrateur,</div>
                <div class="signature-name">{{ $etablissement['directeur'] ?? '' }}</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        Edité par {{ $user?->name ?? 'Système' }}
    </div>
</body>
</html>
