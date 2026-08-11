@hasanyrole('Admin|ofis')
<div class="row">
    <div class="table-responsive">
        <table class="table table-bordered" id="AcentesTable">
            <thead>
                <tr>
                    <th scope="col">Id</th>
                    <th scope="col">Date</th>
                    <th scope="col">From->To</th>
                    <th scope="col">Cash Tahsilat</th>
                    <th scope="col">Diğer Tahsilat</th>
                    <th scope="col">Ödemeler</th>
                    <th scope="col">Açıklama</th>
                </tr>
            </thead>
            <tbody>
                <?php $cashs = 0; $digers = 0; $alinans = 0; ?>
                @foreach ($offsets as $offset)
                    <tr>
                        <td><a href="{{ route('offsets.edit', $offset->id) }}">{{ $offset->id }}</a></td>
                        <td>{{ date('d/m/Y H:i', strtotime($offset->tarih)) }}</td>
                        <td>
                            <a href="{{ route('acentes.show', $offset->alacakli->id) }}?src=offset">{{ $offset->alacakli->name }}</a> ->
                            <a href="{{ route('acentes.show', $offset->borclu->id) }}?src=offset">{{ $offset->borclu->name }}</a>
                        </td>
                        <td>
                            @if (isset($offset->harekets[0]) && ($offset->harekets[0]->aciklama == 'Cash transfer' || $offset->harekets[0]->aciklama == 'Cash Ticket'))
                                {{ $cash = $offset->harekets[1]->amount ?? 0 }}
                            @endif
                        </td>
                        <td>
                            {{ $diger = ($offset->b_acente_id == $acente->id && isset($offset->harekets[1])) ? $offset->harekets[1]->amount : 0 }}
                        </td>
                        <td>
                            {{ $alinan = $offset->harekets[0]->amount ?? 0 }}
                        </td>
                        <td>{{ $offset->aciklama }}</td>
                    </tr>
                    <?php 
                        $digers += (float) $diger; 
                        $cashs += (float) $cash; 
                        $alinans += (float) $alinan; 
                        $diger = 0; 
                        $cash = 0; 
                        $alinan = 0;
                    ?>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">TOPLAM</td>
                    <td>{{ $cashs }}</td>
                    <td>{{ $digers }}</td>
                    <td>{{ $alinans }}</td>
                    <td>TOPLAM: {{ $cashs + $digers + $alinans }}</td>
                </tr>
            </tfoot>
        </table>
        <div class="row"> 
            <div class="col-sm">   
            <button id="exportButton" class="btn btn-primary">
            <i class="fas fa-file-excel"></i> Export to Excel
        </button>
      </div>
        </div>
    </div>
</div>
@endhasrole