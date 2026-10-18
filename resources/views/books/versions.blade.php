@extends('layouts.app')
@section('title', __('Versionen der Kinder'))
@section('content')
    <p><a href="{{ route('home') }}">← {{ __('Bibliothek') }}</a></p>
    <h1>📋 {{ $book->title }}</h1>
    <div class="panel">
        <p style="margin-top:0">{{ __('Vorlage für die Klasse :name.', ['name' => $book->schoolClass?->name ?? '—']) }}
            {{ __(':a von :b Kindern haben angefangen, :c haben abgegeben.', ['a' => $started, 'b' => $rows->count(), 'c' => $submitted]) }}</p>
        <p class="hint">{{ __('Änderungen an der Vorlage gelten nur für Kinder, die noch nicht angefangen haben. Bereits angelegte Versionen bleiben, wie sie sind.') }}</p>
        <div class="actions">
            <a class="btn small primary" href="{{ route('books.edit', $book) }}">✏️ {{ __('Vorlage bearbeiten') }}</a>
            <a class="btn small" href="{{ route('books.log', $book) }}">🕘 {{ __('Änderungsprotokoll') }}</a>
        </div>
    </div>
    <div class="panel">
        <div class="table-scroll">
        <table class="list">
            <thead><tr><th>{{ __('Kind') }}</th><th>{{ __('Stand') }}</th><th>{{ __('Zuletzt geändert') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $z)
                @php($v = $z['version'])
                <tr>
                    <td><b>{{ $z['pupil']->last_name }}</b>{{ $z['pupil']->last_name ? ',' : '' }} {{ $z['pupil']->first_name }}</td>
                    <td>
                        @if(! $v)<span class="hint">{{ __('noch nicht begonnen') }}</span>
                        @elseif($v->submitted_at)<span class="badge ok">✓ {{ __('abgegeben') }} {{ $v->submitted_at->format('d.m. H:i') }}</span>
                        @elseif($v->isReturned())<span class="badge returned" title="{{ $v->return_note }}">↩️ {{ __('zurückgegeben') }} {{ $v->returned_at->format('d.m. H:i') }}</span>
                        @else<span class="badge teacher">{{ __('in Arbeit') }}</span>@endif
                    </td>
                    <td class="hint">{{ $v?->updated_at->format('d.m.Y H:i') ?? '—' }}</td>
                    <td style="white-space:nowrap">
                        @if($v)
                            <a class="btn small blue" href="{{ route('books.read', $v) }}">📖 {{ __('Lesen') }}</a>
                            <a class="btn small" href="{{ route('books.edit', $v) }}">✏️ {{ __('Bearbeiten') }}</a>
                            <a class="btn small" href="{{ route('books.log', $v) }}">🕘</a>
                            @if($v->returnableBy(auth()->user()))
                                <button type="button" class="btn small" data-return="{{ route('books.return', $v) }}" data-name="{{ $z['pupil']->first_name }}">↩️ {{ __('Zurückgeben') }}</button>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">{{ __('In dieser Klasse sind noch keine Kinder.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @include('partials.return-dialog')
@endsection
