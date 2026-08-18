@props([
    'searchId' => 'typeToSearchInput',
    'searchPlaceholder' => 'Cari...',
    'onkeyup' => '',
    'searchFormAction' => '',
    'searchValue' => '',
])

<div class="dt-toolbar d-flex flex-wrap align-items-center justify-content-between gap-3 my-3">
    @if($searchFormAction)
        <form action="{{ $searchFormAction }}" method="GET" class="dt-search m-0 flex-grow-1">
            @foreach(request()->except(['search', 'page']) as $key => $val)
                @if(is_array($val))
                    @foreach($val as $item)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endif
            @endforeach
            <i class="ti ti-search search-icon"></i>
            <input type="text" name="search" class="form-control" placeholder="{{ $searchPlaceholder }}" value="{{ $searchValue ?: request('search') }}" autocomplete="off">
        </form>
    @else
        <div class="dt-search flex-grow-1">
            <i class="ti ti-search search-icon"></i>
            <input type="text" id="{{ $searchId }}" class="form-control" placeholder="{{ $searchPlaceholder }}" @if($onkeyup) onkeyup="{{ $onkeyup }}" @endif autocomplete="off">
        </div>
    @endif

    @if(isset($actions))
        <div class="dt-actions d-flex gap-2 align-items-center flex-wrap">
            {{ $actions }}
        </div>
    @endif
</div>
