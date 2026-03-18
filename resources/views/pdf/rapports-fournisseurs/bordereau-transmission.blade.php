@extends('pdf.rapports._layout-rapport')

@section('title', 'Bordereau de Transmission')
@section('page-size', 'A4 landscape')
@section('report-title', $titre ?? 'BORDEREAU DE TRANSMISSION')

@section('content')
    @if(count($lignes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucun r&egrave;glement s&eacute;lectionn&eacute;.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 80px">Date</th>
                    <th>Fournisseur</th>
                    <th style="width: 110px">N&deg; Pi&egrave;ce</th>
                    <th style="width: 100px">Mode de Paiement</th>
                    <th>Institution</th>
                    <th class="montant" style="width: 110px">Montant</th>
                    <th>B&eacute;n&eacute;ficiaire</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td>{{ $ligne['date'] }}</td>
                        <td>{{ $ligne['fournisseur'] }}</td>
                        <td>{{ $ligne['numero_piece'] }}</td>
                        <td>{{ $ligne['mode_paiement'] }}</td>
                        <td>{{ $ligne['institution'] }}</td>
                        <td class="montant">{{ number_format($ligne['montant'], 0, ',', ' ') }}</td>
                        <td>{{ $ligne['beneficiaire'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
