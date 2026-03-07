@if (($canEdit ?? false) || ($canDelete ?? false))
    <div class="btn-group">
        <button type="button" class="btn btn-light dropdown-toggle p-3" data-toggle="dropdown">Aksi</button>
        <ul class="dropdown-menu">
            @if ($canEdit ?? false)
                <li><a class="dropdown-item" href="{{ $editUrl }}"><i
                            class="ti-pencil btn-icon-prepend mr-2"></i>Ubah</a></li>
            @endif
            @if ($canDelete ?? false)
                <li>
                    <button class="dropdown-item danger" data-toggle="modal" data-target="#deleteConfirmationModal"
                        onclick="window.confirmDelete('{{ $deleteUrl }}', '{{ $deleteName }}')">
                        <i class="ti-trash btn-icon-prepend mr-2"></i>Hapus
                    </button>
                </li>
            @endif

        </ul>
    </div>
@endif
