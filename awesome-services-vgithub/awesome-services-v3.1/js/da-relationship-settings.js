jQuery(document).ready(function($) {

    $(document).on('change', '[id$="_post_type"]', function() {
        var selectedPostType = $(this).val();
        var contextName = this.id.replace('_post_type', '');

        if (selectedPostType) {
            var taxonomySelect = $(`#${contextName}_connector_tax, #${contextName}_type_taxonomy`);
            if (taxonomySelect.length) {
                updateTaxonomyOrTermField(selectedPostType, taxonomySelect, 'awesome_get_taxonomies_for_post_type');
            }
        }
    });

    $(document).on('change', '#locations_connector_tax', function() {
        var selectedTaxonomy = $(this).val();
        if (selectedTaxonomy) {
            updateTaxonomyOrTermField(selectedTaxonomy, $('#main_term'), 'getTermObjects');
        }
    });

    function updateTaxonomyOrTermField(slugName, targetField, actionName) {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: actionName, slug: slugName },
            success: function(response) {
                if (response.success) {
                    targetField.empty().append(new Option('Please Select', ''));
                    $.each(response.data, function(slug, label) {
                        targetField.append(new Option(label, slug));
                    });
                }
            },
            error: function(error) {
                console.log('Error:', error);
            }
        });
    }
    $('#add-context-btn').on('click', function() {
        $('#add-context-section').toggle();
    });
    $('#confirm-add-context').on('click', function() {
        var contextName = $('#new_context_name').val().trim();
        var contextType = $('input[name="new_context_type"]:checked').val();

        if (!contextName || !contextType) {
            alert('Please enter a valid context name and type.');
            return;
        }

        $.post(ajaxurl, {
            action: 'build_individual_context',
            new_context_name: contextName,
            new_context_type: contextType
        }, function(response) {
            if (response.success) {
                var toggleHtml = response.data.new_toggle_html;
                var sectionHtml = response.data.new_section_html;
                $('#toggles_section .section-fields').append(toggleHtml);
                $('#' + contextType + ' .section-fields').append(sectionHtml);
                $('#new_context_name').val('');
                $('input[name="new_context_type"]:checked').prop('checked', false);
                $('#add-context-section').hide();
               } else {
                alert(response.data.message || 'Failed to add context.');
            }
        });
    });
    $(document).on('click', '[id$="_remove"]', function() {
        var contextName = this.id.replace('_remove', '');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'remove_custom_context',
                context_name: contextName
            },
            success: function(response) {
                if (response.success) {
                    $(`#${contextName}`).remove();
                    $(`#${contextName}_enabled`).remove();
                } else {
                    console.error('Error:', response.message);
                }
            },
            error: function(error) {
                console.error('Error:', error);
            }
        });
    });
});
