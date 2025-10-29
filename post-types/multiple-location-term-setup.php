<?php

function render_location_meta_box($term_or_post, $locations_taxonomy) {
    $connector_contexts = get_option('enabled_connector_contexts');
    $locations_context= $connector_contexts['locations'];
    $template_fields =  initialize_dafields('location_');
    if ($term_or_post instanceof WP_Post){
        $post_id = $term_or_post->ID;
        $current_location_term_id = dibraco_get_current_term_id_for_post($post_id, $locations_taxonomy);
        $all_values = get_term_meta($current_location_term_id);
        $all_values = array_map('maybe_unserialize', array_map('current', $all_values));
    }
    elseif (!$term_or_post instanceof WP_Post){
        $post_id = null;
        $location_term = $term_or_post;
        $current_location_term_id = (int)$location_term->term_id;
        $all_values = get_term_meta($current_location_term_id);
        $all_values = array_map('maybe_unserialize', array_map('current', $all_values));
        $current_location_term_name = $location_term->name;
        $current_location_post_id = $all_values['location_post_id']??'';
        $current_location_slug = $location_term->slug;
        if (!empty($current_location_post_id)){
            $current_location_post_id = (int)$current_location_post_id;
            $about_enabled = $locations_context['about_section'];
            $about_location_value ='';
            if ($about_enabled === '1') {
                $about_location_value = get_post_meta($current_location_post_id, 'da_about_blurb', true);
                }
            elseif ($locations_context['dibraco_banner']==="1"){
                 $about_location_value = get_post_meta($current_location_post_id, 'da_banner_description', true);
                }
            $all_values['about_location'] = $about_location_value;
            }
    }
   if (!$post_id) {
       $status = get_option('locations_areas_status');
        if ($status === 'both') {   
            $area_connector_tax = $connector_contexts['service_areas']['taxonomy'];
            register_term_meta($locations_taxonomy, 'associated_act_terms', ['type' => 'array', 'single' => false, 'show_in_rest' => ['schema' => ['type' => 'array', 'items' => ['type' => 'integer']]]]);
            render_area_connection_checkboxes_for_location_terms($location_term, $locations_taxonomy);
        }
        $related_unique_contexts = $locations_context['related_unique_contexts'];
        if (!empty($related_unique_contexts)) {
            render_context_tables($related_unique_contexts, 'unique', $current_location_term_id);
        }
        $related_type_contexts = $locations_context['related_type_contexts'];
        if (!empty($related_type_contexts)) {
            render_context_tables($related_type_contexts, 'type', $current_location_term_id);
        }
    }
$storage_keys= dibraco_extract_nested_arrays_test($template_fields);
   error_log(print_r($storage_keys, true)); 

//echo do_shortcode("[dibraco_location_map loc='$current_location_slug']");
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
        FormHelper::generateField('who_cares', ['type' => 'valueinjector', 'meta_array' => $mapped_values]);
        echo FormHelper::generateVisualSection('location-main-form', ['fields' => $template_fields]);
        FormHelper::generateField('who_cares', ['type' => 'injectionend']);
}

