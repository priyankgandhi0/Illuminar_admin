@extends('layouts.app')
@php
    $pageTitle = __('common.comment_moderation');
    $pageIcon  = 'bi-chat-square-text';
@endphp
@section('title', $pageTitle)

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">
                        <span id="pageTitleText">{{ $pageTitle }}</span>
                    </h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.home') }}</a></li>
                        <li class="breadcrumb-item active" id="breadcrumbActive">{{ $pageTitle }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            {{-- Tab Navigation (temporarily hidden) --}}
            {{-- <div class="cm-tabs-row">
                <a href="#" class="cm-tab" data-tab="all">
                    <i class="bi bi-chat-square-text"></i>
                    {{ __('common.comment_moderation') }}
                    <span class="cm-tab-count" id="tabCountAll">0</span>
                </a>
                <a href="#" class="cm-tab" data-tab="reported">
                    <i class="bi bi-flag"></i>
                    {{ __('common.reported_comments') }}
                    <span class="cm-tab-count cm-tab-count-red" id="tabCountReported">0</span>
                </a>
                <a href="#" class="cm-tab" data-tab="hidden">
                    <i class="bi bi-eye-slash"></i>
                    {{ __('common.hidden_comments') }}
                    <span class="cm-tab-count" id="tabCountHidden">0</span>
                </a>
            </div> --}}

            <div class="cm-content-wrapper">
            <div class="row g-0">

                {{-- LEFT: Card List --}}
                <div class="col-lg-7 cm-col-left">
                    <div class="cm-list-wrap">

                        {{-- Filter Area --}}
                        <div class="cm-filter-area">
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <select class="form-select form-select-sm" id="filterSource" style="max-width:200px;">
                                    <option value="">{{ __('common.all') }}</option>
                                    <option value="dl">{{ __('common.all_daily_lights') }}</option>
                                    <option value="jornada">{{ __('common.all_jornadas') }}</option>
                                </select>
                                <select class="form-select form-select-sm" id="filterReported" style="max-width:160px;">
                                    <option value="">{{ __('common.all_comments') }}</option>
                                    <option value="reported">{{ __('common.reported_only') }}</option>
                                    <option value="hidden">{{ __('common.hidden_only') }}</option>
                                </select>
                                <div class="ms-auto" style="min-width:200px;max-width:280px;flex:1;">
                                    <div class="cm-search-wrap">
                                        <i class="bi bi-search cm-search-icon"></i>
                                        <input type="text" id="cmSearch" class="cm-search-input"
                                            placeholder="{{ __('common.search_comments') }}">
                                        <button type="button" class="cm-search-clear d-none" id="cmSearchClear" title="Clear">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- List Header --}}
                        <div class="cm-list-header">
                            <span>
                                <strong id="cmVisibleCount">0</strong>
                                {{ __('common.comments') }}
                            </span>
                            <span id="cmTabBadge" class="cm-reported-badge d-none"></span>
                        </div>

                        {{-- Card List --}}
                        <div class="cm-list-panel" id="cmListPanel">
                            @forelse($comments as $comment)
                            @php
                                $cTz     = $comment['userTimezone'] ?: 'America/Sao_Paulo';
                                $dateStr = $comment['createdAt']
                                    ? \Carbon\Carbon::parse($comment['createdAt'])->setTimezone($cTz)->format('d/m/Y H:i')
                                    : '—';
                                $msgFull = $comment['message'];
                                $msgPrev = mb_strlen($msgFull) > 100 ? mb_substr($msgFull, 0, 100) . '…' : $msgFull;
                                $initial = mb_strtoupper(mb_substr($comment['userName'] ?: '?', 0, 1));
                                $hasProhibitedReply = collect($comment['repliesList'])->contains(fn($r) => !empty($r['isProhibitedWord']));
                            @endphp
                            <div class="cm-card"
                                 id="cm-card-{{ $comment['id'] }}"
                                 data-comment-id="{{ $comment['id'] }}"
                                 data-source-type="{{ $comment['sourceType'] ?? 'dl' }}"
                                 data-jornada-id="{{ $comment['jornadaId'] ?? '' }}"
                                 data-lesson-id="{{ $comment['lessonId'] ?? '' }}"
                                 data-dl-id="{{ $comment['dlId'] }}"
                                 data-dl-date="{{ $comment['dlDate'] }}"
                                 data-dl-title="{{ $comment['dlTitle'] }}"
                                 data-user-id="{{ $comment['userId'] }}"
                                 data-user-name="{{ $comment['userName'] }}"
                                 data-user-email="{{ $comment['userEmail'] }}"
                                 data-user-timezone="{{ $comment['userTimezone'] }}"
                                 data-is-blocked="{{ $comment['isBlocked'] ? '1' : '0' }}"
                                 data-message="{{ e($msgFull) }}"
                                 data-created-at="{{ $comment['createdAt'] }}"
                                 data-is-reported="{{ $comment['isReported'] ? '1' : '0' }}"
                                 data-report-count="{{ $comment['reportCount'] }}"
                                 data-auto-hidden="{{ !empty($comment['autoHidden']) ? '1' : '0' }}"
                                 data-is-hidden="{{ $comment['isHidden'] ? '1' : '0' }}"
                                 data-is-spam="{{ $comment['isSpam'] ? '1' : '0' }}"
                                 data-is-prohibited="{{ $comment['isProhibitedWord'] ? '1' : '0' }}"
                                 data-has-prohibited-reply="{{ $hasProhibitedReply ? '1' : '0' }}"
                                 data-has-reported-reply="{{ ($comment['hasReportedReply'] ?? false) ? '1' : '0' }}"
                                 data-is-subscribed="{{ $comment['isSubscribedUser'] ? '1' : '0' }}"
                                 data-user-photo="{{ $comment['userPhotoUrl'] }}"
                                 data-likes="{{ $comment['likes'] }}"
                                 data-replies-count="{{ $comment['repliesCount'] }}"
                                 data-replies-json="{{ json_encode($comment['repliesList']) }}"
                                 data-date-display="{{ $dateStr }}"
                                 data-search-text="{{ strtolower($comment['userName'] . ' ' . $comment['message']) }}">

                                <div class="d-flex gap-3">
                                    {{-- Avatar --}}
                                    <div class="cm-card-avatar" data-initial="{{ $initial }}">
                                        @if($comment['userPhotoUrl'])
                                            <img src="{{ $comment['userPhotoUrl'] }}" alt="{{ $comment['userName'] }}"
                                                 style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                                                 onerror="var p=this.parentElement;p.removeChild(this);p.textContent=p.dataset.initial;">
                                        @else
                                            {{ $initial }}
                                        @endif
                                    </div>

                                    {{-- Content --}}
                                    <div style="flex:1;min-width:0;">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <span class="cm-card-username">{{ $comment['userName'] ?: '—' }}</span>
                                            @if($comment['isBlocked'])
                                                <span class="cm-badge cm-badge-blocked">{{ __('common.blocked') }}</span>
                                            @endif
                                            @if($comment['isSubscribedUser'])
                                                <span class="cm-badge cm-badge-subscriber">{{ __('common.subscriber') }}</span>
                                            @endif
                                            @if($comment['isHidden'])
                                                <span class="cm-badge cm-badge-hidden"><i class="bi bi-eye-slash me-1"></i>{{ __('common.hidden') }}</span>
                                            @endif
                                            @if($comment['isSpam'])
                                                <span class="cm-badge cm-badge-spam">{{ __('common.spam') }}</span>
                                            @endif
                                            @if($comment['isProhibitedWord'])
                                                <span class="cm-badge cm-badge-prohibited"><i class="bi bi-shield-exclamation me-1"></i>{{ __('common.prohibited_word') }}</span>
                                            @endif
                                            @if($comment['reportCount'] > 0)
                                                <span class="cm-report-count-badge" style="background:#fff7ed;color:#ea580c;border-color:#fdba74;"><i class="bi bi-flag-fill me-1"></i>{{ $comment['reportCount'] }} {{ __('common.reports') }}</span>
                                            @endif
                                            {{-- Reply-reason badges: shown by JS when card appears due to reply flag --}}
                                            <span class="cm-badge cm-badge-reported cm-reply-reported-badge d-none" style="font-size:10px;"><i class="bi bi-reply me-1"></i>{{ __('common.reply_reported') }}</span>
                                            <span class="cm-badge cm-badge-prohibited cm-reply-blocked-badge d-none" style="font-size:10px;"><i class="bi bi-reply me-1"></i>{{ __('common.reply_blocked') }}</span>
                                            <span class="cm-badge cm-badge-prohibited cm-reply-proh-badge d-none" style="font-size:10px;"><i class="bi bi-shield-exclamation me-1"></i>{{ __('common.prohibited_word') }}</span>
                                            <span class="cm-report-count-badge cm-reply-rep-count-badge d-none" style="background:#fff7ed;color:#ea580c;border-color:#fdba74;"></span>
                                        </div>
                                        <div class="cm-card-meta">
                                            <i class="bi {{ ($comment['sourceType'] ?? 'dl') === 'jornada' ? 'bi-journals' : 'bi-brightness-high' }}" style="color:var(--tp-primary);font-size:11px;"></i>
                                            @if($comment['dlTitle']){{ mb_substr($comment['dlTitle'], 0, 32) }} &nbsp;·&nbsp; @endif{{ $dateStr }}
                                        </div>
                                        <div class="cm-card-text">{{ $msgPrev }}</div>
                                    </div>

                                </div>

                            </div>
                            @empty
                            <div class="cm-no-results">
                                <i class="bi bi-chat-square" style="font-size:32px;opacity:0.3;display:block;margin-bottom:8px;"></i>
                                {{ __('common.no_comments') }}
                            </div>
                            @endforelse

                            <div id="cmNoResults" class="cm-no-results d-none">
                                <i class="bi bi-search" style="font-size:28px;opacity:0.3;display:block;margin-bottom:8px;"></i>
                                {{ __('common.no_comments') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Detail Panel --}}
                <div class="col-lg-5 cm-col-right">
                    <div class="cm-detail-panel">

                        {{-- Header --}}
                        <div class="cm-dp-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi {{ $pageIcon }}"></i>
                                <span class="fw-semibold" style="font-size:14px;">{{ __('common.comment_details') }}</span>
                            </div>
                        </div>

                        {{-- Body --}}
                        <div class="cm-dp-body">

                            {{-- Empty state --}}
                            <div class="cm-dp-empty" id="cmDpEmpty">
                                <i class="bi bi-chat-square-dots"></i>
                                <span>{{ __('common.select_a_comment') }}</span>
                            </div>

                            {{-- Content --}}
                            <div id="cmDpContent" class="d-none">

                                {{-- Back to parent comment (shown when viewing a reply) --}}
                                <div id="dpBackRow" class="d-none" style="margin-bottom:10px;">
                                    <button type="button" id="dpBackBtn" style="display:inline-flex;align-items:center;gap:4px;background:none;border:none;padding:0;font-size:12px;color:var(--tp-primary);font-weight:600;cursor:pointer;line-height:1;">
                                        <i class="bi bi-arrow-left-short" style="font-size:16px;"></i>
                                        {{ __('common.back_to_comment') }}
                                    </button>
                                </div>

                                <div class="cm-dp-section-label">{{ __('common.source') }}</div>
                                <div class="cm-dp-source">
                                    <i class="bi bi-brightness-high" id="dpSourceIcon" style="color:var(--tp-primary);"></i>
                                    <span class="fw-semibold" id="dpSourceTitle"></span>
                                </div>

                                <div class="cm-dp-section-label">{{ __('common.message') }}</div>
                                <div class="cm-dp-message-box">
                                    <div class="cm-dp-message" id="dpMessage"></div>
                                </div>

                                <div class="cm-dp-section-label">{{ __('common.user_info') }}</div>
                                <div class="cm-dp-user-card">
                                    <div class="cm-dp-avatar-circle" id="dpAvatarCircle">?</div>
                                    <div style="min-width:0;flex:1;">
                                        <div class="fw-semibold" id="dpUserName" style="font-size:14px;"></div>
                                        <div style="font-size:12px;color:var(--tp-text-secondary);word-break:break-all;" id="dpUserEmail"></div>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <span id="dpBlockedBadge" class="cm-badge cm-badge-blocked d-none">{{ __('common.blocked') }}</span>
                                            <span id="dpSubscriberBadge" class="cm-badge cm-badge-subscriber d-none">{{ __('common.subscriber') }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Comment status badges --}}
                                <div class="d-flex flex-wrap gap-1 mt-1 mb-1">
                                    <span id="dpReplyBadge" class="cm-badge d-none" style="background:#eff6ff;color:#2563eb;border:1px solid #93c5fd;font-weight:600;">
                                        <i class="bi bi-reply-fill me-1"></i>{{ __('common.reply') }}
                                    </span>
                                    <span id="dpReportedBadge" class="cm-badge cm-badge-reported d-none">
                                        <i class="bi bi-flag me-1"></i>{{ __('common.reported') }}
                                    </span>
                                    <span id="dpHiddenBadge" class="cm-badge cm-badge-hidden d-none">
                                        <i class="bi bi-eye-slash me-1"></i>{{ __('common.hidden') }}
                                    </span>
                                    <span id="dpSpamBadge" class="cm-badge cm-badge-spam d-none">{{ __('common.spam') }}</span>
                                    <span id="dpProhibitedBadge" class="cm-badge cm-badge-prohibited d-none">
                                        <i class="bi bi-shield-exclamation me-1"></i>{{ __('common.prohibited_word') }}
                                    </span>
                                    <span id="dpReportCountBadge" class="cm-badge d-none" style="background:#fff7ed;color:#ea580c;border:1px solid #fdba74;font-weight:600;"></span>
                                </div>

                                <div class="cm-dp-meta-row">
                                    <span><i class="bi bi-clock me-1"></i><span id="dpDate">—</span></span>
                                    <span><i class="bi bi-heart me-1" style="color:#ef4444;"></i><span id="dpLikes">0</span></span>
                                    <span><i class="bi bi-chat me-1"></i><span id="dpReplies">0</span></span>
                                </div>

                                {{-- Replies --}}
                                <div id="dpRepliesSection" class="d-none">
                                    <div class="cm-dp-section-label">{{ __('common.replies') }} <span id="dpRepliesCount" class="cm-tab-count ms-1">0</span></div>
                                    <div id="dpRepliesList" class="cm-replies-list"></div>
                                </div>


                            </div>
                        </div>

                        {{-- Action Buttons: Approve → Hide → Block → Delete --}}
                        <div class="cm-dp-action-row d-none" id="cmDpActionRow">
                            <button type="button" class="cm-dp-action-btn btn-dp-approve d-none" id="dpClearReportBtn"
                                title="{{ __('common.approve_comment') }}">
                                <i class="bi bi-check-circle-fill"></i>
                            </button>
                            <button type="button" class="cm-dp-action-btn btn-dp-approve-prohibited d-none" id="dpApproveProhibitedBtn"
                                title="{{ __('common.approve_prohibited') }}">
                                <i class="bi bi-check-circle-fill"></i>
                            </button>
                            <button type="button" class="cm-dp-action-btn btn-dp-hide" id="dpHideBtn"
                                title="{{ __('common.hide_comment') }}">
                                <i class="bi bi-eye-slash" id="dpHideIcon"></i>
                            </button>
                            <button type="button" class="cm-dp-action-btn btn-dp-spam" id="dpSpamBtn"
                                title="{{ __('common.mark_as_spam') }}">
                                <i class="bi bi-ban"></i>
                            </button>
                            <button type="button" class="cm-dp-action-btn btn-dp-unspam d-none" id="dpUnspamBtn"
                                title="{{ __('common.unspam_comment') }}">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            <button type="button" class="cm-dp-action-btn btn-dp-block" id="dpBlockBtn"
                                title="{{ __('common.block_user') }}">
                                <i class="bi bi-person-slash" id="dpBlockIcon"></i>
                            </button>
                            <button type="button" class="cm-dp-action-btn btn-dp-delete" id="dpDeleteBtn"
                                title="{{ __('common.delete') }}">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>

                    </div>
                </div>

            </div>
            </div>{{-- /.cm-content-wrapper --}}
        </div>
    </div>
