@php
    $isReadonly = $isReadonly ?? false;
    $isEdit = isset($dailyLight);
    $languages = [
        'pt' => __('common.portuguese_pt'),
        'en' => __('common.english_en'),
        'es' => __('common.spanish_es'),
    ];

    // Determine which optional languages are enabled
    $enabledLangs = ['pt'];
    if (old('lang_enabled_en', $isEdit && isset($dailyLight['translations']['en']))) $enabledLangs[] = 'en';
    if (old('lang_enabled_es', $isEdit && isset($dailyLight['translations']['es']))) $enabledLangs[] = 'es';

    // Default completion messages per language
    $completionDefaults = [
        'pt' => [
            'title'       => 'MAIS UM DIA ILUMINADO!',
            'description' => 'Você fortaleceu hoje o seu hábito de caminhar com Deus. Volte amanhã para continuar construindo sua Jornada de Luz.',
        ],
        'en' => [
            'title'       => 'ONE MORE ILLUMINATED DAY!',
            'description' => 'You strengthened your habit of walking with God today. Come back tomorrow to continue building your Journey of Light.',
        ],
        'es' => [
            'title'       => '¡UN DÍA MÁS ILUMINADO!',
            'description' => 'Fortaleciste hoy tu hábito de caminar con Dios. Vuelve mañana para continuar construyendo tu Jornada de Luz.',
        ],
    ];

    // Find first language tab with server-side errors
    $firstErrorTab = null;
    foreach (array_keys($languages) as $code) {
        if (!in_array($code, $enabledLangs)) continue;
        $hasLangError = $errors->has("title_{$code}") || $errors->has("description_{$code}");
        for ($s = 1; $s <= 5; $s++) {
            if ($errors->has("section_title_{$code}_{$s}")) $hasLangError = true;
        }
        if ($hasLangError && !$firstErrorTab) {
            $firstErrorTab = $code;
        }
    }
@endphp

@if($isEdit)
<input type="hidden" id="currentDocId" value="{{ $dailyLight['id'] }}">
@endif

