<?php

function dibraco_relationships_migrate_acf() {
    // Ensure ACF Pro is active, as it's required for taxonomy fields.
    if (!function_exists('acf_get_field_groups')) {
        echo '<div class="wrap"><h2>Error</h2><p>Advanced Custom Fields is not active.</p></div>';
        return;
    }
    dibraco_migrator_handle_post_requests();

    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'post_types';
    $selected_item = isset($_GET['selected_item']) ? $_GET['selected_item'] : '';
    
    echo '<div class="wrap"><h1>ACF Field Migrator</h1>';
    
    dibraco_migrator_admin_styles();
    dibraco_migrator_render_tabs($active_tab);
    dibraco_migrator_render_selector_form($active_tab, $selected_item);

    // If no post type or taxonomy is selected, stop here.
    if (!$selected_item) {
        echo '<p><em>Select an item from the dropdown to configure field mapping.</em></p></div>';
        return;
    }

    // Fetch the relevant fields for the selected item.
    $acf_fields = dibraco_migrator_get_acf_fields($selected_item, $active_tab);
    $meta_keys = dibraco_migrator_get_meta_keys($selected_item, $active_tab, $acf_fields);
    
    // Render the main mapping form.
    dibraco_migrator_render_mapping_form($selected_item, $active_tab, $acf_fields, $meta_keys);

    echo '</div>'; // close .wrap
}

/**
 * Prints the CSS styles for the migrator admin page.
 */
