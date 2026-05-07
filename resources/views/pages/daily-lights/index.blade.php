@extends('layouts.app')
@section('title', __('common.daily_lights'))

@section('vendorStyles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ __('daily_lights.daily_light') }}</h3></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('daily_lights.daily_light') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            {{-- Daily Notification Toggle (commented out)
            <div class="card mb-3">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <strong><i class="bi bi-bell me-2"></i>{{ __('daily_lights.daily_morning_notif') }}</strong>
                            <a href="javascript:void(0)" id="editNotifBtn" class="{{ $dailyNotificationEnabled ? '' : 'd-none' }}" style="font-size: 13px; color: #b8860b; font-weight: 600; text-decoration: none;">
                                <i class="bi bi-pencil-square me-1"></i>{{ __('common.edit') }}
                            </a>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input toggle-status" type="checkbox" role="switch"
                                id="dailyNotifToggle" {{ $dailyNotificationEnabled ? 'checked' : '' }}
                                style="width: 3em; height: 1.5em; cursor: pointer;">
                        </div>
                    </div>
                    <div class="text-muted" style="font-size: 13px;">{{ __('daily_lights.notif_description') }}</div>
                </div>
            </div>

            Edit Notification Modal
            <div class="modal fade" id="editNotifModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg, #C8902E 0%, #9A6D22 100%); color: #fff; border-bottom: none;">
                            <h6 class="modal-title"><i class="bi bi-bell me-2"></i>{{ __('daily_lights.edit_notif_content') }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0) invert(1); opacity: 0.8;"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" style="font-weight:600;">{{ __('daily_lights.notif_title') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="notifTitle"
                                    value="{{ $notifTitle }}" placeholder="e.g. Daily Light">
                                <small class="text-danger d-none" id="notifTitleError">{{ __('daily_lights.notif_title_required') }}</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-weight:600;">{{ __('daily_lights.notif_message') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="notifBody"
                                    value="{{ $notifBody }}" placeholder="e.g. Start your day with today's Daily Light">
                                <small class="text-danger d-none" id="notifBodyError">{{ __('daily_lights.notif_message_required') }}</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                            <button type="button" class="btn btn-primary btn-sm" id="saveNotifContent">
                                <span class="spinner-border spinner-border-sm d-none me-1" id="notifSaveSpinner"></span>
                                <i class="bi bi-check-lg me-1" id="notifSaveIcon"></i>{{ __('common.save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            --}}

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <strong>{{ __('daily_lights.daily_light_list') }}</strong>
                        <a href="{{ route('daily-lights.create') }}" class="btn btn-primary btn-sm ms-auto">
                            <i class="bi bi-plus-lg me-1"></i> {{ __('common.create_new') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="dl-table-wrap">
                        <table class="table table-bordered" id="dailyLightsTable">
                            <thead>
                                <tr>
                                    <th width="50">{{ __('common.hash') }}</th>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('common.title') }}</th>
                                    <th>{{ __('common.languages') }}</th>
                                    <th>{{ __('common.featured') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                    <th width="110">{{ __('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyLights as $index => $item)
                                <tr>
                                    <td data-label="{{ __('common.hash') }}"></td>
                                    <td data-label="{{ __('common.date') }}" data-order="{{ $item['sortTimestamp'] ?? 0 }}">
                                        {{ $item['date'] }}
                                        @if(!empty($item['publishTime']))
                                            {{-- <small class="text-muted ms-1">{{ $item['publishTime'] }}</small> --}}
                                            &nbsp; &nbsp;{{ $item['publishTime'] }}
                                        @endif
                                    </td>
                                    <td data-label="{{ __('common.title') }}">{{ $item['title'] }}</td>
                                    <td data-label="{{ __('common.languages') }}">
                                        @foreach($item['languages'] as $lang)
                                            <span class="dl-lang-badge dl-lang-{{ $lang }}">{{ strtoupper($lang) }}</span>
                                        @endforeach
                                    </td>
                                    <td data-label="{{ __('common.featured') }}">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-status dl-featured-toggle"
                                                type="checkbox"
                                                data-id="{{ $item['id'] }}"
                                                {{ !empty($item['isFeatured']) ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('common.status') }}">
                                        @if($item['status'] === 'published')
                                            <span class="dl-status-badge dl-status-published">{{ __('common.published') }}</span>
                                        @elseif($item['status'] === 'scheduled')
                                            <span class="dl-status-badge dl-status-scheduled">{{ __('common.scheduled') }}</span>
                                        @else
                                            <span class="dl-status-badge dl-status-draft">{{ __('common.draft') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('common.action') }}">
                                        <div class="dl-action-wrap">
                                            <a href="{{ route('daily-lights.edit', $item['id']) }}" class="dl-action-box dl-action-edit" title="{{ __('common.edit') }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            @if($item['status'] === 'published')
                                            <a href="{{ route('daily-lights.comments', $item['id']) }}" class="dl-action-box dl-action-comments" title="{{ __('common.comments') }}">
                                                <i class="bi bi-chat-dots"></i>
                                            </a>
                                            @endif
                                            <form action="{{ route('daily-lights.destroy', $item['id']) }}" method="POST" class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
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
    <div class="loader-text">{{ __('daily_lights.deleting') }}</div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Show success toastr if redirected from AJAX save
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('saved') === '1') {
        toastr.success("{{ __('daily_lights.created_success') }}");
        // Clean URL
        window.history.replaceState({}, '', window.location.pathname);
    }

    var table = $('#dailyLightsTable').DataTable({
        order: [[1, 'desc']],
        columnDefs: [{ orderable: false, targets: [0, 4, 6] }],
        language: {
            search: Lang.dt_search,
            searchPlaceholder: "{{ __('daily_lights.search_placeholder') }}",
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

    // Featured toggle with confirmation
    $(document).on('change', '.dl-featured-toggle', function() {
        var $toggle = $(this);
        var id = $toggle.data('id');
        var isFeatured = $toggle.is(':checked');
        var actionText = isFeatured ? Lang.confirm_featured_mark_dl : Lang.confirm_featured_remove_dl;

        // Revert immediately until the admin confirms
        $toggle.prop('checked', !isFeatured);

        Swal.fire({
            title: Lang.are_you_sure,
            text: actionText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#c6a55a',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm
        }).then(function(result) {
            if (result.isConfirmed) {
                $toggle.prop('checked', isFeatured);
                $.ajax({
                    url: '{{ url("daily-lights") }}/' + id + '/toggle-featured',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        isFeatured: isFeatured ? 1 : 0
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(Lang.featured_updated_dl);
                        } else {
                            toastr.error(Lang.failed_featured_dl);
                            $toggle.prop('checked', !isFeatured);
                        }
                    },
                    error: function() {
                        toastr.error(Lang.failed_featured_dl);
                        $toggle.prop('checked', !isFeatured);
                    }
                });
            }
        });
    });

    /* Daily Notification JS (commented out)
    // Edit button - open modal
    $('#editNotifBtn').on('click', function() {
        $('#editNotifModal').modal('show');
    });

    // Daily notification toggle
    $('#dailyNotifToggle').on('change', function() {
        var enabled = $(this).is(':checked');

        if (enabled) {
            $('#editNotifBtn').removeClass('d-none');
        } else {
            $('#editNotifBtn').addClass('d-none');
        }

        saveNotifSettings(enabled, false);
    });

    // Clear validation on input
    $('#notifTitle').on('input', function() {
        $(this).removeClass('is-invalid');
        $('#notifTitleError').addClass('d-none');
    });
    $('#notifBody').on('input', function() {
        $(this).removeClass('is-invalid');
        $('#notifBodyError').addClass('d-none');
    });

    // Store saved values to restore on cancel
    var savedNotifTitle = $('#notifTitle').val();
    var savedNotifBody = $('#notifBody').val();

    // Clear validation when modal opens
    $('#editNotifModal').on('show.bs.modal', function() {
        $('#notifTitle, #notifBody').removeClass('is-invalid');
        $('#notifTitleError, #notifBodyError').addClass('d-none');
    });

    // Restore saved values when modal is closed (cancel/X)
    $('#editNotifModal').on('hidden.bs.modal', function() {
        $('#notifTitle').val(savedNotifTitle);
        $('#notifBody').val(savedNotifBody);
    });

    // Save notification content button
    $('#saveNotifContent').on('click', function() {
        var title = $('#notifTitle').val().trim();
        var body = $('#notifBody').val().trim();
        var valid = true;

        // Reset
        $('#notifTitle, #notifBody').removeClass('is-invalid');
        $('#notifTitleError, #notifBodyError').addClass('d-none');

        if (!title) {
            $('#notifTitle').addClass('is-invalid');
            $('#notifTitleError').removeClass('d-none');
            valid = false;
        }
        if (!body) {
            $('#notifBody').addClass('is-invalid');
            $('#notifBodyError').removeClass('d-none');
            valid = false;
        }
        if (!valid) return;

        var enabled = $('#dailyNotifToggle').is(':checked');
        saveNotifSettings(enabled, true, function() {
            // Update saved values after successful save
            savedNotifTitle = $('#notifTitle').val();
            savedNotifBody = $('#notifBody').val();
            $('#editNotifModal').modal('hide');
        });
    });

    function saveNotifSettings(enabled, saveContent, onSuccess) {
        var data = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            enabled: enabled ? 1 : 0
        };

        if (saveContent) {
            data.save_content = 1;
            data.title = $('#notifTitle').val().trim();
            data.body = $('#notifBody').val().trim();
            $('#saveNotifContent').prop('disabled', true);
            $('#notifSaveSpinner').removeClass('d-none');
            $('#notifSaveIcon').addClass('d-none');
        }

        $.ajax({
            url: '{{ route("daily-lights.toggle-notification") }}',
            method: 'POST',
            data: data,
            success: function(res) {
                if (res.success) {
                    if (saveContent) {
                        toastr.success(Lang.notif_content_updated);
                    } else {
                        var msg = enabled ? Lang.notif_enabled : Lang.notif_disabled;
                        toastr.success(msg);
                    }
                    if (onSuccess) onSuccess();
                }
            },
            error: function() {
                toastr.error(Lang.notif_save_failed);
                if (!enabled) {
                    $('#dailyNotifToggle').prop('checked', true);
                }
            },
            complete: function() {
                $('#saveNotifContent').prop('disabled', false);
                $('#notifSaveSpinner').addClass('d-none');
                $('#notifSaveIcon').removeClass('d-none');
            }
        });
    }
    */

    $(document).on('submit', '.delete-form', function(e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: Lang.are_you_sure,
            text: Lang.confirm_delete_dl,
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
});
</script>
@endsection
