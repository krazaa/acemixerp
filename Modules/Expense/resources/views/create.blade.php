<x-default-layout>
@section('title', 'New Expense Claim')
@section('sub-title', 'Add one or more expense entries. The claim total is calculated automatically.')

@section('toolbar-button')
<a href="{{ route('expense.index') }}" class="btn btn-outline-secondary">Back</a>
@endsection

    <form method="POST" action="{{ route('expense.store') }}">
        @csrf
        @include('expense::_form', ['claim' => null, 'employees' => $employees, 'departments' => $departments, 'expenseAccounts' => $expenseAccounts])
        <div class="d-flex justify-content-end gap-2 mt-3"><a href="{{ route('expense.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary">Save Draft</button></div>
    </form>

@push('script')
<script src="https://cdn.tiny.cloud/1/u2n79yb9awhgbr57xzkg8dus3yn5ippkkps998r8y4hzki6n/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script>
  tinymce.init({
    selector: 'textarea',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
  });
</script>
@endpush
</x-default-layout>
