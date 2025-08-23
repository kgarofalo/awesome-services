<?php

function dibraco_get_taxonomy_box($taxonomy, $post_type){
        remove_meta_box("{$taxonomy}div", $post_type, 'side');
        remove_meta_box("{$taxonomy}div", $post_type, 'normal');
        remove_meta_box("tagsdiv-{$taxonomy}",   $post_type, 'side');
        remove_meta_box("tagsdiv-{$taxonomy}",   $post_type, 'normal');
        $human_readable_tax = ucwords(str_replace(['_', '-'], ' ', $taxonomy));
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, ]);
        $options_array =[];
        foreach($terms as $term){
            $term_id = (int)$term->term_id;
            $term_name = $term->name;
            $options_array[$term_id] = $term_name;
        }
         return ['label' => "Select a {$human_readable_tax} Term", 'options_array' => $options_array];
}
function dibraco_prepare_term_images($image_type, $term_id) {
    $meta = (array) get_term_meta($term_id, "dibraco_{$image_type}_images", true);
    $all_image_values = array_values($meta);
    $prepared_term_image_values = array_filter($all_image_values);
    shuffle($prepared_term_image_values);
    return $prepared_term_image_values;
}
function prepareBeforeAfterGroup($primary_term_id, $post_id) {
    $term_ba_data = (array) get_term_meta($primary_term_id, 'dibraco_ba', true);
    $row_count = (int) $term_ba_data['dibraco_ba_row_count'];
    $is_locked = get_post_meta($post_id, 'dibraco_ba_lock', true);
    $final_before = get_post_meta($post_id, 'dibraco_ba_before', true);
    $final_after = get_post_meta($post_id, 'dibraco_ba_after', true);
    if ($is_locked !== '1' || (empty($final_before) && empty($final_after))) {
        $ba_pairs = [];
        for ($i = 0; $i < $row_count; $i++) {
            $before = $term_ba_data["dibraco_ba[{$i}][before]"];
            $after = $term_ba_data["dibraco_ba[{$i}][after]"];
            if ($before && $after) {
                $ba_pairs[] = ['before' => $before, 'after' => $after];
            }
        }
        if (!empty($ba_pairs)) {
            $random_pair = $ba_pairs[array_rand($ba_pairs)];
            $final_before = $random_pair['before'];
            $final_after = $random_pair['after'];
        }
    }
    return [
        ['type' => 'image', 'name' => 'dibraco_ba_before', 'label' => 'Before Image', 'value' => $final_before],
        ['type' => 'image', 'name' => 'dibraco_ba_after', 'label' => 'After Image', 'value' => $final_after],
        ['type' => 'checkbox', 'name' => 'dibraco_ba_lock', 'label' => 'Lock This B/A Pair', 'value' => $is_locked]
    ];
}
function dibraco_prepare_landscape_portrait_images($image_type, $primary_term_id, $post_id){
    $pool = dibraco_prepare_term_images($image_type, $primary_term_id);
    $lock_value_1 = get_post_meta($post_id, "dibraco_{$image_type}_1_lock", true);
    $image_1      = get_post_meta($post_id, "dibraco_{$image_type}_1", true);
    $lock_value_2 = get_post_meta($post_id, "dibraco_{$image_type}_2_lock", true);
    $image_2      = get_post_meta($post_id, "dibraco_{$image_type}_2", true);

   if ($lock_value_1 === "1" && !empty($image_1) && in_array($image_1, $pool, true)) {
        unset($pool[array_search($image_1, $pool, true)]);
    }
    if ($lock_value_2 === "1" && !empty($image_2) && in_array($image_2, $pool, true)) {
       unset($pool[array_search($image_2, $pool, true)]);
    }
    if ($lock_value_1 !== '1' && !empty($pool)) { $image_1 = array_shift($pool); }
    if ($lock_value_2 !== '1' && !empty($pool)) { $image_2 = array_shift($pool); }
    return [ "dibraco_{$image_type}_1" => ['value'=> $image_1, 'type' => 'image'], "dibraco_{$image_type}_1_lock" =>['value'=> $lock_value_1, 'type' => 'toggle'], "dibraco_{$image_type}_2" => ['value'=>$image_2,'type' => 'image'], "dibraco_{$image_type}_2_lock" => ['value'=> $lock_value_2, 'type' => 'toggle']];
}

