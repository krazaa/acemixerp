<?php

namespace App\Http\Controllers;

use App\Contracts\CategoryManager;
use App\Contracts\ItemManager;
use App\Contracts\UnitManager;
use App\Enums\ItemType;
use App\Enums\RecordStatus;
use App\Http\Requests\Items\StoreItemRequest;
use App\Http\Requests\Items\UpdateItemRequest;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function __construct(
        private readonly ItemManager $items,
        private readonly CategoryManager $categories,
        private readonly UnitManager $units,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Item::class);

        return view('items.index', [
            'items' => $this->items->paginate(
                $request->only(['search', 'status', 'item_type', 'category_id'])
            ),
            'categories' => $this->categories->allActive(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Item::class);

        return view('items.create', [
            'item' => new Item(['status' => RecordStatus::Active, 'item_type' => ItemType::Stock]),
            'categories' => $this->categories->allActive(),
            'units' => $this->units->allActive(),
        ]);
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        $item = $this->items->create(
            $request->validated(),
            $request->file('image'),
        );

        return redirect()->route('items.show', $item)
            ->with('status', "Item {$item->name} created.");
    }

    public function show(Item $item): View
    {
        $this->authorize('view', $item);

        return view('items.show', [
            'item' => $item->load(['category', 'unit', 'creator', 'updater']),
        ]);
    }

    public function edit(Item $item): View
    {
        $this->authorize('update', $item);

        return view('items.edit', [
            'item' => $item,
            'categories' => $this->categories->allActive(),
            'units' => $this->units->allActive(),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $this->items->update(
            $item,
            $request->validated(),
            $request->file('image'),
        );

        return redirect()->route('items.show', $item)->with('status', 'Item updated.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        $this->authorize('delete', $item);

        $this->items->delete($item);

        return redirect()->route('items.index')->with('status', 'Item deleted.');
    }

    public function changeStatus(Request $request, Item $item): RedirectResponse
    {
        $this->authorize('changeStatus', $item);
        $request->validate(['status' => ['required', 'string']]);

        $this->items->changeStatus($item, RecordStatus::from((string) $request->input('status')));

        return back()->with('status', 'Item status updated.');
    }
}
