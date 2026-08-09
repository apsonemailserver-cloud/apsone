@props([
    'submitText' => 'Simpan Data',
    'cancelHref' => '#',
    'cancelText' => 'Batal',
    'submitIcon' => '',
])

<div class="d-flex gap-2 mt-4 pt-2">
    <button type="submit" {{ $attributes->merge(['class' => 'btn btn-primary']) }}>
        @if($submitIcon)<i class="{{ $submitIcon }} me-1"></i>@endif{{ $submitText }}
    </button>
    <a href="{{ $cancelHref }}" class="btn btn-secondary">
        {{ $cancelText }}
    </a>
</div>
