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
    register_term_meta($locations_connector_taxonomy, 'associated_act_slugs', ['type' => 'array', 'single' => false, 'show_in_rest' => ['schema' => ['type' => 'array', 'items' => ['type' => 'string']]]]);
    
    add_action("{$locations_connector_taxonomy}_add_form_fields", function($locations_connector_taxonomy) {
        render_area_connection_checkboxes_for_location_terms(null, $locations_connector_taxonomy);
    }, 10, 1);
    add_action("{$locations_connector_taxonomy}_edit_form_fields",'render_area_connection_checkboxes_for_location_terms', 10, 2);
    
    add_action("{$area_connector_tax}_add_form_fields", function($area_connector_tax) {
     render_locations_connection_radio_for_area_terms(null, $area_connector_tax);
    }, 10, 1);
    add_action("{$area_connector_tax}_edit_form_fields", 'render_locations_connection_radio_for_area_terms', 10, 2);

    add_action("created_{$area_connector_tax}", 'save_location_connection_for_area_terms', 10, 2);
    add_action("edited_{$area_connector_tax}", 'save_location_connection_for_area_terms', 10, 2);
    add_action("created_{$locations_connector_taxonomy}", 'save_area_connections_for_location_terms', 10, 2);
    add_action("edited_{$locations_connector_taxonomy}", 'save_area_connections_for_location_terms', 10, 2);
    add_action("delete_{$locations_connector_taxonomy}", 'delete_location_area_term_handler', 10, 2);
    add_action("delete_{$area_connector_tax}", 'delete_service_area_location_term_handler', 10, 2);
  
$location_term_id_key = 'area_parent_location_term';
$location_term_name_key = 'area_parent_location_name'; 
$column_key = 'area_parent_location_name';
add_filter("manage_edit-{$area_connector_tax}_columns", function ($columns) use ($column_key) {
    $columns[$column_key] = 'Parent Location';
    return $columns;
});
add_action("manage_{$area_connector_tax}_custom_column", function ($content, $column_name, $term_id) use ($column_key, $location_term_id_key) { 
    if ($column_name === $column_key) {
        $parent_name = get_term_meta($term_id, $column_key, true);
        $display = '<span class="area_parent_location_name">' . esc_html($parent_name) . '</span>';
        $parent_id = get_term_meta($term_id, $location_term_id_key, true);
        $hidden = sprintf( '<input type="hidden" class="quick-edit-%s" data-term-id="%d" value="%s" />', esc_attr($location_term_id_key), $term_id, esc_attr($parent_id) );
        return $display . $hidden;
    }
    return $content;
}, 10, 3);
add_filter("manage_edit-{$area_connector_tax}_sortable_columns", function ($sortable_columns) {
    $sortable_columns['area_parent_location_name'] = 'area_parent_location_name';
    return $sortable_columns;
});

add_action('quick_edit_custom_box', function($column_name, $screen_name, $taxonomy_name)
    use ($area_connector_tax, $column_key) {

    if ($taxonomy_name === $area_connector_tax && $column_name === $column_key) {
        render_locations_connection_radio_for_area_terms(null, $area_connector_tax, 'quick_edit');
    }
}, 10, 3);

}

