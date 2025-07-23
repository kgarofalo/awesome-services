<?php
$status = get_option('locations_areas_status');
if ($status === 'both') {   
    initialize_act_lct_ml_features();
};
function initialize_act_lct_ml_features() {
    $connector_contexts = get_option('enabled_connector_contexts');
    $main_area_post_type = $connector_contexts['service_areas']['post_type'];
    $area_connector_tax = $connector_contexts['service_areas']['taxonomy'];
    $locations_connector_taxonomy = $connector_contexts['locations']['taxonomy'];
    register_term_meta($area_connector_tax, 'area_parent_location_term', ['type' => 'integer', 'single' => true, 'show_in_rest' => true, ]);
    register_term_meta($locations_connector_taxonomy, 'associated_act_terms', ['type' => 'array', 'single' => false, 'show_in_rest' => ['schema' => ['type' => 'array', 'items' => ['type' => 'integer']]]]);
    register_term_meta($area_connector_tax, 'area_parent_location_name', ['type' => 'string', 'single' => true, 'show_in_rest' => true, ]);
 
    add_action("{$locations_connector_taxonomy}_add_form_fields", function($term) use ($area_connector_tax) {
        render_area_connection_checkboxes_for_location_terms(null, $area_connector_tax);
    }, 10, 1);
    add_action("{$locations_connector_taxonomy}_edit_form_fields", function($term) use ($area_connector_tax) {
    render_area_connection_checkboxes_for_location_terms((int)$term->term_id, $area_connector_tax);
    }, 10, 1);

    add_action("{$area_connector_tax}_add_form_fields", function($term) use ($locations_connector_taxonomy) {
        render_locations_connection_radio_for_area_terms(null, $locations_connector_taxonomy);
    }, 10, 1);
    add_action("{$area_connector_tax}_edit_form_fields", function($term) use ($locations_connector_taxonomy) {
        render_locations_connection_radio_for_area_terms($term, $locations_connector_taxonomy);
    }, 10, 1);

    add_action("created_{$area_connector_tax}", 'save_location_connection_for_area_terms', 10, 2);
    add_action("edited_{$area_connector_tax}", 'save_location_connection_for_area_terms', 10, 2);
    add_action("created_{$locations_connector_taxonomy}", 'save_area_connections_for_location_terms', 10, 2);
    add_action("edited_{$locations_connector_taxonomy}", 'save_area_connections_for_location_terms', 10, 2);
    add_action("delete_{$locations_connector_taxonomy}", 'delete_location_area_term_handler', 10, 2);
    add_action("delete_{$area_connector_tax}", 'delete_service_area_location_term_handler', 10, 2);
    
$meta_key = 'area_parent_location_name';
$column_key = "associated_{$locations_connector_taxonomy}";
add_filter("manage_edit-{$area_connector_tax}_columns", function($columns) use ($column_key) {
    $columns[$column_key] = ucwords(str_replace('_', ' ', $column_key));
    return $columns;
});
add_filter("manage_edit-{$area_connector_tax}_sortable_columns", function($columns) use ($column_key, $meta_key) {
    $columns[$column_key] = $meta_key;
    return $columns;
});

add_filter("manage_{$area_connector_tax}_custom_column", function($content, $column_name, $term_id) use ($column_key, $meta_key) {
    if ($column_name === $column_key) {
        $value = get_term_meta($term_id, $meta_key, true) ?? '';
        $meta_value = get_term_meta($term_id, 'area_parent_location_term', true);
        $hidden = '<input type="hidden" class="dibraco-term-quickedit-data" value="' . $meta_value . '">';
        return $value . $hidden;
    }
    return $content;
}, 10, 3);
   dibraco_setup_service_area_term_quickedit_hook($area_connector_tax, $locations_connector_taxonomy);
}
function dibraco_setup_service_area_term_quickedit_hook($area_connector_tax, $locations_connector_taxonomy) {
 add_action("quick_edit_custom_box", function($column_name, $screen_name, $taxonomy_name) use ($area_connector_tax, $locations_connector_taxonomy) {
    $target_column = "associated_{$locations_connector_taxonomy}";
        if ($taxonomy_name !== $area_connector_tax || $column_name !== $target_column) {return;}
render_locations_connection_radio_for_area_terms('', $locations_connector_taxonomy, 'quick_edit'); }, 10, 3);}

