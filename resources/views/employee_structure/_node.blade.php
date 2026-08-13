{{--
    Holi Organigramme Node Card
    Matches staff data photo profile logic: asset('storage/photo/'.$node->profile_picture) with asset('storage/photo/user.jpg') default fallback.
--}}
@php
    $hasChildren = !empty($node->children_tree) && count($node->children_tree) > 0;
    // Default: collapse level 2 and deeper so only top-level node(s) show initially
    $isCollapsed = ($level >= 2) && $hasChildren;
@endphp

<div class="org-node-wrapper" id="node-wrapper-{{ $node->id }}">
    {{-- CARD ITEM --}}
    <div class="org-card-item {{ $hasChildren ? 'has-children' : '' }}" id="card-{{ $node->id }}">
        <div class="org-card-content">
            {{-- Avatar photo profile matching staff data --}}
            <div class="org-avatar-box">
                @if($node->profile_picture)
                    <img src="{{ asset('storage/photo/'.$node->profile_picture) }}" alt="Avatar" class="org-avatar-img">
                @else
                    <img src="{{ asset('storage/photo/user.jpg') }}" alt="Avatar" class="org-avatar-img">
                @endif
            </div>

            {{-- Info block --}}
            <div class="org-info-box">
                <span class="org-name-text" title="{{ $node->fullname }}">{{ $node->fullname }}</span>
                <span class="org-role-text" title="{{ $node->jobTitle->name ?? $node->getRoleName() ?? 'Staff Lapangan' }}">
                    {{ $node->jobTitle->name ?? $node->getRoleName() ?? 'Staff Lapangan' }}
                </span>
                <span class="org-nip-text">NIP: {{ $node->id }} &bull; {{ $node->station ?? 'HO' }}</span>
            </div>
        </div>

        {{-- Details link --}}
        <div class="org-card-footer">
            <a href="javascript:void(0)" class="org-details-btn"
               onclick="openSetSuperiorModal('{{ $node->id }}', '{{ addslashes($node->fullname) }}', '{{ $node->pic_id }}')">
                Detail / Ubah Atasan
            </a>
        </div>
    </div>

    {{-- CONNECTING LINE & CIRCULAR BADGE (IF HAS CHILDREN) --}}
    @if($hasChildren)
        <div class="org-stem-down">
            {{-- Interactive Circular Subordinate Badge --}}
            <div class="org-circle-badge {{ $isCollapsed ? 'collapsed' : '' }}"
                 id="badge-{{ $node->id }}"
                 onclick="toggleNodeBranch('{{ $node->id }}')"
                 title="Klik untuk {{ $isCollapsed ? 'membuka' : 'menutup' }} {{ count($node->children_tree) }} bawahan langsung">
                <span class="badge-count">{{ count($node->children_tree) }}</span>
                <span class="badge-sign">{{ $isCollapsed ? '+' : '-' }}</span>
            </div>
        </div>

        {{-- CHILDREN BRANCH CONTAINER --}}
        <div class="org-children-container {{ $isCollapsed ? 'org-hidden' : '' }}" id="children-{{ $node->id }}">
            @foreach($node->children_tree as $child)
                @include('employee_structure._node', ['node' => $child, 'level' => ($level ?? 1) + 1])
            @endforeach
        </div>
    @endif
</div>
