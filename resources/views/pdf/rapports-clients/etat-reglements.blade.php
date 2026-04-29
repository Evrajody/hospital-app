@extends('pdf.rapports._layout-rapport')

@section('title', 'État des Règlements Clients')
@section('page-size', 'A4 portrait')
@section('page-margin', '20mm 25mm')
@section('report-title', $titre ?? 'ÉTAT DES RÈGLEMENTS CLIENTS')

@if($mode === 'un_client')
    @section('report-subtitle', 'Règlements d\'un client')
@elseif($mode === 'tous_clients')
    @section('report-subtitle', 'Récapitulatif tous clients' . (!empty($periode['debut']) && !empty($periode['fin']) ? ' - Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') : ''))
@else
    @section('report-subtitle', 'Règlements par client')
@endif

@section('extra-styles')
    .client-block { margin-bottom: 25px; page-break-inside: avoid; }
    .client-header { border: 1px solid #000; padding: 8px 12px; margin-bottom: 10px; font-size: 11px; }
@endsection

@section('content')
    @if(count($data) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
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
                            <th>Date Règlement</th>
                            <th class="montant" style="width: 100px">Montant Facture</th>
                            <th class="montant" style="width: 100px">Montant Payé</th>
                            <th class="montant" style="width: 80px">Rejet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clientData['lignes'] as $ligne)
                            <tr>
                                <td><strong>{{ $ligne['numero'] }}</strong></td>
                                <td>{{ $ligne['reference'] }}</td>
                                <td>{{ $ligne['date_facture'] }}</td>
                                <td>{{ $ligne['date_reglement'] }}</td>
                                <td class="montant">{{ number_format($ligne['montant_facture'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['montant_paye'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($ligne['rejet'], 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="4" class="total-label">Total :</td>
                            <td class="montant">{{ number_format($clientData['total_facture'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($clientData['total_paye'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($clientData['total_rejet'], 0, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach

        @if(count($data) > 1)
            @php
                $grandFacture = collect($data)->sum('total_facture');
                $grandPaye = collect($data)->sum('total_paye');
                $grandRejet = collect($data)->sum('total_rejet');
            @endphp
            <table class="report-table" style="margin-top: 20px;">
                <tfoot>
                    <tr class="total-row">
                        <td class="total-label" style="text-align: right;">TOTAL GÉNÉRAL :</td>
                        <td class="montant" style="width: 120px;">{{ number_format($grandFacture, 0, ',', ' ') }}</td>
                        <td class="montant" style="width: 120px;">{{ number_format($grandPaye, 0, ',', ' ') }}</td>
                        <td class="montant" style="width: 100px;">{{ number_format($grandRejet, 0, ',', ' ') }}</td>
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
                    <th class="montant">Montant Factures</th>
                    <th class="montant">Montant Règlements</th>
                    <th class="montant">Rejet</th>
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
                        <td class="montant">{{ number_format($row['total_rejet'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $grandFacture = collect($data)->sum('total_facture');
                    $grandPaye = collect($data)->sum('total_paye');
                    $grandRejet = collect($data)->sum('total_rejet');
                @endphp
                <tr class="total-row">
                    <td colspan="3" class="total-label">TOTAL GÉNÉRAL</td>
                    <td class="montant">{{ number_format($grandFacture, 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandPaye, 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandRejet, 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
