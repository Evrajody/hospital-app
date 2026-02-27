<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Situation des Fournisseurs</title>
    <style>
        @page { size: A4 {{ $mode === 'par_fournisseur' ? 'landscape' : 'portrait' }}; margin: 15mm 25mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; color: #000000; line-height: 1.4; padding: 10mm 10mm; }
        .title-box { text-align: center; margin: 20px 0 25px; }
        .title-box h1 { font-size: 16px; font-weight: bold; text-decoration: underline; margin: 0; }
        .separator { width: 120px; height: 3px; background: #000000; margin: 15px auto; }
        .compte-header { font-size: 12px; margin: 20px 0 10px; font-style: italic; }
        .fournisseur-header { font-size: 12px; margin: 15px 0 8px; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { border-top: 2px solid #000000; border-bottom: 2px solid #000000; padding: 8px 6px; text-align: left; font-weight: bold; font-size: 10px; }
        td { border-bottom: 1px solid #cccccc; padding: 6px; }
        .montant { text-align: right; font-family: 'Courier New', monospace; }
        .restant { color: #000000; font-weight: bold; }
        .total-row { border-top: 2px solid #000000; font-weight: bold; }
        .total-row td { border-bottom: 2px solid #000000; padding: 8px 6px; font-size: 11px; }
        .total-label { text-align: right; text-transform: uppercase; }
        .grand-total-row { border-top: 3px solid #000000; font-weight: bold; }
        .grand-total-row td { border-bottom: 3px solid #000000; padding: 10px 6px; font-size: 12px; color: #cc0000; }
        .footer-section { margin-top: 40px; font-size: 10px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div style="font-size: 16px; font-weight: bold; margin-bottom: 20px;">HÔPITAL DE MÉNONTIN</div>

    <div class="title-box">
        <h1>{{ $titre ?? 'Situation des fournisseurs (point des dettes)' }}{{ $date ? ' au ' . \Carbon\Carbon::parse($date)->format('d/m/Y') : '' }}</h1>
    </div>
    <div class="separator"></div>

    @if($mode === 'tous')
        @if(count($data) === 0)
            <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px">N&deg;</th>
                        <th>Raison sociale</th>
                        <th class="montant" style="width: 130px">Montant total d&ucirc;</th>
                        <th class="montant" style="width: 130px">Montant total des r&egrave;glements</th>
                        <th class="montant restant" style="width: 120px">Restant d&ucirc;</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                        <tr>
                            <td><strong>{{ $row['numero'] }}</strong></td>
                            <td>{{ $row['raison_sociale'] }}</td>
                            <td class="montant">{{ number_format($row['montant_du'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($row['montant_reglements'], 0, ',', ' ') }}</td>
                            <td class="montant restant">{{ number_format($row['restant_du'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2" class="total-label">TOTAL G&Eacute;N&Eacute;RAL</td>
                        <td class="montant">{{ number_format($grandTotal['montant_du'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotal['montant_reglements'], 0, ',', ' ') }}</td>
                        <td class="montant restant">{{ number_format($grandTotal['restant_du'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif

    @elseif($mode === 'par_compte')
        @if(count($data) === 0)
            <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
        @else
            @if(!empty($compte))
                <div class="compte-header">
                    <strong>Compte :</strong> <em>{{ $compte['numero_compte'] }} {{ $compte['libelle'] }}</em>
                </div>
            @endif
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px">N&deg;</th>
                        <th>Raison sociale</th>
                        <th class="montant" style="width: 130px">Montant total d&ucirc;</th>
                        <th class="montant" style="width: 130px">Montant total des r&egrave;glements</th>
                        <th class="montant restant" style="width: 120px">Restant d&ucirc;</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                        <tr>
                            <td><strong>{{ $row['numero'] }}</strong></td>
                            <td>[{{ $row['numero_compte'] }}] {{ $row['raison_sociale'] }}</td>
                            <td class="montant">{{ number_format($row['montant_du'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($row['montant_reglements'], 0, ',', ' ') }}</td>
                            <td class="montant restant">{{ number_format($row['restant_du'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2" class="total-label">TOTAL G&Eacute;N&Eacute;RAL</td>
                        <td class="montant">{{ number_format($grandTotal['montant_du'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotal['montant_reglements'], 0, ',', ' ') }}</td>
                        <td class="montant restant">{{ number_format($grandTotal['restant_du'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif

    @elseif($mode === 'par_fournisseur')
        @if(count($data) === 0)
            <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
        @else
            @foreach($data as $fData)
                <div class="fournisseur-header">
                    <strong>Fournisseur :</strong> {{ $fData['fournisseur'] }}
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 70px">N&deg;PC</th>
                            <th style="width: 65px">Date PC</th>
                            <th style="width: 80px">R&eacute;f. Fact.</th>
                            <th class="montant">Mt Fact.</th>
                            <th class="montant">Avoir</th>
                            <th class="montant">Mt M.O.</th>
                            <th class="montant" style="width: 45px">AIB (%)</th>
                            <th class="montant">Mt AIB</th>
                            <th class="montant">Mt D&ucirc;</th>
                            <th class="montant">Total R&egrave;g.</th>
                            <th class="montant">Solde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fData['lignes'] as $ligne)
                            <tr>
                                <td>{{ $ligne['numero_piece'] }}</td>
                                <td>{{ $ligne['date'] }}</td>
                                <td>{{ $ligne['reference_facture'] }}</td>
                                <td class="montant">{{ number_format($ligne['montant_facture'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['avoir'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['montant_mo'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ $ligne['taux_aib'] ? number_format($ligne['taux_aib'], 1) . '%' : '0%' }}</td>
                                <td class="montant">{{ number_format($ligne['montant_aib'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['montant_du'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['total_reglement'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['solde'], 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" class="total-label">TOTAL :</td>
                            <td class="montant">{{ number_format($fData['totaux']['montant_facture'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($fData['totaux']['avoir'], 0, ',', ' ') }}</td>
                            <td class="montant"></td>
                            <td class="montant"></td>
                            <td class="montant"></td>
                            <td class="montant">{{ number_format($fData['totaux']['montant_du'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($fData['totaux']['total_reglement'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($fData['totaux']['solde'], 0, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endforeach

            <table style="margin-top: 20px;">
                <tfoot>
                    <tr class="grand-total-row">
                        <td colspan="3" class="total-label">TOTAL G&Eacute;N&Eacute;RAL :</td>
                        <td class="montant">{{ number_format($grandTotaux['montant_facture'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotaux['avoir'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotaux['montant_mo'], 0, ',', ' ') }}</td>
                        <td class="montant"></td>
                        <td class="montant">{{ number_format($grandTotaux['montant_aib'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotaux['montant_du'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotaux['total_reglement'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($grandTotaux['solde'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    @endif

    <table class="footer-section" style="width: 100%; border: none; margin-top: 40px;">
        <tr>
            <td style="border: none; font-style: italic;">
                &Eacute;dit&eacute; par {{ $generatedBy ?? 'Utilisateur' }} - {{ $generatedAt ?? now()->format('d/m/Y à H:i') }}
            </td>
            <td style="border: none; text-align: right;">
                Page <span class="page-num"></span>
            </td>
        </tr>
    </table>
</body>
</html>