function render_locations_connection_radio_for_area_terms($act_term, $area_connector_tax, $context = '') {
    $current_act_term_id = '';
    if ($act_term) {
        $current_act_term_id =  $act_term->term_id;
    }
    $locations_connector_taxonomy = get_option('enabled_connector_contexts')['locations']['taxonomy'];
    $current_selected_lct_term = get_term_meta($current_act_term_id, 'area_parent_location_term', true);
    if ($current_selected_lct_term !== '') {
       $current_selected_lct_term = (int)$current_selected_lct_term;
       $term_exists_in_taxonomy = get_term_by('id', $current_selected_lct_term, $locations_connector_taxonomy);
       if (!$term_exists_in_taxonomy) {
           $current_selected_lct_term = '';
        }
    }
    $options_array = get_terms(['taxonomy' => $locations_connector_taxonomy, 'hide_empty' => false, 'fields' => 'id=>name']);
    $fieldset_html = formHelper::generateRadioFieldsetWithIntegerValues( 'area_parent_location_term', 'Location Connector Parent', $current_selected_lct_term, $options_array, []);

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
    $act_int=(int)$area_term_id;
    $act_slug = get_term($act_int)->slug;
    $assignments = get_option('act_to_lct_assignments', []);
    $assigned_slugs = get_option('act_to_lct_slug_assignments', []);
        if (isset($assignments[$area_term_id])) {
        $current_associated_lct_database_term = $assignments[$area_term_id];
        $current_associated_lct_term_slug = $assigned_slugs[$act_slug];
    } else {
        $current_associated_lct_database_term = '';
        $current_associated_lct_term_slug = '';
        update_act_assignments($area_term_id, '');
    }
    if ($new_lct_id === '0' || $new_lct_id === 0 || empty($new_lct_id)) {
        update_term_meta($area_term_id, 'area_parent_location_term', '');
        update_term_meta($area_term_id, 'area_parent_location_name', '');
        update_term_meta($area_term_id, 'area_parent_location_slug', '');
        if ($current_associated_lct_database_term!=='') {
            $old_associated_act_terms = get_term_meta($current_associated_lct_database_term, 'associated_act_terms', true) ?: [];
            $old_associated_act_term_slugs = get_term_meta($current_associated_lct_database_term, 'associated_act_terms', true) ?: [];
            if (($key = array_search($area_term_id, $old_associated_act_terms)) !== false) {
                unset($old_associated_act_terms[$key]);
                update_term_meta($current_associated_lct_database_term, 'associated_act_terms', $old_associated_act_terms);
            }
            $act_id_int = (int)$area_term_id;
            $act_term_slug = get_term($act_id_int)->slug;
            
            if (($slug_key = array_search($act_term_slug, $old_associated_act_term_slugs)) !== false) {
                unset($old_associated_act_term_slugs[$slug_key]);
                update_term_meta($associated_lct_id, 'associated_act_slugs', $old_associated_act_term_slugs);
            }
        }
    } else {
        $new_lct_id = absint($new_lct_id); 
        $new_lct_slug = get_term($new_lct_id)->slug;
        $new_lct_name = get_term($new_lct_id)->name; 
        update_term_meta($area_term_id, 'area_parent_location_term', $new_lct_id);
        update_term_meta($area_term_id, 'area_parent_location_name', $new_lct_name);
        update_term_meta($area_term_id, 'area_parent_location_slug', $new_lct_slug);
        if ($current_associated_lct_database_term !== $new_lct_id) {
            $new_associated_act_terms = get_term_meta($new_lct_id, 'associated_act_terms', true) ?: [];
            $new_associated_act_slugs = get_term_meta($new_lct_id, 'associated_act_slugs', true) ?: [];
            if (!in_array($area_term_id, $new_associated_act_terms)) {
                $new_associated_act_terms[] = $area_term_id;
                 $new_associated_act_slugs[] = $act_slug;
                update_term_meta($new_lct_id, 'associated_act_terms', $new_associated_act_terms);
                
            }
            
        }
    }

    update_act_assignments($area_term_id, $new_lct_id);
}
/*
function render_area_connection_checkboxes_for_location_terms($location_term, $locations_connector_taxonomy) {
 if($location_term ) {
  $current_lct_term_id = (int)$location_term->term_id;
    $current_lct_term_id = $location_term->term_id;
    $current_lct_associated_area_term_ids = get_term_meta($current_lct_term_id, 'associated_act_terms', true);
    $current_lct_associated_area_term_slugs = get_term_meta($current_lct_term_id, 'associated_act_slugs', true);
 }
 
  $area_connector_tax = get_option('enabled_connector_contexts')['service_areas']['taxonomy'];
  $act_terms = get_terms(['taxonomy' => $area_connector_tax, 'hide_empty' => false, 'fields' => 'id=>name']);
  $assignments =  get_option('act_to_lct_assignments', []);
  $slug_assignments = get_option('act_to_lct_slug_assignments',[]);
  $assigned_act_ids = [];
  $selected_terms = [];
  foreach ($assignments as $act_id => $lct_id) {
   if ($lct_id !== '') { 
    $assigned_act_ids[] = $act_id;
    }
    if ($current_lct_term_id !== null && $current_lct_term_id === $lct_id) {
        $selected_terms[] = $act_id;
    } 
  }
    ?>
 <table class="form-table dibraco-term-form-table widefat">
  <tr>
    <?= '<p><strong>Associated Area Connector Terms:</strong></p>';
    $current_lct_associated_area_term_slugs = implode(', ', $current_lct_associated_area_term_slugs);

echo "<div><strong>Slugs A:</strong> $current_lct_associated_area_term_slugs</div>";
    ?> <td>
     <?php $renderedOne = false;  ?>
    <?php foreach ($act_terms as $act_id => $act_name){
        $act_term = get_term($act_id, $area_connector_tax);
        $act_slug = $act_term->slug;
        $is_slug_synced = in_array($act_slug, (array)$current_lct_associated_area_term_slugs);
                    if (!in_array($act_id, $assigned_act_ids, true) || in_array($act_id, $selected_terms, true)) {
                        $renderedOne = true;
                        $checked = in_array($act_id, $selected_terms, true);
                        $input_id = "associated_act_term_{$act_id}";
                    ?>
                        <label for="<?= $input_id; ?>">
                            <input type="checkbox" id="<?= $input_id; ?>" name="associated_act_terms[]" value="<?= $act_id; ?>"
                                <?= $checked ? 'checked="checked"' : ''; ?>>
                            <?= esc_html($act_name); ?>
                            <?php if ($is_slug_synced) { ?>
                                <span style="color: green;">(Slugs Synced)</span>
                            <?php } else { ?>
                                <span style="color: red;">(Slugs Not Synced)</span>
                            <?php } ?>
                        </label><br>
                    <?php }
                } ?>
            <?php if (!$renderedOne) { ?>
                    <?= '<p>No area terms available.</p>'; ?>
                <?php } ?>
    </td>
  </tr>
</table>
    <?php
}*/
function render_area_connection_checkboxes_for_location_terms($location_term, $locations_connector_taxonomy) { 
    $area_connector_tax = get_option('enabled_connector_contexts')['service_areas']['taxonomy'];
$taxonomy_human_readable = ucwords(str_replace(['-', '_'], ' ', $area_connector_tax));

    $all_act_terms = get_terms(['taxonomy' => $area_connector_tax, 'hide_empty' => false, 'fields' => 'id=>name']);
    $assignments = get_option('act_to_lct_assignments', []);
    $assigned_act_ids =[];
   $renderedOne = false; 
    foreach ($assignments as $assigned_act_id => $assigned_lct_id){
        $assigned_act_id = (int)$assigned_act_id;
        $assigned_act_ids[] = $assigned_act_id;
        }
    $unassigned_act_ids = [];
    foreach ($all_act_terms as $act_term_id => $act_term_name) {
        $area_term_id = (int)$act_term_id;
        if (in_array($area_term_id, $assigned_act_ids)) {continue;}
            $unassigned_act_ids[$area_term_id] = $act_term_name;
        }
   $selected_terms = [];
    if ($location_term) {
        $current_location_connector_term_id = (int)$location_term->term_id;
        $act_ids_for_this_location = get_term_meta($current_location_connector_term_id, 'associated_act_terms', true);
        foreach ($act_ids_for_this_location as $act_id_in_meta) {
            $act_id_in_meta =  (int)$act_id_in_meta;
            $act_in_meta_name = get_term($act_id_in_meta)->name;
            $selected_terms[$act_id_in_meta] = $act_in_meta_name;
       }
    }
    $terms_to_display = $selected_terms + $unassigned_act_ids;
    if (!empty($terms_to_display)) {
$rows = [];
$cols_per_row = 4;
$current_row = [];

foreach ($terms_to_display as $act_id => $act_name) {
    $is_selected = array_key_exists($act_id, $selected_terms);
    $input_id = "associated_act_term_{$act_id}";
    $checkbox_html = '<input type="checkbox" id="'.$input_id.'" name="associated_act_terms[]" value="'.$act_id.'"'.checked($is_selected, true, false).'>';
    $label_html = '<label for="'.$input_id.'">'.$act_name.'</label>';
    $current_row[] = $checkbox_html.' '.$label_html;

    if (count($current_row) === $cols_per_row) {
        $rows[] = $current_row;
        $current_row = [];
    }
}

if (!empty($current_row)) {
    while (count($current_row) < $cols_per_row) {
        $current_row[] = '';
    }
    $rows[] = $current_row;
}
   $table = [
    'title'   => '',
    'headers' => [$taxonomy_human_readable, '&nbsp;', '&nbsp;', '&nbsp;'],
    'styles'  => ['width:25%','width:25%','width:25%','width:25%'],
    'rows'    => $rows,
    ];
    render_dibraco_admin_table($table);
} else {
    echo '<p>No service areas are available to be assigned.</p>';
}
}
function save_area_connections_for_location_terms($term_id, $tt_id) {
    $term = get_term($term_id);
    $taxonomy = $term->taxonomy;
   $posted_act_terms = array_map('absint', $_POST['associated_act_terms'] ?? []);
    $posted_act_terms = array_filter($posted_act_terms, function($val) {
      return is_numeric($val) && $val > 0;
    });    
    $new_lct_slug = $term->slug;
    $new_lct_name = $term->name;
    $lct_term_id = $term->term_id;
    update_term_meta($term_id, 'associated_act_terms', $posted_act_terms);
    $assignments = get_option('act_to_lct_assignments', []);
        foreach ($assignments as $act_id => $lct_id) {
            if ($lct_id === $term_id && !in_array($act_id, $posted_act_terms)) {
                update_act_assignments($act_id, ''); 
                update_term_meta($act_id, 'area_parent_location_term', ''); 
                update_term_meta($act_id, 'area_parent_location_name', ''); 
                update_term_meta($act_id, 'area_parent_location_slug', ''); 

                }
            }
 
         $act_slugs= [];
         foreach ($posted_act_terms as $act_id) {
            $act_int = (int)$act_id;
            $act_slug = get_term($act_int)->slug;
            $act_slugs[] = $act_slug;
            update_act_assignments($act_id, $term_id); 
            update_term_meta($act_id, 'area_parent_location_term', $lct_term_id);
            update_term_meta($act_id, 'area_parent_location_name', $new_lct_name); 
            update_term_meta($act_id, 'area_parent_location_slug', $new_lct_slug); 
        }   
        update_term_meta($lct_term_id, 'associated_act_slugs', $act_slugs);
    }