function dibraco_render_side_metabox($post) {
    $post_type = $post->post_type;
    $post_id = $post->ID;
    $enabled_contexts = get_option('enabled_contexts');
    foreach ($enabled_contexts as $context) {
        if ($context['post_type'] === $post_type) {
            $context = $context;
            break;
        }
    }    
    $context_name = $context['context_name'];
     $context_type = $context['context_type'];
     $landscape = $context['repeater_images'];
     $portrait = $context['portrait_images'];

    if ($context_type !== 'unique') {
        $primary_taxonomy = $context['taxonomy'];
        $primary_term_id = dibraco_get_current_term_id_for_post($post_id, $primary_taxonomy);
        $primary_taxonomy_box = dibraco_get_taxonomy_box($primary_taxonomy, $post_type);
        echo FormHelper::generateRadioFieldsetWithIntegerValues("{$primary_taxonomy}_term", $primary_taxonomy_box['label'], $primary_term_id, $primary_taxonomy_box['options_array'], []);

        if ($context_type === 'type' && $context['post_per_term'] === "1") {
            $main_term_maps = get_option("{$context_name}_main_posts"); $is_main = "0";
            if (is_array($main_term_maps)) { foreach ($main_term_maps as $map_term_id => $map_post_id){ if ((int)$primary_term_id === (int)$map_term_id && (int)$post_id === (int)$map_post_id){ $is_main = "1"; break; } } }
            echo FormHelper::generateCheckBox('main_post_for_term', 'Is This The Main Post?', $is_main, []);

            if ($is_main === "1") {
                $grouped_options = []; $registered_types = (array)get_taxonomy($primary_taxonomy)->object_type;
                foreach ($registered_types as $registered_type){
                    $posts = get_posts(['post_type'=>$registered_type,'posts_per_page'=>-1,'post_status'=>'publish','tax_query'=>[['taxonomy'=>$primary_taxonomy,'field'=>'term_id','terms'=>[(int)$primary_term_id]]]]);
                    if (!empty($posts)) { $options = []; foreach ($posts as $obj){ $options[$obj->ID] = $obj->post_title; } $grouped_options[$registered_type] = $options; }
                }
                foreach ($grouped_options as $registered_type => $options){
                    $type_obj = get_post_type_object($registered_type); $label = (!empty($type_obj->labels->singular_name) ? $type_obj->labels->singular_name : ucfirst($registered_type));
                    echo FormHelper::generateField("main_posts[{$primary_term_id}]", ['type'=>'select','label'=>"Select {$label}",'value'=>$post_id,'options'=>$options]);
                }
            }
        }
    }
    if ($context_type !== 'connector'){
        $related_connectors = $context['related_connectors'];
        if ($related_connectors!==[]) {
                foreach ($related_connectors as $related_connector_context_name => $related_connector_context_data) {
                    $related_taxonomy = $related_connector_context_data['taxonomy'];
                    $related_term_id = dibraco_get_current_term_id_for_post($post_id, $related_taxonomy);
                    $related_taxonomy_box = dibraco_get_taxonomy_box($related_taxonomy, $post_type);
                echo FormHelper::generateRadioFieldsetWithIntegerValues("{$related_taxonomy}_term", $related_taxonomy_box['label'], $related_term_id, $related_taxonomy_box['options_array'],  []);
            }
        } 
    }


if ($landscape ==='1'){
    echo "<div class='dibraco-landscape-images-box'>";
    if ($context_type==='unique'){
     $fields = get_landscape_image_fields('unique');
     foreach($fields as $field_key => $field_config){
          $field_value = get_post_meta( $post_id, $field_key, true );
          $field_config['value'] = $field_value;
         echo FormHelper::generateField($field_key, $field_config );
     }
    } else {
        $landscape_images = dibraco_prepare_landscape_portrait_images('landscape', $primary_term_id, $post_id);
            foreach ($landscape_images as $field_key => $field_config){
            echo FormHelper::generateField( $field_key, $field_config );
        }
    }
echo "</div>";    
}
if ($portrait ==='1'){
    echo "<div class='dibraco-portrait-images-box'>";
      if ($context_type==='unique'){
      $fields = get_portrait_image_fields('unique');
     foreach($fields as $field_key => $field_config){
          $field_value = get_post_meta( $post_id, $field_key, true );
          $field_config['value'] = $field_value;
         echo FormHelper::generateField( $field_key, $field_config );
     }
    } else {
        $portrait_images = dibraco_prepare_landscape_portrait_images('portrait', $primary_term_id, $post_id);
        foreach ($portrait_images as $field_key => $field_config){
            echo FormHelper::generateField( $field_key, $field_config );
        }
    }
        
echo "</div>";    
}  
if ($context_type === 'type') {
    $primary_term_id = dibraco_get_current_term_id_for_post($post_id, $context['taxonomy']);
    if ($context['term_icon'] === '1') {
        $field_config = ['value' => get_term_meta($primary_term_id, 'term_icon', true), 'type' => 'image'];
        echo "<div class='dibraco-term-icon-box'>";
        echo FormHelper::generateField('term_icon', $field_config);
        echo '</div>';
    }
    if ($context['before_after'] === "1") {
        echo "<div class='dibraco-ba-images-box'>";
        $pair_lock = prepareBeforeAfterGroup($primary_term_id, $post_id);
        foreach ($pair_lock as $field_config) {
            echo FormHelper::generateField($field_config['name'], $field_config);
        }  
        echo '</div>';
    }
}
}
function dibraco_save_landscape_portrait_fields($post_id, $context_type, $field_definitions, $image_type){
        foreach ($field_definitions as $field_name => $field_config) {
        if (str_contains($field_name, 'lock')){
             $value = (string)$_POST[$field_name];
             }  else {
                 $value = (int)$_POST[$field_name];
             }
             update_post_meta($post_id, $field_name, $value);
        }
}
function dibraco_save_meta_boxes($post_id, $post_type, $update){
    if ($update===false){return;}
    $post_type = get_post_type($post_id);
    $enabled_contexts = get_option('enabled_contexts');
    $post_permalink = get_permalink($post_id);
    foreach ($enabled_contexts as $context) {
        if ($context['post_type'] === $post_type) {
            $context = $context;
            break;
        }
    }
    $context_name = $context['context_name'];


    $context_type = $context['context_type'];
    $contact_fields = $context['contact_section'];
    $dibraco_banner = $context['dibraco_banner'];
    $main_sections = $context['main_sections'];
    $landscape = $context['repeater_images'];
    $portrait = $context['portrait_images'];
    $dibraco_about = $context['about_section'] ?? '';
    if ($landscape === '1') {
        $land_field_defs = get_landscape_image_fields($context_type);
        dibraco_save_landscape_portrait_fields($post_id, $context_type, $land_field_defs, 'landscape');
    }
    if ($portrait === '1') {
        $port_field_definitions = get_portrait_image_fields($context_type);
        dibraco_save_landscape_portrait_fields($post_id, $context_type, $port_field_definitions, 'portrait');
    }
    $user_defined_fields = get_dibraco_custom_fields_for_context($context_name);
    if (!empty($user_defined_fields)) {
        foreach ($user_defined_fields as $field_id => $field_config) {
            update_post_meta($post_id, $field_id, sanitize_textarea_field($_POST[$field_id]));
        }
    }

    if ($context_type ==='connector') {
        $meta_prefix = rtrim($context_name, 's');
        $post_id_key = "{$meta_prefix}_post_id";
        $link_url_key = "{$meta_prefix}_link_url";
        $taxonomy = $context['taxonomy'];
        $new_term_id = $_POST["{$taxonomy}_term"];
        if ($context_name ==='locations'){
            handle_save_location_term_meta($new_term_id, $taxonomy);
        }
        dibraco_enforce_one_connector_term_per_connector_post($post_id, $new_term_id, $taxonomy, $post_id_key, $link_url_key);
    }
    if ($context_type === 'unique'){
        $related_connectors = $context['related_connectors'];
        if (!empty($related_connectors)) {
            save_related_connector_terms_to_unique($post_id, $context, $related_connectors);
        }
        if ($context_name ==='employee') {   
             $has_certification = $context['has_certification'];
             $fields = get_employee_fields($has_certification)['employee-fields']['fields'];
             $employee_info = [];
             foreach ($fields as $field_key => $field_config) {
                if($field_key !== 'certification_data'){
                    $employee_info[$field_key] = $_POST[$field_key];
                }
                if(($has_certification ==="1") && ($field_key === 'certification_data' && $_POST['has_certification'] ==="1")){
                    $cert_data = [];
                    foreach ($field_config['fields']  as $subfield_key => $subfield_config){
                        $cert_data[$subfield_key] = $_POST[$subfield_key];
                     }
                       update_post_meta($post_id, 'certification_data', $cert_data);
                    }
               update_post_meta($post_id, 'employee_data', $employee_info);
            }
        }   
    }
    if ($context_type ==='type'){
        $related_connectors = $context['related_connectors'];
        $before_after = $context['before_after'];
        $taxonomy = $context['taxonomy'];
        $post_per_term = $context['post_per_term'];
        $checkbox_value = "0";
        $previous_type_term_id = dibraco_get_current_term_id_for_post($post_id, $taxonomy);
        $new_type_term_id = $_POST["{$taxonomy}_term"];
        if ($new_type_term_id!==''){
                $new_type_term_id = (int)$new_type_term_id;
            }
        if($post_per_term==="1"){
             if($context['term_icon']==="1") {
                $value = (int)$_POST['term_icon'];
                    update_post_meta($post_id, 'term_icon', $value);
                 }
             if ($before_after ==="1"){
                $ba_meta_keys = [ 'dibraco_ba_before', 'dibraco_ba_after', 'dibraco_ba_lock' ];
                foreach ($ba_meta_keys as $meta_key) {
                    update_post_meta($post_id, $meta_key, $_POST[$meta_key]);
                 }
             }
        $repeater_data = [];
        $row_count = $_POST['da_list_repeater_row_count'];
        $repeater_data['da_list_repeater_row_count'] = $row_count;
        $repeater_data['da_list_title'] = $_POST['da_list_title'];  
        for ($index = 0; $index < $row_count; $index++){
            $repeater_data["da_list_repeater[{$index}][item]"] = $_POST["da_list_repeater"][$index]['item'];  
        }
        update_post_meta($post_id, '_list_repeater', $repeater_data);
        $related_connector_count = $context['related_connector_count'];
        if ($related_connector_count ===1){
            save_related_connector_data_table_fallbacks($context, $post_id, $new_type_term_id, $previous_type_term_id, $post_permalink);
            }
        if ($related_connector_count ===2){
            save_related_connector_data_table_fallbacks($context, $post_id, $new_type_term_id, $previous_type_term_id, $post_permalink);
            }
        }
        if($new_type_term_id ===''){
            $new_type_term_id = [];
            }
        wp_set_object_terms($post_id, $new_type_term_id, $taxonomy);
            if (!empty($related_connectors)) {
                update_related_connector_terms_from_type_post_update($post_id, $context, $previous_type_term_id, $new_type_term_id);
            }
        }
    $fields_to_save = [];
    if ($dibraco_banner === "1") {
      $fields_to_save += get_banner_fields();
    }
    if ($dibraco_about === "1") {
      $fields_to_save += get_about_fields();
    }
    if ($main_sections === "1") {
        $fields_to_save += get_section_title_fields();
    }
    if ($contact_fields === "1") {
     $fields_to_save += get_contact_fields();
    }
    foreach ($fields_to_save as $field_name => $field_data){
        if ($field_data['type'] ==="text"){
            update_post_meta($post_id, $field_name, sanitize_text_field($_POST[$field_name]));
            continue;
        }
       if ($field_data['type'] ==="textarea"){
            update_post_meta($post_id, $field_name, sanitize_textarea_field($_POST[$field_name]));
            continue;
     }
     if ($field_data['type'] ==="wysiwyg"){
         update_post_meta($post_id, $field_name, wp_kses_post($_POST[$field_name]));
     }
    }
}

