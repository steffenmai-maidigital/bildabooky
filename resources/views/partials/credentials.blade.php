{{-- Anmeldekarten: $credentials = [['name','username','password','class','role'], …]

     Login cards: $credentials = [['name','username','password','class','role'], …] --}}
<div class="cards-area">
    <div class="cards">
        @foreach($credentials as $z)
            <div class="card">
                <div class="card-school"><img src="{{ \App\Support\Branding::logoUrl() }}" alt=""> {{ \App\Support\Branding::school() }}</div>
                <b>{{ $z['name'] }}</b>
                @if($z['class'])<span class="hint"> · {{ __('Klasse') }} {{ $z['class'] }}</span>@endif
                <dl>
                    <dt>{{ __('Name') }}</dt><dd>{{ $z['username'] }}</dd>
                    <dt>{{ __('Passwort') }}</dt><dd>{{ $z['password'] }}</dd>
                </dl>
                <div class="address">📚 {{ url('/') }}</div>
            </div>
        @endforeach
    </div>
</div>
