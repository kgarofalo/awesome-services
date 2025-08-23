document.addEventListener('DOMContentLoaded', function() {
    
    // Helper function to handle AJAX requests using Fetch API
    function updateFieldsWithAjax(actionName, data, onSuccess) {
        var formData = new FormData();
        formData.append('action', actionName);
        
        for (var key in data) {
            formData.append(key, data[key]);
        }
        
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(response) {
            if (response.success) {
                onSuccess(response.data);
            } else {
                if (response.data && response.data.message) {
                    alert(response.data.message);
                } else {
                    console.error('Error:', response);
                }
            }
        })
        .catch(function(error) {
            console.error('Network or server error:', error);
        });
    }

    // Dynamic dropdown functionality
    document.addEventListener('change', function(event) {
        var target = event.target;
        if (target && target.id && target.id.endsWith('_post_type')) {
            var selectedPostType = target.value;
            var contextName = target.id.replace('_post_type', '');
            
            if (selectedPostType) {
                var taxonomySelect = document.getElementById(contextName + '_taxonomy');
                if (taxonomySelect) {
                    updateFieldsWithAjax(
                        'awesome_get_taxonomies_for_post_type', 
                        { slug: selectedPostType }, 
                        function(responseData) {
                            taxonomySelect.innerHTML = '';
                            taxonomySelect.appendChild(new Option('Please Select', ''));
                            for (var slug in responseData) {
                                taxonomySelect.appendChild(new Option(responseData[slug], slug));
                            }
                        }
                    );
                }
            }
        } else if (target && target.id === 'locations_taxonomy') {
            var selectedTaxonomy = target.value;
            var mainTermSelect = document.getElementById('locations_main_term');
            if (mainTermSelect) {
                mainTermSelect.innerHTML = '';
                mainTermSelect.appendChild(new Option('Please Select', ''));
                
                updateFieldsWithAjax(
                    'getTermObjects', 
                    { slug: selectedTaxonomy }, 
                    function(responseData) {
                        for (var id in responseData) {
                            mainTermSelect.appendChild(new Option(responseData[id], id));
                        }
                    }
                );
            }
        }
    });

    // Toggle 'Add Context' section
    var addContextBtn = document.getElementById('add-context-btn');
    if (addContextBtn) {
        addContextBtn.addEventListener('click', function() {
            var addContextSection = document.getElementById('add-context-section');
            if (addContextSection) {
                addContextSection.style.display = (addContextSection.style.display === 'none') ? 'block' : 'none';
            }
        });
    }

    // Add new context via AJAX
    var confirmAddContextBtn = document.getElementById('confirm-add-context');
    if (confirmAddContextBtn) {
        confirmAddContextBtn.addEventListener('click', function() {
            var contextNameInput = document.getElementById('new_context_name');
            var contextTypeInput = document.querySelector('input[name="new_context_type"]:checked');
            
            var contextName = contextNameInput ? contextNameInput.value.trim() : '';
            var contextType = contextTypeInput ? contextTypeInput.value : '';

            if (!contextName || !contextType) {
                alert('Please enter a valid context name and type.');
                return;
            }

            updateFieldsWithAjax(
                'build_individual_context', 
                { new_context_name: contextName, new_context_type: contextType },
                function(responseData) {
                    var togglesSection = document.querySelector('#toggles_section .section-fields');
                    var contextsContainer = document.getElementById(contextType);
                    
                    if (togglesSection) {
                        togglesSection.insertAdjacentHTML('beforeend', responseData.new_toggle_html);
                    }
                    if (contextsContainer) {
                        contextsContainer.insertAdjacentHTML('beforeend', responseData.new_section_html);
                    }

                    if (contextNameInput) contextNameInput.value = '';
                    if (contextTypeInput) contextTypeInput.checked = false;
                    document.getElementById('add-context-section').style.display = 'none';
                }
            );
        });
    }

    // Remove a context via AJAX
    document.addEventListener('click', function(event) {
        var target = event.target;
        if (target && target.id && target.id.endsWith('_remove')) {
            var contextName = target.id.replace('_remove', '');
            
            updateFieldsWithAjax(
                'remove_custom_context',
                { context_name: contextName },
                function(responseData) {
                    var contextSection = document.getElementById(contextName);
                    var toggleElement = document.getElementById(contextName + '_enabled');
                    if (contextSection) contextSection.remove();
                    if (toggleElement) toggleElement.remove();
                }
            );
        }
    });
});
/*
jQuery(document).ready(function($) {

    $(document).on('change', '[id$="_post_type"]', function() {
        var selectedPostType = $(this).val();
        var contextName = this.id.replace('_post_type', '');

        if (selectedPostType) {
            var taxonomySelect = $(`#${contextName}_taxonomy`);
            if (taxonomySelect.length) {
                updateTaxonomyOrTermField(selectedPostType, taxonomySelect, 'awesome_get_taxonomies_for_post_type');
            }
        }
    });
    
        $('#locations_taxonomy').on('change', function() {
        var contextName = this.id.replace('_taxonomy', '');
            var selectedTaxonomy = $(this).val();
            $('#locations_main_term').empty().append(new Option('Please Select', ''));
            updateTaxonomyOrTermField(selectedTaxonomy, $('#locations_main_term'), 'getTermObjects');
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
                console.log('Toggle HTML:', response.data.new_toggle_html);

                var toggleHtml = response.data.new_toggle_html;
                var sectionHtml = response.data.new_section_html;
                $('#toggles_section .section-fields').append(toggleHtml);
                $('#' + contextType).append(sectionHtml); 
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
*/