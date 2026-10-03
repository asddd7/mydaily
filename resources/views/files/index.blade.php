@extends('layouts.app')

@section('title', 'File Manager - MyDaily')

@section('content')
<section class="card">
    <h1>File Manager</h1>
    <p>Ukuran maksimum 50 MB. Format yang didukung mencakup gambar, video, dokumen, arsip, dan teks.</p>
    <form method="post" action="{{ route('files.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" required>
        <button type="submit">Upload file</button>
    </form>
</section>

<section class="card">
    <h2>File tersimpan</h2>
    <div class="file-grid">
        @forelse ($files as $file)
            <article class="file-card">
                <p><strong>{{ $file->file_original }}</strong></p>
                <p>{{ number_format($file->file_size / 1024, 2) }} KB · {{ $file->file_type }}</p>
                <a href="{{ route('files.download', $file->id) }}">Download</a>
                <form method="post" action="{{ route('files.update', $file->id) }}">
                    @csrf
                    @method('put')
                    <label>Nama file <input name="file_original" value="{{ $file->file_original }}" maxlength="255" required></label>
                    <button type="submit">Ubah nama</button>
                </form>
                <form method="post" action="{{ route('files.destroy', $file->id) }}" onsubmit="return confirm('Hapus file ini?')">
                    @csrf
                    @method('delete')
                    <button type="submit">Hapus</button>
                </form>
            </article>
        @empty
            <p>Belum ada file.</p>
        @endforelse
    </div>
</section>
@endsection
