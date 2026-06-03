{{-- Bordereau de versement AIB — reproduction conforme du formulaire officiel DGID.
     Template AUTONOME (n'étend PAS _layout-rapport) : pas d'en-tête hôpital, pas de
     cadre titre, pas de pied de page "Édité par". Tient sur une seule page A4 portrait. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau de versement AIB</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
            padding: 12mm 15mm;
        }
        .header-admin { text-align: center; font-size: 12px; line-height: 1.6; }
        .header-admin .bold { font-weight: bold; }
        .header-admin .sep { font-size: 10px; color: #666; }
        .titre-encadre { border: 1px solid #000; padding: 10px 15px; text-align: center; font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .annee-mois { width: 100%; border: none; margin: 12px 0 10px; }
        .annee-mois td { border: none; font-size: 13px; }
        .section-header { border: 1px solid #000; background: #c9c9c9; padding: 6px 10px; text-align: center; font-weight: bold; font-size: 12px; text-transform: uppercase; }
        .section-content { border: 1px solid #000; border-top: none; padding: 12px 15px; }
        .info-line { margin: 4px 0; font-size: 11px; }
        .info-line strong { font-weight: bold; }
        table.droits { width: 100%; border-collapse: collapse; font-size: 11px; margin: 0; }
        table.droits th { background: #fff; border: 1px solid #000; padding: 6px; text-align: center; font-weight: bold; }
        table.droits td { border: 1px solid #000; padding: 5px 8px; }
        table.droits .montant { text-align: right; font-family: 'Courier New', monospace; }
        table.droits .cat-header td { font-weight: bold; font-size: 11px; background: #e2e2e2; }
        table.droits .total-row td { font-weight: bold; font-size: 12px; border-top: 1px solid #000; }
        table.droits .lettres-row td { font-size: 10px; font-style: italic; padding: 8px; }
        .paiement-section { border-bottom: none; }
        .paiement-line { margin: 8px 0; font-size: 11px; }
        .paiement-line .box { display: inline-block; width: 20px; height: 14px; border: 1px solid #000; vertical-align: middle; margin: 0 8px; }
        .paiement-line .underline { display: inline-block; border-bottom: 1px solid #000; min-width: 150px; }
        .signature-table { width: 100%; border-collapse: collapse; }
        .signature-table td { border: 1px solid #000; padding: 10px 12px; vertical-align: top; font-size: 10px; height: 120px; }
    </style>
</head>
<body>
    @php
        // Le template étant autonome (sans le layout commun), on récupère ici l'établissement.
        $etablissement = $etablissement ?? \App\Models\Setting::getEtablissement();
    @endphp
    {{-- EN-TETE ADMINISTRATIF --}}
    <table style="width: 100%; border: none;">
        <tr>
            <td style="width: 40%; border: none; vertical-align: top;">
                <div class="header-admin">
                    <div class="bold">R&eacute;publique du B&eacute;nin</div>
                    <div class="sep">-----------------</div>
                    <div class="bold">Minist&egrave;re des Finances</div>
                    <div class="sep">-----------------</div>
                    <div class="bold">Direction G&eacute;n&eacute;rale des Imp&ocirc;ts<br>et des Domaines</div>
                    <div class="sep">-----------------</div>
                </div>
            </td>
            <td style="width: 60%; border: none; vertical-align: top; padding-top: 10px;">
                <div class="titre-encadre">
                    BORDEREAU DE VERSEMENT DES PRELEVEMENTS D'ACOMPTE IMPUTABLE SUR L'IMP&Ocirc;T ASSIS SUR LES BENEFICES
                </div>
            </td>
        </tr>
    </table>

    <table class="annee-mois">
        <tr>
            <td>
                <strong>Ann&eacute;e</strong>&nbsp;&nbsp;&nbsp;<strong>{{ $annee ?? \Carbon\Carbon::parse($dateDebut)->format('Y') }}</strong>
            </td>
            <td style="text-align: right;">
                @php
                    $moisNoms = ['', 'JANVIER', 'FÉVRIER', 'MARS', 'AVRIL', 'MAI', 'JUIN', 'JUILLET', 'AOÛT', 'SEPTEMBRE', 'OCTOBRE', 'NOVEMBRE', 'DÉCEMBRE'];
                @endphp
                <strong>Mois</strong>&nbsp;&nbsp;&nbsp;<strong>{{ $mois ? ($moisNoms[(int)$mois] ?? '') : \Carbon\Carbon::parse($dateDebut)->translatedFormat('F') }}</strong>
            </td>
        </tr>
    </table>

    {{-- I- IDENTIFICATION DU REDEVABLE --}}
    <div class="section-header">I- IDENTIFICATION DU REDEVABLE</div>
    <div class="section-content">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; width: 65%; vertical-align: top;">
                    <div class="info-line"><strong>Nom ou Raison sociale :</strong>&nbsp;&nbsp;<strong>{{ $etablissement['nom'] ?? '' }}</strong></div>
                    <div class="info-line"><strong>Activit&eacute; :</strong></div>
                    <div class="info-line"><strong>Adresse :</strong>&nbsp;&nbsp;{{ $etablissement['adresse'] ?? '' }}</div>
                    <div class="info-line"><strong>T&eacute;l&eacute;phone :</strong>&nbsp;&nbsp;{{ $etablissement['telephone'] ?? '' }}</div>
                    <div class="info-line"><strong>N&deg; IFU :</strong>&nbsp;&nbsp;<strong>6201200887407</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>N&deg; INSAE :</strong></div>
                    <div class="info-line" style="margin-top: 8px;">
                        <strong>P&eacute;riode allant du</strong>&nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }}</strong>
                        &nbsp;&nbsp;&nbsp;au&nbsp;&nbsp;&nbsp;
                        <strong>{{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</strong>
                    </div>
                </td>
                <td style="border: none; width: 35%; vertical-align: top; text-align: center;">
                    <div style="border: 1px solid #999; padding: 20px 10px; font-size: 10px; color: #666; font-style: italic; margin-top: 5px;">
                        Cachet de la Recette<br>des Imp&ocirc;ts
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- II- LIQUIDATION DES DROITS --}}
    <div class="section-header" style="margin-top: 12px;">II- LIQUIDATION DES DROITS</div>
    <table class="droits">
        <thead>
            <tr>
                <th style="width: 55%">NATURE</th>
                <th style="width: 20%">BASE</th>
                <th style="width: 25%">MONTANT</th>
            </tr>
        </thead>
        <tbody>
            {{-- Achats de marchandises --}}
            <tr class="cat-header"><td colspan="3">Achats de marchandises</td></tr>
            <tr>
                <td>AIB de %</td>
                <td class="montant">-</td>
                <td class="montant">-</td>
            </tr>

            {{-- Prestations de services --}}
            <tr class="cat-header"><td colspan="3">Prestations de services</td></tr>
            @foreach($parTaux as $tauxData)
                <tr>
                    <td>AIB de {{ number_format($tauxData['taux'], 0) }}%</td>
                    <td class="montant">{{ $tauxData['base'] > 0 ? number_format($tauxData['base'], 0, ',', ' ') : '' }}</td>
                    <td class="montant">{{ number_format($tauxData['montant'], 0, ',', ' ') }}</td>
                </tr>
            @endforeach

            {{-- Total --}}
            <tr class="total-row">
                <td>Montant total &agrave; reverser</td>
                <td class="montant"></td>
                <td class="montant">{{ number_format($montantTotal, 0, ',', ' ') }}</td>
            </tr>

            {{-- Montant en lettres (même cadre que la liquidation) --}}
            <tr class="lettres-row">
                <td colspan="3">Montant du versement en lettres : {{ $montantEnLettres }}</td>
            </tr>
        </tbody>
    </table>

    {{-- III- PAIEMENT --}}
    <div class="section-header" style="margin-top: 12px;">III-PAIEMENT (Obligatoirement joint &agrave; la d&eacute;claration)</div>
    <div class="section-content paiement-section">
        <div class="paiement-line">
            Esp&egrave;ces <span class="box"></span>
        </div>
        <div class="paiement-line">
            Ch&egrave;que <span class="box"></span>
            &nbsp;&nbsp;&nbsp;&nbsp;Banque: <span class="underline">&nbsp;</span>
            &nbsp;&nbsp;&nbsp;&nbsp;N&deg; Ch&egrave;que: <span class="underline">&nbsp;</span>
        </div>
        <div class="paiement-line">
            Virement <span class="box"></span>
            &nbsp;&nbsp;&nbsp;&nbsp;Banque: <span class="underline">&nbsp;</span>
            &nbsp;&nbsp;&nbsp;&nbsp;R&eacute;f. Virement: <span class="underline">&nbsp;</span>
        </div>
    </div>

    {{-- SIGNATURES (rattachées directement au cadre paiement) --}}
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <strong>CHEF SERVICE FINANCIER ET COMPTABLE</strong><br><br>
                <strong>A&nbsp;&nbsp;&nbsp;&nbsp;COTONOU</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;le&nbsp;&nbsp;&nbsp;&nbsp;___/___/______<br><br>
                Signature et Cachet
            </td>
            <td style="width: 50%;">
                <strong>Cadre r&eacute;serv&eacute; &agrave; l'Administration</strong><br>
                <div style="text-align: center; margin: 3px 0;">N&deg; de Quittance:</div>
                <div style="text-align: center; margin: 3px 0;">Date d'&eacute;mission:</div>
                <br>
                P&eacute;nalit&eacute;s<br>
                <div style="margin-left: 30px;">Motif:</div>
                <div style="margin-left: 30px;">Montant:</div>
                Montant total per&ccedil;u:
            </td>
        </tr>
    </table>
</body>
</html>