function render_locations_connection_radio_for_area_terms($term, $locations_connector_taxonomy, $context = '') {
    $current_act_term_id = $term ? $term->term_id : '';
    $current_selected_lct_term = '';
    if ($current_act_term_id !== '') {
        $current_selected_lct_term = get_term_meta($current_act_term_id, 'area_parent_location_term', true) ?? '';
        if ($current_selected_lct_term !== '') {
            $term_exists_in_taxonomy = get_term_by('id', $current_selected_lct_term, $locations_connector_taxonomy);
            if (!$term_exists_in_taxonomy) {
                $current_selected_lct_term = '';
            }
        }
    }
    $location_terms = get_terms(['taxonomy' => $locations_connector_taxonomy, 'hide_empty' => false]);
    $options_array = [];
    foreach ($location_terms as $location_term) {
        $options_array[$location_term->term_id] = $location_term->name;
    }
    $fieldset_html = formHelper::generateRadioFieldsetWithIntegerValues( 'area_parent_location_term', 'Location Connector Parent', $current_selected_lct_term, $options_array );

    if ($context === 'quick_edit') {
      echo '<div class="inline-edit-col">';
        echo $fieldset_html;
      echo '</div>';
    } else {
        ?>
        <table class="striped widefat fixed">
            <td class="area-parent-term-connector">
                <?= $fieldset_html; ?>
            </td>
        </table>
        <?php
    }
}

function save_location_connection_for_area_terms($area_term_id, $tt_id) {
    $new_lct_id = $_POST['area_parent_location_term'] ?? '';
    $assignments = get_option('act_to_lct_assignments', []);
        if (isset($assignments[$area_term_id])) {
        $current_associated_lct_database_term = $assignments[$area_term_id];
    } else {
        $current_associated_lct_database_term = '';
        update_act_assignments($area_term_id, '');
    }
    if ($new_lct_id === '0' || $new_lct_id === 0 || empty($new_lct_id)) {
        update_term_meta($area_term_id, 'area_parent_location_term', '');
        update_term_meta($area_term_id, 'area_parent_location_name', '');
        if ($current_associated_lct_database_term) {
            $old_associated_act_terms = get_term_meta($current_associated_lct_database_term, 'associated_act_terms', true) ?: [];
            if (($key = array_search($area_term_id, $old_associated_act_terms)) !== false) {
                unset($old_associated_act_terms[$key]);
                update_term_meta($current_associated_lct_database_term, 'associated_act_terms', $old_associated_act_terms);
            }
        }
    } else {
        $new_lct_id = absint($new_lct_id); 
        $new_lct_name = get_term($new_lct_id)->name;
        update_term_meta($area_term_id, 'area_parent_location_term', $new_lct_id);
        update_term_meta($area_term_id, 'area_parent_location_name', $new_lct_name);
        if ($current_associated_lct_database_term !== $new_lct_id) {
            $new_associated_act_terms = get_term_meta($new_lct_id, 'associated_act_terms', true) ?: [];
            if (!in_array($area_term_id, $new_associated_act_terms)) {
                $new_associated_act_terms[] = $area_term_id;
                update_term_meta($new_lct_id, 'associated_act_terms', $new_associated_act_terms);
            }
        }
    }

    update_act_assignments($area_term_id, $new_lct_id);
}