function dibraco_migrator_admin_styles() {
    ?>
    <style>
        .dibraco-migrator-form { margin: 20px 0; }
        .dibraco-migrator-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 1.5em; }
        .dibraco-migrator-panel { border: 1px solid #c3c4c7; background-color: white; }
        .dibraco-migrator-panel h3 { margin: 0; padding: 12px; border-bottom: 1px solid #dcdcde; font-size: 1.2em; }
        .dibraco-migrator-actions { margin-top: 20px; }
        .dibraco-delete-button { color: #b32d2e; border-color: #b32d2e; background: #f8f9f9; }
        .dibraco-delete-button:hover { background: #b32d2e; color: #fff; border-color: #b32d2e; }
    </style>
    <?php
}


/**
 * Handles POST requests for saving mapping and running the migration.
 */
function dibraco_migrator_handle_post_requests() {
    if (empty($_POST) || !isset($_POST['dibraco_nonce'])) {
        return;
    }

    // Verify the nonce for security.
    if (!check_admin_referer('dibraco_acf_migrator_action', 'dibraco_nonce')) {
        return;
    }

    $selected_item = $_POST['selected_item'] ?? ''; 
    $active_tab = $_POST['tab'] ?? '';           
    
    // Handle the "Save Mapping" action.
    if (isset($_POST['save_mapping'])) {
        if ($selected_item) {
            $mapping = $_POST['map'] ?? [];
            $filtered_mapping = array_filter($mapping, fn($val) => $val !== '');
            update_option('acf_migration_map_' . $selected_item, $filtered_mapping);
            echo '<div class="notice notice-success is-dismissible"><p><strong>Mapping saved!</strong></p></div>';
        }
    }

    // Handle the "Run Migration" action.
    if (isset($_POST['run_migration'])) {
        $mapping = get_option('acf_migration_map_' . $selected_item, []);
        if (empty($mapping)) {
            echo '<div class="notice notice-error"><p>No mapping saved. Please configure and save the mapping first.</p></div>';
        } else {
            $migrated_count = dibraco_migrator_execute_migration($selected_item, $active_tab, $mapping);
            echo '<div class="notice notice-success is-dismissible"><p><strong>Migration complete!</strong> Copied ' . intval($migrated_count) . ' values.</p></div>';
        }
    }

    // Handle the "Delete Meta Key" action.
    if (isset($_POST['delete_meta_key'])) {
        $meta_key_to_delete = $_POST['delete_meta_key'];
        $deleted_count = dibraco_migrator_delete_meta_key($selected_item, $active_tab, $meta_key_to_delete);
        echo '<div class="notice notice-success is-dismissible"><p><strong>Meta key deleted.</strong> Permanently removed <code>' . esc_html($meta_key_to_delete) . '</code> from ' . intval($deleted_count) . ' items.</p></div>';
    }

    // Handle the "Delete ACF Values" action.
    if (isset($_POST['delete_acf_values'])) {
        $acf_field_to_delete = $_POST['delete_acf_values'];
        $deleted_count = dibraco_migrator_delete_acf_values($selected_item, $active_tab, $acf_field_to_delete);
        echo '<div class="notice notice-success is-dismissible"><p><strong>ACF values deleted.</strong> Permanently removed values for <code>' . esc_html($acf_field_to_delete) . '</code> from ' . intval($deleted_count) . ' items.</p></div>';
    }
}

function dibraco_migrator_execute_migration($selected_item, $active_tab, $mapping) {
    $migrated_count = 0;
    $is_taxonomy = ($active_tab === 'taxonomies');
    $optimized_mapping = []; 

    foreach ($mapping as $meta_key => $acf_field_name) {
        if (!is_string($acf_field_name) || empty($acf_field_name)) {
            continue;
        }

        $entry = ['acf_name' => $acf_field_name, 'parent_key' => $meta_key, 'child_key' => null, 'type' => 'simple'];

        $bracket_pos = strpos($meta_key, '[');
        if ($bracket_pos !== false) {
            $entry['parent_key'] = substr($meta_key, 0, $bracket_pos);
            $entry['child_key'] = rtrim(substr($meta_key, $bracket_pos + 1), ']');
            $entry['type'] = 'array_child';
        }
        $optimized_mapping[] = $entry;
    }

    // Get all posts or terms to be migrated.
    $items = $is_taxonomy
        ? get_terms(['taxonomy' => $selected_item, 'hide_empty' => false])
        : get_posts(['post_type' => $selected_item, 'posts_per_page' => -1, 'post_status' => 'any']);

    // PHASE 2: Loop through each item and migrate the data.
    foreach ($items as $item) {
        $item_id = $is_taxonomy ? $item->term_id : $item->ID;
        // ACF requires a specific context for terms vs. posts.
        $context = $is_taxonomy ? 'term_' . $item_id : $item_id;
        $grouped_updates = [];

        foreach ($optimized_mapping as $entry) {
            // 1. Read the value from the source ACF field.
            $acf_value = get_field($entry['acf_name'], $context);

            if ($acf_value === null) continue;

            if (is_array($acf_value) || (is_string($acf_value) && filter_var($acf_value, FILTER_VALIDATE_URL))) {
                if (is_array($acf_value)) {
                    $acf_value = $acf_value['ID'] ?? null;
                } elseif (is_string($acf_value) && filter_var($acf_value, FILTER_VALIDATE_URL)) {
                    $acf_value = attachment_url_to_postid($acf_value);
                }
                $acf_value = (int)$acf_value;
            } 
            
            // 3. Write the value to the destination meta key.
            if ($entry['type'] === 'array_child') {
                // For serialized arrays, group the updates to avoid overwriting data.
                $parent_key = $entry['parent_key'];
                $child_key = $entry['child_key'];
                
                if (!isset($grouped_updates[$parent_key])) {
                    $existing_data = $is_taxonomy 
                        ? get_term_meta($item_id, $parent_key, true) 
                        : get_post_meta($item_id, $parent_key, true);
                    $grouped_updates[$parent_key] = is_array($existing_data) ? $existing_data : [];
                }
                $grouped_updates[$parent_key][$child_key] = $acf_value;
            } else {
                // For simple meta keys, update directly.
                if ($is_taxonomy) {
                    update_term_meta($item_id, $entry['parent_key'], $acf_value);
                } else {
                    update_post_meta($item_id, $entry['parent_key'], $acf_value);
                }
                $migrated_count++;
            }
        }

        // After processing all mappings for an item, perform the grouped updates for serialized arrays.
        foreach ($grouped_updates as $parent_key => $data) {
            if ($is_taxonomy) {
                update_term_meta($item_id, $parent_key, $data);
            } else {
                update_post_meta($item_id, $parent_key, $data);
            }
            $migrated_count += count($data); 
        }
    }

    return $migrated_count;
}


function dibraco_migrator_delete_meta_key($selected_item, $active_tab, $meta_key) {
    $is_taxonomy = ($active_tab === 'taxonomies');
    $items = $is_taxonomy
        ? get_terms(['taxonomy' => $selected_item, 'hide_empty' => false, 'fields' => 'ids'])
        : get_posts(['post_type' => $selected_item, 'posts_per_page' => -1, 'post_status' => 'any', 'fields' => 'ids']);

    $deleted_count = 0;
    $parent_key = $meta_key;
    $child_key = null;

    // Check if we are deleting a child key from a serialized array.
    $bracket_pos = strpos($meta_key, '[');
    if ($bracket_pos !== false) {
        $parent_key = substr($meta_key, 0, $bracket_pos);
        $child_key = rtrim(substr($meta_key, $bracket_pos + 1), ']');
    }

    foreach ($items as $item_id) {
        if ($child_key) {
            // Handle deletion from a serialized array.
            $data = $is_taxonomy ? get_term_meta($item_id, $parent_key, true) : get_post_meta($item_id, $parent_key, true);
            if (is_array($data) && isset($data[$child_key])) {
                unset($data[$child_key]);
                if ($is_taxonomy) {
                    update_term_meta($item_id, $parent_key, $data);
                } else {
                    update_post_meta($item_id, $parent_key, $data);
                }
                $deleted_count++;
            }
        } else {
            // Handle simple meta key deletion.
            if ($is_taxonomy) {
                delete_term_meta($item_id, $parent_key);
            } else {
                delete_post_meta($item_id, $parent_key);
            }
            $deleted_count++;
        }
    }
    return count($items); 
}


function dibraco_migrator_delete_acf_values($selected_item, $active_tab, $acf_field_name) {
    $is_taxonomy = ($active_tab === 'taxonomies');
    $items = $is_taxonomy
        ? get_terms(['taxonomy' => $selected_item, 'hide_empty' => false, 'fields' => 'ids'])
        : get_posts(['post_type' => $selected_item, 'posts_per_page' => -1, 'post_status' => 'any', 'fields' => 'ids']);
    
    foreach ($items as $item_id) {
        $context = $is_taxonomy ? 'term_' . $item_id : $item_id;
        delete_field($acf_field_name, $context);
    }

    return count($items);
}
function dibraco_migrator_get_meta_keys($selected_item, $active_tab, $acf_fields) {
    $is_taxonomy = ($active_tab === 'taxonomies');
    $item_id = 0;

    // Get the first post/term to sample its meta keys.
    if ($is_taxonomy) {
        $terms = get_terms(['taxonomy' => $selected_item, 'number' => 1, 'hide_empty' => false, 'fields' => 'ids']);
        if (!empty($terms) && !is_wp_error($terms)) $item_id = $terms[0];
    } else {
        $posts = get_posts(['post_type' => $selected_item, 'posts_per_page' => 1, 'fields' => 'ids', 'post_status' => 'any']);
        if (!empty($posts)) $item_id = $posts[0];
    }

    if (!$item_id) return [];

    // Get ALL meta keys for the item, including hidden ones.
    $all_meta = $is_taxonomy ? get_term_meta($item_id) : get_post_meta($item_id);

    if (empty($all_meta)) return [];

    $acf_keys_to_exclude = [];

    // --- Identify ACF fields reliably ---
    foreach ($all_meta as $key => $value_array) {
        if (strpos($key, '_') === 0) {
            $partner_key = substr($key, 1);
            if (isset($all_meta[$partner_key])) {
                $reference_value = $value_array[0] ?? '';
                if (is_string($reference_value) && strpos($reference_value, 'field_') === 0) {
                    $acf_keys_to_exclude[$partner_key] = true;
                    $acf_keys_to_exclude[$key] = true;
                }
            }
        }
    }
    // --- END ACF Identification ---

    $flat_keys = [];
    
    // Build the final list of keys for the right-hand column.
    foreach($all_meta as $key => $value_array) {
        // Skip ACF keys
        if (isset($acf_keys_to_exclude[$key])) {
            continue;
        }

         if (strpos($key, 'math') !== false) {
            continue;
        }        if (strpos($key, 'fusion') !== false) {
            continue;
        }
        // *** END NEW ***

        $value = maybe_unserialize($value_array[0]);

        if (is_array($value)) {
            foreach ($value as $child_key => $child_value) {
                // *** NEW: Also skip child keys containing "fusion" ***
                if (strpos($child_key, 'fusion') !== false) {
                    continue;
                }
                // *** END NEW ***
                
                if (!is_array($child_value)) {
                    $flat_keys[] = "{$key}[{$child_key}]";
                }
            }
        } else {
            // It's a simple value, add the key directly.
            $flat_keys[] = $key;
        }
    }
    
    sort($flat_keys);
    return $flat_keys;
}


function dibraco_migrator_get_acf_fields($selected_item, $active_tab) {
    $groups = [];
    $is_taxonomy = ($active_tab === 'taxonomies');
    $location_param = $is_taxonomy ? 'taxonomy' : 'post_type';

    // Find all field groups assigned to the selected post type or taxonomy.
    foreach (acf_get_field_groups() as $group) {
        if (empty($group['location'])) continue;
        foreach ($group['location'] as $ruleset) {
            foreach ($ruleset as $rule) {
                if ($rule['param'] === $location_param && $rule['value'] === $selected_item) {
                    $groups[] = $group;
                    continue 3; // Found a match, move to the next field group.
                }
            }
        }
    }
    $flatten_fields = function($fields, $prefix = '') use (&$flatten_fields) {
        $flat_list = [];
        foreach ($fields as $field) {
            $field_name = $prefix ? $prefix . '_' . $field['name'] : $field['name'];
            if ($field['type'] === 'group' && !empty($field['sub_fields'])) {
                $flat_list = array_merge($flat_list, $flatten_fields($field['sub_fields'], $field_name));
            } else {
                $flat_list[] = ['name' => $field_name, 'label' => $field['label'], 'type' => $field['type']];
            }
        }
        return $flat_list;
    };

    $all_fields = [];
    foreach ($groups as $group) {
        $fields_in_group = acf_get_fields($group['key']);
        if ($fields_in_group) {
             $all_fields = array_merge($all_fields, $fields_in_group);
        }
    }

    return $flatten_fields($all_fields);
}

function dibraco_migrator_render_tabs($active_tab) {
    $post_types_class = ($active_tab === 'post_types' ? 'nav-tab-active' : '');
    $taxonomies_class = ($active_tab === 'taxonomies' ? 'nav-tab-active' : '');

    echo '<h2 class="nav-tab-wrapper">';
    echo '<a href="?page=dibraco-relationships-acf-migrator&tab=post_types" class="nav-tab ' . $post_types_class . '">Post Types</a>';
    echo '<a href="?page=dibraco-relationships-acf-migrator&tab=taxonomies" class="nav-tab ' . $taxonomies_class . '">Taxonomies</a>';
    echo '</h2>';
}

function dibraco_migrator_render_selector_form($active_tab, $selected_item) {
    $enabled_contexts = get_option('enabled_contexts', []);

    echo '<form method="get" class="dibraco-migrator-form">';
    echo '<input type="hidden" name="page" value="dibraco-relationships-acf-migrator">';
    echo '<input type="hidden" name="tab" value="' . $active_tab . '">'; // No esc_attr

    if ($active_tab === 'post_types') {
        echo '<label for="selected_item"><strong>Select Post Type:</strong></label> ';
        echo '<select name="selected_item" id="selected_item" onchange="this.form.submit()">';
        echo '<option value="">-- Choose Post Type --</option>';

        foreach ($enabled_contexts as $name => $data) {
            if (!isset($data['post_type'])) continue;
            $pt_object = get_post_type_object($data['post_type']);
            if (!$pt_object) continue;

            $value = $data['post_type']; // No esc_attr
            $selected_attr = selected($selected_item, $data['post_type'], false);
            $label = $pt_object->labels->singular_name; // No esc_html
            $context_name = $name; // No esc_html

            echo "<option value=\"{$value}\" {$selected_attr}>{$label} ({$context_name})</option>";
        }

    } else { // Taxonomies
        echo '<label for="selected_item"><strong>Select Taxonomy:</strong></label> ';
        echo '<select name="selected_item" id="selected_item" onchange="this.form.submit()">';
        echo '<option value="">-- Choose Taxonomy --</option>';

        foreach ($enabled_contexts as $name => $data) {
            if (!isset($data['taxonomy']) || (isset($data['context_type']) && $data['context_type'] === 'unique')) continue;
            $tax_object = get_taxonomy($data['taxonomy']);
            if (!$tax_object) continue;

            $value = $data['taxonomy']; // No esc_attr
            $selected_attr = selected($selected_item, $data['taxonomy'], false);
            $label = $tax_object->labels->singular_name; // No esc_html
            $context_name = $name; // No esc_html

            echo "<option value=\"{$value}\" {$selected_attr}>{$label} ({$context_name})</option>";
        }
    }
    echo '</select></form>';
}
function dibraco_migrator_render_mapping_form($selected_item, $active_tab, $acf_fields, $meta_keys) {
    if (empty($acf_fields) && empty($meta_keys)) {
        echo '<div class="notice notice-info"><p>There are no ACF fields or meta keys to display for this item.</p></div>';
        return;
    }

    $saved_mapping = get_option('acf_migration_map_' . $selected_item, []);
    $item_label = ucwords(str_replace('_', ' ', $selected_item));

    // Start Form
    echo '<h2>Map Fields for ' . $item_label . '</h2>';
    echo '<form method="post">';

    wp_nonce_field('dibraco_acf_migrator_action', 'dibraco_nonce');

    echo '<input type="hidden" name="selected_item" value="' . $selected_item . '">';
    echo '<input type="hidden" name="tab" value="' . $active_tab . '">';
    echo '<div class="dibraco-migrator-grid">';

    // LEFT COLUMN: ACF Fields (Source)
    echo '<div class="dibraco-migrator-panel"><h3>ACF Fields (Source)</h3>';
    if (empty($acf_fields)) {
        echo '<p style="padding: 12px;">No ACF fields found for this item.</p>';
    } else {
        echo '<table class="wp-list-table widefat striped"><tbody>';
        foreach ($acf_fields as $acf) {
            $delete_confirm_msg = sprintf(
                'Are you sure you want to PERMANENTLY DELETE all values for the ACF field \'%s\'? This will affect ALL items of this type and cannot be undone.',
                $acf['label']
            );
            // Combine row output
            echo '<tr>' .
                 '<td><code>' . $acf['name'] . '</code><br><em>' . $acf['label'] . ' (' . $acf['type'] . ')</em></td>' .
                 '<td style="width: 30%; text-align: right;">' .
                 '<button type="submit" name="delete_acf_values" value="' . $acf['name'] . '" class="button dibraco-delete-button" onclick="return confirm(\'' . $delete_confirm_msg . '\')">Delete Values</button>' .
                 '</td>' .
                 '</tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>'; // End ACF Panel

    echo '<div class="dibraco-migrator-panel"><h3>Your Meta Keys (Destination)</h3>';
    if (empty($meta_keys)) {
        echo '<p style="padding: 12px;">No destination meta fields found.</p>';
    } else {
        echo '<table class="wp-list-table widefat striped"><tbody>';

  
        $acf_options_base_html = '<option value="">-- Select ACF Field --</option>';
        foreach ($acf_fields as $acf) {
            $acf_options_base_html .= sprintf(
                '<option value="%s">%s (%s)</option>',
                $acf['name'],
                $acf['label'],
                $acf['type']
            );
        }

        foreach ($meta_keys as $meta_key) {
            $selected_acf_value = $saved_mapping[$meta_key] ?? '';
            $delete_confirm_msg = sprintf(
                'Are you sure you want to PERMANENTLY DELETE the meta key \'%s\' and all of its data? This will affect ALL items of this type and cannot be undone.',
                $meta_key
            );
            $current_acf_options_html = $acf_options_base_html;
            if ($selected_acf_value !== '') {
                $current_acf_options_html = str_replace(
                    'value="' . $selected_acf_value . '"',
                    'value="' . $selected_acf_value . '" selected="selected"',
                    $acf_options_base_html
                );
            }

            echo '<tr>' .
                 '<td><strong><code>' . $meta_key . '</code></strong></td>' .
                 '<td><select name="map[' . $meta_key . ']" style="width:100%;">' . $current_acf_options_html . '</select></td>' .
                 '<td style="width: 20%; text-align: right;">' .
                 '<button type="submit" name="delete_meta_key" value="' . $meta_key . '" class="button dibraco-delete-button" onclick="return confirm(\'' . $delete_confirm_msg . '\')">Delete</button>' .
                 '</td>' .
                 '</tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div></div>';

    if (!empty($meta_keys)) {
        echo '<p class="dibraco-migrator-actions">' .
             '<button class="button button-primary" type="submit" name="save_mapping">Save Mapping</button> ' .
             '<button class="button button-secondary" type="submit" name="run_migration" onclick="return confirm(\'Are you sure you want to run the migration? This will overwrite destination field data for ALL items of this type. This action cannot be undone.\')">Run Migration</button>' .
             '</p>';
    }
    echo '</form>'; // End Form
}