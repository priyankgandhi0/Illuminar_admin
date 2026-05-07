@extends('layouts.app')
@section('title', __('common.jornadas_categories'))

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ __('common.jornadas_categories') }}</h3></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('common.jornadas_categories') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="d-flex align-items-center gap-2">
                            <strong>{{ __('jornada_categories.category_list') }}</strong>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm ms-auto" id="btnCreateCategory">
                            <i class="bi bi-plus-lg me-1"></i> {{ __('common.create_new') }}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="jn-table-wrap">
                        <table class="table table-bordered" id="jnCategoriesTable">
                            <thead>
                                <tr>
                                    <th width="40" style="text-align:center; color:#aaa;"><i class="bi bi-grip-vertical"></i></th>
                                    <th>{{ __('common.title') }}</th>
                                    <th>{{ __('common.languages') }}</th>
                                    <th width="110">{{ __('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="jnCategoriesTbody">
                                @foreach($categories as $item)
                                <tr data-id="{{ $item['id'] }}">
                                    <td class="jc-drag-handle" style="cursor: grab; text-align: center; color: #aaa;">
                                        <i class="bi bi-grip-vertical"></i>
                                    </td>
                                    <td>{{ $item['title'] }}</td>
                                    <td>
                                        @foreach($item['languages'] as $lang)
                                            <span class="dl-lang-badge dl-lang-{{ $lang }}">{{ strtoupper($lang) }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <div class="dl-action-wrap">
                                            <button type="button" class="dl-action-box dl-action-edit btn-edit-category"
                                                title="{{ __('common.edit') }}" data-id="{{ $item['id'] }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form action="{{ route('jornada-categories.destroy', $item['id']) }}"
                                                method="POST" class="d-inline jc-delete-form" data-id="{{ $item['id'] }}">
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

{{-- Create/Edit Modal --}}
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">{{ __('jornada_categories.create_category') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="categoryId" value="">

                {{-- Language Tabs --}}
                <ul class="nav dl-lang-tabs mb-3" id="modalLangTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link dl-lang-tab active" id="modal-tab-pt"
                            data-bs-toggle="tab" data-bs-target="#modal-panel-pt" type="button" role="tab" data-lang="pt">
                            {{ __('common.portuguese_pt') }}
                            <span class="dl-lang-dot active"></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link dl-lang-tab" id="modal-tab-en"
                            data-bs-toggle="tab" data-bs-target="#modal-panel-en" type="button" role="tab" data-lang="en">
                            {{ __('common.english_en') }}
                            <span class="dl-lang-dot active"></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link dl-lang-tab" id="modal-tab-es"
                            data-bs-toggle="tab" data-bs-target="#modal-panel-es" type="button" role="tab" data-lang="es">
                            {{ __('common.spanish_es') }}
                            <span class="dl-lang-dot active"></span>
                        </button>
                    </li>
                </ul>

                {{-- Tab Content --}}
                <div class="tab-content" id="modalLangTabContent">
                    <div class="tab-pane fade show active" id="modal-panel-pt" role="tabpanel">
                        <div class="mb-3">
                            <label class="dl-label">{{ __('jornada_categories.title_pt') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title_pt" class="form-control modal-validate-field"
                                placeholder="Enter category title" data-lang="pt">
                        </div>
                    </div>
                    <div class="tab-pane fade" id="modal-panel-en" role="tabpanel">
                        <div class="mb-3">
                            <label class="dl-label">{{ __('jornada_categories.title_en') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title_en" class="form-control modal-validate-field"
                                placeholder="Enter category title" data-lang="en">
                        </div>
                    </div>
                    <div class="tab-pane fade" id="modal-panel-es" role="tabpanel">
                        <div class="mb-3">
                            <label class="dl-label">{{ __('jornada_categories.title_es') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title_es" class="form-control modal-validate-field"
                                placeholder="Enter category title" data-lang="es">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btnSaveCategory">
                    <span class="btn-text">{{ __('jornada_categories.save_category') }}</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="dl-overlay-loader" id="dlOverlayLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text" id="dlLoaderText">{{ __('jornada_categories.deleting') }}</div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
$(document).ready(function() {

    var allLangs = ['pt', 'en', 'es'];
    var categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));

    // ---- Drag-and-drop ordering ----
    var tbody = document.getElementById('jnCategoriesTbody');
    if (tbody) {
        var jcSortable = Sortable.create(tbody, {
            handle: '.jc-drag-handle',
            animation: 150,
            onEnd: function() {
                var items = [];
                $(tbody).find('tr').each(function(i) {
                    items.push({ id: $(this).data('id'), order: i + 1 });
                });

                jcSortable.option('disabled', true);
                $('#dlLoaderText').text(Lang.order_saving);
                $('#dlOverlayLoader').addClass('active');

                $.ajax({
                    url: '{{ route("jornada-categories.reorder") }}',
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
                        jcSortable.option('disabled', false);
                    }
                });
            }
        });
    }

    // ---- Reset Modal ----
    function resetModal() {
        $('#categoryId').val('');
        $('#categoryModalLabel').text(Lang.create_category_title);
        $('#btnSaveCategory .btn-text').text(Lang.save_category);
        allLangs.forEach(function(lang) {
            $('input[name="title_' + lang + '"]').val('').removeClass('is-invalid');
            $('#modal-tab-' + lang).find('.dl-lang-dot').addClass('active').removeClass('error');
        });
        $('.modal-client-error').remove();
        $('#modal-tab-pt').tab('show');
    }

    // ---- Create Button ----
    $('#btnCreateCategory').on('click', function() {
        resetModal();
        categoryModal.show();
    });

    // ---- Edit Button ----
    $(document).on('click', '.btn-edit-category', function() {
        var id = $(this).data('id');
        resetModal();
        $('#categoryId').val(id);
        $('#categoryModalLabel').text(Lang.edit_category_title);
        $('#btnSaveCategory .btn-text').text(Lang.update_category);

        // Fetch category data
        $.ajax({
            url: '{{ url("jornada-categories") }}/' + id + '/edit',
            type: 'GET',
            success: function(res) {
                if (res.success) {
                    $('input[name="title_pt"]').val(res.category.pt_title);
                    $('input[name="title_en"]').val(res.category.en_title);
                    $('input[name="title_es"]').val(res.category.es_title);
                    categoryModal.show();
                } else {
                    toastr.error(res.message || Lang.failed_load_category);
                }
            },
            error: function() {
                toastr.error(Lang.failed_load_category_data);
            }
        });
    });

    // ---- Input handler ----
    $(document).on('input', '.modal-validate-field', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.modal-client-error').remove();
        var lang = $(this).data('lang');
        $('#modal-tab-' + lang).find('.dl-lang-dot').addClass('active').removeClass('error');
    });

    // ---- Save ----
    $('#btnSaveCategory').on('click', function() {
        var $btn = $(this);

        // Clear previous errors
        $('.modal-validate-field').removeClass('is-invalid');
        $('.modal-client-error').remove();

        // Validate
        for (var i = 0; i < allLangs.length; i++) {
            var lang = allLangs[i];
            var $input = $('input[name="title_' + lang + '"]');
            if (!($input.val() || '').trim()) {
                $input.addClass('is-invalid');
                if (!$input.next('.modal-client-error').length) {
                    $input.after('<div class="invalid-feedback modal-client-error">' + Lang.required_field + '</div>');
                }
                $('#modal-tab-' + lang).find('.dl-lang-dot').removeClass('active').addClass('error');
                $('#modal-tab-' + lang).tab('show');
                setTimeout(function() { $input.focus(); }, 200);
                return;
            }
        }

        var id = $('#categoryId').val();
        var isEdit = id !== '';
        var url = isEdit ? '{{ url("jornada-categories") }}/' + id : '{{ route("jornada-categories.store") }}';
        var method = isEdit ? 'PUT' : 'POST';

        $btn.prop('disabled', true);
        $btn.find('.btn-text').addClass('d-none');
        $btn.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: method,
                title_pt: $('input[name="title_pt"]').val(),
                title_en: $('input[name="title_en"]').val(),
                title_es: $('input[name="title_es"]').val()
            },
            success: function(res) {
                if (res.success) {
                    categoryModal.hide();
                    Swal.fire({
                        title: Lang.success,
                        text: res.message,
                        icon: 'success',
                        confirmButtonColor: '#c6a55a',
                        confirmButtonText: Lang.ok
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    toastr.error(res.message || Lang.something_wrong);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors || {};
                    allLangs.forEach(function(lang) {
                        if (errors['title_' + lang]) {
                            var $input = $('input[name="title_' + lang + '"]');
                            $input.addClass('is-invalid');
                            $input.after('<div class="invalid-feedback modal-client-error">' + errors['title_' + lang][0] + '</div>');
                            $('#modal-tab-' + lang).find('.dl-lang-dot').removeClass('active').addClass('error');
                        }
                    });
                } else {
                    toastr.error(Lang.something_wrong);
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.find('.btn-text').removeClass('d-none');
                $btn.find('.spinner-border').addClass('d-none');
            }
        });
    });

    // ---- Delete ----
    $(document).on('submit', '.jc-delete-form', function(e) {
        e.preventDefault();
        var form = this;
        var categoryId = $(form).data('id');
        var $btn = $(form).find('.dl-action-delete');
        var btnHtml = $btn.html();

        // Show spinner on button
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span>');

        // Check if category has jornadas
        $.ajax({
            url: '{{ url("jornada-categories") }}/' + categoryId + '/check-usage',
            type: 'GET',
            success: function(res) {
                $btn.prop('disabled', false).html(btnHtml);
                if (res.in_use) {
                    Swal.fire({
                        title: Lang.cannot_delete,
                        text: Lang.category_has_jornadas,
                        icon: 'warning',
                        confirmButtonColor: '#c6a55a',
                        confirmButtonText: Lang.ok
                    });
                } else {
                    Swal.fire({
                        title: Lang.are_you_sure,
                        text: Lang.confirm_delete_category,
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
                }
            },
            error: function() {
                $btn.prop('disabled', false).html(btnHtml);
                toastr.error(Lang.failed_check_usage);
            }
        });
    });
});
</script>
@endsection
