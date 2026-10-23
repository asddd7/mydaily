@extends('layouts.app')

@section('title', 'Struktur Database - MyDaily')

@section('content')
<section class="card">
    <h1>Struktur Database</h1>
    <label>Cari tabel <input id="tableSearch" type="search" autocomplete="off"></label>
</section>

@foreach ($tables as $table => $structure)
    <section class="card database-table" data-table="{{ strtolower($table) }}">
        <h2>{{ $table }}</h2>
        <details class="database-relations">
            <summary>Relasi tabel</summary>
            @if ($relations[$table]['outgoing'] || $relations[$table]['incoming'])
                @if ($relations[$table]['outgoing'])
                    <h3>Merujuk ke</h3>
                    <ul>
                        @foreach ($relations[$table]['outgoing'] as $relation)
                            <li>
                                <code>{{ $table }}.{{ $relation['column'] }}</code>
                                &rarr;
                                <code>{{ $relation['referenced_table'] }}.{{ $relation['referenced_column'] }}</code>
                                <small>({{ $relation['constraint'] }})</small>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($relations[$table]['incoming'])
                    <h3>Dirujuk oleh</h3>
                    <ul>
                        @foreach ($relations[$table]['incoming'] as $relation)
                            <li>
                                <code>{{ $relation['source_table'] }}.{{ $relation['column'] }}</code>
                                &rarr;
                                <code>{{ $table }}.{{ $relation['referenced_column'] }}</code>
                                <small>({{ $relation['constraint'] }})</small>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @else
                <p>Tidak ada foreign key yang terdaftar untuk tabel ini.</p>
            @endif
        </details>
        <pre>{{ $structure['sql'] }}</pre>
        <button type="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.textContent)">Salin struktur</button>
    </section>
@endforeach
@endsection

@push('scripts')
<script>
document.getElementById('tableSearch').addEventListener('input', function () {
    const value = this.value.trim().toLowerCase();
    document.querySelectorAll('.database-table').forEach(table => {
        table.hidden = value !== '' && !table.dataset.table.includes(value);
    });
});
</script>
@endpush
