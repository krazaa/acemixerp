<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Requests\GoodsReceipts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Procurement\Models\GoodsReceipt;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', GoodsReceipt::class);
    }

    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'received_date' => ['required', 'date'],
            'supplier_delivery_note' => ['nullable', 'string', 'max:64'],
            'carrier' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'integer', 'exists:purchase_order_lines,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->whereNull('deleted_at')],
            'lines.*.origin_id' => ['nullable', 'integer', Rule::exists('origins', 'id')->whereNull('deleted_at')],
            'lines.*.received_quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.accepted_quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.batch_number' => ['nullable', 'string', 'max:128'],
            'lines.*.expiry_date' => ['nullable', 'date'],
            'lines.*.manufacturing_date' => ['nullable', 'date', 'before_or_equal:received_date'],
            'lines.*.rejection_reason' => ['nullable', 'string', 'max:500'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($v): void
    {
        $v->after(function ($v) {
            foreach ($this->input('lines', []) as $i => $line) {
                $received = (float) ($line['received_quantity'] ?? 0);
                $accepted = (float) ($line['accepted_quantity'] ?? 0);
                $rejected = (float) ($line['rejected_quantity'] ?? 0);

                if (abs(($accepted + $rejected) - $received) > 0.00005) {
                    $v->errors()->add(
                        "lines.{$i}.accepted_quantity",
                        'Accepted + Rejected must equal Received quantity.',
                    );
                }

                if ($accepted > 0 && blank($line['batch_number'] ?? null)) {
                    $v->errors()->add("lines.{$i}.batch_number", 'A batch number is required for accepted quantity.');
                }
            }
        });
    }
}
