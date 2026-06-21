@extends('pdf.rapports._layout-rapport')

@section('title', $titre ?? 'Situation des banques')
@section('page-size', 'A4 portrait')
@section('page-margin', '32mm 25mm 22mm')
@section('report-title', $titre)

@section('extra-styles')
    .negatif { color: #cc0000; }
@endsection

@section('content')
    @if(count($lignes) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donn&eacute;e trouv&eacute;e.</p>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 100px">N&deg; Compte</th>
                    <th>Intitul&eacute;</th>
                    <th class="montant" style="width: 130px">Total D&eacute;bit</th>
                    <th class="montant" style="width: 130px">Total Cr&eacute;dit</th>
                    <th class="montant" style="width: 130px">Solde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td class="col-num">{{ $ligne['numero_compte'] }}</td>
                        <td>{{ $ligne['intitule'] }}</td>
                        <td class="montant">{{ number_format($ligne['total_debit'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($ligne['total_credit'], 0, ',', ' ') }}</td>
                        <td class="montant {{ $ligne['solde'] < 0 ? 'negatif' : '' }}">{{ number_format($ligne['solde'], 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="total-label">TOTAL SOLDE :</td>
                    <td class="montant">{{ number_format($totalDebit, 0, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($totalCredit, 0, ',', ' ') }}</td>
                    <td class="montant {{ $totalSolde < 0 ? 'negatif' : '' }}">{{ number_format($totalSolde, 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
