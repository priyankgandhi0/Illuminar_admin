@php
    $isEdit = isset($jornada);
    $languages = [
        'pt' => __('common.portuguese_pt'),
        'en' => __('common.english_en'),
        'es' => __('common.spanish_es'),
    ];

    // Determine which optional languages are enabled
    $enabledLangs = ['pt'];
    if (old('lang_enabled_en', $isEdit && isset($jornada['translations']['en']))) $enabledLangs[] = 'en';
    if (old('lang_enabled_es', $isEdit && isset($jornada['translations']['es']))) $enabledLangs[] = 'es';

    // Build categories list for dropdown
    $categoriesList = [];
    foreach (($categories ?? []) as $catId => $catFields) {
        $categoriesList[] = [
            'id' => $catId,
            'pt_title' => $catFields['pt_title']['stringValue'] ?? '',
            'en_title' => $catFields['en_title']['stringValue'] ?? '',
            'es_title' => $catFields['es_title']['stringValue'] ?? '',
        ];
    }

    // Find first language tab with server-side errors
    $firstErrorTab = null;
    foreach (array_keys($languages) as $code) {
        if (!in_array($code, $enabledLangs)) continue;
        $hasLangError = $errors->has("title_{$code}") || $errors->has("description_{$code}") || $errors->has("cover_image_{$code}");
        if ($hasLangError && !$firstErrorTab) {
            $firstErrorTab = $code;
        }
    }

    // lessonsByLang is passed separately in edit mode
    if (!isset($lessonsByLang)) $lessonsByLang = [];
@endphp

{{-- Per-language lesson count hidden inputs (both create and edit) --}}
@foreach(['pt', 'en', 'es'] as $langCode)
    @php
        $initCount = $isEdit ? count($lessonsByLang[$langCode] ?? []) : ($langCode === 'pt' ? 1 : 0);
    @endphp
    <input type="hidden" name="lesson_count_{{ $langCode }}" id="lessonCount_{{ $langCode }}" value="{{ $initCount }}">
@endforeach

{{-- Lesson doc ID (edit mode) --}}
@if($isEdit && isset($lessonDocId) && $lessonDocId)
    <input type="hidden" name="lesson_doc_id" value="{{ $lessonDocId }}">
@endif

{{-- Jornada doc ID (used by sequential AJAX save) --}}
<input type="hidden" id="jornadaDocId" value="{{ $isEdit ? $jornada['id'] : '' }}">

