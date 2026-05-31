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
            font-family: 'Times New Roman', serif;
            font-size: 13px;
            color: #000;
            line-height: 1.5;
            padding: 0 15mm;
        }

        .page-break { page-break-after: always; }

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
            margin: 25px 0 10px;
        }

        .document-title h1 {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            border: 1px solid #000;
            display: inline-block;
            padding: 8px 30px;
        }

        .numero-piece {
            margin: 12px 0 5px;
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
            display: block;
            font-size: 10px;
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .spacer td { height: 12px; }

        .montants-section { margin: 15px 0; }
        .montants-section table { width: 100%; border-collapse: collapse; }
        .montants-section td { padding: 5px 0; vertical-align: top; }
        .montants-section .details-label { width: 250px; }

        .fait-a {
            text-align: center;
            margin: 40px 0 25px;
            font-style: italic;
            font-weight: bold;
            font-size: 14px;
        }

        .signatures-table { width: 100%; }
        .signatures-table td { width: 50%; vertical-align: top; padding: 0 15px; }
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

        <div class="document-title">
            <h1>Bordereau de Règlement</h1>
        </div>

        <div class="numero-piece">
            <em>N° {{ $facture->numero_piece }}/DAF/H.M.</em>
        </div>

        <div class="exercice">EXERCICE {{ \Carbon\Carbon::parse($reglement->date_reglement)->format('Y') }}</div>

        <div class="intro-text">
            En vertu des crédits ouverts au titre du compte désigné ci-contre, le Directeur de l'hôpital
            ordonne le règlement par la caisse du Centre, de la créance détaillée ci-après :
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
                    <td class="details-label">MONTANT FACTURE HT :</td>
                    <td>{{ number_format((float) $facture->montant_facture, 0, ',', ' ') }}</td>
                </tr>
                @if($facture->assujetti_tva && (float) ($facture->montant_tva ?? 0) > 0)
                <tr>
                    <td class="details-label">MONTANT TVA ({{ $facture->taux_tva }}%) :</td>
                    <td>{{ number_format((float) $facture->montant_tva, 0, ',', ' ') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="details-label">MONTANT TTC :</td>
                    <td>{{ number_format((float) $facture->montant_ttc, 0, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td class="details-label">MONTANT AVOIR :</td>
                    <td>{{ number_format((float) ($facture->avoir ?? 0), 0, ',', ' ') }}</td>
                </tr>
                @if($facture->taux && (float) $facture->taux > 0)
                <tr>
                    <td class="details-label">IMPÔT / AIB {{ $facture->taux }}% :</td>
                    <td>{{ number_format((float) ($reglement->montant_aib_deduit ?? 0), 0, ',', ' ') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="details-label">MONTANT PAYE :</td>
                    <td>
                        {{ number_format((float) $facture->montant_paye, 0, ',', ' ') }}
                        <span class="montant-lettres">{{ strtoupper($montantEnLettres) }}</span>
                    </td>
                </tr>
                <tr>
                    @php $resteAPayer = (float) $facture->reste_a_payer; @endphp
                    <td class="details-label">RESTE A PAYER :</td>
                    <td>
                        {{ number_format($resteAPayer, 0, ',', ' ') }}
                        @if($resteAPayer > 0)
                        <span class="montant-lettres">{{ $resteAPayerLettres }}</span>
                        @endif
                    </td>
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
                    {{ $modeLabel }}
                    @if($reglement->banque)
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $reglement->banque }}
                    @endif
                    @if($reglement->reference)
                    <br>N°{{ $reglement->reference }}
                        @if($reglement->date_reglement)
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;du {{ \Carbon\Carbon::parse($reglement->date_reglement)->format('d/m/Y') }}
                        @endif
                    @endif
                </td>
            </tr>
        </table>

        <div class="fait-a">
            <em>Fait à Cotonou, le {{ \Carbon\Carbon::parse($reglement->date_reglement)->format('d/m/Y') }}</em>
        </div>

        <table class="signatures-table">
            <tr>
                <td>
                    <div class="signature-title">Le Bénéficiaire,</div>
                    <div class="signature-name">{{ strtoupper($reglement->fournisseur_nom ?: $reglement->fournisseur?->nom ?? '') }}</div>
                </td>
                <td style="text-align: right;">
                    <div class="signature-title">Le Directeur,</div>
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
