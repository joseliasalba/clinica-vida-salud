@props(['column', 'heading' => false])
@aware(['columns' => []])
@php
    if (! array_key_exists($column, $columns)) {
        throw new InvalidArgumentException('La celda debe usar una columna declarada en responsive-table.');
    }
@endphp
@if($heading)
<th scope="row" role="rowheader" data-column="{{ $column }}" {{ $attributes->class(['responsive-table-cell']) }}>
    <span class="table-cell-label" aria-hidden="true">{{ $columns[$column] }}</span>
    <div class="table-cell-value">{{ $slot }}</div>
</th>
@else
<td role="cell" data-column="{{ $column }}" {{ $attributes->class(['responsive-table-cell']) }}>
    <span class="table-cell-label" aria-hidden="true">{{ $columns[$column] }}</span>
    <div class="table-cell-value">{{ $slot }}</div>
</td>
@endif
