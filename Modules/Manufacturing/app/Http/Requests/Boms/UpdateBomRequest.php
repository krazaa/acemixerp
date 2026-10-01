<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Http\Requests\Boms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Inventory\Models\StockBalance;
use Modules\Manufacturing\Enums\BomStatus;

class UpdateBomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('bom')) ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('bom')->id;

        return [
            'code' => ['sometimes', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('bill_of_materials', 'code')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:128'],
            'revision' => ['sometimes', 'integer', 'min:1'],
            'product_id' => ['sometimes', 'integer', 'exists:items,id'],
            'output_quantity' => ['sometimes', 'numeric', 'gt:0'],
            'output_unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'labour_cost' => ['nullable', 'numeric', 'min:0'],
            'overhead_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::enum(BomStatus::class)],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.component_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.batch_id' => ['nullable', 'integer', 'exists:stock_batches,id'],
            'lines.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.scrap_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->input('lines', []) as $index => $line) {
                if (! is_array($line) || empty($line['component_id']) || empty($line['batch_id'])) {
                    continue;
                }

                if (! StockBalance::query()
                    ->where('item_id', $line['component_id'])
                    ->where('batch_id', $line['batch_id'])
                    ->exists()) {
                    $validator->errors()->add("lines.{$index}.batch_id", 'The selected batch does not belong to the selected ingredient.');
                }
            }
        }];
    }
}
