{{-- Entête officielle commune aux bordereaux et autres documents PDF.
     Utilisation : @include('pdf._entete-officiel') (la variable $etablissement doit être disponible). --}}
<div style="margin-bottom: 10px;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="vertical-align: middle; width: 62%; padding: 0;">
                <img src="{{ public_path('images/ministere_sante.png') }}"
                     alt="Ministère de la Santé - République du Bénin"
                     style="height: 48px; width: auto;" />
            </td>
            <td style="vertical-align: top; width: 38%; padding: 0; text-align: right; font-size: 10.5px; line-height: 1.5; font-weight: bold;">
                BP 01-882 BENIN<br>
                <span style="text-decoration: underline;">Tél.</span> +229 21 33 21 78 / 21 33 21 63<br>
                <span style="color: #1a5fb4; text-decoration: underline;">info@sante.gouv.bj</span><br>
                <span style="color: #1a5fb4; text-decoration: underline;">www.sante.gouv.bj</span>
            </td>
        </tr>
    </table>

    <div style="text-align: center; font-size: 11.5px; font-weight: bold; line-height: 1.35; margin-top: 6px;">
        <div>DIRECTION DEPARTEMENTALE DE LA SANTE DU LITTORAL</div>
        <div style="letter-spacing: 2px; font-weight: normal;">******</div>
        <div>ZONE SANITAIRE COTONOU V</div>
        <div style="letter-spacing: 2px; font-weight: normal;">*********</div>
        <div style="font-size: 13px;">{{ strtoupper($etablissement['nom'] ?? 'HOPITAL DE ZONE DE MENONTIN') }}</div>
        <div style="letter-spacing: 2px; font-weight: normal;">*********</div>
    </div>

    <hr style="border: 0; border-top: 1px solid #000; margin-top: 4px; margin-bottom: 0;" />
</div>
