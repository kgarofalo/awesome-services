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
jQuery(function ($) {
    $(document).on('click', '#bulk_edit', function (e) {
        e.preventDefault();

        const bulkEditRow = $(this).closest('tr');
        const localizedTaxonomies = quickEditData.taxonomy;
   const nonceValue = $('input[name="dibraco_bulk_edit_token"]').val();


        const ajaxData = {
            action: 'bulk_save_taxonomy_terms_on_post',
            nonce: nonceValue,
            post_ids: [],
            taxonomies: localizedTaxonomies
        };

        console.log('Nonce:', ajaxData.nonce);  

        bulkEditRow.find('#bulk-titles-list .ntdelbutton').each(function () {
            const id = $(this).attr('id');
            ajaxData.post_ids.push(id.replace('_', ''));
        });

        console.log('Post IDs:', ajaxData.post_ids); 

        localizedTaxonomies.forEach(function (taxonomy_slug) {
            const fieldName = `${taxonomy_slug}_term`;
            const selectedTerm = bulkEditRow.find(`select[name="${fieldName}"]`).val();

            ajaxData[fieldName] = selectedTerm;
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: ajaxData,
            success: function (response) {
                console.log('Response from server:', response);  // Log the full response from the server
                if (response.success) {
                    location.reload();  // Reload the page on success
                } else {
                    alert('Server error: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);  // Log the actual error for debugging
                alert('A critical server error occurred.');
            }
        });
    });
});







 