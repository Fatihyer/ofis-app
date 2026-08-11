@php
  $billingLabels = [
    'to_invoice' => ['À facturer', 'warning'],
    'invoiced' => ['Facturé', 'success'],
    'do_not_invoice' => ['Ne pas facturer', 'secondary'],
  ];
  $destinationLabels = [
    'france' => ['Compte France', 'primary'],
    'turkey' => ['Compte Turquie', 'info'],
    'cash' => ['Espèces', 'dark'],
    'other' => ['Autre', 'secondary'],
  ];
  $paymentLabels = [
    'not_received' => ['Non encaissé', 'danger'],
    'partial' => ['Partiel', 'warning'],
    'received' => ['Encaissé', 'success'],
  ];
  $billingStatus = $post->billing_status ?: 'to_invoice';
  $paymentStatus = $post->payment_status ?: 'not_received';
  $paymentDestination = $post->payment_destination;
@endphp

<div class="card mb-3">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
    <div>
      <strong>Facturation & paiement</strong>
      <small class="text-muted ml-2">Suivi interne du dossier</small>
    </div>
    <div class="d-flex flex-wrap" style="gap:6px">
      <span class="badge badge-{{ $billingLabels[$billingStatus][1] ?? 'secondary' }}">{{ $billingLabels[$billingStatus][0] ?? $billingStatus }}</span>
      @if($paymentDestination)
        <span class="badge badge-{{ $destinationLabels[$paymentDestination][1] ?? 'secondary' }}">{{ $destinationLabels[$paymentDestination][0] ?? $paymentDestination }}</span>
      @endif
      <span class="badge badge-{{ $paymentLabels[$paymentStatus][1] ?? 'secondary' }}">{{ $paymentLabels[$paymentStatus][0] ?? $paymentStatus }}</span>
    </div>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('posts.billing.update', $post->id) }}">
      @csrf
      @method('PATCH')
      <div class="row">
        <div class="form-group col-md-4">
          <label>Statut facturation</label>
          <select name="billing_status" class="form-control">
            <option value="to_invoice" {{ $billingStatus === 'to_invoice' ? 'selected' : '' }}>À facturer</option>
            <option value="invoiced" {{ $billingStatus === 'invoiced' ? 'selected' : '' }} {{ $post->invoice->isEmpty() ? 'disabled' : '' }}>Facturé</option>
            <option value="do_not_invoice" {{ $billingStatus === 'do_not_invoice' ? 'selected' : '' }}>Ne pas facturer</option>
          </select>
          @if($post->invoice->isEmpty())
            <small class="text-muted">Facturé sera disponible après création d'une facture.</small>
          @endif
        </div>
        <div class="form-group col-md-4">
          <label>Destination paiement</label>
          <select name="payment_destination" class="form-control">
            <option value="" {{ !$paymentDestination ? 'selected' : '' }}>Non défini</option>
            <option value="france" {{ $paymentDestination === 'france' ? 'selected' : '' }}>Compte France</option>
            <option value="turkey" {{ $paymentDestination === 'turkey' ? 'selected' : '' }}>Compte Turquie</option>
            <option value="cash" {{ $paymentDestination === 'cash' ? 'selected' : '' }}>Espèces</option>
            <option value="other" {{ $paymentDestination === 'other' ? 'selected' : '' }}>Autre</option>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>Statut paiement</label>
          <select name="payment_status" class="form-control">
            <option value="not_received" {{ $paymentStatus === 'not_received' ? 'selected' : '' }}>Non encaissé</option>
            <option value="partial" {{ $paymentStatus === 'partial' ? 'selected' : '' }}>Partiel</option>
            <option value="received" {{ $paymentStatus === 'received' ? 'selected' : '' }}>Encaissé</option>
          </select>
        </div>
      </div>
      <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:8px">
        <div class="text-muted small">
          Factures liées: {{ $post->invoice->count() }}
          @if($post->invoice->count()) · Total: {{ number_format((float)$post->invoice->sum('amount'), 2, ',', ' ') }} € @endif
        </div>
        <button class="btn btn-primary btn-sm">Enregistrer</button>
      </div>
    </form>
  </div>
</div>