function handle_save_location_term_meta($location_term_id, $taxonomy) {
    $locations_context =get_option('enabled_connector_contexts')['locations'];
    $about_section = $locations_context['about_section'];
    $dibraco_banner = $locations_context['dibraco_banner'];
    $template_fields = initialize_dafields('location_'); 
    $storage_keys= dibraco_extract_nested_arrays_test($template_fields);
    
    $data_to_save = [];
    $individual_fields =[];
   foreach ($storage_keys as $container_name => $field_name) {
         if (!is_array($field_name)) {
             $individual_fields[$field_name]= $_POST[$field_name];
         }
         if (is_array($field_name)){
             foreach ($field_name as $field_name => $field_value){
                 $data_to_save[$container_name][$field_name] = $_POST[$field_name];
               if ($container_name === 'hours_of_operation') {
                 $hours_of_operation_data = $data_to_save[$container_name];
                 $day_map = get_dibraco_day_map();
                    if ($hours_of_operation_data['open_247'] === '1') {
                    foreach ($day_map as $full_day_name => $abbr) {
                       $hours_of_operation_data["{$abbr}_open_hour"] = '';
                       $hours_of_operation_data["{$abbr}_close_hour"] = '';
                       $hours_of_operation_data["open_{$full_day_name}"] = '1';
                    }
                } else if ($hours_of_operation_data['open_247'] !== '1') {
                        foreach ($day_map as $full_day_name => $abbr) {
                            if ($hours_of_operation_data["open_{$full_day_name}"] !== '1') {
                                $hours_of_operation_data["{$abbr}_open_hour"] = '';
                                $hours_of_operation_data["{$abbr}_close_hour"] = '';
                                }
                            }
                        }
                    }
                }
                update_term_meta($location_term_id, $container_name, $data_to_save[$container_name] );
                 unset($data_to_save[$container_name]);
            }
    $linked_post_id = get_term_meta($location_term_id, 'location_post_id', true);
    if (!empty($linked_post_id)) {
        $linked_post_id = (int)$linked_post_id;
        
            if ($about_section ==="1"){       
                update_post_meta($linked_post_id, 'da_about_blurb', $individual_fields['about_location']);
            } elseif($dibraco_banner ==="1"){
                update_post_meta($linked_post_id, 'da_banner_description', $individual_fields['about_location']);
            }
        
        }
    unset($individual_fields['about_location']); 

   
        foreach ($individual_fields as $field_name => $value) {
            update_term_meta($location_term_id, $field_name, $value);
        }
    }
    $place_id = $individual_fields['place_id'] ?? '';
    if ($place_id!==''){
        $gmb_url = 'https://www.google.com/maps/place/?q=place_id:' . urlencode($place_id);
        update_term_meta($location_term_id, 'gmb_map_link', $gmb_url);
    }
    
    $street_address = $individual_fields['street_address'];
    $city = $individual_fields['city'];
    $state = $individual_fields['state'];
    $zipcode = $individual_fields['zipcode'];
    $location_name = $individual_fields['location_name'];
    $street_address_2 = $individual_fields['street_address_2'];

    $coords_query ='';
    if (!empty($city) && !empty($state)) {
        $geo = get_lat_long_from_osm_2($street_address, $city, $state, $zipcode);
        if ($geo) {
            $individual_fields['latitude'] = $geo['lat'];
            $individual_fields['longitude'] = $geo['long'];
            if (isset($geo['boundingbox'])) {
                $polygon_json = json_encode($geo['boundingbox']);
                update_term_meta($location_term_id,'bounding_box', $polygon_json);
            }
            $coords_query = $geo['lat'] . ',' . $geo['long'];
        }
    }
    if (!empty($street_address_2)){
        $street_address = "{$street_address} {$street_address_2}";
    }
    $location_name = $individual_fields['location_name'];
    $address_query = implode(', ', array_filter([$location_name, $street_address, $city, $state, $zipcode]));
    $base_url = 'https://maps.google.com/maps?q=' . urlencode($address_query);
    $normal_url = $base_url . '&z=14';
    update_term_meta($location_term_id, 'normal_map', $normal_url);
    $extracted_place_id = '';
        $embed_temp_frame = $normal_url . '&output=embed';
        $response = wp_remote_get($embed_temp_frame);
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);

    if (preg_match('/"(ChIJ[a-zA-Z0-9_-]+)"/', $body, $matches)) {
                $extracted_place_id = $matches[1];
                update_term_meta($location_term_id, 'place_id', $extracted_place_id);
            }
        }
    
    if (empty($place_id)){
        update_term_meta($location_term_id, 'gmb_map_link', $normal_url);
    }
    if ($coords_query !== '') {
        $streetview_params = ['q'=>$address_query,'cbll'=>$coords_query,'cbp'=>'12,235,,0,5','layer'=>'c','output'=>'svembed'];
        $street_url = 'https://maps.google.com/maps?' . http_build_query($streetview_params);
    }
    update_term_meta($location_term_id, 'street_map', $street_url);
    $location_term = get_term($location_term_id);
    $location_term_name = $location_term->name;
    $kml_content = generate_custom_kml_file([$location_term_id], $location_term_name);
    update_term_meta($location_term_id, 'kml_content', $kml_content);
}


