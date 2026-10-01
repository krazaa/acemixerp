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

			<!--begin::Menu-->
            @canany(['accounting.view', 'coa.view', 'reports.view', 'banking.view'])
			<div class="menu menu-column menu-rounded menu-sub-indentionfw-semibold" data-kt-menu="true">
				<!--begin::Menu item-->
				<div class="menu-item menu-sub-indention menu-accordion  {{ request()->routeIs('accounts.*') || request()->routeIs('system-accounts.*') || request()->routeIs('financial-years.*') || request()->routeIs('accounting-periods.*')  || request()->routeIs('journals.*') || request()->routeIs('gl.trial-balance') || request()->routeIs('gl.aging.receivables')|| request()->routeIs('gl.aging.payables') || request()->routeIs('bank-accounts.*')  || request()->routeIs('bank-reconciliation.*')? 'here show' : '' }}" data-kt-menu-trigger="click">
					<!--begin::Menu link-->
					<a href="#" class="menu-link py-3">
						<span class="menu-icon">
							<i class="fa-solid fa-calculator fs-2"></i>
						</span>
						<span class="menu-title">Accounts</span>
						<span class="menu-arrow"></span>
					</a>
					<!--end::Menu link-->
					<!--begin::Menu sub-->
                    @can('coa.view')
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}">
								<span class="menu-bullet">
									<i class="fa-duotone fa-solid fa-book"></i>
								</span>
								<span class="menu-title">Chart Of Accounts</span>
							</a>
						</div>
						<!--end::Menu item-->
					</div>
                    @endcan

                    <!--begin::Menu sub-->
                    @can('coa.manage')
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('system-accounts.*') ? 'active' : '' }}" href="{{ route('system-accounts.edit') }}">
								<span class="menu-bullet">
									<i class="fa-duotone fa-solid fa-project-diagram"></i>
								</span>
								<span class="menu-title">System Mapping</span>
							</a>
						</div>
						<!--end::Menu item-->
					</div>
                    @endcan

                      <!--begin::Menu sub-->
                    @can('viewAny', \App\Models\FinancialYear::class)
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('financial-years.*') ? 'active' : '' }}" href="{{ route('financial-years.index') }}">
								<span class="menu-bullet">
									<i class="fa-duotone fa-solid fa-calendar-days"></i>
								</span>
								<span class="menu-title">Financial Years</span>
							</a>
						</div>
						<!--end::Menu item-->
					</div>
                    @endcan
                      <!--begin::Menu sub-->
                    @can('viewAny', \App\Models\AccountingPeriod::class)
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('accounting-periods.*') ? 'active' : '' }}" href="{{ route('accounting-periods.index') }}">
								<span class="menu-bullet">
									<i class="fa-duotone fa-solid fa-clock"></i>
								</span>
								<span class="menu-title">Accounting Periods</span>
							</a>
						</div>
						<!--end::Menu item-->
					</div>
                    @endcan

                     <!--begin::Menu sub-->
                    @can('accounting.view')
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('gl.*') ? 'active' : '' }}" href="{{ route('gl.index') }}">
								<span class="menu-bullet">
                                    <i class="fa-solid fa-book"></i>
								</span>
								<span class="menu-title">General Ledger</span>
							</a>
						</div>
						<!--end::Menu item-->
					</div>
                    @endcan

                      <!--begin::Menu sub-->
                    @can('accounting.view')
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('journals.*') ? 'active' : '' }}" href="{{ route('journals.index') }}">
								<span class="menu-bullet">
									<i class="fa-duotone fa-solid fa-book-journal-whills"></i>
								</span>
								<span class="menu-title">Journal Entries</span>
							</a>
						</div>
						<!--end::Menu item-->
					</div>
                    @endcan

                      <!--begin::Menu sub-->
                    @can('reports.financial')
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('reports.balance-sheet') ? 'active' : '' }}" href="{{ route('reports.balance-sheet') }}">
                                <span class="menu-bullet">
                                    <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>
                                </span>
                                <span class="menu-title">Balance Sheet</span>
                            </a>
                        </div>
                    </div>
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('gl.trial-balance') ? 'active' : '' }}" href="{{ route('gl.trial-balance') }}">
								<span class="menu-bullet">
									<i class="fa fa-balance-scale"></i>
								</span>
								<span class="menu-title">Trial Balance</span>
							</a>
						</div>
						<!--end::Menu item-->
					</div>

                      <!--begin::Menu sub-->
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('gl.aging.receivables') ? 'active' : '' }}" href="{{ route
                            ('gl.aging.receivables') }}">
								<span class="menu-bullet">
									<i class="fa-duotone fa-solid fa-clock-rotate-left"></i>
								</span>
								<span class="menu-title">AR Aging</span>
							</a>
						</div>
						<!--end::Menu item-->
					</div>

                      <!--begin::Menu sub-->
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('gl.aging.payables') ? 'active' : '' }}" href="{{ route('gl.aging.payables') }}">
								<span class="menu-bullet">
									<i class="fa-duotone fa-solid fa-file-invoice"></i>
								</span>
								<span class="menu-title">AP Aging</span>
							</a>
						</div>
						<!--end::Menu item-->
					</div>
                    @endcan

                      <!--begin::Menu sub-->
                    @can('banking.view')
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('bank-accounts.*') ? 'active' : '' }}" href="{{ route('bank-accounts.index') }}">
								<span class="menu-bullet">
									<i class="fa-duotone fa-solid fa-landmark"></i>
								</span>
								<span class="menu-title">Bank Accounts</span>
							</a>
						</div>
						<!--end::Menu item-->
					</div>

                      <!--begin::Menu sub-->
					<div class="menu-sub menu-sub-accordion">
						<div class="menu-item">
							<a class="menu-link {{ request()->routeIs('bank-reconciliation.*') ? 'active' : '' }}" href="{{ route('bank-reconciliation.index') }}">
								<span class="menu-bullet">
									<i class="fa-solid fa-landmark me-2"></i>
                                    <i class="fa-solid fa-file-invoice-dollar me-2"></i>
								</span>
								<span class="menu-title">Bank Reconciliation</span>
							</a>
						</div>
						<!--end::Menu item-->
					</div>
                    @endcan
				</div>
			</div>
            @endcanany
			<!--end::Menu-->
            @can('expense.view')
            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('expense.vendor-invoices.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
                        <i class="fa-solid fa-circle-dollar-to-slot fs-2"></i>

					</span>
					<span class="menu-title">Payments</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->

				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('expense.vendor-invoices.*') ? 'active' : '' }}" href="{{ route('expense.vendor-invoices.index') }}">
							<span class="menu-bullet">
                                <i class="fa-solid fa-money-bill-transfer"></i>
							</span>
							<span class="menu-title">Local Vendors</span>
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
						<a class="menu-link {{ request()->routeIs('expense.*') ? 'active' : '' }}" href="{{ route('expense.index') }}">
							<span class="menu-bullet">
                                <i class="fa-solid fa-money-bills"></i>
							</span>
							<span class="menu-title">Reimbursements</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
			</div>
            @endcan

            @can('procurement.view')
			<!--begin:Menu item-->
			<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('procurement.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
                        <i class="fa-solid fa-handshake fs-2">

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
                        @can('purchase_request.view')
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('procurement.*') ? 'active' : '' }}" href="{{ route('procurement.purchase-requisitions.index') }}">
							<span class="menu-bullet">
								<i class="fa fa-clipboard-list fs-2">
								<span class="path1"></span>
								<span class="path2"></span>
								</i>
							</span>
							<span class="menu-title">Requisitions</span>
						</a>
						<!--end:Menu link-->
                        @endcan
                        @can('rfq.view')
                        <!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('procurement.*') ? 'active' : '' }}" href="{{ route('procurement.rfqs.index') }}">
							<span class="menu-bullet">
                                <i class="fa-solid fa-file-invoice-dollar fs-2">
								<span class="path1"></span>
								<span class="path2"></span>
								</i>
							</span>
							<span class="menu-title">Rfqs</span>
						</a>
						<!--end:Menu link-->
                        @endcan
                        @can('purchase_order.view')
                        <!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('procurement.*') ? 'active' : '' }}" href="{{ route('procurement.purchase-orders.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-file-lines fs-2">
								<span class="path1"></span>
								<span class="path2"></span>
								</i>
							</span>
							<span class="menu-title">Purchase Order</span>
						</a>
						<!--end:Menu link-->
                        @endcan
                        <!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('procurement.*') ? 'active' : '' }}" href="{{ route('procurement.goods-receipts.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-receipt fs-2">
								<span class="path1"></span>
								<span class="path2"></span>
								</i>
							</span>
							<span class="menu-title">GRN Receipts</span>
						</a>
						<!--end:Menu link-->
                        @can('invoice.view')
                        <!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('procurement.*') ? 'active' : '' }}" href="{{ route('procurement.supplier-invoices.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-file-invoice fs-2">
								<span class="path1"></span>
								<span class="path2"></span>
								</i>
							</span>
							<span class="menu-title">Supplier Invoices</span>
						</a>
						<!--end:Menu link-->
                        @endcan
                        @can('payment.view')
                        <!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('procurement.*') ? 'active' : '' }}" href="{{ route('procurement.vendor-payments.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-money-check-alt fs-2">
								<span class="path1"></span>
								<span class="path2"></span>
								</i>
							</span>
							<span class="menu-title">Supplier Payments</span>
						</a>
						<!--end:Menu link-->
                        @endcan
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
			</div>
            @endcan
			<!--end:Menu item-->
            <!--end:Menu link-->
            @can('returns.view')
                @cannot('sales.view')<div class="menu-item"><a class="menu-link {{ request()->routeIs('sales.returns.*') ? 'active' : '' }}" href="{{ route('sales.returns.index') }}"><span class="menu-icon"><i class="bi bi-arrow-return-left"></i></span><span class="menu-title">Sales Returns</span></a></div>@endcannot
            @endcan
            @can('sales.view')
			<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('sales.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="fa-solid fa-store fs-2"></i>
					</span>
					<span class="menu-title">Sales</span>
					<span class="menu-arrow"></span>
				</span>
                {{-- @can('returns.view')<div class="menu-sub menu-sub-accordion"><div class="menu-item"><a class="menu-link {{ request()->routeIs('sales.returns.*') ? 'active' : '' }}" href="{{ route('sales.returns.index') }}"><span class="menu-bullet"><i class="bi bi-arrow-return-left"></i></span><span class="menu-title">Sales Returns</span></a></div></div>@endcan --}}
				<!--end:Menu link-->
                @can('quotation.view')
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('sales.quotations.*') ? 'active' : '' }}" href="{{ route('sales.quotations.index') }}">
							<span class="menu-bullet">
                                <i class="fa-solid fa-receipt"></i>
							</span>
							<span class="menu-title">Quotations</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
                @endcan

                <!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('sales.sales-orders.*') ? 'active' : '' }}" href="{{ route('sales.sales-orders.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-clipboard-list"></i>
							</span>
							<span class="menu-title">Sales Orders</span>
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
						<a class="menu-link {{ request()->routeIs('sales.deliveries.*') ? 'active' : '' }}" href="{{ route('sales.deliveries.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-box"></i>
							</span>
							<span class="menu-title">Deliveries</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
                @can('supplier_invoice.view')
                <!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('sales.sales-invoices.*') ? 'active' : '' }}" href="{{ route('sales.sales-invoices.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-file-invoice"></i>
							</span>
                            <span class="menu-title">Sales Invoices</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
                @endcan
                @can('receipt.view')
                 <!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('sales.customer-receipts.*') ? 'active' : '' }}" href="{{ route('sales.customer-receipts.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-receipt"></i>
							</span>
							<span class="menu-title">Customer Receipts</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
                @endcan
			</div>
            @endcan

            @can('hr.view')
			<!--end:Menu link-->
			<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('chart-of-accounts.*') || request()->routeIs('accounting.general-ledger.index') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">

						<i class="fa-duotone fa-solid fa-money-bill fs-2"></i>
					</span>
					<span class="menu-title">HR</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->

				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('hr.*') ? 'active' : '' }}" href="{{ route('hr.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-folder-tree"></i>
							</span>
							<span class="menu-title">Employee</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
			</div>
            @endcan
            @can('inventory.view')
            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('inventory.*')  ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
                        <i class="fa-solid fa-warehouse fs-2"></i>
					</span>
					<span class="menu-title">Inventory</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->

				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.dashboard') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-gauge-high"></i>
							</span>
							<span class="menu-title">Inventory Dashboard</span>
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
						<a class="menu-link {{ request()->routeIs('inventory.stock.*') ? 'active' : '' }}" href="{{ route('inventory.stock.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-boxes-stacked"></i>
							</span>
							<span class="menu-title">Stock On-Hand</span>
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
						<a class="menu-link {{ request()->routeIs('inventory.low-stock') ? 'active' : '' }}" href="{{ route('inventory.low-stock') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-arrow-trend-down"></i>
							</span>
							<span class="menu-title">Low Stock</span>
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
						<a class="menu-link {{ request()->routeIs('inventory.movements.*') ? 'active' : '' }}" href="{{ route('inventory.movements.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-people-carry-box"></i>
							</span>
							<span class="menu-title">Movement Ledger</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
                <!--begin:Menu sub-->
                 @can('inventory.transfer')
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('inventory.transfers.*') ? 'active' : '' }}" href="{{ route('inventory.transfers.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-right-left"></i>
							</span>
							<span class="menu-title">Transfers</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
                @endcan
				<!--end:Menu sub-->
                @can('inventory.adjust')
                <!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('inventory.adjustments.*') ? 'active' : '' }}" href="{{ route('inventory.adjustments.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-up-down-left-right"></i>
							</span>
							<span class="menu-title">Adjustments</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
                @endcan
				<!--end:Menu sub-->
                @can('inventory.count')
                <!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('inventory.counts.*') ? 'active' : '' }}" href="{{ route('inventory.counts.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-shapes"></i>
							</span>
							<span class="menu-title">Stock Counts</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
                @endcan
				<!--end:Menu sub-->
                <!--begin:Menu sub-->
                @can('reports.view')
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('inventory.valuation') ? 'active' : '' }}" href="{{ route('inventory.valuation') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-coins"></i>
							</span>
							<span class="menu-title">Valuation</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
                @endcan
				<!--end:Menu sub-->
			</div>
            @endcan

            @canany(['production.view', 'bom.view'])
            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('manufacturing.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
                        <i class="fa fa-industry fs-2" aria-hidden="true"></i>
					</span>
					<span class="menu-title">Production</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->
                @can('bom.view')
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('manufacturing.boms.*') ? 'active' : '' }}" href="{{ route('manufacturing.boms.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-file-lines"></i>
							</span>
							<span class="menu-title">Bills of Materials</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
                @endcan

                @can('production.view')
				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('manufacturing.production-orders.*') ? 'active' : '' }}" href="{{ route('manufacturing.production-orders.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-list-check"></i>
							</span>
							<span class="menu-title">Production Orders</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
				</div>
				<!--end:Menu sub-->
                @endcan
			</div>
            @endcan

			<!--end:Menu item-->
			<!--begin:Menu item-->


            @canany('settings.view')
            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('departments.*') || request()->routeIs('cost-centers.*') || request()->routeIs('designations.*')  || request()->routeIs('customers.*') || request()->routeIs('vendors.*') || request()->routeIs('categories.*') || request()->routeIs('units.*') || request()->routeIs('items.*')|| request()->routeIs('warehouses.*')|| request()->routeIs('banks.*')|| request()->routeIs('tax-rates.*')|| request()->routeIs('payment-terms.*') ? 'here show' : '' }}">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
                        <i class="fa-solid fa-layer-group fs-2"></i>
					</span>
					<span class="menu-title">Master Data</span>
					<span class="menu-arrow"></span>
				</span>
				<!--end:Menu link-->

				<!--begin:Menu sub-->
				<div class="menu-sub menu-sub-accordion">
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-sitemap"></i>
							</span>
							<span class="menu-title">Departments</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
                    <!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('designations.*') ? 'active' : '' }}" href="{{ route('designations.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-user-tie"></i>
							</span>
							<span class="menu-title">Designations</span>
						</a>
						<!--end:Menu link-->
					</div>
                    <!--begin:Menu item-->

                 @can('viewAny', \Modules\Inventory\Models\Brand::class)
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('inventory.brands.*') ? 'active' : '' }}" href="{{ route('inventory.brands.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-flag"></i>
							</span>
							<span class="menu-title">Brands</span>
						</a>
						<!--end:Menu link-->
					</div>
                    @endcan

                     @can('viewAny', \Modules\Inventory\Models\Origin::class)
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('inventory.origins.*') ? 'active' : '' }}" href="{{ route('inventory.origins.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-globe"></i>
							</span>
							<span class="menu-title">Origins</span>
						</a>
						<!--end:Menu link-->
					</div>
                    @endcan
					<!--end:Menu item-->
                    <!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('cost-centers.*') ? 'active' : '' }}" href="{{ route('cost-centers.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-file-invoice-dollar"></i>
							</span>
							<span class="menu-title">Cost Centers</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
                    <!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-users" aria-hidden="true"></i>
							</span>
							<span class="menu-title">Customers</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
                    <!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('vendors.*') ? 'active' : '' }}" href="{{ route('vendors.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-handshake"></i>
							</span>
							<span class="menu-title">Vendors</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
                    <!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-sitemap"></i>
							</span>
							<span class="menu-title">Categories</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
                    <!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-scale-balanced"></i>
							</span>
							<span class="menu-title">Units</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
                    <!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('items.*') ? 'active' : '' }}" href="{{ route('items.index') }}">
							<span class="menu-bullet">
								<i class="fa fa-list-alt" aria-hidden="true"></i>
							</span>
							<span class="menu-title">Items &amp; Services</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
                    <!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}" href="{{ route('warehouses.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-warehouse"></i>
							</span>
							<span class="menu-title">Warehouses</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
                    <!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('banks.*') ? 'active' : '' }}" href="{{ route('banks.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-bank"></i>
							</span>
							<span class="menu-title">Banks</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->

                     {{-- <!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('tax-rates.*') ? 'active' : '' }}" href="{{ route('tax-rates.index') }}">
							<span class="menu-bullet">
								<i class="fa-solid fa-percent"></i>
							</span>
							<span class="menu-title">Tax Rates</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item--> --}}

                     <!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('payment-terms.*') ? 'active' : '' }}" href="{{ route('payment-terms.index') }}">
							<span class="menu-bullet">
								<i class="fa-duotone fa-solid fa-file-contract"></i>
							</span>
							<span class="menu-title">Payment Terms</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->

				</div>
				<!--end:Menu sub-->
			</div>
            @endcan

			<div class="menu-item pt-5">
				<!--begin:Menu content-->
				<div class="menu-content">
					<span class="menu-heading fw-bold text-uppercase fs-7">Apps</span>
				</div>
				<!--end:Menu content-->
			</div>
			<!--end:Menu item-->
            @can('settings.view')
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
						<a class="menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Users</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
                    @can('root.view')
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Roles</span>
						</a>
						<!--end:Menu link-->
					</div>
                    @endcan
					<!--end:Menu item-->
                    @can('root.view')
					<!--begin:Menu item-->
					<div class="menu-item">
						<!--begin:Menu link-->
						<a class="menu-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Permissions</span>
						</a>
						<!--end:Menu link-->
					</div>
					<!--end:Menu item-->
                    @endcan
				</div>
				<!--end:Menu sub-->
			</div>
			<!--end:Menu item-->
            @endcan

		</div>
		<!--end::Menu-->
	</div>
	<!--end::Menu wrapper-->
</div>
<!--end::sidebar menu-->
