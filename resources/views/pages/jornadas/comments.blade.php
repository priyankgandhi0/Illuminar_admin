@extends('layouts.app')
@section('title', __('common.comments') . ' — ' . $jornadaTitle)

@section('vendorStyles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ __('common.comments') }} — {{ $jornadaTitle }}</h3></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('jornadas.index') }}">{{ __('common.jornadas') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('common.comments') }}</li>
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
                        <strong>{{ count($comments) }} {{ __('common.comments') }}</strong>
                        <a href="{{ route('jornadas.index') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="jn-table-wrap">
                        <table class="table table-bordered" id="jnCommentsTable">
                            <thead>
                                <tr>
                                    <th width="50">{{ __('common.hash') }}</th>
                                    <th width="80">{{ __('common.lesson') }}</th>
                                    <th width="160">{{ __('common.user') }}</th>
                                    <th>{{ __('common.message') }}</th>
                                    <th width="160">{{ __('common.date') }}</th>
                                    <th width="100">{{ __('common.subscriber') }}</th>
                                    <th width="60">{{ __('common.likes') }}</th>
                                    <th width="120">{{ __('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comments as $comment)
                                @php $msgFull = $comment['message']; $msgShort = mb_strlen($msgFull) > 120; @endphp
                                <tr id="jn-comment-row-{{ $comment['id'] }}">
                                    <td></td>
                                    <td class="text-center">
                                        <span class="dl-status-badge" style="background:#e0e7ff;color:#4338ca;border:1px solid #c7d2fe;">
                                            {{ __('common.lesson') }} {{ $comment['lessonNumber'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button"
                                            class="btn btn-link p-0 text-start btn-jn-user-info fw-semibold"
                                            data-user-id="{{ $comment['userId'] }}"
                                            data-user-name="{{ $comment['userName'] }}"
                                            data-user-email="{{ $comment['userEmail'] }}"
                                            data-user-updated-at="{{ $comment['userUpdatedAt'] }}"
                                            data-user-timezone="{{ $comment['userTimezone'] }}"
                                            data-is-blocked="{{ $comment['isBlocked'] ? '1' : '0' }}"
                                            style="text-decoration:none;color:inherit;line-height:1.3;">
                                            {{ $comment['userName'] ?: '—' }}
                                        </button>
                                        @if($comment['isBlocked'])
                                            <br><span class="dl-status-badge dl-status-cancelled dl-blocked-badge" style="font-size:10px;padding:1px 6px;margin-top:3px;display:inline-block;">{{ __('common.blocked') }}</span>
                                        @endif
                                    </td>
                                    <td style="max-width:320px;word-break:break-word;">
                                        @if($msgShort)
                                            {{ mb_substr($msgFull, 0, 120) }}…
                                            <button type="button"
                                                class="btn btn-link p-0 btn-jn-read-more"
                                                data-message="{{ e($msgFull) }}"
                                                data-user="{{ $comment['userName'] ?: '—' }}"
                                                data-date="{{ $comment['createdAt'] ? \Carbon\Carbon::parse($comment['createdAt'])->setTimezone($comment['userTimezone'] ?: 'America/Sao_Paulo')->format('d/m/Y H:i') : '—' }}"
                                                style="font-size:12px;vertical-align:middle;">
                                                {{ __('js.read_more') }}
                                            </button>
                                        @else
                                            {{ $msgFull }}
                                        @endif

                                        @if($comment['isHidden'] || $comment['isProhibitedWord'] || $comment['isReported'])
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            @if($comment['isHidden'])
                                                <span class="cm-badge cm-badge-hidden" style="font-size:10px;padding:1px 7px;"><i class="bi bi-eye-slash me-1"></i>{{ __('common.hidden') }}</span>
                                            @endif
                                            @if($comment['isProhibitedWord'])
                                                <span class="cm-badge cm-badge-prohibited" style="font-size:10px;padding:1px 7px;"><i class="bi bi-shield-exclamation me-1"></i>{{ __('common.prohibited_word') }}</span>
                                            @endif
                                            @if($comment['isReported'])
                                                <span class="cm-badge" style="font-size:10px;padding:1px 7px;background:#fff7ed;color:#ea580c;border:1px solid #fdba74;font-weight:600;"><i class="bi bi-flag-fill me-1"></i>{{ __('common.reported') }}</span>
                                            @endif
                                        </div>
                                        @endif
                                    </td>
                                    <td data-order="{{ $comment['createdAt'] }}" style="white-space:nowrap;">
                                        @if($comment['createdAt'])
                                            @php $cTz = $comment['userTimezone'] ?: 'America/Sao_Paulo'; @endphp
                                            {{ \Carbon\Carbon::parse($comment['createdAt'])->setTimezone($cTz)->format('d/m/Y H:i') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($comment['isSubscribedUser'])
                                            <span class="dl-status-badge dl-status-published">{{ __('common.yes') }}</span>
                                        @else
                                            <span class="dl-status-badge dl-status-cancelled">{{ __('common.no') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $comment['likes'] }}</td>
                                    <td>
                                        <div class="dl-action-wrap">
                                            <button type="button"
                                                class="dl-action-box dl-action-block btn-jn-block-user {{ $comment['isBlocked'] ? 'active-blocked' : '' }}"
                                                data-user-id="{{ $comment['userId'] }}"
                                                data-is-blocked="{{ $comment['isBlocked'] ? '1' : '0' }}"
                                                title="{{ $comment['isBlocked'] ? __('common.unblock_user') : __('common.block_user') }}">
                                                <i class="bi {{ $comment['isBlocked'] ? 'bi-person-lock' : 'bi-person-slash' }}"></i>
                                            </button>
                                            <button type="button"
                                                class="dl-action-box dl-action-delete btn-jn-delete-comment"
                                                data-comment-id="{{ $comment['id'] }}"
                                                data-lesson-id="{{ $comment['lessonId'] }}"
                                                title="{{ __('common.delete') }}">
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

{{-- User Info Modal --}}
<div class="modal fade" id="jnUserInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#C8902E 0%,#9A6D22 100%);color:#fff;border-bottom:none;">
                <h6 class="modal-title"><i class="bi bi-person-circle me-2"></i>{{ __('common.user_info') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:brightness(0) invert(1);opacity:.8;"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><strong>{{ __('common.name') }}:</strong> <span id="jnModalUserName">—</span></div>
                <div class="mb-2"><strong>{{ __('common.email') }}:</strong> <span id="jnModalUserEmail">—</span></div>
                <div class="mb-2"><strong>{{ __('common.last_updated') }}:</strong> <span id="jnModalUserUpdatedAt">—</span></div>
            </div>
            <div class="modal-footer">
                <button type="button" id="jnBtnToggleBlock" class="btn btn-sm btn-warning">
                    <i class="bi bi-person-slash me-1" id="jnBlockIcon"></i><span id="jnBlockLabel">{{ __('common.block_user') }}</span>
                </button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Read More Modal --}}
<div class="modal fade" id="jnReadMoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="jnReadMoreUser"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size:12px;" id="jnReadMoreDate"></p>
                <p style="white-space:pre-wrap;word-break:break-word;" id="jnReadMoreMessage"></p>
            </div>
        </div>
    </div>
</div>
<div class="dl-overlay-loader" id="jnOverlayLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text" id="jnLoaderText">{{ __('common.processing') }}</div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function () {
    var jornadaId = '{{ $id }}';
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // DataTable
    var table = $('#jnCommentsTable').DataTable({
        order: [[4, 'desc']],
        columnDefs: [{ orderable: false, targets: [0, 1, 7] }],
        language: {
            search: Lang.dt_search,
            searchPlaceholder: '{{ __("daily_lights.search_placeholder") }}',
            emptyTable: Lang.dt_empty,
            zeroRecords: Lang.dt_zero_records,
            lengthMenu: Lang.dt_length_menu,
            info: Lang.dt_info,
            infoEmpty: Lang.dt_info_empty,
            infoFiltered: Lang.dt_info_filtered,
            paginate: { previous: Lang.dt_paginate_previous, next: Lang.dt_paginate_next }
        },
        drawCallback: function () {
            var api = this.api();
            var start = api.page.info().start;
            api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = start + i + 1;
            });
        }
    });

    // User info modal
    $(document).on('click', '.btn-jn-user-info', function () {
        var $btn = $(this);
        $('#jnModalUserName').text($btn.data('user-name') || '—');
        $('#jnModalUserEmail').text($btn.data('user-email') || '—');
        $('#jnModalUserUpdatedAt').text($btn.data('user-updated-at') || '—');
        var isBlocked = $btn.data('is-blocked') == '1';
        $('#jnBtnToggleBlock').data('user-id', $btn.data('user-id')).data('is-blocked', isBlocked ? '1' : '0');
        $('#jnBlockIcon').attr('class', isBlocked ? 'bi bi-person-check me-1' : 'bi bi-person-slash me-1');
        $('#jnBlockLabel').text(isBlocked ? '{{ __("common.unblock_user") }}' : '{{ __("common.block_user") }}');
        $('#jnUserInfoModal').modal('show');
    });

    // Toggle block user
    $('#jnBtnToggleBlock').on('click', function () {
        var $btn = $(this);
        var userId = $btn.data('user-id');
        $.post('{{ route("users.toggle-block", ":uid") }}'.replace(':uid', userId), { _token: csrfToken }, function (res) {
            if (res.success) {
                toastr.success(res.isBlocked ? '{{ __("common.user_blocked") }}' : '{{ __("common.user_unblocked") }}');
                $('#jnUserInfoModal').modal('hide');
                setTimeout(function () { location.reload(); }, 800);
            }
        });
    });

    // Read more modal
    $(document).on('click', '.btn-jn-read-more', function () {
        $('#jnReadMoreUser').text($(this).data('user'));
        $('#jnReadMoreDate').text($(this).data('date'));
        $('#jnReadMoreMessage').text($(this).data('message'));
        $('#jnReadMoreModal').modal('show');
    });

    // Delete comment
    $(document).on('click', '.btn-jn-delete-comment', function () {
        var commentId = $(this).data('comment-id');
        var lessonId  = $(this).data('lesson-id');
        Swal.fire({
            title: Lang.are_you_sure,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_delete
        }).then(function (result) {
            if (result.isConfirmed) {
                $('#jnOverlayLoader').addClass('active');
                $.ajax({
                    url: '/jornadas/' + jornadaId + '/lessons/' + lessonId + '/comments/' + commentId,
                    method: 'POST',
                    data: { _token: csrfToken, _method: 'DELETE' },
                    success: function (res) {
                        $('#jnOverlayLoader').removeClass('active');
                        if (res.success) {
                            table.row($('#jn-comment-row-' + commentId)).remove().draw(false);
                            toastr.success('{{ __("common.deleted_success") }}');
                        } else {
                            toastr.error('{{ __("common.error_occurred") }}');
                        }
                    },
                    error: function () {
                        $('#jnOverlayLoader').removeClass('active');
                        toastr.error('{{ __("common.error_occurred") }}');
                    }
                });
            }
        });
    });

    function updateJnUserButtons(userId, nowBlocked) {
        // Update all block buttons for this user
        $('.btn-jn-block-user[data-user-id="' + userId + '"]').each(function () {
            $(this)
                .data('is-blocked', nowBlocked ? '1' : '0')
                .attr('data-is-blocked', nowBlocked ? '1' : '0')
                .attr('title', nowBlocked ? '{{ __("common.unblock_user") }}' : '{{ __("common.block_user") }}')
                .toggleClass('active-blocked', nowBlocked)
                .find('i').attr('class', 'bi ' + (nowBlocked ? 'bi-person-lock' : 'bi-person-slash'));
        });
        // Update blocked badge under username in table
        $('.btn-jn-user-info[data-user-id="' + userId + '"]').each(function () {
            $(this).data('is-blocked', nowBlocked ? '1' : '0').attr('data-is-blocked', nowBlocked ? '1' : '0');
            var $cell = $(this).closest('td');
            $cell.find('.dl-blocked-badge').remove();
            if (nowBlocked) {
                $(this).after('<br><span class="dl-status-badge dl-status-cancelled dl-blocked-badge" style="font-size:10px;padding:1px 6px;margin-top:3px;display:inline-block;">{{ __("common.blocked") }}</span>');
            }
        });
    }

    // Block user buttons in table rows
    $(document).on('click', '.btn-jn-block-user', function () {
        var $btn    = $(this);
        var userId  = $btn.data('user-id');
        var blocked = $btn.data('is-blocked') == '1';
        Swal.fire({
            title: (blocked ? '{{ __("common.unblock_user") }}' : '{{ __("common.block_user") }}') + '?',
            text: blocked ? '{{ __("common.unblock_user_confirm") }}' : '{{ __("common.block_user_confirm") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: blocked ? '#c6a55a' : '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '{{ __("common.yes_confirm") }}',
            cancelButtonText: '{{ __("common.cancel") }}'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $('#jnOverlayLoader').addClass('active');
            $.post('{{ route("users.toggle-block", ":uid") }}'.replace(':uid', userId), { _token: csrfToken }, function (res) {
                $('#jnOverlayLoader').removeClass('active');
                if (res.success) {
                    updateJnUserButtons(userId, res.isBlocked);
                    toastr.success(res.isBlocked ? '{{ __("common.user_blocked") }}' : '{{ __("common.user_unblocked") }}');
                }
            }).fail(function () {
                $('#jnOverlayLoader').removeClass('active');
                toastr.error('{{ __("common.something_wrong") }}');
            });
        });
    });
});
</script>
@endsection
