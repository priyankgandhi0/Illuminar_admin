<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
$(document).ready(function() {

    var allLangs = ['pt', 'en', 'es'];
    var isEditMode = $('input[name="_method"]').length > 0;

    function getEnabledLangs() {
        var langs = ['pt'];
        if ($('#langEnabled_en').is(':checked')) langs.push('en');
        if ($('#langEnabled_es').is(':checked')) langs.push('es');
        return langs;
    }

    // ---- Language Names ----
    var langNames = { pt: '{{ __("common.portuguese_pt") }}', en: '{{ __("common.english_en") }}', es: '{{ __("common.spanish_es") }}' };

    // ---- Category Dropdown Sync ----
    $(document).on('change', '.jn-category-select', function() {
        var selectedVal = $(this).val();
        var changedLang = $(this).data('lang');
        $('#categoryId').val(selectedVal);

        var syncedLangs = [];
        $('.jn-category-select').not(this).each(function() {
            var otherLang = $(this).data('lang');
            if (!isLangEnabled(otherLang)) return;
            if ($(this).find('option[value="' + selectedVal + '"]').length) {
                $(this).val(selectedVal);
                syncedLangs.push(langNames[otherLang]);
            } else {
                $(this).val('');
            }
        });

        if (selectedVal && syncedLangs.length > 0) {
            toastr.info(Lang.category_synced.replace(':langs', syncedLangs.join(' & ')));
        }

        $('.jn-category-select').removeClass('is-invalid');
        $('.jn-category-select').next('.dl-client-error').remove();
    });

    // ---- Language Toggle ----
    $(document).on('change', '.lang-toggle', function() {
        var lang = $(this).data('lang');
        var enabled = $(this).is(':checked');
        var $content = $('#langContent_' + lang);

        if (enabled) {
            $content.removeClass('d-none');
            var currentCatId = $('#categoryId').val();
            if (currentCatId) {
                var $langSelect = $content.find('.jn-category-select');
                if ($langSelect.find('option[value="' + currentCatId + '"]').length) {
                    $langSelect.val(currentCatId);
                }
            }
            var count = getLessonCount(lang);
            if (count === 0) {
                $content.find('.jn-add-lesson-btn').trigger('click');
            }
        } else {
            $content.addClass('d-none');
            $content.find('.is-invalid').removeClass('is-invalid');
            $content.find('.dl-client-error').remove();
            $content.find('.dl-upload-zone').removeClass('dl-upload-error');
        }
        updateLangDot(lang);
    });

    // ---- Lang Dot ----
    function isLangEnabled(lang) {
        if (lang === 'pt') return true;
        return $('#langEnabled_' + lang).is(':checked');
    }

    function updateLangDot(lang) {
        var dot = $('#tab-' + lang).find('.dl-lang-dot');
        if (isLangEnabled(lang)) {
            dot.addClass('active').removeClass('error');
        } else {
            dot.removeClass('active error');
        }
    }

    $(document).on('input change', '.dl-validate-field', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.dl-client-error').remove();
        var $pane = $(this).closest('.tab-pane');
        if ($pane.length) {
            var lang = $pane.attr('id').replace('panel-', '');
            updateLangDot(lang);
        }
    });

    allLangs.forEach(function(lang) { updateLangDot(lang); });

    // ---- File Upload Preview ----
    $(document).on('change', '.dl-file-input', function() {
        var input = this;
        var previewId = $(input).data('preview');
        var type = $(input).data('type');
        var placeholderId = previewId.replace('Preview', 'Placeholder');

        var $zone = $(input).closest('.dl-upload-zone');
        $zone.removeClass('dl-upload-error');
        $zone.next('.dl-client-error').remove();
        $zone.next('.dl-dimension-error').remove();

        if (input.files && input.files[0]) {
            var file = input.files[0];
            var maxSize = type === 'audio' ? 50 * 1024 * 1024 : 5 * 1024 * 1024;
            if (file.size > maxSize) {
                toastr.error(type === 'audio' ? Lang.file_too_large_audio : Lang.file_too_large_image);
                $(input).val('');
                return;
            }

            var $preview = $('#' + previewId);
            var $placeholder = $('#' + placeholderId);

            if (type === 'image') {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = new Image();
                    img.onload = function() {
                        if (img.width !== 1080 || img.height !== 1080) {
                            var errMsg = Lang.image_wrong_dimensions
                                .replace(':size', '1080 x 1080')
                                .replace(':actual', img.width + ' x ' + img.height);
                            $zone.addClass('dl-upload-error');
                            $zone.after('<div class="dl-dimension-error"><i class="bi bi-exclamation-circle"></i> ' + errMsg + '</div>');
                            $(input).val('');
                            return;
                        }
                        $preview.find('.dl-preview-img').attr('src', e.target.result);
                        $placeholder.addClass('d-none');
                        $preview.removeClass('d-none');
                        var $pane2 = $(input).closest('.tab-pane');
                        if ($pane2.length) updateLangDot($pane2.attr('id').replace('panel-', ''));
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
                return;
            } else if (type === 'audio') {
                var url = URL.createObjectURL(file);
                $preview.find('.dl-audio-name').text(file.name);
                var $audio = $preview.find('.dl-audio-player');
                $audio.attr('src', '');
                $audio.find('source').remove();
                $audio[0].src = url;
                $audio[0].load();
                $placeholder.addClass('d-none');
                $preview.removeClass('d-none');
            }
        }

        var $pane = $(input).closest('.tab-pane');
        if ($pane.length) {
            var lang = $pane.attr('id').replace('panel-', '');
            updateLangDot(lang);
        }
    });

    // ---- Remove Preview ----
    $(document).on('click', '.dl-preview-remove', function() {
        var targetId = $(this).data('target');
        var placeholderId = $(this).data('placeholder');
        var previewId = $(this).data('preview');
        $(this).closest('.dl-upload-zone').next('.dl-dimension-error').remove();
        $('#' + targetId).val('');
        $('#' + previewId).addClass('d-none');
        $('#' + placeholderId).removeClass('d-none');

        // Clear existing audio hidden inputs if inside a lesson card
        var $card = $(this).closest('.jn-lesson-card');
        if ($card.length) {
            $card.find('.jn-existing-audio').val('');
            $card.find('.jn-existing-duration').val('');
        }

        // Clear existing cover image key if inside a cover section
        var $coverSection = $(this).closest('.jn-cover-section');
        if ($coverSection.length) {
            $coverSection.find('input[id^="existingCoverImage_"]').val('');
        }

        var $pane = $(this).closest('.tab-pane');
        if ($pane.length) {
            var lang = $pane.attr('id').replace('panel-', '');
            updateLangDot(lang);
        }
    });

    // ---- Click upload zone ----
    $(document).on('click', '.dl-upload-placeholder', function() {
        $(this).siblings('.dl-file-input').trigger('click');
    });

    // ---- Drag and drop ----
    $(document).on('dragover', '.dl-upload-zone', function(e) {
        e.preventDefault();
        $(this).addClass('dl-dragover');
    });
    $(document).on('dragleave', '.dl-upload-zone', function(e) {
        e.preventDefault();
        $(this).removeClass('dl-dragover');
    });
    $(document).on('drop', '.dl-upload-zone', function(e) {
        e.preventDefault();
        $(this).removeClass('dl-dragover');
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            var input = $(this).find('.dl-file-input')[0];
            var dataTransfer = new DataTransfer();
            dataTransfer.items.add(files[0]);
            input.files = dataTransfer.files;
            $(input).trigger('change');
        }
    });

    // ---- Only one audio plays at a time ----
    $(document).on('play', 'audio', function() {
        var current = this;
        $('audio').each(function() {
            if (this !== current) this.pause();
        });
    });

    // ---- Dynamic Lessons (index-based) ----

    function getLessonCount(lang) {
        return $('#lessonsContainer_' + lang).find('.jn-lesson-card').length;
    }

    function toggleRemoveButtons(lang) {
        if (lang) {
            var count = getLessonCount(lang);
            var $container = $('#lessonsContainer_' + lang);
            $container.find('.jn-remove-lesson').each(function() {
                if (count <= 1) {
                    $(this).addClass('d-none');
                } else {
                    $(this).removeClass('d-none');
                }
            });
        } else {
            allLangs.forEach(function(l) {
                toggleRemoveButtons(l);
            });
        }
    }

    function renumberLessons(lang) {
        var $container = $('#lessonsContainer_' + lang);
        $container.find('.jn-lesson-card').each(function(idx) {
            var $card = $(this);
            $card.attr('data-lesson-key', idx);
            $card.find('.jn-lesson-number').text(Lang.lesson_prefix + ' ' + (idx + 1));

            // Update hidden inputs
            $card.find('.jn-existing-audio').attr('name', 'lesson_existing_audio_' + lang + '_' + idx);
            $card.find('.jn-existing-duration').attr('name', 'lesson_existing_duration_' + lang + '_' + idx);

            // Update title input
            $card.find('input[type="text"][name^="lesson_title_"]').attr('name', 'lesson_title_' + lang + '_' + idx);

            // Update description textarea
            $card.find('textarea[name^="lesson_description_"]').attr('name', 'lesson_description_' + lang + '_' + idx);

            // Update file input
            var $fileInput = $card.find('.dl-file-input');
            if ($fileInput.length) {
                $fileInput.attr('name', 'lesson_audio_' + lang + '_' + idx);
                $fileInput.attr('id', 'lessonAudioInput_' + lang + '_' + idx);
                $fileInput.attr('data-preview', 'lessonAudioPreview_' + lang + '_' + idx);
            }

            // Update placeholder ID
            $card.find('.dl-upload-placeholder').attr('id', 'lessonAudioPlaceholder_' + lang + '_' + idx);

            // Update preview ID
            $card.find('.dl-upload-preview').attr('id', 'lessonAudioPreview_' + lang + '_' + idx);

            // Update preview remove button data attributes
            $card.find('.dl-preview-remove').each(function() {
                $(this).attr('data-target', 'lessonAudioInput_' + lang + '_' + idx);
                $(this).attr('data-placeholder', 'lessonAudioPlaceholder_' + lang + '_' + idx);
                $(this).attr('data-preview', 'lessonAudioPreview_' + lang + '_' + idx);
            });

            // Update subscribers_only checkbox
            var $subsOnly = $card.find('.jn-subscribers-only');
            $subsOnly.attr('name', 'lesson_subscribers_only_' + lang + '_' + idx);
            $subsOnly.attr('id', 'lessonSubscribersOnly_' + lang + '_' + idx);
            $subsOnly.closest('label').attr('for', 'lessonSubscribersOnly_' + lang + '_' + idx);
        });
    }

    // Add Lesson
    $(document).on('click', '.jn-add-lesson-btn', function() {
        var lang = $(this).data('lang');
        var template = document.getElementById('lessonTemplate');
        var $container = $('#lessonsContainer_' + lang);
        var idx = getLessonCount(lang);

        var html = template.innerHTML
            .replace(/__LANG__/g, lang)
            .replace(/__KEY__/g, idx)
            .replace(/__LESSON_LABEL__/g, Lang.lesson_prefix + ' ' + (idx + 1));

        $container.append(html);
        $('#lessonCount_' + lang).val(idx + 1);
        renumberLessons(lang);
        toggleRemoveButtons(lang);
    });

    // Remove Lesson
    $(document).on('click', '.jn-remove-lesson', function() {
        var $card = $(this).closest('.jn-lesson-card');
        var $container = $card.closest('.jn-lessons-container');
        var lang = $container.attr('id').replace('lessonsContainer_', '');

        if (getLessonCount(lang) <= 1) return;

        $card.remove();
        renumberLessons(lang);
        $('#lessonCount_' + lang).val(getLessonCount(lang));
        toggleRemoveButtons(lang);
    });

    toggleRemoveButtons();

    // ---- Lesson drag-and-drop reorder ----
    allLangs.forEach(function(lang) {
        var container = document.getElementById('lessonsContainer_' + lang);
        if (container) {
            Sortable.create(container, {
                handle: '.jn-lesson-drag-handle',
                animation: 150,
                onEnd: function() {
                    renumberLessons(lang);
                    toggleRemoveButtons(lang);
                }
            });
        }
    });

    // ---- Validation ----
    function validateLang(lang) {
        var valid = true;
        var $panel = $('#panel-' + lang);

        var $title = $panel.find('input[name="title_' + lang + '"]');
        if (!($title.val() || '').trim()) {
            $title.addClass('is-invalid');
            if (!$title.next('.dl-client-error').length) {
                $title.after('<div class="invalid-feedback dl-client-error">' + Lang.required_field + '</div>');
            }
            valid = false;
        }

        var $desc = $panel.find('textarea[name="description_' + lang + '"]');
        if (!($desc.val() || '').trim()) {
            $desc.addClass('is-invalid');
            if (!$desc.next('.dl-client-error').length) {
                $desc.after('<div class="invalid-feedback dl-client-error">' + Lang.required_field + '</div>');
            }
            valid = false;
        }

        // Cover image: always required
        var coverInput = document.getElementById('coverInput_' + lang);
        if (coverInput) {
            var hasNewFile = coverInput.files && coverInput.files.length > 0;
            var $coverPreview = $('#coverPreview_' + lang);
            var hasExisting = $coverPreview.length && !$coverPreview.hasClass('d-none');
            if (!hasNewFile && !hasExisting) {
                var $zone = $(coverInput).closest('.dl-upload-zone');
                $zone.addClass('dl-upload-error');
                if (!$zone.next('.dl-client-error').length) {
                    $zone.after('<div class="dl-client-error" style="color:#dc3545;font-size:0.875em;margin-top:4px;">' + Lang.cover_image_required + '</div>');
                }
                valid = false;
            }
        }

        // Validate all lessons for this language
        var $container = $('#lessonsContainer_' + lang);
        $container.find('.jn-lesson-card').each(function() {
            var $card = $(this);

            var $lTitle = $card.find('input[name^="lesson_title_"]');
            if (!($lTitle.val() || '').trim()) {
                $lTitle.addClass('is-invalid');
                if (!$lTitle.next('.dl-client-error').length) {
                    $lTitle.after('<div class="invalid-feedback dl-client-error">' + Lang.required_field + '</div>');
                }
                valid = false;
            }

            var $lDesc = $card.find('textarea[name^="lesson_description_"]');
            if (!($lDesc.val() || '').trim()) {
                $lDesc.addClass('is-invalid');
                if (!$lDesc.next('.dl-client-error').length) {
                    $lDesc.after('<div class="invalid-feedback dl-client-error">' + Lang.required_field + '</div>');
                }
                valid = false;
            }

            // Audio: check file input OR existing audio hidden input
            var $audioInput = $card.find('input[type="file"]');
            var hasNewAudio = $audioInput[0] && $audioInput[0].files && $audioInput[0].files.length > 0;
            var hasExistingAudio = ($card.find('.jn-existing-audio').val() || '') !== '';

            if (!hasNewAudio && !hasExistingAudio) {
                var $audZone = $audioInput.closest('.dl-upload-zone');
                $audZone.addClass('dl-upload-error');
                if (!$audZone.next('.dl-client-error').length) {
                    $audZone.after('<div class="dl-client-error" style="color:#dc3545;font-size:0.875em;margin-top:4px;">' + Lang.audio_required + '</div>');
                }
                valid = false;
            }
        });

        // Ensure at least 1 lesson exists
        if (getLessonCount(lang) === 0) {
            valid = false;
            toastr.error(Lang.min_one_lesson.replace(':lang', langNames[lang]));
        }

        return valid;
    }

    // ---- Form Submit ----
    var isSaving = false;

    $('#jornadaForm').on('submit', function(e) {
        e.preventDefault();

        if (isSaving) return; // block double submission

        // Clear previous errors
        $('.dl-validate-field').removeClass('is-invalid');
        $('.dl-client-error').remove();
        $('.dl-upload-zone').removeClass('dl-upload-error');

        // Validate category
        if (!$('#categoryId').val()) {
            var $firstCatSelect = $('.jn-category-select:visible').first();
            if ($firstCatSelect.length) {
                $firstCatSelect.addClass('is-invalid');
                if (!$firstCatSelect.next('.dl-client-error').length) {
                    $firstCatSelect.after('<div class="invalid-feedback dl-client-error">' + Lang.select_category + '</div>');
                }
                $('html, body').animate({ scrollTop: $firstCatSelect.offset().top - 120 }, 300);
            }
            return false;
        }

        // Renumber all lessons before submit to ensure sequential indices
        var enabledLangs = getEnabledLangs();
        enabledLangs.forEach(function(lang) {
            renumberLessons(lang);
            $('#lessonCount_' + lang).val(getLessonCount(lang));
        });

        for (var idx = 0; idx < enabledLangs.length; idx++) {
            var lang = enabledLangs[idx];
            var isValid = validateLang(lang);

            if (!isValid) {
                $('#tab-' + lang).tab('show');
                allLangs.forEach(function(l) { updateLangDot(l); });
                $('#tab-' + lang).find('.dl-lang-dot').removeClass('active').addClass('error');

                setTimeout(function() {
                    var errorLang = lang;
                    var $firstError = $('#panel-' + errorLang).find('.is-invalid, .dl-upload-error').first();
                    if ($firstError.length) {
                        $('html, body').animate({ scrollTop: $firstError.offset().top - 120 }, 300);
                    }
                }, 200);

                return false;
            }
        }

        // All valid - start sequential AJAX save
        isSaving = true;
        doSequentialSave(enabledLangs);
    });

    // ---- Sequential AJAX Save ----
    var storeMainUrl      = '{{ route("jornadas.store-main") }}';
    var uploadFileBaseUrl = '{{ route("jornadas.upload-file", "JORNADA_ID") }}';
    var storeLangBaseUrl  = '{{ route("jornadas.store-lang", "JORNADA_ID") }}';

    function getCsrf() {
        return $('input[name="_token"]').val();
    }

    function doSequentialSave(enabledLangs) {
        $('#dlLoaderText').text(Lang.saving_main_data);
        $('#dlOverlayLoader').addClass('active');

        var fd = new FormData();
        fd.append('_token', getCsrf());
        fd.append('status', $('#status').val());
        fd.append('category_id', $('#categoryId').val());
        fd.append('is_edit', isEditMode ? '1' : '0');
        fd.append('doc_id', $('#jornadaDocId').val() || '');
        fd.append('lesson_doc_id', $('input[name="lesson_doc_id"]').val() || '');
        if ($('#langEnabled_en').is(':checked')) fd.append('lang_enabled_en', '1');
        if ($('#langEnabled_es').is(':checked')) fd.append('lang_enabled_es', '1');

        $.ajax({
            url: storeMainUrl,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if (!res.success) {
                    isSaving = false;
                    $('#dlOverlayLoader').removeClass('active');
                    toastr.error(res.message || Lang.something_wrong);
                    return;
                }
                saveLangsSequentially(res.docId, res.lessonDocId, enabledLangs, 0);
            },
            error: function(xhr) {
                isSaving = false;
                $('#dlOverlayLoader').removeClass('active');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : Lang.something_wrong;
                toastr.error(msg);
            }
        });
    }

    function saveLangsSequentially(docId, lessonDocId, langs, index) {
        if (index >= langs.length) {
            $('#dlOverlayLoader').removeClass('active');
            toastr.success(Lang.save_complete);
            setTimeout(function() {
                window.location.href = '{{ route("jornadas.index") }}';
            }, 2000);
            return;
        }

        var lang = langs[index];
        var total = langs.length;
        var current = index + 1;
        var uploadUrl = uploadFileBaseUrl.replace('JORNADA_ID', docId);

        $('#dlLoaderText').text(Lang.saving_lang_data.replace(':lang', langNames[lang]).replace(':current', current).replace(':total', total));

        uploadCoverImage(uploadUrl, lang, function(coverKey) {
            uploadLessonsSequentially(uploadUrl, lang, 0, getLessonCount(lang), [], function(audioResults) {
                saveLangData(docId, lessonDocId, lang, coverKey, audioResults, langs, index);
            }, function() {
                isSaving = false;
                $('#dlOverlayLoader').removeClass('active');
                toastr.error(Lang.upload_failed.replace(':lang', langNames[lang]));
            });
        }, function() {
            isSaving = false;
            $('#dlOverlayLoader').removeClass('active');
            toastr.error(Lang.upload_failed.replace(':lang', langNames[lang]));
        });
    }

    // function uploadCoverImage(uploadUrl, lang, onSuccess, onError) {
    //     var coverInput = document.getElementById('coverInput_' + lang);
    //     if (!coverInput || !coverInput.files || coverInput.files.length === 0) {
    //         onSuccess('');
    //         return;
    //     }

    //     var fd = new FormData();
    //     fd.append('_token', getCsrf());
    //     fd.append('file', coverInput.files[0]);
    //     fd.append('lang', lang);
    //     fd.append('type', 'cover_image');

    //     $('#dlLoaderText').text(Lang.uploading_file.replace(':lang', langNames[lang]).replace(':current', '1').replace(':total', '1'));

    //     $.ajax({
    //         url: uploadUrl,
    //         method: 'POST',
    //         data: fd,
    //         processData: false,
    //         contentType: false,
    //         timeout: 120000,
    //         success: function(res) {
    //             if (res.success) { onSuccess(res.storage_key); }
    //             else { onError(res.message); }
    //         },
    //         error: function(xhr) {
    //             onError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : Lang.something_wrong);
    //         }
    //     });
    // }

    function uploadCoverImage(uploadUrl, lang, onSuccess, onError) {

        var coverInput = document.getElementById('coverInput_' + lang);

        if (!coverInput || !coverInput.files || coverInput.files.length === 0) {
            onSuccess('');
            return;
        }

        var file = coverInput.files[0];

        $('#dlLoaderText').text(
            Lang.uploading_file
                .replace(':lang', langNames[lang])
                .replace(':current', '1')
                .replace(':total', '1')
        );

        // STEP 1: Get signed URL from Laravel
        $.ajax({
            url: uploadUrl,
            method: 'POST',
            data: {
                _token: getCsrf(),
                file_name: file.name,
                file_type: file.type,
                lang: lang,
                type: 'cover_image'
            },

            success: function(res) {

                if (!res.success) {
                    onError(res.message || 'Failed to get upload URL');
                    return;
                }

                // STEP 2: Direct upload to Cloudflare R2
                fetch(res.upload_url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': file.type
                    },
                    body: file
                })
                .then(response => {

                    if (!response.ok) {
                        throw new Error('Upload failed');
                    }

                    onSuccess(res.storage_key);
                })
                .catch(error => {
                    onError(error.message);
                });
            },

            error: function(xhr) {
                onError(
                    (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : Lang.something_wrong
                );
            }
        });
    }

    function uploadLessonsSequentially(uploadUrl, lang, index, count, results, onSuccess, onError) {
        if (index >= count) {
            onSuccess(results);
            return;
        }

        var $card = $('#lessonsContainer_' + lang).find('.jn-lesson-card').eq(index);
        var $audioInput = $card.find('input[type="file"]');

        if (!$audioInput[0] || !$audioInput[0].files || $audioInput[0].files.length === 0) {
            results.push({ key: '', duration: '' });
            uploadLessonsSequentially(uploadUrl, lang, index + 1, count, results, onSuccess, onError);
            return;
        }

        uploadLessonWithRetry(uploadUrl, lang, index, count, $audioInput[0].files[0], 0, function(key, duration) {
            results.push({ key: key, duration: duration });
            uploadLessonsSequentially(uploadUrl, lang, index + 1, count, results, onSuccess, onError);
        }, onError);
    }

    // function uploadLessonWithRetry(uploadUrl, lang, index, count, file, attempt, onSuccess, onError) {
    //     var maxAttempts = 3;

    //     var fd = new FormData();
    //     fd.append('_token', getCsrf());
    //     fd.append('file', file);
    //     fd.append('lang', lang);
    //     fd.append('type', 'lesson_audio');
    //     fd.append('lesson_index', index);

    //     var attemptLabel = attempt > 0 ? ' (tentativa ' + (attempt + 1) + ')' : '';
    //     $('#dlLoaderText').text(Lang.uploading_file.replace(':lang', langNames[lang]).replace(':current', (index + 1)).replace(':total', count) + attemptLabel);

    //     $.ajax({
    //         url: uploadUrl,
    //         method: 'POST',
    //         data: fd,
    //         processData: false,
    //         contentType: false,
    //         timeout: 280000, // 280 seconds (matches server max_execution_time)
    //         success: function(res) {
    //             if (res.success) {
    //                 onSuccess(res.storage_key, res.audio_duration || '00:00');
    //             } else {
    //                 if (attempt + 1 < maxAttempts) {
    //                     setTimeout(function() {
    //                         uploadLessonWithRetry(uploadUrl, lang, index, count, file, attempt + 1, onSuccess, onError);
    //                     }, 2000);
    //                 } else {
    //                     onError(res.message);
    //                 }
    //             }
    //         },
    //         error: function(xhr) {
    //             if (attempt + 1 < maxAttempts) {
    //                 setTimeout(function() {
    //                     uploadLessonWithRetry(uploadUrl, lang, index, count, file, attempt + 1, onSuccess, onError);
    //                 }, 2000);
    //             } else {
    //                 onError((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : Lang.something_wrong);
    //             }
    //         }
    //     });
    // }

    function uploadLessonWithRetry(uploadUrl, lang, index, count, file, attempt, onSuccess, onError) {

        var maxAttempts = 3;

        var attemptLabel = attempt > 0 ? ' (tentativa ' + (attempt + 1) + ')' : '';

        $('#dlLoaderText').text(
            Lang.uploading_file
                .replace(':lang', langNames[lang])
                .replace(':current', (index + 1))
                .replace(':total', count) + attemptLabel
        );

        // CHECK IF FILE IS AUDIO
        if (file.type.startsWith('audio/')) {

            getAudioDuration(file, function(duration) {

                startUpload(duration);

            });

        } else {

            // IMAGE / OTHER FILE
            startUpload('00:00');
        }

        /**
         * START ACTUAL UPLOAD
         */
        function startUpload(duration) {

            // STEP 1: GET SIGNED URL FROM LARAVEL
            $.ajax({
                url: uploadUrl,
                method: 'POST',
                data: {
                    _token: getCsrf(),
                    file_name: file.name,
                    file_type: file.type,
                    lang: lang,
                    type: 'lesson_audio',
                    lesson_index: index,
                    audio_duration: duration
                },

                success: function(res) {

                    if (!res.success) {

                        retryOrFail();
                        return;
                    }

                    // STEP 2: DIRECT UPLOAD TO CLOUD STORAGE
                    fetch(res.upload_url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': file.type
                        },
                        body: file
                    })
                    .then(response => {

                        if (!response.ok) {
                            throw new Error('Upload failed');
                        }

                        // RETURN STORAGE KEY + DURATION
                        onSuccess(
                            res.storage_key,
                            duration || '00:00'
                        );

                    })
                    .catch(err => {
                        retryOrFail();
                    });
                },

                error: function() {
                    retryOrFail();
                }
            });
        }

        /**
         * RETRY HANDLER
         */
        function retryOrFail() {

            if (attempt + 1 < maxAttempts) {

                setTimeout(function() {

                    uploadLessonWithRetry(
                        uploadUrl,
                        lang,
                        index,
                        count,
                        file,
                        attempt + 1,
                        onSuccess,
                        onError
                    );

                }, 2000);

            } else {

                onError('Upload failed after retries');
            }
        }
    }

    /**
     * GET AUDIO DURATION
     */
    function getAudioDuration(file, callback) {

        var audio = document.createElement('audio');

        audio.preload = 'metadata';

        audio.onloadedmetadata = function () {

            window.URL.revokeObjectURL(audio.src);

            var totalSeconds = Math.round(audio.duration);

            var minutes = Math.floor(totalSeconds / 60);
            var seconds = totalSeconds % 60;

            var formatted =
                String(minutes).padStart(2, '0') +
                ':' +
                String(seconds).padStart(2, '0');

            callback(formatted);
        };

        audio.onerror = function () {

            callback('00:00');
        };

        audio.src = URL.createObjectURL(file);
    }

    function saveLangData(docId, lessonDocId, lang, coverKey, audioResults, langs, index) {
        var storeLangUrl = storeLangBaseUrl.replace('JORNADA_ID', docId);
        var total = langs.length;
        var current = index + 1;

        $('#dlLoaderText').text(Lang.saving_lang_data.replace(':lang', langNames[lang]).replace(':current', current).replace(':total', total));

        var fd = new FormData();
        fd.append('_token', getCsrf());
        fd.append('lang', lang);
        fd.append('lesson_doc_id', lessonDocId || '');
        fd.append('is_edit', isEditMode ? '1' : '0');
        fd.append('title_' + lang, $('input[name="title_' + lang + '"]').val() || '');
        fd.append('description_' + lang, $('textarea[name="description_' + lang + '"]').val() || '');
        fd.append('cover_image_key_' + lang, coverKey || '');
        fd.append('existing_cover_image_' + lang, $('#existingCoverImage_' + lang).val() || '');

        var lessonCount = getLessonCount(lang);
        fd.append('lesson_count_' + lang, lessonCount);

        $('#lessonsContainer_' + lang).find('.jn-lesson-card').each(function(i) {
            var $card = $(this);
            var audioKey      = (audioResults[i] && audioResults[i].key)      ? audioResults[i].key      : '';
            var audioDuration = (audioResults[i] && audioResults[i].duration)  ? audioResults[i].duration : '';
            fd.append('lesson_title_' + lang + '_' + i,              $card.find('input[name^="lesson_title_"]').val() || '');
            fd.append('lesson_description_' + lang + '_' + i,        $card.find('textarea[name^="lesson_description_"]').val() || '');
            fd.append('lesson_audio_key_' + lang + '_' + i,          audioKey);
            fd.append('lesson_audio_duration_' + lang + '_' + i,     audioDuration);
            fd.append('lesson_existing_audio_' + lang + '_' + i,     $card.find('.jn-existing-audio').val() || '');
            fd.append('lesson_existing_duration_' + lang + '_' + i,  $card.find('.jn-existing-duration').val() || '');
            fd.append('lesson_subscribers_only_' + lang + '_' + i,   $card.find('.jn-subscribers-only').is(':checked') ? '1' : '0');
        });

        $.ajax({
            url: storeLangUrl,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    saveLangsSequentially(docId, lessonDocId, langs, index + 1);
                } else {
                    isSaving = false;
                    $('#dlOverlayLoader').removeClass('active');
                    toastr.error(res.message || Lang.save_failed_lang.replace(':lang', langNames[lang]));
                }
            },
            error: function(xhr) {
                isSaving = false;
                $('#dlOverlayLoader').removeClass('active');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : Lang.something_wrong;
                toastr.error(msg);
            }
        });
    }
});
</script>
