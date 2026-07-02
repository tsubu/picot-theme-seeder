window.picotseInitBlock = function () {
    jQuery(function ($) {
        const $wrap = $('#bts-app');
        if (!$wrap.length || $wrap.data('pts-inited')) {
            return;
        }
        $wrap.data('pts-inited', true);

        const rootData = window.picotseData || {};
        const btsData = Object.assign(
            {
                restUrl: rootData.restUrl,
                restNonce: rootData.restNonce,
                presets: [],
                categoryLabels: {},
                strings: {},
            },
            rootData.block || {}
        );

        const state = {
            presets: [],
            currentStep: 1,
            selection: {},
            params: {},
            currentFilter: 'all',
        };

        const definitions = btsData.definitions || {
            templates: [],
            parts: [],
            partsExtended: [],
            partsJpLp: [],
            partsProductLp: [],
            partsLayoutKit: [],
            patterns: [],
        };

        const labelById = {};
        ['templates', 'parts', 'partsExtended', 'partsJpLp', 'partsProductLp', 'partsLayoutKit', 'patterns'].forEach((group) => {
            (definitions[group] || []).forEach((item) => {
                labelById[item.id] = item.name;
            });
        });

        const $form = $('#bts-form');

        const partsSets = [
            { checkbox: '#bts-parts-set-basic', list: '#bts-parts-list', id: 'parts.basicSet' },
            { checkbox: '#bts-parts-set-extended', list: '#bts-parts-extended-list', id: 'parts.extendedSet' },
            { checkbox: '#bts-parts-set-jplp', list: '#bts-parts-jplp-list', id: 'parts.jpLpSet' },
            { checkbox: '#bts-parts-set-productlp', list: '#bts-parts-productlp-list', id: 'parts.productLpSet' },
            { checkbox: '#bts-parts-set-layoutkit', list: '#bts-parts-layoutkit-list', id: 'parts.layoutKitSet' },
        ];

        function getPartsSetMembers(set) {
            const $inputs = $(set.list + ' input.bts-selection-cb');
            if (set.id === 'parts.basicSet') {
                return $inputs.filter(function () {
                    return $(this).data('basicSetMember') !== 0;
                });
            }
            return $inputs;
        }

        function applyPartsSetMembers(set, checked) {
            getPartsSetMembers(set).prop('checked', checked);
        }

        function syncPartsSetMasterFromMembers(set) {
            const $setCb = $(set.checkbox);
            if (!$setCb.length) {
                return;
            }
            const $members = getPartsSetMembers(set);
            const allChecked = $members.length > 0 && $members.filter(':not(:checked)').length === 0;
            $setCb.prop('checked', allChecked);
        }

        function syncAllPartsSetsFromMasters() {
            partsSets.forEach((set) => {
                const $setCb = $(set.checkbox);
                if ($setCb.length && $setCb.is(':checked')) {
                    applyPartsSetMembers(set, true);
                }
            });
        }

        function init() {
            loadPresets();
            setupNavigation();
            setupFilters();
            setupForm();
            setupLayoutMode();
            renderCheckboxes();
            setupPartsSet();
            syncAllPartsSetsFromMasters();
            syncLayoutModeFromSidebar();
        }

        function setupLayoutMode() {
            $form.on('change', '.bts-layout-mode-radio', function () {
                syncSidebarFromLayoutMode();
                updatePreview();
            });

            $form.on('change', 'input[name="selection[parts.sidebar]"]', function () {
                syncLayoutModeFromSidebar();
                updatePreview();
            });
        }

        function syncSidebarFromLayoutMode() {
            const mode = $form.find('input[name="params[layoutMode]"]:checked').val() || 'one-column';
            const $sidebar = $form.find('input[name="selection[parts.sidebar]"]');
            if ($sidebar.length) {
                $sidebar.prop('checked', mode === 'two-column');
            }
        }

        function syncLayoutModeFromSidebar() {
            const $sidebar = $form.find('input[name="selection[parts.sidebar]"]');
            const mode = $sidebar.length && $sidebar.is(':checked') ? 'two-column' : 'one-column';
            $form.find(`input[name="params[layoutMode]"][value="${mode}"]`).prop('checked', true);
        }

        function setupPartsSet() {
            partsSets.forEach((set) => {
                const $setCb = $(set.checkbox);
                if (!$setCb.length) {
                    return;
                }
                labelById[set.id] = $setCb.data('label') || set.id;

                // Checking the set checks every part in its list; unchecking clears them.
                $setCb.on('change', function () {
                    applyPartsSetMembers(set, this.checked);
                    updatePreview();
                });

                // Keep the set checkbox in sync with the individual part checkboxes.
                $form.on('change', set.list + ' input.bts-selection-cb', function () {
                    syncPartsSetMasterFromMembers(set);
                    updatePreview();
                });
            });
        }

        function renderCheckboxes() {
            renderList('bts-templates-list', definitions.templates);
            renderList('bts-parts-list', definitions.parts);
            renderList('bts-parts-extended-list', definitions.partsExtended);
            renderList('bts-parts-jplp-list', definitions.partsJpLp);
            renderList('bts-parts-productlp-list', definitions.partsProductLp);
            renderList('bts-parts-layoutkit-list', definitions.partsLayoutKit);
            renderList('bts-patterns-list', definitions.patterns);
        }

        function renderList(containerId, items) {
            const container = $('#' + containerId);
            container.empty();
            items.forEach((item) => {
                const $row = $('<div class="pts-checkbox-row"></div>');
                const $label = $('<label class="pts-checkbox-label"></label>');
                const $input = $(
                    `<input type="checkbox" class="bts-selection-cb" name="selection[${item.id}]" value="1">`
                );
                if (item.basicSet === false) {
                    $input.attr('data-basic-set-member', '0');
                }
                if (item.checked !== false) {
                    $input.prop('checked', true);
                }
                $label.append($input);
                $label.append(document.createTextNode(' ' + item.name));
                $row.append($label);
                if (item.description) {
                    $row.append($('<p class="pts-item-desc"></p>').text(item.description));
                }
                container.append($row);
            });
        }

        function loadPresets() {
            state.presets = btsData.presets && btsData.presets.length > 0 ? btsData.presets : [];
            renderPresets();
        }

        function setupFilters() {
            $wrap.find('.bts-filter-btn').on('click', function () {
                $wrap.find('.bts-filter-btn').removeClass('active');
                $(this).addClass('active');
                state.currentFilter = $(this).data('filter');
                renderPresets();
            });
        }

        function renderPresets() {
            const container = $('#bts-preset-list');
            container.empty();

            const filtered = state.presets.filter((p) => {
                if (state.currentFilter === 'all') {
                    return true;
                }
                return p.category === state.currentFilter;
            });

            if (filtered.length === 0) {
                const noPresets =
                    btsData.strings && btsData.strings.noPresets
                        ? btsData.strings.noPresets
                        : 'No presets found for this category.';
                container.append($('<p></p>').text(noPresets));
                return;
            }

            const categoryLabels = btsData.categoryLabels || {};
            filtered.forEach((preset) => {
                const categoryLabel = categoryLabels[preset.category] || preset.category;
                const card = $('<div class="bts-card"></div>')
                    .append($('<h3></h3>').text(preset.name))
                    .append($('<div class="bts-badge"></div>').text(categoryLabel))
                    .append($('<p></p>').text(preset.description))
                    .data('preset', preset)
                    .on('click', function () {
                        $wrap.find('.bts-card').removeClass('selected');
                        $(this).addClass('selected');
                        applyPreset(preset);
                    });
                container.append(card);
            });
        }

        function applyPreset(preset) {
            $form.find('.bts-selection-cb').prop('checked', false);

            if (preset.apply && preset.apply.selection) {
                for (const key in preset.apply.selection) {
                    if (preset.apply.selection[key]) {
                        const $el = $form.find(`input[name="selection[${key}]"]`);
                        if ($el.length) {
                            $el.prop('checked', true);
                        }
                    }
                }
            }

            // Presets often enable *.Set flags only; expand them to every child checkbox.
            syncAllPartsSetsFromMasters();
            syncLayoutModeFromSidebar();

            $form.find('.bts-themejson-cb').prop('checked', true);
            $form.find('input[name="selection[templates.index]"]').prop('checked', true);
            updatePreview();
        }

        function setupNavigation() {
            $wrap.find('.next-step').on('click', function () {
                if (state.currentStep === 1) {
                    if (!$('#bts-themeName').val() || !$('#bts-themeSlug').val()) {
                        alert(
                            (btsData.strings && btsData.strings.fillRequired) ||
                                'Please fill in Theme Name and Slug.'
                        );
                        return;
                    }
                }

                if (state.currentStep < 6) {
                    state.currentStep++;
                    showStep(state.currentStep);
                }
            });

            $wrap.find('.prev-step').on('click', function () {
                if (state.currentStep > 1) {
                    state.currentStep--;
                    showStep(state.currentStep);
                }
            });

            $wrap.find('.bts-steps li').on('click', function () {
                const step = $(this).data('step');
                state.currentStep = step;
                showStep(step);
            });
        }

        function showStep(step) {
            $wrap.find('.bts-step-content').removeClass('active');
            $wrap.find(`.bts-step-content[data-step="${step}"]`).addClass('active');
            $wrap.find('.bts-steps li').removeClass('active');
            $wrap.find(`.bts-steps li[data-step="${step}"]`).addClass('active');

            if (step === 5) {
                updatePreview();
            }
        }

        function setupForm() {
            $('#bts-generate-btn').on('click', function () {
                const rawArray = $form.serializeArray();
                const structured = { themeType: 'block' };

                rawArray.forEach((field) => {
                    const name = field.name;
                    const value = field.value;
                    const matchSel = name.match(/^selection\[(.*?)\]$/);
                    if (matchSel) {
                        if (!structured.selection) {
                            structured.selection = {};
                        }
                        structured.selection[matchSel[1]] = value;
                        return;
                    }
                    if (!name.includes('[')) {
                        structured[name] = value;
                        return;
                    }
                    const keys = name.replace(/\]/g, '').split('[');
                    let current = structured;
                    for (let i = 0; i < keys.length; i++) {
                        const key = keys[i];
                        if (i === keys.length - 1) {
                            current[key] = value;
                        } else {
                            current[key] = current[key] || {};
                            current = current[key];
                        }
                    }
                });

                const $btn = $(this);
                const generatingLabel =
                    (btsData.strings && btsData.strings.generating) || 'Generating...';
                const generateBtnLabel =
                    (btsData.strings && btsData.strings.generateBtn) || 'Generate Theme';
                const successLabel =
                    (btsData.strings && btsData.strings.success) ||
                    'Theme generated successfully.';
                const errorPrefix =
                    (btsData.strings && btsData.strings.error) || 'Error: ';
                $btn.prop('disabled', true).text(generatingLabel);

                $.ajax({
                    url: btsData.restUrl,
                    method: 'POST',
                    beforeSend(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', btsData.restNonce);
                    },
                    data: JSON.stringify(structured),
                    contentType: 'application/json',
                    success(response) {
                        if (response.zipUrl) {
                            window.location.href = response.zipUrl;
                        } else if (response.success && response.message) {
                            alert(response.message);
                        } else if (response.message) {
                            alert(response.message);
                        } else {
                            alert(successLabel);
                        }
                    },
                    error(xhr, textStatus, errorThrown) {
                        const unknownError =
                            (btsData.strings && btsData.strings.unknownError) || 'Unknown error';
                        let msg = unknownError;
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        } else if (errorThrown) {
                            msg = errorThrown;
                        }
                        alert(errorPrefix + msg);
                    },
                    complete() {
                        $btn.prop('disabled', false).text(generateBtnLabel);
                    },
                });
            });
        }

        function updatePreview() {
            const list = $('#bts-file-list');
            list.empty();

            const layoutMode = $form.find('input[name="params[layoutMode]"]:checked').val() || 'one-column';
            const layoutLabel =
                layoutMode === 'two-column'
                    ? (btsData.strings && btsData.strings.layoutTwoColumn) || 'Default layout: 2 columns'
                    : (btsData.strings && btsData.strings.layoutOneColumn) || 'Default layout: 1 column';
            list.append($('<li></li>').text(layoutLabel));

            $form.find('input[type="checkbox"]:checked').each(function () {
                const name = $(this).attr('name');
                const match = name && name.match(/selection\[(.*?)\]/);
                if (match) {
                    const label = labelById[match[1]] || match[1];
                    list.append($('<li></li>').text(label));
                }
            });
        }

        init();
    });
};
