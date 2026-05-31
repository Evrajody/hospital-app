@extends('pdf.rapports._layout-rapport')

@section('title', 'Imputations Comptables Clients')
@section('page-size', 'A4 portrait')
@section('page-margin', '22mm 25mm')
@section('report-title', $titre ?? 'IMPUTATIONS COMPTABLES CLIENTS')

@if(!empty($periode['debut']) && !empty($periode['fin']))
    @section('report-subtitle', 'Du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y'))
@endif

@section('extra-styles')
    .imputation-block { margin-bottom: 20px; page-break-inside: avoid; }
    .imputation-date { font-size: 12px; font-weight: bold; text-decoration: underline; margin: 0 0 6px 0; }
    .imputation-table { width: 100%; border-collapse: collapse; }
    .imputation-table th, .imputation-table td { border: 1px solid #000; padding: 6px 8px; font-size: 10px; }
    .imputation-table th { text-align: center; font-weight: bold; text-transform: uppercase; background: #f8f8f8; }
    .imputation-table .compte-col { text-align: center; font-family: 'Courier New', monospace; }
    .imputation-table .montant-col { text-align: right; }
@endsection

@section('content')
    @if(count($groupes ?? []) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune imputation trouvée pour cette période.</p>
    @else
        @foreach($groupes as $g)
            <div class="imputation-block">
                <div class="imputation-date">Date d'imputation : {{ $g['date'] }}</div>
                <table class="imputation-table">
                    <thead>
                        <tr>
                            <th style="width: 110px">Compte</th>
                            <th style="width: 120px">Débit</th>
                            <th style="width: 120px">Crédit</th>
                            <th>Libellé</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($g['lignes'] as $l)
                            <tr>
                                <td class="compte-col">{{ $l['compte'] }}</td>
                                <td class="montant-col">{{ $l['debit'] ? number_format($l['debit'], 0, ',', ' ') : '' }}</td>
                                <td class="montant-col">{{ $l['credit'] ? number_format($l['credit'], 0, ',', ' ') : '' }}</td>
                                <td>{{ $l['libelle'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
@endsection
