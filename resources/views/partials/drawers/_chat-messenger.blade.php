<!--begin::Chat drawer-->
<div id="kt_drawer_chat" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="chat" data-kt-drawer-activate="true" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'300px', 'md': '500px'}" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_drawer_chat_toggle" data-kt-drawer-close="#kt_drawer_chat_close">
	<!--begin::Messenger-->
	<div class="card w-100 border-0 rounded-0" id="kt_drawer_chat_messenger">
		<!--begin::Card header-->
		<div class="card-header pe-5" id="kt_drawer_chat_messenger_header">
			<!--begin::Title-->
			<div class="card-title">
				<!--begin::User-->
				<div class="d-flex justify-content-center flex-column me-3">
					<a href="#" class="fs-4 fw-bold text-gray-900 text-hover-primary me-1 mb-2 lh-1">Messages</a>
				</div>
				<!--end::User-->
			</div>
			<!--end::Title-->
			<!--begin::Card toolbar-->
			<div class="card-toolbar">
				<!--begin::Close-->
				<div class="btn btn-sm btn-icon btn-active-color-primary" id="kt_drawer_chat_close"><i class="ki-duotone ki-cross-square fs-2"><span class="path1"></span><span class="path2"></span></i></div>
				<!--end::Close-->
			</div>
			<!--end::Card toolbar-->
		</div>
		<!--end::Card header-->
		<!--begin::Card body-->
		<div class="card-body" id="kt_drawer_chat_messenger_body">
			<!--begin::Messages-->
			<div class="scroll-y me-n5 pe-5" data-kt-element="messages" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_drawer_chat_messenger_header, #kt_drawer_chat_messenger_footer" data-kt-scroll-wrappers="#kt_drawer_chat_messenger_body" data-kt-scroll-offset="0px">
				<!--begin::Message(in)-->
				<div class="d-flex justify-content-start mb-10">
					<!--begin::Wrapper-->
					<div class="d-flex flex-column align-items-start">
						<!--begin::User-->
						<div class="d-flex align-items-center mb-2">
							<!--begin::Avatar-->
							<div class="symbol symbol-35px symbol-circle">
								<img alt="Pic" src="{{ asset('assets/media/avatars/300-25.jpg') }}" />
							</div>
							<!--end::Avatar-->
							<!--begin::Details-->
							<div class="ms-3">
								<a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary me-1">Brian Cox</a>
								<span class="text-muted fs-7 mb-1">2 mins</span>
							</div>
							<!--end::Details-->
						</div>
						<!--end::User-->
						<!--begin::Text-->
						<div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start" data-kt-element="message-text">How likely are you to recommend our company to your friends and family ?</div>
						<!--end::Text-->
					</div>
					<!--end::Wrapper-->
				</div>
			</div>
			<!--end::Messages-->
		</div>
		<!--end::Card body-->
	</div>
	<!--end::Messenger-->
</div>
<!--end::Chat drawer-->
