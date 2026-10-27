@php
    // raw format: column HTML separated by a line containing only ---
    $raw = (string) ($block['raw'] ?? '');
    $columns = preg_split('/^---\s*$/m', $raw) ?: [];
    $columns = array_map(fn ($c) => trim($c), $columns);
    $columns = array_values(array_filter($columns, fn ($c) => $c !== ''));
@endphp
@if(count($columns))
<section class="pb-block pb-block-columns">
    <div class="container">
        <div class="columns is-multiline">
            @foreach($columns as $column)
                <div class="column {{ count($columns) >= 3 ? 'is-4' : 'is-6' }}">
                    <div class="content">{!! sanitize_html($column) !!}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
