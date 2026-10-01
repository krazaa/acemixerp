<x-default-layout>
@section('title', 'Edit Stock Count')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 m-0">Edit Stock Count</h1>
        <div class="text-muted small"><code>{{ $count->number }}</code></div>
    </div>
    <a href="{{ route('inventory.counts.show', $count) }}" class="btn btn-outline-secondary btn-sm">View</a>
</div>

<form method="POST" action="{{ route('inventory.counts.update', $count) }}" novalidate>
    @csrf @method('PUT')
    @include('inventory::counts._form', ['count' => $count])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('inventory.counts.show', $count) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit"
                @if(! in_array($count->status->value, ['draft', 'counting'])) disabled @endif>
            Save Changes
        </button>
    </div>
</form>
</x-default-layout>
