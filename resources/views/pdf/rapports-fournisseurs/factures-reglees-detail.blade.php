{{-- État des factures réglées — DÉTAIL : un fournisseur par page.
     Template AUTONOME : chaque fournisseur est sur sa propre page, avec l'en-tête
     établissement (sans logo, centré) + le titre repris en haut de chaque page. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ ($titre ?? 'Etat des factures réglées') . ' - Détail' }}</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* La marge est portée par le BODY (conteneur racine du flux) : dompdf la
           réapplique sur CHAQUE page, y compris les pages de continuation d'un
           tableau qui déborde et les sauts de page entre fournisseurs.
           (Une marge sur un bloc enfant ou sur @page n'est PAS reprise en continuation.) */
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #000; line-height: 1.4; padding: 14mm 14mm 20mm; }

        .fournisseur-page + .fournisseur-page { page-break-before: always; }

        .header-section { width: 100%; margin-bottom: 8px; }
        .header-section td { border: none; padding: 0; vertical-align: middle; }
        .hospital-name { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .hospital-info { font-size: 10px; color: #333; line-height: 1.6; margin-top: 4px; }

        .report-title-wrapper { text-align: center; margin: 12px 0; }
        .report-title { display: inline-block; text-align: center; padding: 8px 18px; border: 1px solid #000; }
        .report-title h1 { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
        .report-title .sous-titre { font-size: 10px; margin-top: 4px; font-style: italic; }

        .fournisseur-label { font-size: 12px; margin: 14px 0 6px; }

        table.report-table { width: 100%; max-width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 10px; margin-top: 6px; }
        table.report-table th { background: #fff; border: 1px solid #000; padding: 6px 4px; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 9px; word-wrap: break-word; }
        table.report-table td { background: #fff; border: 1px solid #000; padding: 5px 4px; word-wrap: break-word; }
        table.report-table .montant { text-align: right; }
        table.report-table .col-num, table.report-table .col-date { text-align: center; }
        table.report-table .total-row td { font-weight: bold; border: 1px solid #000; }
        table.report-table .total-label { text-align: right; text-transform: uppercase; }
        table.report-table tr.soldee-row td { background-color: #fff3e0; }
        table.report-table td.deficit { color: #b91c1c; font-weight: bold; }

        .soldee-legende { font-size: 9px; color: #92400e; margin: 4px 0 8px; }
        .soldee-legende .swatch { display: inline-block; width: 9px; height: 9px; background: #fff3e0; border: 1px solid #fb923c; vertical-align: middle; }

        .footer-section { position: fixed; bottom: 8mm; left: 0; right: 0; font-size: 10px; padding: 0 14mm; color: #666; font-style: italic; }
        .footer-section td { border: none; padding: 4px 0; }
        .page-num:after { content: counter(page); }
    </style>
</head>
<body>
    @php
        $etablissement = $etablissement ?? \App\Models\Setting::getEtablissement();
    @endphp

    @if(count($detail ?? []) === 0)
        <div class="fournisseur-page">
            <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
        </div>
    @else
        @foreach($detail as $fData)
            <div class="fournisseur-page">
                @if($loop->first)
                {{-- En-tête officielle (identique au bordereau de paiement) — uniquement sur la 1ère page --}}
                @include('pdf._entete-officiel')

                {{-- Titre du rapport --}}
                <div class="report-title-wrapper">
                    <div class="report-title">
                        <h1>{{ $titre ?? 'Etat des factures réglées' }}</h1>
                        <div class="sous-titre">(Détail par fournisseur)</div>
                    </div>
                </div>
                @endif

                @if(!empty($fData['has_soldee']))
                    <div class="soldee-legende"><span class="swatch"></span> Lignes en orange = factures marquées comme soldées (clôturées sans règlement) ; « Mt Total Rég. » en rouge = déficit.</div>
                @endif

                <div class="fournisseur-label">
                    <strong>Fournisseur :</strong> {{ $fData['fournisseur'] }}
                </div>

                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 70px">N°PC</th>
                            <th>Libellé facture</th>
                            <th style="width: 65px">Date PC</th>
                            <th style="width: 65px">Date Règ.</th>
                            <th class="montant">Mt TTC</th>
                            <th class="montant">Avoir</th>
                            <th class="montant">Mt M.O.</th>
                            <th class="montant" style="width: 45px">AIB (%)</th>
                            <th class="montant">Mt AIB</th>
                            <th class="montant">Rég. Période</th>
                            <th class="montant">Mt Total Rég.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fData['lignes'] as $ligne)
                            <tr class="{{ !empty($ligne['marquee_soldee']) ? 'soldee-row' : '' }}">
                                <td>{{ $ligne['numero_piece'] }}{{ !empty($ligne['marquee_soldee']) ? ' (soldée)' : '' }}</td>
                                <td>{{ $ligne['libelle'] }}</td>
                                <td>{{ $ligne['date'] }}</td>
                                <td>{{ $ligne['date_reglement'] }}</td>
                                <td class="montant">{{ number_format($ligne['montant_facture'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['avoir'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['montant_mo'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ $ligne['taux_aib'] ? number_format($ligne['taux_aib'], 1) . '%' : '' }}</td>
                                <td class="montant">{{ number_format($ligne['montant_aib'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['reg_periode'], 0, ',', ' ') }}</td>
                                <td class="montant {{ !empty($ligne['marquee_soldee']) ? 'deficit' : '' }}">{{ number_format($ligne['mt_total_reg'], 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="4" class="total-label">Total Fournisseur :</td>
                            <td class="montant">{{ number_format($fData['totaux']['montant_facture'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($fData['totaux']['avoir'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($fData['totaux']['montant_mo'], 0, ',', ' ') }}</td>
                            <td class="montant"></td>
                            <td class="montant">{{ number_format($fData['totaux']['montant_aib'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($fData['totaux']['reg_periode'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($fData['totaux']['mt_total_reg'], 0, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                </table>

                @if($loop->last && count($detail) > 1)
                    <table class="report-table" style="margin-top: 14px;">
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="4" class="total-label">TOTAL GÉNÉRAL :</td>
                                <td class="montant">{{ number_format($grandTotaux['montant_facture'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($grandTotaux['avoir'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($grandTotaux['montant_mo'], 0, ',', ' ') }}</td>
                                <td class="montant"></td>
                                <td class="montant">{{ number_format($grandTotaux['montant_aib'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($grandTotaux['reg_periode'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($grandTotaux['mt_total_reg'], 0, ',', ' ') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        @endforeach
    @endif

    {{-- Pied de page répété sur chaque page --}}
    <table class="footer-section">
        <tr>
            <td>Édité par {{ $generatedBy ?? 'Utilisateur' }} - {{ $generatedAt ?? now()->format('d/m/Y à H:i') }}</td>
            <td style="text-align: right;">Page <span class="page-num"></span></td>
        </tr>
    </table>
</body>
</html>
