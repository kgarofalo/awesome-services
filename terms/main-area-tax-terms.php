<?php

function render_service_area_meta_box($term_or_post, $service_areas_taxonomy) {
    $service_area_fields = get_service_area_term_fields();
    $service_areas_context = get_option('enabled_connector_contexts')['service_areas'];
    if ($term_or_post instanceof WP_Post){
        $post_id = $term_or_post->ID;
        $current_service_area_term_id = dibraco_get_current_term_id_for_post($post_id, $service_areas_taxonomy);
    } elseif (!$term_or_post instanceof WP_Post){
        $post_id = null;
        $area_term = $term_or_post;
        $current_service_area_term_id = $area_term->term_id;
        $current_service_area_term_name = $area_term->name;
        $current_service_area_post_id = get_term_meta($current_service_area_term_id, 'service_area_post_id', true);
        if ($service_areas_context['landscape_images'] === "1") {
           $service_area_fields += get_term_landscape_fields();
        }
        if (($service_areas_context['portrait_images']) === "1") {
            $service_area_fields += get_term_portrait_fields();
        }
    }
$mapped_values = [];
$all_values = get_term_meta($current_service_area_term_id, '', true);
$all_values = array_map('maybe_unserialize', array_map('current', $all_values));
$storage_keys = dibraco_extract_nested_arrays_test($service_area_fields);
error_log(print_r($all_values,true));
 foreach($storage_keys as $container_name => $storage_array_key){
    if (is_array($storage_array_key)){
        if (array_key_exists($container_name, $all_values)){
         $all_values = array_merge($all_values,$all_values[$container_name]); 
          $storage_keys = array_merge($storage_keys,$storage_keys[$container_name]);
          unset($all_values[$container_name]);
          unset($storage_keys[$container_name]);
        }
    }
}    
    $mapped_values = array_intersect_key($all_values, $storage_keys);
   wp_nonce_field('save_service_area_term', 'service_area_context_nonce');
      $location_term_slug = get_term_meta($current_service_area_term_id, 'area_parent_location_slug', true);
if (!empty($location_term_slug)) {
    echo '<div class="service-area-map">';
    echo do_shortcode("[dibraco_location_map loc='{$location_term_slug}']");
    echo '</div>';
}

 
        $service_area_link_url = get_term_meta($current_service_area_term_id, 'service_area_link_url',true);
        echo '<div class="no-edits-sa">';
        echo FormHelper::generateField('service_area_link_url', ['type'=>'no-edit', 'value'=> $service_area_link_url, 'label' => 'Service Area Post Url']);    
        echo FormHelper::generateField('instructions', ['type'=>'no-edit', 'value'=> 'Empty Lat/Long to refresh  coordinates']);    
        echo '</div>';
        FormHelper::generateField('who_cares', ['type' => 'valueinjector', 'meta_array' => $mapped_values]);
        echo FormHelper::generateVisualSection('service_area_section_fields', ['fields' => $service_area_fields]);
        FormHelper::generateField('who_cares', ['type' => 'injectionend']);
        $related_unique_contexts = $service_areas_context['related_unique_contexts'];
   if (!$post_id) {
           render_locations_connection_radio_for_area_terms($area_term, $service_areas_taxonomy);
        if (!empty($related_unique_contexts)) {
            render_context_tables($related_unique_contexts, 'unique', $act_term);
        }
        $related_type_contexts = $service_areas_context['related_type_contexts'];
        if (!empty($related_type_contexts)){
            render_context_tables($related_type_contexts, 'type', $current_service_area_term_id);
        }
    }
}