<div class="card dl-form-card">

    {{-- Readonly Banner --}}
    @if($isReadonly)
    <div class="alert alert-warning d-flex align-items-center mb-0" style="border-radius: 0; border-bottom: 1px solid #e9e1cc; font-size: 14px;">
        <i class="bi bi-lock-fill me-2"></i>
        <span>{{ __('daily_lights.readonly_published_note') }}</span>
    </div>
    @endif

    {{-- Global Error Alert --}}
    @if($errors->any())
    <div class="alert alert-danger d-flex align-items-start mb-0" style="border-radius: 0; border-bottom: 1px solid #e9e1cc; font-size: 13px;">
        <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
        <div>
            <strong>{{ __('common.validation_errors') }}</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Global Session Error --}}
    @if(session('error'))
    <div class="alert alert-danger d-flex align-items-center mb-0" style="border-radius: 0; border-bottom: 1px solid #e9e1cc; font-size: 13px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    {{-- Header: Publish Date & Time --}}
    <div class="card-header dl-card-header">
        <div class="row align-items-center g-3">
            <div class="col-md-4">
                <label class="dl-label mb-1" for="publishDate">{{ __('daily_lights.publish_date') }} <span class="text-danger">*</span></label>
                @if($isReadonly)
                    <div class="form-control bg-light" style="pointer-events: none;">
                        {{ \Carbon\Carbon::parse($dailyLight['date'])->format('d-m-y') }}
                    </div>
                    <input type="hidden" name="publishDate" value="{{ $dailyLight['date'] }}">
                @else
                    <div class="input-group">
                        <input type="text" name="publishDate" id="publishDate"
                            class="form-control dl-validate-field @error('publishDate') is-invalid @enderror"
                            placeholder="dd-mm-yyyy"
                            value="{{ old('publishDate', $isEdit ? $dailyLight['date'] : '') }}">
                        <span class="input-group-text" id="publishDateIcon" style="cursor:pointer;"><i class="bi bi-calendar-event"></i></span>
                    </div>
                    {{-- <small class="text-muted" style="font-size:11px;">{{ __('daily_lights.auto_publish_note') }}</small> --}}
                    <div class="dl-date-error" id="dateExistsError">{{ __('daily_lights.date_exists') }}</div>
                    @error('publishDate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                @endif
            </div>
            <div class="col-md-3">
                <label class="dl-label mb-1" for="publishTime">{{ __('daily_lights.publish_time') }} <span class="text-danger">*</span></label>
                @if($isReadonly)
                    <div class="form-control bg-light" style="pointer-events: none;">
                        {{ $dailyLight['publishTime'] ?? '--:--' }}
                    </div>
                    <input type="hidden" name="publishTime" value="{{ $dailyLight['publishTime'] ?? '' }}">
                @else
                    <input type="time" name="publishTime" id="publishTime"
                        class="form-control @error('publishTime') is-invalid @enderror"
                        value="{{ old('publishTime', $isEdit ? ($dailyLight['publishTime'] ?? '') : '') }}">
                    {{-- <small class="text-muted" id="publishTimeHint" style="font-size:11px;">&nbsp;</small> --}}
                    <div class="text-danger small mt-1 d-none" id="publishTimeError" style="font-size:12px;"></div>
                    @error('publishTime')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                @endif
            </div>
        </div>
    </div>

    {{-- Language Tabs --}}
    <ul class="nav dl-lang-tabs px-4 pt-2" id="langTabs" role="tablist">
        @foreach($languages as $code => $label)
        <li class="nav-item" role="presentation">
            @php
                $isEnabled = in_array($code, $enabledLangs);
                $isActiveTab = $firstErrorTab ? ($firstErrorTab === $code) : ($code === 'pt');
                $hasContent = old("title_{$code}", $isEdit ? ($dailyLight['translations'][$code]['title'] ?? '') : '') !== '';
                $hasError = false;
                if ($isEnabled) {
                    $hasError = $errors->has("title_{$code}") || $errors->has("description_{$code}");
                    for ($s = 1; $s <= 5; $s++) {
                        if ($errors->has("section_title_{$code}_{$s}")) $hasError = true;
                    }
                }
            @endphp
            <button class="nav-link dl-lang-tab {{ $isActiveTab ? 'active' : '' }}"
                id="tab-{{ $code }}" data-bs-toggle="tab"
                data-bs-target="#panel-{{ $code }}" type="button" role="tab"
                data-lang="{{ $code }}">
                {{ $label }}
                @if($code !== 'pt')
                    <span class="dl-lang-dot {{ $hasError ? 'error' : ($isEnabled && $hasContent ? 'active' : '') }}"></span>
                @else
                    <span class="dl-lang-dot {{ $hasError ? 'error' : ($hasContent ? 'active' : '') }}"></span>
                @endif
            </button>
        </li>
        @endforeach
    </ul>

    {{-- Tab Content --}}
    <div class="card-body pt-0">
        <div class="tab-content pt-3" id="langTabContent">
            @foreach($languages as $code => $label)
            @php
                $isEnabled = in_array($code, $enabledLangs);
                $isActiveTab = $firstErrorTab ? ($firstErrorTab === $code) : ($code === 'pt');
            @endphp
            <div class="tab-pane fade {{ $isActiveTab ? 'show active' : '' }}" id="panel-{{ $code }}" role="tabpanel">

                {{-- Enable Toggle for EN/ES --}}
                @if($code !== 'pt')
                <div class="dl-enable-banner mb-3">
                    <div>
                        <strong>{{ __('common.enable_language', ['lang' => $label]) }}</strong>
                        <p class="mb-0 text-muted" style="font-size:12px;">{{ __('common.enable_lang_desc') }}</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input toggle-status lang-toggle" type="checkbox"
                            name="lang_enabled_{{ $code }}" value="1" id="langEnabled_{{ $code }}"
                            data-lang="{{ $code }}"
                            {{ $isEnabled ? 'checked' : '' }}
                            {{ $isReadonly ? 'disabled' : '' }}>
                    </div>
                </div>
                @endif

                {{-- Language form content (hidden if not enabled for EN/ES) --}}
                <div class="dl-lang-content {{ ($code !== 'pt' && !$isEnabled) ? 'd-none' : '' }}" id="langContent_{{ $code }}">

                    {{-- Title & Description --}}
                    <div class="row mb-4 g-3">
                        <div class="col-md-5">
                            <label class="dl-label">{{ __('common.title') }} ({{ strtoupper($code) }}) <span class="text-danger">*</span></label>
                            <input type="text" name="title_{{ $code }}"
                                class="form-control dl-validate-field @error("title_{$code}") is-invalid @enderror"
                                placeholder="{{ __('daily_lights.enter_title') }}"
                                value="{{ old("title_{$code}", $isEdit ? ($dailyLight['translations'][$code]['title'] ?? '') : '') }}"
                                {{ $isReadonly ? 'disabled' : '' }}>
                            @error("title_{$code}")
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-7">
                            <label class="dl-label">{{ __('common.description') }} ({{ strtoupper($code) }}) <span class="text-danger">*</span></label>
                            <textarea name="description_{{ $code }}"
                                class="form-control dl-validate-field @error("description_{$code}") is-invalid @enderror"
                                rows="1"
                                placeholder="{{ __('daily_lights.enter_description') }}"
                                {{ $isReadonly ? 'disabled' : '' }}>{{ old("description_{$code}", $isEdit ? ($dailyLight['translations'][$code]['description'] ?? '') : '') }}</textarea>
                            @error("description_{$code}")
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Content Steps --}}
                    <h6 class="dl-section-heading">{{ __('daily_lights.content_steps', ['lang' => strtoupper($code)]) }}</h6>

                    @for($i = 1; $i <= 5; $i++)
                    @php
                        $sec = $isEdit ? ($dailyLight['translations'][$code]['steps'][$i - 1] ?? []) : [];
                        $hasImage = !empty($sec['image_url']);
                        $hasAudio = !empty($sec['audio_url']);
                    @endphp
                    <div class="dl-section-card">
                        <div class="dl-section-header">
                            <strong>{{ __("daily_lights.step_{$i}") }}</strong>
                            <div class="d-flex align-items-center gap-3">
                                @if($i === 5)
                                @php
                                    $forSubscribe = $isEdit ? ($sec['forSubscribeMember'] ?? false) : false;
                                @endphp
                                <label class="d-flex align-items-center gap-2 m-0" id="subscriberBox_{{ $code }}" for="forSubscribeMember_{{ $code }}" style="cursor:pointer;">
                                    <input type="checkbox" id="forSubscribeMember_{{ $code }}" name="forSubscribeMember_{{ $code }}" value="1"
                                        class="subscriber-check"
                                        style="width:17px;height:17px;cursor:pointer;accent-color:#d4a528;flex-shrink:0;"
                                        {{ $forSubscribe ? 'checked' : '' }}
                                        {{ $isReadonly ? 'disabled' : '' }}>
                                    <span class="fw-bold" style="font-size:15px;color:#5a4a2f;white-space:nowrap;">
                                        <i class="bi bi-star-fill me-1" style="color:#d4a528;font-size:13px;"></i>{{ __('daily_lights.subscribed_users_only') }}
                                    </span>
                                </label>
                                @endif
                                @if(!$isReadonly)
                                <button type="button" class="dl-clear-btn dl-clear-section"
                                    data-lang="{{ $code }}" data-section="{{ $i }}">{{ __('common.clear') }}</button>
                                @endif
                            </div>
                        </div>

                        @if($i <= 4)
                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="dl-label">{{ __('common.select_icon') }} <span class="text-danger">*</span></label>
                                @php $stepCatId = old("step_category_{$code}_{$i}", $sec['category_id'] ?? ''); @endphp
                                <input type="hidden" name="step_category_{{ $code }}_{{ $i }}" id="stepCat_{{ $code }}_{{ $i }}" value="{{ $stepCatId }}">
                                <div class="dlc-custom-select {{ $isReadonly ? 'dlc-readonly' : '' }}" id="dlcSelect_{{ $code }}_{{ $i }}" data-lang="{{ $code }}" data-step="{{ $i }}">
                                    <div class="dlc-trigger">
                                        @php
                                            $selectedCat = null;
                                            foreach (($categories ?? []) as $c) { if ($c['id'] === $stepCatId) { $selectedCat = $c; break; } }
                                        @endphp
                                        @if($selectedCat)
                                            <span class="dlc-trigger-val">
                                                @php $selIcon = $selectedCat['icon_dropdown_url'] ?? $selectedCat['icon_url'] ?? ''; @endphp
                                                @if($selIcon)<img src="{{ $selIcon }}" class="dlc-opt-icon {{ $selectedCat['icon_dropdown_url'] ? '' : 'dlc-opt-icon-legacy' }}">@endif
                                                <span>{{ $selectedCat['title_' . $code] ?? $selectedCat['title'] }}</span>
                                            </span>
                                        @else
                                            <span class="dlc-trigger-ph"><i class="bi bi-grid me-1"></i>{{ __('common.select_icon') }}</span>
                                        @endif
                                        <i class="bi bi-chevron-down dlc-arrow"></i>
                                    </div>
                                    <div class="dlc-dropdown">
                                        @foreach($categories ?? [] as $cat)
                                        @php $ddIcon = $cat['icon_dropdown_url'] ?? $cat['icon_url'] ?? ''; @endphp
                                        <div class="dlc-opt" data-value="{{ $cat['id'] }}" data-icon="{{ $ddIcon }}" data-has-dropdown="{{ $cat['icon_dropdown_url'] ? '1' : '0' }}" data-title="{{ $cat['title_' . $code] ?? $cat['title'] }}">
                                            @if($ddIcon)<img src="{{ $ddIcon }}" class="dlc-opt-icon {{ $cat['icon_dropdown_url'] ? '' : 'dlc-opt-icon-legacy' }}">@endif
                                            <span>{{ $cat['title_' . $code] ?? $cat['title'] }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="dl-label">{{ __('common.title') }} <span class="text-danger">*</span></label>
                                <input type="text" name="section_title_{{ $code }}_{{ $i }}"
                                    class="form-control dl-validate-field @error("section_title_{$code}_{$i}") is-invalid @enderror"
                                    placeholder="{{ __('daily_lights.enter_title') }}"
                                    value="{{ old("section_title_{$code}_{$i}", $sec['title'] ?? '') }}"
                                    {{ $isReadonly ? 'disabled' : '' }}>
                                @error("section_title_{$code}_{$i}")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        @else
                        <div class="mb-3">
                            <label class="dl-label">{{ __('common.title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="section_title_{{ $code }}_{{ $i }}"
                                class="form-control dl-validate-field @error("section_title_{$code}_{$i}") is-invalid @enderror"
                                placeholder="{{ __('daily_lights.enter_title') }}"
                                value="{{ old("section_title_{$code}_{$i}", $sec['title'] ?? '') }}"
                                {{ $isReadonly ? 'disabled' : '' }}>
                            @error("section_title_{$code}_{$i}")
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="dl-label">{{ __('common.description') }} <span class="text-danger">*</span></label>
                            <textarea name="section_description_{{ $code }}_{{ $i }}"
                                class="form-control dl-validate-field @error("section_description_{$code}_{$i}") is-invalid @enderror"
                                rows="2"
                                placeholder="{{ __('daily_lights.enter_description') }}"
                                {{ $isReadonly ? 'disabled' : '' }}>{{ old("section_description_{$code}_{$i}", $sec['description'] ?? '') }}</textarea>
                            @error("section_description_{$code}_{$i}")
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3">
                            {{-- Image Upload --}}
                            <div class="{{ $i === 5 ? 'col-md-4' : 'col-md-6' }}">
                                <label class="dl-upload-label"><i class="bi bi-image me-1"></i>{{ __('common.upload_image') }} <span class="text-danger">*</span></label>
                                <div class="dl-upload-zone {{ $isReadonly ? 'dl-upload-readonly' : '' }}">
                                    @if(!$isReadonly)
                                    <input type="file" name="section_image_{{ $code }}_{{ $i }}"
                                        accept="image/png,image/jpeg,image/jpg"
                                        class="dl-file-input" id="imageInput_{{ $code }}_{{ $i }}"
                                        data-preview="imagePreview_{{ $code }}_{{ $i }}"
                                        data-type="image">

                                    <div class="dl-upload-placeholder {{ $hasImage ? 'd-none' : '' }}" id="imagePlaceholder_{{ $code }}_{{ $i }}">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                        <span>{{ __('common.upload_image') }}</span>
                                        <small>{{ __('common.image_size_info') }}</small>
                                        <div class="dl-ideal-size"><i class="bi bi-lightbulb me-1"></i>{{ __('common.ideal_size', ['size' => '1080 x 1080px']) }}</div>
                                    </div>
                                    @endif

                                    <div class="dl-upload-preview {{ $hasImage ? '' : 'd-none' }}" id="imagePreview_{{ $code }}_{{ $i }}">
                                        <img src="{{ $hasImage ? $sec['image_url'] : '' }}" alt="Preview" class="dl-preview-img">
                                        @if(!$isReadonly)
                                        <button type="button" class="dl-preview-remove"
                                            data-target="imageInput_{{ $code }}_{{ $i }}"
                                            data-placeholder="imagePlaceholder_{{ $code }}_{{ $i }}"
                                            data-preview="imagePreview_{{ $code }}_{{ $i }}">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($i === 5)
                            {{-- Background Image Upload (Step 5 only) --}}
                            @php $hasBgImage = !empty($sec['bg_image_url']); @endphp
                            <div class="col-md-4">
                                <label class="dl-upload-label"><i class="bi bi-images me-1"></i>{{ __('common.upload_bg_image') }} <span class="text-danger">*</span></label>
                                <div class="dl-upload-zone {{ $isReadonly ? 'dl-upload-readonly' : '' }}">
                                    @if(!$isReadonly)
                                    <input type="file" name="section_bg_image_{{ $code }}_{{ $i }}"
                                        accept="image/png,image/jpeg,image/jpg"
                                        class="dl-file-input" id="bgImageInput_{{ $code }}_{{ $i }}"
                                        data-preview="bgImagePreview_{{ $code }}_{{ $i }}"
                                        data-type="image">

                                    <div class="dl-upload-placeholder {{ $hasBgImage ? 'd-none' : '' }}" id="bgImagePlaceholder_{{ $code }}_{{ $i }}">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                        <span>{{ __('common.upload_bg_image') }}</span>
                                        <small>{{ __('common.image_size_info') }}</small>
                                        <div class="dl-ideal-size"><i class="bi bi-lightbulb me-1"></i>{{ __('common.ideal_size', ['size' => '1080 x 605px']) }}</div>
                                    </div>
                                    @endif

                                    <div class="dl-upload-preview {{ $hasBgImage ? '' : 'd-none' }}" id="bgImagePreview_{{ $code }}_{{ $i }}">
                                        <img src="{{ $hasBgImage ? $sec['bg_image_url'] : '' }}" alt="Preview" class="dl-preview-img">
                                        @if(!$isReadonly)
                                        <button type="button" class="dl-preview-remove"
                                            data-target="bgImageInput_{{ $code }}_{{ $i }}"
                                            data-placeholder="bgImagePlaceholder_{{ $code }}_{{ $i }}"
                                            data-preview="bgImagePreview_{{ $code }}_{{ $i }}">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- Audio Upload --}}
                            <div class="{{ $i === 5 ? 'col-md-4' : 'col-md-6' }}">
                                <label class="dl-upload-label"><i class="bi bi-mic me-1"></i>{{ __('common.upload_audio') }} <span class="text-danger">*</span></label>
                                <div class="dl-upload-zone {{ $isReadonly ? 'dl-upload-readonly' : '' }}">
                                    @if(!$isReadonly)
                                    <input type="file" name="section_audio_{{ $code }}_{{ $i }}"
                                        accept="audio/mpeg,audio/wav,audio/mp3"
                                        class="dl-file-input" id="audioInput_{{ $code }}_{{ $i }}"
                                        data-preview="audioPreview_{{ $code }}_{{ $i }}"
                                        data-type="audio">

                                    <div class="dl-upload-placeholder {{ $hasAudio ? 'd-none' : '' }}" id="audioPlaceholder_{{ $code }}_{{ $i }}">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                        <span>{{ __('common.upload_audio') }}</span>
                                        <small>{{ __('common.audio_size_info') }}</small>
                                    </div>
                                    @endif

                                    <div class="dl-upload-preview {{ $hasAudio ? '' : 'd-none' }}" id="audioPreview_{{ $code }}_{{ $i }}">
                                        <div class="dl-audio-info">
                                            <i class="bi bi-file-earmark-music"></i>
                                            <span class="dl-audio-name">{{ $hasAudio ? basename($sec['audio'] ?? 'audio') : '' }}</span>
                                        </div>
                                        <audio controls class="dl-audio-player">
                                            @if($hasAudio)<source src="{{ $sec['audio_url'] }}" type="audio/mpeg">@endif
                                        </audio>

                                        @if(!$isReadonly)
                                        <button type="button" class="dl-preview-remove"
                                            data-target="audioInput_{{ $code }}_{{ $i }}"
                                            data-placeholder="audioPlaceholder_{{ $code }}_{{ $i }}"
                                            data-preview="audioPreview_{{ $code }}_{{ $i }}">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    @endfor

                    {{-- Completion Messages --}}
                    @php
                        $cTitle = old("completion_title_{$code}", $isEdit ? ($dailyLight['translations'][$code]['completionTitle'] ?? $completionDefaults[$code]['title']) : $completionDefaults[$code]['title']);
                        $cDesc  = old("completion_description_{$code}", $isEdit ? ($dailyLight['translations'][$code]['completionDescription'] ?? $completionDefaults[$code]['description']) : $completionDefaults[$code]['description']);
                    @endphp
                    <div class="dl-notif-section-lang mt-4">
                        <label class="mb-3 d-block" style="font-size:16px;font-weight:600;color:#3d2e0a;">
                            <i class="bi bi-trophy me-1" style="color:#c6a55a;"></i> {{ __('daily_lights.completion_messages') }} ({{ strtoupper($code) }})
                        </label>
                        <div style="background:#faf6ed;border:1px solid #e9e1cc;border-radius:8px;padding:16px;">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="dl-label">{{ __('daily_lights.completion_title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="completion_title_{{ $code }}"
                                        class="form-control dl-validate-field @error("completion_title_{$code}") is-invalid @enderror"
                                        placeholder="{{ __('daily_lights.enter_title') }}"
                                        value="{{ $cTitle }}"
                                        {{ $isReadonly ? 'disabled' : '' }}>
                                    @error("completion_title_{$code}")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-7">
                                    <label class="dl-label">{{ __('daily_lights.completion_description') }} <span class="text-danger">*</span></label>
                                    <textarea name="completion_description_{{ $code }}"
                                        class="form-control dl-validate-field @error("completion_description_{$code}") is-invalid @enderror"
                                        rows="2"
                                        placeholder="{{ __('daily_lights.enter_description') }}"
                                        {{ $isReadonly ? 'disabled' : '' }}>{{ $cDesc }}</textarea>
                                    @error("completion_description_{$code}")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Send Notification (per-language) — saves to DB only, does NOT send --}}
                    @php
                        $notif = $isEdit ? ($dailyLight['translations'][$code]['notification'] ?? []) : [];
                        $notifEnabled = old("send_notification_{$code}", $notif['enabled'] ?? false);
                    @endphp
                    <div class="dl-notif-section-lang mt-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="mb-0" style="font-size: 16px; font-weight: 600; color: #3d2e0a;">
                                <i class="bi bi-bell me-1" style="color: #c6a55a;"></i> {{ __('daily_light_categories.send_notification') }} ({{ strtoupper($code) }})
                            </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input dl-notif-toggle" type="checkbox"
                                    name="send_notification_{{ $code }}" id="dlSendNotification_{{ $code }}" value="1"
                                    data-lang="{{ $code }}"
                                    {{ $notifEnabled ? 'checked' : '' }}
                                    {{ $isReadonly ? 'disabled' : '' }}
                                    style="width: 2.8em; height: 1.4em; cursor: pointer;">
                            </div>
                        </div>
                        <div id="dlNotifFields_{{ $code }}" class="{{ $notifEnabled ? '' : 'd-none' }}" style="margin-top: 14px; background: #faf6ed; border: 1px solid #e9e1cc; border-radius: 8px; padding: 16px;">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="dl-label">{{ __('daily_light_categories.notif_title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="notif_title_{{ $code }}" id="dlNotifTitle_{{ $code }}" class="form-control"
                                        placeholder="{{ __('daily_light_categories.notif_title') }}"
                                        value="{{ old("notif_title_{$code}", $notif['title'] ?? '') }}"
                                        {{ $isReadonly ? 'disabled' : '' }}>
                                    <div class="text-danger small d-none mt-1" id="dlNotifTitleError_{{ $code }}"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="dl-label">{{ __('daily_light_categories.notif_time') }} <span class="text-danger">*</span></label>
                                    <input type="time" name="notif_time_{{ $code }}" id="dlNotifTime_{{ $code }}" class="form-control"
                                        value="{{ old("notif_time_{$code}", $notif['time'] ?? '') }}"
                                        {{ $isReadonly ? 'disabled' : '' }}>
                                    <div class="text-danger small d-none mt-1" id="dlNotifTimeError_{{ $code }}"></div>
                                </div>
                                <div class="col-12">
                                    <label class="dl-label">{{ __('daily_light_categories.notif_message') }} <span class="text-danger">*</span></label>
                                    <textarea name="notif_message_{{ $code }}" id="dlNotifMessage_{{ $code }}" class="form-control" rows="2"
                                        placeholder="{{ __('daily_light_categories.notif_message') }}"
                                        {{ $isReadonly ? 'disabled' : '' }}>{{ old("notif_message_{$code}", $notif['message'] ?? '') }}</textarea>
                                    <div class="text-danger small d-none mt-1" id="dlNotifMessageError_{{ $code }}"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Footer: Cancel + Save --}}
    <div class="card-footer dl-card-footer">
        <a href="{{ route('daily-lights.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
        @if(!$isReadonly)
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? __('daily_lights.update_daily_light') : __('daily_lights.save_daily_light') }}
        </button>
        @endif
    </div>

</div>

<style>
/* Custom Category Dropdown */
.dlc-custom-select { position: relative; }
.dlc-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 12px;
    background: #fff;
    border: 1px solid #d5c9a8;
    border-radius: 6px;
    cursor: pointer;
    color: #2c2108;
    min-height: 38px;
    transition: border-color .2s;
}
.dlc-trigger:hover { border-color: #c6a55a; }
.dlc-trigger-error { border-color: #dc3545 !important; }
.dlc-trigger-ph { opacity: .5; font-size: 13px; }
.dlc-trigger-val { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; }
.dlc-arrow { font-size: 12px; opacity: .6; transition: transform .2s; }
.dlc-custom-select.open .dlc-arrow { transform: rotate(180deg); }
.dlc-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0; right: 0;
    background: #fff;
    border: 1px solid #d5c9a8;
    border-radius: 6px;
    z-index: 50;
    display: none;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0,0,0,.1);
}
.dlc-custom-select.open .dlc-dropdown { display: block; }
.dlc-opt {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    cursor: pointer;
    color: #2c2108;
    font-size: 14px;
    font-weight: 500;
    transition: background .15s;
}
.dlc-opt:hover { background: #f5edd6; }
.dlc-opt.selected { background: #ebe1c7; }
.dlc-opt-icon {
    width: 24px; height: 24px;
    border-radius: 4px;
    object-fit: contain;
    flex-shrink: 0;
}
.dlc-opt-icon-legacy {
    filter: brightness(0) sepia(1) saturate(3) hue-rotate(15deg) brightness(0.4);
}
/* Send Notification Section (per-language) */
.dl-notif-section-lang {
    border-top: 1px solid #e9e1cc;
    padding: 16px 0 0;
}
.dl-notif-section-lang .form-check-input:checked {
    background-color: #c6a55a;
    border-color: #c6a55a;
}
.dl-notif-section-lang .form-control {
    border-color: #d5c9a8;
}
.dl-notif-section-lang .form-control:focus {
    border-color: #c6a55a;
    box-shadow: 0 0 0 .2rem rgba(198,165,90,.15);
}
/* Readonly styles */
.dlc-readonly .dlc-trigger { pointer-events: none; opacity: 0.7; background: #f8f9fa; }
.dl-upload-readonly { pointer-events: none; opacity: 0.85; background: #f8f9fa; }
.dl-upload-readonly .dl-upload-preview { pointer-events: auto; }
.dl-upload-readonly audio { pointer-events: auto; }
</style>
