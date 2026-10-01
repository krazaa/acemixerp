<?php

namespace Modules\Inventory\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Http\Requests\Brands\StoreBrandRequest;
use Modules\Inventory\Http\Requests\Brands\UpdateBrandRequest;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Services\BrandService;

class BrandController extends Controller
{
    public function __construct(private readonly BrandService $brands) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Brand::class);

        return view('inventory::brands.index', ['brands' => $this->brands->paginate($request->only(['search', 'status']))]);
    }

    public function create(): View
    {
        $this->authorize('create', Brand::class);

        return view('inventory::brands.create', ['brand' => new Brand(['status' => RecordStatus::Active])]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $this->brands->create($request->validated());

        return to_route('inventory.brands.index')->with('status', 'Brand created.');
    }

    public function edit(Brand $brand): View
    {
        $this->authorize('update', $brand);

        return view('inventory::brands.edit', compact('brand'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->brands->update($brand, $request->validated());

        return to_route('inventory.brands.index')->with('status', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);
        $this->brands->delete($brand);

        return to_route('inventory.brands.index')->with('status', 'Brand deleted.');
    }
}
