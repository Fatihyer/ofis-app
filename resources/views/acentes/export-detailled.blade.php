@php
    $formatDate = function ($value, $format = 'd/m/Y H:i') {
        return $value ? \Carbon\Carbon::parse($value)->format($format) : '';
    };
    $grandTotal = 0;
    $totalTransfers = 0;
@endphp

<table>
    <thead>
        <tr>
            <th colspan="12" style="font-size:18px;font-weight:bold;">Dossiers détaillés</th>
        </tr>
        <tr>
            <th colspan="12">Client: {{ $acente->name }}</th>
        </tr>
        <tr>
            <th colspan="12">Période: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</th>
        </tr>
        <tr></tr>
        <tr>
            <th style="font-weight:bold;background:#E5E7EB;">Dossier</th>
            <th style="font-weight:bold;background:#E5E7EB;">Nom dossier</th>
            <th style="font-weight:bold;background:#E5E7EB;">Début dossier</th>
            <th style="font-weight:bold;background:#E5E7EB;">Fin dossier</th>
            <th style="font-weight:bold;background:#E5E7EB;">Pax</th>
            <th style="font-weight:bold;background:#E5E7EB;">Transfert</th>
            <th style="font-weight:bold;background:#E5E7EB;">Service</th>
            <th style="font-weight:bold;background:#E5E7EB;">Départ</th>
            <th style="font-weight:bold;background:#E5E7EB;">Fin</th>
            <th style="font-weight:bold;background:#E5E7EB;">Itinéraire</th>
            <th style="font-weight:bold;background:#E5E7EB;">Véhicule</th>
            <th style="font-weight:bold;background:#E5E7EB;">Montant</th>
        </tr>
    </thead>
    <tbody>
        @foreach($posts as $post)
            @php
                $postTotal = 0;
                $postTransfers = $post->transfer;
            @endphp

            @forelse($postTransfers as $transfer)
                @php
                    $amount = $transfer->harekets->sum('amount');
                    $grandTotal += $amount;
                    $postTotal += $amount;
                    $totalTransfers++;
                    $currency = optional(optional($transfer->harekets->first())->kur)->short_name ?: 'EUR';
                @endphp
                <tr>
                    <td>FP{{ $post->id }}</td>
                    <td>{{ $post->title }}</td>
                    <td>{{ $formatDate($post->start_date, 'd/m/Y') }}</td>
                    <td>{{ $formatDate($post->end_date, 'd/m/Y') }}</td>
                    <td>{{ $post->pax }}</td>
                    <td>#{{ $transfer->id }}</td>
                    <td>{{ optional($transfer->servicetype)->name }}</td>
                    <td>{{ $formatDate($transfer->start_date) }}</td>
                    <td>{{ $formatDate($transfer->end_date) }}</td>
                    <td>{{ trim(($transfer->from ?: '') . ' -> ' . ($transfer->target ?: ''), ' ->') }}</td>
                    <td>{{ optional($transfer->vehicule)->name ?: 'Sans véhicule' }}</td>
                    <td>{{ number_format($amount, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
            @empty
                <tr>
                    <td>FP{{ $post->id }}</td>
                    <td>{{ $post->title }}</td>
                    <td>{{ $formatDate($post->start_date, 'd/m/Y') }}</td>
                    <td>{{ $formatDate($post->end_date, 'd/m/Y') }}</td>
                    <td>{{ $post->pax }}</td>
                    <td colspan="7">Aucun transfert</td>
                </tr>
            @endforelse

            @if($postTransfers->count() > 0)
                <tr>
                    <td colspan="11" style="font-weight:bold;text-align:right;background:#F8FAFC;">Total dossier FP{{ $post->id }}</td>
                    <td style="font-weight:bold;background:#F8FAFC;">{{ number_format($postTotal, 2, ',', ' ') }} EUR</td>
                </tr>
            @endif
        @endforeach

        <tr></tr>
        <tr>
            <td colspan="5" style="font-weight:bold;background:#DBEAFE;">Total dossiers</td>
            <td style="font-weight:bold;background:#DBEAFE;">{{ $posts->count() }}</td>
            <td colspan="4" style="font-weight:bold;background:#DBEAFE;">Total transferts</td>
            <td style="font-weight:bold;background:#DBEAFE;">{{ $totalTransfers }}</td>
            <td style="font-weight:bold;background:#DBEAFE;">{{ number_format($grandTotal, 2, ',', ' ') }} EUR</td>
        </tr>
    </tbody>
</table>
