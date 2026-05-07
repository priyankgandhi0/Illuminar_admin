@extends('layouts.app')
@section('title', __('common.jornadas'))

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ __('common.jornadas') }}</h3></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('common.jornadas') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center w-100 gap-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <strong>{{ __('jornadas.jornada_list') }}</strong>
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
                            <select class="form-select form-select-sm" id="filterStatus" style="width:140px;">
                                <option value="">{{ __('jornadas.all_status') }}</option>
                                <option value="published">{{ __('common.published') }}</option>
                                <option value="draft">{{ __('common.draft') }}</option>
                            </select>
                            <select class="form-select form-select-sm" id="filterCategory" style="width:180px;">
                                <option value="">{{ __('jornadas.all_categories') }}</option>
                                @foreach($categoryNames as $catName)
                                    <option value="{{ $catName }}">{{ $catName }}</option>
                                @endforeach
                            </select>
                            <a href="{{ route('jornadas.create') }}" class="btn btn-primary btn-sm" id="btnCreateJornada"
                                data-has-categories="{{ $hasCategories ? '1' : '0' }}">
                                <i class="bi bi-plus-lg me-1"></i> {{ __('common.create_new') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="jn-table-wrap">
                        <table class="table table-bordered" id="jornadasTable">
                            <thead>
                                <tr>
                                    <th width="40" style="text-align:center; color:#aaa;"><i class="bi bi-grip-vertical"></i></th>
                                    <th>{{ __('common.title') }}</th>
                                    <th>{{ __('common.category') }}</th>
                                    <th>{{ __('common.languages') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                    <th width="110">{{ __('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="jornadasTbody">
                                @foreach($jornadas as $item)
                                <tr data-id="{{ $item['id'] }}"
                                    data-status="{{ $item['status'] }}"
                                    data-category="{{ $item['category'] }}"
                                    data-category-id="{{ $item['category_id'] }}">
                                    <td class="jn-drag-handle" style="cursor: grab; text-align: center; color: #aaa;">
                                        <i class="bi bi-grip-vertical"></i>
                                    </td>
                                    <td>
                                        <a href="{{ route('jornadas.edit', $item['id']) }}" class="text-decoration-none text-dark fw-medium">
                                            {{ $item['title'] ?: '—' }}
                                        </a>
                                    </td>
                                    <td>{{ $item['category'] }}</td>
                                    <td>
                                        @foreach($item['languages'] as $lang)
                                            <span class="dl-lang-badge dl-lang-{{ $lang }}">{{ strtoupper($lang) }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($item['status'] === 'published')
                                            <span class="dl-status-badge dl-status-published">{{ __('common.published') }}</span>
                                        @else
                                            <span class="dl-status-badge dl-status-draft">{{ __('common.draft') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dl-action-wrap">
                                            <a href="{{ route('jornadas.edit', $item['id']) }}"
                                                class="dl-action-box dl-action-edit" title="{{ __('common.edit') }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            @if($item['status'] === 'published')
                                            <a href="{{ route('jornadas.comments', $item['id']) }}"
                                                class="dl-action-box dl-action-comments" title="{{ __('common.comments') }}">
                                                <i class="bi bi-chat-dots"></i>
                                            </a>
                                            @endif
                                            <form action="{{ route('jornadas.destroy', $item['id']) }}"
                                                method="POST" class="d-inline jn-delete-form">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dl-action-box dl-action-delete" title="{{ __('common.delete') }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="dl-overlay-loader" id="dlOverlayLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text" id="dlLoaderText">{{ __('jornadas.deleting') }}</div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
$(document).ready(function() {

    // ---- Filter logic ----
    function applyFilters() {
        var statusVal = $('#filterStatus').val().toLowerCase();
        var catVal = $('#filterCategory').val().toLowerCase();

        $('#jornadasTbody tr').each(function() {
            var rowStatus = ($(this).data('status') || '').toLowerCase();
            var rowCat = ($(this).data('category') || '').toLowerCase();

            var matchStatus = !statusVal || rowStatus === statusVal;
            var matchCat = !catVal || rowCat === catVal;

            $(this).toggle(matchStatus && matchCat);
        });
    }

    $('#filterStatus, #filterCategory').on('change', applyFilters);

    // ---- Drag-and-drop ordering ----
    var tbody = document.getElementById('jornadasTbody');
    var jnSortable = null;
    if (tbody) {
        jnSortable = Sortable.create(tbody, {
            handle: '.jn-drag-handle',
            animation: 150,
            filter: function(evt, el) {
                return $(el).is(':hidden');
            },
            onEnd: function() {
                var items = [];
                var catCounters = {};
                $(tbody).find('tr:visible').each(function() {
                    var catId = $(this).data('category-id') || '_';
                    if (!catCounters[catId]) catCounters[catId] = 0;
                    catCounters[catId]++;
                    items.push({ id: $(this).data('id'), order: catCounters[catId] });
                });

                jnSortable.option('disabled', true);
                $('#dlLoaderText').text(Lang.order_saving);
                $('#dlOverlayLoader').addClass('active');

                $.ajax({
                    url: '{{ route("jornadas.reorder") }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}', items: items },
                    success: function() {
                        toastr.success(Lang.order_saved);
                    },
                    error: function() {
                        toastr.error(Lang.something_wrong);
                    },
                    complete: function() {
                        $('#dlOverlayLoader').removeClass('active');
                        $('#dlLoaderText').text(Lang.deleting);
                        jnSortable.option('disabled', false);
                    }
                });
            }
        });
    }

    // ---- Delete confirmation ----
    $(document).on('submit', '.jn-delete-form', function(e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: Lang.are_you_sure,
            text: Lang.confirm_delete_jornada,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_delete
        }).then((result) => {
            if (result.isConfirmed) {
                $('#dlOverlayLoader').addClass('active');
                form.submit();
            }
        });
    });

    // ---- Intercept Create New if no categories ----
    $('#btnCreateJornada').on('click', function(e) {
        if ($(this).data('has-categories') === '0') {
            e.preventDefault();
            Swal.fire({
                title: Lang.no_categories_found,
                text: Lang.create_category_first,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c6a55a',
                cancelButtonColor: '#6b7280',
                confirmButtonText: Lang.create_category,
                cancelButtonText: Lang.ok
            }).then(function(result) {
                if (result.isConfirmed) {
                    window.location.href = '{{ route("jornada-categories.index") }}';
                }
            });
        }
    });

    // ---- Show popup if redirected from create with no categories ----
    @if(session('no_categories'))
    Swal.fire({
        title: Lang.no_categories_found,
        text: Lang.create_category_first,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c6a55a',
        cancelButtonColor: '#6b7280',
        confirmButtonText: Lang.create_category,
        cancelButtonText: Lang.ok
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = '{{ route("jornada-categories.index") }}';
        }
    });
    @endif
});
</script>
@endsection