function save_related_connector_data_table_fallbacks($context, $post_id, $new_type_term_id, $previous_type_term_id, $post_permalink){
    $context_name = $context['context_name'];
    if ($related_connectors===2){
    $locations_taxonomy =$context['related_connectors']['locations']['taxonomy'];
        $areas_taxonomy =$context['related_connectors']['service_areas']['taxonomy'];
    } else {
         foreach($related_connectors as $related_connector_context => $related_context_data);
         $connector_taxonomy = $related_context_data['taxonomy'];
    }
    $taxonomy = $context['taxonomy'];
    $related_connectors = $context['related_connectors'];
    $related_connector_count = $context['related_connector_count'];
    $meta_key_on_connector_terms = "related_type_{$context_name}";
    $main_post_map_key = "{$context_name}_main_posts";
    $checkbox_value = $_POST['main_post_for_term'];
    $term_status = 'changed';
    $main_post_map = get_option($main_post_map_key);
    foreach ($main_post_map as $map_term_id=>$map_post_id){
      (int)$map_term_id; (int)$map_post_id;
    if ($map_term_id === $previous_type_term_id) {
     
    }
    $main_post_id = get_term_meta($new_type_term_id, 'main_post_for_term', true); 
    if  ($main_post_id ===''){
        dibraco_activate_new_main_type_term_post_fallback($new_type_term_id, $related_connectors, $post_permalink, $meta_key_on_connector_terms);
        $main_post_map[$new_type_term_id] = (int)$post_id;
        update_option($main_post_map_key, $main_post_map);
    }
    if($previous_type_term_id !=='' || $new_type_term_id !==''){
        switch($term_status){
            case 'changed':
                $old_type_term_main_post_id = get_term_meta($previous_type_term_id, 'main_post_for_term', true);
                if ($old_type_term_main_post_id !== '' && $post_id === (int)$old_type_term_main_post_id) {
                    $set_or_not = figure_out_main_post_change_on_old_term($previous_type_term_id, $post_id, $meta_key_on_connector_terms, $taxonomy, $related_connectors, $related_connector_count, $main_post_map_key);
                    if ($set_or_not === 'unset'){
                        unset($main_post_map[$previous_type_term_id]);
                    } else {
                        $main_post_map[$previous_type_term_id] = $set_or_not;
                    }
                    update_option($main_post_map_key, $main_post_map);
                }
                if($new_type_term_id !==''){
                      if ($related_connectors ===1) {
                           $current_connector_term = dibraco_get_current_term_id_for_post($post_id, $connector_taxonomy);
                          $existsing_meta = get_term_meta($current_connector_term, $meta_key_on_connector_terms, true);
                           if ($current_connector_term !==''){
                            type_connector_clear_old_meta_not_touching_fallbacks($current_connector_term, $meta_key, $type_term_id, $post_id, $post_per_term);
                        }  
                      } elseif ($related_connectors===2){
                        $current_service_area_term = dibraco_get_current_term_id_for_post($post_id, $connector_taxonomy);
                        if ($current_service_area_term !==''){
                            type_connector_clear_old_meta_not_touching_fallbacks($current_connector_term, $meta_key, $type_term_id, $post_id, $post_per_term);
                        }
                      }
                    if ($checkbox_value === "1") {
                        dibraco_update_one_main_type_post_fallback($new_type_term_id, $post_permalink, $related_connectors, $meta_key_on_connector_terms, $related_connector_count);
                        $main_post_map[$previous_type_term_id] = (int)$post_id;
                        update_option($main_post_map_key, $main_post_map);
                    }
                }
                break;
            case 'not_changed':
                $current_type_term_main_post_id = (int)$main_post_id;
                $new_type_term_main_post_id     = (int)$post_id;
                if ($checkbox_value === "0") {
                    if ($current_type_term_main_post_id === $new_type_term_main_post_id) {
                        $set_or_not = figure_out_main_post_change_on_old_term($new_type_term_id, $post_id, $meta_key_on_connector_terms, $taxonomy, $related_connectors, $related_connector_count, $main_post_map_key);
                        if ($set_or_not === 'unset'){
                            unset($main_post_map[$previous_type_term_id]);
                        } else {
                            $main_post_map[$previous_type_term_id] = $set_or_not;
                        }
                        update_option($main_post_map_key, $main_post_map);
                    }
                }
                break;
            }
        }
    }
          
    }                
