@extends('layouts.app')

@section('title', 'Profil - MyDaily')

@section('content')
<section class="card">
    <h1>Profil Saya</h1>
    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('put')
        <label>Username <input name="username" maxlength="50" value="{{ $user->username }}" required></label>
        <label>Email <input type="email" name="email" maxlength="100" value="{{ $user->email }}" required></label>
        @foreach (['instagram', 'facebook', 'twitter', 'tiktok', 'linkedin'] as $network)
            <label>{{ ucfirst($network) }} URL
                <input type="url" name="{{ $network }}" value="{{ $user->$network ?? '' }}">
            </label>
        @endforeach
        <label><input type="checkbox" name="sosmed_public" value="1" @checked($user->sosmed_public)> Tampilkan sosial media ke publik</label>
        <button type="submit">Simpan profil</button>
    </form>
    @if ($user->sosmed_public)
        <h2>Sosial media publik</h2>
        @foreach (['instagram', 'facebook', 'twitter', 'tiktok', 'linkedin'] as $network)
            @if ($user->$network)
                <p><a href="{{ $user->$network }}" target="_blank" rel="noopener noreferrer">{{ ucfirst($network) }}</a></p>
            @endif
        @endforeach
    @endif
</section>
@endsection