function service_areas_shortcode($atts) {
    $atts = shortcode_atts(['mode' => 'comma'], $atts);
    $status = get_option('locations_areas_status');
    $mode = $atts['mode'];
    if ($status === 'none') {
        return '';
    }
    $enabled = get_option('enabled_connector_contexts');
    switch ($status) {
        case 'multi_locations':
            $location_tax = $enabled['locations']['taxonomy'];
            return render_all_terms($location_tax, 'location_link_url', $mode);
        case 'multi_areas':
            $service_area_tax = $enabled['service_areas']['taxonomy'];
            return render_all_terms($service_area_tax, 'service_area_link_url', $mode);
        case 'both':
             $location_tax = $enabled['locations']['taxonomy'];
             $service_area_tax = $enabled['service_areas']['taxonomy'];
            return render_combined_mode($location_tax, $service_area_tax, $mode);
        default:
            return '';
    }
}

function render_all_terms($taxonomy, $meta_key, $mode) {
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    $links = [];
    foreach ($terms as $term) {
        $links[] = generate_connector_term_links($term, $meta_key);
    }
    return format_links_output($links, $mode);
}
function generate_connector_term_links($term, $meta_target) {
    $term_url = get_term_meta($term->term_id, $meta_target, true);
    $term_post_id = get_term_meta($term->term_id, $meta_target . '_post_id', true);
    $current_post_id = get_the_ID();
    if ($current_post_id == $term_post_id) {
        return $term->name; 
    }
    return "<a href='" . $term_url . "'>" . $term->name . "</a>";
}
function render_combined_mode($location_tax, $service_area_tax, $mode) {
    $post_id = get_the_ID();
    $location_term_id = da_get_location_term_or_default($post_id);
    if ($location_term_id) {
        return render_location_specific($post_id, $location_term_id, $location_tax, $service_area_tax, $mode);
    }
    return render_global_combined($post_id, $location_tax, $service_area_tax, $mode);
}
function render_location_specific($post_id, $location_term_id, $location_tax, $service_area_tax, $mode) {
    $location_term_id = (int)$location_term_id;
    $location_term_name = get_term($location_term_id)->name;
    $assigned_act_term_ids = get_term_meta($location_term_id, 'associated_act_terms', true);
    if (empty($assigned_act_term_ids)) {
        return render_global_combined($post_id, $location_tax, $service_area_tax, $mode);
    }
    $area_term_id = dibraco_get_current_term_id_for_post($post_id, $service_area_tax);
    if (!empty($area_term_id)) {
        $area_term_id = (int)$area_term_id;
    }
    $service_area_links = [];
    foreach ($assigned_act_term_ids as $assigned_act_term_id) {
        $assigned_act_term_id = (int)$assigned_act_term_id;
        $term_name = get_term($assigned_act_term_id)->name;
        if ($assigned_act_term_id === $area_term_id) {
            $service_area_links[] = $term_name; 
        } else {
            $service_area_post_url = get_term_meta($assigned_act_term_id, 'service_area_link_url', true);
            if (empty($service_area_post_url)) {
                $service_area_links[] = $term_name; 
            } else{
                $service_area_links[] = "<a href='{$service_area_post_url}'>{$term_name}</a>";
            }
        }
    }
    
    $location_link_url =  get_term_meta($location_term_id, 'location_link_url', true);
    $location_link = "<a href='{$location_link_url}'>{$location_term_name}</a>";
    $final_links = [];
    shuffle($service_area_links);
    if ($mode === 'list') {
        $final_links = array_merge([$location_link], $service_area_links);
    } else {
        $final_links = array_merge($service_area_links, [$location_link]);
    }
    return format_links_output($final_links, $mode);
}

