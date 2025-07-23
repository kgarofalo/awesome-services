jQuery(document).ready(function($) {
        var allTaxonomyTermMaps = typeof quickEditData !== 'undefined' ? quickEditData.taxonomy_term_maps : {};

$('body').on('click', '.row-actions .editinline', function() {     
      var $postRow = $(this).closest('tr');
        var idString = $postRow.attr('id'); 
        var postId = parseInt(idString.replace('post-', ''), 10);
        var $editRow = $('#edit-' + postId); 
        populateQuickEdit($postRow, $editRow, postId);
    });


function populateQuickEdit($postRow, $qeRow, postId) {
    if (!$qeRow.length) {
        return;
    }
    var $inlineDataContainer = $('#inline_' + postId);
    if (!$inlineDataContainer.length) {
        $inlineDataContainer = $postRow.find('.hidden');
        if (!$inlineDataContainer.length) {
            return;
        }
    }
    $qeRow.find('fieldset[id$="_term"]').each(function() {
        var $fieldset = $(this);
        var fieldId = $fieldset.attr('id');
        var taxonomy = fieldId.replace('_term', '');  
        if (!taxonomy) {
            return;
        }
        var $inlineTermElement = $inlineDataContainer.find('.' + taxonomy); 
        if ($inlineTermElement.length) {
        } else {
            $inlineTermElement = $inlineDataContainer.find('#' + taxonomy + '_' + postId); 
            if ($inlineTermElement.length) {
            } else {
                $inlineTermElement = $inlineDataContainer.find('input[name^="tax_input[' + taxonomy + ']"]');
                if ($inlineTermElement.length) {
                }
            }
        }
        var savedTermId = '';
        if ($inlineTermElement.is('input')) {
            savedTermId = String($.trim($inlineTermElement.val()));
        } else if ($inlineTermElement.length) {
            savedTermId = String($.trim($inlineTermElement.text()));
        }
        var $radios = $fieldset.find('input[type="radio"][name="' + fieldId + '"]');
        var $target = $radios.filter('[value="' + savedTermId + '"]');
        if ($target.length) {
            $target.prop('checked', true);
        } else {
            var $none = $radios.filter('[value=""]');
            if ($none.length) {
                $none.prop('checked', true); 
            }
        }
        var $commonParentForCheckboxAndRadios = $fieldset.closest('div[class*="inline-edit-"][class$="-input"]');
            var $mainPostCheckbox = $commonParentForCheckboxAndRadios.find('input[type="checkbox"][name="main_post_for_term"]');
            if ($mainPostCheckbox.length && allTaxonomyTermMaps[taxonomy]) {
                var currentTaxonomyMap = allTaxonomyTermMaps[taxonomy];
                var mainPostIdFromMap = parseInt(currentTaxonomyMap[savedTermId], 10);
                var shouldBeChecked = (savedTermId && !isNaN(mainPostIdFromMap) && mainPostIdFromMap === postId);
                $mainPostCheckbox.prop('checked', shouldBeChecked);
            } else if ($mainPostCheckbox.length) {
                $mainPostCheckbox.prop('checked', false);
            } 
    });
}
});
