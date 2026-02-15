<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche d'Imputation FI-{{ str_pad($reglement->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            color: #000000;
            line-height: 1.4;
        }

        .document-page {
            width: 100%;
            padding: 0;
        }

        /* En-tête officiel */
        .header {
            width: 100%;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px double #000000;
        }

        .header-table {
            width: 100%;
        }

        .logo-section {
            text-align: center;
        }

        .republic {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .motto {
            font-size: 11px;
            color: #666666;
            font-style: italic;
            margin: 4px 0;
        }

        .separator-text {
            font-size: 12px;
            color: #666666;
            margin: 6px 0;
        }

        .hospital-name {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 8px 0;
        }

        .hospital-info {
            font-size: 10px;
            color: #666666;
            line-height: 1.6;
        }

        .document-ref {
            text-align: right;
            border: 2px solid #000000;
            padding: 12px;
            vertical-align: top;
            background: #f5f5f5;
        }

        .ref-number {
            font-size: 15px;
            font-weight: bold;
        }

        .ref-year {
            font-size: 11px;
            color: #666666;
            margin-top: 4px;
        }

        /* Titre */
        .document-title-section {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
        }

        .document-title-section h1 {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 0 0 4px 0;
        }

        .document-subtitle {
            font-size: 12px;
            color: #333333;
        }

        .document-date {
            text-align: right;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 15px;
        }

        /* Sections */
        .section {
            margin-bottom: 15px;
        }

        .section h2 {
            font-size: 13px;
            font-weight: bold;
            color: white;
            background: #000000;
            padding: 7px 12px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Info section */
        .info-section {
            background: #f5f5f5;
            padding: 12px;
            border-left: 4px solid #000000;
            margin-bottom: 15px;
        }

        .info-grid-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-grid-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #cccccc;
            font-size: 12px;
            width: 50%;
        }

        .info-label {
            color: #666666;
            font-weight: 600;
        }

        .info-value {
            color: #000000;
        }

        .montant-highlight {
            color: #000000;
            font-weight: bold;
            font-size: 14px;
        }

        /* Imputation table */
        .imputation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .imputation-table thead {
            background: #000000;
            color: white;
        }

        .imputation-table th {
            padding: 10px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .imputation-table th.montant-col {
            text-align: right;
            width: 140px;
        }

        .imputation-table th.compte-col {
            width: 100px;
        }

        .imputation-table tbody tr {
            border-bottom: 1px solid #cccccc;
        }

        .compte-cell {
            padding: 12px 10px;
            font-weight: bold;
            color: #333333;
            font-family: 'Courier New', monospace;
        }

        .libelle-cell {
            padding: 12px 10px;
            color: #000000;
        }

        .detail-cell {
            font-size: 10px;
            color: #666666;
            margin-top: 3px;
            font-style: italic;
        }

        .montant-cell {
            padding: 12px 10px;
            text-align: right;
            font-weight: 600;
        }

        .debit-cell {
            background: #f5f5f5;
        }

        .credit-cell {
            background: #f5f5f5;
        }

        .imputation-table tfoot {
            background: #eeeeee;
            border-top: 2px solid #000000;
        }

        .total-label {
            padding: 12px 10px;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: bold;
        }

        .total-cell {
            font-size: 13px;
            font-weight: bold;
            background: #cccccc;
            text-align: right;
            padding: 12px 10px;
        }

        /* Equilibre */
        .equilibre-note {
            padding: 10px 12px;
            background: #f5f5f5;
            border: 1px solid #000000;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .check-mark {
            color: #16a34a;
            font-weight: bold;
        }

        /* Pièces justificatives */
        .pieces-list {
            margin-bottom: 10px;
        }

        .piece-item {
            padding: 8px 12px;
            background: #f5f5f5;
            border-left: 3px solid #666666;
            font-size: 12px;
            color: #333333;
            margin-bottom: 6px;
        }

        /* Signatures */
        .signature-section {
            margin-top: 20px;
        }

        .signature-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
        }

        .signature-box {
            text-align: center;
            padding: 12px;
            border: 1px solid #cccccc;
            background: #f5f5f5;
            vertical-align: top;
            width: 33%;
        }

        .signature-label {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .signature-sub {
            font-size: 11px;
            color: #666666;
            font-style: italic;
            margin-bottom: 15px;
        }

        .signature-space {
            height: 35px;
            margin: 10px 0 8px 0;
        }

        .signature-name {
            font-size: 11px;
            color: #666666;
            margin-bottom: 4px;
        }

        .signature-date {
            font-size: 10px;
            color: #666666;
        }

        /* Note comptable */
        .comptable-note {
            margin-top: 15px;
            padding: 12px;
            background: #f5f5f5;
            border-left: 4px solid #000000;
            font-size: 11px;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666666;
        }

        .confidential {
            margin-top: 4px;
            font-style: italic;
            color: #000000;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="document-page">
        <!-- En-tête officiel -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-section" style="width: 65%;">
                        <div class="republic">REPUBLIQUE DU BENIN</div>
                        <div class="motto">Fraternite - Justice - Travail</div>
                        <div class="separator-text">***</div>
                        <div class="hospital-name">HOPITAL DE MENONTIN</div>
                        <div class="hospital-info">
                            Service Comptabilite<br>
                            BP 123 - Cotonou<br>
                            Tel: +229 21 XX XX XX
                        </div>
                    </td>
                    <td class="document-ref" style="width: 35%;">
                        <div class="ref-number">FI-{{ str_pad($reglement->id, 6, '0', STR_PAD_LEFT) }}</div>
                        <div class="ref-year">{{ \Carbon\Carbon::parse($reglement->date_reglement)->format('Y') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="document-title-section">
            <h1>FICHE D'IMPUTATION COMPTABLE</h1>
            <div class="document-subtitle">Reglement Fournisseurs</div>
        </div>

        <div class="document-date">
            Cotonou, le {{ \Carbon\Carbon::parse($reglement->date_reglement)->locale('fr')->isoFormat('D MMMM YYYY') }}
        </div>

        <!-- Informations du règlement -->
        <div class="section info-section">
            <h2>INFORMATIONS DU REGLEMENT</h2>
            <table class="info-grid-table">
                <tr>
                    <td>
                        <span class="info-label">Fournisseur :</span><br>
                        <span class="info-value"><strong>{{ $reglement->fournisseur->nom }}</strong></span>
                    </td>
                    <td>
                        <span class="info-label">Code :</span><br>
                        <span class="info-value">FOUR{{ str_pad($reglement->fournisseur->id, 3, '0', STR_PAD_LEFT) }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="info-label">Facture N&deg; :</span><br>
                        <span class="info-value"><strong>{{ $reglement->facture->numero_piece }}</strong></span>
                    </td>
                    <td>
                        <span class="info-label">Date facture :</span><br>
                        <span class="info-value">{{ \Carbon\Carbon::parse($reglement->facture->date)->format('d/m/Y') }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="info-label">Date reglement :</span><br>
                        <span class="info-value">{{ \Carbon\Carbon::parse($reglement->date_reglement)->format('d/m/Y') }}</span>
                    </td>
                    <td>
                        <span class="info-label">Mode paiement :</span><br>
                        <span class="info-value">{{ $modeLabel }}</span>
                    </td>
                </tr>
                <tr>
                    @if($reglement->reference)
                    <td>
                        <span class="info-label">Reference :</span><br>
                        <span class="info-value">{{ $reglement->reference }}</span>
                    </td>
                    @else
                    <td></td>
                    @endif
                    <td>
                        <span class="info-label">Montant :</span><br>
                        <span class="info-value montant-highlight">{{ $montantFormate }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Table d'imputation -->
        <div class="section">
            <h2>IMPUTATION COMPTABLE</h2>
            <table class="imputation-table">
                <thead>
                    <tr>
                        <th class="compte-col">N&deg; Compte</th>
                        <th>Libelle du compte</th>
                        <th class="montant-col">Debit</th>
                        <th class="montant-col">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Débit fournisseur -->
                    <tr>
                        <td class="compte-cell">{{ $compteDebit }}</td>
                        <td class="libelle-cell">
                            Fournisseurs - {{ $reglement->fournisseur->nom }}
                            <div class="detail-cell">Reglement facture {{ $reglement->facture->numero_piece }}</div>
                        </td>
                        <td class="montant-cell debit-cell">{{ $montantFormate }}</td>
                        <td class="montant-cell"></td>
                    </tr>

                    <!-- Crédit selon mode de paiement -->
                    <tr>
                        <td class="compte-cell">{{ $compteCredit }}</td>
                        <td class="libelle-cell">
                            {{ $libelleCredit }}
                            @if($reglement->banque)
                            <div class="detail-cell">{{ $reglement->banque }}{{ $reglement->numero_compte_bancaire ? ' - ' . $reglement->numero_compte_bancaire : '' }}</div>
                            @endif
                            @if($reglement->reference)
                            <div class="detail-cell">Ref: {{ $reglement->reference }}</div>
                            @endif
                        </td>
                        <td class="montant-cell"></td>
                        <td class="montant-cell credit-cell">{{ $montantFormate }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="total-label">TOTAUX</td>
                        <td class="total-cell">{{ $montantFormate }}</td>
                        <td class="total-cell">{{ $montantFormate }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="equilibre-note">
                <span class="check-mark">&#10003;</span> Equilibre comptable verifie : Debit = Credit
            </div>
        </div>

        <!-- Pièces justificatives -->
        <div class="section">
            <h2>PIECES JUSTIFICATIVES</h2>
            <div class="pieces-list">
                <div class="piece-item">
                    &#9679; Facture fournisseur N&deg; {{ $reglement->facture->numero_piece }}
                </div>
                <div class="piece-item">
                    &#9679; Mandat de paiement N&deg; {{ str_pad($reglement->id, 6, '0', STR_PAD_LEFT) }}
                </div>
                @if($reglement->mode_paiement === 'cheque')
                <div class="piece-item">
                    &#9679; Cheque N&deg; {{ $reglement->reference ?: '______' }}
                </div>
                @endif
                @if($reglement->mode_paiement === 'virement')
                <div class="piece-item">
                    &#9679; Ordre de virement N&deg; {{ $reglement->reference ?: '______' }}
                </div>
                @endif
            </div>
        </div>

        <!-- Visas et signatures -->
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td class="signature-box">
                        <div class="signature-label">Prepare par</div>
                        <div class="signature-sub">Le Comptable</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">Nom et signature</div>
                        <div class="signature-date">Date : ___/___/______</div>
                    </td>
                    <td class="signature-box">
                        <div class="signature-label">Verifie par</div>
                        <div class="signature-sub">Le Chef Comptable</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">Nom et signature</div>
                        <div class="signature-date">Date : ___/___/______</div>
                    </td>
                    <td class="signature-box">
                        <div class="signature-label">Approuve par</div>
                        <div class="signature-sub">Le DAF</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">Nom et signature</div>
                        <div class="signature-date">Date : ___/___/______</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Note comptable -->
        <div class="comptable-note">
            <strong>NOTE COMPTABLE :</strong> Cette fiche d'imputation constitue une piece comptable justificative
            de l'enregistrement du reglement fournisseur. Elle doit etre classee avec les pieces justificatives de la periode.
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>Document genere le {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('D MMMM YYYY') }}</div>
            <div class="confidential">Document comptable - A conserver</div>
        </div>
    </div>
</body>
</html>
