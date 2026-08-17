@props([
    'type' => 'link', // 'link' or 'button'
    'action' => 'view', // 'view', 'edit', 'delete', 'download', 'reset', 'blacklist'
    'title' => '',
    'icon' => '',
    'href' => '#',
    'onclick' => '',
])

@php
    $class = 'action-btn';
    $defaultIcon = 'ti ti-eye';
    
    if ($action === 'edit') {
        $class .= ' action-edit';
        $defaultIcon = 'ti ti-pencil';
    } elseif ($action === 'delete') {
        $class .= ' action-delete';
        $defaultIcon = 'ti ti-trash';
    } elseif ($action === 'download') {
        $defaultIcon = 'ti ti-download';
    } elseif ($action === 'reset') {
        $defaultIcon = 'ti ti-key';
    } elseif ($action === 'blacklist') {
        $class .= ' action-delete';
        $defaultIcon = 'ti ti-ban';
    } elseif ($action === 'reset-face' || $action === 'reset_face') {
        $class .= ' action-reset-face';
        $defaultIcon = 'ti ti-scan';
    }
    
    $displayIcon = $icon ?: $defaultIcon;
@endphp

@if($type === 'button')
    <button type="button" class="{{ $class }}" title="{{ $title }}" @if($onclick) onclick="{{ $onclick }}" @endif {{ $attributes }}>
        <i class="{{ $displayIcon }}"></i>
    </button>
@else
    <a href="{{ $href }}" class="{{ $class }}" title="{{ $title }}" {{ $attributes }}>
        <i class="{{ $displayIcon }}"></i>
    </a>
@endif
