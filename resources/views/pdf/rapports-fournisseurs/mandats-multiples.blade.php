<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereaux de Règlement</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 30mm;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #000;
            line-height: 1.6;
            padding: 20mm 18mm 18mm;
        }

        .page-break { page-break-after: always; }

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
            font-size: 13px;
            font-style: italic;
            font-weight: bold;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .details-table td {
            padding: 6px 0;
            vertical-align: top;
        }

        .details-label {
            width: 250px;
            font-weight: bold;
            white-space: nowrap;
            padding-right: 15px;
        }

        .montant-lettres {
            font-size: 11px;
            color: #000;
            font-weight: normal;
            font-style: normal;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .spacer td { height: 12px; }

        /* Mode de paiement : 2 colonnes alignées (mode / N° à gauche, banque / date à droite) */
        .mode-paiement-table { border-collapse: collapse; }
        .mode-paiement-table td { padding: 0 0 2px 0; vertical-align: top; }
        .mode-paiement-table .mp-col1 { white-space: nowrap; padding-right: 30px; }

        .montants-section { margin: 15px 0; }
        .montants-section table { width: 100%; border-collapse: collapse; }
        .montants-section td { padding: 6px 0; vertical-align: top; }
        .montants-section .details-label { width: 250px; }

        /* Montant : nombre aligné à droite, unité « FCFA » en italique juste après */
        .montant-val {
            width: 120px;
            text-align: right;
            white-space: nowrap;
            padding-right: 4px;
        }

        .montant-unit {
            font-style: italic;
            white-space: nowrap;
            width: 62px;
        }

        /* Montant en lettres : colonne propre (le texte qui revient à la ligne reste aligné sous les mots). */
        .montant-mots {
            font-size: 9px;
            text-transform: uppercase;
            text-decoration: underline;
            vertical-align: top;
        }

        /* Montant payé / reste à payer : en gras */
        .montant-fort { font-weight: bold; }

        .fait-a {
            text-align: center;
            margin: 22px 0 14px;
            font-style: italic;
            font-weight: bold;
            font-size: 14px;
        }

        .signatures-table { width: 100%; }
        .signatures-table td.sig-cell { width: 40%; vertical-align: top; padding: 0 15px; text-align: center; }
        .signatures-table td.sig-gap { width: 20%; }
        .signature-title { font-weight: bold; font-size: 13px; margin-bottom: 5px; }
        .signature-name { margin-top: 55px; font-size: 13px; font-weight: bold; }

        .footer { position: fixed; bottom: 10mm; left: 0; right: 0; font-size: 10px; color: #666; font-style: italic; padding: 0 10mm; }
    </style>
</head>
<body>
    @foreach($mandats as $index => $mandat)
        @php
            $reglement = $mandat['reglement'];
            $facture = $mandat['facture'];
            $etablissement = $mandat['etablissement'];
            $modeLabel = $mandat['modeLabel'];
            $montantEnLettres = $mandat['montantEnLettres'];
            $resteAPayerLettres = $mandat['resteAPayerLettres'];
            $user = $mandat['user'];
        @endphp

        @include('pdf._entete-officiel')

        <!-- Titre : à gauche, gras + souligné, N° sur la même ligne -->
        <div class="document-title">
            <span class="title-main">Bordereau de règlement :</span>
            <em class="title-numero">N° {{ $facture->numero_piece }}{{ $reglement->numero_ligne }}/DAF/H.M.</em>
        </div>

        <div class="exercice">EXERCICE {{ \Carbon\Carbon::parse($reglement->date_reglement)->format('Y') }}</div>

        <div class="intro-text">
            En vertu des crédits ouverts au titre du compte désigné ci-contre, le Directeur de l'hôpital
            de Mènontin mandate sur la caisse du Centre, la créance détaillée ci-après :
        </div>

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

        <div class="montants-section">
            <table>
                <tr>
                    <td class="details-label">MONTANT FACTURE :</td>
                    <td class="montant-val">{{ number_format((float) $facture->montant_facture, 0, ',', ' ') }}</td>
                    <td class="montant-unit">&nbsp;&nbsp;FCFA</td>
                    <td class="montant-mots"></td>
                </tr>
                @if($facture->assujetti_tva && (float) ($facture->montant_tva ?? 0) > 0)
                <tr>
                    <td class="details-label">MONTANT TVA ({{ $facture->taux_tva }}%) :</td>
                    <td class="montant-val">{{ number_format((float) $facture->montant_tva, 0, ',', ' ') }}</td>
                    <td class="montant-unit">&nbsp;&nbsp;FCFA</td>
                    <td class="montant-mots"></td>
                </tr>
                @endif
                <tr>
                    <td class="details-label">MONTANT AVOIR / ESCOMPT :</td>
                    <td class="montant-val">{{ number_format((float) ($facture->avoir ?? 0), 0, ',', ' ') }}</td>
                    <td class="montant-unit">&nbsp;&nbsp;FCFA</td>
                    <td class="montant-mots"></td>
                </tr>

                <tr>
                    <td class="details-label">MONTANT PAYE (FCFA):</td>
                    <td class="montant-val montant-fort">{{ number_format((float) $reglement->montant, 0, ',', ' ') }}</td>
                    <td class="montant-unit montant-fort">&nbsp;&nbsp;FCFA</td>
                    <td class="montant-mots">{{ strtoupper($montantEnLettres) }}</td>
                </tr>
                <tr>
                    @php $resteAPayer = (float) $facture->reste_a_payer; @endphp
                    <td class="details-label">RESTE A PAYER (FCFA):</td>
                    <td class="montant-val montant-fort">{{ number_format($resteAPayer, 0, ',', ' ') }}</td>
                    <td class="montant-unit montant-fort">&nbsp;&nbsp;FCFA</td>
                    <td class="montant-mots">{{ $resteAPayerLettres }}</td>
                </tr>
            </table>
        </div>

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
                            @php $dateRef = $reglement->date_reference ?: $reglement->date_reglement; @endphp
                            <td class="mp-col2">@if($dateRef)du {{ \Carbon\Carbon::parse($dateRef)->format('d/m/Y') }}@endif</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <div class="fait-a">
            <em>Fait à Cotonou, le {{ \Carbon\Carbon::parse($reglement->date_reglement)->format('d/m/Y') }}</em>
        </div>

        <table class="signatures-table">
            <tr>
                <td class="sig-cell">
                    <div class="signature-title">Le Bénéficiaire,</div>
                    <div class="signature-name">{{ strtoupper($reglement->beneficiaire ?: ($reglement->fournisseur_nom ?: $reglement->fournisseur?->nom ?? '')) }}</div>
                </td>
                <td class="sig-gap"></td>
                <td class="sig-cell">
                    <div class="signature-title">L'Administrateur,</div>
                    <div class="signature-name">{{ $etablissement['directeur'] ?? '' }}</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            Edité par {{ $user?->name ?? 'Système' }}
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
