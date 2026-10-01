<<x-default-layout>

@section('title', $asset->asset_number)


    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">{{ $asset->asset_number }} — {{ $asset->name }}</h1>
            <div class="text-muted">{{ $asset->vendor?->name ?? 'No vendor' }} · {{ $asset->location ?? 'No location' }}</div>
        </div>
        <span class="badge fs-6 text-bg-{{ $asset->status->badgeClass() }}">{{ $asset->status->label() }}</span>
    </div>

    <div class="row g-3 mb-3">
        @foreach ([['Original Cost', $asset->cost], ['Accumulated Depreciation', $asset->accumulated_depreciation], ['Carrying Amount', $asset->carrying_amount]] as [$label, $amount])
            <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="h4 mb-0">{{ number_format((float) $amount, 2) }}</div></div></div></div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Asset Details</div>
        <div class="card-body"><div class="row g-3 small">
            <div class="col-md-3"><span class="text-muted d-block">Vendor</span>{{ $asset->vendor?->name ?? '—' }}</div>
            <div class="col-md-3"><span class="text-muted d-block">Item / Service</span>{{ $asset->item?->name ?? '—' }}</div>
            <div class="col-md-3"><span class="text-muted d-block">Acquisition Date</span>{{ $asset->acquisition_date->format('d M Y') }}</div>
            <div class="col-md-3"><span class="text-muted d-block">In Service Date</span>{{ $asset->in_service_date?->format('d M Y') ?? '—' }}</div>
            <div class="col-md-3"><span class="text-muted d-block">Useful Life</span>{{ $asset->useful_life_months }} months</div>
            <div class="col-md-3"><span class="text-muted d-block">Salvage Value</span>{{ number_format((float) $asset->salvage_value, 4) }}</div>
            <div class="col-md-3"><span class="text-muted d-block">Department</span>{{ $asset->department?->name ?? '—' }}</div>
            <div class="col-md-3"><span class="text-muted d-block">Location</span>{{ $asset->location ?? '—' }}</div>
            <div class="col-md-4"><span class="text-muted d-block">Asset Account</span>{{ $asset->assetAccount->code }} — {{ $asset->assetAccount->name }}</div>
            <div class="col-md-4"><span class="text-muted d-block">Accumulated Depreciation</span>{{ $asset->accumulatedDepreciationAccount->code }} — {{ $asset->accumulatedDepreciationAccount->name }}</div>
            <div class="col-md-4"><span class="text-muted d-block">Depreciation Expense</span>{{ $asset->depreciationExpenseAccount->code }} — {{ $asset->depreciationExpenseAccount->name }}</div>
        </div></div>
    </div>

    @can('manageLifecycle', $asset)
        <div class="card border-0 shadow-sm mb-3"><div class="card-header bg-white fw-semibold">Lifecycle Transaction</div><div class="card-body">
            <div class="alert alert-info small">Capitalize: select the account currently holding the purchase cost. Depreciation needs only a date. Maintenance requires an expense and funding account. Disposal requires proceeds and gain/loss accounts.</div>
            <form method="POST" action="{{ route('fixed-assets.lifecycle', $asset) }}">@csrf
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Action *</label><select name="type" class="form-select" required><option value="capitalization">Capitalize</option><option value="depreciation">Run depreciation</option><option value="transfer">Transfer</option><option value="maintenance">Maintenance</option><option value="revaluation">Revaluation</option><option value="disposal">Dispose</option></select></div>
                    <div class="col-md-3"><label class="form-label">Date *</label><input type="date" name="transaction_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-3"><label class="form-label">Amount / disposal proceeds</label><input type="number" step="0.0001" min="0" name="amount" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">New carrying amount</label><input type="number" step="0.0001" min="0" name="new_carrying_amount" class="form-control"></div>
                    @foreach (['offset_account_id' => 'Purchase / expense / proceeds account', 'funding_account_id' => 'Funding account (maintenance)', 'gain_loss_account_id' => 'Gain/loss account (disposal)'] as $field => $label)
                        <div class="col-md-4"><label class="form-label">{{ $label }}</label><select name="{{ $field }}" class="form-select"><option value="">Select if needed</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
                    @endforeach
                    <div class="col-md-3"><label class="form-label">From location</label><input name="from_location" class="form-control" value="{{ $asset->location }}"></div><div class="col-md-3"><label class="form-label">To location</label><input name="to_location" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Description</label><input name="description" class="form-control" placeholder="Reason, service provider, or disposal reference"></div>
                </div><div class="text-end mt-3"><button class="btn btn-primary">Post Transaction</button></div>
            </form>
        </div></div>
    @endcan

    <div class="card border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Lifecycle History</div><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Date</th><th>Transaction</th><th>Description</th><th class="text-end">Amount</th><th class="text-end">Carrying Amount</th><th>Journal</th></tr></thead><tbody>@forelse($asset->transactions as $transaction)<tr><td>{{ $transaction->transaction_date->format('d M Y') }}</td><td>{{ $transaction->type->label() }}</td><td>{{ $transaction->description ?: '—' }}</td><td class="text-end">{{ number_format((float) $transaction->amount, 4) }}</td><td class="text-end">{{ number_format((float) $transaction->carrying_amount_after, 4) }}</td><td>@if($transaction->journalEntry)<a href="{{ route('journals.show', $transaction->journalEntry) }}">{{ $transaction->journalEntry->number }}</a>@else — @endif</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">No lifecycle transactions posted.</td></tr>@endforelse</tbody></table></div></div>
    </x-default-layout>
