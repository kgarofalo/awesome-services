jQuery(function ($) {
  const $root = $('#relationships-settings');

  function updateTaxOrTerm(slugName, $target, action) {
    $.post(ajaxurl, { action: action, slug: slugName }, function (res) {
      if (!res || !res.success) {
        console.error('Error:', res && res.message ? res.message : 'Unknown error');
        return;
      }
      $target.empty().append(new Option('Please Select', ''));
      $.each(res.data, function (slug, label) {
        $target.append(new Option(label, slug));
      });
    });
  }

  $root.on('change', '#connector select[id$="_post_type"], #type select[id$="_post_type"]', function () {
    var context = this.id.replace('_post_type', '');
    var val = this.value;
    if (!val) return;
    var $taxonomy = $('#' + context + '_taxonomy');
     updateTaxOrTerm(val, $('#' + context + '_taxonomy'), 'awesome_get_taxonomies_for_post_type');
    
  });

$root.on('change', '#locations_taxonomy', function () {
  var tax = this.value;
  var $main = $('#locations_main_term');
  
  if (!tax) {
    $main.empty().append(new Option('Please Select', ''));
    // Clear all type context locations_main_term dropdowns
    $root.find('select[id$="locations_main_term"]').each(function() {
      $(this).empty().append(new Option('Please Select', ''));
    });
    return;
  }
  
  // Update locations main_term
  updateTaxOrTerm(tax, $main, 'getTermObjects');
  
  // Update all type context locations_main_term dropdowns
  $root.find('select[id$="_locations_main_term"]').each(function() {
    updateTaxOrTerm(tax, $(this), 'getTermObjects');
  });
});

  // ======================
  // TYPE + UNIQUE → remove buttons
  // ======================
  $root.on('click', '#type .remove-context-button, #unique .remove-context-button', function () {
    var context = this.id.replace('_remove', '');
    $.post(ajaxurl, { action: 'remove_custom_context', context_name: context }, function (res) {
      if (!res || !res.success) {
        console.error('Error:', res && res.message ? res.message : 'Unknown error');
        return;
      }
      $('#' + context).remove();
      $('#' + context + '_enabled').remove();
    });
  });

  // ======================
  // ADD CONTEXT (sidebar and append to section)
  // ======================
    const addContextButton = document.getElementById('add-context-btn'); 
    const addContextSection = document.getElementById('add-context-section');
    const togglesContainer = document.getElementById('toggles_section');

    addContextButton.addEventListener('click', () => {
            addContextSection.style.display = ''; 
        });
    const newContextNameInputField = document.getElementById('new_context_name');
    const newContextTypeRadioField = document.getElementById('new_context_type');
    const confirmAddContext = document.getElementById('confirm-add-context');
    confirmAddContext.addEventListener('click', () => {
        const newContextName = newContextNameInputField.value.trim(); 
        const checkedRadio = newContextTypeRadioField.querySelector('input[type="radio"]:checked');
        const contextType = checkedRadio.value; 
        if (!newContextName) {
            alert('Please enter a valid context name');
            return;
        }
    const dataPayload = {
        action: 'build_individual_context',
        new_context_name: newContextName,
        new_context_type: contextType
    }; 
    $.post(ajaxurl, dataPayload,function(res){
       if (!res || !res.success) {
        var msg = (res && res.data && res.data.message) ? res.data.message : 'Failed to add context.';
        alert(msg);
        return;
      }
      var toggleHtml = res.data.new_toggle_html;
      var sectionHtml = res.data.new_section_html;
      const typeSection = document.getElementById('type'); 
      const uniqueSection = document.getElementById('unique');
         togglesContainer.insertAdjacentHTML('beforeend', toggleHtml);

      if (contextType ==='type'){
         typeSection.insertAdjacentHTML('beforeend', sectionHtml);
        }
      if(contextType ==='unique'){
          uniqueSection.insertAdjacentHTML('beforeend', sectionHtml);
      }
     newContextNameInputField.value = '';  
   });
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

    $(document).on('change', '#locations_taxonomy', function() {
        var selectedTaxonomy = $(this).val();
        if (selectedTaxonomy) {
            updateTaxonomyOrTermField(selectedTaxonomy, $('#locations_main_term'), 'getTermObjects');
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



document.addEventListener('DOMContentLoaded', function() {
    function updateFieldsWithAjax(actionName, data, onSuccess) {
        var formData = new FormData();
        formData.append('action', actionName);
        for (var key in data) {
            formData.append(key, data[key]);
        }
        console.log("AJAX URL is:", ajaxurl);

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
                } 
            }
        })

    }
    document.addEventListener('change', function(event) {
        var target = event.target;
        if (target && target.id && target.id.endsWith('_post_type')) {
            var selectedPostType = target.value;
            var contextName = target.id.replace('_post_type', '');
            if (selectedPostType) {
                var taxonomySelect = document.getElementById(contextName + '_taxonomy');
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
        } else if (target && target.id === 'locations_taxonomy') {
            var selectedTaxonomy = target.value;
            var mainTermSelect = document.getElementById('locations_main_term');
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
    });

    var addContextBtn = document.getElementById('add-context-btn');
        addContextBtn.addEventListener('click', function() {
            var addContextSection = document.getElementById('add-context-section');
                addContextSection.style.display = (addContextSection.style.display === 'none') ? 'block' : 'none';
        });
    
    var confirmAddContextBtn = document.getElementById('confirm-add-context');
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
                        togglesSection.insertAdjacentHTML('beforeend', responseData.new_toggle_html);
                        contextsContainer.insertAdjacentHTML('beforeend', responseData.new_section_html);
                    if (contextNameInput) contextNameInput.value = '';
                    if (contextTypeInput) contextTypeInput.checked = false;
                    document.getElementById('add-context-section').style.display = 'none';
                }
            );
        });
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
*/