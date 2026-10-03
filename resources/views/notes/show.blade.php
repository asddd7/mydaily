@extends('layouts.app')

@section('title', 'Edit Catatan - MyDaily')

@section('content')
<section class="card">
    <h1>{{ $note->title }}</h1>
    @if ($note->image)
        <p><img src="{{ route('notes.image', $note->id) }}" alt="" style="max-width:400px"></p>
    @endif
    <form method="post" action="{{ route('notes.update', $note->id) }}" enctype="multipart/form-data">
        @csrf
        @method('put')
        <label>Judul <input name="title" value="{{ $note->title }}" maxlength="255" required></label>
        <label>Isi <textarea name="content" rows="14" required>{{ $note->content }}</textarea></label>
        <label>Ganti gambar <input type="file" name="image" accept="image/*"></label>
        <button type="submit">Simpan perubahan</button>
    </form>
    <p><a href="{{ route('notes.index') }}">Kembali ke daftar catatan</a></p>
</section>
@endsection
