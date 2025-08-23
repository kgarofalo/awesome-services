<?php

function render_location_meta_box($object) {
    $locations_context = get_option('enabled_connector_contexts')['locations'];
    $locations_taxonomy = $locations_context['taxonomy'];
    $location_schema = $locations_context['schema'];
    $current_location_term_id = '';
    $current_location_term_name = '';
    $post_id = get_the_id() ?? null;

    if ($post_id) {
        $current_location_term_id = dibraco_get_current_term_id_for_post($post_id, $locations_taxonomy);
        if ($current_location_term_id !== '') {
            $current_location_term_name = get_term($current_location_term_id)->name;
        }
    } elseif (!$post_id) {
        $term = $object;
        $current_location_term_id = $term->term_id;
        $current_location_term_name = $term->name;
    }


    $logo_value = get_term_meta($current_location_term_id, 'location_logo', true);
    if (empty($logo_value)) {
        $company_info = get_option('company_info', []);
        $logo_value = $company_info['company_logo'];
    }

    $schema_value = get_term_meta($current_location_term_id, 'schema', true);
    if (empty($schema_value)) {
        $schema_value = $location_schema;
    }

    $manager_value = get_term_meta($current_location_term_id, 'location_manager', true);
    $enabled_context_names = get_option('enabled_context_names', []);
    
    // Prepare all section data
    $prefix = 'location_';
    $location_sections = initialize_dafields($prefix);
    $populated_sections = [];

    foreach ($location_sections as $section_key => $section_data) {
        $populated_section = $section_data; // Make a copy to modify.

        if ($section_key === 'hours_of_operation') {
            $keys = get_hours_array_keys();
            $saved_data = get_term_meta($current_location_term_id, 'hours_of_operation', true);
            
            // Loop through the definitive list of keys to populate the fields.
            foreach ($keys as $field_key) {
                if (isset($populated_section['fields'][$field_key])) {
                    $populated_section['fields'][$field_key]['value'] = $saved_data[$field_key];
                }
            }

        } elseif ($section_key === 'social_media') {
            // Get the definitive list of keys for social media.
            $keys = get_option('custom_social_media_keys', []);
            $saved_data = get_term_meta($current_location_term_id, 'social_media', true);

            foreach (array_keys($keys) as $field_key) {
                if (isset($populated_section['fields'][$field_key])) {
                    $populated_section['fields'][$field_key]['value'] = $saved_data[$field_key];
                }
            }
        } else {
            foreach ($section_data['fields'] as $field_key => $field_config) {
                 if (isset($populated_section['fields'][$field_key])) {
                    $populated_section['fields'][$field_key]['value'] = get_term_meta($current_location_term_id, $field_key, true);
                }
            }
        }
        
        $populated_sections[$section_key] = $populated_section;
    }

    ?>
    <div class="location-term-form">
        <div id="image-fields" class="dibraco-section">
            <?php echo FormHelper::generateField('location_logo', ['type' => 'image', 'value' => $logo_value]); ?>
        </div>
        <div class='indi-fields'>
            <?php 
            echo FormHelper::generateField('schema', ['type' => 'select', 'options' => get_company_schema_types(), 'value' => $schema_value]);
            if (in_array('employee', $enabled_context_names)) {
                echo FormHelper::generateField('location_manager', ['type' => 'select', 'options' => get_employee_posts_for_select_options(), 'value' => $manager_value]);
            }
            ?>
        </div>
        
        <?php
        // Now, loop through the prepared data and render the sections.
        foreach ($populated_sections as $section_key => $section_data) {
            echo FormHelper::generateVisualSection($section_key, $section_data);
        }

        // Render relationship tables on term screen only.
        if (!$post_id) {
            $related_unique_contexts = $locations_context['related_unique_contexts'] ?? [];
            if (!empty($related_unique_contexts)) {
                render_context_tables($related_unique_contexts, 'unique', $current_location_term_id);
            }
            $related_type_contexts = $locations_context['related_type_contexts'] ?? [];
            if (!empty($related_type_contexts)) {
                render_context_tables($related_type_contexts, 'type', $current_location_term_id);
            }
        }
        ?>
    </div>
    <?php
}




