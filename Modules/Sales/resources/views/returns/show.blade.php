<x-default-layout>
@section('title', $return->number)
@section('toolbar-button')
    <a class="btn btn-light btn-sm" href="{{ route('sales.returns.index') }}">Back</a>
@endsection

@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<section class="bg-body p-6 mb-6">
    <div class="d-flex flex-wrap justify-content-between gap-4"><div><h2 class="fs-4">{{ $return->invoice->customer?->name }}</h2><div class="text-muted">{{ $return->warehouse?->name }} / {{ $return->return_date->format('d M Y') }}</div></div><span class="badge badge-light-primary align-self-start">{{ ucfirst($return->status) }}</span></div>
    <p class="mt-5">Original invoice: @can('view',$return->invoice)<a href="{{ route('sales.sales-invoices.show',$return->invoice) }}">{{ $return->invoice->number }}</a>@else{{ $return->invoice->number }}@endcan</p>
    <p>{{ $return->reason }}</p>
    @if($return->rejection_reason)<div class="alert alert-danger">{{ $return->rejection_reason }}</div>@endif
    <div class="d-flex flex-wrap gap-3 border-top pt-4">
        @foreach(['requested','approved','received','inspected','accepted','credited','posted'] as $stage)<span class="badge {{ $return->status === $stage ? 'badge-primary' : 'badge-light' }}">{{ $loop->iteration }}. {{ ucfirst($stage) }}</span>@endforeach
    </div>
</section>
@php($step = $return->status === 'approved' ? 'receive' : ($return->status === 'received' ? 'inspect' : null))
@php($canEnter = $step && auth()->user()->can($step,$return))
<section class="bg-body p-6 mb-6">
    <h2 class="fs-4 mb-5">Returned Goods</h2>
    <form method="POST" action="{{ $step ? route('sales.returns.transition',['salesReturn'=>$return,'step'=>$step]) : '#' }}">
        @csrf @method('PATCH')
        <div class="table-responsive">
            <table class="table table-row-dashed align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Requested</th>
                        <th>Received</th>
                        <th>Accepted</th>
                        <th>Inspection Notes</th>
                        @if($canEnter)
                        <th>{{ $step === 'receive' ? 'Received Quantity' : 'Passed Quantity' }}</th>@endif</tr></thead><tbody>
            @foreach($return->lines as $line)<tr>
                <td>{{ $line->invoiceLine->item?->name }}</td><td>{{ $line->requested_quantity }}</td><td>{{ $line->received_quantity }}</td><td>{{ $line->accepted_quantity }}</td>
                <td>@if($canEnter && $step==='inspect')<textarea class="form-control" name="lines[{{ $loop->index }}][notes]" aria-label="Inspection notes for {{ $line->invoiceLine->item?->name }}" maxlength="2000">{{ old('lines.'.$loop->index.'.notes') }}</textarea>@else{{ $line->inspection_notes ?? '-' }}@endif</td>
                @if($canEnter)<td><input type="hidden" name="lines[{{ $loop->index }}][id]" value="{{ $line->id }}"><input type="number" class="form-control" name="lines[{{ $loop->index }}][quantity]" value="{{ old('lines.'.$loop->index.'.quantity') }}" min="0" step="0.0001" max="{{ $step==='receive' ? $line->requested_quantity : $line->received_quantity }}" required aria-label="Quantity for {{ $line->invoiceLine->item?->name }}"></td>@endif
            </tr>@endforeach
        </tbody></table></div>
        @if($canEnter)<div class="text-end"><button class="btn btn-primary">{{ $step==='receive' ? 'Confirm Goods Received' : 'Complete Quality Inspection' }}</button></div>@endif
    </form>
    <div class="d-flex flex-wrap gap-3 mt-5">
        @foreach(['approve'=>'Approve Request','accept'=>'Accept Return & Restock','credit'=>'Create Credit Note','post'=>'Post Accounting Adjustment'] as $action=>$label)
            @if($return->status === ['approve'=>'requested','accept'=>'inspected','credit'=>'accepted','post'=>'credited'][$action])
            @can($action,$return)<form method="POST" action="{{ route('sales.returns.transition',['salesReturn'=>$return,'step'=>$action]) }}">@csrf @method('PATCH')<button class="btn btn-primary">{{ $label }}</button></form>@endcan
            @endif
        @endforeach
    </div>
    @if($return->status === 'requested')@can('reject',$return)<form class="d-flex flex-wrap gap-3 mt-5" method="POST" action="{{ route('sales.returns.transition',['salesReturn'=>$return,'step'=>'reject']) }}">@csrf @method('PATCH')<input name="rejection_reason" class="form-control" aria-label="Rejection reason" placeholder="Rejection reason" required minlength="3" maxlength="2000"><button class="btn btn-light-danger">Reject Request</button></form>@endcan @endif
</section>
@if($return->creditNote)
<section class="bg-body p-6 mb-6"><h2 class="fs-4">Credit Note {{ $return->creditNote->number }}</h2><span class="badge badge-light-success">{{ ucfirst($return->creditNote->status) }}</span><div class="table-responsive"><table class="table mt-4"><thead><tr><th>Currency</th><th>Subtotal</th><th>GST</th><th>WHT</th><th>Customer Credit</th></tr></thead><tbody><tr><td>{{ $return->creditNote->currency_code }}</td>@foreach(['subtotal','tax','wht','total'] as $field)<td>{{ number_format((float)$return->creditNote->$field,2) }}</td>@endforeach</tr></tbody></table></div>@if($return->creditNote->journalEntry)<p>Journal: {{ $return->creditNote->journalEntry->number }} / Posted {{ $return->creditNote->posted_at->format('d M Y H:i') }}</p>@endif</section>
@endif
<section class="bg-body p-6"><h2 class="fs-4">Workflow History</h2><p>Requested by {{ $return->creator?->name }} / {{ $return->created_at->format('d M Y H:i') }}</p>@foreach(['approved','received','inspected','accepted','rejected'] as $stage)@if($return->{$stage.'_at'})<p>{{ ucfirst($stage) }} / {{ $return->{$stage.'_at'}->format('d M Y H:i') }} / User #{{ $return->{$stage.'_by'} }}</p>@endif @endforeach</section>
</x-default-layout>
