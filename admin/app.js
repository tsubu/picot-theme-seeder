jQuery(function ($) {
    const data = window.ptsData || {};
    let selectedType = null;
    let blockInited = false;
    let classicInited = false;

    const $stepType = $('#pts-step-type');
    const $panelBlock = $('#pts-wizard-block');
    const $panelClassic = $('#pts-wizard-classic');
    const $continueBtn = $('#pts-continue-btn');
    const $typeHint = $('#pts-type-hint');

    $('.pts-type-card').on('click', function () {
        $('.pts-type-card').removeClass('is-selected');
        $(this).addClass('is-selected');
        selectedType = $(this).data('theme-type');
        $continueBtn.prop('disabled', false);
        $typeHint.prop('hidden', true);
    });

    $continueBtn.on('click', function () {
        if (!selectedType) {
            $typeHint.text(data.strings.chooseType || 'Please choose a theme type.').prop('hidden', false);
            return;
        }
        showWizard(selectedType);
    });

    $('.pts-change-type').on('click', function () {
        $stepType.prop('hidden', false);
        $panelBlock.prop('hidden', true);
        $panelClassic.prop('hidden', true);
        $('html, body').animate({ scrollTop: $stepType.offset().top - 32 }, 200);
    });

    function showWizard(type) {
        $stepType.prop('hidden', true);
        if (type === 'block') {
            $panelBlock.prop('hidden', false);
            $panelClassic.prop('hidden', true);
            if (!blockInited && typeof window.ptsInitBlock === 'function') {
                window.ptsInitBlock();
                blockInited = true;
            }
        } else {
            $panelClassic.prop('hidden', false);
            $panelBlock.prop('hidden', true);
            if (!classicInited && typeof window.ptsInitClassic === 'function') {
                window.ptsInitClassic();
                classicInited = true;
            }
        }
    }
});
