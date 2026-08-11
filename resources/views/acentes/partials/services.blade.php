<div class="row">
    <div class="table-responsive">
        <table class="table table-bordered" id="AcentesTable">
            <thead>
                <tr>
                    <th>A/R</th>
                    <th>Agency</th>
                    <th>File</th>
                    <th>Start</th>
                    <th>Finish</th>
                    <th>time</th>
                    <th>From/Target</th>
                    <th>Vehicule</th>
                    <th>Pax</th>
                    <th>
                        @if (isset($_GET['allcomment']))
                            <a class="btn btn-primary" href="?src=service&daterange={{ request('daterange') }}">Özet</a>
                        @else
                            <a class="btn btn-success" href="?src=service&daterange={{ request('daterange') }}&allcomment=1">All Comments</a>
                        @endif
                    </th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transfers as $transfer)
                    <tr>
                        <td class="text-{{ $transfer->servicetype->color->name }}">
                            <a href="{{ route('transfers.show', $transfer->id) }}"><i class="fa fa-eye" aria-hidden="true"></i></a>
                            {{ $transfer->servicetype->name }}
                        </td>
                        <td><a href="{{ route('acentes.show', $transfer->post->acente_id) }}">{{ $transfer->post->acente->name }}</a></td>
                        <td><a href="{{ route('posts.show', $transfer->post->id) }}">{{ $transfer->post->id }}</a></td>
                        <td class="table-{{ $transfer->status->color->name ?? '' }}">
                            {{ \Carbon\Carbon::parse($transfer->start_date)->format('d-m-Y H:i') }}
                        </td>
                        <td class="table-{{ $transfer->status->color->name ?? '' }}">
                           
                                {{ \Carbon\Carbon::parse($transfer->end_date)->format('d-m-Y H:i') }}
                          
                        </td>
                        <td>
                            <?php
                           $startDate = \Carbon\Carbon::parse($transfer->start_date);
$endDate = \Carbon\Carbon::parse($transfer->end_date);

$diff = $startDate->diff($endDate);
$hoursAndMinutes = $diff->h . 'h ' . $diff->i . 'm'
?>
{{ $hoursAndMinutes }}

                        </td>
                        <td class="table-{{ $transfer->status->color->name ?? '' }}">
                            @if (isset($_GET['allcomment']))
                                {{ $transfer->from }} -> {{ $transfer->target }}
                            @else
                                {{ \Illuminate\Support\Str::limit($transfer->from, 30) }} -> {{ \Illuminate\Support\Str::limit($transfer->target, 30) }}
                            @endif
                        </td>
                        <td>{{optional($transfer->vehicule)->name}}</td>
                        <td>{{ $transfer->pax }}</td>
                        <td>
                            @if (isset($_GET['allcomment']))
                                {{ $transfer->comments }}
                            @else
                                {{ \Illuminate\Support\Str::limit($transfer->comments, 40) }}
                                <a href="#" data-bs-toggle="popover" title="Comments" data-content="{{ $transfer->comments }}">
                                    <i class="fas fa-comment-dots"></i>
                                </a>
                            @endif
                        </td>
                        <td>{{ optional($transfer->harekets->first())->amount }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8"></td>
                    <td>Total</td>
                    <td>{{ $bakiye }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="row"> 
        <div class="col-sm">   
        <button id="exportButton" class="btn btn-primary">
        <i class="fas fa-file-excel"></i> Export to Excel
    </button>
          </div>

    <div class="text-center">
        {!! $invoices->links() !!}
    </div>

    <div class="panel-heading">Page {{ $invoices->currentPage() }} of {{ $invoices->lastPage() }}</div>
    </div>

    <div class="mt-4">
    <button class="btn btn-info" id="load-driving-hours">Hermes heure de travail</button>
    <div id="hermes-result" class="mt-3"></div>
</div>

</div>
