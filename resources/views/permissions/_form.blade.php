@php($isEdit = $permission !== null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
        <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $permission?->name) }}"
               placeholder="resource.action (e.g. journal.post)"
               pattern="[a-z][a-z0-9_]*\.[a-z][a-z0-9_-]*"
               @if($isEdit) readonly @endif required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if($isEdit)<div class="form-text">Permission name is immutable.</div>@endif
    </div>
    <div class="col-md-6">
        <label class="form-label" for="group">Group <span class="text-danger">*</span></label>
        <input id="group" name="group" class="form-control @error('group') is-invalid @enderror"
               value="{{ old('group', $permission?->group) }}" required>
        @error('group')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Description</label>
        <input id="description" name="description" class="form-control"
               value="{{ old('description', $permission?->description) }}">
    </div>
</div>
