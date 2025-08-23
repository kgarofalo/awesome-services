<?php

function display_service_area_term_fields($term, $service_areas_tax, $area_connector_context) {
    $current_service_area_term_id = $term->term_id;
    $current_service_area_term_name = $term->name;
    $city = get_term_meta($current_service_area_term_id, 'city', true) ?: $current_service_area_term_name;
    $state = get_term_meta($current_service_area_term_id, 'state', true);
    $latitude =  get_term_meta($current_service_area_term_id, 'latitude', true);
    $longitude =  get_term_meta($current_service_area_term_id, 'longitude', true);
    if (empty($state)) {
        $status = get_option('locations_areas_status');
        if ($status === 'both') {
            $area_parent_location_term = get_term_meta($current_service_area_term_id, 'area_parent_location_term', true);
            if (!empty($area_parent_location_term)) {
                $state = get_term_meta($area_parent_location_term, 'state', true);
            }
        }
    }
    if (empty($state)) {
        $state = get_option('company_info')['state'];
    }
    $service_area_link_url = get_term_meta($current_service_area_term_id, 'service_area_link_url', true) ?? '';
  $service_area_fields = get_service_area_term_fields();
$service_area_fields['city']['value'] = $city;
$service_area_fields['state']['value'] = $state;
$service_area_fields['latitude']['value'] = $latitude;
$service_area_fields['longitude']['value'] = $longitude;
?>
<table class="striped widefat fixed term-address-details-wrap">
<?php wp_nonce_field('save_service_area_term', 'custom_fields_nonce'); ?>
    <tr> <td> <div style="display: flex; flex-wrap: wrap;">
                <?php foreach ($service_area_fields as $field_name => $field_config) {
                 echo FormHelper::generateField($field_name, $field_config);  }?>
            </div> <p class="description">Enter City, State, Latitude, and Longitude for this Service Area. Latitude/Longitude can be <a href="#" class="dibraco-fetch-geo-from-city-state" style="cursor:pointer;">fetched from City/State</a> if left blank and City/State are filled.</p>
            <p style="font-weight: bold; margin-bottom: 5px;">Service Area Connector URL:</p>
            <input type="text" value="<?= $service_area_link_url ?>" class="dibraco-text" readonly style="width: 100%; box-sizing: border-box; background-color:lightgrey;">
        </td> </tr></table>
<?php    
$related_unique_contexts = $area_connector_context['related_unique_contexts'];
if (!empty($related_unique_contexts)){
render_related_unique_context_tables($related_unique_contexts, $current_service_area_term_id);
}
$related_type_contexts = $area_connector_context['related_type_contexts'];
if (!empty($related_type_contexts)){
render_related_type_context_tables($related_type_contexts, $current_service_area_term_id);
}
}

function handle_save_service_area_term_related_types($term_id, $submitted_data, $area_connector_context) {
if (!dibraco_verify_post_save_request('custom_fields_nonce', 'save_service_area_term')) {return;}
    $city = $submitted_data['city'] ?? '';
    $state = $submitted_data['state'] ?? '';
    $latitude = $submitted_data['latitude'] ?? '';
    $longitude = $submitted_data['longitude'] ?? '';
    update_term_meta($term_id, 'city', $city);
    update_term_meta($term_id, 'state', $state);
    $service_area_taxonomy = $area_connector_context['taxonomy'];
    $service_area_post_type = $area_connector_context['post_type'];
    if (($latitude ==='' && $longitude === '') && ($city!=='' && $state !=='')) {
        $lat_long = get_lat_long_from_osm_2('', $city, $state, '');
        if ($lat_long && isset($lat_long['lat']) && isset($lat_long['long'])) {
            $latitude = $lat_long['lat'];
            $longitude = $lat_long['long'];
        }
    update_term_meta($term_id, 'latitude', $latitude);
    update_term_meta($term_id, 'longitude', $longitude);
    }
   
     $status = get_option('locations_areas_status');
     if ($status ==='both'){
        $new_area_parent_term = $submitted_data['area_parent_location_term'];
        $current_area_parent_term = get_term_meta($term_id, 'area_parent_location_term', true);
        update_term_meta($term_id, 'area_parent_location_term', $new_area_parent_term);
     }
    $related_type_contexts = $area_connector_context['related_type_contexts'];
    if ($related_type_contexts !==[]){
    foreach ($related_type_contexts as $type_context_data) {
    $meta_key_to_save = "related_type_{$type_context_data['type_name']}";
    $post_per_term = $type_context_data['post_per_term'];
    $related_connector_count = $type_context_data['related_connector_count'];
    $current_meta_data = get_term_meta($term_id, $meta_key_to_save, true);
    if (!empty($current_meta_data)){
    $fall_back_update_flag = false; 
    if ($related_connector_count === 2){
    if (($current_area_parent_term !== $new_area_parent_term) && ($post_per_term === "1") && $new_area_parent_term !== '') {
        $fall_back_update_flag = true;
        $location_term_meta_key_data = get_term_meta($new_area_parent_term, $meta_key_to_save, true);
    }}
        if ($post_per_term === "1") {
            foreach ($submitted_data[$meta_key_to_save] as $type_term_id => $term_data_from_submission) {
                $current_meta_data[$type_term_id]['related_post_title'] = $term_data_from_submission['related_post_title'];
            if ($fall_back_update_flag === true) {
                    $new_fallback_url = $location_term_meta_key_data[$type_term_id]['related_post_url'];
                    $current_meta_data[$type_term_id]['fallback_url'] = $new_fallback_url;
                }
            }
        } else {
            foreach ($submitted_data[$meta_key_to_save] as $type_term_id => $term_posts_from_submission) {
                foreach ($term_posts_from_submission as $related_post_id => $post_data_from_submission) {
                    $current_meta_data[$type_term_id][$related_post_id]['related_post_title'] = $post_data_from_submission['related_post_title'];
                }
            }
        }
        update_term_meta($term_id, $meta_key_to_save, $current_meta_data);
    }
    }
    }
    $related_unique_contexts = $area_connector_context['related_unique_contexts'];
    if (!empty($related_unique_contexts)){
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
