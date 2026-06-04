@extends('pdf.rapports._layout-rapport')

@section('title', 'Plan Comptable OHADA')
@section('page-size', 'A4 portrait')
@section('page-margin', '32mm 20mm 22mm')
@section('report-title', $titre ?? 'PLAN COMPTABLE OHADA')

@if(!empty($sousTitre))
    @section('report-subtitle', $sousTitre)
@endif

@section('extra-styles')
    table.report-table .compte-col { font-family: 'Courier New', monospace; }
    .recap { margin-top: 10px; font-size: 10px; font-style: italic; color: #444; }
@endsection

@section('content')
    @if(count($comptes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucun compte trouvé.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 130px">N° Compte</th>
                    <th>Libellé</th>
                    <th style="width: 60px">Classe</th>
                    <th style="width: 95px">Source</th>
                </tr>
            </thead>
            <tbody>
                @foreach($comptes as $c)
                    <tr>
                        <td class="compte-col">{{ $c->numero_compte }}</td>
                        <td>{{ $c->libelle }}</td>
                        <td style="text-align: center;">{{ $c->classe }}</td>
                        <td style="text-align: center;">{{ ($c->is_custom ?? false) ? 'Personnalisé' : 'Standard' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="recap">{{ count($comptes) }} compte(s).</div>
    @endif
@endsection
