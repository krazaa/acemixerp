<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
	<!--begin::Toolbar container-->
	<div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
		@include('/layouts/partials/sidebar-layout/_page-title')

		<!--begin::Actions-->
		<div class="d-flex align-items-center gap-2 gap-lg-3">
			@yield('toolbar-button')
		</div>
		<!--end::Actions-->
	</div>
	<!--end::Toolbar container-->
</div>
<!--end::Toolbar-->
