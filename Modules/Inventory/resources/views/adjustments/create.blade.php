<x-default-layout>

@section('title', 'New Stock Adjustment')
@section('toolbar-button')
  <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection


    <form method="POST" action="{{ route('inventory.adjustments.store') }}" novalidate>
        @csrf
        @include('inventory::adjustments._form', ['adjustment' => $adjustment])

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Draft</button>
        </div>
    </form>
</x-default-layout>
