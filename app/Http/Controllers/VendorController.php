<?php

namespace App\Http\Controllers;

use App\Contracts\VendorManager;
use App\Enums\VendorStatus;
use App\Http\Requests\Vendors\StoreVendorRequest;
use App\Http\Requests\Vendors\UpdateVendorRequest;
use App\Models\Vendor;
use App\Models\VendorType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(private readonly VendorManager $vendors) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Vendor::class);

        return view('vendors.index', [
            'vendors' => $this->vendors->paginate($request->only(['search', 'status'])),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Vendor::class);

        return view('vendors.create', [
            'vendor' => new Vendor([
                'status' => VendorStatus::Pending,
            ]),
            'categories' => VendorType::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreVendorRequest $request): RedirectResponse
    {
        $vendor = $this->vendors->create($request->validated());

        return redirect()
            ->route('vendors.show', $vendor)
            ->with('status', "Vendor {$vendor->name} created.");
    }

    public function show(Vendor $vendor): View
    {
        $this->authorize('view', $vendor);

        return view('vendors.show', [
            'vendor' => $vendor->load(['addresses', 'creator', 'updater']),
        ]);
    }

    public function edit(Vendor $vendor): View
    {
        $this->authorize('update', $vendor);

        return view('vendors.edit', [
            'vendor' => $vendor->load('addresses'),
            'categories' => VendorType::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->vendors->update($vendor, $request->validated());

        return redirect()
            ->route('vendors.show', $vendor)
            ->with('status', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->authorize('delete', $vendor);

        $this->vendors->delete($vendor);

        return redirect()
            ->route('vendors.index')
            ->with('status', 'Vendor deleted.');
    }

    public function changeStatus(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('changeStatus', $vendor);

        $request->validate(['status' => ['required', 'string']]);

        $this->vendors->changeStatus(
            $vendor,
            VendorStatus::from((string) $request->input('status')),
        );

        return back()->with('status', 'Vendor status updated.');
    }
}
