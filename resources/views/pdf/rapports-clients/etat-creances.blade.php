@extends('pdf.rapports._layout-rapport')

@php
    $typeSuffix = !empty($type_client_label ?? null) ? ' — ' . strtoupper($type_client_label) : '';
@endphp

@section('title', 'États Périodiques des Créances Clients')
@section('page-size', 'A4 portrait')
@section('page-margin', '32mm 25mm 22mm')
@section('report-title', ($titre ?? 'ÉTATS PÉRIODIQUES DES CRÉANCES CLIENTS') . $typeSuffix)
@section('report-subtitle', 'Factures non soldées' . (!empty($periode['debut']) && !empty($periode['fin']) ? ' - Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') : (!empty($periode['fin']) ? ' - Au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') : '')))

@section('extra-styles')
    .client-block { margin-bottom: 25px; }
    .client-header { border: 1px solid #000; padding: 8px 12px; margin-bottom: 10px; font-size: 11px; page-break-after: avoid; }
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
                            <th class="montant" style="width: 95px">Total Factures</th>
                            <th class="montant" style="width: 95px">Total Règlements</th>
                            <th class="montant" style="width: 75px">Total rejet</th>
                            <th class="montant" style="width: 75px">Total pertes</th>
                            <th class="montant reste" style="width: 85px">Reste à Payer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupe['clients'] as $row)
                            <tr>
                                <td><strong>{{ $loop->iteration }}</strong></td>
                                <td>{{ $row['numero_compte'] }}</td>
                                <td><strong>{{ $row['raison_sociale'] }}</strong></td>
                                <td class="montant">{{ number_format($row['total_facture'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($row['total_paye'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($row['total_rejet'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ number_format($row['total_perte'], 0, ',', ' ') }}</td>
                                <td class="montant reste">{{ number_format($row['total_reste'], 0, ',', ' ') }}</td>
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
                                <td class="montant reste">{{ number_format(collect($groupe['clients'])->sum('total_reste'), 0, ',', ' ') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endforeach
    @endif
@endsection
