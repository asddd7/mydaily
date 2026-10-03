@extends('layouts.app')

@section('title', 'Catatan - MyDaily')

@section('content')
<section class="card">
    <h1>Catatan</h1>
    <form method="post" action="{{ route('notes.store') }}" enctype="multipart/form-data">
        @csrf
        <label>Judul <input name="title" maxlength="255" required></label>
        <label>Isi <textarea name="content" rows="8" required></textarea></label>
        <label>Gambar <input type="file" name="image" accept="image/*"></label>
        <button type="submit">Simpan catatan</button>
    </form>
</section>

@forelse ($notes as $note)
    <section class="card">
        <h2><a href="{{ route('notes.show', $note->id) }}">{{ $note->title }}</a></h2>
        <small>{{ $note->created_at }}</small>
        @if ($note->image)
            <p><img src="{{ route('notes.image', $note->id) }}" alt="" style="max-width:200px"></p>
        @endif
        <p>{{ \Illuminate\Support\Str::limit($note->content, 300) }}</p>
        <a href="{{ route('notes.show', $note->id) }}">Buka / edit</a>
        <form method="post" action="{{ route('notes.destroy', $note->id) }}" onsubmit="return confirm('Hapus catatan ini?')">
            @csrf
            @method('delete')
            <button type="submit">Hapus</button>
        </form>
    </section>
@empty
    <section class="card"><p>Belum ada catatan.</p></section>
@endforelse
@endsection
