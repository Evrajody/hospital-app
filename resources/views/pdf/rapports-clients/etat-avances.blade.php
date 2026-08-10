@extends('pdf.rapports._layout-rapport')

@section('title', 'État des Avances Clients')
@section('page-size', 'A4 portrait')
@section('report-title', $titre ?? 'ÉTAT DES AVANCES CLIENTS')
@section('report-subtitle',
    (!empty($periode['debut']) && !empty($periode['fin'])
        ? 'Période du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y')
        : (!empty($periode['fin'])
            ? 'Au ' . \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y')
            : (!empty($periode['debut'])
                ? 'À partir du ' . \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y')
                : 'Toutes périodes'))))

@section('extra-styles')
    .avance-bloc { margin-bottom: 10px; }
    .avance-bloc.page-suivante { page-break-before: always; }
    .avance-entete {
        border: 1px solid #000;
        margin-bottom: 8px;
        font-size: 10px;
    }
    .avance-entete td {
        border: none;
        padding: 4px 8px;
        vertical-align: top;
    }
    .avance-entete .lib { font-weight: bold; text-transform: uppercase; font-size: 9px; color: #333; }
    .avance-entete .val { font-size: 11px; }
    .beneficiaires-val { line-height: 1.45; }
    .reste { color: #cc0000; font-weight: bold; }
    .ligne-vide td { color: #999; font-style: italic; }
    .avance-pied {
        margin-top: 6px;
        font-size: 10px;
        text-align: right;
        line-height: 1.7;
    }
    .avance-pied .ligne { white-space: nowrap; }
    .avance-pied .label { display: inline-block; min-width: 145px; text-transform: uppercase; font-weight: bold; }
    .avance-pied .montant { display: inline-block; min-width: 110px; font-weight: bold; }
@endsection

@section('content')
    @if(count($avances) === 0)
        <p style="text-align: center; padding: 40px; color: #666;">Aucune avance trouvée pour les critères choisis.</p>
    @else
        @foreach($avances as $av)
            <div class="avance-bloc {{ !$loop->first ? 'page-suivante' : '' }}">
                {{-- En-tête de l'avance --}}
                <table class="avance-entete" style="width: 100%; table-layout: fixed;">
                    <tr>
                        <td style="width: 60%;">
                            <div class="lib">Société émettrice</div>
                            <div class="val">
                                @if($av['emetteur_compte'])<strong>{{ $av['emetteur_compte'] }}</strong> — @endif
                                {{ $av['emetteur_nom'] ?: '-' }}
                            </div>
                        </td>
                        <td style="width: 40%;">
                            <div class="lib">Montant du chèque</div>
                            <div class="val"><strong>{{ number_format($av['montant'], 0, ',', ' ') }}</strong> F CFA</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="lib">Date du chèque</div>
                            <div class="val">{{ $av['date_cheque'] ?: '-' }}</div>
                        </td>
                        <td>
                            <div class="lib">Référence du chèque</div>
                            <div class="val">{{ $av['numero_cheque'] ?: '-' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div class="lib">Bénéficiaire(s) / patient(s)</div>
                            <div class="val beneficiaires-val">
                                {{ count($av['beneficiaires']) ? implode(' • ', $av['beneficiaires']) : '-' }}
                            </div>
                        </td>
                    </tr>
                </table>

                {{-- Détail des factures réglées avec l'avance --}}
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 28px">N°</th>
                            <th>Réf. facture</th>
                            <th class="col-date" style="width: 85px">Date fac.</th>
                            <th class="montant" style="width: 125px">Mt facture</th>
                            <th class="montant" style="width: 135px">Mt réglé (avance)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($av['rows'] as $row)
                            <tr>
                                <td class="col-num">{{ $loop->iteration }}</td>
                                <td>{{ $row['facture_ref'] ?: '—' }}</td>
                                <td class="col-date">{{ $row['date_facture'] ?: '—' }}</td>
                                <td class="montant">{{ is_null($row['montant_facture']) ? '—' : number_format($row['montant_facture'], 0, ',', ' ') }}</td>
                                <td class="montant">{{ is_null($row['montant_regle']) ? '—' : number_format($row['montant_regle'], 0, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: #666;">Aucun règlement effectué avec cette avance.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Synthèse simple, sans tableau --}}
                <div class="avance-pied">
                    <div class="ligne">
                        <span class="label">Total utilisé :</span>
                        <span class="montant">{{ number_format($av['montant_utilise'], 0, ',', ' ') }} F CFA</span>
                    </div>
                    <div class="ligne reste">
                        <span class="label">Restant de l'avance :</span>
                        <span class="montant">{{ number_format($av['montant_restant'], 0, ',', ' ') }} F CFA</span>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
@endsection
