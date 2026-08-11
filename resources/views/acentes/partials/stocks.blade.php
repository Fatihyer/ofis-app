<div class="row">
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">Id</th>
                    <th scope="col">Date</th>
                    <th>Urun</th>
                    <th scope="col">From->To</th>
                    <th scope="col">Qty</th>
                    <th scope="col">Cash /Credit<a href="#" data-bs-toggle="tooltip" title="Cash ise Offset ile Girildi! Bir daha eklemeyin">****</a></th>
                    <th scope="col">Alış</th>
                    <th scope="col">Satış</th>
                    <th scope="col">Kar</th>
                    <th scope="col">Açıklama</th>
                </tr>
            </thead>
            <tbody>
                <?php $adets = 0; $alislar = 0; $satislar = 0; $karlars = 0; $cashs = 0; $digers = 0;
                
                ?>
                @foreach ($stocks as $stock)
                    <tr>
                        <td><a href="{{ route('stocks.edit', $stock->id) }}">{{ $stock->id }}</a></td>
                        <td>{{ date('d/m/Y H:i', strtotime($stock->tarih)) }}</td>
                        <td><a href="{{ route('acentes.show', $stock->urun->id) }}?src=stock">{{ $stock->urun->name }}</a></td>
                        <td><a href="{{ route('acentes.show', $stock->aAcente->id) }}?src=stock">{{ $stock->aAcente->name }}</a>-><a href="{{ route('acentes.show', $stock->bAcente->id) }}?src=stock">{{ $stock->bAcente->name }}</a></td>
                        <td>{{ $stock->adet }}</td>
                        <td>{{ $stock->credit == 1 ? 'Cash' : 'Credit' }}</td>
                      
                        <td>
                             @php
                        $alis = $stock->buy_price;
                        $alislar += $alis;


                        $satis = $stock->sell_price;
                        $satislar += $satis;

                        $kar = $satis - $alis;
                        $karlars += $kar;

                        $adets += $stock->adet;
                    @endphp
                         {{ number_format($alis, 2) }}
                            </td>
                            <td>
                        {{ number_format($satis, 2) }}

                            </td>
                            <td>
                        {{ number_format($kar, 2) }}
                        


                            </td>
                          <td>

                        {{ $stock->aciklama }}
                        </td>                
                    
                    </tr>
                    <?php $cashs += $cash; ?>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">TOPLAM</td>
                    <td>{{$adets}}</td>
                    <td></td>
                    <td>{{ $alislar}}</td>
                    <td>{{ $satislar}}</td>
                    <td>TOPLAM: {{ $karlars }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
