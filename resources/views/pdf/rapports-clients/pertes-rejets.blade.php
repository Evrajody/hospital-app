@extends('pdf.rapports._layout-rapport')

@section('title', 'Pertes et Rejets')
@section('page-size', 'A4 portrait')
@section('page-margin', '22mm 25mm')
@section('report-title', $titre ?? 'PERTES ET REJETS')

@php
    $subtitleParts = [];
    if (!empty($typeFilter)) {
        $labels = ['perte' => 'Pertes', 'rejet' => 'Rejets'];
        $subtitleParts[] = $labels[$typeFilter] ?? $typeFilter;
    }
    if (!empty($periode['debut']) && !empty($periode['fin'])) {
        if ($periode['debut'] === $periode['fin']) {
            $subtitleParts[] = 'au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y');
        } else {
            $subtitleParts[] = 'Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y');
        }
    } elseif (!empty($periode['debut'])) {
        $subtitleParts[] = 'à partir du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y');
    } elseif (!empty($periode['fin'])) {
        $subtitleParts[] = 'au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y');
    }
@endphp

@section('report-subtitle', implode(' - ', $subtitleParts))

@section('extra-styles')
    .client-block { margin-bottom: 25px; page-break-inside: avoid; }
    .client-header { border: 1px solid #000; padding: 8px 12px; margin-bottom: 10px; font-size: 11px; }
@endsection

@section('content')
    @if(count($dataParClient ?? []) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
    @else
        @foreach($dataParClient as $clientData)
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
                            <th>Date</th>
                            <th>Réf. Facture</th>
                            <th class="montant" style="width: 110px">Montant</th>
                            <th>Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clientData['lignes'] as $ligne)
                            <tr>
                                <td><strong>{{ $ligne['numero'] }}</strong></td>
                                <td>{{ $ligne['date_reglement'] }}</td>
                                <td>{{ $ligne['facture_reference'] ?? '-' }}</td>
                                <td class="montant">{{ number_format($ligne['montant'], 0, ',', ' ') }}</td>
                                <td style="font-size: 9px;">{{ $ligne['observations'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" class="total-label">Total :</td>
                            <td class="montant">{{ number_format($clientData['total'], 0, ',', ' ') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach
    @endif
@endsection
