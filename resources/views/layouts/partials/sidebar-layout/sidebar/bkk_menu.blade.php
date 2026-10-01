<!--begin::sidebar menu-->
<div class="app-sidebar-menu overflow-hidden flex-column-fluid">
	<!--begin::Menu wrapper-->
	<div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer" data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">
		<!--begin::Menu-->
		<div class="menu menu-column menu-rounded menu-sub-indention px-3 fw-semibold fs-6" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">
			<!--begin:Menu item-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<a class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
					<span class="menu-icon">
						<i class="ki-duotone ki-element-11 fs-2">
							<span class="path1"></span>
							<span class="path2"></span>
							<span class="path3"></span>
							<span class="path4"></span>
						</i></span>
					<span class="menu-title">Dashboard</span>

				</a>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('project.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="fa-duotone fa-solid fa-folder-open fs-2"></i>
					</span>
					<span class="menu-title">Projects Management</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('project.*') ? 'active' : '' }}" href="{{ route('project.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-folder-tree"></i>
							</span>
							<span class="menu-title">All Projects</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
				 		<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('project.tander-cdrs.*') ? 'active' : '' }}" href="{{ route('project.tander-cdrs.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-gavel"></i>
							</span>
							<span class="menu-title">Tanders CDR</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('project.*') ? 'active' : '' }}" href="{{ route('project.wbs.manage.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-sitemap"></i>
							</span>
							<span class="menu-title">WBS</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('project.boq.*') ? 'active' : '' }}" href="{{ route('project.boq.manage.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-cube"></i>
							</span>
							<span class="menu-title">BOQ</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('procurement.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-duotone ki-shop fs-2">
							 <span class="path1"></span>
							 <span class="path2"></span>
							 <span class="path3"></span>
							 <span class="path4"></span>
							 <span class="path5"></span>
						</i>
					</span>
					<span class="menu-title">Procurement</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('po.purchase-orders.*') ? 'active' : '' }}" href="{{ route('po.purchase-orders.index') }}">
							<span class="menu-bullet">
								<i class="ki-duotone ki-purchase fs-2">
								 <span class="path1"></span>
								 <span class="path2"></span>
								</i>
							</span>
							<span class="menu-title">Purchase Orders (POs)</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('po.deliveries.*') ? 'active' : '' }}" href="{{ route('po.deliveries.index') }}">
							<span class="menu-bullet">
								<i class="ki-duotone ki-delivery-3 fs-2">
								 <span class="path1"></span>
								 <span class="path2"></span>
								 <span class="path3"></span>
								</i>
							</span>
							<span class="menu-title">Deliveries</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
			</div>
			<!--end:Menu item-->

			<!--begin:Menu item-->
			<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('assets.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">

					<span class="menu-icon">
						<i class="fa-solid fa-hammer fs-2"></i>
					</span>
					<span class="menu-title">Equipment & Vehicle</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('assets.equipment-vehicle.*') ? 'active' : '' }}" href="{{ route('assets.equipment-vehicle.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-screwdriver-wrench fs-2"></i>
							</span>
							<span class="menu-title">Equipments</span>
						</a>
						<!--end:Menu link-->
					</div>

					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('assets.fuel-logs.analytics') ? 'active' : '' }}" href="{{ route('assets.fuel-logs.analytics') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-chart-line fs-2"></i>
							</span>
							<span class="menu-title">Fuel Analytics </span>
						</a>
						<!--end:Menu link-->
					</div>
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('assets.fuel-logs.index') ? 'active' : '' }}" href="{{ route('assets.fuel-logs.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-gas-pump fs-2"></i>
							</span>
							<span class="menu-title">Fuel Logs </span>
						</a>
						<!--end:Menu link-->
					</div>

					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('assets.vehicles') ? 'active' : '' }}" href="{{ route('assets.vehicles') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-truck-pickup fs-2"></i>
							</span>
							<span class="menu-title">Vehicles</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('assets.maintenances.*') ? 'active' : '' }}" href="{{ route('assets.maintenances.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-hammer fs-2"></i>
							</span>
							<span class="menu-title">Maintenance</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
 
			</div>
			<!--end:Menu item-->

			<!--begin:Menu item-->
			<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('procurement.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="fa-duotone fa-solid fa-chart-diagram fs-2"></i>
					</span>
					<span class="menu-title">Subcontractors</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('subcontracts.contractors.*') ? 'active' : '' }}" href="{{ route('subcontracts.contractors.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-helmet-safety fs-2"></i>
							</span>
							<span class="menu-title">Contracts</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
						<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('subcontracts.work-orders.*') ? 'active' : '' }}" href="{{ route('subcontracts.work-orders.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-list-check fs-2"></i>
							</span>
							<span class="menu-title">Work Orders</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
 
			</div>
			<!--end:Menu item-->

			<!--begin:Menu item-->
			<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('procurement.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="fa-duotone fa-solid fa-people-roof fs-2"></i>
					</span>
					<span class="menu-title">HR & Payrol</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('hrs.*') ? 'active' : '' }}" href="{{ route('hrs.dashboard') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-folder-tree"></i>
							</span>
							<span class="menu-title">Dashboard</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->

					<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('hrs.employees.*') ? 'active' : '' }}" href="{{ route('hrs.employees.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-user-group"></i>
							</span>
							<span class="menu-title">Employees</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->

					<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('hrs.loans.*') ? 'active' : '' }}" href="{{ route('hrs.loans.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-hand-holding-dollar"></i>
							</span>
							<span class="menu-title">Loan Management </span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->

					<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('hrs.grades.*') ? 'active' : '' }}" href="{{ route('hrs.grades.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-ranking-star"></i>
							</span>
							<span class="menu-title">Salary Grades </span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->

					<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('hrs.payroll.*') ? 'active' : '' }}" href="{{ route('hrs.payroll.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-file-invoice-dollar"></i>
							</span>
							<span class="menu-title">Payroll Management </span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
 
			</div>
			<!--end:Menu item-->
		<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('chart-of-accounts.*') || request()->routeIs('accounting.general-ledger.index') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="fa-duotone fa-solid fa-money-bill fs-2"></i>
					</span>
					<span class="menu-title">Accounts</span>
					<span class="menu-arrow"></span>
				</span>
			<!--end:Menu link-->

		<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('chart-of-accounts.*') ? 'active' : '' }}" href="{{ route('chart-of-accounts.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-folder-tree"></i>
							</span>
							<span class="menu-title">Chart Of Accounts</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->

				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('accounting.general-ledger.*') ? 'active' : '' }}" href="{{ route('accounting.general-ledger.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-folder-tree"></i>
							</span>
							<span class="menu-title">General Ledger</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>

				<!--end:Menu sub-->
				<!--begin:Menu item-->
				
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('vouchers.*') ? 'here show' : '' }}">
					<!--begin:Menu link-->
					<span class="menu-link {{ request()->routeIs('vouchers.index') ? 'active' : '' }}">
						<span class="menu-bullet">
							<span class="fa-solid fa-receipt fs-2"></span>

						</span>
						<span class="menu-title">Vouchers</span>
						<span class="menu-arrow"></span>
					</span>
					<!--end:Menu link-->
					<!--begin:Menu sub-->
					<div class="menu-sub menu-sub-accordion menu-active-bg">
						<!--begin:Menu item-->
						<div class="menu-item">
							<!--begin:Menu link-->
							<a class="menu-link {{ request()->routeIs('vouchers.index') ? 'active' : '' }}" href="{{ route('vouchers.index')}}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">All Vouchers list</span>
							</a>
							<!--end:Menu link-->
						</div>
						<!--end:Menu item-->
						<!--begin:Menu item-->
						<div class="menu-item">
							<!--begin:Menu link-->
							<a class="menu-link" href="{{ route('vouchers.bpv')}}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">Bank Payment Voucher</span>
							</a>
							<!--end:Menu link-->
						</div>
						<!--end:Menu item-->
						<!--begin:Menu item-->
						<div class="menu-item">
							<!--begin:Menu link-->
							<a class="menu-link" href="{{ route('vouchers.brv')}}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">Bank Receipt Voucher</span>
							</a>
							<!--end:Menu link-->
						</div>
						<!--end:Menu item-->
						<!--begin:Menu item-->
						<div class="menu-item">
							<!--begin:Menu link-->
							<a class="menu-link" href="{{ route('vouchers.cpv')}}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">Cash Payment Voucher</span>
							</a>
							<!--end:Menu link-->
						</div>
						<!--end:Menu item-->
						<!--begin:Menu item-->
						<div class="menu-item">
							<!--begin:Menu link-->
							<a class="menu-link" href="{{ route('vouchers.crv')}}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">Cash Receipt Voucher</span>
							</a>
							<!--end:Menu link-->
						</div>
						<!--end:Menu item-->
						<!--begin:Menu item-->
						<div class="menu-item">
							<!--begin:Menu link-->
							<a class="menu-link" href="{{ route('vouchers.jv')}}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">Journal Voucher</span>
							</a>
							<!--end:Menu link-->
						</div>
						<!--end:Menu item-->
						<!--begin:Menu item-->
						<div class="menu-item">
							<!--begin:Menu link-->
							<a class="menu-link" href="{{ route('vouchers.ledger')}}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">Ledger</span>
							</a>
							<!--end:Menu link-->
						</div>
						<!--end:Menu item-->
					</div>
					<!--end:Menu sub-->
				</div>
				<!--end:Menu item-->
			</div>
	
	<!--end:Menu sub-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<a class="menu-link {{ request()->routeIs('gallery.index') ? 'active' : '' }}" href="{{ route('gallery.index') }}" >
					<span class="menu-icon">
					<i class="fa-solid fa-images fs-2"></i></span>
					<span class="menu-title">Photos Gallery</span>
				</a>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->

<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('reports.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-duotone ki-abstract-28 fs-2">
							<span class="path1"></span>
							<span class="path2"></span>
						</i>
					</span>
					<span class="menu-title">Reports</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('assets.vehicles.*') ? 'active' : '' }}" href="{{ route('assets.vehicles.allProfitLoss')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">All Vehicles Profit & Loss </span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('financial.statements.dashboard') ? 'active' : '' }}" href="{{ route('financial.statements.dashboard')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Financial Statements </span>
						</a>
						<!--end:Menu link-->
					</div>
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('reports.cash') ? 'active' : '' }}" href="{{ route('reports.cash')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Cash Book</span>
						</a>
						<!--end:Menu link-->
					</div>
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('reports.bank') ? 'active' : '' }}" href="{{ route('reports.bank')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Bank Book</span>
						</a>
						<!--end:Menu link-->
					</div>
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('reports.general-ledger2') ? 'active' : '' }}" href="{{ route('reports.general-ledger2')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">General Ledger</span>
						</a>
						<!--end:Menu link-->
					</div>
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('reports.party-ledger') ? 'active' : '' }}" href="{{ route('reports.party-ledger')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Party Ledger</span>
						</a>
						<!--end:Menu link-->
					</div>
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('reports.project') ? 'active' : '' }}" href="{{ route('reports.project')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Project Wise</span>
						</a>
						<!--end:Menu link-->
					</div>
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('reports.trial') ? 'active' : '' }}" href="{{ route('reports.trial')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Trial Balance</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
			</div>

			<!--begin:Menu item-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<a class="menu-link {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}" href="{{ route('admin.vendors.index')}}">
					<span class="menu-bullet">
						<span class="fa-solid fa-store fs-2"></span>
					</span>
					<span class="menu-title">Vendors</span>
				</a>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<a class="menu-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}" href="{{ route('admin.clients.index')}}">
					<span class="menu-bullet">

						<span class="fa-solid fa-people-line fs-2"></span>
					</span>
					<span class="menu-title">Clients</span>
				</a>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->


			<!--begin:Menu item-->
			<div class="menu-item pt-5">
				<!--begin:Menu content-->
				<div class="menu-content">
					<span class="menu-heading fw-bold text-uppercase fs-7">Apps</span>
				</div>
				<!--end:Menu content-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.manage.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-duotone ki-abstract-28 fs-2">
							<span class="path1"></span>
							<span class="path2"></span>
						</i>
					</span>
					<span class="menu-title">Users Management</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('admin.manage.users.*') ? 'active' : '' }}" href="{{ route('admin.manage.users.index')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Users</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('admin.manage.roles.*') ? 'active' : '' }}" href="{{ route('admin.manage.roles.index')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Roles</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('admin.manage.permissions.*') ? 'active' : '' }}" href="{{ route('admin.manage.permissions.index')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Permissions</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
			</div>
			<!--end:Menu item-->

			<!--begin:Menu item-->
			<div class="menu-item pt-5">
				<!--begin:Menu content-->
				<div class="menu-content">
					<span class="menu-heading fw-bold text-uppercase fs-7">System</span>
				</div>
				<!--end:Menu content-->
			</div>
				<!--begin:Menu item-->
			<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('project.settings.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-duotone ki-setting-2 fs-2">
						 <span class="path1"></span>
						 <span class="path2"></span>
						</i>
					</span>
					<span class="menu-title">Settings</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('project.settings.materials.*') ? 'active' : '' }}" href="{{ route('project.settings.materials.index')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Material Categories</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Departments</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('project.settings.units.*') ? 'active' : '' }}" href="{{ route('project.settings.units.index')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Unit Categories</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('project.settings.wbs.*') ? 'active' : '' }}" href="{{ route('project.settings.wbs.index')}}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">WBS Categories</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
					
				</div>
			</div>
		</div>
		<!--end::Menu-->
	</div>
	<!--end::Menu wrapper-->
</div>
<!--end::sidebar menu-->
