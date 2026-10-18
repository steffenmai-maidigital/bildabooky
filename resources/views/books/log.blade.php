@extends('layouts.app')
@section('title', __('Änderungsprotokoll'))
@section('content')
    <p><a href="{{ route('home') }}">← {{ __('Bibliothek') }}</a></p>
    <h1>🕘 {{ __('Änderungsprotokoll') }}</h1>
    <div class="panel">
        <b>{{ $book->title }}</b>
        <div class="hint">
            @if($book->isClassBook()) 👥 {{ __('Klassenbuch') }}
            @elseif($book->isTemplate()) 📋 {{ __('Vorlage') }}
            @elseif($book->isVersion()) 📋 {{ __('Version der Vorlage „:titel“', ['titel' => $book->template?->title]) }} · {{ $book->owner->name }}
            @else {{ $book->owner->name }} @endif
            @if($book->schoolClass) · {{ __('Klasse :name', ['name' => $book->schoolClass->name]) }} @endif
        </div>
        <div class="actions" style="margin-top:.6rem">
            <a class="btn small blue" href="{{ route('books.read', $book) }}">📖 {{ __('Lesen') }}</a>
            @if($book->editableBy(auth()->user()))<a class="btn small primary" href="{{ route('books.edit', $book) }}">✏️ {{ __('Bearbeiten') }}</a>@endif
        </div>
        <p class="hint">{{ __('Nur Lehrkräfte und Admins sehen dieses Protokoll. Änderungen derselben Person an derselben Seite innerhalb von 10 Minuten sind zu einem Eintrag zusammengefasst.') }}</p>
    </div>

    <div class="panel">
        <div class="table-scroll">
        <table class="list log">
            <thead><tr><th>{{ __('Wann') }}</th><th>{{ __('Wer') }}</th><th>{{ __('Seite') }}</th><th>{{ __('Was') }}</th></tr></thead>
            <tbody>
            @forelse($entries as $e)
                <tr>
                    <td class="hint" style="white-space:nowrap">
                        {{ $e->updated_at->format('d.m.Y H:i') }}
                        @if($e->updated_at->diffInMinutes($e->created_at) >= 1)<br><small>{{ __('seit :zeit', ['zeit' => $e->created_at->format('H:i')]) }}</small>@endif
                    </td>
                    <td>{{ $e->user?->name ?? __('gelöschter Benutzer') }}
                        @if($e->user)<span class="badge {{ $e->user->role }}">{{ $e->user->roleLabel() }}</span>@endif</td>
                    <td>{{ $e->page ?? '—' }}</td>
                    <td>
                        {{ $e->description() }}
                        @foreach(($e->details['texts'] ?? []) as $t)
                            <div class="log-text">„{{ $t }}“</div>
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">{{ __('Noch keine Einträge.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        {{ $entries->links('partials.pagination') }}
    </div>
@endsection
