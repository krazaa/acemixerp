<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Enums\OriginStatus;
use Modules\Inventory\Http\Requests\Origins\StoreOriginRequest;
use Modules\Inventory\Http\Requests\Origins\UpdateOriginRequest;
use Modules\Inventory\Models\Origin;
use Modules\Inventory\Services\OriginService;

class OriginController extends Controller
{
    public function __construct(private readonly OriginService $origins) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Origin::class);

        return view('inventory::origins.index', ['origins' => $this->origins->paginate($request->only(['search', 'status']))]);
    }

    public function create(): View
    {
        $this->authorize('create', Origin::class);

        return view('inventory::origins.create', ['origin' => new Origin(['status' => OriginStatus::Active])]);
    }

    public function store(StoreOriginRequest $request): RedirectResponse
    {
        $this->origins->create($request->validated());

        return to_route('inventory.origins.index')->with('status', 'Origin created.');
    }

    public function edit(Origin $origin): View
    {
        $this->authorize('update', $origin);

        return view('inventory::origins.edit', compact('origin'));
    }

    public function update(UpdateOriginRequest $request, Origin $origin): RedirectResponse
    {
        $this->origins->update($origin, $request->validated());

        return to_route('inventory.origins.index')->with('status', 'Origin updated.');
    }

    public function destroy(Origin $origin): RedirectResponse
    {
        $this->authorize('delete', $origin);
        $this->origins->delete($origin);

        return to_route('inventory.origins.index')->with('status', 'Origin deleted.');
    }
}
