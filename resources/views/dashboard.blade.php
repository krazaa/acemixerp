<x-default-layout>
@section('title', 'Business Overview')
@section('toolbar-button')
    <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 flex-wrap">
        <label for="dashboard-month" class="text-gray-600 fs-7">Reporting month</label>
        <input id="dashboard-month" type="month" name="month" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}" class="form-control form-control-sm form-control-solid w-auto mw-100" required>
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Apply</button>
    </form>
@endsection
@php
    $tones = ['primary', 'success', 'warning', 'danger'];
    $statusColors = ['#1B84FF', '#F6C000', '#17C653', '#F8285A', '#78829D', '#20A4A8', '#8950FC'];
@endphp

<div class="business-dashboard">
    <header class="d-flex align-items-center justify-content-between flex-wrap gap-5 pb-6 mb-7 border-bottom">
        <div class="d-flex align-items-center flex-wrap gap-5">
            <img src="{{ $organization->logo_path ? route('organization.logo') : asset('assets/media/logos/acemix-logo.png') }}" alt="{{ $organization->name }}" width="160" height="54" class="dashboard-logo">
            <div class="border-start ps-5">
                <div class="fw-semibold text-gray-900 fs-5">{{ $month->format('F Y') }}</div>
                <div class="text-gray-500 fs-7 mt-1">{{ $financialYear?->name ?? 'Financial year not set' }}</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge badge-light fw-semibold">{{ $organization->currency_code }}</span>
            <span class="text-gray-500 fs-8">Updated {{ now()->format('d M Y, H:i') }}</span>
        </div>
    </header>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="row g-5 mb-8">
        @forelse($dashboard['cards'] as $card)
            @php($tone = $tones[$loop->index % count($tones)])
            <div class="col-12 col-sm-6 col-xl-3">
                <a href="{{ route($card['route']) }}" class="card card-bordered h-100 dashboard-metric text-reset">
                    <div class="card-body p-5">
                        <div class="d-flex align-items-center justify-content-between mb-5">
                            <span class="symbol symbol-40px"><span class="symbol-label bg-light-{{ $tone }}"><i class="bi bi-{{ $card['icon'] }} fs-3 text-{{ $tone }}" aria-hidden="true"></i></span></span>
                            <i class="bi bi-arrow-up-right text-gray-400" aria-hidden="true"></i>
                        </div>
                        <div class="fw-semibold text-gray-600 fs-7 mb-2">{{ $card['label'] }}</div>
                        <div class="metric-value text-gray-900">@if($card['money'])<span class="fs-8 text-gray-500 me-1">{{ $organization->currency_code }}</span>@endif{{ number_format($card['value'], $card['money'] ? 2 : 0) }}</div>
                        <div class="text-gray-500 fs-8 mt-3">{{ str_replace('this month', $month->format('M Y'), $card['detail']) }}</div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12"><div class="notice d-flex bg-light-primary rounded border border-primary border-dashed p-6">No module summaries are available for your current permissions.</div></div>
        @endforelse
    </div>

    <div class="row g-7 mb-8">
        @if(count($dashboard['charts']['series']))
            <section class="col-12 col-xl-8">
                <div class="dashboard-section h-100">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-6">
                        <div><h2 class="fs-4 fw-semibold mb-2">Financial Activity</h2><div class="text-gray-500 fs-7">Six months ending {{ $month->format('M Y') }}</div></div>
                        <span class="badge badge-light">{{ $organization->currency_code }}</span>
                    </div>
                    <div class="dashboard-chart"><canvas id="financial-chart" role="img" aria-label="Monthly sales, supplier invoices and reimbursed expenses"></canvas></div>
                    <details class="mt-5">
                        <summary class="text-primary fs-7 fw-semibold">Monthly totals</summary>
                        <div class="table-responsive mt-4"><table class="table table-row-dashed align-middle gs-0 gy-3">
                            <thead><tr class="text-gray-500 fs-8 text-uppercase"><th scope="col">Month</th>@foreach($dashboard['charts']['series'] as $series)<th scope="col" class="text-end">{{ $series['label'] }}</th>@endforeach</tr></thead>
                            <tbody>@foreach($dashboard['charts']['labels'] as $index => $label)<tr><td>{{ $label }}</td>@foreach($dashboard['charts']['series'] as $series)<td class="text-end">{{ number_format($series['data'][$index], 2) }}</td>@endforeach</tr>@endforeach</tbody>
                        </table></div>
                    </details>
                </div>
            </section>
        @endif
        <section class="col-12 {{ count($dashboard['charts']['series']) ? 'col-xl-4' : '' }}">
            <div class="dashboard-section h-100">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-2"><h2 class="fs-4 fw-semibold mb-0">Work Queues</h2><span class="symbol symbol-35px"><span class="symbol-label bg-light-warning"><i class="bi bi-inbox text-warning fs-4" aria-hidden="true"></i></span></span></div>
                <p class="text-gray-500 fs-7 mb-5">Current module status</p>
                <div class="dashboard-queue-list">
                    @forelse($dashboard['queues'] as $queue)
                        <a href="{{ route($queue['route']) }}" class="queue-link d-flex align-items-center gap-3 py-4 border-bottom border-gray-200 text-reset">
                            <span class="bullet bullet-vertical h-30px {{ $queue['count'] ? 'bg-warning' : 'bg-success' }}"></span>
                            <span class="flex-grow-1 fw-semibold fs-7">{{ $queue['label'] }}</span>
                            <span class="badge {{ $queue['count'] ? 'badge-light-warning' : 'badge-light-success' }}">{{ number_format($queue['count']) }}</span>
                            <i class="bi bi-chevron-right fs-8 text-gray-400" aria-hidden="true"></i>
                        </a>
                    @empty
                        <div class="text-gray-500 py-8">No work queues available.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <div class="row g-7 mb-8">
        @foreach(['expenses' => 'Expense Claim Status', 'production' => 'Production Order Status'] as $key => $title)
            @if(count($dashboard['charts'][$key]))
                <section class="col-12 col-lg-6">
                    <div class="dashboard-section h-100">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-6">
                            <h2 class="fs-4 fw-semibold mb-0">{{ $title }}</h2>
                            <span class="badge badge-light">{{ number_format(array_sum($dashboard['charts'][$key])) }} total</span>
                        </div>
                        <div class="dashboard-chart dashboard-chart-small"><canvas id="{{ $key }}-chart" role="img" aria-label="{{ $title }}"></canvas></div>
                        <div class="mt-5">
                            @foreach($dashboard['charts'][$key] as $status => $count)
                                <div class="d-flex align-items-center justify-content-between gap-3 py-2">
                                    <span class="d-flex align-items-center gap-3 text-gray-600 fs-7"><span class="bullet" style="background-color: {{ $statusColors[$loop->index % count($statusColors)] }}"></span>{{ \Illuminate\Support\Str::headline($status) }}</span>
                                    <strong class="text-gray-900 fs-7">{{ number_format($count) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        @endforeach
    </div>

    <section class="dashboard-section">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-5"><h2 class="fs-4 fw-semibold mb-0">Modules</h2><span class="badge badge-light">{{ count($dashboard['modules']) }}</span></div>
        <div class="row g-0">
            @foreach($dashboard['modules'] as $module)
                @php($tone = $tones[$loop->index % count($tones)])
                <div class="col-12 col-md-6 col-xxl-4">
                    <a class="module-link d-flex align-items-center gap-4 text-reset" href="{{ route($module['route']) }}">
                        <span class="symbol symbol-40px"><span class="symbol-label bg-light-{{ $tone }}"><i class="bi bi-{{ $module['icon'] }} fs-3 text-{{ $tone }}" aria-hidden="true"></i></span></span>
                        <div class="flex-grow-1"><div class="fw-semibold text-gray-900">{{ $module['name'] }}</div><div class="text-gray-500 fs-8 mt-1">{{ $module['detail'] }}</div></div>
                        <span class="fw-semibold text-gray-700">{{ number_format($module['count']) }}</span>
                        <i class="bi bi-chevron-right text-gray-400 fs-8" aria-hidden="true"></i>
                    </a>
                </div>
            @endforeach
            @can('reports.financial')
                <div class="col-12 col-md-6 col-xxl-4">
                    <a href="{{ route('reports.index') }}" class="module-link d-flex align-items-center gap-4 text-reset">
                        <span class="symbol symbol-40px"><span class="symbol-label bg-light-primary"><i class="bi bi-bar-chart fs-3 text-primary" aria-hidden="true"></i></span></span>
                        <div class="flex-grow-1"><div class="fw-semibold text-gray-900">Reports</div><div class="text-gray-500 fs-8 mt-1">Balances, receivables and payables</div></div>
                        <i class="bi bi-chevron-right text-gray-400 fs-8" aria-hidden="true"></i>
                    </a>
                </div>
            @endcan
        </div>
    </section>
</div>

<style>
    .business-dashboard { letter-spacing: 0; }
    .dashboard-logo { object-fit: contain; object-position: left center; }
    .dashboard-metric { border-radius: 6px; transition: border-color .15s; }
    .dashboard-metric:hover { border-color: var(--bs-primary); }
    .metric-value { font-size: 24px; font-weight: 600; line-height: 1.4; overflow-wrap: anywhere; font-variant-numeric: tabular-nums; }
    .dashboard-section { background: var(--bs-body-bg, #fff); padding: 24px; border-top: 1px solid var(--bs-border-color); min-width: 0; }
    .dashboard-chart { height: 320px; position: relative; width: 100%; }
    .dashboard-chart-small { height: 230px; }
    .dashboard-queue-list { max-height: 366px; overflow-y: auto; }
    .module-link { min-height: 88px; padding: 16px 12px; border-bottom: 1px dashed var(--bs-border-color); }
    .module-link:hover, .queue-link:hover { background: var(--bs-gray-100); }
    .business-dashboard .flex-grow-1 { min-width: 0; overflow-wrap: anywhere; }
    .business-dashboard a:focus-visible, .business-dashboard summary:focus-visible { outline: 2px solid var(--bs-primary); outline-offset: 3px; }
    .queue-link .badge { min-width: 32px; }
    @media(max-width: 575px) { .dashboard-section { padding: 20px 14px; } .dashboard-chart { height: 270px; } .dashboard-chart-small { height: 230px; } }
</style>
@push('scripts')
<script src="{{ asset('xassets/plugins/chart.js/Chart.min.js') }}"></script>
<script>
(() => {
    const data = {{ \Illuminate\Support\Js::from($dashboard['charts']) }};
    const currency = {{ \Illuminate\Support\Js::from($organization->currency_code) }};
    const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    Chart.defaults.global.defaultFontFamily = 'Inter, Arial, sans-serif';
    Chart.defaults.global.defaultFontColor = dark ? '#c8ccd2' : '#68717c';
    const grid = dark ? '#343942' : '#edf0f3';
    const format = value => Number(value).toLocaleString(undefined, {maximumFractionDigits: 2});
    const financial = document.getElementById('financial-chart');
    if (financial) {
        new Chart(financial, {
            type: 'bar',
            data: {labels: data.labels, datasets: data.series.map(series => ({label: series.label, data: series.data, backgroundColor: series.color, borderWidth: 0}))},
            options: {responsive: true, maintainAspectRatio: false, legend: {position: 'bottom', labels: {boxWidth: 12, padding: 20}},
                scales: {xAxes: [{gridLines: {display: false}}], yAxes: [{gridLines: {color: grid}, ticks: {beginAtZero: true, callback: format}}]},
                tooltips: {callbacks: {label: (item, chart) => chart.datasets[item.datasetIndex].label + ': ' + currency + ' ' + format(item.yLabel)}}}
        });
    }
    const colors = {{ \Illuminate\Support\Js::from($statusColors) }};
    ['expenses', 'production'].forEach(key => {
        const canvas = document.getElementById(key + '-chart');
        if (!canvas) return;
        new Chart(canvas, {
            type: key === 'expenses' ? 'doughnut' : 'horizontalBar',
            data: {labels: Object.keys(data[key]).map(status => status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())), datasets: [{data: Object.values(data[key]), backgroundColor: colors, borderWidth: 0}]},
            options: {responsive: true, maintainAspectRatio: false, legend: {display: false}, cutoutPercentage: 70,
                ...(key === 'production' ? {scales: {xAxes: [{ticks: {beginAtZero: true, precision: 0}, gridLines: {color: grid}}], yAxes: [{gridLines: {display: false}}]}} : {})}
        });
    });
})();
</script>
@endpush
</x-default-layout>
