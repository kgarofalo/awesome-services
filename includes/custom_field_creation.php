<?php
function da_render_custom_fields_page() {
    $enabled_contexts = get_option('enabled_contexts', []);
    if (get_option('enable_custom_fields_for_pages', '') === "1") {
        $enabled_contexts['page'] = ['post_type' => 'page'];
    }
    $all_custom_fields = get_option('dibraco_custom_fields', []);
    $field_options = ['text' => 'Text' , 'textarea' => 'Text Area', 'wysiwyg' => 'Wysiwig' ];
    if (isset($_POST['da_save_custom_fields_nonce']) && wp_verify_nonce($_POST['da_save_custom_fields_nonce'], 'da_save_custom_fields_action')) {
        $context_to_save = $_POST['context_selection'];
        $new_fields_for_context = [];
        $validation_error_message = '';
        if (!empty($_POST['fields'])) {
               $pair_start_count = count(array_column($_POST['fields'], 'pair'));
               $pair_end_count = count(array_column($_POST['fields'], 'pair_end'));
            if ($pair_start_count !== $pair_end_count) {
              $validation_error_message = 'The number of starting and ending pairs must match. The changes were not saved.';
             }
            }
        if (!empty($validation_error_message)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($validation_error_message) . '</p></div>';
        } else {
            if (!empty($_POST['fields'])) {
                    foreach ($_POST['fields'] as $original_field_name => $field_data) {
                    if ($original_field_name === '') { continue; }
                    $label = isset($_POST[$original_field_name]) ? trim(stripslashes($_POST[$original_field_name])) : '';
                    if ($label === '') {continue; }
                    $new_field_name = 'da_' . str_replace('-', '_', sanitize_title($label));
                    $type = $field_data['type'];
                    $config = ['type' => $type];
                    if (isset($field_data['pair'])) {
                        $config['pair'] = true;
                    }
                    if (isset($field_data['pair_end'])) {
                        $config['pair_end'] = true;
                    }
                    $new_fields_for_context[$new_field_name] = $config;
                }
            }
            $previous_fields = $all_custom_fields[$context_to_save] ?? [];
            $removed_keys = array_diff(array_keys($previous_fields), array_keys($new_fields_for_context));
            if (!empty($removed_keys)) {
                $post_type = $enabled_contexts[$context_to_save]['post_type'];
                $posts_to_update = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids']);
                if (!empty($posts_to_update)) {
                    foreach ($posts_to_update as $post_id) {
                        foreach ($removed_keys as $meta_key) {
                            delete_post_meta($post_id, $meta_key);
                        }
                    }
                }
            }
            $all_custom_fields[$context_to_save] = $new_fields_for_context;
            update_option('dibraco_custom_fields', $all_custom_fields);
            echo '<div class="notice notice-success is-dismissible"><p>Custom fields saved successfully!</p></div>';
        }
    }
    $current_context = $_GET['context'] ?? key($enabled_contexts);
    $current_fields = $all_custom_fields[$current_context] ?? [];
    ?>
    <div class="wrap">
        <h1>Custom Field Editor</h1>
        <p>Define additional fields for your contexts. These will be added to the main metabox for that context's post type.</p>
        <div style="margin-top: 20px;">
             <label for="context_selector" style="font-weight:bold; font-size:1.2em;">Editing fields for context:</label>
             <select id="context_selector" onchange="window.location.href='?page=dibraco-relationships-custom-fields&context=' + this.value;">
                 <?php foreach ($enabled_contexts as $context_name => $context_data) : ?>
                     <option value="<? $context_name; ?>" <?php selected($current_context, $context_name); ?>>
                         <?php echo esc_html(ucwords(str_replace(['-', '_'], ' ', $context_data['post_type']))); ?>
                     </option>
                 <?php endforeach; ?>
             </select>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="context_selection" value="<?php echo esc_attr($current_context); ?>">
            <?php wp_nonce_field('da_save_custom_fields_action', 'da_save_custom_fields_nonce'); ?>
                <table class="wp-list-table widefat striped" id="custom-fields-table" style="margin-top:20px;">
              <thead>
            <tr>
                <th style="width: 40%;">Field Label</th>
                <th style="width: 40%;">Field Type</th>
                <th style="width: 10%;">In Pair?</th>
                <th style="width: 10%;">Actions</th>
            </tr>
         </thead>
           <tbody id="fields-repeater-container">
            <?php
            if(empty($current_fields)){
                $current_fields = ['new_field' => ['type' => 'text']];
            }
            $first_field = true;
            foreach ($current_fields as $field_name => $config): ?>
            <?php
            $type = $config['type'];
            $is_pair_start = isset($config['pair']);
            $is_pair_end = isset($config['pair_end']);
            $display_label ='';
            If ($field_name !=="new_field"){
            $display_label = ucwords(trim(str_replace('_', ' ', substr($field_name, 3))));
            }
            ?>
        <tr class="field-row">
            <td>
            <input type="text" name="<?= $field_name; ?>" placeholder="New Field Name eg (Section 4 Title)" class="widefat field-name-input" value="<?= $display_label; ?>">
            </td>
            <td>
                <select name="fields[<?= $field_name ?>][type]" class="widefat">
                    <?php foreach ($field_options as $value => $label): ?>
                        <option value="<?= $value; ?>" <?php selected($type, $value); ?>><?= $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
          <td class="pair-toggle-cell">
              <?php if ($is_pair_end): ?>
                Paired<input type="hidden" name="fields[<?= $field_name ?>][pair_end]" value="1">
               </td><td></td>
           <?php else: ?>
                <input type="checkbox" name="fields[<?= $field_name ?>][pair]" value="1" <?= $is_pair_start ? 'checked' : '' ?>>
                   </td><td>
                       <?php if(!$first_field) : ?>
                   <a href="#" class="button button-danger remove-field-button">Remove</a>
                    <?php endif; ?></td>
               <?php endif; ?>
        </tr>
        <?php $first_field = false; endforeach; ?>
          </tbody>
        </table>
            <button type="button" class="button" id="add-field-button" style="margin-top:10px;">+ Add Field</button>
              <?php submit_button('Save Custom Fields'); ?>
        </form>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('fields-repeater-container');
    const addButton = document.getElementById('add-field-button');
    if (!container || !addButton) return;

    // --- Helper Constants for Reused HTML ---
    const removeButtonHTML = '<a href="#" class="button button-danger remove-field-button">Remove</a>';
    const createPairCheckboxHTML = (fieldName) => `<input type="checkbox" name="fields[${fieldName}][pair]" value="1">`;

    const fieldOptions = {
        text: 'Text',
        textarea: 'Text Area',
        wysiwyg: 'Wysiwig'
    };
    let optionsHTML = '';
    for (const key in fieldOptions) {
        if (fieldOptions.hasOwnProperty(key)) {
            optionsHTML += `<option value="${key}">${fieldOptions[key]}</option>`;
        }
    }

    function fixInitialState() {
        const allRows = Array.from(container.querySelectorAll('.field-row'));
        allRows.forEach((currentRow) => {
            const isCurrentRowPairEnd = currentRow.querySelector('input[name*="[pair_end]"]');
            if (isCurrentRowPairEnd) return;
            const nextRow = currentRow.nextElementSibling;
            if (!nextRow) return;

            const nextRowStartsPair = nextRow.querySelector('.pair-toggle-cell input[name*="[pair]"]:checked');
            if (nextRowStartsPair) {
                const currentPairCell = currentRow.querySelector('.pair-toggle-cell');
                if (currentPairCell && currentPairCell.querySelector('input[name*="[pair]"]')) {
                    currentPairCell.innerHTML = '';
                }
            }
        });
    }

    function handlePairChecked(checkbox) {
        const currentRow = checkbox.closest('.field-row');
        const prevRow = currentRow.previousElementSibling;
        const nextRow = currentRow.nextElementSibling;
        if (nextRow) {
            const nextFieldName = nextRow.querySelector('.field-name-input').getAttribute('name');
            const nextPairCell = nextRow.querySelector('.pair-toggle-cell');
            const nextActionsCell = nextPairCell.nextElementSibling;
            nextPairCell.innerHTML = `Paired<input type="hidden" name="fields[${nextFieldName}][pair_end]" value="1">`;
            nextActionsCell.innerHTML = '';
        }
        if (prevRow) {
            const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
            const isPrevRowPairEnd = prevPairCell.querySelector('input[name*="[pair_end]"]');
            if (!isPrevRowPairEnd) {
                if (prevPairCell.querySelector('input[name*="[pair]"]')) {
                    prevPairCell.innerHTML = '';
                }
            }
        }
    }

    function handlePairUnchecked(checkbox) {
        const currentRow = checkbox.closest('.field-row');
        const prevRow = currentRow.previousElementSibling;
        const nextRow = currentRow.nextElementSibling;
        if (nextRow) {
            const nextFieldName = nextRow.querySelector('.field-name-input').getAttribute('name');
            const nextPairCell = nextRow.querySelector('.pair-toggle-cell');
            const nextActionsCell = nextPairCell.nextElementSibling;
            nextPairCell.innerHTML = createPairCheckboxHTML(nextFieldName);
            nextActionsCell.innerHTML = removeButtonHTML;
        }
        if (prevRow) {
            const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
            const isPrevRowPairEnd = prevPairCell.querySelector('input[name*="[pair_end]"]');
            if (!isPrevRowPairEnd) {
                const hasPairCheckbox = prevPairCell.querySelector('input[name*="[pair]"]');
                if (!hasPairCheckbox) {
                    const prevFieldName = prevRow.querySelector('.field-name-input').getAttribute('name');
                    prevPairCell.innerHTML = createPairCheckboxHTML(prevFieldName);
                }
            }
        }
    }

    container.addEventListener('change', function (e) {
        if (e.target.matches('.pair-toggle-cell input[name*="[pair]"]')) {
            const checkbox = e.target;
            if (checkbox.checked) {
                handlePairChecked(checkbox);
            } else {
                handlePairUnchecked(checkbox);
            }
        }
    });


    container.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-field-button')) {
            e.preventDefault();
            const rowToRemove = e.target.closest('.field-row');
            const prevRow = rowToRemove.previousElementSibling;
            const nextRow = rowToRemove.nextElementSibling;

            const pairCheckbox = rowToRemove.querySelector('input[name*="[pair]"]');
            const wasChecked = pairCheckbox && pairCheckbox.checked;
            const hadNoCheckbox = !pairCheckbox;

            rowToRemove.remove();
            if (wasChecked) {
                if (prevRow) {
                    const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
                    if (!prevPairCell.querySelector('input[name*="[pair_end]"]')) {
                        const prevFieldName = prevRow.querySelector('.field-name-input').getAttribute('name');
                        prevPairCell.innerHTML = createPairCheckboxHTML(prevFieldName);
                    }
                }
                if (nextRow) {
                    const nextFieldName = nextRow.querySelector('.field-name-input').getAttribute('name');
                    const nextPairCell = nextRow.querySelector('.pair-toggle-cell');
                    const nextActionsCell = nextPairCell.nextElementSibling;
                    nextPairCell.innerHTML = createPairCheckboxHTML(nextFieldName);
                    nextActionsCell.innerHTML = removeButtonHTML;
                }
            } else if (hadNoCheckbox) {
                if (prevRow) { // Added safety check for prevRow
                    const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
                    if (!prevPairCell.querySelector('input[name*="[pair_end]"]')) {
                        const newNextRow = prevRow.nextElementSibling;
                        let newNextRowStartsPair = false;
                        if (newNextRow) {
                            const check = newNextRow.querySelector('input[name*="[pair]"]:checked');
                            if (check) newNextRowStartsPair = true;
                        }

                        if (!newNextRowStartsPair) {
                            const prevFieldName = prevRow.querySelector('.field-name-input').getAttribute('name');
                            prevPairCell.innerHTML = createPairCheckboxHTML(prevFieldName);
                        }
                    }
                }
            }
        }
    });

    addButton.addEventListener('click', function (e) {
        e.preventDefault();
        const fieldName = 'new_' + Math.random().toString(36).substring(2, 9);
        const lastRow = container.querySelector('.field-row:last-of-type');
        let lastRowStartsPair = false;
        if (lastRow) {
            const lastPairCheckbox = lastRow.querySelector('.pair-toggle-cell input[name*="[pair]"]:checked');
            if (lastPairCheckbox) {
                lastRowStartsPair = true;
            }
        }

        const newRow = document.createElement('tr');
        newRow.className = 'field-row';
        let pairCellHtml, actionsCellHtml;

        if (lastRowStartsPair) {
            pairCellHtml = `<td class="pair-toggle-cell">Paired<input type="hidden" name="fields[${fieldName}][pair_end]" value="1"></td>`;
            actionsCellHtml = '<td></td>';
        } else {
            pairCellHtml = `<td class="pair-toggle-cell">${createPairCheckboxHTML(fieldName)}</td>`;
            actionsCellHtml = `<td>${removeButtonHTML}</td>`;
        }
        newRow.innerHTML = `
            <td><input type="text" name="${fieldName}" placeholder="New Field Name eg (Section 4 Title)" class="widefat field-name-input" value=""></td>
            <td><select name="fields[${fieldName}][type]" class="widefat">${optionsHTML}</select></td>
            ${pairCellHtml}
            ${actionsCellHtml}
        `;
        container.appendChild(newRow);
        if (lastRowStartsPair && lastRow) {
            const lastRowActionsCell = lastRow.querySelector('.pair-toggle-cell').nextElementSibling;
            if (lastRowActionsCell) {
                lastRowActionsCell.innerHTML = '';
            }
        }
    });

    fixInitialState();
});
</script>
<?php
}
function get_dibraco_custom_fields_for_context($context_name) {
    $all_custom_fields = get_option('dibraco_custom_fields', []);
    if (isset($all_custom_fields[$context_name]) && is_array($all_custom_fields[$context_name])) {
    return $all_custom_fields[$context_name];
    }
    return [];
}