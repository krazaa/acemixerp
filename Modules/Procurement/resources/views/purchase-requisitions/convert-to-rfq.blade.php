<x-default-layout>
@section('title', 'Create RFQ from Requisition')
@section('sub-title')
    <span class="text-muted">{{ $requisition->number }}</span>
@endsection
@section('toolbar-button')
    <a href="{{ route('procurement.purchase-requisitions.show', $requisition) }}" class="btn btn-sm btn-light">Back to Requisition</a>
@endsection

<form method="POST" action="{{ route('procurement.purchase-requisitions.store-rfq', $requisition) }}">
    @csrf

    @if($errors->any())
        <div class="alert alert-danger mb-6" role="alert">
            <div class="fw-bold mb-2">Please check the following before creating your RFQ.</div>
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="notice d-flex bg-light-primary rounded border border-primary border-dashed p-6 mb-6">
        <div>
            <div class="fw-bold text-gray-900 mb-1">Review the items, choose vendors, and set a deadline.</div>
            <div class="text-gray-700">Your requisition details will be copied to a draft RFQ. You can review it before issuing it to vendors.</div>
        </div>
    </div>

    <div class="row g-6">
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">
                        <h2 class="fs-5 fw-bold mb-0">Requisition items</h2>
                        <span class="badge badge-light-primary ms-3">{{ $requisition->lines->count() }} items</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="bg-light rounded p-5 mb-5">
                        <div class="fs-7 text-muted mb-1">Source requisition · {{ $requisition->number }}</div>
                        <div class="fw-semibold text-gray-900 text-break">{{ $requisition->purpose }}</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gy-5">
                            <caption class="visually-hidden">Items copied from {{ $requisition->number }} to the draft RFQ</caption>
                            <thead>
                                <tr class="text-muted fw-semibold fs-7">
                                    <th scope="col">Item / specification</th>
                                    <th scope="col">Brand / origin</th>
                                    <th scope="col" class="text-end text-nowrap">Quantity</th>
                                    <th scope="col" class="text-end text-nowrap">Required by</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requisition->lines as $line)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900">{{ $line->item?->name ?? 'Unavailable item' }}</div>
                                            @if($line->specification)
                                                <div class="text-muted fs-7 mt-1 text-break">{{ $line->specification }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-gray-800">{{ $line->brand?->name ?? '—' }}</div>
                                            <div class="text-muted fs-7 mt-1">{{ $line->origin?->name ?? '—' }}</div>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <span class="fw-semibold">{{ rtrim(rtrim(number_format((float) $line->quantity, 4, '.', ','), '0'), '.') }}</span>
                                            <span class="text-muted fs-7">{{ $line->unit?->code }}</span>
                                        </td>
                                        <td class="text-end text-nowrap text-gray-700">{{ $line->required_date?->format('d M Y') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-8">This requisition has no items to convert.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted fs-7 mt-3">Quantities, units, specifications, department, and cost center are carried over from the requisition.</div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title"><h2 class="fs-5 fw-bold mb-0">RFQ settings</h2></div>
                </div>
                <div class="card-body pt-0">
                    <div class="mb-7">
                        <label class="form-label required" for="due_date">Response deadline</label>
                        <input class="form-control form-control-solid @error('due_date') is-invalid @enderror"
                               id="due_date" name="due_date" type="date"
                               value="{{ old('due_date', now()->addDays(14)->toDateString()) }}"
                               min="{{ now()->toDateString() }}" aria-describedby="deadline-help" required>
                        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="deadline-help" class="form-text">The last date for vendors to respond with their quotations.</div>
                    </div>
                    <div>
                        <label class="form-label" for="terms">Terms and conditions <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea class="form-control form-control-solid @error('terms') is-invalid @enderror"
                                  id="terms" name="terms" rows="6" maxlength="2000"
                                  aria-describedby="terms-help" placeholder="Add delivery requirements, payment terms, or quotation instructions.">{{ old('terms') }}</textarea>
                        @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="terms-help" class="form-text">Up to 2,000 characters.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card card-flush">
                <div class="card-body">
                    <fieldset>
                        <legend class="fs-5 fw-bold mb-2">Choose vendors</legend>
                        <p class="text-muted mb-5">Select the vendors you want to request quotations from.</p>
                        <div class="row g-4">
                            @forelse($vendors as $vendor)
                                <div class="col-md-6 col-xl-4">
                                    <label class="d-flex align-items-center gap-4 border rounded p-5 h-100 cursor-pointer" for="vendor-{{ $vendor->id }}">
                                        <span class="form-check form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" id="vendor-{{ $vendor->id }}" name="vendor_ids[]"
                                                   type="checkbox" value="{{ $vendor->id }}"
                                                   @checked(in_array($vendor->id, old('vendor_ids', [])))>
                                        </span>
                                        <span class="text-break">
                                            <span class="d-block fw-semibold text-gray-900">{{ $vendor->name }}</span>
                                            <span class="d-block text-muted fs-7 mt-1">{{ $vendor->code }}</span>
                                        </span>
                                    </label>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-warning mb-0">No active vendors are available. Add or activate a vendor before converting this requisition.</div>
                                </div>
                            @endforelse
                        </div>
                        @error('vendor_ids')<div class="text-danger fs-7 mt-3">{{ $message }}</div>@enderror
                    </fieldset>
                </div>
                <div class="card-footer d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-4">
                    <span class="text-muted fs-7">The RFQ will be saved as a draft for your review.</span>
                    <div class="d-flex flex-wrap gap-3">
                        <a class="btn btn-light" href="{{ route('procurement.purchase-requisitions.show', $requisition) }}">Cancel</a>
                        <button class="btn btn-primary" type="submit" @disabled($vendors->isEmpty() || $requisition->lines->isEmpty())>Create Draft RFQ</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script src="{{ asset('assets/plugins/custom/tinymce/tinymce.bundle.js') }}"></script>
<script>
    if (window.tinymce) {
        tinymce.init({
            selector: '#terms',
            base_url: @json(asset('assets/plugins/custom/tinymce')),
            suffix: '.min',
            height: 300,
            menubar: false,
            plugins: 'paste table lists fullscreen wordcount',
            toolbar: ' toolbar:| blocks fontfamily fontsize | bold italic underline | bullist | table | fullscreen  ',
            paste_as_text: true,
            setup: function (editor) {
                editor.on('SaveContent', function (event) {
                    event.content = editor.getContent({ format: 'text' });
                });
                editor.on('change input undo redo', function () {
                    editor.save();
                });
                editor.getElement().form.addEventListener('submit', function () {
                    editor.save();
                });
            }
        });
    }
</script>
@endpush
</x-default-layout>
