@extends('pdf.rapports._layout-rapport')

@section('title', $titre ?? 'Situation des banques')
@section('page-size', 'A4 portrait')
@section('page-margin', '20mm 25mm')
@section('report-title', $titre)

@section('extra-styles')
    .bank-header { font-size: 12px; margin: 20px 0 10px; }
    .bank-header strong { text-decoration: underline; }
    .bank-header em { font-style: italic; }
    .special-row { font-weight: bold; font-style: italic; }
    .page-break { page-break-before: always; }
@endsection

@section('content')
    @if(count($sections ?? []) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donn&eacute;e trouv&eacute;e.</p>
    @else
        @foreach($sections as $si => $section)
            <div class="bank-header">
                <strong>Banque :</strong>&nbsp; <em>{{ $section['numero_compte'] }} {{ $section['intitule'] }}</em>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 90px">DATE</th>
                        <th>LIBELLES</th>
                        <th class="montant" style="width: 110px">DEBIT</th>
                        <th class="montant" style="width: 110px">CREDIT</th>
                        <th class="montant" style="width: 120px">SOLDE</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Initial balance row --}}
                    <tr class="special-row">
                        <td></td>
                        <td><em>SOLDE AU&nbsp; {{ $dateAvant }}</em></td>
                        <td></td>
                        <td></td>
                        <td class="montant">{{ number_format($section['solde_initial'], 0, ',', ' ') }}</td>
                    </tr>

                    {{-- Transaction rows --}}
                    @foreach($section['transactions'] as $tx)
                        <tr>
                            <td>{{ $tx['date_fmt'] }}</td>
                            <td>{{ $tx['libelle'] }}</td>
                            <td class="montant">{{ $tx['debit'] > 0 ? number_format($tx['debit'], 0, ',', ' ') : '0' }}</td>
                            <td class="montant">{{ $tx['credit'] > 0 ? number_format($tx['credit'], 0, ',', ' ') : '0' }}</td>
                            <td class="montant">{{ number_format($tx['solde'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach

                    {{-- Period total row --}}
                    <tr class="total-row">
                        <td colspan="2"><em>SOLDE DE LA PERIODE</em></td>
                        <td class="montant">{{ number_format($section['total_debit'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($section['total_credit'], 0, ',', ' ') }}</td>
                        <td class="montant">{{ number_format($section['solde_initial'] + $section['solde_periode'], 0, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endif
@endsection