function dibraco_save_side_box_fields($post_id){

}
function dibraco_render_content_metabox($post) {
$post_type = $post->post_type;
    $post_id = $post->ID;
    $enabled_contexts = get_option('enabled_contexts');
    foreach ($enabled_contexts as $context) {
        if ($context['post_type'] === $post_type) {
            $context = $context;
            break;
        }
    }
    $context_name = $context['context_name'];
    $context_type = $context['context_type'];
    if ($context_type !== 'unique'){
        $taxonomy = $context['taxonomy'];
    }
    $dibraco_about = $context['about_section'] ?? '';
    $contact_fields = $context['contact_section'];
    $dibraco_banner = $context['dibraco_banner'];
    $main_sections = $context['main_sections'];
    $context_name = $context['context_name'];
$pairs_to_render = [];
    $fields_to_render[] = [];
    if ($dibraco_banner === "1") {
        $pairs_to_render += get_banner_fields();
    }
    if ($dibraco_about === "1") {
       $pairs_to_render += get_about_fields();
    }
    if ($main_sections === "1") {
      $pairs_to_render += get_section_title_fields();
    }
    $user_defined_fields = get_dibraco_custom_fields_for_context($context_name);
    if (!empty($user_defined_fields)) {
        foreach ($user_defined_fields as $field_id => $field_config) {
            if (isset($field_config['pair']) || isset($field_config['pair_end'])) {
                $pairs_to_render +=  [$field_id => $field_config];
            } else {
                $fields_to_render[] =  [$field_id => $field_config];
            }
         }
    }
    if ($contact_fields === "1") {
        $pairs_to_render += get_contact_fields();
    }
    $pairs_to_render = array_chunk($pairs_to_render, 2, true);
    if ($context_name ==='employee'){
        $has_certification = $context['has_certification'];
        $fields = get_employee_fields($has_certification);
        $employee_data = (array)get_post_meta($post_id, 'employee_data', true);
        if($has_certification ==='1'){
          $cert_data = (array)get_post_meta($post_id, 'certification_data', true);
          $employee_data = array_merge($employee_data, $cert_data);
        }
    echo FormHelper::generateField('injector_start', ['type' => 'valueinjector', 'meta_array' => $employee_data]);
    echo FormHelper::generateVisualSection('employee-fields', $fields['employee-fields']);
    echo FormHelper::generateField('injector_end', ['type' => 'injectionend']);
    }
   echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
   if($context_name ==='service_areas'){
       $base_term_field_definitions =  get_service_area_term_fields();
       $term_id = dibraco_get_current_term_id_for_post($post_id, $taxonomy);
       foreach ($base_term_field_definitions as $field_key => $field_config){
            $field_config['value'] = get_term_meta($term_id, $field_key, true);
            echo '<div style="display: flex; width: 23%;">';
            echo FormHelper::generateField($field_key, $field_config);
            echo '</div>';
        }
   }
   if ($context_name ==='locations'){
      echo  render_location_meta_box($post_id, $context); 
   }
    foreach ($pairs_to_render as $pair) {
        echo '<div style="width: 49%; margin-bottom: 20px;">'; 
        foreach ($pair as $field_key => $field_config) {
            $field_config['value'] = get_post_meta($post_id, $field_key, true);
            echo FormHelper::generateField($field_key, $field_config);
        }
        echo '</div>'; 
    }
    if (!empty($fields_to_render)) {
    echo '<div style="width: 49%; display: flex; flex-wrap: wrap; gap: 2px; margin-bottom: 20px;">';
    foreach ($fields_to_render as $single_field) {
        foreach ($single_field as $field_key => $field_config) {
            $field_config['value'] = get_post_meta($post_id, $field_key, true);
            $type = $field_config['type'];
            $width= '49%';
            if ($field_config['type']==='wysiwyg' ||$field_config['type']==='textarea'){
                $width='98%';
            }
            echo '<div style="width: ' . $width . ';">';
            echo FormHelper::generateField($field_key, $field_config);
            echo '</div>';
        }
    }
        echo '</div>';
    }
    if ($context_type ==='type'){
        $post_per_term = $context['post_per_term'];
        if ($post_per_term ==="1"){
            $repeater_meta = get_post_meta($post_id, '_list_repeater', true);
            $repeater_fields = get_repeater_field_list();
            echo '<div style="width: 49%; margin-bottom:10px;">';
            echo FormHelper::generateField('doesnt_matter', ['type'=> 'starttracking', 'meta_array' => $repeater_meta]);
            foreach ($repeater_fields as $field_key => $field_data) {
                if ($field_key === 'da_list_title') {
                    echo FormHelper::generateField($field_key, $field_data);
                } elseif ($field_key === 'da_list_repeater') {
					echo '<div style="width: 97%; display:block;">';
                    echo FormHelper::generateRepeaterField($field_key, $field_data);
					echo '</div>';
                }
            }
            echo '</div>';
            echo FormHelper::generateField('trackerend', ['type'=> 'endtracking']);
        }
    }
    echo '</div>';
}
function da_save_standard_image_meta($post_id, $landscape_images, $portrait_images, $term_icon) {

    if ($landscape_images === '1') {
        $landscape_field_definitions = get_landscape_image_fields();
        foreach ($landscape_field_definitions as $field_name => $field_config) {
                if (str_contains($field_name, 'lock')){
             $value = (string)$_POST[$field_name];
             }  else {
                 $value = (int)$_POST[$field_name];
             }
             update_post_meta($post_id, $field_name, $value);
        }
    }

    if ($portrait_images === '1') {
        $portrait_field_definitions = get_portrait_image_fields();
        foreach ($portrait_field_definitions as $field_name => $field_config) {
             if (str_contains($field_name, 'lock')){
             $value = (string)$_POST[$field_name];
             }  else {
                 $value = (int)$_POST[$field_name];
             }
             update_post_meta($post_id, $field_name, $value);
        }
    }
}

