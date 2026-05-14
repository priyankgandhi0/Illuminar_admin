<script>
$(document).ready(function() {

    var allLangs = ['pt', 'en', 'es'];
    var dateExists = false; // Track if current date already exists
    var isReadonly = {{ ($isReadonly ?? false) ? 'true' : 'false' }};

    // Get current date/time in Brazil timezone (America/Sao_Paulo)
    function getBrazilNow() {
        var now = new Date();
        var brazil = new Date(now.toLocaleString('en-US', { timeZone: 'America/Sao_Paulo' }));
        return brazil;
    }

    // Initialize Flatpickr on publish date (consistent DD-MM-YYYY for all locales)
    var fpInstance = null;
    if (!isReadonly && $('#publishDate').length) {
        fpInstance = flatpickr('#publishDate', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd-m-Y',
            minDate: 'today',
            allowInput: false,
            onChange: function(selectedDates, dateStr, instance) {
                $(instance.element).trigger('change');
            }
        });
        // Calendar icon click opens the picker
        $('#publishDateIcon').on('click', function() {
            if (fpInstance) fpInstance.open();
        });
    }

    // Set Brazil time as default when clicking on empty time inputs
    if (!isReadonly) {
        function setBrazilTimeOnFocus() {
            if (!this.value) {
                var b = getBrazilNow();
                this.value = String(b.getHours()).padStart(2, '0') + ':' + String(b.getMinutes()).padStart(2, '0');
            }
        }
        $('#publishTime').on('focus', setBrazilTimeOnFocus);
        allLangs.forEach(function(lang) {
            $('#dlNotifTime_' + lang).on('focus', setBrazilTimeOnFocus);
        });
    }

    function getEnabledLangs() {
        var langs = ['pt']; // PT always enabled
        if ($('#langEnabled_en').is(':checked')) langs.push('en');
        if ($('#langEnabled_es').is(':checked')) langs.push('es');
        return langs;
    }

    // ---- Language Toggle: show/hide form fields ----
    $(document).on('change', '.lang-toggle', function() {
        var lang = $(this).data('lang');
        var enabled = $(this).is(':checked');
        var $content = $('#langContent_' + lang);

        if (enabled) {
            $content.removeClass('d-none');
        } else {
            $content.addClass('d-none');
            // Clear errors when disabling
            $content.find('.is-invalid').removeClass('is-invalid');
            $content.find('.dl-client-error').remove();
            $content.find('.dl-upload-zone').removeClass('dl-upload-error');
        }
        updateLangDot(lang);
    });

    // ---- Check if step has file (uploaded or existing preview visible) ----
    // type: 'image', 'audio', 'bgImage'
    function hasFile(lang, step, type) {
        var inputId = type + 'Input_' + lang + '_' + step;
        var previewId = type + 'Preview_' + lang + '_' + step;
        var input = document.getElementById(inputId);
        if (input && input.files && input.files.length > 0) return true;
        var $preview = $('#' + previewId);
        if ($preview.length && !$preview.hasClass('d-none')) return true;
        return false;
    }

    // ---- Check if Step 5 has any content for a language ----
    function hasStep5Content(lang) {
        var title = ($('input[name="section_title_' + lang + '_5"]').val() || '').trim();
        if (title) return true;
        var desc = ($('textarea[name="section_description_' + lang + '_5"]').val() || '').trim();
        if (desc) return true;
        if (hasFile(lang, 5, 'image')) return true;
        if (hasFile(lang, 5, 'bgImage')) return true;
        if (hasFile(lang, 5, 'audio')) return true;
        return false;
    }

    // ---- Check if Step 5 is fully complete for a language ----
    function isStep5Complete(lang) {
        var title = ($('input[name="section_title_' + lang + '_5"]').val() || '').trim();
        if (!title) return false;
        var desc = ($('textarea[name="section_description_' + lang + '_5"]').val() || '').trim();
        if (!desc) return false;
        if (!hasFile(lang, 5, 'image')) return false;
        if (!hasFile(lang, 5, 'bgImage')) return false;
        if (!hasFile(lang, 5, 'audio')) return false;
        return true;
    }

    // ---- Subscriber checkbox is always visible for Step 5 ----
    function updateSubscriberBox() {
        // No-op: subscriber box is always visible since Step 5 is required
    }

    // ---- Update Green/Red Dot ----
    function isLangComplete(lang) {
        var title = ($('input[name="title_' + lang + '"]').val() || '').trim();
        var desc = ($('textarea[name="description_' + lang + '"]').val() || '').trim();
        if (!title || !desc) return false;
        // Steps 1-4 required
        for (var i = 1; i <= 4; i++) {
            var catId = ($('#stepCat_' + lang + '_' + i).val() || '').trim();
            if (!catId) return false;
            var secTitle = ($('input[name="section_title_' + lang + '_' + i + '"]').val() || '').trim();
            if (!secTitle) return false;
            var secDesc = ($('textarea[name="section_description_' + lang + '_' + i + '"]').val() || '').trim();
            if (!secDesc) return false;
            if (!hasFile(lang, i, 'image')) return false;
            if (!hasFile(lang, i, 'audio')) return false;
        }
        // Step 5: all fields required
        if (!isStep5Complete(lang)) return false;
        return true;
    }

    function hasLangAnyContent(lang) {
        if (($('input[name="title_' + lang + '"]').val() || '').trim()) return true;
        if (($('textarea[name="description_' + lang + '"]').val() || '').trim()) return true;
        for (var i = 1; i <= 5; i++) {
            if (($('input[name="section_title_' + lang + '_' + i + '"]').val() || '').trim()) return true;
            if (($('textarea[name="section_description_' + lang + '_' + i + '"]').val() || '').trim()) return true;
        }
        return false;
    }

    function isLangEnabled(lang) {
        if (lang === 'pt') return true;
        return $('#langEnabled_' + lang).is(':checked');
    }

    function updateLangDot(lang) {
        var dot = $('#tab-' + lang).find('.dl-lang-dot');
        if (!isLangEnabled(lang)) {
            dot.removeClass('active error');
            return;
        }
        // Language is enabled — green dot by default
        // Red dot only if partially filled (has some content but not complete)
        if (hasLangAnyContent(lang) && !isLangComplete(lang)) {
            dot.removeClass('active').addClass('error');
        } else {
            dot.addClass('active').removeClass('error');
        }
    }

    // Listen for input changes — clear error + update dot (input for text, change for date/file)
    $(document).on('input change', '.dl-validate-field', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.dl-client-error').remove();
        var $pane = $(this).closest('.tab-pane');
        if ($pane.length) {
            var lang = $pane.attr('id').replace('panel-', '');
            updateLangDot(lang);
        }
        updateSubscriberBox();
    });

    // Initial dot state + subscriber box
    allLangs.forEach(function(lang) { updateLangDot(lang); });
    updateSubscriberBox();

    // ---- Publish Time: real-time past-time validation ----
    function validatePublishTime() {
        var $time = $('#publishTime');
        var $error = $('#publishTimeError');
        if (!$time.length) return true;
        $time.removeClass('is-invalid');
        $error.addClass('d-none').text('');
        var timeVal = $time.val();
        if (!timeVal) {
            $time.addClass('is-invalid');
            $error.text(Lang.required_field).removeClass('d-none');
            return false;
        }
        var publishDate = $('input[name="publishDate"]').val() || '';
        if (!publishDate) return true;
        var brazilNow = getBrazilNow();
        var todayStr = brazilNow.getFullYear() + '-' +
            String(brazilNow.getMonth() + 1).padStart(2, '0') + '-' +
            String(brazilNow.getDate()).padStart(2, '0');
        if (publishDate === todayStr) {
            var parts = timeVal.split(':');
            var selectedMinutes = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
            var nowMinutes = brazilNow.getHours() * 60 + brazilNow.getMinutes();
            if (selectedMinutes <= nowMinutes) {
                $time.addClass('is-invalid');
                $error.text(Lang.publish_time_must_be_future).removeClass('d-none');
                return false;
            }
        }
        return true;
    }

    $('#publishTime').on('change', function() {
        validatePublishTime();
    });

    // ---- AJAX Date Uniqueness Check ----
    var dateCheckTimer = null;
    $('input[name="publishDate"]').on('change', function() {
        var dateVal = $(this).val();
        var $error = $('#dateExistsError');
        var $input = $(this);

        // Clear previous state
        $error.removeClass('active');
        $input.removeClass('is-invalid');
        dateExists = false;

        if (!dateVal) return;

        // Re-validate publish time when date changes
        validatePublishTime();

        // Get current doc ID for edit mode
        var excludeId = $('#currentDocId').length ? $('#currentDocId').val() : '';

        // Debounce the AJAX call
        clearTimeout(dateCheckTimer);
        dateCheckTimer = setTimeout(function() {
            $.ajax({
                url: '{{ route("daily-lights.check-date") }}',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    date: dateVal,
                    exclude_id: excludeId
                },
                success: function(response) {
                    if (response.exists) {
                        dateExists = true;
                        $error.addClass('active');
                        $input.addClass('is-invalid');
                    } else {
                        dateExists = false;
                        $error.removeClass('active');
                        $input.removeClass('is-invalid');
                    }
                },
                error: function() {
                    dateExists = false;
                    $error.removeClass('active');
                }
            });
        }, 300);
    });

    // ---- Reset file input on click so re-selecting same file triggers change ----
    $(document).on('click', '.dl-file-input', function() {
        this.value = '';
    });

    // ---- File Upload Preview ----
    $(document).on('change', '.dl-file-input', function() {
        var input = this;
        var previewId = $(input).data('preview');
        var type = $(input).data('type');
        var placeholderId = previewId.replace('Preview', 'Placeholder');
        var $zone = $(input).closest('.dl-upload-zone');

        // Clear upload error when file selected
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
                var inputId = $(input).attr('id') || '';
                var isBgImage = inputId.indexOf('bgImage') === 0;
                var expectedW = isBgImage ? 1080 : 1080;
                var expectedH = isBgImage ? 605 : 1080;
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = new Image();
                    img.onload = function() {
                        if (img.width !== expectedW || img.height !== expectedH) {
                            var errMsg = isBgImage
                                ? Lang.bg_image_wrong_dimensions
                                : Lang.image_wrong_dimensions;
                            errMsg = errMsg.replace(':size', expectedW + ' x ' + expectedH)
                                          .replace(':actual', img.width + ' x ' + img.height);
                            $zone.addClass('dl-upload-error');
                            $zone.after('<div class="dl-dimension-error"><i class="bi bi-exclamation-circle"></i> ' + errMsg + '</div>');
                            $(input).val('');
                            return;
                        }
                        $preview.find('.dl-preview-img').attr('src', e.target.result);
                        $placeholder.addClass('d-none');
                        $preview.removeClass('d-none');

                        // Update dot after successful upload
                        var $pane = $(input).closest('.tab-pane');
                        if ($pane.length) {
                            var lang = $pane.attr('id').replace('panel-', '');
                            updateLangDot(lang);
                        }
                        updateSubscriberBox();
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
                return; // dot update handled inside img.onload
            } else if (type === 'audio') {
                var url = URL.createObjectURL(file);
                $preview.find('.dl-audio-name').text(file.name);
                $preview.find('.dl-audio-player').attr('src', url);
                $placeholder.addClass('d-none');
                $preview.removeClass('d-none');
            }
        }

        // Update dot after file upload
        var $pane = $(input).closest('.tab-pane');
        if ($pane.length) {
            var lang = $pane.attr('id').replace('panel-', '');
            updateLangDot(lang);
        }
        updateSubscriberBox();
    });

    // ---- Remove Preview ----
    $(document).on('click', '.dl-preview-remove', function() {
        var targetId = $(this).data('target');
        var placeholderId = $(this).data('placeholder');
        var previewId = $(this).data('preview');
        // Clear dimension error
        $(this).closest('.dl-upload-zone').next('.dl-dimension-error').remove();
        $('#' + targetId).val('');
        $('#' + previewId).addClass('d-none');
        $('#' + placeholderId).removeClass('d-none');

        // Update dot after file removal
        var $pane = $(this).closest('.tab-pane');
        if ($pane.length) {
            var lang = $pane.attr('id').replace('panel-', '');
            updateLangDot(lang);
        }
        updateSubscriberBox();
    });

    // ---- Clear Section ----
    $(document).on('click', '.dl-clear-section', function() {
        var lang = $(this).data('lang');
        var section = $(this).data('section');
        $('input[name="section_title_' + lang + '_' + section + '"]').val('').removeClass('is-invalid');
        $('input[name="section_title_' + lang + '_' + section + '"]').next('.dl-client-error').remove();
        $('textarea[name="section_description_' + lang + '_' + section + '"]').val('').removeClass('is-invalid');
        $('textarea[name="section_description_' + lang + '_' + section + '"]').next('.dl-client-error').remove();
        $('#imageInput_' + lang + '_' + section).val('');
        $('#audioInput_' + lang + '_' + section).val('');
        $('#imagePreview_' + lang + '_' + section).addClass('d-none');
        $('#imagePlaceholder_' + lang + '_' + section).removeClass('d-none');
        $('#audioPreview_' + lang + '_' + section).addClass('d-none');
        $('#audioPlaceholder_' + lang + '_' + section).removeClass('d-none');
        // Clear bg image (step 5)
        if (section == 5) {
            $('#bgImageInput_' + lang + '_5').val('');
            $('#bgImagePreview_' + lang + '_5').addClass('d-none');
            $('#bgImagePlaceholder_' + lang + '_5').removeClass('d-none');
            $('#bgImageInput_' + lang + '_5').closest('.dl-upload-zone').removeClass('dl-upload-error');
            $('#bgImageInput_' + lang + '_5').closest('.dl-upload-zone').next('.dl-client-error').remove();
            $('#bgImageInput_' + lang + '_5').closest('.dl-upload-zone').next('.dl-dimension-error').remove();
        }
        // Clear upload errors & dimension errors
        $('#imageInput_' + lang + '_' + section).closest('.dl-upload-zone').removeClass('dl-upload-error');
        $('#imageInput_' + lang + '_' + section).closest('.dl-upload-zone').next('.dl-client-error').remove();
        $('#imageInput_' + lang + '_' + section).closest('.dl-upload-zone').next('.dl-dimension-error').remove();
        $('#audioInput_' + lang + '_' + section).closest('.dl-upload-zone').removeClass('dl-upload-error');
        $('#audioInput_' + lang + '_' + section).closest('.dl-upload-zone').next('.dl-client-error').remove();
        // Uncheck subscriber checkbox when clearing step 5
        if (section == 5) {
            $('#forSubscribeMember_' + lang).prop('checked', false);
        }
        // Reset category dropdown when clearing steps 1-4
        if (section <= 4) {
            var $sel = $('#dlcSelect_' + lang + '_' + section);
            $sel.find('.dlc-trigger').removeClass('dlc-trigger-error').html(
                '<span class="dlc-trigger-ph"><i class="bi bi-grid me-1"></i>{{ __("common.select_icon") }}</span>' +
                '<i class="bi bi-chevron-down dlc-arrow"></i>'
            );
            $('#stepCat_' + lang + '_' + section).val('');
            $sel.find('.dlc-opt').removeClass('selected');
            $sel.next('.dl-client-error').remove();
        }
        updateLangDot(lang);
        updateSubscriberBox();
    });

    // ---- Custom Category Dropdown ----
    $(document).on('click', '.dlc-trigger', function(e) {
        e.stopPropagation();
        var $select = $(this).closest('.dlc-custom-select');
        // Close all other open dropdowns
        $('.dlc-custom-select').not($select).removeClass('open');
        $select.toggleClass('open');
    });

    $(document).on('click', '.dlc-opt', function() {
        var $select = $(this).closest('.dlc-custom-select');
        var lang = $select.data('lang');
        var step = $select.data('step');
        var val = $(this).data('value') || '';
        var icon = $(this).data('icon') || '';
        var title = $(this).data('title') || '';
        var hasDropdown = $(this).data('has-dropdown') === 1 || $(this).data('has-dropdown') === '1';

        // Update hidden input
        $('#stepCat_' + lang + '_' + step).val(val);

        // Clear validation error
        $select.find('.dlc-trigger').removeClass('dlc-trigger-error');
        $select.next('.dl-client-error').remove();

        // Update trigger display
        var $trigger = $select.find('.dlc-trigger');
        if (val && title) {
            var iconClass = 'dlc-opt-icon' + (hasDropdown ? '' : ' dlc-opt-icon-legacy');
            var html = '<span class="dlc-trigger-val">';
            if (icon) html += '<img src="' + icon + '" class="' + iconClass + '">';
            html += '<span>' + $('<span>').text(title).html() + '</span></span>';
            html += '<i class="bi bi-chevron-down dlc-arrow"></i>';
            $trigger.html(html);
        } else {
            $trigger.html(
                '<span class="dlc-trigger-ph">{{ __("common.select_icon") }}</span>' +
                '<i class="bi bi-chevron-down dlc-arrow"></i>'
            );
        }

        // Mark selected
        $select.find('.dlc-opt').removeClass('selected');
        $(this).addClass('selected');

        $select.removeClass('open');
    });

    // Close dropdown when clicking outside
    $(document).on('click', function() {
        $('.dlc-custom-select').removeClass('open');
    });

    // ---- Only one audio plays at a time ----
    $(document).on('play', 'audio', function() {
        var current = this;
        $('audio').each(function() {
            if (this !== current) this.pause();
        });
    });

    // ---- Click upload zone ----
    $(document).on('click', '.dl-upload-placeholder', function() {
        if (isReadonly) return;
        $(this).siblings('.dl-file-input').trigger('click');
    });

    // ---- Drag and drop ----
    $(document).on('dragover', '.dl-upload-zone', function(e) {
        e.preventDefault();
        if (isReadonly) return;
        $(this).addClass('dl-dragover');
    });
    $(document).on('dragleave', '.dl-upload-zone', function(e) {
        e.preventDefault();
        $(this).removeClass('dl-dragover');
    });
    $(document).on('drop', '.dl-upload-zone', function(e) {
        e.preventDefault();
        if (isReadonly) return;
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

    // ---- Validate one language ----
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

        // Steps 1-4: all required
        for (var i = 1; i <= 4; i++) {
            // Validate category (icon) selection
            var catVal = ($('#stepCat_' + lang + '_' + i).val() || '').trim();
            if (!catVal) {
                var $dlcSelect = $('#dlcSelect_' + lang + '_' + i);
                $dlcSelect.find('.dlc-trigger').addClass('dlc-trigger-error');
                if (!$dlcSelect.next('.dl-client-error').length) {
                    $dlcSelect.after('<div class="dl-client-error" style="color:#dc3545;font-size:0.875em;margin-top:4px;">' + Lang.required_field + '</div>');
                }
                valid = false;
            }

            var $secTitle = $panel.find('input[name="section_title_' + lang + '_' + i + '"]');
            if (!($secTitle.val() || '').trim()) {
                $secTitle.addClass('is-invalid');
                if (!$secTitle.next('.dl-client-error').length) {
                    $secTitle.after('<div class="invalid-feedback dl-client-error">' + Lang.required_field + '</div>');
                }
                valid = false;
            }

            var $secDesc = $panel.find('textarea[name="section_description_' + lang + '_' + i + '"]');
            if (!($secDesc.val() || '').trim()) {
                $secDesc.addClass('is-invalid');
                if (!$secDesc.next('.dl-client-error').length) {
                    $secDesc.after('<div class="invalid-feedback dl-client-error">' + Lang.required_field + '</div>');
                }
                valid = false;
            }

            // Validate image
            if (!hasFile(lang, i, 'image')) {
                var $imgZone = $('#imageInput_' + lang + '_' + i).closest('.dl-upload-zone');
                $imgZone.addClass('dl-upload-error');
                if (!$imgZone.next('.dl-client-error').length) {
                    $imgZone.after('<div class="dl-client-error" style="color:#dc3545;font-size:0.875em;margin-top:4px;">' + Lang.image_required + '</div>');
                }
                valid = false;
            }

            // Validate audio
            if (!hasFile(lang, i, 'audio')) {
                var $audZone = $('#audioInput_' + lang + '_' + i).closest('.dl-upload-zone');
                $audZone.addClass('dl-upload-error');
                if (!$audZone.next('.dl-client-error').length) {
                    $audZone.after('<div class="dl-client-error" style="color:#dc3545;font-size:0.875em;margin-top:4px;">' + Lang.audio_required + '</div>');
                }
                valid = false;
            }
        }

        // Step 5: all fields required
        var $sec5Title = $panel.find('input[name="section_title_' + lang + '_5"]');
        if (!($sec5Title.val() || '').trim()) {
            $sec5Title.addClass('is-invalid');
            if (!$sec5Title.next('.dl-client-error').length) {
                $sec5Title.after('<div class="invalid-feedback dl-client-error">' + Lang.required_field + '</div>');
            }
            valid = false;
        }
        var $sec5Desc = $panel.find('textarea[name="section_description_' + lang + '_5"]');
        if (!($sec5Desc.val() || '').trim()) {
            $sec5Desc.addClass('is-invalid');
            if (!$sec5Desc.next('.dl-client-error').length) {
                $sec5Desc.after('<div class="invalid-feedback dl-client-error">' + Lang.required_field + '</div>');
            }
            valid = false;
        }
        if (!hasFile(lang, 5, 'image')) {
            var $imgZone5 = $('#imageInput_' + lang + '_5').closest('.dl-upload-zone');
            $imgZone5.addClass('dl-upload-error');
            if (!$imgZone5.next('.dl-client-error').length) {
                $imgZone5.after('<div class="dl-client-error" style="color:#dc3545;font-size:0.875em;margin-top:4px;">' + Lang.image_required + '</div>');
            }
            valid = false;
        }
        if (!hasFile(lang, 5, 'bgImage')) {
            var $bgImgZone5 = $('#bgImageInput_' + lang + '_5').closest('.dl-upload-zone');
            $bgImgZone5.addClass('dl-upload-error');
            if (!$bgImgZone5.next('.dl-client-error').length) {
                $bgImgZone5.after('<div class="dl-client-error" style="color:#dc3545;font-size:0.875em;margin-top:4px;">' + Lang.bg_image_required + '</div>');
            }
            valid = false;
        }
        if (!hasFile(lang, 5, 'audio')) {
            var $audZone5 = $('#audioInput_' + lang + '_5').closest('.dl-upload-zone');
            $audZone5.addClass('dl-upload-error');
            if (!$audZone5.next('.dl-client-error').length) {
                $audZone5.after('<div class="dl-client-error" style="color:#dc3545;font-size:0.875em;margin-top:4px;">' + Lang.audio_required + '</div>');
            }
            valid = false;
        }

        return valid;
    }

    // ---- Show/Hide Loader ----
    function showLoader(text) {
        if (text) $('#dlLoaderText').text(text);
        $('#dlOverlayLoader').addClass('active');
    }
    function hideLoader() {
        $('#dlOverlayLoader').removeClass('active');
    }
    function updateLoaderText(text) {
        $('#dlLoaderText').text(text);
    }

    // ---- Send Notification Toggle (per-language) ----
    $(document).on('change', '.dl-notif-toggle', function() {
        var lang = $(this).data('lang');
        if ($(this).is(':checked')) {
            $('#dlNotifFields_' + lang).removeClass('d-none');
        } else {
            $('#dlNotifFields_' + lang).addClass('d-none');
            $('#dlNotifTitle_' + lang + ', #dlNotifMessage_' + lang + ', #dlNotifTime_' + lang).removeClass('is-invalid');
            $('#dlNotifTitleError_' + lang + ', #dlNotifMessageError_' + lang + ', #dlNotifTimeError_' + lang).addClass('d-none');
        }
    });
    $(document).on('input', '[id^="dlNotifTitle_"]', function() {
        var lang = this.id.replace('dlNotifTitle_', '');
        $(this).removeClass('is-invalid'); $('#dlNotifTitleError_' + lang).addClass('d-none');
    });
    $(document).on('input', '[id^="dlNotifMessage_"]', function() {
        var lang = this.id.replace('dlNotifMessage_', '');
        $(this).removeClass('is-invalid'); $('#dlNotifMessageError_' + lang).addClass('d-none');
    });
    $(document).on('change', '[id^="dlNotifTime_"]', function() {
        var lang = this.id.replace('dlNotifTime_', '');
        $(this).removeClass('is-invalid'); $('#dlNotifTimeError_' + lang).addClass('d-none');
        var timeVal = $(this).val();
        if (timeVal) {
            // Notification time must be after publish time
            var publishTime = $('#publishTime').val() || '';
            if (publishTime && timeVal <= publishTime) {
                $(this).addClass('is-invalid');
                $('#dlNotifTimeError_' + lang).text(Lang.notif_time_before_publish).removeClass('d-none');
                return;
            }
            // If publish date is today (Brazil TZ) and selected time is in the past, show error
            var publishDate = $('input[name="publishDate"]').val() || '';
            var brazilNow = getBrazilNow();
            var todayStr = brazilNow.getFullYear() + '-' +
                String(brazilNow.getMonth() + 1).padStart(2, '0') + '-' +
                String(brazilNow.getDate()).padStart(2, '0');
            if (publishDate === todayStr) {
                var parts = timeVal.split(':');
                var selectedMinutes = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
                var nowMinutes = brazilNow.getHours() * 60 + brazilNow.getMinutes();
                if (selectedMinutes <= nowMinutes) {
                    $(this).addClass('is-invalid');
                    $('#dlNotifTimeError_' + lang).text(Lang.notif_time_must_be_future).removeClass('d-none');
                }
            }
        }
    });

    function validateNotification() {
        var valid = true;
        var enabledLangs = getEnabledLangs();

        // Check if publish date is today (Brazil TZ)
        var publishDate = $('input[name="publishDate"]').val() || '';
        var brazilNow = getBrazilNow();
        var todayStr = brazilNow.getFullYear() + '-' +
            String(brazilNow.getMonth() + 1).padStart(2, '0') + '-' +
            String(brazilNow.getDate()).padStart(2, '0');
        var isToday = (publishDate === todayStr);
        var nowMinutes = brazilNow.getHours() * 60 + brazilNow.getMinutes();

        for (var idx = 0; idx < enabledLangs.length; idx++) {
            var lang = enabledLangs[idx];
            if (!$('#dlSendNotification_' + lang).is(':checked')) continue;
            if (!$('#dlNotifTitle_' + lang).val().trim()) {
                $('#dlNotifTitle_' + lang).addClass('is-invalid');
                $('#dlNotifTitleError_' + lang).text(Lang.notif_title_required_dlc).removeClass('d-none');
                valid = false;
            }
            if (!$('#dlNotifMessage_' + lang).val().trim()) {
                $('#dlNotifMessage_' + lang).addClass('is-invalid');
                $('#dlNotifMessageError_' + lang).text(Lang.notif_message_required_dlc).removeClass('d-none');
                valid = false;
            }
            var timeVal = $('#dlNotifTime_' + lang).val();
            var publishTime = $('#publishTime').val() || '';
            if (!timeVal) {
                $('#dlNotifTime_' + lang).addClass('is-invalid');
                $('#dlNotifTimeError_' + lang).text(Lang.notif_time_required_dlc).removeClass('d-none');
                valid = false;
            } else if (publishTime && timeVal <= publishTime) {
                // Notification time must be after publish time
                $('#dlNotifTime_' + lang).addClass('is-invalid');
                $('#dlNotifTimeError_' + lang).text(Lang.notif_time_before_publish).removeClass('d-none');
                valid = false;
            } else if (isToday) {
                // If date is today, time must be in the future
                var parts = timeVal.split(':');
                var selectedMinutes = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
                if (selectedMinutes <= nowMinutes) {
                    $('#dlNotifTime_' + lang).addClass('is-invalid');
                    $('#dlNotifTimeError_' + lang).text(Lang.notif_time_must_be_future).removeClass('d-none');
                    valid = false;
                }
            }
        }
        return valid;
    }

    // ---- Form config ----
    var isEdit = {{ (isset($dailyLight) ? 'true' : 'false') }};
    var editId = {!! json_encode($dailyLight['id'] ?? '') !!};
    var storeMainUrl = {!! json_encode(route('daily-lights.store-main')) !!};
    var uploadFileUrlBase = {!! json_encode(route('daily-lights.upload-file', ['id' => '__ID__'])) !!};
    var storeLangUrlBase = {!! json_encode(route('daily-lights.store-lang', ['id' => '__ID__'])) !!};
    var redirectUrl = {!! json_encode(route('daily-lights.index')) !!};
    var csrfToken = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();

    var langLabels = { pt: 'Português (PT)', en: 'Inglês (EN)', es: 'Espanhol (ES)' };

    // Collect all new files for a language (files user selected in file inputs)
    function collectLangFiles(lang) {
        var files = [];
        for (var i = 1; i <= 5; i++) {
            var imageInput = document.getElementById('imageInput_' + lang + '_' + i);
            if (imageInput && imageInput.files.length > 0) {
                files.push({ step: i, type: 'image', file: imageInput.files[0] });
            }
            var audioInput = document.getElementById('audioInput_' + lang + '_' + i);
            if (audioInput && audioInput.files.length > 0) {
                files.push({ step: i, type: 'audio', file: audioInput.files[0] });
            }
            if (i === 5) {
                var bgInput = document.getElementById('bgImageInput_' + lang + '_5');
                if (bgInput && bgInput.files.length > 0) {
                    files.push({ step: 5, type: 'bgImage', file: bgInput.files[0] });
                }
            }
        }
        return files;
    }

    // Upload a single file and return a promise with the storage key
    // function uploadSingleFile(docId, lang, fileInfo) {
    //     return new Promise(function(resolve, reject) {
    //         var fd = new FormData();
    //         fd.append('_token', csrfToken);
    //         fd.append('lang', lang);
    //         fd.append('step', fileInfo.step);
    //         fd.append('type', fileInfo.type);
    //         fd.append('file', fileInfo.file);

    //         var url = uploadFileUrlBase.replace('__ID__', docId);

    //         $.ajax({
    //             url: url,
    //             type: 'POST',
    //             data: fd,
    //             processData: false,
    //             contentType: false,
    //             headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    //             timeout: 120000,
    //             success: function(resp) {
    //                 if (resp.success) {
    //                     resolve({
    //                         step: fileInfo.step,
    //                         type: fileInfo.type,
    //                         storageKey: resp.storageKey,
    //                         audioDuration: resp.audioDuration || ''
    //                     });
    //                 } else {
    //                     reject(resp.message || Lang.something_wrong);
    //                 }
    //             },
    //             error: function(xhr) {
    //                 var msg = Lang.something_wrong;
    //                 if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
    //                 reject(msg);
    //             }
    //         });
    //     });
    // }

    function uploadSingleFile(docId, lang, fileInfo) {

        return new Promise(async function(resolve, reject) {

            try {

                // STEP 1: Request presigned upload URL

                var fd = new FormData();

                fd.append('_token', csrfToken);
                fd.append('lang', lang);
                fd.append('step', fileInfo.step);
                fd.append('type', fileInfo.type);

                fd.append('file_name', fileInfo.file.name);
                fd.append('file_type', fileInfo.file.type);

                var url = uploadFileUrlBase.replace('__ID__', docId);

                const presignedResp = await $.ajax({
                    url: url,
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    timeout: 120000
                });

                if (!presignedResp.success) {
                    reject(presignedResp.message || Lang.something_wrong);
                    return;
                }

                // STEP 2: Upload directly to R2

                const uploadResponse = await fetch(presignedResp.upload_url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': fileInfo.file.type
                    },
                    body: fileInfo.file
                });

                if (!uploadResponse.ok) {
                    reject('File upload failed.');
                    return;
                }

                // STEP 3: Resolve final response

                resolve({
                    step: fileInfo.step,
                    type: fileInfo.type,
                    storageKey: presignedResp.storage_key,
                    audioDuration: ''
                });

            } catch (xhr) {

                var msg = Lang.something_wrong;

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }

                reject(msg);
            }
        });
    }

    // Upload files one-by-one sequentially, returning collected keys
    function uploadFilesSequentially(docId, lang, files, index, keys, totalFiles, filesUploaded) {
        return new Promise(function(resolve, reject) {
            if (index >= files.length) {
                resolve(keys);
                return;
            }
            var f = files[index];
            var uploadNum = filesUploaded + index + 1;
            var progressText = (Lang.uploading_file || 'Uploading :lang file :current/:total...')
                .replace(':lang', langLabels[lang] || lang.toUpperCase())
                .replace(':current', uploadNum.toString())
                .replace(':total', totalFiles.toString());
            updateLoaderText(progressText);

            uploadSingleFile(docId, lang, f).then(function(result) {
                keys.push(result);
                uploadFilesSequentially(docId, lang, files, index + 1, keys, totalFiles, filesUploaded).then(resolve).catch(reject);
            }).catch(reject);
        });
    }

    // Build text-only FormData for storeLang (no files, just text + file keys)
    function buildLangTextData(lang, fileKeys) {
        var fd = new FormData();
        fd.append('_token', csrfToken);
        fd.append('lang', lang);
        fd.append('is_edit', isEdit ? '1' : '0');

        // Text fields
        fd.append('title_' + lang, $('input[name="title_' + lang + '"]').val() || '');
        fd.append('description_' + lang, $('textarea[name="description_' + lang + '"]').val() || '');

        // Steps 1-5
        for (var i = 1; i <= 5; i++) {
            if (i <= 4) {
                fd.append('step_category_' + lang + '_' + i, $('input[name="step_category_' + lang + '_' + i + '"]').val() || '');
            }
            fd.append('section_title_' + lang + '_' + i, $('input[name="section_title_' + lang + '_' + i + '"]').val() || '');
            fd.append('section_description_' + lang + '_' + i, $('textarea[name="section_description_' + lang + '_' + i + '"]').val() || '');
            if (i === 5) {
                fd.append('forSubscribeMember_' + lang, $('input[name="forSubscribeMember_' + lang + '"]').is(':checked') ? '1' : '0');
            }
        }

        // Add file keys from uploads
        fileKeys.forEach(function(k) {
            fd.append('file_key_' + k.type + '_' + k.step, k.storageKey);
            if (k.type === 'audio' && k.audioDuration) {
                fd.append('file_duration_audio_' + k.step, k.audioDuration);
            }
        });

        // Completion message fields
        fd.append('completion_title_' + lang, $('input[name="completion_title_' + lang + '"]').val() || '');
        fd.append('completion_description_' + lang, $('textarea[name="completion_description_' + lang + '"]').val() || '');

        // Notification fields
        var notifEnabled = $('input[name="send_notification_' + lang + '"]').is(':checked');
        fd.append('send_notification_' + lang, notifEnabled ? '1' : '0');
        if (notifEnabled) {
            fd.append('notif_title_' + lang, $('input[name="notif_title_' + lang + '"]').val() || '');
            fd.append('notif_message_' + lang, $('textarea[name="notif_message_' + lang + '"]').val() || '');
            fd.append('notif_time_' + lang, $('input[name="notif_time_' + lang + '"]').val() || '');
        }

        return fd;
    }

    // Count total files across all languages
    function countTotalFiles(enabledLangs) {
        var total = 0;
        enabledLangs.forEach(function(lang) {
            total += collectLangFiles(lang).length;
        });
        return total;
    }

    // Main save: storeMain → upload files one-by-one → storeLang text → next lang → redirect
    function doSequentialSave(enabledLangs) {
        showLoader(Lang.saving_main_data);

        var mainFd = new FormData();
        mainFd.append('_token', csrfToken);
        mainFd.append('publishDate', $('input[name="publishDate"]').val() || '');
        mainFd.append('publishTime', $('#publishTime').val() || '');
        mainFd.append('is_edit', isEdit ? '1' : '0');
        mainFd.append('edit_id', editId);
        mainFd.append('is_feature', $('input[name="is_feature_pt"]').is(':checked') ? '1' : '0');
        enabledLangs.forEach(function(l) {
            mainFd.append('lang_enabled_' + l, '1');
        });

        $.ajax({
            url: storeMainUrl,
            type: 'POST',
            data: mainFd,
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function(resp) {
                if (!resp.success) {
                    hideLoader();
                    console.error('storeMain failed:', resp);
                    toastr.error(resp.message || Lang.something_wrong);
                    return;
                }
                var docId = resp.docId;
                console.log('storeMain OK, docId:', docId, 'langs:', enabledLangs);
                var totalFiles = countTotalFiles(enabledLangs);
                processLangsSequentially(docId, enabledLangs, 0, totalFiles, 0);
            },
            error: function(xhr) {
                hideLoader();
                console.error('storeMain error:', xhr.status, xhr.responseText);
                var msg = Lang.something_wrong;
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                toastr.error(msg);
            }
        });
    }

    // Process one language: upload its files → save text + keys → next language
    function processLangsSequentially(docId, langs, index, totalFiles, filesUploaded) {
        if (index >= langs.length) {
            updateLoaderText(Lang.save_complete);
            window.location.href = redirectUrl + '?saved=1';
            return;
        }

        var lang = langs[index];
        var label = langLabels[lang] || lang.toUpperCase();
        var files = collectLangFiles(lang);

        // Upload files for this language one-by-one
        uploadFilesSequentially(docId, lang, files, 0, [], totalFiles, filesUploaded).then(function(fileKeys) {
            // All files uploaded — now save text data + keys
            var progressText = (Lang.saving_lang_data || 'Saving :lang... (:current/:total)')
                .replace(':lang', label)
                .replace(':current', (index + 1).toString())
                .replace(':total', langs.length.toString());
            updateLoaderText(progressText);

            var fd = buildLangTextData(lang, fileKeys);
            var url = storeLangUrlBase.replace('__ID__', docId);

            $.ajax({
                url: url,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                timeout: 30000,
                success: function(resp) {
                    console.log('storeLang ' + lang + ' OK:', resp);
                    if (!resp.success) {
                        hideLoader();
                        var errMsg = (Lang.save_failed_lang || 'Failed to save :lang.').replace(':lang', label);
                        toastr.error(errMsg + (resp.message ? ' ' + resp.message : ''));
                        return;
                    }
                    processLangsSequentially(docId, langs, index + 1, totalFiles, filesUploaded + files.length);
                },
                error: function(xhr) {
                    hideLoader();
                    console.error('storeLang ' + lang + ' error:', xhr.status, xhr.responseText);
                    var errMsg = (Lang.save_failed_lang || 'Failed to save :lang.').replace(':lang', label);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var firstErr = Object.values(xhr.responseJSON.errors)[0];
                        if (Array.isArray(firstErr)) firstErr = firstErr[0];
                        errMsg += ' ' + firstErr;
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg += ' ' + xhr.responseJSON.message;
                    }
                    toastr.error(errMsg);
                }
            });
        }).catch(function(err) {
            hideLoader();
            console.error('File upload failed for ' + lang + ':', err);
            var errMsg = (Lang.upload_failed || 'File upload failed for :lang.').replace(':lang', label);
            toastr.error(errMsg + ' ' + err);
        });
    }

    // ---- Form Validation — sequential per enabled language ----
    $('#dailyLightForm').on('submit', function(e) {
        e.preventDefault(); // Always prevent default — we use AJAX now

        // Block submission in readonly mode
        if (isReadonly) {
            return false;
        }

        // Clear previous client errors
        $('.dl-validate-field').removeClass('is-invalid');
        $('.dl-client-error').remove();
        $('.dl-dimension-error').remove();
        $('.dl-upload-zone').removeClass('dl-upload-error');
        $('.dlc-trigger').removeClass('dlc-trigger-error');

        // Check publish date (skip if date is locked for published items)
        var $publishDate = $('input[name="publishDate"]:not([disabled])');
        if ($publishDate.length && !$publishDate.val()) {
            $publishDate.addClass('is-invalid');
            if (!$publishDate.next('.dl-client-error').length) {
                $publishDate.after('<div class="invalid-feedback dl-client-error">' + Lang.required_field + '</div>');
            }
            $('html, body').animate({ scrollTop: $publishDate.offset().top - 120 }, 300);
            return false;
        }

        // Block if date already exists
        if (dateExists) {
            $publishDate.addClass('is-invalid');
            $('#dateExistsError').addClass('active');
            $('html, body').animate({ scrollTop: $publishDate.offset().top - 120 }, 300);
            return false;
        }

        // Block if publish time is in the past for today
        if (!validatePublishTime()) {
            $('html, body').animate({ scrollTop: $('#publishTime').offset().top - 120 }, 300);
            return false;
        }

        // Validate enabled languages sequentially
        var enabledLangs = getEnabledLangs();

        for (var idx = 0; idx < enabledLangs.length; idx++) {
            var lang = enabledLangs[idx];
            var isValid = validateLang(lang);

            if (!isValid) {
                // Switch to this language tab
                var errorLang = lang;
                $('#tab-' + errorLang).tab('show');

                // Update dots
                allLangs.forEach(function(l) { updateLangDot(l); });
                $('#tab-' + errorLang).find('.dl-lang-dot').removeClass('active').addClass('error');

                // Scroll to first error after tab switch completes
                setTimeout(function() {
                    var $firstError = $('#panel-' + errorLang).find('.is-invalid, .dl-upload-error').first();
                    if ($firstError.length) {
                        $('html, body').animate({ scrollTop: $firstError.offset().top - 120 }, 300);
                    }
                }, 200);

                return false;
            }
        }

        // Validate notification fields if enabled (per-language)
        if (!validateNotification()) {
            var $firstNotifErr = $('[id^="dlNotifTitleError_"], [id^="dlNotifMessageError_"], [id^="dlNotifTimeError_"]').not('.d-none').first();
            if ($firstNotifErr.length) {
                var notifLang = $firstNotifErr.attr('id').split('_').pop();
                $('#tab-' + notifLang).tab('show');
                setTimeout(function() {
                    $('html, body').animate({ scrollTop: $firstNotifErr.offset().top - 120 }, 300);
                }, 200);
            }
            return false;
        }

        // All validation passed — start sequential AJAX save
        doSequentialSave(enabledLangs);
    });
});
</script>
