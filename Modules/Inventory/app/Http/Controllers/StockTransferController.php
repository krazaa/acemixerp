<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Contracts\ItemManager;
use App\Contracts\UnitManager;
use App\Contracts\WarehouseManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Contracts\StockTransferManager;
use Modules\Inventory\Data\StockTransferData;
use Modules\Inventory\Enums\TransferStatus;
use Modules\Inventory\Http\Requests\Transfers\ReceiveStockTransferRequest;
use Modules\Inventory\Http\Requests\Transfers\StoreStockTransferRequest;
use Modules\Inventory\Http\Requests\Transfers\UpdateStockTransferRequest;
use Modules\Inventory\Models\StockTransfer;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferManager $transfers,
        private readonly WarehouseManager $warehouses,
        private readonly ItemManager $items,
        private readonly UnitManager $units,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockTransfer::class);

        return view('inventory::transfers.index', [
            'transfers' => $this->transfers->paginate(
                $request->only(['search', 'status', 'from_warehouse_id', 'to_warehouse_id'])
            ),
            'warehouses' => $this->warehouses->allActive(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', StockTransfer::class);

        return view('inventory::transfers.create', $this->formDependencies() + [
            'transfer' => new StockTransfer([
                'transfer_date' => now()->toDateString(),
                'status' => TransferStatus::Draft,
            ]),
        ]);
    }

    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        $transfer = $this->transfers->create(
            StockTransferData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('inventory.transfers.show', $transfer)
            ->with('status', "Transfer {$transfer->number} created as draft.");
    }

    public function show(StockTransfer $transfer): View
    {
        $this->authorize('view', $transfer);

        return view('inventory::transfers.show', [
            'transfer' => $transfer->load([
                'lines.item', 'lines.unit', 'lines.batch',
                'fromWarehouse', 'toWarehouse',
                'submitter', 'approver', 'receiver', 'creator', 'updater',
            ]),
        ]);
    }

    public function edit(StockTransfer $transfer): View
    {
        $this->authorize('update', $transfer);

        return view('inventory::transfers.edit', $this->formDependencies() + [
            'transfer' => $transfer->load('lines'),
        ]);
    }

    public function update(UpdateStockTransferRequest $request, StockTransfer $transfer): RedirectResponse
    {
        $this->transfers->update($transfer, StockTransferData::fromRequest($request), $request->user()->id);

        return redirect()
            ->route('inventory.transfers.show', $transfer)
            ->with('status', 'Transfer updated.');
    }

    public function destroy(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('delete', $transfer);

        if ($transfer->status->value !== 'draft') {
            return back()->withErrors(['error' => 'Only drafts can be deleted. Cancel instead.']);
        }

        $transfer->delete();

        return redirect()->route('inventory.transfers.index')
            ->with('status', 'Transfer deleted.');
    }

    public function submit(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('submit', $transfer);
        $this->transfers->submit($transfer, auth()->id());

        return back()->with('status', 'Transfer submitted for approval.');
    }

    public function approve(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('approve', $transfer);
        $this->transfers->approve($transfer, auth()->id());

        return back()->with('status', 'Transfer approved.');
    }

    public function dispatch(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('dispatch', $transfer);
        $this->transfers->dispatch($transfer, auth()->id());

        return back()->with('status', 'Transfer dispatched. Stock movements recorded.');
    }

    public function receive(ReceiveStockTransferRequest $request, StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('receive', $transfer);

        $quantities = collect($request->input('received', []))
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (string) $v])
            ->all();

        $this->transfers->receive($transfer, $quantities, auth()->id());

        return back()->with('status', 'Transfer received into destination warehouse.');
    }

    public function cancel(Request $request, StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('cancel', $transfer);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->transfers->cancel($transfer, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Transfer cancelled.');
    }

    /** @return array<string, mixed> */
    private function formDependencies(): array
    {
        return [
            'warehouses' => $this->warehouses->allActive(),
            'items' => $this->items->allPurchasable(),
            'units' => $this->units->allActive(),
        ];
    }
}