function render_global_combined($post_id, $location_tax, $service_area_tax, $mode) {
    $max_total_items = 20;
    $locations = get_terms(['taxonomy' => $location_tax, 'hide_empty' => true]);
    $location_links = [];
    $location_name_lookup = [];
    foreach ($locations as $location) {
        $location_links[] = generate_connector_term_links($location, 'location_link_url');
        $location_name_lookup[strtolower($location->name)] = true;
    }
    $service_area_limit = $max_total_items - count($location_links);
    $service_area_links = [];
    if ($service_area_limit > 0) {
        $all_service_areas = get_terms(['taxonomy' => $service_area_tax, 'hide_empty' => false]);
        foreach ($all_service_areas as $service_area) {
            if (!isset($location_name_lookup[strtolower($service_area->name)])) {
                $service_area_links[] = generate_connector_term_links($service_area, 'service_area_link_url');
            }
            if (count($service_area_links) >= $service_area_limit) {
                break;
            }
        }
    }
$final_links = [];
shuffle($service_area_links);
    if ($mode === 'list') {
        $final_links = array_merge($location_links, $service_area_links);
    } else {
        $final_links = array_merge($service_area_links, $location_links);
    }
    return format_links_output($final_links, $mode);
}
function format_service_area_sentence($current_area_name, $other_area_links, $location_name, $location_link, $is_service_area_page = false) {
    $location_fronts = [
        "We provide service in {location_name}",
        "Our service area includes {location_name}",
        "From our {location_name} office, we serve",
        "We proudly serve {location_name}"
    ];
    $service_area_fronts = [
        "We provide service in {current_area_name}",
        "Our services are available in {current_area_name}",
        "We proudly serve the {current_area_name} area",
        "Our team regularly works in {current_area_name}"
    ];
    $ends = [
        "and other areas we serve from our {location_link} location.",
        "as well as many nearby communities from our location in {location_link}.",
        "plus surrounding areas, all serviced by our {location_link} location.",
        "and the surrounding region from our central {location_link} location."
    ];

    shuffle($other_area_links);
    $other_area_links = array_slice($other_area_links, 0, 10);
    $other_areas_string = implode(", ", $other_area_links);

    $output = '';
    if ($is_service_area_page) {
        $front_phrase = $service_area_fronts[array_rand($service_area_fronts)];
        $output = str_replace('{current_area_name}', $current_area_name, $front_phrase);

    } else { 
        $front_phrase = $location_fronts[array_rand($location_fronts)];
        $output = str_replace('{location_name}', $location_name, $front_phrase);
    }
    
    if (!empty($other_areas_string)) {
        $output .= ", " . $other_areas_string;
    }

    $end_phrase = $ends[array_rand($ends)];
    $output .= " " . str_replace('{location_link}', $location_link, $end_phrase);

    return $output;
}
function format_links_output($links, $mode = 'comma') {
    if ($mode === 'list') {
        $html = '<div class="marketcitylist"><ul>';
        foreach ($links as $link) {
            $html .= '<li>' . $link . '</li>';
        }
        return $html . '</ul></div>';
    }
    $count = count($links);
    if ($count === 0) {return ''; }
    if ($count === 1) {return $links[0]; }
    if ($count === 2) {return implode(' and ', $links); }
    $last = array_pop($links);
    return implode(', ', $links) . ", and {$last}";
}
add_shortcode('service_areas', 'service_areas_shortcode');

