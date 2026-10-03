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