function da_render_images_for_pages(){
$post_id = get_the_ID();
$fields_definitions_for_landscape = get_landscape_image_fields();
$fields_definitions_for_portrait = get_portrait_image_fields();
echo "<div id='da_type_landscape_images' class='dibraco-landscape-images-container dibraco-landscape-images'>";
foreach ($fields_definitions_for_landscape as $field_key => $field_config) { 
if (str_ends_with($field_key, '_lock')) {
    continue;
    }
            $field_value = get_post_meta( $post_id, $field_key, true );
            $field_config['value'] = $field_value;
            echo FormHelper::generateField( $field_key, $field_config );
        }
        echo '</div>';
        echo "<div id='da_type_portrait_images' class='dibraco-portrait-images-container dibraco-portrait-images'>";
        foreach ($fields_definitions_for_portrait as $field_key => $field_config) { 
            if (str_ends_with($field_key, '_lock')) {
                continue;
            }
            $field_value = get_post_meta( $post_id, $field_key, true );
            $field_config['value'] = $field_value;
            echo FormHelper::generateField( $field_key, $field_config );
        }
        echo '</div>';
}

function render_da_pages_metabox(){
$post_id = get_the_id();
$pairs_to_render = [];
$pairs_to_render += get_banner_fields();
$pairs_to_render += get_about_fields();
$pairs_to_render += get_section_title_fields();
$fields_to_render[] = [];

    $user_defined_fields = get_dibraco_custom_fields_for_context('page');
    if (!empty($user_defined_fields)) {
        foreach ($user_defined_fields as $field_id => $field_config) {
            if (isset($field_config['pair']) || isset($field_config['pair_end'])) {
                $pairs_to_render +=  [$field_id => $field_config];
            } else {
                $fields_to_render[] = [$field_id => $field_config];
            }
         }
    }
    $pairs_to_render += get_contact_fields();
    $pairs_to_render = array_chunk($pairs_to_render, 2, true);
    echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
    foreach ($pairs_to_render as $pair) {
        echo '<div style="width: 49%; margin-bottom: 20px;">'; 
        foreach ($pair as $field_key => $field_config) {
            $field_config['value'] = get_post_meta($post_id, $field_key, true);
            echo FormHelper::generateField($field_key, $field_config);
        }
        echo '</div>'; 
    }
    if (!empty($fields_to_render)) {
    echo '<div style="width: 49%; display: flex; flex-wrap: wrap; gap: 2px; margin-bottom: 20px;">';
    foreach ($fields_to_render as $single_field) {
        foreach ($single_field as $field_key => $field_config) {
            $field_config['value'] = get_post_meta($post_id, $field_key, true);
            $type = $field_config['type'];
            $width= '49%';
            if ($field_config['type']==='wysiwyg' ||$field_config['type']==='textarea'){
                $width='98%';
            }
            echo '<div style="width: ' . $width . ';">';
            echo FormHelper::generateField($field_key, $field_config);
            echo '</div>';
        }
    }
        echo '</div>';
    }
    echo '</div>';
}
function dibraco_save_section_fields_page($post_id){
 if (!dibraco_verify_post_save_request('da_section_fields', 'dibraco_save_da_section_fields')) {return;}

  $user_defined_fields = get_dibraco_custom_fields_for_context('page');
    if (!empty($user_defined_fields)) {
        foreach ($user_defined_fields as $field_id => $field_config) {
       update_post_meta($post_id, $field_id, sanitize_textarea_field($_POST[$field_id]));
        }
    }
$fields_to_save=[];
$fields_to_save += get_banner_fields();
$fields_to_save += get_about_fields();
$fields_to_save += get_section_title_fields();
$fields_to_save += get_contact_fields();
$fields_to_save += get_landscape_image_fields();
$fields_to_save += get_portrait_image_fields();
     foreach ($fields_to_save as $field_id => $field_data){
         if ($field_data['type']==='textarea' || $field_data['type']==='text' ){
            update_post_meta($post_id, $field_id, sanitize_textarea_field($_POST[$field_id]));
         continue;
         }
         if ($field_data['type']==='image'){
            update_post_meta($post_id, $field_id, (int)$_POST[$field_id]);
            continue;
         }
          if ($field_data['type']==='wysiwyg'){
            update_post_meta($post_id, $field_id, wp_kses_post($_POST[$field_id]));
            continue;
         }
     }
}