function da_get_location_term_or_default($post_id, $extra_data = '', $location_slug = '') {
	$status = get_option('locations_areas_status');
	if (!$status || ($status !== 'both' && $status !== 'multi_locations')) {
		return null;
	}
	if (($status) && $status !=='none'){
	    $connector_contexts = get_option('enabled_connector_contexts');
	    if ($status ==='multi-locations' || $status ==='both'){
	        $location_tax = $connector_contexts['locations']['taxonomy'];
	    }
	    if($status ==='both'){
            $service_area_tax = $connector_contexts['service_areas']['taxonomy'];
	    }
	}
	$company_info = get_option('company_info');
    $show_address_on_org = $company_info['show_address_on_org'];
	if ($location_slug && ($status ==='multi-locations' || $status ==='both')){
		$term = get_term_by('slug', $location_slug, $location_tax);
		if ($term && !is_wp_error($term)) {
		    $location_term_id = $term->term_id;
			 if($extra_data ==="1"){
			     return ['location_term_id' => $location_term_id ];
			 }
			 else return $location_term_id;
		}
	}
    if ($status ==='both') {
	    $area_term_id = dibraco_get_current_term_id_for_post($post_id, $service_area_tax);
	    if ($area_term_id !=='') {
	    	$location_term_id = get_term_meta($area_term_id, 'area_parent_location_term', true);
	    	if ($location_term_id !=='') {
	    		 if($extra_data ==="1"){
	    		     return ['area_term_id' => $area_term_id, 'location_term_id' => $location_term_id];
	    	    }
		    else return $location_term_id;
	        }
	    }
	}
	$location_term_id = dibraco_get_current_term_id_for_post($post_id, $location_tax);
    if ($location_term_id !=='') {
        if($extra_data ==="1"){
            return  ['location_term_id' => $location_term_id ];
        }
         else return $location_term_id;
    }

	if ($show_address_on_org ==="0"){
        $default_term_id = $company_info['default_term'];
        return $default_term_id;
	}
	return null;
}
function register_dynamic_social_media_link_shortcodes() {
   $social_fields = get_social_media_fields();
   $social_media_fields = array_keys($social_fields);
   $status = get_option('locations_areas_status');
   $post_id = get_the_ID();
   $location_term_id ='';
   if ($status === 'both'){
   		$connector_contexts = get_option('enabled_connector_contexts');
       	$location_tax = $connector_contexts['locations']['taxonomy'];
        $service_area_tax = $connector_contexts['service_areas']['taxonomy'];
    	$area_term_id = dibraco_get_current_term_id_for_post($post_id, $service_area_tax);
        if ($area_term_id !=='') {
		    $location_term_id = get_term_meta($area_term_id, 'area_parent_location_term', true);
        }
        if ($location_term_id ===''){
        	$location_term_id = dibraco_get_current_term_id_for_post($post_id, $location_tax);
        }
   }
 	if ($status === 'multi_locations'){
		$connector_contexts = get_option('enabled_connector_contexts');
    	$location_tax = $connector_contexts['locations']['taxonomy'];
    	$location_term_id = dibraco_get_current_term_id_for_post($post_id, $location_tax);
    }
    foreach ($social_media_fields as $social_media_field) {
        add_shortcode($social_media_field . '_link', function($atts) use ($social_media_field, $location_term_id) {
            $atts = shortcode_atts(['loc' => ''], $atts);
            $location_slug = $atts['loc'];
            if ($location_slug !==''){
        	    $location_tax = $connector_contexts['locations']['taxonomy']??'';
                $term = get_term_by('slug', $location_slug, $location_tax);
	    	    if ($term && !is_wp_error($term)) {
		         $location_term_id = $term->term_id;
	    	    }
            }
            $link = ''; 
            if ($location_term_id !=='') {
                $term_social_media_data = get_term_meta($location_term_id, 'social_media', true);
               if (!empty($term_social_media_fields)){
                    $link = $term_social_media_data[$social_media_field]; 
                }
            }
            if ($link ==='') {
                $comapany_social_media_fields = get_option('company_info')['social_media'];
                $link = $comapany_social_media_fields[$social_media_field];
            }
        return $link;
    });
}
}
add_action('init', 'register_dynamic_social_media_link_shortcodes');


