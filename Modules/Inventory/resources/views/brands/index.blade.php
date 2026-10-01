<x-default-layout>
@section('title', 'Brands')

@section('toolbar-button')
    @can('create', \Modules\Inventory\Models\Brand::class)
        <a href="{{ route('inventory.brands.create') }}" class="btn btn-sm btn-primary">New Brand</a>
    @endcan
@endsection

@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@error('brand')<div class="alert alert-danger">{{ $message }}</div>@enderror

<form method="GET" class="row g-2 mb-4">
    <div class="col-md-6"><input name="search" class="form-control" value="{{ request('search') }}" placeholder="Search brands" aria-label="Search brands"></div>
    <div class="col-md-3"><select name="status" class="form-select" aria-label="Status"><option value="">All statuses</option>@foreach(\App\Enums\RecordStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
    <div class="col-md-3"><button class="btn btn-light-info">Filter</button> <a href="{{ route('inventory.brands.index') }}" class="btn btn-outline-secondary">Reset</a></div>
</form>
<div class="card">
<div class="card-body">
    <div class="table-responsive">
<table class="table align-middle mb-0">
    <thead>
        <tr><th>Name</th><th>Description</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
@forelse($brands as $brand)
<tr>
    <td>{{ $brand->name }}</td><td>{{ $brand->description }}</td>
    <td><span class="badge badge-{{ $brand->status->badgeClass() }}">{{ $brand->status->label() }}</span></td>
    <td class="text-end">
        @can('update', $brand)<a href="{{ route('inventory.brands.edit', $brand) }}" class="btn btn-sm btn-outline-primary">Edit</a>@endcan
        @can('delete', $brand)<form method="POST" action="{{ route('inventory.brands.destroy', $brand) }}" class="d-inline" onsubmit="return confirm('Delete this brand?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>@endcan
    </td>
</tr>
@empty
<tr><td colspan="4" class="text-center text-muted py-4">No brands found.</td></tr>
@endforelse
</tbody></table></div>
</div></div>
<div class="mt-3">{{ $brands->links() }}</div>
</x-default-layout>
