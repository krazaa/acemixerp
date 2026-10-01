<?php

declare(strict_types=1);

namespace App\Http\Requests\Journals;

use App\Models\JournalEntry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', JournalEntry::class);
    }

    public function rules(): array
    {
        return [
            'entry_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:64'],
            'description' => ['required', 'string', 'max:500'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:500'],
            'lines.*.cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'lines.*.department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'lines.*.customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'lines.*.vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'lines.*.employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $lines = collect($this->input('lines', []));
            $debit = $lines->sum(fn ($l) => (float) ($l['debit'] ?? 0));
            $credit = $lines->sum(fn ($l) => (float) ($l['credit'] ?? 0));

            if (abs($debit - $credit) > 0.00005) {
                $v->errors()->add(
                    'lines',
                    sprintf('Journal is unbalanced: debit %.4f ≠ credit %.4f.', $debit, $credit)
                );
            }

            foreach ($lines as $i => $line) {
                $d = (float) ($line['debit'] ?? 0);
                $c = (float) ($line['credit'] ?? 0);
                if ($d > 0 && $c > 0) {
                    $v->errors()->add("lines.{$i}.debit", 'A line cannot have both debit and credit.');
                }
                if ($d == 0 && $c == 0) {
                    $v->errors()->add("lines.{$i}.debit", 'A line must have a debit or credit amount.');
                }
            }
        });
    }
}