function handle_save_service_area_term($term_id, $service_areas_taxonomy) {
if (!dibraco_verify_post_save_request('service_area_context_nonce', 'save_service_area_term')) {return;}
    $service_areas_context = get_option('enabled_connector_contexts')['service_areas'];
    $template_fields = get_service_area_term_fields();
    $individual_fields =[];
    $data_to_save=[];

    if ($service_areas_context['landscape_images'] === "1") {
           $template_fields += get_term_landscape_fields();
        }
        if (($service_areas_context['portrait_images']) === "1") {
            $template_fields += get_term_portrait_fields();
        }
   $submitted_data = $_POST;
   $repeater_fields_detected = [];
 foreach ($_POST as $key => $value) {
    if (strpos($key, '_row_count') !== false) {
        $repeater_field = str_replace('_row_count', '', $key);
        
        if (isset($_POST["{$repeater_field}_end"])) {
            $repeater_fields_detected[$repeater_field] = true;
        }
    }
}
foreach ($repeater_fields_detected as $repeater_field => $detected) {
    dibraco_save_repeater_fields($repeater_field, $repeater_fields_detected, 'term');
}
   $storage_keys = dibraco_extract_nested_arrays_test($template_fields);
   foreach ($storage_keys as $container_name => $field_name) {
         if (!is_array($field_name)) {
             $individual_fields[$field_name]= $_POST[$field_name];
        }   
        if (is_array($field_name)){
             foreach ($field_name as $field_name => $field_value){
                $data_to_save[$container_name][$field_name] = $_POST[$field_name];
            }
            update_term_meta($term_id, $container_name, $data_to_save);
        }
    }
     
    foreach ($individual_fields as $field_name => $value) {
        update_term_meta($term_id, $field_name, $value);
    }
    $city = $individual_fields['city'];
    $state = $individual_fields['state'];
    $latitude = $individual_fields['latitude'];
    $longitude =  $individual_fields['longitude'];
    if (($latitude ==='' && $longitude === '') && ($city!=='' && $state !=='')) {
        $geo = get_lat_long_from_osm_2('', $city, $state, '');
        if ($geo) {
           $latitude = $geo['lat'];
           $longitude  = $geo['long'];
         if (isset($geo['boundingbox'])) {
                $polygon_json = json_encode($geo['boundingbox']);
                update_term_meta($term_id,'bounding_box', $polygon_json);
            }
        }
    update_term_meta($term_id, 'latitude', $latitude);
    update_term_meta($term_id, 'longitude', $longitude);
    }
     $status = get_option('locations_areas_status');
     if ($status ==='both'){
        $new_area_parent_term = $submitted_data['area_parent_location_term'];
        $current_area_parent_term = get_term_meta($term_id, 'area_parent_location_term', true);
     }
   
    $related_type_contexts = $service_areas_context['related_type_contexts'];
    if (!empty($related_type_contexts)) {
        foreach ($related_type_contexts as $type_context_data) {
            $meta_key_to_save = "related_type_{$type_context_data['type_name']}";
            $current_meta_data = get_term_meta($term_id, $meta_key_to_save, true);
            foreach ($submitted_data[$meta_key_to_save] as $type_term_id => $term_posts_from_submission) {
                if ($type_context_data['post_per_term'] === '1') {
                    $current_meta_data[$type_term_id]['related_post_title'] = $term_posts_from_submission['related_post_title'];
                } else {
                    foreach ($term_posts_from_submission as $related_post_id => $post_data_from_submission) {
                        $current_meta_data[$type_term_id][$related_post_id]['related_post_title'] = $post_data_from_submission['related_post_title'];
                    }
                }
            }
            update_term_meta($term_id, $meta_key_to_save, $current_meta_data);
        }
    }
    $related_unique_contexts = $service_areas_context['related_unique_contexts'];
    if (!empty($related_unique_contexts)) {
        foreach ($related_unique_contexts as $unique_context_data) {
            $meta_key_to_save_unique = "related_unique_{$unique_context_data['unique_name']}";
            $current_unique_meta_data = get_term_meta($term_id, $meta_key_to_save_unique, true);
            foreach ($submitted_data[$meta_key_to_save_unique] as $post_id => $post_data_from_submission) {
                $current_unique_meta_data[$post_id]['related_post_title'] = $post_data_from_submission['related_post_title'];
            }

            update_term_meta($term_id, $meta_key_to_save_unique, $current_unique_meta_data);
        }
  }
}