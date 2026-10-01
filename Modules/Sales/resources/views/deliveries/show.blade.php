<x-default-layout>
@section('title', $delivery->number)

@section('sub-title')
        <div class="text-muted small">
            {{ $delivery->delivery_date->format('d/m/Y') }}
            · SO
            <a href="{{ route('sales.sales-orders.show', $delivery->sales_order_id) }}">
                {{ $delivery->salesOrder?->number }}
            </a>
            · {{ $delivery->customer?->name }}
            <span class="badge text-bg-{{ $delivery->status->badgeClass() }} ms-2">
            {{ $delivery->status->label() }}
        </span>
        </div>
@endsection


@section('toolbar-button')
        @can('pick', $delivery)
            <form method="POST" action="{{ route('sales.deliveries.pick', $delivery) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-light-primary">Mark Picked</button>
            </form>
        @endcan
        @can('dispatch', $delivery)
            <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#dispatch-delivery-modal">
                Dispatch
            </button>
        @endcan
        @can('view', $delivery)
            <a href="{{ route('sales.deliveries.note', $delivery) }}"
            target="_blank"
            class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer"></i> Print Delivery Note
            </a>
        @endcan
        @can('markDelivered', $delivery)
            <form method="POST" action="{{ route('sales.deliveries.mark-delivered', $delivery) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-success">Mark Delivered</button>
            </form>
        @endcan
        @can('close', $delivery)
            @if($delivery->status->value === 'delivered')
                <form method="POST" action="{{ route('sales.deliveries.close', $delivery) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-dark">Close</button>
                </form>
            @endif
        @endcan
        @can('cancel', $delivery)
            <form method="POST" action="{{ route('sales.deliveries.cancel', $delivery) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Cancel this delivery?')">Cancel</button>
            </form>
        @endcan
        @can('update', $delivery)
            <a href="{{ route('sales.deliveries.edit', $delivery) }}" class="btn btn-sm btn-light-primary">Edit</a>
        @endcan
        @can('delete', $delivery)
            <form method="POST" action="{{ route('sales.deliveries.destroy', $delivery) }}" class="d-inline"
                  onsubmit="return confirm('Delete this draft delivery?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-light-danger">Delete</button>
            </form>
        @endcan
