<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>États Périodiques des Créances Clients</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 25mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; color: #000000; line-height: 1.4; padding: 10mm 10mm; }
        .title-box { text-align: center; margin: 20px 0; padding: 14px; border: 3px double #000000; }
        .title-box h1 { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
        .title-box .subtitle { font-size: 11px; font-style: italic; margin-top: 4px; }
        .title-box .periode { font-size: 11px; margin-top: 6px; }
        .client-block { margin-bottom: 25px; page-break-inside: avoid; }
        .client-header { border: 2px solid #000000; padding: 8px 12px; margin-bottom: 10px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #eeeeee; border: 1px solid #000000; padding: 6px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        td { border: 1px solid #cccccc; padding: 5px 6px; }
        .montant { text-align: right; font-family: 'Courier New', monospace; }
        .reste { color: #cc0000; font-weight: bold; }
        .total-row { background: #eeeeee; border-top: 2px solid #000000; font-weight: bold; }
        .total-row td { border: 1px solid #000000; font-size: 11px; }
        .total-label { text-align: right; text-transform: uppercase; }
    </style>
</head>
<body>
    @include('pdf.rapports-clients._header')

    <div class="title-box">
        <h1>{{ $titre ?? 'ÉTATS PÉRIODIQUES DES CRÉANCES CLIENTS' }}</h1>
        <div class="subtitle">Factures non soldées</div>
        @if($mode === 'tous_clients' && !empty($periode['debut']) && !empty($periode['fin']))
            <div class="periode">Période du {{ \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') }}</div>
        @endif
    </div>

    @if(count($data) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune créance trouvée.</p>
    @elseif($mode === 'par_client' || $mode === 'un_client')
        @foreach($data as $clientData)
            <div class="client-block">
                <div class="client-header">
                    <strong><u>N° Compte :</u></strong> {{ $clientData['numero_compte'] }}
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <strong><u>Raison sociale :</u></strong> {{ $clientData['raison_sociale'] }}
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30px">N°</th>
                            <th>Réf. Facture</th>
                            <th>Date Facture</th>
                            <th class="montant" style="width: 110px">Montant Facture</th>
                            <th class="montant" style="width: 110px">Montant Payé</th>
                            <th class="montant reste" style="width: 100px">Reste à Payer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clientData['lignes'] as $ligne)
                            <tr>
                                <td><strong>{{ $ligne['numero'] }}</strong></td>
                                <td>{{ $ligne['reference'] }}</td>
                                <td>{{ $ligne['date_facture'] }}</td>
                                <td class="montant">{{ number_format($ligne['montant_facture'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['montant_paye'], 0, ',', ' ') }}</td>
                                <td class="montant reste">{{ number_format($ligne['reste_a_payer'], 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" class="total-label">Total :</td>
                            <td class="montant">{{ number_format($clientData['total_facture'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($clientData['total_paye'], 0, ',', ' ') }}</td>
                            <td class="montant reste">{{ number_format($clientData['total_reste'], 0, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach

        @if(count($data) > 1)
            @php
                $grandFacture = collect($data)->sum('total_facture');
                $grandPaye = collect($data)->sum('total_paye');
                $grandReste = collect($data)->sum('total_reste');
            @endphp
            <table style="margin-top: 20px;">
                <tfoot>
                    <tr class="total-row">
                        <td class="total-label" style="text-align: right;">TOTAL GÉNÉRAL :</td>
                        <td class="montant" style="width: 130px;">{{ number_format($grandFacture, 0, ',', ' ') }}</td>
                        <td class="montant" style="width: 130px;">{{ number_format($grandPaye, 0, ',', ' ') }}</td>
                        <td class="montant reste" style="width: 120px;">{{ number_format($grandReste, 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    @elseif($mode === 'tous_clients')
        <table>
            <thead>
                <tr>
                    <th style="width: 30px">N°</th>
                    <th style="width: 90px">N° Compte</th>
                    <th>Raison sociale</th>
                    <th class="montant">Total Factures</th>
                    <th class="montant">Total Règlements</th>
                    <th class="montant reste">Reste à Payer</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                    <tr>
                        <td><strong>{{ $row['numero'] }}</strong></td>
                        <td>{{ $row['numero_compte'] }}</td>
                        <td><strong>{{ $row['raison_sociale'] }}</strong></td>
                        <td class="montant">{{ number_format($row['total_facture'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($row['total_paye'], 0, ',', ' ') }}</td>
                        <td class="montant reste">{{ number_format($row['total_reste'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $grandFacture = collect($data)->sum('total_facture');
                    $grandPaye = collect($data)->sum('total_paye');
                    $grandReste = collect($data)->sum('total_reste');
                @endphp
                <tr class="total-row">
                    <td colspan="3" class="total-label">TOTAL GÉNÉRAL</td>
                    <td class="montant">{{ number_format($grandFacture, 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandPaye, 0, ',', ' ') }}</td>
                    <td class="montant reste">{{ number_format($grandReste, 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    @include('pdf.rapports-clients._footer')
</body>
</html>
