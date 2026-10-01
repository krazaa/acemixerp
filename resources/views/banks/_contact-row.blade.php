@php($i = $index)
@php($c = is_array($contact) ? $contact : [])
<div class="contact-row border rounded p-3 mb-2 bg-light-subtle">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong class="text-muted small">
            Contact
            @if(is_numeric($i))<span class="badge text-bg-secondary">#{{ $i + 1 }}</span>@endif
        </strong>
        <button type="button" class="btn btn-sm btn-outline-danger"
                onclick="bankRemoveContactRow(this)" title="Remove this contact">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label small mb-1">Name</label>
            <input name="bank_contacts[{{ $i }}][name]"
                   class="form-control form-control-sm" maxlength="128"
                   value="{{ $c['name'] ?? '' }}" placeholder="Jane Doe">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Role</label>
            <input name="bank_contacts[{{ $i }}][role]"
                   class="form-control form-control-sm" maxlength="64"
                   value="{{ $c['role'] ?? '' }}" placeholder="Relationship Manager">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Email</label>
            <input name="bank_contacts[{{ $i }}][email]" type="email"
                   class="form-control form-control-sm" maxlength="255"
                   value="{{ $c['email'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Phone</label>
            <input name="bank_contacts[{{ $i }}][phone]"
                   class="form-control form-control-sm" maxlength="32"
                   value="{{ $c['phone'] ?? '' }}">
        </div>
        <div class="col-12">
            <label class="form-label small mb-1">Notes</label>
            <input name="bank_contacts[{{ $i }}][notes]"
                   class="form-control form-control-sm" maxlength="500"
                   value="{{ $c['notes'] ?? '' }}">
        </div>
    </div>
</div>
