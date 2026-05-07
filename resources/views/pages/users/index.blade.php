@extends('layouts.app')
@section('title', __('common.users'))

@section('vendorStyles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ __('common.users') }}</h3></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('common.users') }}</li>
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
                        <strong>{{ __('users.user_list') }}</strong>
                    </div>
                </div>
                <div class="card-body">
                    <div class="usr-table-wrap">
                    <table class="table table-bordered" id="usersTable">
                        <thead>
                            <tr>
                                <th width="50">{{ __('common.hash') }}</th>
                                <th width="60">{{ __('users.image') }}</th>
                                <th>{{ __('users.name') }}</th>
                                <th>{{ __('users.email') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th width="90">{{ __('common.blocked') }}</th>
                                <th>{{ __('users.subscription') }}</th>
                                {{-- <th>{{ __('users.active_language') }}</th>
                                <th>{{ __('users.timezone') }}</th> --}}
                                <th>{{ __('users.created') }}</th>
                                <th width="130">{{ __('common.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr data-uid="{{ $user['uid'] }}">
                                <td></td>
                                <td class="text-center">
                                    @if($user['profileImage'])
                                        <img src="{{ $user['profileImage'] }}" class="usr-avatar" alt=""
                                            onerror="this.src='{{ asset('assets/images/default-avatar.svg') }}';">
                                    @else
                                        <img src="{{ asset('assets/images/default-avatar.svg') }}" class="usr-avatar" alt="">
                                    @endif
                                </td>
                                <td>
                                    <div class="usr-cell-text" title="{{ $user['name'] }}">
                                        {{ $user['name'] ?: '-' }}
                                    </div>
                                </td>
                                <td>{{ $user['email'] ?: '-' }}</td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input toggle-status toggle-active"
                                               type="checkbox"
                                               role="switch"
                                               data-uid="{{ $user['uid'] }}"
                                               {{ $user['isActive'] ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td data-order="{{ $user['isBlocked'] ? 1 : 0 }}">
                                    @if($user['isBlocked'])
                                        <span class="dl-status-badge dl-status-cancelled" style="font-size:11px;padding:2px 8px;">{{ __('common.blocked') }}</span>
                                    @else
                                        <span class="dl-status-badge dl-status-published" style="font-size:11px;padding:2px 8px;">{{ __('common.active') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user['is_subscribed'])
                                        <span class="usr-sub-badge usr-sub-yes">{{ __('users.subscribed') }}</span>
                                    @else
                                        <span class="usr-sub-badge usr-sub-no">{{ __('users.free') }}</span>
                                    @endif
                                </td>
                                {{-- <td>
                                    @php $lang = $user['activeLanguage'] ?? ''; @endphp
                                    @if($lang)
                                        <span class="badge bg-light text-dark border">{{ strtoupper($lang) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $user['timezone'] ?: '-' }}</span>
                                </td> --}}
                                <td>
                                    {{ $user['createdAt'] ? \Carbon\Carbon::parse($user['createdAt'])->format('d M Y') : '-' }}
                                </td>
                                <td>
                                    <div class="usr-action-wrap">
                                        <button type="button" class="usr-action-box usr-action-view btn-view-user"
                                                data-uid="{{ $user['uid'] }}" title="{{ __('common.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button"
                                                class="usr-action-box btn-toggle-block {{ $user['isBlocked'] ? 'usr-action-unblock' : 'usr-action-block' }}"
                                                data-uid="{{ $user['uid'] }}"
                                                data-is-blocked="{{ $user['isBlocked'] ? '1' : '0' }}"
                                                title="{{ $user['isBlocked'] ? __('common.unblock_user') : __('common.block_user') }}">
                                            <i class="bi {{ $user['isBlocked'] ? 'bi-person-lock' : 'bi-person-slash' }}"></i>
                                        </button>
                                        {{-- <button type="button" class="usr-action-box btn-edit-prefs"
                                                data-uid="{{ $user['uid'] }}"
                                                data-name="{{ $user['name'] }}"
                                                data-lang="{{ $user['activeLanguage'] ?? '' }}"
                                                data-timezone="{{ $user['timezone'] ?? '' }}"
                                                title="{{ __('users.edit_preferences') }}"
                                                style="background:#e8f4fd; color:#2563eb;">
                                            <i class="bi bi-gear"></i>
                                        </button> --}}
                                        <button type="button" class="usr-action-box usr-action-delete btn-delete-user"
                                                data-uid="{{ $user['uid'] }}" data-name="{{ $user['name'] }}" title="{{ __('common.delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

{{-- User Details Modal --}}
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 12px; border: none;">

            {{-- Loader --}}
            <div class="usr-modal-loader" id="userModalLoader">
                <div class="loader-spinner" style="margin: 0 auto 12px;"></div>
                <div style="font-size:15px; font-weight:600; color:#6b7280;">{{ __('users.loading_details') }}</div>
            </div>

            {{-- Content --}}
            <div class="usr-modal-content" id="userModalContent">
                {{-- Header --}}
                <div class="usr-modal-header">
                    <div class="d-flex justify-content-between align-items-start w-100">
                        <div class="d-flex align-items-center gap-3">
                            <div id="modalAvatar"></div>
                            <div>
                                <div class="usr-modal-name" id="modalName">-</div>
                                <div class="usr-modal-email" id="modalEmail">-</div>
                                <div class="usr-modal-badges">
                                    <span class="usr-modal-badge" id="modalActiveBadge"></span>
                                    <span class="usr-modal-badge" id="modalSubBadge"></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="modal-body p-0">
                    {{-- Personal Info --}}
                    <div class="usr-info-section">
                        <div class="usr-info-title"><i class="bi bi-person"></i> {{ __('users.personal_info') }}</div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">{{ __('users.full_name') }}</span>
                            <span class="usr-info-value" id="modalFullName">-</span>
                        </div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">{{ __('users.email') }}</span>
                            <span class="usr-info-value" id="modalEmailInfo">-</span>
                        </div>
                        {{-- <div class="usr-info-row">
                            <span class="usr-info-label">UID</span>
                            <span class="usr-info-value" id="modalUid" style="font-size:12px;">-</span>
                        </div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">{{ __('users.login_type') }}</span>
                            <span class="usr-info-value" id="modalLoginType">-</span>
                        </div> --}}
                    </div>

                    {{-- Account Info --}}
                    {{-- <div class="usr-info-section">
                        <div class="usr-info-title"><i class="bi bi-shield-check"></i> Account Information</div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">{{ __('common.status') }}</span>
                            <span class="usr-info-value" id="modalStatus">-</span>
                        </div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">{{ __('users.subscription') }}</span>
                            <span class="usr-info-value" id="modalSubscription">-</span>
                        </div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">{{ __('users.created') }}</span>
                            <span class="usr-info-value" id="modalCreated">-</span>
                        </div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">Last Updated</span>
                            <span class="usr-info-value" id="modalUpdated">-</span>
                        </div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">{{ __('users.last_login') }}</span>
                            <span class="usr-info-value" id="modalLastLogin">-</span>
                        </div>
                    </div> --}}

                    {{-- Preferences --}}
                    {{-- <div class="usr-info-section">
                        <div class="usr-info-title"><i class="bi bi-gear"></i> {{ __('users.preferences') }}</div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">{{ __('users.active_language') }}</span>
                            <span class="usr-info-value" id="modalActiveLanguage">-</span>
                        </div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">{{ __('users.timezone') }}</span>
                            <span class="usr-info-value" id="modalTimezone">-</span>
                        </div>
                    </div> --}}

                    {{-- Activity Info --}}
                    <div class="usr-info-section">
                        <div class="usr-info-title"><i class="bi bi-fire"></i> {{ __('users.activity') }}</div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">{{ __('users.current_streak') }}</span>
                            <span class="usr-info-value" id="modalCurrentStreak">0</span>
                        </div>
                        <div class="usr-info-row">
                            <span class="usr-info-label">{{ __('users.lifetime_streak') }}</span>
                            <span class="usr-info-value" id="modalLifetimeStreak">0</span>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 14px 28px;">
                    <button type="button" class="btn btn-primary btn-sm px-4" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Edit Preferences Modal --}}
{{-- <div class="modal fade" id="editPrefsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #f1f5f9; padding: 16px 20px;">
                <h6 class="modal-title fw-bold mb-0">{{ __('users.edit_preferences') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">{{ __('users.active_language') }}</label>
                    <select class="form-select" id="prefLanguage">
                        <option value="">{{ __('users.select_language') }}</option>
                        <option value="pt">Português (PT)</option>
                        <option value="en">English (EN)</option>
                        <option value="es">Español (ES)</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold small">{{ __('users.timezone') }}</label>
                    <select class="form-select" id="prefTimezone">
                        <option value="">{{ __('users.select_timezone') }}</option>
                        @foreach(timezone_identifiers_list() as $tz)
                            <option value="{{ $tz }}">{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 12px 20px;">
                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary btn-sm px-3" id="btnSavePrefs">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div> --}}

{{-- Overlay Loader --}}
<div class="dl-overlay-loader" id="userOverlayLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text" id="userLoaderText">{{ __('common.processing') }}</div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
var baseUrl = "{{ url('/users') }}";

$(document).ready(function() {
    // DataTable
    var table = $('#usersTable').DataTable({
        order: [[7, 'desc']],
        columnDefs: [
            { orderable: false, targets: [0, 1, 4, 8] }
        ],
        language: {
            search: Lang.dt_search,
            searchPlaceholder: Lang.search_placeholder || "{{ __('users.search_placeholder') }}",
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

    // Toggle Active Status
    $(document).on('change', '.toggle-active', function(e) {
        e.preventDefault();
        var $switch = $(this);
        var uid = $switch.data('uid');
        var newState = $switch.is(':checked');

        // Revert immediately, confirm first
        $switch.prop('checked', !newState);

        var action = newState ? 'activate' : 'deactivate';

        Swal.fire({
            title: newState ? Lang.activate_user : Lang.deactivate_user,
            text: newState ? Lang.confirm_activate : Lang.confirm_deactivate,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#C8902E',
            cancelButtonColor: '#6b7280',
            confirmButtonText: newState ? Lang.yes_activate : Lang.yes_deactivate
        }).then(function(result) {
            if (result.isConfirmed) {
                showLoader(Lang.updating_status);

                $.ajax({
                    url: baseUrl + '/' + uid + '/toggle-active',
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        isActive: newState ? 1 : 0
                    },
                    success: function(res) {
                        hideLoader();
                        if (res.success) {
                            $switch.prop('checked', newState);
                            Swal.fire({
                                title: Lang.success,
                                text: res.message,
                                icon: 'success',
                                confirmButtonColor: '#C8902E'
                            });
                        } else {
                            toastr.error(res.error || Lang.failed_update_status);
                        }
                    },
                    error: function() {
                        hideLoader();
                        toastr.error(Lang.something_wrong);
                    }
                });
            }
        });
    });

    // Block / Unblock User
    var blockUrl = '{{ route("users.toggle-block", "USER_ID") }}';
    $(document).on('click', '.btn-toggle-block', function() {
        var $btn     = $(this);
        var uid      = $btn.data('uid');
        var blocked  = $btn.data('is-blocked') === 1 || $btn.data('is-blocked') === '1';

        Swal.fire({
            title: blocked ? Lang.unblock_user : Lang.block_user,
            text: blocked ? Lang.unblock_user_confirm : Lang.block_user_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: blocked ? '#C8902E' : '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function(result) {
            if (!result.isConfirmed) return;
            showLoader(Lang.processing);
            $.ajax({
                url: blockUrl.replace('USER_ID', uid),
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(res) {
                    hideLoader();
                    if (res.success) {
                        var nowBlocked = res.isBlocked;
                        // Update button
                        $btn.data('is-blocked', nowBlocked ? '1' : '0')
                            .attr('data-is-blocked', nowBlocked ? '1' : '0')
                            .attr('title', nowBlocked ? Lang.unblock_user : Lang.block_user)
                            .removeClass('usr-action-block usr-action-unblock')
                            .addClass(nowBlocked ? 'usr-action-unblock' : 'usr-action-block')
                            .find('i').attr('class', 'bi ' + (nowBlocked ? 'bi-person-lock' : 'bi-person-slash'));
                        // Update blocked status badge in the row
                        var $row = $btn.closest('tr');
                        var $badge = $row.find('td').eq(5).find('span');
                        if (nowBlocked) {
                            $badge.attr('class', 'dl-status-badge dl-status-cancelled').text(Lang.blocked || '{{ __("common.blocked") }}');
                        } else {
                            $badge.attr('class', 'dl-status-badge dl-status-published').text(Lang.active || '{{ __("common.active") }}');
                        }
                        Swal.fire({ icon: 'success', title: Lang.success, text: nowBlocked ? Lang.user_blocked : Lang.user_unblocked, confirmButtonColor: '#C8902E' });
                    } else {
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                },
                error: function() {
                    hideLoader();
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            });
        });
    });

    // Delete User (Soft Delete)
    $(document).on('click', '.btn-delete-user', function() {
        var uid = $(this).data('uid');
        var name = $(this).data('name') || 'this user';
        var $row = $(this).closest('tr');

        Swal.fire({
            title: Lang.delete_user,
            text: Lang.confirm_delete_user.replace(':name', name),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_delete_user
        }).then(function(result) {
            if (result.isConfirmed) {
                showLoader(Lang.deleting_user);

                $.ajax({
                    url: baseUrl + '/' + uid,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        hideLoader();
                        if (res.success) {
                            table.row($row).remove().draw();
                            Swal.fire({
                                title: Lang.deleted,
                                text: res.message,
                                icon: 'success',
                                confirmButtonColor: '#C8902E'
                            });
                        } else {
                            toastr.error(res.error || Lang.failed_delete_user);
                        }
                    },
                    error: function() {
                        hideLoader();
                        toastr.error(Lang.something_wrong);
                    }
                });
            }
        });
    });

    // View User Details
    $(document).on('click', '.btn-view-user', function() {
        var uid = $(this).data('uid');

        $('#userModalContent').addClass('d-none');
        $('#userModalLoader').addClass('active');
        $('#viewUserModal').modal('show');

        $.ajax({
            url: baseUrl + '/' + uid,
            method: 'GET',
            success: function(u) {
                // Avatar
                var defaultAvatar = '{{ asset("assets/images/default-avatar.svg") }}';
                if (u.profileImage) {
                    $('#modalAvatar').html('<img src="' + escHtml(u.profileImage) + '" class="usr-modal-avatar" alt="" onerror="this.src=\'' + defaultAvatar + '\';">');
                } else {
                    $('#modalAvatar').html('<img src="' + defaultAvatar + '" class="usr-modal-avatar" alt="">');
                }

                // Header
                $('#modalName').text(u.name || '-');
                $('#modalEmail').text(u.email || '-');

                // Badges
                if (u.isActive) {
                    $('#modalActiveBadge').text(Lang.active || '{{ __('common.active') }}').css({ background: '#22c55e', color: '#fff' });
                } else {
                    $('#modalActiveBadge').text(Lang.inactive || '{{ __('common.inactive') }}').css({ background: '#ef4444', color: '#fff' });
                }
                if (u.is_subscribed) {
                    $('#modalSubBadge').text(Lang.subscribed || '{{ __('users.subscribed') }}').css({ background: '#dbeafe', color: '#1e40af' });
                } else {
                    $('#modalSubBadge').text(Lang.free || '{{ __('users.free') }}').css({ background: '#f1f3f5', color: '#6b7280' });
                }

                // Personal Info
                $('#modalFullName').text(u.name || '-');
                $('#modalEmailInfo').text(u.email || '-');
                $('#modalUid').text(u.uid || '-');
                $('#modalLoginType').text(u.loginType ? ucfirst(u.loginType) : '-');

                // Account Info
                $('#modalStatus').html(u.isActive
                    ? '<span class="badge bg-success">' + (Lang.active || '{{ __('common.active') }}') + '</span>'
                    : '<span class="badge bg-danger">' + (Lang.inactive || '{{ __('common.inactive') }}') + '</span>');
                $('#modalSubscription').html(u.is_subscribed
                    ? '<span class="badge bg-success">' + (Lang.subscribed || '{{ __('users.subscribed') }}') + '</span>'
                    : '<span class="badge bg-secondary">' + (Lang.free || '{{ __('users.free') }}') + '</span>');
                $('#modalCreated').text(formatDate(u.createdAt));
                $('#modalUpdated').text(formatDate(u.updatedAt));
                $('#modalLastLogin').text(formatDate(u.lastLoginAt));

                // Preferences (commented out)
                // var langMap = { pt: 'Português (PT)', en: 'English (EN)', es: 'Español (ES)' };
                // $('#modalActiveLanguage').text(u.activeLanguage ? langMap[u.activeLanguage] || u.activeLanguage.toUpperCase() : '-');
                // $('#modalTimezone').text(u.timezone || '-');

                // Activity
                $('#modalCurrentStreak').text(u.currentStreak || 0);
                $('#modalLifetimeStreak').text(u.lifetimeStreak || 0);

                // Show content
                $('#userModalLoader').removeClass('active');
                $('#userModalContent').removeClass('d-none');
            },
            error: function() {
                $('#userModalLoader').removeClass('active');
                $('#viewUserModal').modal('hide');
                toastr.error(Lang.failed_load_details);
            }
        });
    });

    // Helpers
    function showLoader(text) {
        $('#userLoaderText').text(text || Lang.processing);
        $('#userOverlayLoader').addClass('active');
    }

    function hideLoader() {
        $('#userOverlayLoader').removeClass('active');
    }

    function formatDate(val) {
        if (!val) return '-';
        var d = new Date(val);
        if (isNaN(d.getTime())) return '-';
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var day = String(d.getDate()).padStart(2, '0');
        var mon = months[d.getMonth()];
        var year = d.getFullYear();
        var hrs = d.getHours();
        var mins = String(d.getMinutes()).padStart(2, '0');
        var ampm = hrs >= 12 ? 'PM' : 'AM';
        hrs = hrs % 12 || 12;
        return day + ' ' + mon + ' ' + year + ', ' + hrs + ':' + mins + ' ' + ampm;
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Read More - click to show full text in popup
    $(document).on('click', '.usr-read-more', function(e) {
        e.stopPropagation();
        var fullText = $(this).closest('.usr-cell-text').attr('title');
        Swal.fire({
            text: fullText,
            confirmButtonColor: '#C8902E',
            confirmButtonText: Lang.close,
            customClass: {
                popup: 'usr-readmore-popup'
            }
        });
    });

    // Truncate long names and show "Read more" link
    var maxChars = 30;
    function addReadMoreLinks() {
        $('.usr-cell-text').each(function() {
            var $el = $(this);
            if ($el.data('processed')) return;

            var fullText = $el.attr('title') || '';
            if (fullText.length > maxChars) {
                var truncated = fullText.substring(0, maxChars) + '...';
                $el.html('<span class="usr-text-truncated">' + escHtml(truncated) + '</span> <a href="javascript:void(0)" class="usr-read-more">' + Lang.read_more + '</a>');
            }
            $el.data('processed', true);
        });
    }

    table.on('draw', function() {
        $('.usr-cell-text').data('processed', false);
        addReadMoreLinks();
    });
    addReadMoreLinks();

    // Edit Preferences (commented out)
    /*
    var editPrefsUid = null;
    $(document).on('click', '.btn-edit-prefs', function() {
        editPrefsUid = $(this).data('uid');
        $('#prefLanguage').val($(this).data('lang') || '');
        $('#prefTimezone').val($(this).data('timezone') || '');
        $('#editPrefsModal').modal('show');
    });

    $('#btnSavePrefs').on('click', function() {
        var lang = $('#prefLanguage').val();
        var timezone = $('#prefTimezone').val();

        showLoader(Lang.saving_preferences);
        $('#editPrefsModal').modal('hide');

        $.ajax({
            url: baseUrl + '/' + editPrefsUid + '/update-fields',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                activeLanguage: lang,
                timezone: timezone
            },
            success: function(res) {
                hideLoader();
                if (res.success) {
                    var $row = $('tr[data-uid="' + editPrefsUid + '"]');
                    var $btn = $row.find('.btn-edit-prefs');
                    $btn.data('lang', lang).data('timezone', timezone);

                    var langCell = $row.find('td').eq(6);
                    if (lang) {
                        langCell.html('<span class="badge bg-light text-dark border">' + lang.toUpperCase() + '</span>');
                    } else {
                        langCell.html('<span class="text-muted">-</span>');
                    }

                    var tzCell = $row.find('td').eq(7);
                    tzCell.html('<span class="text-muted small">' + (timezone || '-') + '</span>');

                    toastr.success(Lang.preferences_updated);
                } else {
                    toastr.error(res.error || Lang.failed_update_preferences);
                }
            },
            error: function() {
                hideLoader();
                toastr.error(Lang.something_wrong);
            }
        });
    });
    */
});

function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}
</script>
@endsection