function da_map_embed_shortcode($atts) {
 return 'shortcode_deprecated';
}
add_shortcode('da_map_embed', 'da_map_embed_shortcode');


function da_reviews_shortcode($atts) {
    $atts = shortcode_atts(['extra_data'=> '', 'loc' => ''], $atts);
    $location_slug = $atts['loc'];
    $extra_data = $atts['extra_data'];
    $post_id = get_the_ID();    
    $location_term_id = da_get_location_term_or_default($post_id, $location_slug);
        if ($location_term_id) {
            $term_reviews = get_term_meta($location_term_id, 'location_reviews_shortcode', true);
            return $term_reviews;
        }
     return get_option('company_info')['company_reviews_shortcode'];
}
add_shortcode('da_reviews', 'da_reviews_shortcode');
function da_get_grouped_opening_hours($hours) {
    $days_map = get_dibraco_day_map();
    $logic_map = [];
    foreach ($days_map as $day_full => $day_short) {
        if ($hours['open_' . $day_full] === '1') {
            $opens = da_format_time_ampm($hours[$day_short . '_open_hour']);
            $closes = da_format_time_ampm($hours[$day_short . '_close_hour']);
            $logic_map[ucfirst($day_short)] = "{$opens} - {$closes}";
        } else {
            $logic_map[ucfirst($day_short)] = 'Closed';
        }
    }
    $grouped_ranges = [];
    $current_range_key = null;
    foreach ($logic_map as $day => $range) {
        if ($range !== $current_range_key) {
            $grouped_ranges[] = ['days' => [$day], 'range' => $range];
            $current_range_key = $range;
        } else {
            $last_key = count($grouped_ranges) - 1;
            $grouped_ranges[$last_key]['days'][] = $day;
        }
    }
    return $grouped_ranges;
}
function da_display_opening_hours($atts) {
    $atts = shortcode_atts(['extra_data'=> '', 'loc' => '', 'output' => 'ranges'], $atts);
    $location_slug = $atts['loc'];
    $extra_data = $atts['extra_data'];
    $post_id = get_the_ID();
    $location_term_id = da_get_location_term_or_default($post_id, $extra_data, $location_slug);

    if ($location_term_id) {
        $hours = get_term_meta($location_term_id, 'hours_of_operation', true);
    } else {
        $hours = get_option('company_info')['hours_of_operation'];
    }

    if (empty($hours)) { return ''; }

    if ($hours['open_247'] === "1") {
        return 'Open 24/7';
    }

    $output_lines = [];
    if (strtolower($atts['output']) === 'list') {
        $days_map = get_dibraco_day_map();
        foreach ($days_map as $day_full => $day_short) {
            $day_label = ucfirst($day_short);
            if ($hours['open_' . $day_full] === '1') {
                $opens = da_format_time_ampm($hours[$day_short . '_open_hour']);
                $closes = da_format_time_ampm($hours[$day_short . '_close_hour']);
                $output_lines[] = "<strong>{$day_label}</strong><br>{$opens} - {$closes}";
            } else {
                $output_lines[] = "<strong>{$day_label}:</strong> Closed";
            }
        }
    } else { 
        $grouped_hours = da_get_grouped_opening_hours($hours);
        foreach ($grouped_hours as $group) {
            $first_day = reset($group['days']);
            $day_label = (count($group['days']) > 1) ? $first_day . ' - ' . end($group['days']) : $first_day;
            
            if ($group['range'] === 'Closed') {
                $output_lines[] = "<strong>{$day_label}:</strong> {$group['range']}";
            } else {
                $output_lines[] = "<strong>{$day_label}</strong><br>{$group['range']}";
            }
        }
    }
    return implode('<br>', $output_lines);
}
function da_format_time_ampm($time_24) {
    if (empty($time_24)) return '';
    $timestamp = strtotime($time_24);
    return $timestamp ? date('g:i A', $timestamp) : 'N/A';
}
add_shortcode('da_opening_hours', 'da_display_opening_hours');

