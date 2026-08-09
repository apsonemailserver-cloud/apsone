@props([
    'searchId' => 'typeToSearchInput',
    'searchPlaceholder' => 'Cari...',
    'onkeyup' => '',
    'searchFormAction' => '',
    'searchValue' => '',
])

<div class="dt-toolbar d-flex flex-wrap align-items-center justify-content-between gap-3 my-3">
    @if($searchFormAction)
        <form action="{{ $searchFormAction }}" method="GET" class="dt-search m-0 flex-grow-1" style="max-width: 320px;">
            @foreach(request()->except(['search', 'page']) as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endforeach
            <i class="ti ti-search search-icon"></i>
            <input type="text" name="search" class="form-control" placeholder="{{ $searchPlaceholder }}" value="{{ $searchValue ?: request('search') }}">
        </form>
    @else
        <div class="dt-search flex-grow-1" style="max-width: 320px;">
            <i class="ti ti-search search-icon"></i>
            <input type="text" id="{{ $searchId }}" class="form-control" placeholder="{{ $searchPlaceholder }}" @if($onkeyup) onkeyup="{{ $onkeyup }}" @endif>
        </div>
    @endif

    @if(isset($actions))
        <div class="dt-actions d-flex gap-2 align-items-center">
            {{ $actions }}
        </div>
    @endif
</div>