<div class="card dl-form-card">

    {{-- Hidden category_id (synced by JS from per-language dropdowns) --}}
    <input type="hidden" name="category_id" id="categoryId"
        value="{{ old('category_id', $isEdit ? ($jornada['category_id'] ?? '') : '') }}">

    {{-- Header: Status --}}
    <div class="card-header dl-card-header">
        <div class="row align-items-center g-3">
            <div class="col-md-4">
                <label class="dl-label mb-1" for="status">{{ __('common.status') }} <span class="text-danger">*</span></label>
                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="draft" {{ old('status', $isEdit ? $jornada['status'] : 'draft') === 'draft' ? 'selected' : '' }}>{{ __('common.draft') }}</option>
                    <option value="published" {{ old('status', $isEdit ? $jornada['status'] : '') === 'published' ? 'selected' : '' }}>{{ __('common.published') }}</option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
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
                $hasError = false;
                if ($isEnabled) {
                    $hasError = $errors->has("title_{$code}") || $errors->has("description_{$code}") || $errors->has("cover_image_{$code}");
                }
            @endphp
            <button class="nav-link dl-lang-tab {{ $isActiveTab ? 'active' : '' }}"
                id="tab-{{ $code }}" data-bs-toggle="tab"
                data-bs-target="#panel-{{ $code }}" type="button" role="tab"
                data-lang="{{ $code }}">
                {{ $label }}
                <span class="dl-lang-dot {{ $hasError ? 'error' : ($isEnabled ? 'active' : '') }}"></span>
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
                $trans = $isEdit ? ($jornada['translations'][$code] ?? []) : [];
                $hasImage = !empty($trans['image_url']);
                $catsForLang = array_filter($categoriesList, fn($c) => !empty($c["{$code}_title"]));
                $hasCatsForLang = count($catsForLang) > 0;
                $selectedCatId = old('category_id', $isEdit ? ($jornada['category_id'] ?? '') : '');
                if ($code !== 'pt' && !$hasCatsForLang) $isEnabled = false;
                $langLessons = $lessonsByLang[$code] ?? [];
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
                            {{ !$hasCatsForLang ? 'disabled' : '' }}>
                    </div>
                </div>

                @if(!$hasCatsForLang)
                <div class="alert alert-warning mb-3" style="font-size:0.875em;">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    {{ __('jornadas.no_categories', ['lang' => $label]) }}
                    <a href="{{ route('jornada-categories.index') }}">{{ __('jornadas.add_category') }}</a> {{ __('jornadas.add_category_translation_first', ['lang' => $label]) }}
                </div>
                @endif
                @endif

                {{-- Language form content --}}
                @if($code === 'pt' || $hasCatsForLang)
                <div class="dl-lang-content {{ ($code !== 'pt' && !$isEnabled) ? 'd-none' : '' }}" id="langContent_{{ $code }}">

                    {{-- Journey Details Heading --}}
                    <h6 class="dl-section-heading">{{ __('jornadas.journey_details', ['lang' => strtoupper($code)]) }}</h6>

                    {{-- Category Dropdown --}}
                    <div class="row mb-4 g-3">
                        <div class="col-md-5">
                            <label class="dl-label">{{ __('common.category') }} ({{ strtoupper($code) }}) <span class="text-danger">*</span></label>
                            <select class="form-select jn-category-select @error('category_id') is-invalid @enderror" data-lang="{{ $code }}">
                                <option value="">{{ __('common.select_category') }}</option>
                                @foreach($catsForLang as $cat)
                                <option value="{{ $cat['id'] }}" {{ $selectedCatId === $cat['id'] ? 'selected' : '' }}>
                                    {{ $cat["{$code}_title"] }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Title & Description --}}
                    <div class="row mb-4 g-3">
                        <div class="col-md-5">
                            <label class="dl-label">{{ __('common.title') }} ({{ strtoupper($code) }}) <span class="text-danger">*</span></label>
                            <input type="text" name="title_{{ $code }}"
                                class="form-control dl-validate-field @error("title_{$code}") is-invalid @enderror"
                                placeholder="{{ __('jornadas.enter_title') }}"
                                value="{{ old("title_{$code}", $trans['title'] ?? '') }}">
                            @error("title_{$code}")
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-7">
                            <label class="dl-label">{{ __('common.description') }} ({{ strtoupper($code) }}) <span class="text-danger">*</span></label>
                            <textarea name="description_{{ $code }}"
                                class="form-control dl-validate-field @error("description_{$code}") is-invalid @enderror"
                                rows="1"
                                placeholder="{{ __('jornadas.enter_description') }}">{{ old("description_{$code}", $trans['description'] ?? '') }}</textarea>
                            @error("description_{$code}")
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Cover Image --}}
                    <div class="jn-cover-section mb-4">
                        <input type="hidden" id="existingCoverImage_{{ $code }}" value="{{ $isEdit ? ($trans['image'] ?? '') : '' }}">
                        <label class="dl-upload-label"><i class="bi bi-image me-1"></i>{{ __('jornadas.cover_image', ['lang' => strtoupper($code)]) }} <span class="text-danger">*</span></label>
                        <div class="dl-upload-zone">
                            <input type="file" name="cover_image_{{ $code }}"
                                accept="image/png,image/jpeg,image/jpg"
                                class="dl-file-input" id="coverInput_{{ $code }}"
                                data-preview="coverPreview_{{ $code }}"
                                data-type="image">

                            <div class="dl-upload-placeholder {{ $hasImage ? 'd-none' : '' }}" id="coverPlaceholder_{{ $code }}">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <span>{{ __('jornadas.upload_cover') }}</span>
                                <small>{{ __('common.image_size_info') }}</small>
                                <div class="dl-ideal-size"><i class="bi bi-lightbulb me-1"></i>{{ __('common.ideal_size', ['size' => '1080 x 1080px']) }}</div>
                            </div>

                            <div class="dl-upload-preview {{ $hasImage ? '' : 'd-none' }}" id="coverPreview_{{ $code }}">
                                <img src="{{ $hasImage ? $trans['image_url'] : '' }}" alt="Preview" class="dl-preview-img">
                                <button type="button" class="dl-preview-remove"
                                    data-target="coverInput_{{ $code }}"
                                    data-placeholder="coverPlaceholder_{{ $code }}"
                                    data-preview="coverPreview_{{ $code }}">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>
                        </div>
                        @error("cover_image_{$code}")
                            <div class="text-danger" style="font-size:0.875em;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Lessons Section --}}
                    <h6 class="dl-section-heading">{{ __('jornadas.lessons', ['lang' => strtoupper($code)]) }}</h6>

                    <div class="jn-lessons-container" id="lessonsContainer_{{ $code }}">
                        @if(!$isEdit && $code === 'pt')
                            {{-- Only PT gets an initial lesson in create mode --}}
                            <div class="dl-section-card jn-lesson-card" data-lesson-key="0">
                                <input type="hidden" name="lesson_existing_audio_{{ $code }}_0" class="jn-existing-audio" value="">
                                <input type="hidden" name="lesson_existing_duration_{{ $code }}_0" class="jn-existing-duration" value="">

                                <div class="dl-section-header">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="jn-lesson-drag-handle" style="cursor: grab; color: #aaa;"><i class="bi bi-grip-vertical"></i></span>
                                        <strong class="jn-lesson-number">{{ __('jornadas.lesson', ['number' => 1]) }}</strong>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <label class="d-flex align-items-center gap-2 m-0" for="lessonSubscribersOnly_{{ $code }}_0" style="cursor:pointer;">
                                            <input type="checkbox" class="jn-subscribers-only"
                                                name="lesson_subscribers_only_{{ $code }}_0"
                                                id="lessonSubscribersOnly_{{ $code }}_0"
                                                value="1"
                                                style="width:17px;height:17px;cursor:pointer;accent-color:#d4a528;flex-shrink:0;">
                                            <span class="fw-bold" style="font-size:15px;color:#5a4a2f;white-space:nowrap;">
                                                <i class="bi bi-star-fill me-1" style="color:#d4a528;font-size:13px;"></i>{{ __('jornadas.subscribers_only') }}?
                                            </span>
                                        </label>
                                        <button type="button" class="btn btn-sm btn-outline-danger jn-remove-lesson d-none" title="Remove lesson">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="dl-label">{{ __('common.title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="lesson_title_{{ $code }}_0"
                                        class="form-control dl-validate-field"
                                        placeholder="{{ __('jornadas.enter_lesson_title') }}"
                                        value="{{ old("lesson_title_{$code}_0") }}">
                                </div>

                                <div class="mb-3">
                                    <label class="dl-label">{{ __('common.description') }} <span class="text-danger">*</span></label>
                                    <textarea name="lesson_description_{{ $code }}_0"
                                        class="form-control dl-validate-field"
                                        rows="2"
                                        placeholder="{{ __('jornadas.enter_lesson_description') }}">{{ old("lesson_description_{$code}_0") }}</textarea>
                                </div>

                                <div>
                                    <label class="dl-upload-label"><i class="bi bi-mic me-1"></i>{{ __('common.upload_audio') }} <span class="text-danger">*</span></label>
                                    <div class="dl-upload-zone">
                                        <input type="file" name="lesson_audio_{{ $code }}_0"
                                            accept="audio/mpeg,audio/wav,audio/mp3"
                                            class="dl-file-input" id="lessonAudioInput_{{ $code }}_0"
                                            data-preview="lessonAudioPreview_{{ $code }}_0"
                                            data-type="audio">

                                        <div class="dl-upload-placeholder" id="lessonAudioPlaceholder_{{ $code }}_0">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                            <span>{{ __('common.upload_audio') }}</span>
                                            <small>{{ __('common.audio_size_info') }}</small>
                                        </div>

                                        <div class="dl-upload-preview d-none" id="lessonAudioPreview_{{ $code }}_0">
                                            <div class="dl-audio-info">
                                                <i class="bi bi-file-earmark-music"></i>
                                                <span class="dl-audio-name"></span>
                                            </div>
                                            <audio controls class="dl-audio-player"></audio>
                                            <button type="button" class="dl-preview-remove"
                                                data-target="lessonAudioInput_{{ $code }}_0"
                                                data-placeholder="lessonAudioPlaceholder_{{ $code }}_0"
                                                data-preview="lessonAudioPreview_{{ $code }}_0">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Existing lessons (edit mode) --}}
                        @foreach($langLessons as $idx => $lesson)
                        @php
                            $hasAudio = !empty($lesson['audio_url']);
                        @endphp
                        <div class="dl-section-card jn-lesson-card" data-lesson-key="{{ $idx }}">
                            <input type="hidden" name="lesson_existing_audio_{{ $code }}_{{ $idx }}" class="jn-existing-audio" value="{{ $lesson['audio_path'] ?? '' }}">
                            <input type="hidden" name="lesson_existing_duration_{{ $code }}_{{ $idx }}" class="jn-existing-duration" value="{{ $lesson['audioDuration'] ?? '' }}">

                            <div class="dl-section-header">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="jn-lesson-drag-handle" style="cursor: grab; color: #aaa;"><i class="bi bi-grip-vertical"></i></span>
                                    <strong class="jn-lesson-number">{{ __('jornadas.lesson', ['number' => $idx + 1]) }}</strong>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <label class="d-flex align-items-center gap-2 m-0" for="lessonSubscribersOnly_{{ $code }}_{{ $idx }}" style="cursor:pointer;">
                                        <input type="checkbox" class="jn-subscribers-only"
                                            name="lesson_subscribers_only_{{ $code }}_{{ $idx }}"
                                            id="lessonSubscribersOnly_{{ $code }}_{{ $idx }}"
                                            value="1"
                                            {{ ($lesson['subscribers_only'] ?? false) ? 'checked' : '' }}
                                            style="width:17px;height:17px;cursor:pointer;accent-color:#d4a528;flex-shrink:0;">
                                        <span class="fw-bold" style="font-size:15px;color:#5a4a2f;white-space:nowrap;">
                                            <i class="bi bi-star-fill me-1" style="color:#d4a528;font-size:13px;"></i>{{ __('jornadas.subscribers_only') }}?
                                        </span>
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-danger jn-remove-lesson {{ count($langLessons) <= 1 ? 'd-none' : '' }}" title="Remove lesson">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="dl-label">{{ __('common.title') }} <span class="text-danger">*</span></label>
                                <input type="text" name="lesson_title_{{ $code }}_{{ $idx }}"
                                    class="form-control dl-validate-field"
                                    placeholder="{{ __('jornadas.enter_lesson_title') }}"
                                    value="{{ old("lesson_title_{$code}_{$idx}", $lesson['title'] ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="dl-label">{{ __('common.description') }} <span class="text-danger">*</span></label>
                                <textarea name="lesson_description_{{ $code }}_{{ $idx }}"
                                    class="form-control dl-validate-field"
                                    rows="2"
                                    placeholder="{{ __('jornadas.enter_lesson_description') }}">{{ old("lesson_description_{$code}_{$idx}", $lesson['description'] ?? '') }}</textarea>
                            </div>

                            <div>
                                <label class="dl-upload-label"><i class="bi bi-mic me-1"></i>{{ __('common.upload_audio') }} <span class="text-danger">*</span></label>
                                <div class="dl-upload-zone">
                                    <input type="file" name="lesson_audio_{{ $code }}_{{ $idx }}"
                                        accept="audio/mpeg,audio/wav,audio/mp3"
                                        class="dl-file-input" id="lessonAudioInput_{{ $code }}_{{ $idx }}"
                                        data-preview="lessonAudioPreview_{{ $code }}_{{ $idx }}"
                                        data-type="audio">

                                    <div class="dl-upload-placeholder {{ $hasAudio ? 'd-none' : '' }}" id="lessonAudioPlaceholder_{{ $code }}_{{ $idx }}">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                        <span>{{ __('common.upload_audio') }}</span>
                                        <small>{{ __('common.audio_size_info') }}</small>
                                    </div>

                                    <div class="dl-upload-preview {{ $hasAudio ? '' : 'd-none' }}" id="lessonAudioPreview_{{ $code }}_{{ $idx }}">
                                        <div class="dl-audio-info">
                                            <i class="bi bi-file-earmark-music"></i>
                                            <span class="dl-audio-name">{{ $hasAudio ? basename($lesson['audio_path'] ?? 'audio') : '' }}</span>
                                        </div>
                                        <audio controls class="dl-audio-player">
                                            @if($hasAudio)<source src="{{ $lesson['audio_url'] }}" type="audio/mpeg">@endif
                                        </audio>
                                        <button type="button" class="dl-preview-remove"
                                            data-target="lessonAudioInput_{{ $code }}_{{ $idx }}"
                                            data-placeholder="lessonAudioPlaceholder_{{ $code }}_{{ $idx }}"
                                            data-preview="lessonAudioPreview_{{ $code }}_{{ $idx }}">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Add Lesson Button --}}
                    <button type="button" class="btn jn-add-lesson-btn mt-2" data-lang="{{ $code }}">
                        <i class="bi bi-plus-circle me-1"></i> {{ __('jornadas.add_lesson') }}
                    </button>

                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Footer --}}
    <div class="card-footer dl-card-footer">
        <a href="{{ route('jornadas.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? __('jornadas.update_jornadas') : __('jornadas.save_jornadas') }}
        </button>
    </div>