function handle_save_location_term_meta($term_id, $taxonomy) {

    $tracking_flag = false;
    $submitted_data = $_POST;
    $hours_of_operation_field_keys = get_hours_array_keys();
    $day_map = get_dibraco_day_map();
    $social_media_field_keys = get_option('custom_social_media_keys');
    $meta_to_save = [];
    $hours_of_operation_data = [];
    $social_media_data = [];
    foreach ($submitted_data as $fieldname => $value) {
        if ($fieldname === 'tracking_started') {
            $tracking_flag = true;
            continue;
        }
        if (!$tracking_flag) {
            continue;
        }
        if ($fieldname === 'tracking_finished') {
            break;
        }
        if (in_array($fieldname, $hours_of_operation_field_keys)) {
            $hours_of_operation_data[$fieldname] = $value;
        } elseif (in_array($fieldname, $social_media_field_keys)) { 
            $social_media_data[$fieldname] = $value;
        } else {
        if ($fieldname !== 'tracking_started' && $fieldname !== 'tracking_finished'  && !str_starts_with($fieldname, 'submit')) {
                $meta_to_save[$fieldname] = $value;
            }
        }
    }  
    $linked_post_id = get_term_meta($term_id, 'location_post_id', true);
    if (!empty($linked_post_id)) {
        $linked_post_id = (int)$linked_post_id;
        $blurb_value = $meta_to_save['da_about_blurb']; 
        update_post_meta($linked_post_id, 'da_about_blurb', $blurb_value);
    }
    unset($meta_to_save['da_about_blurb']); 
    if ($hours_of_operation_data['open_247'] === '1') {
        foreach ($day_map as $full => $abbr) {
            $hours_of_operation_data["open_{$full}"] = '1';
            $hours_of_operation_data["{$abbr}_open_hour"] = '';
            $hours_of_operation_data["{$abbr}_close_hour"] = '';
        }
    } else {
        foreach ($day_map as $full => $abbr) {
            if ($hours_of_operation_data["open_{$full}"] !== '1') {
                $hours_of_operation_data["{$abbr}_open_hour"] = '';
                $hours_of_operation_data["{$abbr}_close_hour"] = '';
            }
        }
    }
    update_term_meta($term_id, 'hours_of_operation', $hours_of_operation_data);
    update_term_meta($term_id, 'social_media', $social_media_data);
    foreach ($meta_to_save as $key => $value) {
        update_term_meta($term_id, $key, $value);
    }
    $place_id = $meta_to_save['place_id'];
    if ($place_id!==''){
        $gmb_url = 'https://www.google.com/maps/place/?q=place_id:' . urlencode($place_id);
        update_term_meta($term_id, 'gmb_map_link', $gmb_url);
    }
    $street_address = $meta_to_save['street_address'];
    $city = $meta_to_save['city'];
    $state = $meta_to_save['state'];
    $zipcode = $meta_to_save['zipcode'];
    $coords_query ='';
    if (!empty($city) && !empty($state)) {
        $geo = get_lat_long_from_osm_2($street_address, $city, $state, $zipcode);
        if ($geo && isset($geo['lat']) && isset($geo['long'])) {
            update_term_meta($term_id, 'latitude', $geo['lat']);
            update_term_meta($term_id, 'longitude', $geo['long']);
            $coords_query = $geo['lat'] . ',' . $geo['long'];
        }
    }
    if (!empty( $meta_to_save['street_address_2'])){
        $street_address = "{$street_address} {$meta_to_save['street_address_2']}";
    }
    $address_query = implode(', ', array_filter([$meta_to_save['location_name'], $street_address, $city, $state, $zipcode]));
    $base_url = 'https://maps.google.com/maps?q=' . urlencode($address_query);
    $normal_url = $base_url . '&z=14';
    update_term_meta($term_id, 'normal_map', $normal_url);
    if (empty($place_id)){
        update_term_meta($term_id, 'gmb_map_link', $normal_url);
    }
    if ($coords_query !== '') {
        $streetview_params = ['q'=>$address_query,'cbll'=>$coords_query,'cbp'=>'12,235,,0,5','layer'=>'c','output'=>'svembed'];
        $street_url = 'https://maps.google.com/maps?q=' . http_build_query($streetview_params);
    }
    update_term_meta($term_id, 'street_map', $street_url);
	$location_term = get_term($term_id);
	$kml_content = generate_custom_kml_file([$term_id], esc_html($location_term->name));
    error_log($kml_content);
    update_term_meta($term_id, 'kml_content', $kml_content);
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
    $current_url = get_permalink(get_the_ID());
    if ($term_url && trailingslashit($term_url) !== trailingslashit($current_url)) {
        return "<a href='" . esc_url($term_url) . "'>" . esc_html($term->name) . "</a>";
    }
    return esc_html($term->name);
}
function render_combined_mode($location_tax, $service_area_tax, $mode) {
    $post_id = get_the_ID();
    $location_term_id = da_get_location_term_or_default($post_id);
    if ($location_term_id) {
        return render_location_specific($location_term_id, $location_tax, $service_area_tax, $mode);
    }
    return render_global_combined($location_tax, $service_area_tax, $mode);
}

