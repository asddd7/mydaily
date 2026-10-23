@extends('layouts.app')

@section('title', 'Struktur Aplikasi & Database - MyDaily')

@push('styles')
    @vite('resources/css/database-structure.css')
@endpush

@section('content')
<div class="database-structure-page">
    <section class="card database-structure-heading">
        <div>
            <p class="database-structure-eyebrow">DOKUMENTASI APLIKASI</p>
            <h1>Struktur Aplikasi &amp; Database</h1>
            <p>Buka modul untuk melihat tabel database, relasi, route, controller, view, dan file terkait.</p>
        </div>
        <label class="database-module-search" for="tableSearch">
            <span>Cari modul, tabel, atau file</span>
            <input id="tableSearch" type="search" autocomplete="off" placeholder="Contoh: tasks atau TaskController">
        </label>
    </section>

    <div class="database-modules" id="databaseModules">
        @foreach ($modules as $module)
            <details class="database-module" data-search="{{ $module['search'] ?? strtolower($module['title'].' '.implode(' ', $module['tables'])) }}">
                <summary class="database-module-summary">
                    <span class="database-module-title">
                        <strong>{{ $module['title'] }}</strong>
                        <small>{{ $module['description'] }}</small>
                    </span>
                    <span class="database-module-counts">
                        <span>{{ count($module['tables']) }} tabel</span>
                        <span>{{ collect($module['files'])->flatten()->count() }} file</span>
                    </span>
                </summary>

                <div class="database-module-content">
                    @if ($module['tables'])
                        <section class="database-module-section">
                            <h2>Tabel database</h2>
                            <div class="database-module-tables">
                                @foreach ($module['tables'] as $table)
                                    <details class="database-table">
                                        <summary>{{ $table }}</summary>
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
                                        <pre>{{ $tables[$table]['sql'] }}</pre>
                                        <button type="button" class="database-copy-button" onclick="navigator.clipboard.writeText(this.previousElementSibling.textContent)">Salin struktur</button>
                                    </details>
                                @endforeach
                            </div>
                        </section>
                    @else
                        <p class="database-no-tables">Modul ini tidak memakai tabel database secara langsung.</p>
                    @endif

                    @if ($module['routes'])
                        <section class="database-module-section">
                            <h2>Route</h2>
                            <ul class="database-route-list">
                                @foreach ($module['routes'] as $route)
                                    <li><code>{{ $route }}</code></li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($module['files'])
                        <section class="database-module-section">
                            <h2>File terkait</h2>
                            <div class="database-file-groups">
                                @foreach ($module['files'] as $category => $paths)
                                    <div class="database-file-group">
                                        <h3>{{ $category }}</h3>
                                        <ul>
                                            @foreach ($paths as $path)
                                                <li><code>{{ $path }}</code></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </details>
        @endforeach
    </div>

    <p class="database-no-results" id="databaseNoResults" hidden>Tidak ada modul, tabel, atau file yang cocok.</p>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const input = document.getElementById('tableSearch');
    const modules = [...document.querySelectorAll('.database-module')];
    const noResults = document.getElementById('databaseNoResults');
    if (!input || !noResults) return;

    input.addEventListener('input', () => {
        const query = input.value.trim().toLowerCase();
        let visibleCount = 0;

        modules.forEach(module => {
            const visible = !query || module.dataset.search.includes(query);
            module.hidden = !visible;
            if (visible) visibleCount++;
        });

        noResults.hidden = visibleCount > 0;
    });
})();
</script>
@endpush