function da_logo_url_shortcode($atts) {
    $atts = shortcode_atts(['extra_data'=> '', 'loc' => ''], $atts);
	$location_slug = $atts['loc'];
    $extra_data = $atts['extra_data'];
	$post_id = get_the_ID();
	$location_term_id = da_get_location_term_or_default($post_id, $extra_data, $location_slug);
	if ($location_term_id) {
		$location_logo_id = get_term_meta( $location_term_id, 'location_logo', true);
		if ($location_logo_id !=='') {
			$logo_url = wp_get_attachment_url((int)$location_logo_id);
			if (!empty($logo_url)) {
			return $logo_url;
		}
	}
	}
	$company_logo = get_option('company_info')['company_logo'];
	if ($company_logo !==''){
	return wp_get_attachment_url((int)$company_logo);
	}
	return;
}
add_shortcode('da_logo_url', 'da_logo_url_shortcode');

function da_address_shortcode($atts) {
    $atts = shortcode_atts(['extra_data'=> '', 'loc' => '', 'country' => ''], $atts);
    $location_slug = $atts['loc'];
    $extra_data = $atts['extra_data'];
    $post_id = get_the_ID();
    $address_keys = ['street_address', 'street_address_2', 'city', 'state', 'zipcode', 'addy_country'];
    if ($atts['country'] !== 'yes'){
     $address_keys = ['street_address', 'street_address_2', 'city', 'state', 'zipcode'];
    }
    $location_term_id = da_get_location_term_or_default($post_id, $extra_data, $location_slug);
    $address_parts=[];
    if ($location_term_id) {
        foreach ($address_keys as $address_field) {
            $address_parts[$address_field] = get_term_meta($location_term_id, $address_field, true);
        }
    }
    if (!$location_term_id) {
        $company_info = get_option('company_info');
        foreach ($address_keys as $address_field) {
            $address_parts[$address_field] = $company_info[$address_field];
        }
    }
    $street_address = $address_parts['street_address'];
    $street_address_2 = $address_parts['street_address_2'];
    $city = $address_parts['city'];
    $state = $address_parts['state'];
    $zipcode = $address_parts['zipcode'];
    $country = $address_parts['addy_country']??'';
    $address = "{$street_address}";
    if (!empty($street_address_2) && strlen($street_address_2) < 10) {
        $address = "{$street_address} {$street_address_2}";
    } elseif (!empty($street_address_2) && strlen($street_address_2) > 10){
        $address .= "<br>{$street_address_2}";
    }
    $address .= "<br>{$city}, {$state} {$zipcode} {$country}";
    return $address;
}
add_shortcode('da_address', 'da_address_shortcode');

function convert_vanity_number($input) {
    if (!preg_match('/[A-Z]/i', $input)) {
        return $input;
    }
    $map = [
        'A' => '2', 'B' => '2', 'C' => '2', 'D' => '3', 'E' => '3', 'F' => '3',
        'G' => '4', 'H' => '4', 'I' => '4', 'J' => '5', 'K' => '5', 'L' => '5',
        'M' => '6', 'N' => '6', 'O' => '6', 'P' => '7', 'Q' => '7', 'R' => '7', 'S' => '7',
        'T' => '8', 'U' => '8', 'V' => '8', 'W' => '9', 'X' => '9', 'Y' => '9', 'Z' => '9'
    ];
    $input = strtoupper($input);
    $output = '';
    $letters_converted = 0;
    for ($i = 0; $i < strlen($input); $i++) {
        $char = $input[$i];
        if (isset($map[$char])) {
            if ($letters_converted < 7) {
                $output .= $map[$char];
                $letters_converted++;
            } else {break;}
        } else {
         $output .= $char;
    }
    return $output;
}
}
function parse_us_telephone_number($telephone) {
    $num = preg_replace('/[^0-9]/', '', $telephone);
    if (strlen($num) === 10) { $num = '1' . $num; }
        if (strlen($num) !== 11 || substr($num, 0, 1) !== '1') {return null;}
        $area_code = substr($num, 1, 3);
        $toll_free_codes = ['800', '888', '877', '866', '855', '844', '833'];
        return ['country' => '1', 'area' => $area_code, 'prefix' => substr($num, 4, 3), 'line' => substr($num, 7), 'is_toll_free' => in_array($area_code, $toll_free_codes, true)];
}
if (!function_exists('format_telephone_for_display')) {
    function format_telephone_for_display($telephone) {
        return esc_html($telephone);
    }
}

