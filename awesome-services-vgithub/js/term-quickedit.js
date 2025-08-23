jQuery(function($) {
    const wpInlineEdit = inlineEditTax.edit;
    inlineEditTax.edit = function(id) {
        wpInlineEdit.apply(this, arguments);
        const termId = (typeof id === 'object' && id !== null)
            ? $(id).closest('tr').attr('id').replace('tag-', '')
            : id;
        setTimeout(function() {
            const row = $('#tag-' + termId);
            const parentTermId = $(row).find('.dibraco-term-quickedit-data').val();
            if (typeof dibraco_qe_data === 'undefined') {
                return;
            }
            const selector = 'input[name="' + dibraco_qe_data.meta_key + '"][value="' + parentTermId + '"]';
            $('#edit-' + termId).find(selector).prop('checked', true);
        }, 0);
    };
});
