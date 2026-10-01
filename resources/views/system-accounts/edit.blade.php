<x-default-layout>
@section('title', 'System Account Mappings')
@section('sub-title', ' Bind each business role to a specific chart-of-accounts entry. Downstream modules read these mappings — never hard-coded IDs (§25).')
@section('toolbar-button')

  <a href="{{ route('accounts.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back to CoA
        </a>
@endsection


@if(! empty($missing))
    <div class="alert alert-danger">
        <strong>Missing required mappings:</strong>
        @foreach($missing as $role)
            <span class="badge badge-danger me-1">{{ $role }}</span>
        @endforeach
        <div class="small mt-2">
            Posting will fail until every required role is mapped. This protects §25 and §61.
        </div>
    </div>
@endif

<form method="POST" action="{{ route('system-accounts.update') }}">
    @csrf @method('PUT')

    <div class="card border-0 shadow-sm">

        <div class="card-body">
            <div class="row g-3">
                @foreach($roles as $role)
                    @php($current = $mappings[$role->value] ?? null)
                    <div class="col-md-6">
                        <label class="form-label" for="role-{{ $role->value }}">
                            {{ $role->label() }}
                            @if($role->isRequired())
                                <span class="badge badge-danger ms-1">Required</span>
                            @endif
                        </label>
                        <select id="role-{{ $role->value }}"
                                name="mappings[{{ $role->value }}]"
                                class="form-select" data-control="select2" @if($role->isRequired() && ! $current) is-invalid @endif">
                            <option value="">— Not mapped —</option>
                            @foreach($accounts as $a)
                                <option value="{{ $a->id }}" @selected((int) $current === $a->id)>
                                    {{ $a->code }} — {{ $a->name }}
                                </option>
                            @endforeach
                        </select>
                        @if($role->isRequired() && ! $current)
                            <div class="invalid-feedback">
                                This role is required for posting.
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end">
            @can('coa.manage')
                <button class="btn btn-primary" type="submit">Save Mappings</button>
            @endcan
        </div>
    </div>
</form>
</x-default-layout>
