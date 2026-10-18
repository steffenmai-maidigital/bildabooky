@if($paginator->hasPages())
    <div class="actions">
        @if(! $paginator->onFirstPage())<a class="btn small" href="{{ $paginator->previousPageUrl() }}">← {{ __('Neuere') }}</a>@endif
        <span class="hint" style="align-self:center">{{ __('Seite :n von :m', ['n' => $paginator->currentPage(), 'm' => $paginator->lastPage()]) }}</span>
        @if($paginator->hasMorePages())<a class="btn small" href="{{ $paginator->nextPageUrl() }}">{{ __('Ältere') }} →</a>@endif
    </div>
@endif