function render_location_specific($location_term_id, $location_tax, $service_area_tax, $mode) {
    $assignments = get_option('act_to_lct_assignments', []);
    $associated_ids = [];
    foreach ($assignments as $area_id => $assigned_location_id) {
        if ((int)$assigned_location_id === (int)$location_term_id) {
            $associated_ids[] = (int)$area_id;
        }
    }

    if (empty($associated_ids)) {
        return render_global_combined($location_tax, $service_area_tax, $mode);
    }

    $service_area_links = [];
    foreach ($associated_ids as $id) {
        $term = get_term($id, $service_area_tax);
        $service_area_links[] = generate_connector_term_links($term, 'service_area_link_url');
    }
    $location_term = get_term($location_term_id, $location_tax);
    $location_link = generate_connector_term_links($location_term, 'location_link_url');
    $final_links = [];
    shuffle($service_area_links);
    if ($mode === 'list') {
        $final_links = array_merge([$location_link], $service_area_links);
    } else {
        $final_links = array_merge($service_area_links, [$location_link]);
    }
    return format_links_output($final_links, $mode);
}

function render_global_combined($location_tax, $service_area_tax, $mode) {
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
	if (($status !== 'both') && ($status !== 'multi_locations')) {
		return null;
	}
	$connector_contexts = get_option('enabled_connector_contexts');
	$location_tax = $connector_contexts['locations']['taxonomy'];
    $service_area_tax = ''; 
	if ($status === 'both'){
	$service_area_tax = $connector_contexts['service_areas']['taxonomy'];
    }
	if ($location_slug) {
		$term = get_term_by('slug', $location_slug, $location_tax);
		if ($term && !is_wp_error($term)) {
		    $location_term_id = $term->term_id;
			 if($extra_data ==="1"){
			     return ['location_term_id' => $location_term_id ];
			 }
			 else return $location_term_id;
		}
	}
	$location_term_id = dibraco_get_current_term_id_for_post($post_id, $location_tax);
    if ($location_term_id !=='') {
        if($extra_data ==="1"){
        return  [ 'location_term_id' => $location_term_id ];
        }
         else return $location_term_id;
    }
    if ($service_area_tax !== ''){
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
	return null;
}
function register_dynamic_social_media_link_shortcodes() {
    $social_keys = get_option('custom_social_media_keys'); 
    foreach ($social_keys as $key) {
        add_shortcode($key . '_link', function($atts) use ($key) {
            $atts = shortcode_atts(['extra_data'=> '', 'loc' => ''], $atts);
            $location_slug = $atts['loc'];
            $extra_data = $atts['extra_data'];
            $post_id = get_the_ID();
            $link = ''; 
            $location_term_id = da_get_location_term_or_default($post_id, $extra_data, $location_slug);
            if ($location_term_id) {
               $term_social_media_data = get_term_meta($location_term_id, 'social_media', true) ?? [];
               $link = $term_social_media_data[$key] ?? ''; 
            }
             if (empty($link)) {
                  $options = get_option('company_info');
                $company_social_media_data = $options['social_media'] ?? []; 
                $link = $company_social_media_data[$key] ?? '';
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

function da_get_address_data($location_term_id = '') {
    $address_keys = ['street_address', 'street_address_2', 'city', 'state', 'zipcode', 'addy_country'];
    $address_parts = [];
    if (!empty($location_term_id)) {
        foreach ($address_keys as $key) {
            $address_parts[$key] = get_term_meta($location_term_id, $key, true);
        }
          $validation_fields = [
            $address_parts['street_address'],
            $address_parts['city'],
            $address_parts['state']
        ];
        $valid_field_count = count(array_filter($validation_fields));
        if ($valid_field_count < 2) {
            $location_term_id = '';
        }
    }
    if (empty($location_term_id)) {
        $company_info = get_option('company_info');
        foreach ($address_keys as $key) {
            $address_parts[$key] = $company_info[$key];
        }
    }
    return $address_parts;
}

function da_address_shortcode($atts) {
    $atts = shortcode_atts(['extra_data'=> '', 'loc' => '', 'country' => ''], $atts);
    $location_slug = $atts['loc'];
    $extra_data = $atts['extra_data'];
    $post_id = get_the_ID();
    $location_term_id = da_get_location_term_or_default($post_id, $extra_data, $location_slug);
    $parts = da_get_address_data($location_term_id);
    $output = '';
    if (!empty($parts['street_address'])) {
        if (!empty($parts['street_address_2']) && strlen($parts['street_address_2']) < 10) {
            $output .= $parts['street_address'] . ' ' . $parts['street_address_2'] . '<br>';
        } else {
            $output .= $parts['street_address'] . '<br>';
            if (!empty($parts['street_address_2'])) {
                $output .= $parts['street_address_2'] . '<br>';
            }
        }
    }
    if (!empty($parts['city']) && !empty($parts['state'])) {
        $output .= $parts['city'] . ', ' . $parts['state'];
        if (!empty($parts['zipcode'])) {
            $output .= ' ' . $parts['zipcode'];
        }
        $output .= '<br>';
    }
    if ($atts['country'] === 'yes' && !empty($parts['addy_country'])) {
        $output .= $parts['addy_country'];
    }
    return $output; 
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
    $location_term_id = $post_id = get_the_ID();     
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
