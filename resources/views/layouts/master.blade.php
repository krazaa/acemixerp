<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<!--begin::Head-->
<head>
    <base href=""/>
    <title>@yield('page-title', config('app.name', 'Laravel'))</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8"/>
    <meta name="description" content=""/>
    <meta name="keywords" content=""/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta property="og:locale" content="en_US"/>
    <meta property="og:type" content="article"/>
    <meta property="og:title" content=""/>
    <link rel="canonical" href="{{ url()->current() }}"/>

    <!--begin::Fonts-->
    <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" /> -->
    <!--end::Fonts-->

    <!--begin::Global Stylesheets Bundle(used by all pages)-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->

    <!--begin::Vendor Stylesheets(used by this page)-->
    <!-- <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css')}}" rel="stylesheet" type="text/css" /> -->
        <!-- <link href="{{ asset('assets/plugins/custom/vis-timeline/vis-timeline.bundle.css')}}" rel="stylesheet" type="text/css" /> -->
    <!--end::Vendor Stylesheets-->

    <!--begin::Custom Stylesheets(optional)-->
    
    <!--end::Custom Stylesheets-->

    
</head>
<!--end::Head-->

<!--begin::Body-->
<body id="kt_app_body" 
        data-kt-app-layout="dark-sidebar" 
        data-kt-app-sidebar-enabled="true" 
        data-kt-app-sidebar-fixed="true" 
        data-kt-app-sidebar-hoverable="true" 
        data-kt-app-sidebar-push-header="true" 
        data-kt-app-sidebar-push-toolbar="true" 
        data-kt-app-sidebar-push-footer="true" 
        data-kt-app-toolbar-enabled="true" 
        data-kt-app-page-loading-enabled="true" 
        data-kt-app-page-loading="on" 
        class="app-default">

@include('partials/theme-mode/_init')

<!--begin::Page loading(append to body)-->
    <div class="page-loader">
        <span class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </span>
    </div>
    <!--end::Page loading-->

@yield('content')
@stack('modals')

<!--begin::Javascript-->
<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>        
<!--end::Global Javascript Bundle-->

<!--begin::Vendors Javascript(used by this page)-->
<!-- <script src="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script> -->
        <!-- <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/radar.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script> -->
        <!-- <script src="https://cdn.amcharts.com/lib/5/map.js"></script> -->
        <!-- <script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/geodata/continentsLow.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/geodata/usaLow.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZonesLow.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZoneAreasLow.js"></script> -->
        <!-- <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script> -->
<!--end::Vendors Javascript-->

<!--begin::Custom Javascript(optional)-->
    <!-- <script src="{{ asset('assets/js/widgets.bundle.js') }}"></script> -->
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        // ========================
        // Toastr Notifications
        // ========================
        toastr.options = {
          "closeButton": true,
          "debug": false,
          "newestOnTop": false,
          "progressBar": true,
          "positionClass": "toastr-top-right",
          "preventDuplicates": true,
          "onclick": null,
          "showDuration": "300",
          "hideDuration": "1000",
          "timeOut": "5000",
          "extendedTimeOut": "1000",
          "showEasing": "swing",
          "hideEasing": "linear",
          "showMethod": "fadeIn",
          "hideMethod": "fadeOut"
        };


        @if(Session::has('success'))
          toastr.success("{{ Session::get('success') }}");
        @endif

        @if(Session::has('error'))
          toastr.error("{{ Session::get('error') }}");
        @endif

        @if(Session::has('info'))
          toastr.info("{{ Session::get('info') }}");
        @endif

        @if(Session::has('warning'))
          toastr.warning("{{ Session::get('warning') }}");
        @endif
      });
    </script>      

<!--end::Custom Javascript-->
@stack('scripts')
@auth
@include('notifications.device-prompt')
<script src="{{ asset('assets/js/firebase-push.js') }}?v=2" defer
        data-push-user="{{ auth()->id() }}"
        data-push-login="{{ auth()->user()->last_login_at?->timestamp ?? 0 }}"
        data-push-config="{{ route('push.config', [], false) }}"
        data-push-store="{{ route('push.store', [], false) }}"
        data-push-test="{{ route('push.test', [], false) }}"></script>
@endauth
<!--end::Javascript-->

</body>
<!--end::Body-->

</html>
