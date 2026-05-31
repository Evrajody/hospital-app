@extends('pdf.rapports._layout-rapport')

@section('title', $titre ?? 'Etat des PC')
@section('page-size', 'A4 portrait')
@section('page-margin', '22mm 25mm')
@section('report-title', $titre)

@section('extra-styles')
    .date-header { font-size: 12px; margin: 20px 0 10px; }
    .date-header strong { text-decoration: underline; }
    .date-header em { font-style: italic; }
@endsection

@section('content')
    @if(count($groupes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune pi&egrave;ce comptable trouv&eacute;e.</p>
    @else
        @foreach($groupes as $groupe)
            <div class="date-header">
                <strong>Date d'enregistrement :</strong> <em>{{ $groupe['date_longue'] }}</em>
            </div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 120px">N&deg; PC</th>
                        <th>Objet PC</th>
                        <th class="montant" style="width: 130px">Montant TTC</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupe['lignes'] as $ligne)
                        <tr>
                            <td>{{ $ligne['numero_piece'] }}</td>
                            <td>{{ $ligne['libelle'] }}</td>
                            <td class="montant">{{ number_format($ligne['montant'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2" class="total-label">TOTAL :</td>
                        <td class="montant">{{ number_format($groupe['total'], 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endforeach
    @endif
@endsection
