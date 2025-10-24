jQuery(document).ready(function($) {
    $(document).on('click', '.editinline', function(e) {
        var $row = $(this).closest('tr');
        var postId = $row.attr('id').replace('post-', '');
        $('.quickedit-fieldset').each(function() {
            var $fieldset = $(this);
            var fieldsetId = $fieldset.attr('id');
            var taxonomy = fieldsetId.replace('_term', '');
            var termId = $('#' + taxonomy + '_' + postId).text().trim();
            
            if (termId) {
                $fieldset.find('input[value="' + termId + '"]').prop('checked', true);
            } else {
                $fieldset.find('input[value=""]').prop('checked', true);
            }
        });
    });
});