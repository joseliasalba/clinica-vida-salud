@props(['columns', 'label'])
<div {{ $attributes->class(['responsive-table-region']) }}>
    <table class="responsive-table" role="table" aria-label="{{ $label }}">
        <thead role="rowgroup"><tr role="row">
            @foreach($columns as $key => $heading)
                <th scope="col" role="columnheader" data-column="{{ $key }}">{{ $heading }}</th>
            @endforeach
        </tr></thead>
        <tbody role="rowgroup">{{ $slot }}</tbody>
    </table>
</div>
