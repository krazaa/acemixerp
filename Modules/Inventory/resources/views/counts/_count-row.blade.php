@php($i = $index)
@php($l = is_array($line) ? $line : [])
@php($systemQty   = (string) ($l['system_quantity'] ?? '0'))
@php($countedQty  = $l['counted_quantity'] ?? null)
@php($variance    = (string) ($l['variance'] ?? '0'))
@php($item        = $l['item'] ?? null)

<tr class="count-row">
    <td class="text-muted">{{ $i + 1 }}</td>

    <td>
        @if($item)
            <code>{{ $item->code }}</code> {{ $item->name }}
        @elseif(! empty($l['item_id']))
            <code>#{{ $l['item_id'] }}</code>
        @else
            <span class="text-muted">—</span>
        @endif
        <input type="hidden" name="lines[{{ $i }}][item_id]" value="{{ $l['item_id'] ?? '' }}">
        <input type="hidden" name="lines[{{ $i }}][stock_count_line_id]" value="{{ $l['id'] ?? '' }}">
    </td>

    <td class="text-end system-qty">{{ number_format((float) $systemQty, 4) }}</td>

    <td class="text-end" style="width: 160px;">
        <input name="counted[{{ $l['id'] ?? '__INDEX__' }}]"
               type="number"
               step="0.0001"
               min="0"
               class="form-control form-control-sm text-end counted-input"
               data-line-id="{{ $l['id'] ?? '' }}"
               value="{{ $countedQty !== null ? $countedQty : '' }}">
    </td>

    <td class="text-end variance-cell
        {{ bccomp($variance, '0', 0) > 0 ? 'text-success fw-semibold' : '' }}
        {{ bccomp($variance, '0', 0) < 0 ? 'text-danger fw-semibold' : '' }}
        {{ bccomp($variance, '0', 0) === 0 ? 'text-muted' : '' }}">
        {{ number_format((float) $variance, 0) }}
    </td>

    <td class="text-end">{{ number_format((float) ($l['unit_cost'] ?? 0), 0) }}</td>

    <td class="text-muted small">{{ $l['notes'] ?? '' }}</td>
</tr>
