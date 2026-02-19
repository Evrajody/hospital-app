<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Brouillard de Chèques</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 25mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; color: #000000; line-height: 1.4; padding: 10mm 10mm; }
        .title-box { text-align: center; margin: 20px 0; padding: 14px; border: 3px double #000000; }
        .title-box h1 { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
        .title-box .periode { font-size: 11px; margin-top: 6px; font-style: italic; }
        .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; padding: 8px 0; margin: 30px 0 10px; border-bottom: 2px solid #000000; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #eeeeee; border: 1px solid #000000; padding: 6px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        td { border: 1px solid #cccccc; padding: 5px 6px; }
        .montant { text-align: right; font-family: 'Courier New', monospace; }
        .solde { font-weight: bold; }
        .date-header { background: #f0f0f0; border-top: 2px solid #000000; }
        .total-row { background: #eeeeee; border-top: 2px solid #000000; font-weight: bold; }
        .total-row td { border: 1px solid #000000; font-size: 11px; }
        .total-label { text-align: right; text-transform: uppercase; }
        .compte-cell { font-family: 'Courier New', monospace; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    @include('pdf.rapports-clients._header')

    <div class="title-box">
        <h1>{{ $titre ?? 'BROUILLARD DE CHÈQUES' }}</h1>
        @if(!empty($periode['debut']) && !empty($periode['fin']))
            <div class="periode">Du {{ \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') }}</div>
        @endif
    </div>

    @if(count($data) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée pour cette période.</p>
    @else
        {{-- Brouillard de chèques groupé par date --}}
        @php
            $groups = [];
            $currentDate = null;
            foreach ($data as $entry) {
                if ($entry['date'] !== $currentDate) {
                    $currentDate = $entry['date'];
                    $groups[] = ['date' => $currentDate, 'entries' => []];
                }
                $groups[count($groups) - 1]['entries'][] = $entry;
            }
            $totalDebit = collect($data)->sum('debit');
            $totalCredit = collect($data)->sum('credit');
            $soldeGeneral = count($data) > 0 ? $data[count($data) - 1]['solde'] : 0;
        @endphp

        <table>
            <thead>
                <tr>
                    <th style="width: 80px">Date</th>
                    <th>Libellés</th>
                    <th class="montant" style="width: 100px">Débit</th>
                    <th class="montant" style="width: 100px">Crédit</th>
                    <th class="montant" style="width: 100px">Solde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $group)
                    <tr class="date-header">
                        <td><strong>{{ $group['date'] }}</strong></td>
                        <td colspan="4"></td>
                    </tr>
                    @foreach($group['entries'] as $entry)
                        <tr>
                            <td></td>
                            <td>{{ $entry['libelle'] }}</td>
                            <td class="montant">{{ $entry['debit'] ? number_format($entry['debit'], 0, ',', ' ') : '' }}</td>
                            <td class="montant">{{ $entry['credit'] ? number_format($entry['credit'], 0, ',', ' ') : '' }}</td>
                            <td class="montant solde">{{ number_format($entry['solde'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="total-label">TOTAUX</td>
                    <td class="montant">{{ number_format($totalDebit, 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($totalCredit, 0, ',', ' ') }}</td>
                    <td class="montant solde">{{ number_format($soldeGeneral, 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Imputations Comptables --}}
        <div class="section-title">IMPUTATIONS COMPTABLES</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 70px">Date</th>
                    <th style="width: 100px">N° Pièce</th>
                    <th>Libellé</th>
                    <th class="compte-cell" style="width: 80px">Cpte Débit</th>
                    <th class="compte-cell" style="width: 80px">Cpte Crédit</th>
                    <th class="montant" style="width: 100px">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $entry)
                    <tr>
                        <td>{{ $entry['date'] }}</td>
                        <td><strong>{{ $entry['reference'] }}</strong></td>
                        <td>{{ $entry['client_nom'] }} ({{ $entry['facture_ref'] }})</td>
                        <td class="compte-cell">{{ $entry['compte_debit'] }}</td>
                        <td class="compte-cell">{{ $entry['compte_credit'] }}</td>
                        <td class="montant">{{ number_format($entry['montant'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php $totalMontant = collect($data)->sum('montant'); @endphp
                <tr class="total-row">
                    <td colspan="5" class="total-label">TOTAL</td>
                    <td class="montant">{{ number_format($totalMontant, 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    @include('pdf.rapports-clients._footer')
</body>
</html>