</div>

{{-- Lesson Template for JS cloning --}}
<template id="lessonTemplate">
    <div class="dl-section-card jn-lesson-card" data-lesson-key="__KEY__">
        <input type="hidden" name="lesson_existing_audio___LANG_____KEY__" class="jn-existing-audio" value="">
        <input type="hidden" name="lesson_existing_duration___LANG_____KEY__" class="jn-existing-duration" value="">

        <div class="dl-section-header">
            <div class="d-flex align-items-center gap-2">
                <span class="jn-lesson-drag-handle" style="cursor: grab; color: #aaa;"><i class="bi bi-grip-vertical"></i></span>
                <strong class="jn-lesson-number">__LESSON_LABEL__</strong>
            </div>
            <div class="d-flex align-items-center gap-3">
                <label class="d-flex align-items-center gap-2 m-0" for="lessonSubscribersOnly___LANG_____KEY__" style="cursor:pointer;">
                    <input type="checkbox" class="jn-subscribers-only"
                        name="lesson_subscribers_only___LANG_____KEY__"
                        id="lessonSubscribersOnly___LANG_____KEY__"
                        value="1"
                        style="width:17px;height:17px;cursor:pointer;accent-color:#d4a528;flex-shrink:0;">
                    <span class="fw-bold" style="font-size:15px;color:#5a4a2f;white-space:nowrap;">
                        <i class="bi bi-star-fill me-1" style="color:#d4a528;font-size:13px;"></i>{{ __('jornadas.subscribers_only') }}?
                    </span>
                </label>
                <button type="button" class="btn btn-sm btn-outline-danger jn-remove-lesson" title="Remove lesson">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <div class="mb-3">
            <label class="dl-label">{{ __('common.title') }} <span class="text-danger">*</span></label>
            <input type="text" name="lesson_title___LANG_____KEY__"
                class="form-control dl-validate-field"
                placeholder="{{ __('jornadas.enter_lesson_title') }}">
        </div>
        <div class="mb-3">
            <label class="dl-label">{{ __('common.description') }} <span class="text-danger">*</span></label>
            <textarea name="lesson_description___LANG_____KEY__"
                class="form-control dl-validate-field"
                rows="2"
                placeholder="{{ __('jornadas.enter_lesson_description') }}"></textarea>
        </div>
        <div>
            <label class="dl-upload-label"><i class="bi bi-mic me-1"></i>{{ __('common.upload_audio') }} <span class="text-danger">*</span></label>
            <div class="dl-upload-zone">
                <input type="file" name="lesson_audio___LANG_____KEY__"
                    accept="audio/mpeg,audio/wav,audio/mp3"
                    class="dl-file-input" id="lessonAudioInput___LANG_____KEY__"
                    data-preview="lessonAudioPreview___LANG_____KEY__"
                    data-type="audio">
                <div class="dl-upload-placeholder" id="lessonAudioPlaceholder___LANG_____KEY__">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <span>{{ __('common.upload_audio') }}</span>
                    <small>{{ __('common.audio_size_info') }}</small>
                </div>
                <div class="dl-upload-preview d-none" id="lessonAudioPreview___LANG_____KEY__">
                    <div class="dl-audio-info">
                        <i class="bi bi-file-earmark-music"></i>
                        <span class="dl-audio-name"></span>
                    </div>
                    <audio controls class="dl-audio-player"></audio>
                    <button type="button" class="dl-preview-remove"
                        data-target="lessonAudioInput___LANG_____KEY__"
                        data-placeholder="lessonAudioPlaceholder___LANG_____KEY__"
                        data-preview="lessonAudioPreview___LANG_____KEY__">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
