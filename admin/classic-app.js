window.ptsInitClassic = function () {
    jQuery(function ($) {
        const $wrap = $('#cts-app');
        if (!$wrap.length || $wrap.data('pts-inited')) {
            return;
        }
        $wrap.data('pts-inited', true);

        const rootData = window.ptsData || {};
        const data = Object.assign(
            {
                restUrl: rootData.restUrl,
                restNonce: rootData.restNonce,
                presets: [],
                templateGroups: [],
                featureGroups: [],
                strings: {},
            },
            rootData.classic || {}
        );

        const $form = $('#cts-form');
        const $steps = $wrap.find('.cts-steps li');
        const $stepContents = $wrap.find('.cts-step-content');
        const $fileList = $('#cts-file-list');

        function renderDescRow(item) {
            const $row = $('<div class="pts-checkbox-row"></div>');
            const $label = $('<label class="pts-checkbox-label"></label>');
            const checked = item.checked !== false;
            const disabled = item.disabled === true;
            const $input = $(
                `<input type="checkbox" class="cts-selection-cb" name="selection[${item.id}]" value="1">`
            );
            $input.prop('checked', checked);
            if (disabled) {
                $input.prop('disabled', true);
            }
            $label.append($input);
            $label.append(document.createTextNode(' ' + item.name));
            $row.append($label);
            if (item.description) {
                $row.append($('<p class="pts-item-desc"></p>').text(item.description));
            }
            return $row;
        }

        function renderGroupedList($container, groups) {
            $container.empty();
            if (!groups || !groups.length) {
                return;
            }
            groups.forEach((group) => {
                const $section = $('<div class="cts-checkbox-section"></div>');
                $section.append($('<p class="cts-group-title"></p>').append($('<strong></strong>').text(group.title)));
                if (group.intro) {
                    $section.append($('<p class="description pts-group-intro"></p>').text(group.intro));
                }
                const $list = $('<div class="cts-group-items"></div>');
                (group.items || []).forEach((item) => {
                    $list.append(renderDescRow(item));
                });
                $section.append($list);
                $container.append($section);
            });
        }

        renderGroupedList($('#cts-templates-panel'), data.templateGroups);
        renderGroupedList($('#cts-features-panel'), data.featureGroups);

        setupLayoutMode();

        function setupLayoutMode() {
            $form.on('change', '.bts-layout-mode-radio', function () {
                syncSidebarFromLayoutMode();
                updatePreview();
            });

            $form.on('change', 'input[name="selection[templates.sidebar]"]', function () {
                syncLayoutModeFromSidebar();
                updatePreview();
            });
        }

        function syncSidebarFromLayoutMode() {
            const mode = $form.find('input[name="params[layoutMode]"]:checked').val() || 'one-column';
            const $sidebar = $form.find('input[name="selection[templates.sidebar]"]');
            if ($sidebar.length) {
                $sidebar.prop('checked', mode === 'two-column');
            }
        }

        function syncLayoutModeFromSidebar() {
            const $sidebar = $form.find('input[name="selection[templates.sidebar]"]');
            const mode = $sidebar.length && $sidebar.is(':checked') ? 'two-column' : 'one-column';
            $form.find(`input[name="params[layoutMode]"][value="${mode}"]`).prop('checked', true);
        }

        syncLayoutModeFromSidebar();

        $wrap.find('.next-step').on('click', function () {
            const $currentStep = $wrap.find('.cts-step-content.active');
            const stepNum = parseInt($currentStep.data('step'), 10);

            if (stepNum === 1) {
                if (!$('#cts-themeName').val() || !$('#cts-themeSlug').val()) {
                    alert(data.strings.fillRequired || 'Please fill in Theme Name and Slug.');
                    return;
                }
            }

            if (stepNum === 4) {
                updatePreview();
            }

            goToStep(stepNum + 1);
        });

        $wrap.find('.prev-step').on('click', function () {
            const stepNum = parseInt($wrap.find('.cts-step-content.active').data('step'), 10);
            goToStep(stepNum - 1);
        });

        $steps.on('click', function () {
            goToStep(parseInt($(this).data('step'), 10));
        });

        function goToStep(num) {
            if (num < 1 || num > 6) {
                return;
            }
            $steps.removeClass('active');
            $steps.filter(`[data-step="${num}"]`).addClass('active');
            $stepContents.removeClass('active');
            $stepContents.filter(`[data-step="${num}"]`).addClass('active');
            if (num === 5) {
                updatePreview();
            }
        }

        function renderPresets() {
            const $container = $('#cts-preset-list');
            $container.empty();

            if (data.presets && data.presets.length > 0) {
                data.presets.forEach((preset) => {
                    const $item = $(`
                    <div class="cts-preset-item" data-id="${preset.id}" style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; cursor: pointer;">
                        <h4></h4>
                        <p></p>
                        <button type="button" class="button select-preset">${data.strings.select || 'Select'}</button>
                    </div>
                `);
                    $item.find('h4').text(preset.name);
                    $item.find('p').text(preset.description);
                    $item.find('.select-preset').on('click', function (e) {
                        e.stopPropagation();
                        applyPreset(preset);
                        $container.find('.cts-preset-item').removeClass('is-selected').css('background', 'none');
                        $item.addClass('is-selected').css('background', '#f0f6fc');
                    });
                    $container.append($item);
                });
            }
        }

        function applyPreset(preset) {
            if (!preset.apply) {
                return;
            }

            $form.find('input[type="checkbox"]:not([disabled])').prop('checked', false);

            if (preset.apply.full) {
                $form.find('input[type="checkbox"]:not([disabled])').prop('checked', true);
            } else if (preset.apply.selection) {
                for (const key in preset.apply.selection) {
                    if (preset.apply.selection[key]) {
                        $form.find(`input[name="selection[${key}]"]`).prop('checked', true);
                    }
                }
            }

            $form.find('input[name="selection[templates.index]"]').prop('checked', true);
            syncLayoutModeFromSidebar();
        }

        function updatePreview() {
            $fileList.empty();
            $('#cts-feature-list').empty();

            const layoutMode = $form.find('input[name="params[layoutMode]"]:checked').val() || 'one-column';
            const layoutLabel =
                layoutMode === 'two-column'
                    ? (data.strings && data.strings.layoutTwoColumn) || 'Default layout: 2 columns'
                    : (data.strings && data.strings.layoutOneColumn) || 'Default layout: 1 column';
            $fileList.append($('<li></li>').text(layoutLabel));

            const files = ['style.css', 'index.php', 'functions.php'];

            (data.templateGroups || []).forEach((group) => {
                (group.items || []).forEach((item) => {
                    if ($form.find(`input[name="selection[${item.id}]"]`).is(':checked')) {
                        const file = item.name.replace(/\s+—\s+.+$/, '').trim();
                        if (file) {
                            files.push(file);
                        }
                    }
                });
            });

            [...new Set(files)].forEach((f) => $fileList.append($(`<li>${f}</li>`)));

            (data.featureGroups || []).forEach((group) => {
                (group.items || []).forEach((item) => {
                    if ($form.find(`input[name="selection[${item.id}]"]`).is(':checked')) {
                        $('#cts-feature-list').append($('<li></li>').text(item.name));
                    }
                });
            });
        }

        $('#cts-generate-btn').on('click', function () {
            const $btn = $(this);
            $btn.prop('disabled', true).text(data.strings.generating || 'Generating...');

            const payload = {
                themeType: 'classic',
                themeName: $('#cts-themeName').val(),
                themeSlug: $('#cts-themeSlug').val(),
                themeAuthor: $('#cts-themeAuthor').val(),
                themeAuthorUri: $('#cts-themeAuthorUri').val(),
                themeDescription: $('#cts-themeDescription').val(),
                outputMode: $form.find('input[name="outputMode"]:checked').val(),
                params: { rootLayout: {}, layoutMode: 'one-column' },
                selection: {},
            };

            $form.serializeArray().forEach((field) => {
                const rootMatch = field.name.match(/^params\[rootLayout\]\[(.*?)\]$/);
                if (rootMatch) {
                    payload.params.rootLayout[rootMatch[1]] = field.value;
                    return;
                }
                if (field.name === 'params[layoutMode]') {
                    payload.params.layoutMode = field.value;
                }
            });

            $form.find('input[type="checkbox"]:checked').each(function () {
                const name = $(this).attr('name');
                const match = name && name.match(/selection\[(.*?)\]/);
                if (match) {
                    payload.selection[match[1]] = true;
                }
            });

            payload.selection['templates.index'] = true;

            $.ajax({
                url: data.restUrl,
                method: 'POST',
                beforeSend(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', data.restNonce);
                },
                data: JSON.stringify(payload),
                contentType: 'application/json',
                success(response) {
                    if (response.zipUrl) {
                        alert(data.strings.success || 'Theme generated successfully.');
                        window.location.href = response.zipUrl;
                    } else if (response.message) {
                        alert(response.message);
                    } else {
                        alert(data.strings.success || 'Theme generated successfully.');
                    }
                    $btn.prop('disabled', false).text(data.strings.generateBtn || 'Generate Theme');
                },
                error(xhr) {
                    alert(
                        (data.strings.error || 'Error: ') +
                            (xhr.responseJSON
                                ? xhr.responseJSON.message
                                : data.strings.unknownError || 'Unknown error')
                    );
                    $btn.prop('disabled', false).text(data.strings.generateBtn || 'Generate Theme');
                },
            });
        });

        renderPresets();
    });
};
