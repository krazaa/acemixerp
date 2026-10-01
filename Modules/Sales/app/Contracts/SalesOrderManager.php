<?php

namespace Modules\Sales\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Sales\Data\SalesOrderData;
use Modules\Sales\Exceptions\SalesOrderException;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SalesOrder;

interface SalesOrderManager
{
    /**
     * Paginated list with filters: search, status, customer_id, from, to.
     */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /**
     * Create a new Sales Order in draft status.
     *
     * @throws SalesOrderException
     */
    public function create(SalesOrderData $data, int $userId): SalesOrder;

    /**
     * Convert an accepted quotation into a Sales Order.
     * Marks the quotation as converted and links both directions.
     *
     * @throws SalesOrderException
     */
    public function createFromQuotation(Quotation $quotation, int $userId): SalesOrder;

    /**
     * Update a draft or rejected order. Only editable statuses accept changes.
     *
     * @throws SalesOrderException
     */
    public function update(SalesOrder $order, SalesOrderData $data, int $userId): SalesOrder;

    /**
     * Submit for approval. Runs the credit check; if credit is exceeded,
     * transitions to `on_hold` instead of `submitted` and returns normally.
     *
     * @throws SalesOrderException
     */
    public function submit(SalesOrder $order, int $userId): SalesOrder;

    /**
     * Approve a submitted order.
     *
     * @throws SalesOrderException when the submitter is the approver
     */
    public function approve(SalesOrder $order, int $userId): SalesOrder;

    /**
     * Reject a submitted order with a reason.
     *
     * @throws SalesOrderException
     */
    public function reject(SalesOrder $order, int $userId, string $reason): SalesOrder;

    /**
     * Release an order from credit hold, moving it back to submitted
     * so it can be approved.
     *
     * @throws SalesOrderException
     */
    public function releaseHold(SalesOrder $order, int $userId, string $reason): SalesOrder;

    /**
     * Confirm an approved order. The order becomes immutable from this point.
     * Deliveries will be created against a confirmed order (Phase 5B).
     *
     * @throws SalesOrderException
     */
    public function confirm(SalesOrder $order, int $userId): SalesOrder;

    /**
     * Cancel an order before it is confirmed.
     *
     * @throws SalesOrderException
     */
    public function cancel(SalesOrder $order, int $userId, ?string $reason = null): SalesOrder;

    /**
     * Close a delivered or partially-delivered order. Terminal state.
     *
     * @throws SalesOrderException
     */
    public function close(SalesOrder $order, int $userId): SalesOrder;

    /**
     * Soft-delete a draft order.
     *
     * @throws SalesOrderException
     */
    public function delete(SalesOrder $order): void;
}