</main>

<div class="dl-overlay-loader" id="dlOverlayLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text" id="dlLoaderText">{{ __('common.deleting') }}</div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {

    var blockUrl       = '{{ route("users.toggle-block", "USER_ID") }}';
    var deleteUrl      = '{{ route("daily-lights.comments.destroy",  ["id" => "DL_ID", "commentId" => "COMMENT_ID"]) }}';
    var clearReportUrl = '{{ route("daily-lights.comments.clear-report", ["id" => "DL_ID", "commentId" => "COMMENT_ID"]) }}';
    var hideUrl        = '{{ route("daily-lights.comments.hide",    ["id" => "DL_ID", "commentId" => "COMMENT_ID"]) }}';
    var approveUrl     = '{{ route("daily-lights.comments.approve", ["id" => "DL_ID", "commentId" => "COMMENT_ID"]) }}';
    var spamUrl        = '{{ route("daily-lights.comments.spam",    ["id" => "DL_ID", "commentId" => "COMMENT_ID"]) }}';
    var unspamUrl      = '{{ route("daily-lights.comments.unspam",  ["id" => "DL_ID", "commentId" => "COMMENT_ID"]) }}';
    var deleteReplyUrl               = '{{ route("daily-lights.replies.destroy",  ["id" => "DL_ID", "commentId" => "COMMENT_ID", "replyId" => "REPLY_ID"]) }}';
    var approveProhibitedUrl         = '{{ route("daily-lights.comments.approve-prohibited", ["id" => "DL_ID", "commentId" => "COMMENT_ID"]) }}';
    var approveReplyProhibitedUrl    = '{{ route("daily-lights.replies.approve-prohibited",  ["id" => "DL_ID", "commentId" => "COMMENT_ID", "replyId" => "REPLY_ID"]) }}';
    var hideReplyUrl                 = '{{ route("daily-lights.replies.hide",    ["id" => "DL_ID", "commentId" => "COMMENT_ID", "replyId" => "REPLY_ID"]) }}';
    var approveReplyUrl              = '{{ route("daily-lights.replies.approve", ["id" => "DL_ID", "commentId" => "COMMENT_ID", "replyId" => "REPLY_ID"]) }}';
    var clearReplyReportUrl          = '{{ route("daily-lights.replies.clear-report", ["id" => "DL_ID", "commentId" => "COMMENT_ID", "replyId" => "REPLY_ID"]) }}';
    // Jornada comment URLs
    var jnDeleteUrl  = '{{ route("jornadas.comments.destroy",  ["id" => "JORNADA_ID", "lessonId" => "LESSON_ID", "commentId" => "COMMENT_ID"]) }}';
    var jnHideUrl    = '{{ route("jornadas.comments.hide",     ["id" => "JORNADA_ID", "lessonId" => "LESSON_ID", "commentId" => "COMMENT_ID"]) }}';
    var jnApproveUrl = '{{ route("jornadas.comments.approve",  ["id" => "JORNADA_ID", "lessonId" => "LESSON_ID", "commentId" => "COMMENT_ID"]) }}';
    var csrf           = '{{ csrf_token() }}';

    var Lang = {
        success:              '{{ __("common.success") }}',
        something_wrong:      '{{ __("common.something_wrong") }}',
        block_user:           '{{ __("common.block_user") }}',
        unblock_user:         '{{ __("common.unblock_user") }}',
        block_user_confirm:   '{{ __("common.block_user_confirm") }}',
        unblock_user_confirm: '{{ __("common.unblock_user_confirm") }}',
        yes_confirm:          '{{ __("common.yes_confirm") }}',
        yes_delete:           '{{ __("common.yes_delete") }}',
        cancel:               '{{ __("common.cancel") }}',
        confirm_delete:       '{{ __("common.confirm_delete") }}',
        deleted_success:      '{{ __("common.deleted_success") }}',
        user_blocked:         '{{ __("common.user_blocked") }}',
        user_unblocked:       '{{ __("common.user_unblocked") }}',
        hide_comment:         '{{ __("common.hide_comment") }}',
        hide_confirm:         '{{ __("common.hide_confirm") }}',
        unhide_comment:       '{{ __("common.unhide_comment") }}',
        unhide_comment_title: '{{ __("common.unhide_comment_title") }}',
        unhide_confirm:       '{{ __("common.unhide_confirm") }}',
        approve_comment:      '{{ __("common.approve_comment") }}',
        mark_as_spam:         '{{ __("common.mark_as_spam") }}',
        mark_as_spam_confirm: '{{ __("common.mark_as_spam_confirm") }}',
        unspam_comment:       '{{ __("common.unspam_comment") }}',
        unspam_confirm:       '{{ __("common.unspam_confirm") }}',
        comment_unspammed:    '{{ __("common.comment_unspammed") }}',
        comment_hidden:       '{{ __("common.comment_hidden") }}',
        comment_approved:     '{{ __("common.comment_approved") }}',
        comment_spam:         '{{ __("common.comment_spam") }}',
        report_cleared:       '{{ __("common.report_cleared") }}',
        reports:              '{{ __("common.reports") }}',
        reported:             '{{ __("common.reported") }}',
        prohibited_word:      '{{ __("common.prohibited_word") }}',
        processing:                  '{{ __("common.processing") }}',
        loading:                     '{{ __("common.loading") }}',
        approve_prohibited:          '{{ __("common.approve_prohibited") }}',
        approve_prohibited_confirm:  '{{ __("common.approve_prohibited_confirm") }}',
        prohibited_approved:         '{{ __("common.prohibited_approved") }}',
        reported_by_x_users:         '{{ __("common.reported_by_x_users") }}',
        auto_hidden_by_reports:      '{{ __("common.auto_hidden_by_reports") }}',
        approve_reported_title:      '{{ __("common.approve_reported_title") }}',
        approve_reported_confirm:    '{{ __("common.approve_reported_confirm") }}',
        approve_reported_also_unhide:'{{ __("common.approve_reported_also_unhide") }}',
        hide_reported_title:         '{{ __("common.hide_reported_title") }}',
        hide_reported_confirm:       '{{ __("common.hide_reported_confirm") }}',
        delete_reported_title:       '{{ __("common.delete_reported_title") }}',
        delete_reported_confirm:     '{{ __("common.delete_reported_confirm") }}',
        block_reported_title:        '{{ __("common.block_reported_title") }}',
        block_reported_confirm:      '{{ __("common.block_reported_confirm") }}',
        unblock_reported_title:      '{{ __("common.unblock_reported_title") }}',
        unblock_reported_confirm:    '{{ __("common.unblock_reported_confirm") }}',
        comment_approved_visible:    '{{ __("common.comment_approved_visible") }}',
        report_cleared_success:      '{{ __("common.report_cleared_success") }}',
    };

    function showLoader(text) {
        $('#dlLoaderText').text(text || Lang.processing);
        $('#dlOverlayLoader').addClass('active');
    }

    // ---- Tab state ----
    var currentTab = '{{ $activeTab }}';

    function updateTabCounts() {
        $('#tabCountAll').text($('.cm-card').length);
        $('#tabCountReported').text($('.cm-card').filter(function () { return String($(this).data('is-reported')) === '1'; }).length);
        $('#tabCountHidden').text($('.cm-card').filter(function () { return String($(this).data('is-prohibited')) === '1'; }).length);
    }

    var tabTitles = {
        all:      { text: '{{ __("common.comment_moderation") }}', icon: 'bi-chat-square-text' },
        reported: { text: '{{ __("common.reported_comments") }}',  icon: 'bi-flag' },
        hidden:   { text: '{{ __("common.hidden_comments") }}',    icon: 'bi-eye-slash' },
    };

    function switchTab(tab) {
        currentTab = tab;
        $('.cm-tab').removeClass('cm-tab-active');
        $('.cm-tab[data-tab="' + tab + '"]').addClass('cm-tab-active');

        // Update page title + breadcrumb
        var t = tabTitles[tab] || tabTitles.all;
        $('#pageTitleText').text(t.text);
        $('#breadcrumbActive').text(t.text);

        var $badge = $('#cmTabBadge');
        if (tab === 'reported') {
            $badge.html('<i class="bi bi-flag-fill"></i> {{ __("common.reported_only") }}')
                  .removeClass('d-none').css({background:'#fef2f2', color:'#dc2626', 'border-color':'#fca5a5'});
            $('#filterReported').val('').hide();
        } else if (tab === 'hidden') {
            $badge.html('<i class="bi bi-eye-slash-fill"></i> {{ __("common.hidden_only") }}')
                  .removeClass('d-none').css({background:'#fdf6e3', color:'var(--tp-primary)', 'border-color':'var(--tp-primary)'});
            $('#filterReported').val('').hide();
        } else {
            $badge.addClass('d-none').removeAttr('style');
            $('#filterReported').show();
        }
        filterCards();
        var $first = $('.cm-card:visible').first();
        if ($first.length) {
            $first.trigger('click');
        } else {
            $('#cmDpContent').addClass('d-none').data('active-comment', null);
            $('#cmDpEmpty').removeClass('d-none');
            $('#cmDpActionRow').addClass('d-none');
        }
    }

    // ---- Success helper (centered modal, auto-dismisses, no OK button, no reload) ----
    function showSuccess(text) {
        Swal.fire({
            icon: 'success',
            title: Lang.success,
            text: text,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
        });
    }

    // ---- Synthetic reply cards (for Blocked tab) ----

    // ---- Filter & Search ----
    function filterCards() {
        var search   = $('#cmSearch').val().toLowerCase().trim();
        var source   = $('#filterSource').val();
        var reported = $('#filterReported').val();
        var visible  = 0;

        $('.cm-card').each(function () {
            var $c   = $(this);
            var show = true;
            if (currentTab === 'reported' && $c.attr('data-is-reported') !== '1'
                                        && $c.attr('data-has-reported-reply') !== '1') show = false;
            if (currentTab === 'hidden'   && $c.attr('data-is-prohibited') !== '1') show = false;
            if (source              && $c.attr('data-source-type') !== source) show = false;
            if (reported === 'reported' && $c.attr('data-is-reported') !== '1'
                                       && $c.attr('data-has-reported-reply') !== '1') show = false;
            if (reported === 'hidden'   && $c.attr('data-is-prohibited') !== '1'
                                       && $c.attr('data-has-prohibited-reply') !== '1') show = false;
            if (search   && ($c.attr('data-search-text') || '').indexOf(search) === -1) show = false;
            $c.toggle(show);
            if (show) {
                visible++;
                // Show reply-reason badge when card is visible only because of a reply flag
                var mainReported   = $c.attr('data-is-reported')   === '1';
                var mainProhibited = $c.attr('data-is-prohibited')  === '1';
                var hasRepReply    = $c.attr('data-has-reported-reply')   === '1';
                var hasProhReply   = $c.attr('data-has-prohibited-reply') === '1';
                var inReportedView = (currentTab === 'reported' || reported === 'reported');
                var inHiddenView   = (currentTab === 'hidden'   || reported === 'hidden');
                var showRepBadge   = inReportedView && !mainReported && hasRepReply;
                var showProhBadge  = inHiddenView   && !mainProhibited && hasProhReply;
                $c.find('.cm-reply-reported-badge').toggleClass('d-none', !showRepBadge);
                $c.find('.cm-reply-blocked-badge').toggleClass('d-none',  !showProhBadge);
                $c.find('.cm-reply-proh-badge').toggleClass('d-none', !showProhBadge);
                if (!showRepBadge) { $c.find('.cm-reply-rep-count-badge').addClass('d-none').text(''); }

                // When card shows only because of a reply, display that reply's message in the card
                // and store its ID so the click handler opens it directly in the detail panel
                if (showRepBadge || showProhBadge) {
                    var rJson2 = $c.attr('data-replies-json') || '[]';
                    var rList2 = [];
                    try { rList2 = JSON.parse(rJson2); } catch(e) {}
                    var targetReply = null;
                    if (showRepBadge) {
                        for (var ri = 0; ri < rList2.length; ri++) {
                            if (rList2[ri].isReported || (rList2[ri].reportCount > 0)) { targetReply = rList2[ri]; break; }
                        }
                    } else {
                        for (var ri = 0; ri < rList2.length; ri++) {
                            if (rList2[ri].isProhibitedWord) { targetReply = rList2[ri]; break; }
                        }
                    }
                    if (targetReply) {
                        $c.attr('data-auto-open-reply', targetReply.id);
                        var rMsg = targetReply.message || '';
                        var rMsgPrev = rMsg.length > 100 ? rMsg.substring(0, 100) + '…' : rMsg;
                        $c.find('.cm-card-text').text(rMsgPrev);
                        // Show reply author name on left card (store original to restore later)
                        if (!$c.attr('data-orig-username')) {
                            $c.attr('data-orig-username', $c.find('.cm-card-username').text().trim());
                        }
                        if (targetReply.userName) {
                            $c.find('.cm-card-username').text(targetReply.userName);
                        }
                        // Show reply report count badge on left card
                        var rRepCnt = targetReply.reportCount || 0;
                        if (showRepBadge && rRepCnt > 0) {
                            $c.find('.cm-reply-rep-count-badge').removeClass('d-none').html('<i class="bi bi-flag-fill me-1"></i>' + rRepCnt + ' ' + Lang.reports);
                        } else {
                            $c.find('.cm-reply-rep-count-badge').addClass('d-none').text('');
                        }
                        // Update avatar to reply author's photo/initial
                        var $av = $c.find('.cm-card-avatar');
                        if (!$c.attr('data-orig-avatar-initial')) {
                            $c.attr('data-orig-avatar-initial', $av.attr('data-initial') || '');
                        }
                        var rAvi = (targetReply.userName || '?').charAt(0).toUpperCase();
                        $av.attr('data-initial', rAvi);
                        if (targetReply.photoUrl) {
                            $av.html('<img src="' + targetReply.photoUrl + '" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="var p=this.parentElement;p.removeChild(this);p.textContent=p.dataset.initial;">');
                        } else {
                            $av.empty().text(rAvi);
                        }
                    }
                } else {
                    // Restore original message, username and avatar
                    $c.removeAttr('data-auto-open-reply');
                    var origMsg = $c.attr('data-message') || '';
                    var origPrev = origMsg.length > 100 ? origMsg.substring(0, 100) + '…' : origMsg;
                    $c.find('.cm-card-text').text(origPrev);
                    var origName = $c.attr('data-orig-username');
                    if (origName) { $c.find('.cm-card-username').text(origName); $c.removeAttr('data-orig-username'); }
                    var origAvi = $c.attr('data-orig-avatar-initial');
                    if (origAvi !== undefined) {
                        var $av2 = $c.find('.cm-card-avatar'), origPhoto = $c.data('user-photo') || '';
                        $av2.attr('data-initial', origAvi);
                        if (origPhoto) { $av2.html('<img src="' + origPhoto + '" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="var p=this.parentElement;p.removeChild(this);p.textContent=p.dataset.initial;">'); }
                        else { $av2.empty().text(origAvi); }
                        $c.removeAttr('data-orig-avatar-initial');
                    }
                }
            } else {
                $c.find('.cm-reply-reported-badge, .cm-reply-blocked-badge, .cm-reply-proh-badge, .cm-reply-rep-count-badge').addClass('d-none');
                $c.find('.cm-reply-rep-count-badge').text('');
                $c.removeAttr('data-auto-open-reply');
                var origMsg = $c.attr('data-message') || '';
                var origPrev = origMsg.length > 100 ? origMsg.substring(0, 100) + '…' : origMsg;
                $c.find('.cm-card-text').text(origPrev);
                var origName2 = $c.attr('data-orig-username');
                if (origName2) { $c.find('.cm-card-username').text(origName2); $c.removeAttr('data-orig-username'); }
                var origAvi2 = $c.attr('data-orig-avatar-initial');
                if (origAvi2 !== undefined) {
                    var $av3 = $c.find('.cm-card-avatar'), origPhoto2 = $c.data('user-photo') || '';
                    $av3.attr('data-initial', origAvi2);
                    if (origPhoto2) { $av3.html('<img src="' + origPhoto2 + '" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="var p=this.parentElement;p.removeChild(this);p.textContent=p.dataset.initial;">'); }
                    else { $av3.empty().text(origAvi2); }
                    $c.removeAttr('data-orig-avatar-initial');
                }
            }
        });

        $('#cmVisibleCount').text(visible);
        $('#cmNoResults').toggleClass('d-none', visible > 0 || $('.cm-card').length === 0);

        // If the active comment's card is now hidden, clear the detail panel
        var activeId = $('#cmDpContent').data('active-comment');
        if (activeId) {
            var $activeCard = $('#cm-card-' + activeId);
            if ($activeCard.length === 0 || !$activeCard.is(':visible')) {
                $('#cmDpContent').addClass('d-none').data('active-comment', null);
                $('#cmDpEmpty').removeClass('d-none');
                $('#cmDpActionRow').addClass('d-none');
            }
        }
    }

    $('#filterSource, #filterReported').on('change', filterCards);
    $('#cmSearch').on('input', function() {
        $('#cmSearchClear').toggleClass('d-none', !$(this).val());
        filterCards();
    });
    $('#cmSearchClear').on('click', function() {
        $('#cmSearch').val('').trigger('input').focus();
    });

    // ---- Select card → populate detail panel ----
    $(document).on('click', '.cm-card', function () {
        var $card = $(this);
        $('.cm-card').removeClass('cm-selected');
        $('.cm-reply-item').removeClass('cm-reply-active');
        $card.addClass('cm-selected');
        $('#dpBackRow').addClass('d-none');
        // Restore action buttons that may have been hidden by a syn-card or reply-item click
        $('#dpHideBtn').removeClass('d-none');
        $('#dpSpamBtn').removeClass('d-none');
        $('#dpDeleteBtn').data('is-reply', '0');
        $('#dpApproveProhibitedBtn').data('is-reply', '0').data('syn-card', false);

        var userName    = $card.data('user-name') || '—';
        var userId      = $card.data('user-id');
        var isBlocked   = $card.data('is-blocked')  === '1' || $card.data('is-blocked')  === 1;
        var isReported  = $card.data('is-reported') === '1' || $card.data('is-reported') === 1;
        var isHidden    = $card.data('is-hidden')   === '1' || $card.data('is-hidden')   === 1;
        var isSpam       = $card.data('is-spam')       === '1' || $card.data('is-spam')       === 1;
        var isProhibited = $card.data('is-prohibited') === '1' || $card.data('is-prohibited') === 1;
        var isSubbed     = $card.data('is-subscribed') === '1' || $card.data('is-subscribed') === 1;
        var reportCount  = parseInt($card.data('report-count') || 0);
        var autoHidden   = String($card.data('auto-hidden')) === '1';
        var sourceType   = $card.attr('data-source-type') || 'dl';
        var jornadaId    = $card.attr('data-jornada-id') || '';
        var lessonId     = $card.attr('data-lesson-id') || '';

        $('#dpSourceTitle').text($card.data('dl-title') || '—');
        $('#dpSourceIcon').attr('class', 'bi ' + (sourceType === 'jornada' ? 'bi-journals' : 'bi-brightness-high'));
        $('#dpMessage').text($card.data('message') || '—');
        var userPhoto   = $card.data('user-photo') || '';
        var dpInitial   = (userName.charAt(0) || '?').toUpperCase();
        // Restore gold background for comment author avatar
        $('#dpAvatarCircle').css('background', '');
        if (userPhoto) {
            var $dpImg = $('<img alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">').attr('src', userPhoto);
            $dpImg.on('error', function () {
                $('#dpAvatarCircle').html('').text(dpInitial);
            });
            $('#dpAvatarCircle').html($dpImg);
        } else {
            $('#dpAvatarCircle').html('').text(dpInitial);
        }
        $('#dpUserName').text(userName);
        $('#dpUserEmail').text($card.data('user-email') || '—');
        $('#dpDate').text($card.data('date-display') || '—');
        $('#dpLikes').text($card.data('likes') || 0);
        $('#dpReplies').text($card.data('replies-count') || 0);

        $('#dpReplyBadge').addClass('d-none'); // hide reply badge when viewing a comment
        isBlocked  ? $('#dpBlockedBadge').removeClass('d-none')    : $('#dpBlockedBadge').addClass('d-none');
        isSubbed   ? $('#dpSubscriberBadge').removeClass('d-none') : $('#dpSubscriberBadge').addClass('d-none');
        isReported ? $('#dpReportedBadge').removeClass('d-none')   : $('#dpReportedBadge').addClass('d-none');
        isHidden   ? $('#dpHiddenBadge').removeClass('d-none')     : $('#dpHiddenBadge').addClass('d-none');
        isSpam       ? $('#dpSpamBadge').removeClass('d-none')       : $('#dpSpamBadge').addClass('d-none');
        isProhibited ? $('#dpProhibitedBadge').removeClass('d-none') : $('#dpProhibitedBadge').addClass('d-none');
        isReported   ? $('#dpClearReportBtn').removeClass('d-none')       : $('#dpClearReportBtn').addClass('d-none');
        isProhibited ? $('#dpApproveProhibitedBtn').removeClass('d-none')  : $('#dpApproveProhibitedBtn').addClass('d-none');
        // Jornada comments: hide spam/unspam (not supported)
        if (sourceType === 'jornada') {
            $('#dpSpamBtn, #dpUnspamBtn').addClass('d-none');
        } else {
            // Hide spam button if: already spam, prohibited word, is reported, or reported tab
            (isSpam || isProhibited || isReported || currentTab === 'reported') ? $('#dpSpamBtn').addClass('d-none') : $('#dpSpamBtn').removeClass('d-none');
            // Show unspam button only when already spam
            isSpam ? $('#dpUnspamBtn').removeClass('d-none') : $('#dpUnspamBtn').addClass('d-none');
        }
        $('#dpUnspamBtn').data('comment-id', $card.data('comment-id')).data('dl-id', $card.data('dl-id')).data('source-type', sourceType);
        // Hide hide/unhide button if already spam
        isSpam ? $('#dpHideBtn').addClass('d-none') : null;
        $('#dpApproveProhibitedBtn').data({ 'comment-id': $card.data('comment-id'), 'dl-id': $card.data('dl-id'), 'is-reply': '0', 'source-type': sourceType, 'jornada-id': jornadaId, 'lesson-id': lessonId });

        if (reportCount > 0) {
            $('#dpReportCountBadge').removeClass('d-none').text(reportCount + ' ' + Lang.reports);
        } else {
            $('#dpReportCountBadge').addClass('d-none');
        }


        updateDpBlockBtn(isBlocked);
        updateDpHideBtn(isHidden);

        $('#dpBlockBtn').data('user-id', userId).data('is-blocked', isBlocked ? '1' : '0');
        $('#dpDeleteBtn').data('comment-id', $card.data('comment-id')).data('dl-id', $card.data('dl-id')).data('source-type', sourceType).data('jornada-id', jornadaId).data('lesson-id', lessonId);
        $('#dpClearReportBtn').data('comment-id', $card.data('comment-id')).data('dl-id', $card.data('dl-id')).data('is-reply', '0').data('reply-id', '').data('source-type', sourceType).data('jornada-id', jornadaId).data('lesson-id', lessonId);
        $('#dpHideBtn').data('comment-id', $card.data('comment-id')).data('dl-id', $card.data('dl-id')).data('is-hidden', isHidden ? '1' : '0').data('is-reply', '0').data('reply-id', '').data('source-type', sourceType).data('jornada-id', jornadaId).data('lesson-id', lessonId);
        $('#dpSpamBtn').data('comment-id', $card.data('comment-id')).data('dl-id', $card.data('dl-id')).data('source-type', sourceType);

        $('#cmDpContent').data('active-comment', $card.data('comment-id'));
        $('#cmDpEmpty').addClass('d-none');
        $('#cmDpContent').removeClass('d-none');
        $('#cmDpActionRow').removeClass('d-none');

        // Populate replies
        var repliesJson = $card.attr('data-replies-json') || '[]';
        var replies = [];
        try { replies = JSON.parse(repliesJson); } catch(e) { replies = []; }
        var $repList = $('#dpRepliesList').empty();
        if (replies.length > 0) {
            $('#dpRepliesCount').text(replies.length);
            replies.forEach(function (r) {
                var initial = (r.userName || '?').charAt(0).toUpperCase();
                var $item = $('<div class="cm-reply-item"></div>');
                $item.append('<div class="cm-reply-avatar">' + initial + '</div>');
                var $body = $('<div class="cm-reply-body"></div>');
                var $rName = $('<div class="cm-reply-name d-flex align-items-center gap-2"></div>').text(r.userName || '—');
                if (r.isProhibitedWord) {
                    $rName.append(' <span class="cm-badge cm-badge-prohibited ms-1"><i class="bi bi-shield-exclamation me-1"></i>' + Lang.prohibited_word + '</span>');
                }
                if (r.isReported) {
                    $rName.append(' <span class="cm-badge cm-badge-reported ms-1"><i class="bi bi-flag me-1"></i>' + Lang.reported + '</span>');
                    if (r.reportCount > 0) {
                        $rName.append(' <span class="cm-badge ms-1" style="background:#fff7ed;color:#ea580c;border:1px solid #fdba74;font-weight:600;"><i class="bi bi-flag-fill me-1"></i>' + r.reportCount + ' ' + Lang.reports + '</span>');
                    }
                }
                $body.append($rName);
                $body.append($('<div class="cm-reply-msg"></div>').text(r.message || ''));
                var $rmeta = $('<div class="cm-reply-meta d-flex align-items-center gap-2"></div>');
                if (r.createdAt) $rmeta.append($('<span></span>').text(r.createdAt));
                if (r.likes > 0) $rmeta.append('<span><i class="bi bi-heart-fill" style="color:#e11d48;font-size:10px;"></i> ' + r.likes + '</span>');
                if (r.reportCount > 0) $rmeta.append('<span style="color:#ea580c;"><i class="bi bi-flag-fill" style="font-size:10px;"></i> ' + r.reportCount + '</span>');
                $body.append($rmeta);
                $item.append($body);
                if (r.id) {
                    var $del = $('<button type="button" class="cm-reply-delete btn-reply-delete" title="{{ __("common.delete") }}"><i class="bi bi-trash"></i></button>');
                    $del.data('reply-id', r.id)
                        .data('comment-id', $card.data('comment-id'))
                        .data('dl-id', $card.data('dl-id'));
                    $item.append($del);
                }

                // Click reply item → switch detail panel to reply context
                (function (reply) {
                    $item.on('click', function (e) {
                        if ($(e.target).closest('.btn-reply-delete').length) return;

                        var rBlocked = reply.isBlocked || false;
                        var rInitial = (reply.userName || '?').charAt(0).toUpperCase();

                        // Mark active
                        $('.cm-reply-item').removeClass('cm-reply-active');
                        $item.addClass('cm-reply-active');

                        // Show back button
                        $('#dpBackRow').removeClass('d-none').data('parent-card', $card);

                        // Avatar — use blue background to distinguish reply author from comment author
                        $('#dpAvatarCircle').css('background', 'linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%)');
                        if (reply.photoUrl) {
                            var $rImg = $('<img alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">').attr('src', reply.photoUrl);
                            $rImg.on('error', function () { $('#dpAvatarCircle').html('').text(rInitial); });
                            $('#dpAvatarCircle').html($rImg);
                        } else {
                            $('#dpAvatarCircle').html('').text(rInitial);
                        }

                        // Populate detail panel fields
                        $('#dpMessage').text(reply.message || '—');
                        $('#dpUserName').text(reply.userName || '—');
                        $('#dpUserEmail').text(reply.userEmail || '—');
                        $('#dpDate').text(reply.createdAt || '—');
                        $('#dpLikes').text(reply.likes || 0);
                        $('#dpReplies').text(0);

                        // Badges
                        var rIsHidden = reply.isHidden || false;
                        $('#dpReplyBadge').removeClass('d-none'); // always show "Reply" badge when viewing a reply
                        $('#dpSpamBadge').addClass('d-none');
                        reply.isSubscribedUser ? $('#dpSubscriberBadge').removeClass('d-none') : $('#dpSubscriberBadge').addClass('d-none');
                        reply.isProhibitedWord ? $('#dpProhibitedBadge').removeClass('d-none') : $('#dpProhibitedBadge').addClass('d-none');
                        rIsHidden ? $('#dpHiddenBadge').removeClass('d-none') : $('#dpHiddenBadge').addClass('d-none');
                        rBlocked ? $('#dpBlockedBadge').removeClass('d-none') : $('#dpBlockedBadge').addClass('d-none');
                        // Show "Reported" badge only when no count available (legacy); count badge handles it otherwise
                        (reply.isReported && !(reply.reportCount > 0)) ? $('#dpReportedBadge').removeClass('d-none') : $('#dpReportedBadge').addClass('d-none');
                        if (reply.reportCount > 0) {
                            $('#dpReportCountBadge').removeClass('d-none').html('<i class="bi bi-flag-fill me-1"></i>' + reply.reportCount + ' ' + Lang.reports);
                        } else {
                            $('#dpReportCountBadge').addClass('d-none');
                        }
                        updateDpBlockBtn(rBlocked);

                        // Action buttons
                        $('#dpSpamBtn, #dpUnspamBtn').addClass('d-none');
                        reply.isProhibitedWord ? $('#dpApproveProhibitedBtn').removeClass('d-none') : $('#dpApproveProhibitedBtn').addClass('d-none');
                        $('#dpApproveProhibitedBtn').data({ 'comment-id': $card.data('comment-id'), 'dl-id': $card.data('dl-id'), 'is-reply': '1', 'reply-id': reply.id, 'syn-card': false });
                        // Show clear-report button when reply is reported
                        var rIsReported = reply.isReported || (reply.reportCount > 0);
                        if (rIsReported) {
                            $('#dpClearReportBtn').removeClass('d-none')
                                .data({ 'comment-id': $card.data('comment-id'), 'dl-id': $card.data('dl-id'), 'is-reply': '1', 'reply-id': reply.id });
                        } else {
                            $('#dpClearReportBtn').addClass('d-none');
                        }
                        $('#dpBlockBtn').data('user-id', reply.userId).data('is-blocked', rBlocked ? '1' : '0');
                        $('#dpDeleteBtn').data({ 'comment-id': $card.data('comment-id'), 'dl-id': $card.data('dl-id'), 'is-reply': '1', 'reply-id': reply.id });
                        $('#dpHideBtn').removeClass('d-none')
                            .data({ 'comment-id': $card.data('comment-id'), 'dl-id': $card.data('dl-id'), 'is-hidden': rIsHidden ? '1' : '0', 'is-reply': '1', 'reply-id': reply.id });
                        updateDpHideBtn(rIsHidden);

                        // Hide replies section (not applicable to a reply)
                        $('#dpRepliesSection').addClass('d-none');
                        $('#cmDpContent').data('active-comment', null);
                    });
                })(r);

                $repList.append($item);
            });
            var hasRepReply = $card.attr('data-has-reported-reply') === '1';
            if (currentTab !== 'reported' || hasRepReply) $('#dpRepliesSection').removeClass('d-none');

            // Auto-open a specific reply in the detail panel (when card shows due to reply reason)
            var autoReplyId = $card.attr('data-auto-open-reply');
            if (autoReplyId) {
                var $targetItem = $repList.find('.cm-reply-item').filter(function () {
                    return $(this).find('.btn-reply-delete').data('reply-id') == autoReplyId;
                }).first();
                if ($targetItem.length) {
                    $targetItem.trigger('click');
                }
            }
        } else {
            $('#dpRepliesSection').addClass('d-none');
        }
    });

    function updateDpBlockBtn(blocked) {
        $('#dpBlockIcon').attr('class', 'bi ' + (blocked ? 'bi-person-lock' : 'bi-person-slash'));
        $('#dpBlockBtn').attr('title', blocked ? Lang.unblock_user : Lang.block_user);
    }

    function updateDpHideBtn(hidden) {
        if (hidden) {
            $('#dpHideIcon').attr('class', 'bi bi-eye');
            $('#dpHideBtn').attr('title', Lang.approve_comment)
                           .removeClass('btn-dp-hide').addClass('btn-dp-approve');
        } else {
            $('#dpHideIcon').attr('class', 'bi bi-eye-slash');
            $('#dpHideBtn').attr('title', Lang.hide_comment)
                           .removeClass('btn-dp-approve').addClass('btn-dp-hide');
        }
    }

    // ---- Back to parent comment ----
    $('#dpBackBtn').on('click', function () {
        var $parentCard = $('#dpBackRow').data('parent-card');
        if ($parentCard && $parentCard.length) {
            $parentCard[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            $parentCard.trigger('click');
        }
    });

    // ---- Block from card ----
    $(document).on('click', '.btn-cm-block', function () {
        var userId  = $(this).data('user-id');
        var blocked = $(this).data('is-blocked') === '1' || $(this).data('is-blocked') === 1;
        confirmBlock(userId, blocked, function (nowBlocked) {
            updateAllBlockUi(userId, nowBlocked);
        });
    });

    // ---- Block from detail panel ----
    $('#dpBlockBtn').on('click', function () {
        var userId  = $(this).data('user-id');
        var blocked = $(this).data('is-blocked') === '1' || $(this).data('is-blocked') === 1;
        confirmBlock(userId, blocked, function (nowBlocked) {
            updateAllBlockUi(userId, nowBlocked);
            $('#dpBlockBtn').data('is-blocked', nowBlocked ? '1' : '0');
            updateDpBlockBtn(nowBlocked);
            nowBlocked ? $('#dpBlockedBadge').removeClass('d-none') : $('#dpBlockedBadge').addClass('d-none');
        });
    });

    function confirmBlock(userId, blocked, onSuccess) {
        var blockTitle = blocked ? Lang.unblock_reported_title : Lang.block_reported_title;
        var blockText  = blocked ? Lang.unblock_reported_confirm : Lang.block_reported_confirm;
        Swal.fire({
            title: blockTitle,
            text:  blockText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: blocked ? '#C8902E' : '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;
            showLoader(Lang.processing);
            $.ajax({
                url: blockUrl.replace('USER_ID', userId),
                method: 'POST',
                data: { _token: csrf },
                success: function (res) {
                    $('#dlOverlayLoader').removeClass('active');
                    if (res.success) {
                        if (typeof onSuccess === 'function') onSuccess(res.isBlocked);
                        showSuccess(res.isBlocked ? Lang.user_blocked : Lang.user_unblocked);
                    } else {
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                },
                error: function () {
                    $('#dlOverlayLoader').removeClass('active');
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            });
        });
    }

    function updateAllBlockUi(userId, nowBlocked) {
        $('.btn-cm-block[data-user-id="' + userId + '"]').each(function () {
            $(this).data('is-blocked', nowBlocked ? '1' : '0')
                   .attr('data-is-blocked', nowBlocked ? '1' : '0')
                   .attr('title', nowBlocked ? Lang.unblock_user : Lang.block_user)
                   .toggleClass('is-blocked', nowBlocked)
                   .find('i').attr('class', 'bi ' + (nowBlocked ? 'bi-person-lock' : 'bi-person-slash'));
        });
        $('.cm-card[data-user-id="' + userId + '"]').attr('data-is-blocked', nowBlocked ? '1' : '0');
        $('.cm-card[data-user-id="' + userId + '"] .cm-badge-blocked').remove();
        if (nowBlocked) {
            $('.cm-card[data-user-id="' + userId + '"] .cm-card-username').after(
                '<span class="cm-badge cm-badge-blocked ms-1">{{ __("common.blocked") }}</span>'
            );
        }
    }

    // ---- Delete from card ----
    $(document).on('click', '.btn-cm-delete', function () {
        confirmDelete($(this).data('comment-id'), $(this).data('dl-id'), function (cid) { removeCard(cid); });
    });

    // ---- Delete from detail panel ----
    $('#dpDeleteBtn').on('click', function () {
        var isReply    = $(this).data('is-reply') === '1';
        var commentId  = $(this).data('comment-id');
        var dlId       = $(this).data('dl-id');
        var sourceType = $(this).data('source-type') || 'dl';
        var jornadaId  = $(this).data('jornada-id') || '';
        var lessonId   = $(this).data('lesson-id') || '';

        var deleteTitle = currentTab === 'reported' ? Lang.delete_reported_title : Lang.confirm_delete;
        var deleteText  = Lang.delete_reported_confirm;

        Swal.fire({
            title: deleteTitle,
            text:  deleteText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_delete,
            cancelButtonText: Lang.cancel
        }).then(function (result) {
            if (!result.isConfirmed) return;

            if (isReply) {
                var replyId = $('#dpDeleteBtn').data('reply-id');
                showLoader(Lang.deleting);
                $.ajax({
                    url: deleteReplyUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId).replace('REPLY_ID', replyId),
                    method: 'DELETE',
                    data: { _token: csrf },
                    success: function (res) {
                        $('#dlOverlayLoader').removeClass('active');
                        if (res.success) {
                            {
                                // Reply-item context: update parent card JSON + counts, re-click card
                                var $parentCard = $('#cm-card-' + commentId);
                                var repliesJson = $parentCard.attr('data-replies-json') || '[]';
                                var replies = [];
                                try { replies = JSON.parse(repliesJson); } catch(e) { replies = []; }
                                replies = replies.filter(function (rep) { return rep.id !== replyId; });
                                $parentCard.attr('data-replies-json', JSON.stringify(replies));
                                var newCount = Math.max(0, parseInt($parentCard.data('replies-count') || 0) - 1);
                                $parentCard.data('replies-count', newCount).attr('data-replies-count', newCount);
                                $parentCard.trigger('click');
                            }
                            showSuccess(Lang.deleted_success);
                        } else {
                            Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                        }
                    },
                    error: function () {
                        $('#dlOverlayLoader').removeClass('active');
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                });
                return;
            }

            showLoader(Lang.deleting);
            var deleteAjaxUrl = sourceType === 'jornada'
                ? jnDeleteUrl.replace('JORNADA_ID', jornadaId).replace('LESSON_ID', lessonId).replace('COMMENT_ID', commentId)
                : deleteUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId);
            $.ajax({
                url: deleteAjaxUrl,
                method: 'DELETE',
                data: { _token: csrf },
                success: function (res) {
                    $('#dlOverlayLoader').removeClass('active');
                    if (res.success) {
                        removeCard(commentId);
                        showSuccess(Lang.deleted_success);
                    } else {
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                },
                error: function () {
                    $('#dlOverlayLoader').removeClass('active');
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            });
        });
    });

    function confirmDelete(commentId, dlId, onSuccess) {
        Swal.fire({
            title: Lang.confirm_delete,
            text:  Lang.delete_reported_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_delete,
            cancelButtonText: Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;
            showLoader(Lang.deleting);
            $.ajax({
                url: deleteUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId),
                method: 'DELETE',
                data: { _token: csrf },
                success: function (res) {
                    $('#dlOverlayLoader').removeClass('active');
                    if (res.success) {
                        if (typeof onSuccess === 'function') onSuccess(commentId);
                        showSuccess(Lang.deleted_success);
                    } else {
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                },
                error: function () {
                    $('#dlOverlayLoader').removeClass('active');
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            });
        });
    }

    function removeCard(commentId) {
        $('#cm-card-' + commentId).fadeOut(250, function () {
            $(this).remove();
            updateTabCounts();
            var remaining = $('.cm-card:visible').length;
            $('#cmVisibleCount').text(remaining);
            if (remaining === 0) $('#cmNoResults').removeClass('d-none');
        });
        if (String($('#cmDpContent').data('active-comment')) === String(commentId)) {
            $('#cmDpContent').addClass('d-none').data('active-comment', null);
            $('#cmDpEmpty').removeClass('d-none');
            $('#cmDpActionRow').addClass('d-none');
        }
    }

    // ---- Delete Reply ----
    $(document).on('click', '.btn-reply-delete', function () {
        var $btn      = $(this);
        var replyId   = $btn.data('reply-id');
        var commentId = $btn.data('comment-id');
        var dlId      = $btn.data('dl-id');
        Swal.fire({
            title: Lang.confirm_delete,
            text:  Lang.delete_reported_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_delete,
            cancelButtonText: Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;
            showLoader(Lang.deleting);
            $.ajax({
                url: deleteReplyUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId).replace('REPLY_ID', replyId),
                method: 'DELETE',
                data: { _token: csrf },
                success: function (res) {
                    $('#dlOverlayLoader').removeClass('active');
                    if (res.success) {
                        $btn.closest('.cm-reply-item').fadeOut(200, function () {
                            $(this).remove();
                            var remaining = $('#dpRepliesList .cm-reply-item').length;
                            $('#dpRepliesCount').text(remaining);
                            if (remaining === 0) $('#dpRepliesSection').addClass('d-none');
                            var $card = $('#cm-card-' + commentId);
                            var newCount = Math.max(0, parseInt($card.data('replies-count') || 0) - 1);
                            $card.data('replies-count', newCount).attr('data-replies-count', newCount);
                            $('#dpReplies').text(newCount);
                            // Keep data-replies-json in sync so re-renders are accurate
                            var repliesJson = $card.attr('data-replies-json') || '[]';
                            var replies = [];
                            try { replies = JSON.parse(repliesJson); } catch(e) { replies = []; }
                            replies = replies.filter(function (rep) { return rep.id !== replyId; });
                            $card.attr('data-replies-json', JSON.stringify(replies));
                        });
                        showSuccess(Lang.deleted_success);
                    } else {
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                },
                error: function () {
                    $('#dlOverlayLoader').removeClass('active');
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            });
        });
    });

    // ---- Clear Report from card ----
    $(document).on('click', '.btn-cm-clear-report', function () {
        var $btn = $(this), commentId = $btn.data('comment-id'), dlId = $btn.data('dl-id');
        Swal.fire({
            title: Lang.report_cleared + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#C8902E',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;
            doClearReport(commentId, dlId, function () {
                $btn.remove();
                var $card = $('#cm-card-' + commentId);
                $card.attr('data-is-reported', '0');
                $card.find('.cm-badge-reported').remove();
                filterCards();
            });
        });
    });

    // ---- Approve (Clear Report) from detail panel ----
    $('#dpClearReportBtn').on('click', function () {
        var commentId  = $(this).data('comment-id'), dlId = $(this).data('dl-id');
        var isReply    = $(this).data('is-reply') === '1' || $(this).data('is-reply') === 1;
        var replyId    = $(this).data('reply-id') || '';
        var sourceType = $(this).data('source-type') || 'dl';
        var jornadaId  = $(this).data('jornada-id') || '';
        var lessonId   = $(this).data('lesson-id') || '';
        var $card      = $('#cm-card-' + commentId);

        Swal.fire({
            title:             Lang.approve_reported_title,
            text:              Lang.approve_reported_confirm,
            icon:              'question',
            showCancelButton:  true,
            confirmButtonColor:'#C8902E',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText:  Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;

            if (isReply && replyId) {
                // Clear reply report: delete reports subcollection docs
                showLoader(Lang.processing);
                var url = clearReplyReportUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId).replace('REPLY_ID', replyId);
                $.ajax({
                    url: url, method: 'POST', data: { _token: csrf },
                    success: function (res) {
                        hideLoader();
                        if (res && res.success) {
                            // Update replies JSON on card
                            var rJson = $card.attr('data-replies-json') || '[]';
                            var rList = []; try { rList = JSON.parse(rJson); } catch(e) {}
                            for (var i = 0; i < rList.length; i++) {
                                if (rList[i].id == replyId) { rList[i].isReported = false; rList[i].reportCount = 0; break; }
                            }
                            $card.attr('data-replies-json', JSON.stringify(rList));
                            $('#dpReportedBadge, #dpClearReportBtn').addClass('d-none');
                            $('#dpReportCountBadge').addClass('d-none');
                            filterCards();
                            showSuccess(Lang.report_cleared_success);
                        } else {
                            Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                        }
                    },
                    error: function () { hideLoader(); Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' }); }
                });
            } else {
                // Clear comment report flag
                var clearOverride = sourceType === 'jornada'
                    ? jnApproveUrl.replace('JORNADA_ID', jornadaId).replace('LESSON_ID', lessonId).replace('COMMENT_ID', commentId)
                    : null;
                doClearReport(commentId, dlId, function () {
                    $card.attr('data-is-reported', '0').attr('data-auto-hidden', '0');
                    $card.find('.cm-badge-reported, .btn-cm-clear-report').remove();
                    $card.find('.cm-report-count-badge').remove();
                    $('#dpReportedBadge, #dpClearReportBtn').addClass('d-none');
                    $('#dpReportCountBadge').addClass('d-none');
                    filterCards();
                }, Lang.report_cleared_success, clearOverride);
            }
        });
    });

    // ---- Approve Prohibited Word (clear isProhibitedWord flag) ----
    $('#dpApproveProhibitedBtn').on('click', function () {
        var $btn       = $(this);
        var commentId  = $btn.data('comment-id');
        var dlId       = $btn.data('dl-id');
        var isReply    = $btn.data('is-reply') === '1';
        var replyId    = $btn.data('reply-id');
        var sourceType = $btn.data('source-type') || 'dl';
        var jornadaId  = $btn.data('jornada-id') || '';
        var lessonId   = $btn.data('lesson-id') || '';

        Swal.fire({
            title: Lang.approve_prohibited + '?',
            text:  Lang.approve_prohibited_confirm,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#C8902E',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;
            showLoader(Lang.processing);

            var ajaxUrl;
            if (sourceType === 'jornada') {
                ajaxUrl = jnApproveUrl.replace('JORNADA_ID', jornadaId).replace('LESSON_ID', lessonId).replace('COMMENT_ID', commentId);
            } else if (isReply) {
                ajaxUrl = approveReplyProhibitedUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId).replace('REPLY_ID', replyId);
            } else {
                ajaxUrl = approveProhibitedUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId);
            }

            $.ajax({
                url: ajaxUrl, method: 'POST', data: { _token: csrf },
                success: function (res) {
                    $('#dlOverlayLoader').removeClass('active');
                    if (res.success) {
                        var $card = $('#cm-card-' + commentId);
                        $card.attr('data-is-prohibited', '0').find('.cm-badge-prohibited').remove();
                        $('#dpProhibitedBadge').addClass('d-none');
                        $btn.addClass('d-none');
                        if (isReply) {
                            // Update reply in data-replies-json
                            var replyId = $btn.data('reply-id');
                            var rJson = $card.attr('data-replies-json') || '[]';
                            var rList = [];
                            try { rList = JSON.parse(rJson); } catch(e) {}
                            rList.forEach(function(rep) { if (rep.id === replyId) rep.isProhibitedWord = false; });
                            $card.attr('data-replies-json', JSON.stringify(rList));
                            // Update has-prohibited-reply flag
                            var anyProhibitedLeft = rList.some(function(rep) { return rep.isProhibitedWord; });
                            $card.attr('data-has-prohibited-reply', anyProhibitedLeft ? '1' : '0');
                            $card.trigger('click');
                        }
                        // Remove card from view if it no longer matches current filter
                        if (currentTab === 'hidden' || $('#filterReported').val() === 'hidden') {
                            $card.fadeOut(200, function () { filterCards(); });
                        }
                        showSuccess(Lang.prohibited_approved);
                    } else {
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                },
                error: function () {
                    $('#dlOverlayLoader').removeClass('active');
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            });
        });
    });

    function doClearReport(commentId, dlId, onSuccess, successMsg, overrideUrl) {
        showLoader(Lang.processing);
        var url = overrideUrl || clearReportUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId);
        $.ajax({
            url: url,
            method: 'POST', data: { _token: csrf },
            success: function (res) {
                $('#dlOverlayLoader').removeClass('active');
                if (res.success) {
                    if (typeof onSuccess === 'function') onSuccess();
                    showSuccess(successMsg || Lang.report_cleared);
                } else {
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            },
            error: function () {
                $('#dlOverlayLoader').removeClass('active');
                Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
            }
        });
    }

    // ---- Hide from card ----
    $(document).on('click', '.btn-cm-hide', function () {
        var $btn = $(this), commentId = $btn.data('comment-id'), dlId = $btn.data('dl-id');
        Swal.fire({
            title: Lang.hide_reported_title,
            text:  Lang.hide_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#C8902E',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;
            doHide(commentId, dlId, function () {
                var $card = $('#cm-card-' + commentId);
                $card.data('is-hidden', '1');
                $btn.attr('class', 'cm-ca-btn cm-ca-approve btn-cm-approve')
                    .html('<i class="bi bi-check-circle-fill"></i> {{ __("common.approve_comment") }}');
                if (!$card.find('.cm-badge-hidden').length) {
                    $card.find('.cm-card-username').after('<span class="cm-badge cm-badge-hidden ms-1"><i class="bi bi-eye-slash me-1"></i>{{ __("common.hidden") }}</span>');
                }
            });
        });
    });

    // ---- Approve (unhide) from card ----
    $(document).on('click', '.btn-cm-approve', function () {
        var $btn = $(this), commentId = $btn.data('comment-id'), dlId = $btn.data('dl-id');
        Swal.fire({
            title: Lang.unhide_comment_title,
            text:  Lang.unhide_confirm,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#C8902E',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;
            doApprove(commentId, dlId, function () {
                var $card = $('#cm-card-' + commentId);
                $card.data('is-hidden', '0');
                $btn.attr('class', 'cm-ca-btn cm-ca-hide btn-cm-hide')
                    .html('<i class="bi bi-eye-slash-fill"></i> {{ __("common.hide_comment") }}');
                $card.find('.cm-badge-hidden').remove();
            });
        });
    });

    // ---- Hide/Approve from detail panel ----
    $('#dpHideBtn').on('click', function () {
        var commentId  = $(this).data('comment-id');
        var dlId       = $(this).data('dl-id');
        var isHidden   = $(this).data('is-hidden') === '1' || $(this).data('is-hidden') === 1;
        var isReply    = $(this).data('is-reply') === '1';
        var replyId    = $(this).data('reply-id');
        var sourceType = $(this).data('source-type') || 'dl';
        var jornadaId  = $(this).data('jornada-id') || '';
        var lessonId   = $(this).data('lesson-id') || '';
        var $card      = $('#cm-card-' + commentId);

        var title, text, btnColor, icon;
        if (isHidden) {
            title    = Lang.unhide_comment_title;
            text     = Lang.unhide_confirm;
            btnColor = '#C8902E';
            icon     = 'question';
        } else {
            title    = Lang.hide_reported_title;
            text     = Lang.hide_confirm;
            btnColor = '#C8902E';
            icon     = 'warning';
        }

        Swal.fire({
            title: title,
            text:  text || undefined,
            icon:  icon,
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;

            // ---- Reply hide/unhide ----
            if (isReply) {
                showLoader(Lang.processing);
                var replyActionUrl = isHidden
                    ? approveReplyUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId).replace('REPLY_ID', replyId)
                    : hideReplyUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId).replace('REPLY_ID', replyId);
                $.ajax({
                    url: replyActionUrl, method: 'POST', data: { _token: csrf },
                    success: function (res) {
                        $('#dlOverlayLoader').removeClass('active');
                        if (res.success) {
                            var nowHidden = !isHidden;
                            $('#dpHideBtn').data('is-hidden', nowHidden ? '1' : '0');
                            updateDpHideBtn(nowHidden);
                            nowHidden ? $('#dpHiddenBadge').removeClass('d-none') : $('#dpHiddenBadge').addClass('d-none');
                            // Sync data-replies-json
                            var repliesJson = $card.attr('data-replies-json') || '[]';
                            var replies = [];
                            try { replies = JSON.parse(repliesJson); } catch(e) { replies = []; }
                            $.each(replies, function(i, rep) {
                                if (rep.id === replyId) { rep.isHidden = nowHidden; return false; }
                            });
                            $card.attr('data-replies-json', JSON.stringify(replies));
                            showSuccess(nowHidden ? Lang.comment_hidden : Lang.comment_approved);
                        } else {
                            Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                        }
                    },
                    error: function () {
                        $('#dlOverlayLoader').removeClass('active');
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                });
                return;
            }

            // ---- Main comment hide/unhide ----
            if (isHidden) {
                var approveOverride = sourceType === 'jornada'
                    ? jnApproveUrl.replace('JORNADA_ID', jornadaId).replace('LESSON_ID', lessonId).replace('COMMENT_ID', commentId)
                    : null;
                doApprove(commentId, dlId, function () {
                    $('#dpHideBtn').data('is-hidden', '0');
                    updateDpHideBtn(false);
                    $('#dpHiddenBadge').addClass('d-none');
                    $card.data('is-hidden', '0').find('.cm-badge-hidden').remove();
                    $card.find('.btn-cm-approve').removeClass('cm-quick-approve btn-cm-approve')
                         .addClass('cm-quick-hide btn-cm-hide').attr('title', Lang.hide_comment)
                         .find('i').attr('class', 'bi bi-eye-slash');
                }, null, approveOverride);
            } else {
                var hideOverride = sourceType === 'jornada'
                    ? jnHideUrl.replace('JORNADA_ID', jornadaId).replace('LESSON_ID', lessonId).replace('COMMENT_ID', commentId)
                    : null;
                doHide(commentId, dlId, function () {
                    $('#dpHideBtn').data('is-hidden', '1');
                    updateDpHideBtn(true);
                    $('#dpHiddenBadge').removeClass('d-none');
                    $card.data('is-hidden', '1');
                    $card.find('.btn-cm-hide').removeClass('cm-quick-hide btn-cm-hide')
                         .addClass('cm-quick-approve btn-cm-approve').attr('title', Lang.approve_comment)
                         .find('i').attr('class', 'bi bi-eye');
                    if (!$card.find('.cm-badge-hidden').length) {
                        $card.find('.cm-card-username').after('<span class="cm-badge cm-badge-hidden ms-1"><i class="bi bi-eye-slash me-1"></i>{{ __("common.hidden") }}</span>');
                    }
                }, hideOverride);
            }
        });
    });

    function doHide(commentId, dlId, onSuccess, overrideUrl) {
        showLoader(Lang.processing);
        var url = overrideUrl || hideUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId);
        $.ajax({
            url: url,
            method: 'POST', data: { _token: csrf },
            success: function (res) {
                $('#dlOverlayLoader').removeClass('active');
                if (res.success) {
                    if (typeof onSuccess === 'function') onSuccess();
                    showSuccess(Lang.comment_hidden);
                } else {
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            },
            error: function () {
                $('#dlOverlayLoader').removeClass('active');
                Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
            }
        });
    }

    function doApprove(commentId, dlId, onSuccess, successMsg, overrideUrl) {
        showLoader(Lang.processing);
        var url = overrideUrl || approveUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId);
        $.ajax({
            url: url,
            method: 'POST', data: { _token: csrf },
            success: function (res) {
                $('#dlOverlayLoader').removeClass('active');
                if (res.success) {
                    if (typeof onSuccess === 'function') onSuccess();
                    showSuccess(successMsg || Lang.comment_approved);
                } else {
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            },
            error: function () {
                $('#dlOverlayLoader').removeClass('active');
                Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
            }
        });
    }

    // ---- Mark as Spam from card ----
    $(document).on('click', '.btn-cm-spam-card', function () {
        var $btn = $(this), commentId = $btn.data('comment-id'), dlId = $btn.data('dl-id');
        Swal.fire({
            title: Lang.mark_as_spam + '?',
            text:  Lang.mark_as_spam_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;
            showLoader(Lang.processing);
            $.ajax({
                url: spamUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId),
                method: 'POST', data: { _token: csrf },
                success: function (res) {
                    $('#dlOverlayLoader').removeClass('active');
                    if (res.success) {
                        var $card = $('#cm-card-' + commentId);
                        $card.data('is-spam', '1').data('is-hidden', '1');
                        // Set isHidden=true on all replies in client-side cache
                        var spamRJson = $card.attr('data-replies-json') || '[]';
                        var spamRList = [];
                        try { spamRList = JSON.parse(spamRJson); } catch(e) {}
                        spamRList.forEach(function(rep) { rep.isHidden = true; });
                        $card.attr('data-replies-json', JSON.stringify(spamRList));
                        if (!$card.find('.cm-badge-spam').length) {
                            $card.find('.cm-card-username').after('<span class="cm-badge cm-badge-spam ms-1">{{ __("common.spam") }}</span>');
                        }
                        if (!$card.find('.cm-badge-hidden').length) {
                            $card.find('.cm-card-username').after('<span class="cm-badge cm-badge-hidden ms-1"><i class="bi bi-eye-slash me-1"></i>{{ __("common.hidden") }}</span>');
                        }
                        // Disable spam button
                        $btn.prop('disabled', true).css('opacity', '0.4');
                        // Sync detail panel if active
                        if ($('#cmDpContent').data('active-comment') === commentId) {
                            $('#dpSpamBadge, #dpHiddenBadge').removeClass('d-none');
                            $('#dpSpamBtn, #dpHideBtn').addClass('d-none');
                        }
                        showSuccess(Lang.comment_spam);
                    } else {
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                },
                error: function () {
                    $('#dlOverlayLoader').removeClass('active');
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            });
        });
    });

    // ---- Mark as Spam from detail panel ----
    $('#dpSpamBtn').on('click', function () {
        var commentId = $(this).data('comment-id'), dlId = $(this).data('dl-id');
        Swal.fire({
            title: Lang.mark_as_spam + '?',
            text:  Lang.mark_as_spam_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;
            showLoader(Lang.processing);
            $.ajax({
                url: spamUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId),
                method: 'POST', data: { _token: csrf },
                success: function (res) {
                    $('#dlOverlayLoader').removeClass('active');
                    if (res.success) {
                        var $card = $('#cm-card-' + commentId);
                        $card.data('is-spam', '1').data('is-hidden', '1');
                        // Set isHidden=true on all replies in client-side cache
                        var spamRJson2 = $card.attr('data-replies-json') || '[]';
                        var spamRList2 = [];
                        try { spamRList2 = JSON.parse(spamRJson2); } catch(e) {}
                        spamRList2.forEach(function(rep) { rep.isHidden = true; });
                        $card.attr('data-replies-json', JSON.stringify(spamRList2));
                        $('#dpSpamBadge, #dpHiddenBadge').removeClass('d-none');
                        $('#dpSpamBtn, #dpHideBtn').addClass('d-none');
                        $('#dpUnspamBtn').removeClass('d-none')
                            .data('comment-id', commentId).data('dl-id', dlId);
                        if (!$card.find('.cm-badge-spam').length) {
                            $card.find('.cm-card-username').after('<span class="cm-badge cm-badge-spam ms-1">{{ __("common.spam") }}</span>');
                        }
                        showSuccess(Lang.comment_spam);
                    } else {
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                },
                error: function () {
                    $('#dlOverlayLoader').removeClass('active');
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            });
        });
    });

    // ---- Un-spam comment ----
    $('#dpUnspamBtn').on('click', function () {
        var commentId = $(this).data('comment-id'), dlId = $(this).data('dl-id');
        Swal.fire({
            title: Lang.unspam_comment + '?',
            text:  Lang.unspam_confirm,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#C8902E',
            cancelButtonColor: '#6b7280',
            confirmButtonText: Lang.yes_confirm,
            cancelButtonText: Lang.cancel
        }).then(function (r) {
            if (!r.isConfirmed) return;
            showLoader(Lang.processing);
            $.ajax({
                url: unspamUrl.replace('DL_ID', dlId).replace('COMMENT_ID', commentId),
                method: 'POST', data: { _token: csrf },
                success: function (res) {
                    $('#dlOverlayLoader').removeClass('active');
                    if (res.success) {
                        var $card = $('#cm-card-' + commentId);
                        $card.data('is-spam', '0').data('is-hidden', '0');
                        $card.find('.cm-badge-spam').remove();
                        $card.find('.cm-badge-hidden').remove();
                        // Restore replies isHidden=false
                        var rJson = $card.attr('data-replies-json') || '[]';
                        var rList = []; try { rList = JSON.parse(rJson); } catch(e) {}
                        rList.forEach(function(rep) { rep.isHidden = false; });
                        $card.attr('data-replies-json', JSON.stringify(rList));
                        // Update detail panel
                        $('#dpSpamBadge, #dpHiddenBadge').addClass('d-none');
                        $('#dpUnspamBtn').addClass('d-none');
                        $('#dpHideBtn').removeClass('d-none');
                        // Show spam button again if conditions allow
                        var nowProhibited = $card.attr('data-is-prohibited') === '1';
                        var nowReported   = $card.attr('data-is-reported') === '1';
                        if (!nowProhibited && !nowReported && currentTab !== 'reported') {
                            $('#dpSpamBtn').removeClass('d-none')
                                .data('comment-id', commentId).data('dl-id', dlId);
                        }
                        showSuccess(Lang.comment_unspammed);
                    } else {
                        Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                    }
                },
                error: function () {
                    $('#dlOverlayLoader').removeClass('active');
                    Swal.fire({ icon: 'error', title: Lang.something_wrong, confirmButtonColor: '#C8902E' });
                }
            });
        });
    });

    // ---- Tab click handler ----
    $(document).on('click', '.cm-tab[data-tab]', function (e) {
        e.preventDefault();
        switchTab($(this).data('tab'));
    });

    // ---- Init: compute counts + activate initial tab ----
    updateTabCounts();
    switchTab(currentTab);

});
</script>
@endsection
