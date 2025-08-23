(function($) {
    'use strict';
    $(function() {
        const inlineEditTax = window.inlineEditTax;
        const wp_edit = inlineEditTax.edit;
        inlineEditTax.edit = function(id) {
        wp_edit.apply(this, arguments);
        setTimeout(function() {
        const termId = $(id).closest('tr').attr('id').replace('tag-', '');
        const hiddenInputClass = '.quick-edit-' + dibraco_qe_data.meta_key;
        const parentTermId = $('#tag-' + termId).find(hiddenInputClass).val();
        if (typeof parentTermId === 'undefined') {
            console.error("ERROR: Could not find the hidden input. Check class name and if the row was found.");
            return;
        }
        const selector = 'input[name="' + dibraco_qe_data.meta_key + '"][value="' + parentTermId + '"]';
        $('#edit-' + termId).find(selector).prop('checked', true);
    }, 0);
};

        const sortableColumnName = dibraco_qe_data.sortable_name;
        const $header = $(`th.column-${sortableColumnName}`);

        $header.on('click', 'a', function(e) {
            e.preventDefault();

            const $tbody = $('table.wp-list-table tbody');
            const rows = $tbody.find('tr:not(.inline-editor)').get();
            const currentOrder = $header.hasClass('asc') ? 'desc' : 'asc';

            rows.sort(function(a, b) {
                const valueA = $(a).find(`td.${sortableColumnName} span.${sortableColumnName}`).text().toUpperCase();
                const valueB = $(b).find(`td.${sortableColumnName} span.${sortableColumnName}`).text().toUpperCase();

                const aIsEmpty = (valueA === '');
                const bIsEmpty = (valueB === '');

                if (aIsEmpty && !bIsEmpty) {
                    return 1;
                }
                if (!aIsEmpty && bIsEmpty) {
                    return -1;
                }

                if (valueA < valueB) return currentOrder === 'asc' ? -1 : 1;
                if (valueA > valueB) return currentOrder === 'asc' ? 1 : -1;
                
                return 0;
            });
            
            $('th.sorted').removeClass('sorted asc desc');
            $header.addClass('sorted').addClass(currentOrder);

            $.each(rows, function(index, row) {
                $tbody.append(row);
            });
        });
    });

})(jQuery);