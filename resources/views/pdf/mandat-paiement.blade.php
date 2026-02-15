<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mandat de Paiement N° {{ str_pad($reglement->id, 6, '0', STR_PAD_LEFT) }}</title>
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
            margin-bottom: 20px;
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
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .motto {
            font-size: 12px;
            color: #666666;
            font-style: italic;
            margin: 4px 0;
        }

        .separator-text {
            font-size: 14px;
            color: #666666;
            margin: 8px 0;
        }

        .hospital-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0 8px 0;
        }

        .hospital-info {
            font-size: 11px;
            color: #666666;
            line-height: 1.6;
        }

        .document-ref {
            text-align: right;
            border: 2px solid #000000;
            padding: 15px;
            vertical-align: top;
        }

        .ref-number {
            font-size: 16px;
            font-weight: bold;
        }

        .ref-year {
            font-size: 12px;
            color: #666666;
            margin-top: 5px;
        }

        /* Titre du document */
        .document-title-section {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
        }

        .document-title-section h1 {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 0 0 4px 0;
        }

        .document-subtitle {
            font-size: 13px;
            color: #333333;
        }

        .document-date {
            text-align: right;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 15px;
        }

        /* Objet */
        .objet-section {
            background: #f5f5f5;
            padding: 12px;
            border-left: 4px solid #000000;
            margin-bottom: 15px;
        }

        .objet-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .objet-content {
            font-size: 14px;
            color: #333333;
            line-height: 1.6;
        }

        /* Sections */
        .section {
            margin-bottom: 15px;
        }

        .section h2 {
            font-size: 14px;
            font-weight: bold;
            color: white;
            background: #333333;
            padding: 8px 12px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Info table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table tr {
            border-bottom: 1px solid #cccccc;
        }

        .info-table td {
            padding: 8px 0;
            font-size: 12px;
        }

        .info-table .label {
            width: 220px;
            color: #666666;
            font-weight: 600;
        }

        .info-table .value {
            color: #000000;
        }

        .info-table .value-sub {
            font-size: 11px;
            color: #666666;
            margin-top: 2px;
        }

        /* Montant */
        .montant-box {
            border: 3px double #000000;
            padding: 15px;
            margin: 15px 0;
        }

        .montant-row-main {
            width: 100%;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #cccccc;
        }

        .montant-row-main td {
            vertical-align: middle;
        }

        .montant-label-text {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .montant-value-text {
            font-size: 28px;
            font-weight: bold;
            text-align: right;
        }

        .montant-lettres {
            font-size: 13px;
            color: #333333;
            line-height: 1.6;
            font-style: italic;
        }

        /* Signatures */
        .signature-section {
            margin-top: 25px;
        }

        .signature-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
        }

        .signature-box {
            text-align: center;
            padding: 15px;
            border: 1px solid #cccccc;
            background: #f5f5f5;
            vertical-align: top;
        }

        .signature-label {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .signature-label-sub {
            font-size: 11px;
            color: #666666;
            font-style: italic;
            margin-bottom: 30px;
        }

        .signature-space {
            height: 40px;
            margin: 15px 0 10px 0;
        }

        .signature-name {
            font-size: 12px;
            color: #666666;
            margin-bottom: 5px;
        }

        .signature-date {
            font-size: 11px;
            color: #666666;
        }

        /* Note officielle */
        .official-note {
            margin-top: 15px;
            padding: 15px;
            background: #eeeeee;
            border-left: 4px solid #000000;
            font-size: 11px;
            line-height: 1.6;
        }

        /* Pied de page */
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #666666;
        }

        .confidential {
            margin-top: 5px;
            font-style: italic;
            color: #000000;
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
                            Service Comptabilite et Finances<br>
                            BP 123 - Cotonou<br>
                            Tel: +229 21 XX XX XX
                        </div>
                    </td>
                    <td class="document-ref" style="width: 35%;">
                        <div class="ref-number">N&deg; {{ str_pad($reglement->id, 6, '0', STR_PAD_LEFT) }}</div>
                        <div class="ref-year">Annee {{ \Carbon\Carbon::parse($reglement->date_reglement)->format('Y') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="document-title-section">
            <h1>MANDAT DE PAIEMENT</h1>
            <div class="document-subtitle">Ordre de paiement aux fournisseurs</div>
        </div>

        <div class="document-date">
            Cotonou, le {{ \Carbon\Carbon::parse($reglement->date_reglement)->locale('fr')->isoFormat('D MMMM YYYY') }}
        </div>

        <!-- Corps du mandat -->
        <div class="document-body">
            <!-- Objet -->
            <div class="objet-section">
                <div class="objet-title">OBJET :</div>
                <div class="objet-content">
                    Reglement de la facture N&deg; <strong>{{ $reglement->facture->numero_piece }}</strong>
                </div>
            </div>

            <!-- Bénéficiaire -->
            <div class="section">
                <h2>BENEFICIAIRE</h2>
                <table class="info-table">
                    <tr>
                        <td class="label">Raison sociale :</td>
                        <td class="value"><strong>{{ $reglement->fournisseur->nom }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Code fournisseur :</td>
                        <td class="value">FOUR{{ str_pad($reglement->fournisseur->id, 3, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                    @if($reglement->fournisseur->ifu)
                    <tr>
                        <td class="label">IFU :</td>
                        <td class="value">{{ $reglement->fournisseur->ifu }}</td>
                    </tr>
                    @endif
                    @if($reglement->fournisseur->rccm)
                    <tr>
                        <td class="label">RCCM :</td>
                        <td class="value">{{ $reglement->fournisseur->rccm }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            <!-- Détails du paiement -->
            <div class="section">
                <h2>DETAILS DU PAIEMENT</h2>
                <table class="info-table">
                    <tr>
                        <td class="label">Facture N&deg; :</td>
                        <td class="value"><strong>{{ $reglement->facture->numero_piece }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Date de reglement :</td>
                        <td class="value">{{ \Carbon\Carbon::parse($reglement->date_reglement)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Mode de paiement :</td>
                        <td class="value"><strong>{{ strtoupper($modeLabel) }}</strong></td>
                    </tr>
                    @if($reglement->reference)
                    <tr>
                        <td class="label">Reference :</td>
                        <td class="value">{{ $reglement->reference }}</td>
                    </tr>
                    @endif
                    @if($reglement->banque)
                    <tr>
                        <td class="label">Compte bancaire :</td>
                        <td class="value">
                            <strong>{{ $reglement->banque }}</strong>
                            @if($reglement->numero_compte_bancaire)
                            <div class="value-sub">{{ $reglement->numero_compte_bancaire }}</div>
                            @endif
                        </td>
                    </tr>
                    @endif
                </table>
            </div>

            <!-- Montant -->
            <div class="montant-box">
                <table class="montant-row-main">
                    <tr>
                        <td class="montant-label-text">MONTANT A PAYER :</td>
                        <td class="montant-value-text">{{ $montantFormate }}</td>
                    </tr>
                </table>
                <div class="montant-lettres">
                    Arrete le present mandat a la somme de : <strong>{{ $montantEnLettres }}</strong>
                </div>
            </div>

            <!-- Visas et signatures -->
            <div class="signature-section">
                <table class="signature-table">
                    <tr>
                        <td class="signature-box" style="width: 50%;">
                            <div class="signature-label">Le Comptable</div>
                            <div class="signature-space"></div>
                            <div class="signature-name">Nom et signature</div>
                            <div class="signature-date">Date : ___/___/______</div>
                        </td>
                        <td class="signature-box" style="width: 50%;">
                            <div class="signature-label">Le Directeur des Affaires Financieres</div>
                            <div class="signature-space"></div>
                            <div class="signature-name">Nom et signature</div>
                            <div class="signature-date">Date : ___/___/______</div>
                        </td>
                    </tr>
                </table>

                <table class="signature-table" style="margin-top: 15px;">
                    <tr>
                        <td style="width: 20%;"></td>
                        <td class="signature-box" style="width: 60%;">
                            <div class="signature-label">Le Directeur General</div>
                            <div class="signature-label-sub">(Approbation et autorisation de paiement)</div>
                            <div class="signature-space"></div>
                            <div class="signature-name">Nom et signature</div>
                            <div class="signature-date">Date : ___/___/______</div>
                        </td>
                        <td style="width: 20%;"></td>
                    </tr>
                </table>
            </div>

            <!-- Note officielle -->
            <div class="official-note">
                <strong>NOTE :</strong> Ce mandat autorise le paiement de la somme mentionnee ci-dessus au beneficiaire designe.
                Tout paiement effectue sans ce mandat dument signe et valide sera considere comme irregulier.
            </div>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <div>Document genere le {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('D MMMM YYYY') }}</div>
            <div class="confidential">Document confidentiel - Usage interne uniquement</div>
        </div>
    </div>
</body>
</html>
