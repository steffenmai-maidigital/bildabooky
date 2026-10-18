<select name="{{ $name }}" id="{{ $id }}" class="subject-symbol" aria-label="{{ __('Symbol') }}">
    @foreach(\App\Models\Subject::SYMBOLS as $sym)
        <option value="{{ $sym }}" @selected($value === $sym)>{{ $sym }}</option>
    @endforeach
    @unless(in_array($value, \App\Models\Subject::SYMBOLS, true))<option value="{{ $value }}" selected>{{ $value }}</option>@endunless
</select>
