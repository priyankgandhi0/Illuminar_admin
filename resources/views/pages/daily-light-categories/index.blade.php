@extends('layouts.app')
@section('title', __('common.daily_light_categories'))

@section('vendorStyles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ __('common.daily_light_categories') }}</h3></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('common.daily_light_categories') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            {{-- Send Notification Button (temporarily commented out)
            <div class="mb-3">
                <button type="button" class="btn btn-warning btn-sm" id="btnSendNotification">
                    <i class="bi bi-bell me-1"></i> {{ __('daily_light_categories.send_notification') }}
                </button>
            </div>
            --}}
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <strong>{{ __('daily_light_categories.category_list') }}</strong>
                        <button type="button" class="btn btn-primary btn-sm ms-auto" id="btnCreateCategory">
                            <i class="bi bi-plus-lg me-1"></i> {{ __('common.create_new') }}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="jn-table-wrap">
                        <table class="table table-bordered" id="dlcCategoriesTable">
                            <thead>
                                <tr>
                                    <th width="50">{{ __('common.hash') }}</th>
                                    <th width="70">{{ __('daily_light_categories.icon') }}</th>
                                    <th>{{ __('common.title') }}</th>
                                    <th>{{ __('common.languages') }}</th>
                                    <th width="110">{{ __('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $item)
                                <tr>
                                    <td></td>
                                    <td>
                                        @if($item['icon_image_url'])
                                            <div class="dlc-table-icon">
                                                <img src="{{ $item['icon_image_url'] }}" alt="icon">
                                            </div>
                                        @elseif($item['icon_svg'])
                                            <div class="dlc-table-icon">{!! $item['icon_svg'] !!}</div>
                                        @elseif($item['icon_url'])
                                            <div class="dlc-table-icon">
                                                <img src="{{ $item['icon_url'] }}" alt="icon">
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
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
                                            {{-- Temporarily commented out delete
                                            <form action="{{ route('daily-light-categories.destroy', $item['id']) }}"
                                                method="POST" class="d-inline dlc-delete-form" data-id="{{ $item['id'] }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dl-action-box dl-action-delete" title="{{ __('common.delete') }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            --}}
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
<div class="modal fade" id="dlcCategoryModal" tabindex="-1" aria-labelledby="dlcCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dlcCategoryModalLabel">{{ __('daily_light_categories.create_category') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dlcCategoryId" value="">

                {{-- SVG Icon Code --}}
                <div class="mb-3">
                    <label class="dl-label">{{ __('daily_light_categories.icon') }} (SVG) <span class="text-danger dlc-icon-required">*</span></label>
                    <div class="d-flex gap-3 align-items-start">
                        <div class="flex-grow-1">
                            <textarea id="dlcSvgCode" class="form-control" rows="5"
                                placeholder="Cole o código SVG aqui..."
                                style="font-family: monospace; font-size: 11px; resize: vertical;"></textarea>
                            <div class="text-danger small d-none mt-1" id="dlcIconError"></div>
                        </div>
                        <div class="dlc-svg-preview" id="dlcSvgPreview">
                            <span style="font-size: 11px; color: #888;">Ícone</span>
                        </div>
                    </div>
                </div>

                {{-- Language Tabs --}}
                <ul class="nav dl-lang-tabs mb-3" id="dlcModalLangTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link dl-lang-tab active" id="dlc-modal-tab-pt"
                            data-bs-toggle="tab" data-bs-target="#dlc-modal-panel-pt" type="button" role="tab" data-lang="pt">
                            {{ __('common.portuguese_pt') }}
                            <span class="dl-lang-dot active"></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link dl-lang-tab" id="dlc-modal-tab-en"
                            data-bs-toggle="tab" data-bs-target="#dlc-modal-panel-en" type="button" role="tab" data-lang="en">
                            {{ __('common.english_en') }}
                            <span class="dl-lang-dot active"></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link dl-lang-tab" id="dlc-modal-tab-es"
                            data-bs-toggle="tab" data-bs-target="#dlc-modal-panel-es" type="button" role="tab" data-lang="es">
                            {{ __('common.spanish_es') }}
                            <span class="dl-lang-dot active"></span>
                        </button>
                    </li>
                </ul>

                {{-- Tab Content --}}
                <div class="tab-content" id="dlcModalLangTabContent">
                    <div class="tab-pane fade show active" id="dlc-modal-panel-pt" role="tabpanel">
                        <div class="mb-3">
                            <label class="dl-label">{{ __('daily_light_categories.title_pt') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title_pt" class="form-control dlc-validate-field"
                                placeholder="{{ __('daily_light_categories.title_pt') }}" data-lang="pt">
                        </div>
                    </div>
                    <div class="tab-pane fade" id="dlc-modal-panel-en" role="tabpanel">
                        <div class="mb-3">
                            <label class="dl-label">{{ __('daily_light_categories.title_en') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title_en" class="form-control dlc-validate-field"
                                placeholder="{{ __('daily_light_categories.title_en') }}" data-lang="en">
                        </div>
                    </div>
                    <div class="tab-pane fade" id="dlc-modal-panel-es" role="tabpanel">
                        <div class="mb-3">
                            <label class="dl-label">{{ __('daily_light_categories.title_es') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title_es" class="form-control dlc-validate-field"
                                placeholder="{{ __('daily_light_categories.title_es') }}" data-lang="es">
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="dlcBtnSaveCategory">
                    <span class="btn-text">{{ __('daily_light_categories.save_category') }}</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="dl-overlay-loader" id="dlOverlayLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text">{{ __('daily_light_categories.deleting') }}</div>
</div>

<style>
.dlc-svg-preview {
    width: 100px;
    height: 100px;
    min-width: 100px;
    background: transparent;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 0;
}
.dlc-svg-preview svg { width: 100%; height: 100%; }
.dlc-svg-preview img { width: 100%; height: 100%; object-fit: contain; }
.dlc-table-icon {
    width: 40px;
    height: 40px;
    background: #3a2e1a;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6px;
}
.dlc-table-icon img,
.dlc-table-icon svg {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
</style>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {

    var allLangs = ['pt', 'en', 'es'];
    var dlcModal = new bootstrap.Modal(document.getElementById('dlcCategoryModal'));
    var existingIconUrl = null;
    var originalSvgCode = '';

    // ---- DataTable ----
    $('#dlcCategoriesTable').DataTable({
        order: [],
        columnDefs: [{ orderable: false, targets: [0, 1, 3, 4] }],
        language: {
            search: Lang.dt_search,
            searchPlaceholder: Lang.search_dl_categories,
            emptyTable: Lang.dt_empty,
            zeroRecords: Lang.dt_zero_records,
            lengthMenu: Lang.dt_length_menu,
            info: Lang.dt_info,
            infoEmpty: Lang.dt_info_empty,
            infoFiltered: Lang.dt_info_filtered,
            paginate: { previous: Lang.dt_paginate_previous, next: Lang.dt_paginate_next }
        },
        drawCallback: function() {
            var api = this.api();
            var start = api.page.info().start;
            api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                cell.innerHTML = start + i + 1;
            });
        }
    });

    // ---- SVG Helpers ----
    function extractFirstSvg(raw) {
        if (!raw) return '';
        var parser = new DOMParser();
        var doc = parser.parseFromString(raw, 'text/html');
        var svgEl = doc.querySelector('svg');
        if (!svgEl || svgEl.children.length === 0) return '';
        return svgEl.outerHTML;
    }

    function updateSvgPreview() {
        var raw = $('#dlcSvgCode').val().trim();
        var svg = extractFirstSvg(raw);
        if (svg) {
            $('#dlcSvgPreview').html(svg);
            $('#dlcSvgPreview svg').css({width: '100%', height: '100%'});
        } else if (existingIconUrl) {
            $('#dlcSvgPreview').html('<img src="' + existingIconUrl + '">');
        } else {
            $('#dlcSvgPreview').html('<span style="font-size:11px;color:#888;">Ícone</span>');
        }
    }

    $('#dlcSvgCode').on('input', function() {
        $(this).removeClass('is-invalid');
        $('#dlcIconError').addClass('d-none');
        updateSvgPreview();
    });

    // ---- Reset Modal ----
    function resetModal() {
        $('#dlcCategoryId').val('');
        $('#dlcCategoryModalLabel').text(Lang.create_dl_category_title);
        $('#dlcBtnSaveCategory .btn-text').text(Lang.save_dl_category);
        $('.dlc-icon-required').show();
        existingIconUrl = null;
        originalSvgCode = '';
        $('#dlcSvgCode').val('').removeClass('is-invalid');
        $('#dlcIconError').addClass('d-none');
        updateSvgPreview();
        allLangs.forEach(function(lang) {
            $('input[name="title_' + lang + '"]').val('').removeClass('is-invalid');
            $('#dlc-modal-tab-' + lang).find('.dl-lang-dot').addClass('active').removeClass('error');
        });
        $('.dlc-client-error').remove();
        $('#dlc-modal-tab-pt').tab('show');
    }

    // ---- Create Button ----
    $('#btnCreateCategory').on('click', function() {
        resetModal();
        dlcModal.show();
    });

    // ---- Edit Button ----
    $(document).on('click', '.btn-edit-category', function() {
        var id = $(this).data('id');
        resetModal();
        $('#dlcCategoryId').val(id);
        $('#dlcCategoryModalLabel').text(Lang.edit_dl_category_title);
        $('#dlcBtnSaveCategory .btn-text').text(Lang.update_dl_category);
        $('.dlc-icon-required').hide();

        $.ajax({
            url: '{{ url("daily-light-categories") }}/' + id + '/edit',
            type: 'GET',
            success: function(res) {
                if (res.success) {
                    $('input[name="title_pt"]').val(res.category.pt_title);
                    $('input[name="title_en"]').val(res.category.en_title);
                    $('input[name="title_es"]').val(res.category.es_title);
                    if (res.category.icon_svg) {
                        originalSvgCode = res.category.icon_svg;
                        $('#dlcSvgCode').val(res.category.icon_svg);
                    }
                    if (res.category.icon_url) {
                        existingIconUrl = res.category.icon_url;
                    }
                    updateSvgPreview();
                    dlcModal.show();
                } else {
                    toastr.error(res.message || Lang.failed_load_dl_category);
                }
            },
            error: function() {
                toastr.error(Lang.failed_load_dl_category);
            }
        });
    });

    // ---- Input handler ----
    $(document).on('input', '.dlc-validate-field', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.dlc-client-error').remove();
        var lang = $(this).data('lang');
        $('#dlc-modal-tab-' + lang).find('.dl-lang-dot').addClass('active').removeClass('error');
    });

    // ---- SVG to PNG Conversion ----
    function prepareSvgForCanvas(svgString, targetSize) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(svgString, 'image/svg+xml');
        var svg = doc.querySelector('svg');
        if (!svg) return svgString;
        svg.setAttribute('width', targetSize);
        svg.setAttribute('height', targetSize);
        return new XMLSerializer().serializeToString(svg);
    }

    function svgToPngBlob(svgString, size, fillColor) {
        return new Promise(function(resolve, reject) {
            var preparedSvg = prepareSvgForCanvas(svgString, size);
            var canvas = document.createElement('canvas');
            canvas.width = size;
            canvas.height = size;
            var ctx = canvas.getContext('2d');

            var img = new Image();
            var blob = new Blob([preparedSvg], { type: 'image/svg+xml;charset=utf-8' });
            var url = URL.createObjectURL(blob);

            img.onload = function() {
                ctx.drawImage(img, 0, 0, size, size);
                if (fillColor) {
                    ctx.globalCompositeOperation = 'source-in';
                    ctx.fillStyle = fillColor;
                    ctx.fillRect(0, 0, size, size);
                }
                URL.revokeObjectURL(url);
                canvas.toBlob(function(pngBlob) {
                    if (pngBlob) resolve(pngBlob);
                    else reject(new Error('PNG conversion failed'));
                }, 'image/png');
            };
            img.onerror = function() {
                URL.revokeObjectURL(url);
                reject(new Error('SVG render failed'));
            };
            img.src = url;
        });
    }

    // ---- Save ----
    $('#dlcBtnSaveCategory').on('click', function() {
        var $btn = $(this);

        // Clear previous errors
        $('.dlc-validate-field').removeClass('is-invalid');
        $('.dlc-client-error').remove();
        $('#dlcSvgCode').removeClass('is-invalid');
        $('#dlcIconError').addClass('d-none');

        var id = $('#dlcCategoryId').val();
        var isEdit = id !== '';
        var rawSvgCode = $('#dlcSvgCode').val().trim();
        var svgCode = extractFirstSvg(rawSvgCode);

        // Validate SVG code format (has content but not a valid SVG)
        if (rawSvgCode && !svgCode) {
            $('#dlcSvgCode').addClass('is-invalid');
            $('#dlcIconError').text('Código SVG inválido.').removeClass('d-none');
            return;
        }

        // Validate icon required (empty textarea in create mode, or cleared in edit mode)
        if (!svgCode && (!isEdit || originalSvgCode)) {
            $('#dlcSvgCode').addClass('is-invalid');
            $('#dlcIconError').text(Lang.image_required).removeClass('d-none');
            return;
        }

        // Validate titles
        for (var i = 0; i < allLangs.length; i++) {
            var lang = allLangs[i];
            var $input = $('input[name="title_' + lang + '"]');
            if (!($input.val() || '').trim()) {
                $input.addClass('is-invalid');
                if (!$input.next('.dlc-client-error').length) {
                    $input.after('<div class="invalid-feedback dlc-client-error">' + Lang.required_field + '</div>');
                }
                $('#dlc-modal-tab-' + lang).find('.dl-lang-dot').removeClass('active').addClass('error');
                $('#dlc-modal-tab-' + lang).tab('show');
                setTimeout(function() { $input.focus(); }, 200);
                return;
            }
        }

        var url = isEdit ? '{{ url("daily-light-categories") }}/' + id : '{{ route("daily-light-categories.store") }}';

        function buildFormData() {
            var fd = new FormData();
            fd.append('_token', '{{ csrf_token() }}');
            if (isEdit) fd.append('_method', 'PUT');
            fd.append('title_pt', $('input[name="title_pt"]').val());
            fd.append('title_en', $('input[name="title_en"]').val());
            fd.append('title_es', $('input[name="title_es"]').val());
            return fd;
        }

        function submitForm(formData) {
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        dlcModal.hide();
                        Swal.fire({
                            title: Lang.success,
                            text: res.message,
                            icon: 'success',
                            confirmButtonColor: '#c6a55a',
                            confirmButtonText: Lang.ok
                        }).then(function() { location.reload(); });
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
                                $input.after('<div class="invalid-feedback dlc-client-error">' + errors['title_' + lang][0] + '</div>');
                                $('#dlc-modal-tab-' + lang).find('.dl-lang-dot').removeClass('active').addClass('error');
                            }
                        });
                        if (errors['svg_code']) {
                            $('#dlcSvgCode').addClass('is-invalid');
                            $('#dlcIconError').text(errors['svg_code'][0]).removeClass('d-none');
                        }
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
        }

        $btn.prop('disabled', true);
        $btn.find('.btn-text').addClass('d-none');
        $btn.find('.spinner-border').removeClass('d-none');

        var fd = buildFormData();
        var svgChanged = svgCode && rawSvgCode !== originalSvgCode;

        if (svgChanged) {
            fd.append('svg_code', btoa(unescape(encodeURIComponent(svgCode))));

            // Convert SVG to 2 PNG images: original + dropdown (dark colored)
            Promise.all([
                svgToPngBlob(svgCode, 200, null),
                svgToPngBlob(svgCode, 200, '#2c2108')
            ]).then(function(blobs) {
                fd.append('icon_image', blobs[0], 'icon.png');
                fd.append('icon_dropdown', blobs[1], 'icon_dropdown.png');
                submitForm(fd);
            }).catch(function(err) {
                console.error('SVG to PNG error:', err);
                // Fallback: submit without PNGs
                submitForm(fd);
            });
        } else {
            submitForm(fd);
        }
    });

    // ---- Delete ----
    $(document).on('submit', '.dlc-delete-form', function(e) {
        e.preventDefault();
        var form = this;
        var categoryId = $(form).data('id');

        $('#dlOverlayLoader').addClass('active');

        $.ajax({
            url: '{{ url("daily-light-categories") }}/' + categoryId + '/check-usage',
            type: 'GET',
            success: function(res) {
                $('#dlOverlayLoader').removeClass('active');
                if (res.in_use) {
                    Swal.fire({
                        title: Lang.cannot_delete,
                        text: Lang.dl_category_has_items,
                        icon: 'warning',
                        confirmButtonColor: '#c6a55a',
                        confirmButtonText: Lang.ok
                    });
                } else {
                    Swal.fire({
                        title: Lang.are_you_sure,
                        text: Lang.confirm_delete_dl_category,
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
                $('#dlOverlayLoader').removeClass('active');
                toastr.error(Lang.failed_check_usage);
            }
        });
    });
});
</script>
@endsection
