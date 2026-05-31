@extends('pdf.rapports._layout-rapport')

@section('title', 'Brouillard de Chèques')
@section('page-size', 'A4 portrait')
@section('page-margin', '32mm 25mm 22mm')
@section('report-title', $titre ?? 'BROUILLARD DE CHÈQUES')

@if(!empty($periode['debut']) && !empty($periode['fin']))
    @section('report-subtitle', 'Du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y'))
@endif

@section('extra-styles')
    .solde { font-weight: bold; }
    .date-cell { vertical-align: top; }
@endsection

@section('content')
    @if(count($data) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée pour cette période.</p>
    @else
        @php
            $groups = [];
            $currentDate = null;
            foreach ($data as $entry) {
                if ($entry['date'] !== $currentDate) {
                    $currentDate = $entry['date'];
                    $groups[] = ['date' => $currentDate, 'entries' => []];
                }
                $groups[count($groups) - 1]['entries'][] = $entry;
            }
        @endphp

        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 80px">Date</th>
                    <th>Libellés</th>
                    <th class="montant" style="width: 100px">Débit</th>
                    <th class="montant" style="width: 100px">Crédit</th>
                    <th class="montant" style="width: 100px">Solde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $group)
                    @foreach($group['entries'] as $i => $entry)
                        <tr>
                            @if($i === 0)
                                <td class="date-cell" rowspan="{{ count($group['entries']) }}"><strong>{{ $group['date'] }}</strong></td>
                            @endif
                            <td>{{ $entry['libelle'] }}</td>
                            <td class="montant">{{ $entry['debit'] ? number_format($entry['debit'], 0, ',', ' ') : '' }}</td>
                            <td class="montant">{{ $entry['credit'] ? number_format($entry['credit'], 0, ',', ' ') : '' }}</td>
                            <td class="montant solde">{{ number_format($entry['solde'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
