@extends('pdf.rapports._layout-rapport')

@section('title', "Chiffres d'Affaires (CA)")
@section('page-size', 'A4 portrait')
@section('page-margin', '20mm 25mm')
@section('report-title', $titre ?? "CHIFFRES D'AFFAIRES (CA)")

@if(in_array($mode, ['global_du', 'global_au', 'global_periode']))
    @if($mode === 'global_du' && !empty($dateRef))
        @section('report-subtitle', 'Du ' . \Carbon\Carbon::parse($dateRef)->format('d/m/Y'))
    @elseif($mode === 'global_au' && !empty($dateRef))
        @section('report-subtitle', 'Au ' . \Carbon\Carbon::parse($dateRef)->format('d/m/Y'))
    @elseif($mode === 'global_periode' && !empty($periode['debut']) && !empty($periode['fin']))
        @section('report-subtitle', 'Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y'))
    @endif
@elseif($mode === 'par_client')
    @section('report-subtitle', 'Par client' . (!empty($periode['debut']) && !empty($periode['fin']) ? ' - Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') : ''))
@endif

@section('extra-styles')
    .client-header { border: 1px solid #000; padding: 8px 12px; margin-bottom: 10px; font-size: 11px; }
    .ecart { color: #cc0000; font-weight: bold; }
    .global-table { max-width: 500px; margin: 30px auto; }
    .global-table th { text-align: center; font-size: 12px; padding: 10px; }
    .global-table td { font-size: 14px; padding: 12px 16px; text-align: center; }
@endsection

@section('content')
    @if(in_array($mode, ['global_du', 'global_au', 'global_periode']))
        @if(is_array($data) && isset($data['theorique']))
            <table class="report-table global-table">
                <thead>
                    <tr>
                        <th>CA Théorique</th>
                        <th>CA Physique</th>
                        <th>Écart</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="montant" style="font-size: 16px; font-weight: bold;">{{ number_format($data['theorique'], 0, ',', ' ') }}</td>
                        <td class="montant" style="font-size: 16px; font-weight: bold;">{{ number_format($data['physique'], 0, ',', ' ') }}</td>
                        <td class="montant ecart" style="font-size: 16px;">{{ number_format($data['ecart'], 0, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
        @endif
    @elseif($mode === 'par_client')
        @if(is_array($data) && isset($data['lignes']))
            <div class="client-header">
                <strong><u>N° Compte :</u></strong> {{ $data['numero_compte'] }}
                &nbsp;&nbsp;&nbsp;&nbsp;
                <strong><u>Raison sociale :</u></strong> {{ $data['raison_sociale'] }}
            </div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 30px">N°</th>
                        <th>Réf. Facture</th>
                        <th>Date Facture</th>
                        <th class="montant" style="width: 130px">Montant CA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['lignes'] as $ligne)
                        <tr>
                            <td><strong>{{ $ligne['numero'] }}</strong></td>
                            <td>{{ $ligne['reference'] }}</td>
                            <td>{{ $ligne['date_facture'] }}</td>
                            <td class="montant">{{ number_format($ligne['montant'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="total-label">Total CA Client :</td>
                        <td class="montant">{{ number_format($data['total_ca'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
        @endif
    @endif
@endsection
