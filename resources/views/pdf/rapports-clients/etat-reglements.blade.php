@extends('pdf.rapports._layout-rapport')

@php
    $typeSuffix = !empty($type_client_label ?? null) ? ' — ' . strtoupper($type_client_label) : '';
@endphp

@section('title', 'État des Règlements Clients')
@section('page-size', 'A4 portrait')
@section('page-margin', '32mm 25mm 22mm')
@section('report-title', ($titre ?? 'ÉTAT DES RÈGLEMENTS CLIENTS') . $typeSuffix)

@if($mode === 'un_client')
    @section('report-subtitle', 'Règlements d\'un client')
@elseif($mode === 'tous_clients')
    @section('report-subtitle', 'Récapitulatif tous clients' . (!empty($periode['debut']) && !empty($periode['fin']) ? ' - Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') : ''))
@else
    @section('report-subtitle', 'Règlements par client')
@endif

@section('extra-styles')
    .client-block { margin-bottom: 25px; }
    .client-header { border: 1px solid #000; padding: 8px 12px; margin-bottom: 10px; font-size: 11px; page-break-after: avoid; }
    .type-group { }
    .type-group + .type-group { page-break-before: always; }
    .type-header { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; padding: 8px 12px; background: #e5e7eb; border-left: 4px solid #1f2937; margin: 0 0 12px; page-break-after: avoid; }
    .type-inline { font-size: 10px; color: #444; margin-right: 6px; }
    .type-inline em { font-style: italic; }
@endsection

@section('content')
    @if(count($data) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
    @elseif($mode === 'par_client' || $mode === 'un_client')
        @php
            // Pour le mode par_client sans type filtré, on utilise les groupes par type ;
            // sinon, on enveloppe les data dans un pseudo-groupe unique pour mutualiser le rendu.
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
                            @if($mode === 'un_client')
                                <span class="type-inline">- <em>{{ $clientData['type_client_label'] ?? 'Non défini' }}</em></span>
                            @endif
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
                                    <th class="montant" style="width: 90px">Montant Facture</th>
                                    <th class="montant" style="width: 80px">Montant Payé</th>
                                    <th class="montant" style="width: 80px">Total rejet</th>
                                    <th class="montant" style="width: 80px">Total pertes</th>
                                    <th class="montant" style="width: 80px">Solde</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clientData['lignes'] as $ligne)
                                    <tr>
                                        <td class="col-num"><strong>{{ $ligne['numero'] }}</strong></td>
                                        <td>{{ $ligne['reference'] }}</td>
                                        <td class="col-date">{{ $ligne['date_facture'] }}</td>
                                        <td class="col-date">{{ $ligne['date_reglement'] }}</td>
                                        <td class="montant">{{ number_format($ligne['montant_facture'], 0, ',', ' ') }}</td>
                                        <td class="montant">{{ number_format($ligne['montant_paye'], 0, ',', ' ') }}</td>
                                        <td class="montant">{{ number_format($ligne['total_rejet'], 0, ',', ' ') }}</td>
                                        <td class="montant">{{ number_format($ligne['total_perte'], 0, ',', ' ') }}</td>
                                        <td class="montant">{{ number_format($ligne['solde'], 0, ',', ' ') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="4" class="total-label">Total :</td>
                                    <td class="montant">{{ number_format($clientData['total_facture'], 0, ',', ' ') }}</td>
                                    <td class="montant">{{ number_format($clientData['total_paye'], 0, ',', ' ') }}</td>
                                    <td class="montant">{{ number_format($clientData['total_rejet'], 0, ',', ' ') }}</td>
                                    <td class="montant">{{ number_format($clientData['total_perte'], 0, ',', ' ') }}</td>
                                    <td class="montant">{{ number_format($clientData['total_solde'], 0, ',', ' ') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endforeach
            </div>
        @endforeach
    @elseif($mode === 'tous_clients')
        @php
            // Un type par page : on regroupe par type et le libellé passe en en-tête au-dessus du tableau.
            $useTypeHeaders = empty($type_client ?? null);
            if ($useTypeHeaders) {
                $groupes = collect($data)
                    ->sortBy(fn($r) => ($r['type_client_label'] ?? 'zzz') . '|' . ($r['raison_sociale'] ?? ''))
                    ->groupBy(fn($r) => $r['type_client_label'] ?? 'Non défini')
                    ->map(fn($clients, $label) => ['label' => $label, 'clients' => $clients->values()->all()])
                    ->values()
                    ->all();
            } else {
                $groupes = [['label' => null, 'clients' => $data]];
            }
        @endphp

        @foreach($groupes as $groupe)
            <div class="type-group">
                @if($useTypeHeaders)
                    <div class="type-header">{{ $groupe['label'] }}</div>
                @endif
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 30px">N°</th>
                            <th style="width: 80px">N° Compte</th>
                            <th>Raison sociale</th>
                            <th class="montant" style="width: 95px">Montant Factures</th>
                            <th class="montant" style="width: 95px">Montant Règlements</th>
                            <th class="montant" style="width: 80px">Total rejet</th>
                            <th class="montant" style="width: 80px">Total pertes</th>
                            <th class="montant" style="width: 85px">Solde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupe['clients'] as $row)
                            <tr>
                                <td class="col-num"><strong>{{ $loop->iteration }}</strong></td>
                                <td class="col-num">{{ $row['numero_compte'] }}</td>
                                <td><strong>{{ $row['raison_sociale'] }}</strong></td>
                                <td class="montant">{{ number_format($row['total_facture'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($row['total_paye'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($row['total_rejet'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($row['total_perte'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($row['total_solde'], 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    @if($useTypeHeaders)
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3" class="total-label">Sous-total {{ $groupe['label'] }} :</td>
                                <td class="montant">{{ number_format(collect($groupe['clients'])->sum('total_facture'), 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format(collect($groupe['clients'])->sum('total_paye'), 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format(collect($groupe['clients'])->sum('total_rejet'), 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format(collect($groupe['clients'])->sum('total_perte'), 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format(collect($groupe['clients'])->sum('total_solde'), 0, ',', ' ') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endforeach
    @endif
@endsection
