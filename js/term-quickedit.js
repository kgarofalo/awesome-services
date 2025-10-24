(function ($) {
    $(document).on('click', 'button.editinline', function () {
        const termRow = $(this).closest('tr');
        const areaTermId = parseInt(termRow.attr('id').replace('tag-', ''), 10);
        const locationTermId = termRow.find('.area_parent_location_term').data('location-term-id') || '';

        setTimeout(() => {
           const editRow = $('#inline-edit'); 
            const radioFieldset = editRow.find('.dibraco-radio-fieldset');
            const selector = `input[name="area_parent_location_term"][value="${locationTermId}"]`;
            const targetRadio = radioFieldset.find(selector);
            if (targetRadio.length) {
                targetRadio.prop('checked', true);
            }
        }, 50);
    });
})(jQuery);
