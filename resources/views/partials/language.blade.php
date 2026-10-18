<details class="language-choice">
    <summary title="{{ __('Sprache') }}">🌐 {{ strtoupper(app()->getLocale()) }}</summary>
    <div class="menu">
        @foreach(config('bildabooky.locales') as $code => $name)
            <a href="{{ route('locale.switch', $code) }}" lang="{{ $code }}" @if(app()->getLocale() === $code) aria-current="true" @endif>
                {{ $name }} @if(app()->getLocale() === $code) ✓ @endif
            </a>
        @endforeach
    </div>
</details>
