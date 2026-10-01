<?php

declare(strict_types=1);

namespace Modules\FixedAssets\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Department;
use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\FixedAssets\Http\Requests\LifecycleAssetRequest;
use Modules\FixedAssets\Http\Requests\StoreAssetRequest;
use Modules\FixedAssets\Models\Asset;
use Modules\FixedAssets\Services\AssetService;

class AssetController extends Controller
{
    public function __construct(private readonly AssetService $assets) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Asset::class);

        return view('fixedassets::assets.index', ['assets' => Asset::query()->with('vendor')->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))->orderByDesc('id')->paginate(25)->withQueryString()]);
    }

    public function create(): View
    {
        $this->authorize('create', Asset::class);

        return view('fixedassets::assets.create', $this->formData());
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $asset = $this->assets->create($request->validated(), (int) $request->user()->id);

        return redirect()->route('fixed-assets.show', $asset)->with('status', 'Asset registered as a draft. Capitalize it when ready.');
    }

    public function show(Asset $asset): View
    {
        $this->authorize('view', $asset);

        return view('fixedassets::assets.show', ['asset' => $asset->load(['vendor', 'item', 'department', 'assetAccount', 'accumulatedDepreciationAccount', 'depreciationExpenseAccount', 'transactions.journalEntry'])] + $this->formData());
    }

    public function lifecycle(LifecycleAssetRequest $request, Asset $asset): RedirectResponse
    {
        $this->assets->lifecycle($asset, $request->validated(), (int) $request->user()->id);

        return back()->with('status', 'Asset lifecycle transaction posted.');
    }

    private function formData(): array
    {
        return ['accounts' => Account::query()->where('is_postable', true)->orderBy('code')->get(['id', 'code', 'name']), 'vendors' => Vendor::query()->orderBy('name')->get(['id', 'code', 'name']), 'items' => Item::query()->orderBy('name')->get(['id', 'code', 'name']), 'departments' => Department::query()->orderBy('name')->get(['id', 'name'])];
    }
}
