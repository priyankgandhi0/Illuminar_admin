@extends('layouts.app')
@php
    $pageTitle = __('subscription.page_title');
    $languages = [
        'pt' => __('common.portuguese_pt'),
        'en' => __('common.english_en'),
        'es' => __('common.spanish_es'),
    ];
@endphp
@section('title', $pageTitle)

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">{{ $pageTitle }}</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('common.home') }}</a></li>
                        <li class="breadcrumb-item active">{{ $pageTitle }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card dl-form-card">

                {{-- Language Tabs --}}
                <ul class="nav dl-lang-tabs px-4 pt-2" id="subLangTabs" role="tablist">
                    @foreach($languages as $code => $label)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link dl-lang-tab {{ $code === 'pt' ? 'active' : '' }}"
                            id="sub-tab-{{ $code }}" data-bs-toggle="tab"
                            data-bs-target="#sub-panel-{{ $code }}" type="button" role="tab"
                            data-lang="{{ $code }}">
                            {{ $label }}
                        </button>
                    </li>
                    @endforeach
                </ul>

                <div class="card-body pt-0">
                    <div class="tab-content pt-3" id="subTabContent">
                        @foreach($languages as $code => $label)
                        @php $d = $details[$code] ?? []; @endphp
                        <div class="tab-pane fade {{ $code === 'pt' ? 'show active' : '' }}"
                            id="sub-panel-{{ $code }}" role="tabpanel">

                            <h6 class="dl-section-heading mb-3">{{ $label }}</h6>

                            {{-- Title --}}
                            <div class="mb-3">
                                <label class="dl-label">{{ __('subscription.title') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control sub-title"
                                    data-lang="{{ $code }}"
                                    placeholder="{{ __('subscription.title_placeholder') }}"
                                    value="{{ $d['title'] ?? '' }}">
                            </div>

                            {{-- Subtitle --}}
                            <div class="mb-3">
                                <label class="dl-label">{{ __('subscription.subtitle') }} <span class="text-danger">*</span></label>
                                <textarea class="form-control sub-subtitle" rows="2"
                                    data-lang="{{ $code }}"
                                    placeholder="{{ __('subscription.subtitle_placeholder') }}">{{ $d['subtitle'] ?? '' }}</textarea>
                            </div>

                            {{-- Button Text --}}
                            <div class="mb-4">
                                <label class="dl-label">{{ __('subscription.button_text') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control sub-btn-text"
                                    data-lang="{{ $code }}"
                                    placeholder="{{ __('subscription.button_placeholder') }}"
                                    value="{{ $d['button_text'] ?? '' }}">
                            </div>

                            {{-- Bullet Points --}}
                            <div class="mb-3">
                                <label class="dl-label">{{ __('subscription.bullet_points') }}</label>
                                <div class="sub-bullets-container" id="bulletsContainer_{{ $code }}">
                                    @forelse($d['bullets'] ?? [] as $bi => $bullet)
                                    <div class="sub-bullet-row d-flex align-items-center gap-2 mb-2">
                                        <span class="sub-bullet-icon"><i class="bi bi-check-circle-fill" style="color:#d4a528;"></i></span>
                                        <input type="text" class="form-control sub-bullet-input"
                                            placeholder="{{ __('subscription.bullet_placeholder') }}"
                                            value="{{ $bullet }}">
                                        <button type="button" class="btn btn-sm btn-outline-danger sub-remove-bullet" title="{{ __('subscription.remove_bullet') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    @empty
                                    <div class="sub-bullet-row d-flex align-items-center gap-2 mb-2">
                                        <span class="sub-bullet-icon"><i class="bi bi-check-circle-fill" style="color:#d4a528;"></i></span>
                                        <input type="text" class="form-control sub-bullet-input"
                                            placeholder="{{ __('subscription.bullet_placeholder') }}"
                                            value="">
                                        <button type="button" class="btn btn-sm btn-outline-danger sub-remove-bullet d-none" title="{{ __('subscription.remove_bullet') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    @endforelse
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary sub-add-bullet mt-2" data-lang="{{ $code }}">
                                    <i class="bi bi-plus-circle me-1"></i>{{ __('subscription.add_bullet') }}
                                </button>
                            </div>

                            {{-- WhatsApp CTA Screen --}}
                            <hr class="my-4">
                            <h6 class="dl-section-heading mb-3">
                                <i class="bi bi-whatsapp me-1" style="color:#25d366;"></i>{{ __('subscription.whatsapp_section') }}
                            </h6>

                            {{-- WhatsApp Title --}}
                            <div class="mb-3">
                                <label class="dl-label">{{ __('subscription.whatsapp_title') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control sub-wa-title"
                                    data-lang="{{ $code }}"
                                    placeholder="{{ __('subscription.whatsapp_title_placeholder') }}"
                                    value="{{ $d['wa_title'] ?? '' }}">
                            </div>

                            {{-- WhatsApp Subtitle --}}
                            <div class="mb-3">
                                <label class="dl-label">{{ __('subscription.whatsapp_subtitle') }} <span class="text-danger">*</span></label>
                                <textarea class="form-control sub-wa-subtitle" rows="2"
                                    data-lang="{{ $code }}"
                                    placeholder="{{ __('subscription.whatsapp_subtitle_placeholder') }}">{{ $d['wa_subtitle'] ?? '' }}</textarea>
                            </div>

                            {{-- WhatsApp Button Text --}}
                            <div class="mb-3">
                                <label class="dl-label">{{ __('subscription.whatsapp_button_text') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control sub-wa-btn-text"
                                    data-lang="{{ $code }}"
                                    placeholder="{{ __('subscription.whatsapp_button_placeholder') }}"
                                    value="{{ $d['wa_button_text'] ?? '' }}">
                            </div>

                            {{-- Save Button --}}
                            <div class="mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-primary sub-save-btn" data-lang="{{ $code }}">
                                    <i class="bi bi-save me-1"></i>{{ __('subscription.save_lang', ['lang' => strtoupper($code)]) }}
                                </button>
                            </div>

                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="dl-overlay-loader" id="dlOverlayLoader">
    <div class="loader-spinner"></div>
    <div class="loader-text" id="dlLoaderText">{{ __('common.saving') }}...</div>
</div>

@section('scripts')
<script>
$(document).ready(function () {
    var saveUrl = '{{ route("subscription.save") }}';
    var csrf    = '{{ csrf_token() }}';

    // Add bullet row
    $(document).on('click', '.sub-add-bullet', function () {
        var lang = $(this).data('lang');
        var $container = $('#bulletsContainer_' + lang);
        var $row = $('<div class="sub-bullet-row d-flex align-items-center gap-2 mb-2">' +
            '<span class="sub-bullet-icon"><i class="bi bi-check-circle-fill" style="color:#d4a528;"></i></span>' +
            '<input type="text" class="form-control sub-bullet-input" placeholder="{{ __('subscription.bullet_placeholder') }}">' +
            '<button type="button" class="btn btn-sm btn-outline-danger sub-remove-bullet" title="{{ __('subscription.remove_bullet') }}">' +
            '<i class="bi bi-trash"></i></button></div>');
        $container.append($row);
        toggleRemoveBullets(lang);
        $row.find('input').focus();
    });

    // Remove bullet row
    $(document).on('click', '.sub-remove-bullet', function () {
        var $btn = $(this);
        var $container = $btn.closest('.sub-bullets-container');
        var lang = $container.attr('id').replace('bulletsContainer_', '');
        Swal.fire({
            title: '{{ __("subscription.remove_bullet") }}',
            text: '{{ __("subscription.remove_bullet_confirm") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '{{ __("common.yes_delete") }}'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $btn.closest('.sub-bullet-row').remove();
            if ($container.find('.sub-bullet-row').length === 0) {
                $container.append('<div class="sub-bullet-row d-flex align-items-center gap-2 mb-2">' +
                    '<span class="sub-bullet-icon"><i class="bi bi-check-circle-fill" style="color:#d4a528;"></i></span>' +
                    '<input type="text" class="form-control sub-bullet-input" placeholder="{{ __('subscription.bullet_placeholder') }}">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger sub-remove-bullet d-none" title="{{ __('subscription.remove_bullet') }}">' +
                    '<i class="bi bi-trash"></i></button></div>');
            }
            toggleRemoveBullets(lang);
            // Auto-save so the deletion persists in Firestore
            var $panel = $('#sub-panel-' + lang);
            var bullets = [];
            $panel.find('.sub-bullet-input').each(function () {
                var v = $(this).val().trim();
                if (v) bullets.push(v);
            });
            $('#dlLoaderText').text('{{ __("common.saving") }}...');
            $('#dlOverlayLoader').addClass('active');
            $.ajax({
                url: saveUrl,
                method: 'POST',
                data: {
                    _token:         csrf,
                    lang:           lang,
                    title:          $panel.find('.sub-title').val().trim(),
                    subtitle:       $panel.find('.sub-subtitle').val().trim(),
                    button_text:    $panel.find('.sub-btn-text').val().trim(),
                    bullets:        bullets,
                    wa_title:       $panel.find('.sub-wa-title').val().trim(),
                    wa_subtitle:    $panel.find('.sub-wa-subtitle').val().trim(),
                    wa_button_text: $panel.find('.sub-wa-btn-text').val().trim(),
                },
                success: function (res) {
                    $('#dlOverlayLoader').removeClass('active');
                    if (res.success) {
                        toastr.success('{{ __("subscription.bullet_deleted") }}');
                    } else {
                        toastr.error(res.message);
                    }
                },
                error: function () {
                    $('#dlOverlayLoader').removeClass('active');
                    toastr.error('{{ __("common.something_wrong") }}');
                }
            });
        });
    });

    function toggleRemoveBullets(lang) {
        var $rows = $('#bulletsContainer_' + lang).find('.sub-bullet-row');
        $rows.find('.sub-remove-bullet').each(function (i) {
            if ($rows.length <= 1) {
                $(this).addClass('d-none');
            } else {
                $(this).removeClass('d-none');
            }
        });
    }

    var requiredMsg = '{{ __("common.required_field") }}';

    function showFieldError($field, msg) {
        $field.addClass('is-invalid');
        if (!$field.next('.sub-field-error').length) {
            $field.after('<div class="invalid-feedback sub-field-error" style="display:block;">' + msg + '</div>');
        }
    }

    function clearFieldErrors($panel) {
        $panel.find('.is-invalid').removeClass('is-invalid');
        $panel.find('.sub-field-error').remove();
    }

    $(document).on('input change', '.sub-title, .sub-subtitle, .sub-btn-text, .sub-wa-title, .sub-wa-subtitle, .sub-wa-btn-text', function () {
        $(this).removeClass('is-invalid');
        $(this).next('.sub-field-error').remove();
    });

    // Save
    $(document).on('click', '.sub-save-btn', function () {
        var lang       = $(this).data('lang');
        var $panel     = $('#sub-panel-' + lang);

        clearFieldErrors($panel);

        var title      = $panel.find('.sub-title').val().trim();
        var subtitle   = $panel.find('.sub-subtitle').val().trim();
        var buttonText = $panel.find('.sub-btn-text').val().trim();
        var waTitle      = $panel.find('.sub-wa-title').val().trim();
        var waSubtitle   = $panel.find('.sub-wa-subtitle').val().trim();
        var waButtonText = $panel.find('.sub-wa-btn-text').val().trim();

        var hasError = false;
        if (!title)        { showFieldError($panel.find('.sub-title'),       requiredMsg); hasError = true; }
        if (!subtitle)     { showFieldError($panel.find('.sub-subtitle'),    requiredMsg); hasError = true; }
        if (!buttonText)   { showFieldError($panel.find('.sub-btn-text'),    requiredMsg); hasError = true; }
        if (!waTitle)      { showFieldError($panel.find('.sub-wa-title'),    requiredMsg); hasError = true; }
        if (!waSubtitle)   { showFieldError($panel.find('.sub-wa-subtitle'), requiredMsg); hasError = true; }
        if (!waButtonText) { showFieldError($panel.find('.sub-wa-btn-text'), requiredMsg); hasError = true; }
        if (hasError) return;

        var bullets = [];
        $panel.find('.sub-bullet-input').each(function () {
            var v = $(this).val().trim();
            if (v) bullets.push(v);
        });

        if (bullets.length === 0) {
            toastr.error('{{ __("subscription.min_one_bullet") }}');
            return;
        }

        $('#dlLoaderText').text('{{ __("common.saving") }}...');
        $('#dlOverlayLoader').addClass('active');

        $.ajax({
            url: saveUrl,
            method: 'POST',
            data: {
                _token:        csrf,
                lang:          lang,
                title:         title,
                subtitle:      subtitle,
                button_text:   buttonText,
                bullets:       bullets,
                wa_title:      waTitle,
                wa_subtitle:   waSubtitle,
                wa_button_text: waButtonText,
            },
            success: function (res) {
                $('#dlOverlayLoader').removeClass('active');
                if (res.success) {
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function () {
                $('#dlOverlayLoader').removeClass('active');
                toastr.error('{{ __("common.something_wrong") }}');
            }
        });
    });

    // Init toggle on load
    ['pt', 'en', 'es'].forEach(function (lang) { toggleRemoveBullets(lang); });
});
</script>
@endsection
@endsection