function update_act_assignments($act_id, $lct_id = null) {
   $act_int = (int)$act_id;
   $act_slug = get_term($act_int)->slug;
    $assignments = get_option('act_to_lct_assignments');
     $assigned_slugs = get_option('act_to_lct_slug_assignments');
    if ($lct_id === null || $lct_id === '' || $lct_id === 0) {
        $assignments[$act_id] = '';
        $assigned_slugs[$act_slug]='';
    } else {
        $assignments[$act_id] = (int)$lct_id;
        $assigned_slugs[$act_slug] = get_term($lct_id)->slug;
    }
    update_option('act_to_lct_assignments', $assignments);
    update_option('act_to_lct_slug_assignments', $assigned_slugs);
}
function delete_service_area_location_term_handler($act_id) {
    $assignments = get_option('act_to_lct_assignments', []);
    $assigned_slugs = get_option('act_to_lct_slug_assignments', []);
    if (!isset($assignments[$act_id])) { return; }
    $associated_lct_id = $assignments[$act_id];
    if (!empty($associated_lct_id)) {
        $associated_area_terms = get_term_meta($associated_lct_id, 'associated_act_terms', true) ?: [];
        $associated_area_term_slugs = get_term_meta($associated_lct_id, 'associated_act_slugs', true) ?: [];
        if (($key = array_search($act_id, $associated_area_terms)) !== false) {
            unset($associated_area_terms[$key]);
            update_term_meta($associated_lct_id, 'associated_act_terms', array_values($associated_area_terms));
        }
            $act_id_int = (int)$act_id;
            $act_term_slug = get_term( $act_id_int)->slug;
            if (($slug_key = array_search($act_term_slug, $associated_area_term_slugs)) !== false) {
                unset($associated_area_term_slugs[$slug_key]);
                update_term_meta($associated_lct_id, 'associated_act_slugs', array_values($associated_area_term_slugs));
            }
    }
    unset($assignments[$act_id]);
    unset($assigned_slugs[$act_term_slug]);
    update_option('act_to_lct_assignments', $assignments); 
    update_option('act_to_lct_slug_assignments', $assigned_slugs);
}
function delete_location_area_term_handler($term_id) {
    $assignments = get_option('act_to_lct_assignments');
    foreach ($assignments as $act_id => $lct_id) {
        if ($lct_id == $term_id) {
            $lct_slug = get_term($lct_id)->slug;
            (int)$act_id;
            update_act_assignments($act_id, '');
            update_term_meta($act_id, 'area_parent_location_name', '');
            update_term_meta($act_id, 'area_parent_location_term', '');
            update_term_meta($act_id, 'area_parent_location_slug', ''); 
        }
    }
}
