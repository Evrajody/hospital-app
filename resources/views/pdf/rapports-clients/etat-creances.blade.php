@extends('pdf.rapports._layout-rapport')

@php
    $typeSuffix = !empty($type_client_label ?? null) ? ' — ' . strtoupper($type_client_label) : '';
@endphp

@section('title', 'États Périodiques des Créances Clients')
@section('page-size', 'A4 portrait')
@section('page-margin', '22mm 25mm')
@section('report-title', ($titre ?? 'ÉTATS PÉRIODIQUES DES CRÉANCES CLIENTS') . $typeSuffix)
@section('report-subtitle', 'Factures non soldées' . (!empty($periode['debut']) && !empty($periode['fin']) ? ' - Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') : (!empty($periode['fin']) ? ' - Au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') : '')))

@section('extra-styles')
    .client-block { margin-bottom: 25px; page-break-inside: avoid; }
    .client-header { border: 1px solid #000; padding: 8px 12px; margin-bottom: 10px; font-size: 11px; }
    .reste { color: #cc0000; font-weight: bold; }
    .type-group + .type-group { page-break-before: always; }
    .type-header { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; padding: 8px 12px; background: #e5e7eb; border-left: 4px solid #1f2937; margin: 0 0 12px; page-break-after: avoid; }
    .type-inline { font-size: 10px; color: #444; }
    .type-inline em { font-style: italic; }
@endsection

@section('content')
    @if(count($data) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune créance trouvée.</p>
    @elseif($mode === 'par_client' || $mode === 'un_client')
        @php
            if ($mode === 'par_client' && !empty($groupes_par_type)) {
                $renderGroupes = $groupes_par_type;
                $useTypeHeaders = true;
            } else {
                $renderGroupes = [['type' => null, 'label' => null, 'clients' => $data]];
                $useTypeHeaders = false;
            }
        @endphp

        @foreach($renderGroupes as $groupe)
            <div class="type-group">
                @if($useTypeHeaders)
                    <div class="type-header">{{ $groupe['label'] }}</div>
                @endif

                @foreach($groupe['clients'] as $clientData)
                    <div class="client-block">
                        <div class="client-header">
                            <strong><u>N° Compte :</u></strong> {{ $clientData['numero_compte'] }}
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <strong><u>Raison sociale :</u></strong> {{ $clientData['raison_sociale'] }}
                            @if($mode === 'un_client')
                                <span class="type-inline">&nbsp;- <em>{{ $clientData['type_client_label'] ?? 'Non défini' }}</em></span>
                            @endif
                        </div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th style="width: 30px">N°</th>
                                    <th>Réf. Facture</th>
                                    <th>Date Facture</th>
                                    <th class="montant" style="width: 90px">Montant Facture</th>
                                    <th class="montant" style="width: 80px">Montant Payé</th>
                                    <th class="montant" style="width: 75px">Total rejet</th>
                                    <th class="montant" style="width: 75px">Total pertes</th>
                                    <th class="montant reste" style="width: 85px">Reste à Payer</th>
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
                                        <td class="montant">{{ number_format($ligne['total_rejet'], 0, ',', ' ') }}</td>
                                        <td class="montant">{{ number_format($ligne['total_perte'], 0, ',', ' ') }}</td>
                                        <td class="montant reste">{{ number_format($ligne['reste_a_payer'], 0, ',', ' ') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="3" class="total-label">Total :</td>
                                    <td class="montant">{{ number_format($clientData['total_facture'], 0, ',', ' ') }}</td>
                                    <td class="montant">{{ number_format($clientData['total_paye'], 0, ',', ' ') }}</td>
                                    <td class="montant">{{ number_format($clientData['total_rejet'], 0, ',', ' ') }}</td>
                                    <td class="montant">{{ number_format($clientData['total_perte'], 0, ',', ' ') }}</td>
                                    <td class="montant reste">{{ number_format($clientData['total_reste'], 0, ',', ' ') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endforeach
            </div>
        @endforeach

        @if(count($data) > 1)
            @php
                $grandFacture = collect($data)->sum('total_facture');
                $grandPaye = collect($data)->sum('total_paye');
                $grandReste = collect($data)->sum('total_reste');
            @endphp
            @php
                $grandRejet = collect($data)->sum('total_rejet');
                $grandPerte = collect($data)->sum('total_perte');
            @endphp
            <table class="report-table" style="margin-top: 20px;">
                <tfoot>
                    <tr class="total-row">
                        <td class="total-label" style="text-align: right;">TOTAL GÉNÉRAL :</td>
                        <td class="montant" style="width: 90px;">{{ number_format($grandFacture, 0, ',', ' ') }}</td>
                        <td class="montant" style="width: 80px;">{{ number_format($grandPaye, 0, ',', ' ') }}</td>
                        <td class="montant" style="width: 75px;">{{ number_format($grandRejet, 0, ',', ' ') }}</td>
                        <td class="montant" style="width: 75px;">{{ number_format($grandPerte, 0, ',', ' ') }}</td>
                        <td class="montant reste" style="width: 85px;">{{ number_format($grandReste, 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    @elseif($mode === 'tous_clients')
        @php
            $useTypeRows = empty($type_client ?? null);
            $rows = [];
            if ($useTypeRows) {
                $sorted = collect($data)
                    ->sortBy(fn($r) => ($r['type_client_label'] ?? 'zzz') . '|' . ($r['raison_sociale'] ?? ''))
                    ->values()
                    ->all();
                $lastType = null;
                foreach ($sorted as $r) {
                    $label = $r['type_client_label'] ?? 'Non défini';
                    if ($label !== $lastType) {
                        $rows[] = ['__type__' => true, 'label' => $label];
                        $lastType = $label;
                    }
                    $rows[] = $r;
                }
            } else {
                $rows = $data;
            }
        @endphp
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 30px">N°</th>
                    <th style="width: 80px">N° Compte</th>
                    <th>Raison sociale</th>
                    <th class="montant" style="width: 95px">Total Factures</th>
                    <th class="montant" style="width: 95px">Total Règlements</th>
                    <th class="montant" style="width: 75px">Total rejet</th>
                    <th class="montant" style="width: 75px">Total pertes</th>
                    <th class="montant reste" style="width: 85px">Reste à Payer</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @if(!empty($row['__type__']))
                        <tr class="type-header-row">
                            <td colspan="8" style="text-align: center; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">{{ $row['label'] }}</td>
                        </tr>
                    @else
                        <tr>
                            <td><strong>{{ $row['numero'] }}</strong></td>
                            <td>{{ $row['numero_compte'] }}</td>
                            <td><strong>{{ $row['raison_sociale'] }}</strong></td>
                            <td class="montant">{{ number_format($row['total_facture'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($row['total_paye'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($row['total_rejet'], 0, ',', ' ') }}</td>
                            <td class="montant">{{ number_format($row['total_perte'], 0, ',', ' ') }}</td>
                            <td class="montant reste">{{ number_format($row['total_reste'], 0, ',', ' ') }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $grandFacture = collect($data)->sum('total_facture');
                    $grandPaye = collect($data)->sum('total_paye');
                    $grandRejet = collect($data)->sum('total_rejet');
                    $grandPerte = collect($data)->sum('total_perte');
                    $grandReste = collect($data)->sum('total_reste');
                @endphp
                <tr class="total-row">
                    <td colspan="3" class="total-label">TOTAL GÉNÉRAL</td>
                    <td class="montant">{{ number_format($grandFacture, 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandPaye, 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandRejet, 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($grandPerte, 0, ',', ' ') }}</td>
                    <td class="montant reste">{{ number_format($grandReste, 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
