@extends('pdf.rapports._layout-rapport')

@section('title', 'États Périodiques des Créances Clients')
@section('page-size', 'A4 portrait')
@section('page-margin', '15mm 25mm')
@section('report-title', $titre ?? 'ÉTATS PÉRIODIQUES DES CRÉANCES CLIENTS')
@section('report-subtitle', 'Factures non soldées' . ($mode === 'tous_clients' && !empty($periode['debut']) && !empty($periode['fin']) ? ' - Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') : ''))

@section('extra-styles')
    .client-block { margin-bottom: 25px; page-break-inside: avoid; }
    .client-header { border: 1px solid #000; padding: 8px 12px; margin-bottom: 10px; font-size: 11px; }
    .reste { color: #cc0000; font-weight: bold; }
@endsection

@section('content')
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
                <table class="report-table">
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
            <table class="report-table" style="margin-top: 20px;">
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
        <table class="report-table">
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
@endsection