@endsection

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Details</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Customer</dt>
                    <dd class="col-sm-8">
                        <a href="{{ route('customers.show', $delivery->customer_id) }}">{{ $delivery->customer?->name }}</a>
                    </dd>
                    <dt class="col-sm-4">Contact Person</dt>
                    <dd class="col-sm-8">
                        {{ $delivery->customer?->c_person }}
                    </dd>
                    <dt class="col-sm-4">Contact #</dt>
                    <dd class="col-sm-8">
                        {{ $delivery->customer?->cp_phone }}
                    </dd>

                    <dt class="col-sm-4">Shipping Address</dt>
                    <dd class="col-sm-8">{{ $delivery->shipping_address ?: '—' }}</dd>
                    <hr>
                    <dt class="col-sm-4">Transporter</dt>
                        <dd class="col-sm-8">{{ $delivery->vendor?->name ?: '—' }} ({{ $delivery->vendor?->contact_person ?: '—' }})</dd>

                    @if($delivery->dispatched_at)
                        <dt class="col-sm-4">Vehicle #</dt>
                        <dd class="col-sm-8">{{ $delivery->vehicle_number ?: '—' }}</dd>
                        <dt class="col-sm-4">Driver</dt>
                        <dd class="col-sm-8">{{ $delivery->driver_name ?: '—' }}</dd>
                        <dt class="col-sm-4">Driver Contact</dt>
                        <dd class="col-sm-8">{{ $delivery->driver_contact ?: '—' }}</dd>
                        <dt class="col-sm-4">Driver CNIC</dt>
                        <dd class="col-sm-8">{{ $delivery->driver_cnic ?: '—' }}</dd>
                        <dt class="col-sm-4">Bilty #</dt>
                        <dd class="col-sm-8">{{ $delivery->bilty_number ?: '—' }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Timeline</h3>
            </div>
            <div class="card-body small">
                <div class="d-flex justify-content-between">
                    <span>Created</span>
                    <span>{{ $delivery->created_at?->format('d/m/Y H:i A') }}
                        @if($delivery->creator) · {{ $delivery->creator->name }}@endif</span>
                </div>
                @if($delivery->picked_at)
                    <div class="d-flex justify-content-between">
                        <span>Picked</span>
                        <span>{{ $delivery->picked_at->format('d/m/Y H:i A') }}
                            @if($delivery->picker) · {{ $delivery->picker->name }}@endif</span>
                    </div>
                @endif
                @if($delivery->dispatched_at)
                    <div class="d-flex justify-content-between text-primary">
                        <span>Dispatched</span>
                        <span>{{ $delivery->dispatched_at->format('d/m/Y H:i A') }}
                            @if($delivery->dispatcher) · {{ $delivery->dispatcher->name }}@endif</span>
                    </div>
                @endif
                @if($delivery->delivered_at)
                    <div class="d-flex justify-content-between text-success">
                        <span>Delivered</span>
                        <span>{{ $delivery->delivered_at->format('d/m/Y H:i A') }}
                            @if($delivery->deliverer) · {{ $delivery->deliverer->name }}@endif</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header">
    <h3 class="card-title">Lines</h3>
        <div class="card-toolbar">
        <span class="text-muted small">{{ $delivery->lines->count() }} line(s)</span>
        </div>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th><th>Item</th>
                    <th class="text-end">Quantity Shipped</th>
                    <th>Unit</th>
                    <th class="text-end">Unit Price</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($delivery->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td><code>{{ $line->item?->code }}</code> {{ $line->item?->name }}</td>
                        <td class="text-end">{{ number_format((float) $line->quantity, 2) }}</td>
                        <td>{{ $line->unit?->code ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $line->unit_price, 2) }}</td>
                        <td class="text-muted small">{{ $line->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>

@if($delivery->notes)
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header">
        <h3 class="card-title">Notes</h3>
        </div>
        <div class="card-body" style="white-space: pre-wrap;">{{ $delivery->notes }}</div>
    </div>
@endif

@can('dispatch', $delivery)
    <div class="modal fade" id="dispatch-delivery-modal" tabindex="-1" aria-labelledby="dispatch-delivery-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('sales.deliveries.dispatch', $delivery) }}" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="dispatch-delivery-modal-label">Dispatch {{ $delivery->number }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Stock movements will be recorded when this delivery is dispatched.</p>
                    <div class="mb-3">
                        <label class="form-label" for="vehicle_number">Vehicle No. <span class="text-danger">*</span></label>
                        <input id="vehicle_number" name="vehicle_number" type="text" class="form-control @error('vehicle_number') is-invalid @enderror" value="{{ old('vehicle_number') }}" maxlength="64" required>
                        @error('vehicle_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="driver_name">Driver Name <span class="text-danger">*</span></label>
                        <input id="driver_name" name="driver_name" type="text" class="form-control @error('driver_name') is-invalid @enderror" value="{{ old('driver_name') }}" maxlength="128" required>
                        @error('driver_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="driver_contact">Contact Number <span class="text-danger">*</span></label>
                        <input id="driver_contact" name="driver_contact" type="tel" class="form-control @error('driver_contact') is-invalid @enderror" value="{{ old('driver_contact') }}" maxlength="32" required>
                        @error('driver_contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="driver_cnic">CNIC <span class="text-danger">*</span></label>
                        <input id="driver_cnic" name="driver_cnic" type="text" inputmode="numeric" class="form-control @error('driver_cnic') is-invalid @enderror" value="{{ old('driver_cnic') }}" placeholder="35202-1234567-1" maxlength="15" required>
                        <div class="form-text">Stored securely and not displayed after dispatch.</div>
                        @error('driver_cnic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="bilty_number">Bilty No. <span class="text-danger">*</span></label>
                        <input id="bilty_number" name="bilty_number" type="text" class="form-control @error('bilty_number') is-invalid @enderror" value="{{ old('bilty_number') }}" maxlength="64" required>
                        @error('bilty_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Dispatch</button>
                </div>
            </form>
        </div>
    </div>

    @if($errors->hasAny(['vehicle_number', 'driver_name', 'driver_contact_number', 'driver_cnic', 'bilty_number']))
        @push('scripts')
            <script>
                new bootstrap.Modal(document.getElementById('dispatch-delivery-modal')).show();
            </script>
        @endpush
    @endif
@endcan
 </x-default-layout>
