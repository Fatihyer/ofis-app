@php
    $currentUser = auth()->user();
    $canSeeBillingStatus = $currentUser && (
        (method_exists($currentUser, 'hasRole') && $currentUser->hasRole('Superadmin')) ||
        strtolower($currentUser->role ?? '') === 'superadmin'
    );
    $billingLabels = [
        'to_invoice' => ['À facturer', 'to-invoice'],
        'invoiced' => ['Facturé', 'invoiced'],
        'do_not_invoice' => ['Ne pas facturer', 'do-not-invoice'],
    ];
@endphp

@if($canSeeBillingStatus)
    <style>
        .billing-status-select {
            min-width: 145px;
            border: 0;
            border-radius: 6px;
            color: #fff;
            font-weight: 700;
            padding: 6px 28px 6px 10px;
        }
        .billing-status-select.to-invoice {
            background-color: #f59e0b;
            color: #111827;
        }
        .billing-status-select.invoiced {
            background-color: #198754;
        }
        .billing-status-select.do-not-invoice {
            background-color: #6c757d;
        }
        .billing-grouped-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: #dbeafe;
            color: #1e40af;
            font-weight: 800;
            padding: 6px 10px;
            text-decoration: none;
        }
        .billing-grouped-meta {
            color: #64748b;
            font-size: 12px;
            margin-top: 4px;
        }
    </style>
@endif

<div class="row">
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th scope="col">@sortablelink('id', __('app.file'))</th>
                    <th scope="col">Name</th>
                    <th scope="col">@sortablelink('start_date', __('app.from'))</th>
                    <th scope="col">To</th>
                    <th scope="col">Comment</th>
                    <th scope="col">Status</th>
                    @if($canSeeBillingStatus)
                        <th scope="col">Facturation</th>
                    @endif
                    <th scope="col">Resmi</th>
                    <th scope="col">Pax</th>
                    <th scope="col">Child</th>
                    <th scope="col">#</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($posts as $post)
                    <tr>
                        <th scope="row"><a href="{{ route('posts.show', $post->id) }}"><b>FP{{ $post->id }} </b></a></th>
                        <td>{{ $post->title }}</td>
                        <td>{{ date('d/m/Y', strtotime($post->start_date)) }}</td>
                        <td>{{ date('d/m/Y', strtotime($post->end_date)) }}</td>
                        <td><p class="teaser">{{  \Illuminate\Support\Str::limit($post->body, 100) }}</p></td>
                        <td><p class="text-{{ $post->status->color->name }}">{{ $post->status->name }}</p></td>
                        @if($canSeeBillingStatus)
                            @php
                                $groupedInvoice = $post->invoice
                                    ->flatMap(function ($invoice) {
                                        return collect($invoice->groupinvoice ?? []);
                                    })
                                    ->first();
                                $billingStatus = $post->billing_status ?: 'to_invoice';
                                $billingLabel = $billingLabels[$billingStatus][0] ?? $billingStatus;
                                $billingClass = $billingLabels[$billingStatus][1] ?? 'secondary';
                            @endphp
                            <td>
                                @if($groupedInvoice)
                                    <a class="billing-grouped-badge" href="{{ route('groupinvoices.edit', $groupedInvoice->id) }}">
                                        Facturation regroupée #{{ $groupedInvoice->id }}
                                    </a>
                                    <div class="billing-grouped-meta">
                                        @if($groupedInvoice->resmi)
                                            N° {{ $groupedInvoice->resmi }}
                                        @endif
                                        @if($groupedInvoice->pennylane_customer_invoice_id)
                                            {{ $groupedInvoice->resmi ? ' · ' : '' }}Pennylane #{{ $groupedInvoice->pennylane_customer_invoice_id }}
                                        @endif
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('posts.billing.update', $post->id) }}" class="mb-0">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="payment_destination" value="{{ $post->payment_destination }}">
                                        <input type="hidden" name="payment_status" value="{{ $post->payment_status ?: 'not_received' }}">
                                        <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                                        <select name="billing_status"
                                                class="billing-status-select {{ $billingClass }}"
                                                onchange="this.className = 'billing-status-select ' + (this.options[this.selectedIndex].dataset.class || ''); this.form.submit();"
                                                aria-label="Statut facturation {{ $post->id }}">
                                            @foreach($billingLabels as $value => $meta)
                                                <option value="{{ $value }}" data-class="{{ $meta[1] }}" {{ $billingStatus === $value ? 'selected' : '' }}>
                                                    {{ $meta[0] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif
                            </td>
                        @endif
                        <td>{{ $post->resmi }}</td>
                        <td>{{ $post->pax }}</td>
                        <td>{{ $post->child }}</td>
                        <td>
                            <a href="{{ route('posts.edit', $post->id) }}" class="btn btn-primary btn-block">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-heading">Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</div>
    <div class="text-center">
        {!! $posts->links() !!}
    </div>
</div>