if (!function_exists('format_telephone_for_link')) {
    function format_telephone_for_link($telephone) {
        $telephone = convert_vanity_number($telephone);
        $parts = parse_us_telephone_number($telephone);
        if (!$parts) return 'tel:' . preg_replace('/[^0-9+]/', '', $telephone);
        return "tel:+{$parts['country']}{$parts['area']}{$parts['prefix']}{$parts['line']}";
    }
}

if (!function_exists('display_telephone_field')) {
function display_telephone_field($atts) {
        $atts = shortcode_atts(['type' => 'telephonelink', 'loc' => ''], $atts);
        $locaton_slug = $atts['loc'];
        $extra_data = "1";
        $post_id = get_the_id();
        $telephone = '';
        $location_data = da_get_location_term_or_default($post_id, $extra_data, $locaton_slug);
        $location_term_id = $location_data['location_term_id'] ?? null;
        $area_term_id = $location_data['area_term_id'] ?? null;
        if ($location_term_id) {
            $telephone = get_term_meta($location_term_id, 'phone_number', true);
        }
        if (empty($telephone)) {
            $telephone = get_option('company_info')['phone_number'];
        }
        if (empty($telephone)) {
            return '';
        }
        switch ($atts['type']) {
            case 'telephoneonly':
                return format_telephone_for_display($telephone);
            case 'telephonelinkonly':
                return esc_url(format_telephone_for_link($telephone));
            default: 
            $display_number = format_telephone_for_display($telephone);
            $location_name = 'main_line';
            if ($location_term_id) {
                $location_name = get_term($location_term_id)->slug;
            }
            $area_data_attr = '';
            if ($area_term_id) {
                $area_name = get_term($area_term_id)->slug;
                $area_data_attr = "data-area='{$area_name}'";
            }
            $telephone_link = esc_url(format_telephone_for_link($telephone));
            return "<a href='{$telephone_link}' data-analytics-event='phone_click' data-location='{$location_name}' {$area_data_attr}>{$display_number}</a>";
    }
}
foreach (['telephonelink', 'telephoneonly', 'telephonelinkonly'] as $shortcode_name) {
        add_shortcode($shortcode_name, function (array $atts = []) use ($shortcode_name) {
            return display_telephone_field(array_merge($atts, ['type' => $shortcode_name]));
        });
    }
}

function display_email_field($atts) {
    $atts = shortcode_atts(['extra_data'=> '', 'loc' => ''], $atts);
    $location_slug = $atts['loc'];
    $extra_data = $atts['extra_data'];
    $post_id = get_the_ID();     
    $location_term_id = da_get_location_term_or_default($post_id, $extra_data, $location_slug);
    $email_field = '';
    if ($location_term_id) {
        $email_field = get_term_meta($location_term_id, 'email_address', true);
    }
    if (empty($email_field)) {
        $email_field = get_option('company_info')['email_address'];
    }
    if (empty($email_field)) {
        return '';
    }
    if ($atts['type'] === 'link') {
        return sprintf(
            '<a href="mailto:%1$s" data-analytics-event="email_click" data-event-label="%1$s">%2$s</a>',
            esc_attr($email_field),
            esc_html($email_field)
        );
    } else {
        return esc_html($email_field);
    }
}
add_shortcode('da_email', 'display_email_field');