function render_area_connection_checkboxes_for_location_terms($current_term_id, $area_connector_tax) {
  $act_terms = get_terms(['taxonomy' => $area_connector_tax, 'hide_empty' => false]);
  $assignments =  get_option('act_to_lct_assignments', []);
  $assigned_act_ids = [];
  $selected_terms = [];
  foreach ($assignments as $act_id => $lct_id) {
   if ($lct_id !== '') { 
    $assigned_act_ids[] = $act_id;
    }
    if ($current_term_id !== null && $current_term_id === $lct_id) {
              $selected_terms[] = $act_id;
    } 
  }
    ?>
 <table class="form-table dibraco-term-form-table widefat">
  <tr>
    <?= '<p><strong>Associated Area Connector Terms:</strong></p>'; ?>
    <td>
      <?php $renderedOne = false; // track if we actually output any checkboxes ?>
      <?php foreach ($act_terms as $act_term) : ?>
        <?php 
          if (!in_array($act_term->term_id, $assigned_act_ids, true) || in_array($act_term->term_id, $selected_terms, true)) :
            $renderedOne = true; 
            $checked   = in_array($act_term->term_id, $selected_terms, true);
            $input_id  = "associated_act_term_{$act_term->term_id}";
        ?>
          <label for="<?= $input_id; ?>">
            <input type="checkbox" id="<?= $input_id; ?>" name="associated_act_terms[]" value="<?= $act_term->term_id; ?>"
              <?= $checked ? 'checked="checked"' : ''; ?>>
            <?= esc_html($act_term->name); ?>
          </label><br>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if (!$renderedOne) : ?>
        <?= '<p>No area terms available.</p>'; ?>
      <?php endif; ?>
    </td>
  </tr>
</table>
    <?php
}
function save_area_connections_for_location_terms($term_id, $tt_id) {
    $term = get_term($term_id);
    $taxonomy = $term->taxonomy;
 $posted_act_terms = array_map('absint', $_POST['associated_act_terms'] ?? []);
    $posted_act_terms = array_filter($posted_act_terms, function($val) {
        return $val > 0;
    });    
    $new_lct_name = $term->name;
    update_term_meta($term_id, 'associated_act_terms', $posted_act_terms);
    $assignments = get_option('act_to_lct_assignments', []);
        foreach ($assignments as $act_id => $lct_id) {
        if ($lct_id === $term_id && !in_array($act_id, $posted_act_terms)) {
        update_act_assignments($act_id, ''); 
        update_term_meta($act_id, 'area_parent_location_term', ''); 
        update_term_meta($act_id, 'area_parent_location_name', ''); 
        }
    }
       foreach ($posted_act_terms as $act_id) {
        update_act_assignments($act_id, $term_id); 
        update_term_meta($act_id, 'area_parent_location_term', $term_id);
        update_term_meta($act_id, 'area_parent_location_name', $new_lct_name); 
    }
}
function update_act_assignments($act_id, $lct_id = null) {
    $assignments = get_option('act_to_lct_assignments', []);
    if ($lct_id === null || $lct_id === '' || $lct_id === 0) {
        $assignments[$act_id] = '';
    } else {
        $assignments[$act_id] = (int)$lct_id;
    }
    update_option('act_to_lct_assignments', $assignments);
}
function delete_service_area_location_term_handler($term_id) {
    $assignments = get_option('act_to_lct_assignments', []);
        if (!isset($assignments[$term_id])) { return; }
    $associated_lct_id = $assignments[$term_id];
    
    if (!empty($associated_lct_id)) {
        $associated_area_terms = get_term_meta($associated_lct_id, 'associated_act_terms', true) ?: [];
        if (($key = array_search($term_id, $associated_area_terms)) !== false) {
            unset($associated_area_terms[$key]);
            update_term_meta($associated_lct_id, 'associated_act_terms', $associated_area_terms);
        }
    }
    unset($assignments[$term_id]);
    update_option('act_to_lct_assignments', $assignments); 
}
function delete_location_area_term_handler($term_id) {
    $assignments = get_option('act_to_lct_assignments', []);
    foreach ($assignments as $act_id => $lct_id) {
        if ($lct_id == $term_id) {
            update_act_assignments($act_id, '');
            update_term_meta($act_id, 'area_parent_location_term', ''); 
        }
    }
}
