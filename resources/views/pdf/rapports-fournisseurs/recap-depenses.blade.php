@extends('pdf.rapports._layout-rapport')

@section('title', $titre ?? 'Point des dépenses')
@section('page-size', 'A4 portrait')
@section('page-margin', '32mm 25mm 22mm')
@section('report-title', $titre)

@section('content')
    @if(count($lignes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donn&eacute;e trouv&eacute;e.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 120px">N&deg; Compte</th>
                    <th>Intitul&eacute;</th>
                    <th class="montant" style="width: 150px">Montant TTC</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td class="col-num">{{ $ligne['numero_compte'] }}</td>
                        <td>{{ $ligne['libelle'] }}</td>
                        <td class="montant">{{ number_format($ligne['montant'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="total-label">{{ $labelTotal ?? 'TOTAL DEPENSES' }} :</td>
                    <td class="montant">{{ number_format($totalDepenses, 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
