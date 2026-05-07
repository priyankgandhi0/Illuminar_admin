@extends('layouts.app')
@section('title', __('common.comments') . ' — ' . $id)

@section('vendorStyles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ __('common.comments') }} — {{ $id }}</h3></div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('daily-lights.index') }}">{{ __('common.daily_lights') }}</a></li>
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
                        <a href="{{ route('daily-lights.index') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="jn-table-wrap">
                        <table class="table table-bordered" id="commentsTable">
                            <thead>
                                <tr>
                                    <th width="50">{{ __('common.hash') }}</th>
                                    <th width="160">{{ __('common.user') }}</th>
                                    <th>{{ __('common.message') }}</th>
                                    <th width="160">{{ __('common.date') }}</th>
                                    <th width="100">{{ __('common.subscriber') }}</th>
                                    <th width="80">{{ __('common.likes') }}</th>
                                    <th width="80">{{ __('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comments as $comment)
                                @php $msgFull = $comment['message']; $msgShort = mb_strlen($msgFull) > 120; @endphp
                                <tr id="comment-row-{{ $comment['id'] }}">
                                    <td></td>
                                    <td>
                                        <button type="button"
                                            class="btn btn-link p-0 text-start btn-user-info fw-semibold"
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
                                        {{-- Message text --}}
                                        @if($msgShort)
                                            {{ mb_substr($msgFull, 0, 120) }}…
                                            <button type="button"
                                                class="btn btn-link p-0 btn-read-more"
                                                data-message="{{ e($msgFull) }}"
                                                data-user="{{ $comment['userName'] ?: '—' }}"
                                                data-date="{{ $comment['createdAt'] ? \Carbon\Carbon::parse($comment['createdAt'])->setTimezone($comment['userTimezone'] ?: 'America/Sao_Paulo')->format('d/m/Y H:i') : '—' }}"
                                                style="font-size:12px;vertical-align:middle;">
                                                {{ __('js.read_more') }}
                                            </button>
                                        @else
                                            {{ $msgFull }}
                                        @endif

                                        {{-- Status badges --}}
                                        @if($comment['isHidden'] || $comment['isSpam'] || $comment['isProhibitedWord'] || $comment['reportCount'] > 0)
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            @if($comment['isHidden'])
                                                <span class="cm-badge cm-badge-hidden" style="font-size:10px;padding:1px 7px;"><i class="bi bi-eye-slash me-1"></i>{{ __('common.hidden') }}</span>
                                            @endif
                                            @if($comment['isSpam'])
                                                <span class="cm-badge cm-badge-spam" style="font-size:10px;padding:1px 7px;">{{ __('common.spam') }}</span>
                                            @endif
                                            @if($comment['isProhibitedWord'])
                                                <span class="cm-badge cm-badge-prohibited" style="font-size:10px;padding:1px 7px;"><i class="bi bi-shield-exclamation me-1"></i>{{ __('common.prohibited_word') }}</span>
                                            @endif
                                            @if($comment['reportCount'] > 0)
                                                <span class="cm-badge" style="font-size:10px;padding:1px 7px;background:#fff7ed;color:#ea580c;border:1px solid #fdba74;font-weight:600;"><i class="bi bi-flag-fill me-1"></i>{{ $comment['reportCount'] }} {{ __('common.reports') }}</span>
                                            @endif
                                        </div>
                                        @endif

                                        {{-- Replies toggle → opens modal --}}
                                        @if(count($comment['repliesList']) > 0)
                                        <div class="mt-2">
                                            <button type="button"
                                                class="btn-show-replies"
                                                data-comment-id="{{ $comment['id'] }}"
                                                data-comment-user="{{ $comment['userName'] ?: '—' }}">
                                                <i class="bi bi-chat-text me-1"></i>
                                                <span class="reply-count-{{ $comment['id'] }}">{{ count($comment['repliesList']) }}</span>
                                                {{ count($comment['repliesList']) === 1 ? __('common.reply') : __('common.replies') }}
                                            </button>
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
                                                class="dl-action-box dl-action-block btn-block-user {{ $comment['isBlocked'] ? 'active-blocked' : '' }}"
                                                data-user-id="{{ $comment['userId'] }}"
                                                data-is-blocked="{{ $comment['isBlocked'] ? '1' : '0' }}"
                                                title="{{ $comment['isBlocked'] ? __('common.unblock_user') : __('common.block_user') }}">
                                                <i class="bi {{ $comment['isBlocked'] ? 'bi-person-lock' : 'bi-person-slash' }}"></i>
                                            </button>
                                            <button type="button"
                                                class="dl-action-box dl-action-delete btn-delete-comment"
                                                data-id="{{ $comment['id'] }}"
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

{{-- ===== Hidden reply template containers (one per comment) ===== --}}
@foreach($comments as $comment)
    @if(count($comment['repliesList']) > 0)
    <div id="replies-tpl-{{ $comment['id'] }}" class="d-none">
        @foreach($comment['repliesList'] as $reply)
        @php $rMsg = $reply['message']; @endphp
        <div class="reply-modal-card" data-reply-id="{{ $reply['id'] }}">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="d-flex align-items-center gap-2" style="min-width:0;">
                    <button type="button"
                        class="btn-user-info reply-avatar-circle"
                        data-user-id="{{ $reply['userId'] }}"
                        data-user-name="{{ $reply['userName'] }}"
                        data-user-email="{{ $reply['userEmail'] }}"
                        data-user-updated-at="{{ $reply['userUpdatedAt'] }}"
                        data-user-timezone="{{ $reply['userTimezone'] }}"
                        data-is-blocked="{{ $reply['isBlocked'] ? '1' : '0' }}"
                        title="{{ __('common.user') }}"
                        style="cursor:pointer;border:none;padding:0;flex-shrink:0;">
                        {{ mb_strtoupper(mb_substr($reply['userName'] ?: '?', 0, 1)) }}
                    </button>
                    <div style="min-width:0;">
                        <button type="button"
                            class="btn btn-link p-0 text-start btn-user-info fw-semibold"
                            data-user-id="{{ $reply['userId'] }}"
                            data-user-name="{{ $reply['userName'] }}"
                            data-user-email="{{ $reply['userEmail'] }}"
                            data-user-updated-at="{{ $reply['userUpdatedAt'] }}"
                            data-user-timezone="{{ $reply['userTimezone'] }}"
                            data-is-blocked="{{ $reply['isBlocked'] ? '1' : '0' }}"
                            style="text-decoration:none;color:inherit;font-size:15px;line-height:1.2;">
                            {{ $reply['userName'] ?: '—' }}
                        </button>
                        @if($reply['isBlocked'])
                            <span class="dl-status-badge dl-status-cancelled dl-reply-blocked-badge" style="font-size:10px;padding:1px 6px;">{{ __('common.blocked') }}</span>
                        @endif
                        @php $rTz = $reply['userTimezone'] ?: 'America/Sao_Paulo'; @endphp
                        <div class="text-muted" style="font-size:13px;">
                            {{ $reply['createdAt'] ? \Carbon\Carbon::parse($reply['createdAt'])->setTimezone($rTz)->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <button type="button"
                        class="dl-action-box dl-action-block btn-block-user {{ $reply['isBlocked'] ? 'active-blocked' : '' }}"
                        data-user-id="{{ $reply['userId'] }}"
                        data-is-blocked="{{ $reply['isBlocked'] ? '1' : '0' }}"
                        title="{{ $reply['isBlocked'] ? __('common.unblock_user') : __('common.block_user') }}"
                        style="width:30px;height:30px;font-size:13px;">
                        <i class="bi {{ $reply['isBlocked'] ? 'bi-person-lock' : 'bi-person-slash' }}"></i>
                    </button>
                    <button type="button"
                        class="dl-action-box dl-action-delete btn-delete-reply"
                        data-comment-id="{{ $comment['id'] }}"
                        data-reply-id="{{ $reply['id'] }}"
                        title="{{ __('common.delete') }}"
                        style="width:30px;height:30px;font-size:13px;">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="mt-2" style="font-size:15px;word-break:break-word;padding-left:46px;color:#222;">
                @if(mb_strlen($rMsg) > 120)
                    {{ mb_substr($rMsg, 0, 120) }}…
                    <button type="button"
                        class="btn btn-link p-0 btn-read-more"
                        data-message="{{ e($rMsg) }}"
                        data-user="{{ $reply['userName'] ?: '—' }}"
                        data-date="{{ $reply['createdAt'] ? \Carbon\Carbon::parse($reply['createdAt'])->setTimezone($rTz)->format('d/m/Y H:i') : '—' }}"
                        style="font-size:13px;vertical-align:middle;">
                        {{ __('js.read_more') }}
                    </button>
                @else
                    {{ $rMsg }}
                @endif
            </div>
            {{-- Status badges --}}
            @if(($reply['isHidden'] ?? false) || ($reply['isSpam'] ?? false) || ($reply['isProhibitedWord'] ?? false) || ($reply['reportCount'] ?? 0) > 0 || ($reply['isReported'] ?? false))
            <div class="d-flex flex-wrap gap-1 mt-2" style="padding-left:46px;">
                @if($reply['isHidden'] ?? false)
                    <span class="cm-badge cm-badge-hidden" style="font-size:10px;padding:1px 7px;"><i class="bi bi-eye-slash me-1"></i>{{ __('common.hidden') }}</span>
                @endif
                @if($reply['isSpam'] ?? false)
                    <span class="cm-badge cm-badge-spam" style="font-size:10px;padding:1px 7px;">{{ __('common.spam') }}</span>
                @endif
                @if($reply['isProhibitedWord'] ?? false)
                    <span class="cm-badge cm-badge-prohibited" style="font-size:10px;padding:1px 7px;"><i class="bi bi-shield-exclamation me-1"></i>{{ __('common.prohibited_word') }}</span>
                @endif
                @if(($reply['reportCount'] ?? 0) > 0)
                    <span class="cm-badge" style="font-size:10px;padding:1px 7px;background:#fff7ed;color:#ea580c;border:1px solid #fdba74;font-weight:600;"><i class="bi bi-flag-fill me-1"></i>{{ $reply['reportCount'] }} {{ __('common.reports') }}</span>
                @elseif($reply['isReported'] ?? false)
                    <span class="cm-badge" style="font-size:10px;padding:1px 7px;background:#fff7ed;color:#ea580c;border:1px solid #fdba74;font-weight:600;"><i class="bi bi-flag-fill me-1"></i>{{ __('common.reported') }}</span>
                @endif
            </div>
            @endif
            <div class="d-flex align-items-center gap-3 mt-2" style="padding-left:46px;font-size:13px;color:#6b7280;">
                <span class="d-flex align-items-center gap-1">
                    <i class="bi bi-heart"></i>
                    <strong style="color:#333;">{{ $reply['likes'] }}</strong>
                </span>
                <span class="d-flex align-items-center gap-1">
                    <span class="text-muted">{{ __('common.subscriber') }}:</span>
                    @if($reply['isSubscribedUser'])
                        <span class="dl-status-badge dl-status-published" style="font-size:11px;padding:2px 8px;">{{ __('common.yes') }}</span>
                    @else
                        <span class="dl-status-badge dl-status-cancelled" style="font-size:11px;padding:2px 8px;">{{ __('common.no') }}</span>
                    @endif
                </span>
            </div>
        </div>
        @endforeach
    </div>
    @endif
@endforeach

{{-- ===== User Info Modal ===== --}}
<div class="modal fade" id="userInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
        <div class="modal-content" style="border-radius:12px;border:none;overflow:hidden;">
            <div class="usr-modal-header" style="padding:14px 18px;">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle" style="font-size:18px;"></i>
                        <span class="fw-bold" style="font-size:15px;">{{ __('common.user_info') }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body" style="padding:20px 20px 16px;">
                <div class="d-flex align-items-center gap-3">
                    {{-- Avatar --}}
                    <div style="flex-shrink:0;">
                        <div id="modalAvatarImg" style="display:none;width:64px;height:64px;border-radius:50%;overflow:hidden;border:2px solid #e9e1cc;">
                            <img src="" alt="" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                        <div id="modalAvatarPlaceholder" class="usr-modal-avatar-placeholder" style="width:64px;height:64px;font-size:26px;">
                            <i class="bi bi-person"></i>
                        </div>
                    </div>
                    {{-- Info --}}
                    <div style="min-width:0;flex:1;">
                        <div class="d-flex align-items-baseline gap-2 mb-1">
                            <span style="font-size:12px;color:#9ca3af;font-weight:600;white-space:nowrap;">{{ __('common.name') }}:</span>
                            <span class="fw-semibold" id="modalUserName" style="font-size:15px;color:#1e293b;word-break:break-word;">—</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:12px;color:#9ca3af;font-weight:600;white-space:nowrap;">{{ __('common.email') }}:</span>
                            <span id="modalUserEmail" style="font-size:14px;color:#374151;word-break:break-all;">—</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between" style="border-top:1px solid #f1f5f9;padding:10px 18px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-sm" id="modalBlockBtn">
                    <i class="bi" id="modalBlockIcon"></i>
                    <span id="modalBlockText"></span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ===== Replies Modal ===== --}}
<div class="modal fade" id="repliesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:680px;">
        <div class="modal-content" style="border-radius:12px;border:none;overflow:hidden;">
            <div class="usr-modal-header" style="padding:16px 24px;">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <div class="fw-bold" style="font-size:15px;" id="repliesModalTitle">{{ __('common.replies') }}</div>
                        <div style="font-size:12px;opacity:0.8;" id="repliesModalSubtitle"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body py-3 px-4" id="repliesModalBody" style="min-height:80px;">
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ===== Read More Modal ===== --}}
<div class="modal fade" id="readMoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:500px;">
        <div class="modal-content" style="border-radius:12px;border:none;overflow:hidden;">
            <div class="usr-modal-header" style="padding:16px 24px;">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <div class="fw-bold" style="font-size:15px;" id="rmUserName">—</div>
                        <div style="font-size:12px;opacity:0.8;" id="rmDate">—</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body py-3 px-4" style="max-height:320px;overflow-y:auto;">
                <p id="rmMessage" style="white-space:pre-wrap;word-break:break-word;margin:0;font-size:15px;"></p>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="dl-overlay-loader" id="dlOverlayLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text" id="dlLoaderText">{{ __('common.deleting') }}</div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {

    var deleteUrl      = '{{ route("daily-lights.comments.destroy", ["id" => $id, "commentId" => "COMMENT_ID"]) }}';
    var deleteReplyUrl = '{{ route("daily-lights.replies.destroy", ["id" => $id, "commentId" => "COMMENT_ID", "replyId" => "REPLY_ID"]) }}';
    var blockUrl       = '{{ route("users.toggle-block", "USER_ID") }}';
    var csrf           = '{{ csrf_token() }}';

    // ---- DataTable ----
    var commentsTable = $('#commentsTable').DataTable({
        order: [[3, 'desc']],
        columnDefs: [{ orderable: false, targets: [0, 1, 2, 4, 5, 6] }],
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
        drawCallback: function() {
            var api = this.api();
            var start = api.page.info().start;
            api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                cell.innerHTML = start + i + 1;
            });
        }
    });

    // ---- Show Replies Modal ----
    $(document).on('click', '.btn-show-replies', function() {
        var commentId   = $(this).data('comment-id');
        var commentUser = $(this).data('comment-user');
        var $tpl        = $('#replies-tpl-' + commentId);
        var count       = $tpl.children('.reply-modal-card').length;

        $('#repliesModalTitle').text(count + ' ' + (count === 1 ? Lang.reply : Lang.replies));
        $('#repliesModalSubtitle').text(commentUser);
        $('#repliesModalBody').html($tpl.html());

        new bootstrap.Modal(document.getElementById('repliesModal')).show();
    });

    // ---- Read More Modal ----
    $(document).on('click', '.btn-read-more', function() {
        $('#rmUserName').text($(this).data('user'));
        $('#rmDate').text($(this).data('date'));
        $('#rmMessage').text($(this).data('message'));
        new bootstrap.Modal(document.getElementById('readMoreModal')).show();
    });

    // ---- Open User Info Modal (from username click) ----
    $(document).on('click', '.btn-user-info', function() {
        var userId    = $(this).data('user-id');
        var userName  = $(this).data('user-name') || '—';
        var userEmail = $(this).data('user-email') || '—';
        var userPhoto = $(this).data('user-photo') || '';
        var blocked   = $(this).data('is-blocked') === 1 || $(this).data('is-blocked') === '1';

        $('#modalUserName').text(userName);
        $('#modalUserEmail').text(userEmail);

        // Avatar: show image if available, otherwise placeholder
        if (userPhoto) {
            $('#modalAvatarImg img').attr('src', userPhoto).attr('alt', userName);
            $('#modalAvatarImg').show();
            $('#modalAvatarPlaceholder').hide();
        } else {
            $('#modalAvatarImg').hide();
            $('#modalAvatarPlaceholder').show();
        }

        $('#modalBlockBtn').data('user-id', userId).data('is-blocked', blocked ? '1' : '0');
        updateModalBlockBtn(blocked);
        new bootstrap.Modal(document.getElementById('userInfoModal')).show();
    });

    function updateModalBlockBtn(blocked) {
        $('#modalBlockBtn')
            .removeClass('btn-danger btn-gold-theme btn-outline-secondary')
            .addClass(blocked ? 'btn-gold-theme' : 'btn-danger');
        $('#modalBlockIcon').attr('class', 'bi ' + (blocked ? 'bi-person-lock' : 'bi-person-slash'));
        $('#modalBlockText').text(blocked ? Lang.unblock_user : Lang.block_user);
    }

    // ---- Shared block AJAX helper ----
    function doBlockRequest(userId, blocked, onSuccess) {
        $('#dlOverlayLoader').addClass('active');
        $.ajax({
            url: blockUrl.replace('USER_ID', userId),
            method: 'POST',
            data: { _token: csrf },
            success: function(res) {
                $('#dlOverlayLoader').removeClass('active');
                if (res.success) {
                    updateAllUserButtons(userId, res.isBlocked);
                    if (typeof onSuccess === 'function') onSuccess(res.isBlocked);
                    Swal.fire({
                        icon: 'success',
                        title: Lang.success,
                        text: res.isBlocked ? Lang.user_blocked : Lang.user_unblocked,
                        confirmButtonColor: '#C8902E'
                    });
                } else {
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            },
            error: function() {
                $('#dlOverlayLoader').removeClass('active');
                Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
            }
        });
    }

    // ---- Block / Unblock (works for both table buttons and reply modal buttons) ----
    $(document).on('click', '.btn-block-user', function() {
        // Skip if this is the modal-footer button (handled separately)
        if ($(this).attr('id') === 'modalBlockBtn') return;

        var $btn    = $(this);
        var userId  = $btn.data('user-id');
        var blocked = $btn.data('is-blocked') === 1 || $btn.data('is-blocked') === '1';

        Swal.fire({
            title: (blocked ? Lang.unblock_user : Lang.block_user) + '?',
            text: blocked ? Lang.unblock_user_confirm : Lang.block_user_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: blocked ? '#c6a55a' : '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function(result) {
            if (!result.isConfirmed) return;
            doBlockRequest(userId, blocked, null);
        });
    });

    // ---- Block / Unblock from User Info Modal ----
    $('#modalBlockBtn').on('click', function() {
        var $btn    = $(this);
        var userId  = $btn.data('user-id');
        var blocked = $btn.data('is-blocked') === 1 || $btn.data('is-blocked') === '1';

        Swal.fire({
            title: (blocked ? Lang.unblock_user : Lang.block_user) + '?',
            text: blocked ? Lang.unblock_user_confirm : Lang.block_user_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: blocked ? '#c6a55a' : '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function(result) {
            if (!result.isConfirmed) return;
            doBlockRequest(userId, blocked, function(nowBlocked) {
                $btn.data('is-blocked', nowBlocked ? '1' : '0');
                updateModalBlockBtn(nowBlocked);
            });
        });
    });

    function updateAllUserButtons(userId, nowBlocked) {
        // Update all block buttons (table + reply modal + templates)
        $('.btn-block-user[data-user-id="' + userId + '"]').each(function() {
            $(this)
                .data('is-blocked', nowBlocked ? '1' : '0')
                .attr('data-is-blocked', nowBlocked ? '1' : '0')
                .attr('title', nowBlocked ? Lang.unblock_user : Lang.block_user)
                .toggleClass('active-blocked', nowBlocked)
                .find('i').attr('class', 'bi ' + (nowBlocked ? 'bi-person-lock' : 'bi-person-slash'));
        });
        // Update username buttons in table (only inside <td>)
        $('.btn-user-info[data-user-id="' + userId + '"]').each(function() {
            $(this).data('is-blocked', nowBlocked ? '1' : '0').attr('data-is-blocked', nowBlocked ? '1' : '0');
            var $cell = $(this).closest('td');
            if ($cell.length === 0) return; // skip reply modal buttons
            $cell.find('.dl-blocked-badge').remove();
            if (nowBlocked) {
                $(this).after('<br><span class="dl-status-badge dl-status-cancelled dl-blocked-badge" style="font-size:10px;padding:1px 6px;margin-top:3px;display:inline-block;">{{ __("common.blocked") }}</span>');
            }
        });
        // Update blocked badge inside reply cards (modal + templates)
        $('[data-reply-id] .btn-block-user[data-user-id="' + userId + '"]').closest('.reply-modal-card').each(function() {
            var $badge = $(this).find('.dl-reply-blocked-badge');
            if (nowBlocked && $badge.length === 0) {
                $(this).find('.btn-user-info.fw-semibold').after('<span class="dl-status-badge dl-status-cancelled dl-reply-blocked-badge" style="font-size:10px;padding:1px 6px;">{{ __("common.blocked") }}</span>');
            } else if (!nowBlocked) {
                $badge.remove();
            }
        });
    }

    // ---- Delete Comment ----
    $(document).on('click', '.btn-delete-comment', function() {
        var commentId = $(this).data('id');
        Swal.fire({
            title: Lang.confirm_delete,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_delete,
            cancelButtonText: Lang.cancel
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $('#dlOverlayLoader').addClass('active');
            $.ajax({
                url: deleteUrl.replace('COMMENT_ID', commentId),
                method: 'DELETE',
                data: { _token: csrf },
                success: function(res) {
                    $('#dlOverlayLoader').removeClass('active');
                    if (res.success) {
                        var $row = $('#comment-row-' + commentId);
                        $row.fadeOut(300, function() {
                            commentsTable.row($row).remove().draw(false);
                        });
                        $('#replies-tpl-' + commentId).remove();
                        Swal.fire({ icon: 'success', title: Lang.success, text: Lang.deleted_success, confirmButtonColor: '#C8902E' });
                    } else {
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                },
                error: function() {
                    $('#dlOverlayLoader').removeClass('active');
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            });
        });
    });

    // ---- Delete Reply (works in modal body via delegation) ----
    $(document).on('click', '.btn-delete-reply', function() {
        var $btn      = $(this);
        var commentId = $btn.data('comment-id');
        var replyId   = $btn.data('reply-id');

        Swal.fire({
            title: Lang.confirm_delete,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_delete,
            cancelButtonText: Lang.cancel
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $btn.prop('disabled', true);
            var url = deleteReplyUrl.replace('COMMENT_ID', commentId).replace('REPLY_ID', replyId);
            $.ajax({
                url: url,
                method: 'DELETE',
                data: { _token: csrf },
                success: function(res) {
                    $btn.prop('disabled', false);
                    if (res.success) {
                        // Remove from template
                        $('#replies-tpl-' + commentId + ' [data-reply-id="' + replyId + '"]').remove();
                        // Remove from open modal
                        $('#repliesModalBody [data-reply-id="' + replyId + '"]').fadeOut(250, function() { $(this).remove(); });
                        // Update counter in table
                        var remaining = $('#replies-tpl-' + commentId + ' .reply-modal-card').length;
                        if (remaining === 0) {
                            // Remove the replies button row from the table cell
                            $('.btn-show-replies[data-comment-id="' + commentId + '"]').closest('div.mt-2').remove();
                            // Close modal if empty
                            bootstrap.Modal.getInstance(document.getElementById('repliesModal'))?.hide();
                        } else {
                            $('.reply-count-' + commentId).text(remaining);
                            $('#repliesModalTitle').text(remaining + ' ' + (remaining === 1 ? Lang.reply : Lang.replies));
                        }
                        Swal.fire({ icon: 'success', title: Lang.success, text: Lang.deleted_success, confirmButtonColor: '#C8902E' });
                    } else {
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            });
        });
    });

});
</script>
@endsection
